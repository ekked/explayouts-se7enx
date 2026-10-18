<?php
/**
 * The code of extension/explayouts/modules/explayouts/rule_list.php, moved into a class (#207 stage 1). The file extension/explayouts/modules/explayouts/rule_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace Exponential\View\Extension\Explayouts\Explayouts
{

class RuleList extends \Exponential\Runnable\ModuleView
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

        if ( !\eZUser::currentUser()->hasAccessTo( 'explayouts', 'read' ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
        }

        $message = '';
        $error = '';

        if ( \eZUser::currentUser()->hasAccessTo( 'explayouts', 'edit' ) && $http->hasPostVariable( 'DeleteRule' ) )
        {
            $deleteId = (int)$http->postVariable( 'DeleteRuleID' );
            $rule = \expLayoutsRule::fetch( $deleteId );
            if ( $rule )
            {
                foreach ( $rule->targets() as $t ) $t->remove();
                foreach ( $rule->conditions() as $c ) $c->remove();
                $rule->remove();
                \expLayoutsResolver::clearCache();
                $message = \ezpI18n::tr( 'design/admin/explayouts/rule_list', 'Rule deleted.' );
            }
            else
            {
                $error = \ezpI18n::tr( 'design/admin/explayouts/rule_list', 'Rule not found.' );
            }
        }

        if ( \eZUser::currentUser()->hasAccessTo( 'explayouts', 'edit' ) && $http->hasPostVariable( 'CopyRule' ) )
        {
            $copyId = (int)$http->postVariable( 'CopyRuleID' );
            $rule = \expLayoutsRule::fetch( $copyId );
            if ( $rule )
            {
                $newRule = \expLayoutsRule::create( (int)$rule->attribute( 'layout_id' ) );
                $newRule->setAttribute( 'priority', (int)$rule->attribute( 'priority' ) );
                $newRule->setAttribute( 'enabled', (int)$rule->attribute( 'enabled' ) );
                $newRule->store();

                foreach ( $rule->targets() as $target )
                {
                    $newTarget = \expLayoutsRuleTarget::create( $newRule->attribute( 'id' ), $target->attribute( 'target_type' ), $target->attribute( 'target_value' ) );
                    $newTarget->store();
                }

                foreach ( $rule->conditions() as $condition )
                {
                    $newCondition = \expLayoutsRuleCondition::create( $newRule->attribute( 'id' ), $condition->attribute( 'condition_type' ), $condition->attribute( 'condition_value' ) );
                    $newCondition->store();
                }

                \expLayoutsResolver::clearCache();
                $message = \ezpI18n::tr( 'design/admin/explayouts/rule_list', 'Rule copied.' );
            }
            else
            {
                $error = \ezpI18n::tr( 'design/admin/explayouts/rule_list', 'Rule not found.' );
            }
        }

        // all rules: the template marks the disabled ones (a disabled rule was not listed, so it could not be
        // found again to switch it back on)
        $rules = \expLayoutsRule::fetchAll();

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'rules', $rules );
        $tpl->setVariable( 'message', $message );
        $tpl->setVariable( 'error', $error );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:explayouts/rule_list.tpl' );
        $Result['left_menu'] = 'design:parts/explayouts_ui/menu.tpl';
        $Result['path'] = array( array( 'url' => 'explayouts_ui/dashboard', 'text' => \ezpI18n::tr( 'explayouts/layout', 'Layouts' ) ),
                                 array( 'url' => false,
                                        'text' => \ezpI18n::tr( 'explayouts/rule', 'Layout Rules' ) ) );
        return $this->viewResult( isset( $Result ) ? $Result : null,  $Result );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
