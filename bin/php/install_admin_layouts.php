#!/usr/bin/env php
<?php
/**
 * Installs the explayouts tables, the base layout and the admin layouts of
 * the admin4l design into the database of one siteaccess.
 *
 *   php extension/explayouts/bin/php/install_admin_layouts.php -s <siteaccess>
 *   DRY=1 php extension/explayouts/bin/php/install_admin_layouts.php -s <siteaccess>
 *
 * Steps, each one only if needed:
 *   1. create the explayouts_* tables from sql/mysql/schema.sql if they are missing
 *   2. install the base layout (installBaseData, idempotent)
 *   3. insert the admin layouts from share/fragments/admin_layouts_db_data.dba,
 *      only when none of its ids exists yet; one transaction, rolled back on error
 *
 * Only MySQL/MariaDB is supported by the schema file used here.
 */
require 'autoload.php';

$dry = getenv( 'DRY' ) === '1';
$extensionDir = dirname( __DIR__, 2 );

$script = eZScript::instance( array( 'description' => 'explayouts: install tables and admin layouts',
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'user' => false ) );
$script->startup();
$options = $script->getOptions( '', '', array(), false, array( 'siteaccess' => true ) );
if ( $options['siteaccess'] )
    $script->setUseSiteAccess( $options['siteaccess'] );
$script->initialize();

$cli = eZCLI::instance();
$db = eZDB::instance();
if ( !( $db instanceof eZMySQLiDB ) )
{
    $cli->error( 'Only MySQL/MariaDB is supported by this command.' );
    $script->shutdown( 1 );
}
$cli->output( 'siteaccess=' . $script->usedSiteAccess() . ' db=' . $db->DB . ( $dry ? ' (dry run)' : '' ) );

// 1) tables
$schema = file_get_contents( $extensionDir . '/sql/mysql/schema.sql' );
preg_match_all( '/CREATE TABLE (explayouts_\w+) \(.*?\)\s*[^;]*;/s', $schema, $matches, PREG_SET_ORDER );
foreach ( $matches as $stmt )
{
    $table = $stmt[1];
    if ( count( $db->arrayQuery( "SHOW TABLES LIKE '$table'" ) ) )
    {
        $cli->output( "table $table: present" );
        continue;
    }
    $cli->output( "table $table: missing, create" );
    if ( !$dry && $db->query( $stmt[0] ) === false )
    {
        $cli->error( "table $table could not be created: " . $db->errorMessage() );
        $script->shutdown( 1 );
    }
}
if ( $dry )
{
    $cli->output( 'dry run: stopping before base layout and admin layouts' );
    $script->shutdown();
    exit( 0 );
}

// 2) base layout
$result = expLayoutsFixtures::installBaseData();
$cli->output( 'base layout: ' . ( $result['message'] ?? $result['error'] ?? 'done' ) );

// 3) admin layouts
$data = array();
include $extensionDir . '/share/fragments/admin_layouts_db_data.dba';
foreach ( $data as $table => $t )
{
    if ( !in_array( 'id', $t['fields'], true ) )
        continue;
    $idIndex = array_search( 'id', $t['fields'], true );
    $ids = array();
    foreach ( $t['rows'] as $row )
        $ids[] = (int)$row[$idIndex];
    if ( !$ids )
        continue;
    $probe = $db->arrayQuery( "SELECT COUNT(*) AS c FROM $table WHERE id IN (" . implode( ',', $ids ) . ')' );
    if ( (int)$probe[0]['c'] > 0 )
    {
        $cli->error( "admin layouts: ids of $table already present, nothing was changed" );
        $script->shutdown( 1 );
    }
}

$db->begin();
try
{
    foreach ( $data as $table => $t )
    {
        $numeric = array();
        foreach ( $db->arrayQuery( "SHOW COLUMNS FROM $table" ) as $column )
            if ( preg_match( '/int|decimal|float|double/i', $column['Type'] ) )
                $numeric[$column['Field']] = true;
        foreach ( $t['rows'] as $row )
        {
            $values = array();
            foreach ( $t['fields'] as $i => $field )
            {
                $value = $row[$i];
                $values[] = ( $value === '' && isset( $numeric[$field] ) )
                    ? 'NULL'
                    : "'" . $db->escapeString( (string)$value ) . "'";
            }
            // eZDB answers false instead of throwing: a failed row rolls the whole import back
            if ( $db->query( "INSERT INTO $table (" . implode( ',', $t['fields'] ) . ') VALUES (' . implode( ',', $values ) . ')' ) === false )
                throw new RuntimeException( "a row of $table was refused: " . $db->errorMessage() );
        }
        $cli->output( "admin layouts, $table: " . count( $t['rows'] ) . ' rows' );
    }
    $db->commit();
}
catch ( Exception $e )
{
    $db->rollback();
    $cli->error( 'admin layouts failed, rolled back: ' . $e->getMessage() );
    $script->shutdown( 1 );
}
$cli->output( 'admin layouts: committed' );
$script->shutdown();
