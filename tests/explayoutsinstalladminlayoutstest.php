<?php
/**
 * The data bin/php/install_admin_layouts.php works with, without a database:
 *
 *  IA-01 - the command's pattern finds a CREATE TABLE statement in sql/mysql/schema.sql for every table of
 *          share/fragments/admin_layouts_db_data.dba
 *  IA-02 - every field of the fragment is a column of that table, and every row has one value per field
 *  IA-03 - the ids of a table with an id field are unique, so the "already present" probe works (a table without one,
 *          block parameters and rule conditions, hangs on the ids of another)
 *
 * Run directly with:
 *   php tests/explayoutsinstalladminlayoutstest.php
 *
 * @license GPL-2.0-or-later
 */

$failures = 0;
function iaCheck( $condition, $message )
{
    global $failures;
    if ( !$condition )
    {
        echo "FAIL: $message\n";
        $failures++;
    }
}

$extensionDir = dirname( __DIR__ );
$schema = file_get_contents( $extensionDir . '/sql/mysql/schema.sql' );
// the same pattern as the command
preg_match_all( '/CREATE TABLE (explayouts_\w+) \(.*?\)\s*[^;]*;/s', $schema, $matches, PREG_SET_ORDER );
$statements = array();
foreach ( $matches as $stmt )
    $statements[$stmt[1]] = $stmt[0];

$data = array();
include $extensionDir . '/share/fragments/admin_layouts_db_data.dba';
iaCheck( count( $data ) > 0, 'IA-00 the fragment has tables' );

foreach ( $data as $table => $t )
{
    iaCheck( isset( $statements[$table] ), "IA-01 schema.sql creates $table" );
    if ( !isset( $statements[$table] ) )
        continue;
    // the column list of the whole statement (the pattern of the command stops at the first ")", the rest follows)
    $start = strpos( $schema, "CREATE TABLE $table (" );
    $end = strpos( $schema, ';', $start );
    $full = substr( $schema, $start, $end - $start );
    preg_match_all( '/^\s+([a-z_]+)\s+[a-z]/m', $full, $columns );
    $columns = array_diff( $columns[1], array( 'key', 'primary', 'unique' ) );
    foreach ( $t['fields'] as $field )
        iaCheck( in_array( $field, $columns, true ), "IA-02 $table.$field is a column of schema.sql" );
    foreach ( $t['rows'] as $n => $row )
        iaCheck( count( $row ) === count( $t['fields'] ), "IA-02 $table row $n has one value per field" );

    $idIndex = array_search( 'id', $t['fields'], true );
    if ( $idIndex === false )
        continue;
    $ids = array();
    foreach ( $t['rows'] as $row )
        $ids[] = (int)$row[$idIndex];
    iaCheck( count( $ids ) === count( array_unique( $ids ) ), "IA-03 the ids of $table are unique" );
}

if ( $failures )
{
    echo "$failures failure(s)\n";
    exit( 1 );
}
echo "all passed\n";
