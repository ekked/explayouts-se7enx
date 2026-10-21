<?php
// @description Show the details of a layout: layout_info.php <layout_id>
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
if ( !isset( $scriptArguments[0] ) )
{
    echo "Usage: layout_info.php <layout_id>\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

$layoutId = (int)$scriptArguments[0];
$service = new expLayoutsCoreLayoutService();
$layout = $service->load( $layoutId );

if ( !$layout instanceof expLayoutsLayout )
{
    echo "Layout not found.\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

echo "ID:          " . $layout->attribute( 'id' ) . "\n";
echo "Identifier:  " . $layout->attribute( 'identifier' ) . "\n";
echo "Name:        " . $layout->attribute( 'name' ) . "\n";
echo "Type:        " . $layout->attribute( 'layout_type' ) . "\n";
echo "Status:      " . ( $layout->attribute( 'status' ) == 2 ? 'published' : 'draft' ) . "\n";
echo "Created:     " . date( 'Y-m-d H:i', (int)$layout->attribute( 'created' ) ) . "\n";
echo "Modified:    " . date( 'Y-m-d H:i', (int)$layout->attribute( 'modified' ) ) . "\n";
echo "Zones:\n";

$zones = expLayoutsZone::fetchByLayout( $layoutId, (int)$layout->attribute( 'status' ) );
foreach ( $zones as $zone )
{
    echo "  - " . $zone->attribute( 'identifier' ) . " (id " . $zone->attribute( 'id' ) . ")\n";
    $blocks = expLayoutsBlock::fetchByZone( $zone->attribute( 'id' ), (int)$layout->attribute( 'status' ) );
    foreach ( $blocks as $block )
        echo "      block " . $block->attribute( 'id' ) . ": " . $block->attribute( 'definition_identifier' ) . " / " . $block->attribute( 'name' ) . "\n";
}