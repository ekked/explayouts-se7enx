<?php
/**
 * The Layouts provider of the kernel's reverse relation check, against the installed database (read only).
 *
 * Run with:
 *   php bin/php/ezexec.php extension/explayouts/tests/explayoutsreverserelationprovidertest.php --allow-root-user
 *
 *  RP-01 - content.ini registers the provider
 *  RP-02 - a path that is not digits and slashes is never queried; no paths, no rows
 *  RP-03 - a node that a published block's collection names is reported with its layout, block, link and path
 *  RP-04 - the limit caps the rows, the total counts all
 */

$failures = 0;
function rpCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$ini = eZINI::instance( 'content.ini' );
rpCheck( in_array( 'expLayoutsReverseRelationProvider', (array) $ini->variable( 'ReverseRelationSettings', 'ProviderClassList' ), true ),
         'RP-01 content.ini registers the provider' );

rpCheck( expLayoutsReverseRelationProvider::reverseRelations( array( "/1/2/' OR 1=1 --/" ), 10 ) === array( 'total' => 0, 'rows' => array() ),
         'RP-02 a path with other characters is not queried' );
rpCheck( expLayoutsReverseRelationProvider::reverseRelations( array(), 10 ) === array( 'total' => 0, 'rows' => array() ),
         'RP-02 no paths, no rows' );

$db = eZDB::instance();
$named = $db->arrayQuery( 'SELECT t.path_string AS path_string, i.value_id AS node_id FROM explayouts_collection_item i'
                          . ' JOIN explayouts_collection c ON c.id = i.collection_id AND c.status = 2'
                          . ' JOIN explayouts_block b ON b.id = c.block_id AND b.status = 2'
                          . ' JOIN explayouts_layout l ON l.id = b.layout_id AND l.status = 2'
                          . ' JOIN ezcontentobject_tree t ON t.node_id = i.value_id ORDER BY i.id', array( 'limit' => 1 ) );
if ( !$named )
{
    echo "SKIP: RP-03/RP-04 no published block names a node in this database\n";
}
else
{
    $answer = expLayoutsReverseRelationProvider::reverseRelations( array( $named[0]['path_string'] ), 100 );
    $row = is_array( $answer ) && $answer['rows'] ? $answer['rows'][0] : null;
    rpCheck( $row && $answer['total'] >= 1 && $row['to_node_id'] === (int) $named[0]['node_id']
             && $row['to_path'] === $named[0]['path_string'] && strpos( $row['url'], '/explayouts_ui_api/app#layout/' ) === 0
             && strpos( $row['label'], ': ' ) !== false && $row['type'] !== '',
             'RP-03 the node of a published block is reported: ' . ( $row ? $row['label'] : 'none' ) );
    $all = expLayoutsReverseRelationProvider::reverseRelations( array( '/1/' ), 1 );
    rpCheck( is_array( $all ) && count( $all['rows'] ) === 1 && $all['total'] >= 1, 'RP-04 the limit caps the rows (total ' . ( is_array( $all ) ? $all['total'] : '?' ) . ')' );
}

echo $failures ? "FAIL: $failures\n" : "PASS: all\n";
if ( $failures )
    exit( 1 );
