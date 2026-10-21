<?php
// @description List rules the resolver ignores or never matches: enabled rules without a target, targets and conditions of an unknown type (read only): check_rules.php
//
// Run through bin/php/ezexec.php with the siteaccess of the installation to check, for example
//   php bin/php/ezexec.php -s <siteaccess> extension/explayouts/bin/php/check_rules.php
// A rule without a target matches no page, and a condition of an unknown type is never met (see doc/FAQ.md).

$problems = 0;
foreach ( expLayoutsRule::fetchAll() as $rule )
{
    $id = (int)$rule->attribute( 'id' );
    $label = "rule $id (layout " . (int)$rule->attribute( 'layout_id' ) . ', ' . ( (int)$rule->attribute( 'enabled' ) === 1 ? 'enabled' : 'disabled' ) . ')';
    $targets = $rule->targets();
    if ( (int)$rule->attribute( 'enabled' ) === 1 && count( $targets ) === 0 )
    {
        echo "$label: no target, matches no page\n";
        $problems++;
    }
    foreach ( $targets as $target )
    {
        $type = (string)$target->attribute( 'target_type' );
        if ( !in_array( $type, expLayoutsResolver::TARGET_TYPES, true ) )
        {
            echo "$label: target of unknown type '$type', never matches\n";
            $problems++;
        }
    }
    foreach ( $rule->conditions() as $condition )
    {
        $type = (string)$condition->attribute( 'condition_type' );
        if ( !in_array( $type, expLayoutsResolver::CONDITION_TYPES, true ) )
        {
            echo "$label: condition of unknown type '$type', never met\n";
            $problems++;
        }
    }
}

echo $problems === 0 ? "No problems found.\n" : "$problems problem(s) found.\n";
if ( $problems > 0 && isset( $script ) )
    $script->setExitCode( 1 );
