<?php
class expLayoutsBlockHandlerFactory
{
    /**
     * Renamed block identifiers: the content component blocks were
     * ibexa_component_<type> and are exp_component_<type>. A block stored
     * under the old name still finds its definition, until
     * bin/php/updatecomponentblockidentifiers.php has renamed the rows.
     */
    static function currentIdentifier( $definitionIdentifier )
    {
        $definitionIdentifier = (string)$definitionIdentifier;
        if ( strpos( $definitionIdentifier, 'ibexa_component_' ) === 0 )
            return 'exp_component_' . substr( $definitionIdentifier, strlen( 'ibexa_component_' ) );

        return $definitionIdentifier;
    }

    static function get( $definitionIdentifier )
    {
        $ini = eZINI::instance( 'explayouts.ini' );
        $blockSection = 'BlockDefinition_' . self::currentIdentifier( $definitionIdentifier );
        if ( !$ini->hasGroup( $blockSection ) )
            return false;

        $handlerClass = $ini->variable( $blockSection, 'Handler' );
        if ( !class_exists( $handlerClass ) )
            return false;

        return new $handlerClass();
    }

    static function getAvailableBlocks()
    {
        $ini = eZINI::instance( 'explayouts.ini' );
        if ( !$ini->hasVariable( 'BlockSettings', 'AvailableBlocks' ) )
            return array();

        return $ini->variable( 'BlockSettings', 'AvailableBlocks' );
    }

    /**
     * The group of a parameter of a block definition: 'content' when [BlockDefinition_<identifier>]
     * ContentParameters[] lists it, else 'design'. A parameter inside a compound one (a child) is in the group of its
     * parent.
     *
     * @param string $definitionIdentifier
     * @param string $parameterName the name of a top-level parameter
     * @return string 'content' or 'design'
     */
    static function parameterGroup( $definitionIdentifier, $parameterName )
    {
        $info = self::getBlockInfo( $definitionIdentifier );
        return is_array( $info ) && in_array( (string)$parameterName, $info['content_parameters'], true ) ? 'content' : 'design';
    }

    static function getBlockInfo( $definitionIdentifier )
    {
        $ini = eZINI::instance( 'explayouts.ini' );
        $blockSection = 'BlockDefinition_' . self::currentIdentifier( $definitionIdentifier );
        if ( !$ini->hasGroup( $blockSection ) )
            return false;

        $name = $ini->hasVariable( $blockSection, 'Name' ) ? $ini->variable( $blockSection, 'Name' ) : $definitionIdentifier;
        $viewTypes = $ini->hasVariable( $blockSection, 'ViewTypes' ) ? $ini->variable( $blockSection, 'ViewTypes' ) : array( 'default' );
        $hasCollection = $ini->hasVariable( $blockSection, 'HasCollection' ) ? (bool)$ini->variable( $blockSection, 'HasCollection' ) : false;
        $isContainer = $ini->hasVariable( $blockSection, 'IsContainer' ) ? (bool)$ini->variable( $blockSection, 'IsContainer' ) : false;
        $placeholders = $ini->hasVariable( $blockSection, 'Placeholders' ) ? $ini->variable( $blockSection, 'Placeholders' ) : array();
        $category = $ini->hasVariable( $blockSection, 'Category' ) ? $ini->variable( $blockSection, 'Category' ) : 'standard';
        // Labels of the view types in the editor's View type select, as
        // ViewTypeNames[<view type>]=<label>; a view type without one is shown
        // by its identifier.
        $viewTypeNames = $ini->hasVariable( $blockSection, 'ViewTypeNames' ) ? $ini->variable( $blockSection, 'ViewTypeNames' ) : array();
        if ( !is_array( $viewTypeNames ) )
            $viewTypeNames = array();

        // The parameters edited in the Content tab of the layout editor, as the reference's parameters of the group
        // "content"; the others are in the Design tab (and every parameter of a definition that lists none).
        $contentParameters = $ini->hasVariable( $blockSection, 'ContentParameters' ) ? $ini->variable( $blockSection, 'ContentParameters' ) : array();
        if ( !is_array( $contentParameters ) )
            $contentParameters = array();

        return array(
            'identifier' => $definitionIdentifier,
            'name' => $name,
            'content_parameters' => array_values( array_filter( array_map( 'trim', $contentParameters ), 'strlen' ) ),
            'view_types' => $viewTypes,
            'view_type_names' => $viewTypeNames,
            'has_collection' => $hasCollection,
            'is_container' => $isContainer,
            'placeholders' => $placeholders,
            'category' => $category,
        );
    }
}
