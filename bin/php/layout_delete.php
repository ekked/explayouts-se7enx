<?php
// @description Delete a layout: layout_delete.php <layout_id>
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
if ( !isset( $scriptArguments[0] ) )
{
    echo "Usage: layout_delete.php <layout_id>\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

$layoutId = (int)$scriptArguments[0];
$service = new expLayoutsCoreLayoutService();
$result = $service->delete( $layoutId );

if ( $result )
    echo "Layout " . $layoutId . " deleted.\n";
else
{
    echo "Delete failed.\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
}