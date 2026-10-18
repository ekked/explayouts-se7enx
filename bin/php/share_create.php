<?php
// @description Create a share token for a layout: share_create.php <layout_id>
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
if ( !isset( $scriptArguments[0] ) )
{
    echo "Usage: share_create.php <layout_id>\n";
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

$db = eZDB::instance();
$db->query( 'CREATE TABLE IF NOT EXISTS explayouts_share (
    id int(11) NOT NULL AUTO_INCREMENT,
    layout_id int(11) NOT NULL DEFAULT 0,
    token varchar(64) NOT NULL,
    created int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY layout_id (layout_id),
    UNIQUE KEY token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' );

$token = bin2hex( random_bytes( 32 ) );
$created = time();
$db->query( 'INSERT INTO explayouts_share (layout_id, token, created) VALUES ( '
    . $layoutId . ', \''
    . $db->escapeString( $token ) . '\', '
    . $created . ' )' );

echo "Share token for layout " . $layoutId . ": " . $token . "\n";