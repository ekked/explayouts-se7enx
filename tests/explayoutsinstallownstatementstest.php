<?php
/**
 * Tests for expLayoutsInstall::ownStatements(): the schema installer runs only the statements of the
 * explayouts_* tables, never those of the kernel or sibling tables the schema files also describe.
 *
 * Run directly with:
 *   php tests/explayoutsinstallownstatementstest.php
 */

require_once __DIR__ . '/../classes/explayoutsinstall.php';

$failures = 0;
function check( $condition, $message )
{
    global $failures;
    if ( !$condition )
    {
        echo "FAIL: $message\n";
        $failures++;
    }
}

// A hand-made file: comments, kernel tables, an index, a sequence and an Oracle trigger.
$sql = "-- the layouts\nCREATE TABLE explayouts_layout (\n  id int NOT NULL\n);\n"
     . "CREATE TABLE ezurl (\n  id int NOT NULL\n);\n"
     . "CREATE TABLE eztags (\n  id int NOT NULL\n);\n"
     . "CREATE INDEX explayouts_layout_status ON explayouts_layout (status);\n"
     . "CREATE INDEX ezurl_url ON ezurl (url);\n"
     . "ALTER TABLE ONLY explayouts_layout ADD CONSTRAINT explayouts_layout_pkey PRIMARY KEY (id);\n"
     . "ALTER TABLE ONLY ezurl ADD CONSTRAINT ezurl_pkey PRIMARY KEY (id);\n"
     . "CREATE SEQUENCE explayouts_layout_s START 1;\n"
     . "CREATE SEQUENCE ezurl_s START 1;\n"
     . "DROP TABLE IF EXISTS explayouts_zone;\n"
     . "DROP TABLE IF EXISTS ezurl_object_link;\n"
     . "INSERT INTO explayouts_layout (id) VALUES (1);\n";
$own = expLayoutsInstall::ownStatements( $sql );
check( count( $own ) === 5, 'five own statements in the sample, got ' . count( $own ) . ': ' . implode( ' | ', $own ) );
foreach ( $own as $statement )
{
    check( strpos( $statement, 'explayouts_' ) !== false, "an own statement names an explayouts_ table: $statement" );
    check( !preg_match( '/\b(ezurl|eztags|exp_info_collection)\b/', $statement ), "no kernel table: $statement" );
    check( strpos( $statement, '--' ) !== 0, "leading comments are stripped: $statement" );
}
check( !preg_grep( '/^INSERT/i', $own ), 'data statements are not schema statements' );

// Every shipped schema file: each CREATE TABLE explayouts_* is kept, no kernel or sibling table is touched.
$files = glob( __DIR__ . '/../sql/*/schema.sql' );
check( count( $files ) > 0, 'schema files found' );
foreach ( $files as $file )
{
    $text = file_get_contents( $file );
    $own = expLayoutsInstall::ownStatements( $text );
    $name = basename( dirname( $file ) );
    preg_match_all( '/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?["`]?(explayouts_\w+)/i', $text, $m );
    $tables = array_unique( array_map( 'strtolower', $m[2] ) );
    $created = array();
    foreach ( $own as $statement )
    {
        check( !preg_match( '/\b(ON|TABLE|INTO)\s+(ONLY\s+)?["`]?(ez\w+|exp_info_collection)\b/i', $statement ), "$name: no kernel table: " . substr( $statement, 0, 80 ) );
        if ( preg_match( '/^CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?["`]?(explayouts_\w+)/i', $statement, $t ) )
            $created[] = strtolower( $t[2] );
    }
    sort( $tables );
    $created = array_unique( $created );
    sort( $created );
    check( $tables === $created, "$name: every explayouts_ table is created (" . implode( ',', array_diff( $tables, $created ) ) . ' missing)' );
    echo "$name: " . count( $own ) . ' own statements, ' . count( $created ) . " tables\n";
}

if ( $failures )
{
    echo "FAIL ($failures)\n";
    exit( 1 );
}
echo "PASS\n";
