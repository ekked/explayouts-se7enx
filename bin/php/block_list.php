<?php
// @description List the blocks of a layout: block_list.php <layout_id>
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
if ( !isset( $scriptArguments[0] ) )
{
    echo "Usage: block_list.php <layout_id>\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

$layoutId = (int)$scriptArguments[0];
$layout = expLayoutsLayout::fetch( $layoutId );

if ( !$layout instanceof expLayoutsLayout )
{
    echo "Layout not found.\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

$zones = expLayoutsZone::fetchByLayout( $layoutId, (int)$layout->attribute( 'status' ) );
foreach ( $zones as $zone )
{
    $blocks = expLayoutsBlock::fetchByZone( $zone->attribute( 'id' ), (int)$layout->attribute( 'status' ) );
    foreach ( $blocks as $block )
    {
        echo $zone->attribute( 'identifier' ) . "\t"
            . $block->attribute( 'id' ) . "\t"
            . $block->attribute( 'definition_identifier' ) . "\t"
            . $block->attribute( 'name' ) . "\t"
            . $block->attribute( 'view_type' ) . "\n";
    }
}