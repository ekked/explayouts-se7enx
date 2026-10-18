<?php
/**
 * The layout import (expLayoutsImporter) and the class of the export (expLayoutsExporter), without the kernel: the
 * layout classes and eZDB are stand-ins that record what is stored.
 *
 * Run directly with:
 *   php tests/explayoutsimportertest.php
 *
 *  IM-01 - the export class is expLayoutsExporter, as the layout list calls it (it was expLayoutsExponentialorter)
 *  IM-02 - both export forms import: this extension's (version 1, parameters as a map) and the layout editor's (no
 *          version, parameters as a list of name/value pairs, an empty collection list)
 *  IM-03 - the layout is a draft whatever the export says; rules of an export are not imported
 *  IM-04 - an unknown layout type, an unknown block definition or another version imports nothing
 *  IM-05 - one transaction: a failure halfway rolls back
 */

$failures = 0;
function imCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

class imLog
{
    public static $stored = array();
    public static $parameters = array();
    public static $collections = 0;
    public static $transaction = array();
    public static $failOn = null;
}
class eZDB
{
    public static function instance() { return new self(); }
    public function begin() { imLog::$transaction[] = 'begin'; }
    public function commit() { imLog::$transaction[] = 'commit'; }
    public function rollback() { imLog::$transaction[] = 'rollback'; }
}
class imObject
{
    public $values;
    public function __construct( array $values = array() ) { $this->values = $values; }
    public function attribute( $name ) { return isset( $this->values[$name] ) ? $this->values[$name] : null; }
    public function setAttribute( $name, $value ) { $this->values[$name] = $value; }
    public function store()
    {
        if ( imLog::$failOn === static::class )
            throw new RuntimeException( 'Lost connection' );
        if ( !isset( $this->values['id'] ) )
            $this->values['id'] = count( imLog::$stored ) + 1;
        imLog::$stored[] = array( static::class, $this->values );
    }
}
class expLayoutsLayout extends imObject
{
    public static function create( $identifier ) { return new self( array( 'identifier' => $identifier, 'status' => 1 ) ); }
    public static function fetchByIdentifier( $identifier, $status ) { return false; }
}
class expLayoutsZone extends imObject
{
    public static function create( $layoutId, $identifier, $status = 1 ) { return new self( array( 'layout_id' => $layoutId, 'identifier' => $identifier, 'status' => $status ) ); }
}
class expLayoutsBlock extends imObject
{
    public static function create( $zoneId, $layoutId, $definition, $name = '' ) { return new self( array( 'zone_id' => $zoneId, 'definition_identifier' => $definition, 'view_type' => 'default', 'status' => 1 ) ); }
}
class expLayoutsBlockParameter
{
    public static function set( $blockId, $name, $value ) { imLog::$parameters[$name] = $value; }
}
class expLayoutsCollection extends imObject
{
    public static function create( $blockId, $type ) { imLog::$collections++; return new self( array( 'block_id' => $blockId, 'collection_type' => $type ) ); }
}
class expLayoutsCollectionItem extends imObject
{
    public static function create( $collectionId, $valueId, $valueType, $itemType ) { return new self( array( 'value_id' => $valueId ) ); }
}
class expLayoutsRule extends imObject {}
class expLayoutsLayoutType
{
    public static function getTypeInfo( $identifier ) { return $identifier === 'layout_1' ? array( 'zones' => array( 'main' ) ) : false; }
}
class expLayoutsBlockHandlerFactory
{
    public static function getBlockInfo( $identifier )
    {
        return in_array( $identifier, array( 'title', 'list' ), true ) ? array( 'view_types' => array( 'title', 'title_big', 'list' ) ) : false;
    }
}

require_once dirname( __DIR__ ) . '/classes/explayoutsexporter.php';
require_once dirname( __DIR__ ) . '/classes/explayoutsimporter.php';

$reset = function() { imLog::$stored = array(); imLog::$parameters = array(); imLog::$collections = 0; imLog::$transaction = array(); imLog::$failOn = null; };
$storedOf = function( $class ) { return array_values( array_map( function( $row ) { return $row[1]; }, array_filter( imLog::$stored, function( $row ) use ( $class ) { return $row[0] === $class; } ) ) ); };

// IM-01
imCheck( class_exists( 'expLayoutsExporter', false ) && method_exists( 'expLayoutsExporter', 'exportLayout' ), 'IM-01 expLayoutsExporter exists' );
imCheck( class_exists( 'expLayoutsExponentialorter', false ), 'IM-01 the old name still answers' );

// IM-02: this extension's export
$reset();
$result = expLayoutsImporter::import( array( 'version' => 1, 'identifier' => 'home', 'name' => 'Home', 'layout_type' => 'layout_1',
    'zones' => array( array( 'identifier' => 'main', 'blocks' => array(
        array( 'definition_identifier' => 'title', 'view_type' => 'title_big', 'parameters' => array( 'title' => 'Hello', 'level' => '2' ), 'collection' => false ),
    ) ) ) ) );
imCheck( !isset( $result['error'] ) && imLog::$parameters === array( 'title' => 'Hello', 'level' => '2' ), 'IM-02 version 1 with a parameter map' );
imCheck( $storedOf( 'expLayoutsBlock' )[0]['view_type'] === 'title_big', 'IM-02 a view type of the block is kept' );

// IM-02, IM-03: the layout editor's export
$reset();
$result = expLayoutsImporter::import( array( 'id' => 9, 'identifier' => 'home', 'name' => 'Home', 'layout_type' => 'layout_1', 'status' => 2,
    'zones' => array( array( 'identifier' => 'main', 'status' => 2, 'blocks' => array(
        array( 'definition_identifier' => 'title', 'view_type' => '<script>', 'status' => 2,
               'parameters' => array( array( 'name' => 'title', 'value' => 'Hi' ), array( 'name' => 'level', 'value' => '3' ) ), 'collection' => array() ),
    ) ) ),
    'rules' => array( array( 'enabled' => 1, 'targets' => array( array( 'target_type' => 'path_info_prefix', 'target_value' => '/' ) ) ) ) ) );
imCheck( !isset( $result['error'] ) && imLog::$parameters === array( 'title' => 'Hi', 'level' => '3' ), 'IM-02 no version, parameters as a list' );
imCheck( imLog::$collections === 0, 'IM-02 an empty collection list makes no collection' );
$layouts = $storedOf( 'expLayoutsLayout' );
imCheck( (int)$layouts[0]['status'] === 1, 'IM-03 the layout is a draft' );
imCheck( (int)$storedOf( 'expLayoutsZone' )[0]['status'] === 1 && (int)$storedOf( 'expLayoutsBlock' )[0]['status'] === 1, 'IM-03 its zones and blocks too' );
imCheck( $storedOf( 'expLayoutsBlock' )[0]['view_type'] === 'default', 'IM-03 an unknown view type is not taken' );
imCheck( $storedOf( 'expLayoutsRule' ) === array(), 'IM-03 no rule is imported' );
imCheck( imLog::$transaction === array( 'begin', 'commit' ), 'IM-05 in a transaction' );

// IM-04
foreach ( array(
    'layout type' => array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => '../evil', 'zones' => array() ),
    'block' => array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1', 'zones' => array( array( 'identifier' => 'main', 'blocks' => array( array( 'definition_identifier' => 'nope' ) ) ) ) ),
    'version' => array( 'version' => 2, 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1', 'zones' => array() ),
    'zones' => array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1', 'zones' => 'main' ),
) as $what => $export )
{
    $reset();
    $result = expLayoutsImporter::import( $export );
    imCheck( isset( $result['error'] ) && imLog::$stored === array() && imLog::$transaction === array(), "IM-04 refused before writing: $what" );
}

// IM-05
$reset();
imLog::$failOn = 'expLayoutsBlock';
$result = expLayoutsImporter::import( array( 'identifier' => 'x', 'name' => 'X', 'layout_type' => 'layout_1',
    'zones' => array( array( 'identifier' => 'main', 'blocks' => array( array( 'definition_identifier' => 'title' ) ) ) ) ) );
imCheck( isset( $result['error'] ) && imLog::$transaction === array( 'begin', 'rollback' ), 'IM-05 a failure halfway rolls back' );

echo $failures ? "$failures failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
