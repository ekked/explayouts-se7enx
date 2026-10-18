<?php
/**
 * Imports a layout export, the one of this extension (expLayoutsExporter, "version": 1, parameters as a map of name
 * and value) and the one of the layout editor (explayouts_ui_api, no version, parameters as a list of name/value
 * pairs, rules). The layout is always a draft; its layout type and the definitions of its blocks must be ones of the
 * settings; rules of an export come along disabled and with known target and condition types only (enabled, they
 * would take pages over as soon as the draft is published). A zone is linked to a shared layout by its identifier
 * only: a row id of another installation names an unrelated layout here. All in one transaction: an import that
 * fails halfway leaves nothing behind. validate() is used by the layout editor's import as well.
 */
class expLayoutsImporter
{
    static function importJson( $json )
    {
        $data = json_decode( $json, true );
        if ( !is_array( $data ) )
            return array( 'error' => 'Invalid JSON provided.' );

        return self::import( $data );
    }

    /**
     * The problems of an export before anything is written: an empty list when it can be imported.
     *
     * @param array $data
     * @return string[]
     */
    static function validate( array $data )
    {
        // the layout editor's export carries no version; this extension's carries 1
        if ( isset( $data['version'] ) && (int)$data['version'] !== 1 )
            return array( 'Unsupported export version. Only version 1 is supported.' );

        foreach ( array( 'identifier', 'name', 'layout_type' ) as $key )
        {
            if ( !isset( $data[$key] ) )
                return array( 'Missing required field: ' . $key );
            if ( !is_scalar( $data[$key] ) || trim( (string)$data[$key] ) === '' )
                return array( 'Invalid field: ' . $key );
        }
        if ( isset( $data['zones'] ) && !is_array( $data['zones'] ) )
            return array( 'Invalid export: zones must be a list.' );
        if ( isset( $data['rules'] ) && !is_array( $data['rules'] ) )
            return array( 'Invalid export: rules must be a list.' );
        if ( !is_string( $data['layout_type'] ) || expLayoutsLayoutType::getTypeInfo( $data['layout_type'] ) === false )
            return array( 'Unknown layout type: ' . $data['layout_type'] );

        $errors = array();
        $optionalText = function( array $item, $key ) { return !isset( $item[$key] ) || is_scalar( $item[$key] ); };
        foreach ( isset( $data['zones'] ) ? $data['zones'] : array() as $zoneData )
        {
            if ( !is_array( $zoneData ) || !isset( $zoneData['identifier'] ) || !is_scalar( $zoneData['identifier'] )
                 || !$optionalText( $zoneData, 'position' ) || !$optionalText( $zoneData, 'linked_layout_identifier' )
                 || !$optionalText( $zoneData, 'linked_zone_identifier' )
                 || ( isset( $zoneData['blocks'] ) && !is_array( $zoneData['blocks'] ) ) )
            {
                $errors[] = 'Invalid zone in export.';
                continue;
            }
            foreach ( isset( $zoneData['blocks'] ) ? $zoneData['blocks'] : array() as $blockData )
            {
                $definition = is_array( $blockData ) && isset( $blockData['definition_identifier'] ) ? $blockData['definition_identifier'] : null;
                if ( !is_string( $definition ) || expLayoutsBlockHandlerFactory::getBlockInfo( $definition ) === false )
                {
                    $errors[] = 'Unknown block type: ' . ( is_scalar( $definition ) ? $definition : '?' );
                    continue;
                }
                foreach ( array( 'name', 'view_type', 'position' ) as $key )
                {
                    if ( !$optionalText( $blockData, $key ) )
                        $errors[] = "Invalid block field: $key";
                }
                if ( isset( $blockData['parameters'] ) && !is_array( $blockData['parameters'] ) )
                    $errors[] = 'Invalid block field: parameters';
                if ( isset( $blockData['collection'] ) && $blockData['collection'] !== false && !is_array( $blockData['collection'] ) )
                    $errors[] = 'Invalid block field: collection';
            }
        }
        return array_values( array_unique( $errors ) );
    }

    /**
     * The parameters of an exported block as name => value, from either form: a map (this extension's export) or a
     * list of array( 'name' => ..., 'value' => ... ) (the layout editor's).
     *
     * @param mixed $parameters
     * @return array
     */
    static function parameterMap( $parameters )
    {
        if ( !is_array( $parameters ) )
            return array();
        $map = array();
        foreach ( $parameters as $key => $parameter )
        {
            if ( is_array( $parameter ) && isset( $parameter['name'] ) && is_scalar( $parameter['name'] ) )
                $map[(string)$parameter['name']] = isset( $parameter['value'] ) && is_scalar( $parameter['value'] ) ? (string)$parameter['value'] : '';
            elseif ( is_string( $key ) && ( is_scalar( $parameter ) || $parameter === null ) )
                $map[$key] = (string)$parameter;
        }
        return $map;
    }

    static function import( array $data )
    {
        $errors = self::validate( $data );
        if ( $errors )
            return array( 'error' => implode( ' ', $errors ) );

        $db = eZDB::instance();
        $db->begin();
        try
        {
            $result = self::write( $data );
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Layout import failed: ' . $e->getMessage(), __METHOD__ );
            // a failed query may have ended the transaction already (eZDB rolls back on an error)
            if ( $db->transactionCounter() > 0 )
                $db->rollback();
            return array( 'error' => 'Import failed.' );
        }
        if ( !$db->commit() )
        {
            eZDebug::writeError( 'Layout import failed: the transaction was not committed', __METHOD__ );
            return array( 'error' => 'Import failed.' );
        }
        return $result;
    }

    /**
     * The id of the published shared layout an exported zone links to, by its identifier; 0 when there is none.
     * The row id of the export is not used: on another installation it names an unrelated layout.
     *
     * @param array $zoneData
     * @return int
     */
    static function linkedLayoutId( array $zoneData )
    {
        if ( !isset( $zoneData['linked_layout_identifier'] ) || !is_scalar( $zoneData['linked_layout_identifier'] )
             || (string)$zoneData['linked_layout_identifier'] === '' )
            return 0;
        $linkedLayout = expLayoutsLayout::fetchByIdentifier( (string)$zoneData['linked_layout_identifier'], 2 );
        return $linkedLayout ? (int)$linkedLayout->attribute( 'id' ) : 0;
    }

    /**
     * Imports the rules of an export for $layoutId: disabled, with the target and condition types the resolver
     * knows only.
     *
     * @param array $rules
     * @param int $layoutId
     */
    static function importRules( array $rules, $layoutId )
    {
        foreach ( $rules as $ruleData )
        {
            if ( !is_array( $ruleData ) )
                continue;
            $rule = expLayoutsRule::create( $layoutId, isset( $ruleData['priority'] ) && is_numeric( $ruleData['priority'] ) ? (int)$ruleData['priority'] : null );
            $rule->setAttribute( 'enabled', 0 );
            $rule->store();
            $ruleId = (int)$rule->attribute( 'id' );
            foreach ( isset( $ruleData['targets'] ) && is_array( $ruleData['targets'] ) ? $ruleData['targets'] : array() as $target )
            {
                $type = is_array( $target ) && isset( $target['target_type'] ) && is_string( $target['target_type'] ) ? $target['target_type'] : '';
                $value = is_array( $target ) && isset( $target['target_value'] ) && is_scalar( $target['target_value'] ) ? (string)$target['target_value'] : '';
                if ( in_array( $type, expLayoutsResolver::TARGET_TYPES, true ) )
                    expLayoutsRuleTarget::create( $ruleId, $type, $value )->store();
            }
            foreach ( isset( $ruleData['conditions'] ) && is_array( $ruleData['conditions'] ) ? $ruleData['conditions'] : array() as $condition )
            {
                $type = is_array( $condition ) && isset( $condition['condition_type'] ) && is_string( $condition['condition_type'] ) ? $condition['condition_type'] : '';
                $value = is_array( $condition ) && isset( $condition['condition_value'] ) && is_scalar( $condition['condition_value'] ) ? (string)$condition['condition_value'] : '';
                if ( in_array( $type, expLayoutsResolver::CONDITION_TYPES, true ) )
                    expLayoutsRuleCondition::create( $ruleId, $type, $value )->store();
            }
        }
    }

    protected static function write( array $data )
    {
        $baseIdentifier = preg_replace( '/[^a-zA-Z0-9_-]/', '_', (string)$data['identifier'] );
        $uniqueIdentifier = 'import_' . time() . '_' . $baseIdentifier;

        $layout = expLayoutsLayout::create( $uniqueIdentifier );
        if ( !$layout )
            throw new RuntimeException( 'Could not create layout.' );

        $layout->setAttribute( 'name', (string)$data['name'] . ' (imported)' );
        $layout->setAttribute( 'layout_type', $data['layout_type'] );
        $layout->setAttribute( 'shared', !empty( $data['shared'] ) ? 1 : 0 );
        // always a draft, whatever the export says
        $layout->setAttribute( 'status', 1 );
        $layout->setAttribute( 'created', time() );
        $layout->setAttribute( 'modified', time() );
        $layout->store();

        $layoutId = (int)$layout->attribute( 'id' );
        $zoneMap = array();

        foreach ( isset( $data['zones'] ) ? $data['zones'] : array() as $zoneData )
        {
            $zone = expLayoutsZone::create( $layoutId, (string)$zoneData['identifier'], 1 );
            if ( isset( $zoneData['position'] ) )
                $zone->setAttribute( 'position', (int)$zoneData['position'] );
            // by the shared layout's identifier only (linkedLayoutId())
            $linkedLayoutId = self::linkedLayoutId( $zoneData );

            if ( $linkedLayoutId > 0 )
            {
                $zone->setAttribute( 'linked_layout_id', $linkedLayoutId );
                $zone->setAttribute( 'linked_zone_identifier',
                    isset( $zoneData['linked_zone_identifier'] ) && $zoneData['linked_zone_identifier'] !== null
                        ? (string)$zoneData['linked_zone_identifier']
                        : (string)$zoneData['identifier'] );
            }
            $zone->store();

            $oldZoneId = isset( $zoneData['id'] ) ? (int)$zoneData['id'] : 0;
            if ( $oldZoneId > 0 )
                $zoneMap[$oldZoneId] = (int)$zone->attribute( 'id' );

            if ( isset( $zoneData['blocks'] ) && is_array( $zoneData['blocks'] ) )
            {
                foreach ( $zoneData['blocks'] as $blockData )
                {
                    self::importBlock( $blockData, $zone->attribute( 'id' ), $layoutId );
                }
            }
        }

        if ( isset( $data['rules'] ) )
            self::importRules( $data['rules'], $layoutId );

        return array( 'layout_id' => $layoutId, 'layout' => $layout );
    }

    static function importBlock( array $blockData, $zoneId, $layoutId )
    {
        $name = isset( $blockData['name'] ) && is_scalar( $blockData['name'] ) ? (string)$blockData['name']
            : ( isset( $blockData['definition_identifier'] ) && is_string( $blockData['definition_identifier'] ) ? $blockData['definition_identifier'] : 'block' );
        $definition = isset( $blockData['definition_identifier'] ) ? $blockData['definition_identifier'] : 'text';

        $block = expLayoutsBlock::create( $zoneId, $layoutId, $definition, $name );
        // a view type of the block's definition, else the block's default
        if ( isset( $blockData['view_type'] ) && is_string( $blockData['view_type'] ) )
        {
            $info = expLayoutsBlockHandlerFactory::getBlockInfo( $definition );
            $viewTypes = $info && is_array( $info['view_types'] ) ? $info['view_types'] : array( 'default' );
            if ( in_array( $blockData['view_type'], $viewTypes, true ) )
                $block->setAttribute( 'view_type', $blockData['view_type'] );
        }
        if ( isset( $blockData['position'] ) )
            $block->setAttribute( 'position', (int)$blockData['position'] );
        $block->store();

        $blockId = (int)$block->attribute( 'id' );

        if ( isset( $blockData['parameters'] ) )
        {
            foreach ( self::parameterMap( $blockData['parameters'] ) as $paramName => $paramValue )
            {
                expLayoutsBlockParameter::set( $blockId, $paramName, $paramValue );
            }
        }

        // the layout editor exports an empty list for a block without a collection
        if ( isset( $blockData['collection'] ) && is_array( $blockData['collection'] ) && $blockData['collection'] )
        {
            $collectionData = $blockData['collection'];
            $collection = expLayoutsCollection::create( $blockId, isset( $collectionData['collection_type'] ) && is_string( $collectionData['collection_type'] ) ? $collectionData['collection_type'] : 'manual' );
            if ( isset( $collectionData['offset_value'] ) )
                $collection->setAttribute( 'offset_value', (int)$collectionData['offset_value'] );
            if ( isset( $collectionData['limit_value'] ) )
                $collection->setAttribute( 'limit_value', (int)$collectionData['limit_value'] );
            $collection->store();

            $collectionId = (int)$collection->attribute( 'id' );
            if ( isset( $collectionData['items'] ) && is_array( $collectionData['items'] ) )
            {
                foreach ( $collectionData['items'] as $itemData )
                {
                    if ( !is_array( $itemData ) )
                        continue;
                    $item = expLayoutsCollectionItem::create(
                        $collectionId,
                        isset( $itemData['value_id'] ) ? (int)$itemData['value_id'] : 0,
                        isset( $itemData['value_type'] ) && is_string( $itemData['value_type'] ) ? $itemData['value_type'] : 'ez_content',
                        isset( $itemData['item_type'] ) && is_string( $itemData['item_type'] ) ? $itemData['item_type'] : 'manual'
                    );
                    if ( isset( $itemData['position'] ) )
                        $item->setAttribute( 'position', (int)$itemData['position'] );
                    $item->store();
                }
            }
        }

        return $block;
    }
}
