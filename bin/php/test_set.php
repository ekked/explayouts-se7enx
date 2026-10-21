<?php
// @description Check whether the database driver runs SET @var statements (development only)
if ( !isset( $script ) && !isset( $options ) ) { require __DIR__ . '/inc/standalone.php'; return; }

ini_set( 'display_errors', 1 );
error_reporting( E_ALL );

try
{
    $db = eZDB::instance();
    $queries = array(
        'SET @foo = 1',
        'SELECT @foo AS foo',
    );
    foreach ( $queries as $q )
    {
        echo 'Running: ' . $q . "\n";
        $r = $db->query( $q );
        var_dump( $r );
    }
}
catch ( Exception $e )
{
    echo 'Exception: ' . $e->getMessage() . "\n";
}
