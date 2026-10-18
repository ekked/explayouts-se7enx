<?php
/**
 * The code of extension/explayouts/modules/explayouts/rule_edit.php, moved into a class (#207 stage 1). The file extension/explayouts/modules/explayouts/rule_edit.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace Exponential\View\Extension\Explayouts\Explayouts
{

class RuleEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $http = \eZHTTPTool::instance();
        $module = $Params['Module'];

        if ( !\eZUser::currentUser()->hasAccessTo( 'explayouts', 'edit' ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
        }

        $ruleId = isset( $Params['RuleID'] ) ? (int)$Params['RuleID'] : 0;
        if ( $ruleId > 0 )
            $rule = \expLayoutsRule::fetch( $ruleId );
        else
            $rule = \expLayoutsRule::create( 0 );

        if ( !$rule )
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );

        $message = '';
        $error = '';

        if ( $http->hasPostVariable( 'SaveRule' ) )
        {
            $layoutId = (int)$http->postVariable( 'LayoutID' );
            $priority = (int)$http->postVariable( 'Priority' );
            $enabled = $http->hasPostVariable( 'Enabled' ) ? 1 : 0;

            $rule->setAttribute( 'layout_id', $layoutId );
            $rule->setAttribute( 'priority', $priority );
            $rule->setAttribute( 'enabled', $enabled );
            $rule->store();

            // remove old targets/conditions and re-create
            foreach ( $rule->targets() as $t ) { $t->remove(); }
            foreach ( $rule->conditions() as $c ) { $c->remove(); }

            // Only the types the resolver knows (expLayoutsResolver::TARGET_TYPES, CONDITION_TYPES), and only lists of
            // strings: a single posted value made count() throw a TypeError, an unknown type was stored and
            // never matched (a target) or, before the resolver refused unknown conditions, always did.
            $rejected = array();
            $targets = self::postedPairs( $http, 'TargetType', 'TargetValue', \expLayoutsResolver::TARGET_TYPES, $rejected );
            foreach ( $targets as $pair )
            {
                $t = new \expLayoutsRuleTarget( array(
                    'rule_id' => $rule->attribute( 'id' ),
                    'target_type' => $pair[0],
                    'target_value' => $pair[1],
                ) );
                $t->store();
            }

            $conditions = self::postedPairs( $http, 'ConditionType', 'ConditionValue', \expLayoutsResolver::CONDITION_TYPES, $rejected );
            foreach ( $conditions as $pair )
            {
                $c = new \expLayoutsRuleCondition( array(
                    'rule_id' => $rule->attribute( 'id' ),
                    'condition_type' => $pair[0],
                    'condition_value' => $pair[1],
                ) );
                $c->store();
            }

            // the resolver caches its answers per path: a changed rule must be looked at again
            \expLayoutsResolver::clearCache();

            $message = \ezpI18n::tr( 'design/admin/explayouts/rule_edit', 'Rule saved.' );
            if ( $rejected )
                $error = \ezpI18n::tr( 'design/admin/explayouts/rule_edit', 'Unknown target or condition types were not saved: %types', null,
                                       array( '%types' => implode( ', ', array_unique( $rejected ) ) ) );
            if ( !$targets && $enabled )
                $error = trim( $error . ' ' . \ezpI18n::tr( 'design/admin/explayouts/rule_edit', 'A rule without a target applies to no page.' ) );
        }

        $layouts = \expLayoutsLayout::fetchList( 2 );
        $targets = $rule->targets();
        $conditions = $rule->conditions();

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'rule', $rule );
        $tpl->setVariable( 'layouts', $layouts );
        $tpl->setVariable( 'targets', $targets );
        $tpl->setVariable( 'conditions', $conditions );
        $tpl->setVariable( 'message', $message );
        $tpl->setVariable( 'error', $error );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:explayouts/rule_edit.tpl' );
        $Result['left_menu'] = 'design:parts/explayouts_ui/menu.tpl';
        $Result['path'] = array( array( 'url' => 'explayouts_ui/dashboard', 'text' => \ezpI18n::tr( 'explayouts/layout', 'Layouts' ) ),
                                 array( 'url' => false,
                                        'text' => \ezpI18n::tr( 'explayouts/rule', 'Edit Rule' ) ) );
        return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }

    /**
     * The posted type/value pairs of a rule form ($typeField[] and $valueField[] by the same index): empty types are
     * skipped, types not in $known and values that are not strings are left out and their types added to $rejected.
     *
     * @param \eZHTTPTool $http
     * @param string $typeField
     * @param string $valueField
     * @param string[] $known
     * @param string[] &$rejected
     * @return array list of array( type, value )
     */
    public static function postedPairs( $http, $typeField, $valueField, array $known, array &$rejected )
    {
        $types = $http->hasPostVariable( $typeField ) ? $http->postVariable( $typeField ) : array();
        $values = $http->hasPostVariable( $valueField ) ? $http->postVariable( $valueField ) : array();
        return self::pairs( $types, $values, $known, $rejected );
    }

    /**
     * See postedPairs(), on the posted values themselves.
     *
     * @param mixed $types
     * @param mixed $values
     * @param string[] $known
     * @param string[] &$rejected
     * @return array list of array( type, value )
     */
    public static function pairs( $types, $values, array $known, array &$rejected )
    {
        $types = is_array( $types ) ? array_values( $types ) : array();
        $values = is_array( $values ) ? array_values( $values ) : array();
        $pairs = array();
        foreach ( $types as $i => $type )
        {
            $value = isset( $values[$i] ) ? $values[$i] : '';
            if ( !is_string( $type ) || !is_string( $value ) )
            {
                $rejected[] = is_string( $type ) ? $type : '?';
                continue;
            }
            $type = trim( $type );
            if ( $type === '' )
                continue;
            if ( !in_array( $type, $known, true ) )
            {
                $rejected[] = $type;
                continue;
            }
            $pairs[] = array( $type, trim( $value ) );
        }
        return $pairs;
    }
}

}
