<?php
/**
 * The views of the classic layouts administration (explayouts/rule_edit, rule_list, layout_edit, block_edit, layout_list),
 * the Layouts tab of the node view and the scripts under bin/php, without the kernel: the kernel classes and the
 * layout classes are stand-ins that record what is written; the resolver is the real one on a sandbox directory.
 *
 * Run directly with:
 *   php tests/explayoutsviewstest.php
 *
 *  VW-01 - every write of rule_edit, rule_list, layout_edit (Publish) and layout_list (Delete) empties the resolver
 *          cache (expLayoutsResolver::clearCache(), which renews the admin generation)
 *  VW-02 - rule_edit saves nothing when a target or condition type is unknown or an enabled rule has no target (and
 *          says why), saves rule, targets and conditions in one transaction otherwise, takes a posted value that is not a
 *          list without a TypeError, and says what it left out
 *  VW-03 - rule_list lists the disabled rules too
 *  VW-04 - layout_edit adds only blocks of the settings and saves no draft with an unknown layout type; block_edit
 *          saves nothing (no "saved" beside the error) for an unknown view type or a parameter posted as a list;
 *          block_edit keeps the view type unless the posted one is one of the block's
 *  VW-05 - the Layouts tab of the node view is drawn and filled only for users with explayouts/read
 *  VW-06 - the scripts under bin/php read their argument when run through bin/php/ezexec.php
 *  VW-07 - every block of the site (BlockDefinition_*, not admin_*) has a template in the standard design, also the
 *          content components exp_component_* (a layout with one failed on the missing template)
 */

$failures = 0;
function vwCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$root = dirname( __DIR__ );

/** what the stand-ins were asked to do */
class vwLog
{
    public static $writes = array();
    public static $generationBumps = 0;
}

eval( 'namespace Exponential\\Runnable; abstract class ModuleView { abstract public function run( array $scope ); protected function viewResult( $result, $returned ) { return empty( $result ) ? $returned : $result; } }' );

class eZINI
{
    public static $values = array();
    public static function instance( $name = 'site.ini' ) { return new self(); }
    public function variable( $group, $name ) { return isset( self::$values[$group][$name] ) ? self::$values[$group][$name] : false; }
    public function hasVariable( $group, $name ) { return isset( self::$values[$group][$name] ); }
}
class eZDB
{
    public static $log = array();
    public static function instance() { return new self(); }
    public function begin() { self::$log[] = 'begin'; }
    public function commit() { self::$log[] = 'commit'; return true; }
}
class eZFile
{
    public static function create( $file, $dir, $data, $atomic = false )
    {
        if ( $file === 'admin_generation' )
            vwLog::$generationBumps++;
        if ( !is_dir( $dir ) )
            mkdir( $dir, 0777, true );
        file_put_contents( $dir . '/' . $file, $data );
    }
}
class eZHTTPTool
{
    public static $post = array();
    public static function instance() { return new self(); }
    public function hasPostVariable( $name ) { return array_key_exists( $name, self::$post ); }
    public function postVariable( $name ) { return array_key_exists( $name, self::$post ) ? self::$post[$name] : null; }
}
class eZUser
{
    public static function currentUser() { return new self(); }
    public function hasAccessTo( $module, $function = false ) { return array( 'accessWord' => 'yes' ); }
}
class eZError { const KERNEL_ACCESS_DENIED = 1; const KERNEL_NOT_FOUND = 2; }
class ezpI18n
{
    public static function tr( $context, $source, $comment = null, $arguments = null ) { return strtr( $source, (array)$arguments ); }
}
class eZTemplate
{
    public static $last = array();
    public static function factory() { self::$last = array(); return new self(); }
    public function setVariable( $name, $value ) { self::$last[$name] = $value; }
    public function fetch( $uri ) { return ''; }
}
class vwModule
{
    public function handleError( $code, $kind ) { return array( 'error' => $code ); }
    public function redirectTo( $uri ) { return array( 'redirect' => $uri ); }
}

/** a persistent object stand-in */
class vwObject
{
    public $values;
    public function __construct( array $values = array() ) { $this->values = $values; }
    public function attribute( $name ) { return isset( $this->values[$name] ) ? $this->values[$name] : null; }
    public function setAttribute( $name, $value ) { $this->values[$name] = $value; }
    public function store() { if ( !isset( $this->values['id'] ) ) $this->values['id'] = 900 + count( vwLog::$writes ); vwLog::$writes[] = 'store ' . static::class; }
    public function remove() { vwLog::$writes[] = 'remove ' . static::class; }
}
class expLayoutsRuleTarget extends vwObject
{
    public static $stored = array();
    public function store() { parent::store(); self::$stored[] = $this->values; }
    public static function create( $ruleId, $type, $value ) { return new self( array( 'rule_id' => $ruleId, 'target_type' => $type, 'target_value' => $value ) ); }
}
class expLayoutsRuleCondition extends vwObject
{
    public static $stored = array();
    public function store() { parent::store(); self::$stored[] = $this->values; }
    public static function create( $ruleId, $type, $value ) { return new self( array( 'rule_id' => $ruleId, 'condition_type' => $type, 'condition_value' => $value ) ); }
}
class expLayoutsRule extends vwObject
{
    public static $all = array();
    public static function fetch( $id ) { foreach ( self::$all as $r ) if ( (int)$r->attribute( 'id' ) === (int)$id ) return $r; return false; }
    public static function create( $layoutId ) { return new self( array( 'layout_id' => $layoutId, 'priority' => 0, 'enabled' => 1 ) ); }
    public static function fetchAll() { return self::$all; }
    public static function fetchEnabled() { return array_values( array_filter( self::$all, function( $r ) { return (int)$r->attribute( 'enabled' ) === 1; } ) ); }
    public function targets() { return array(); }
    public function conditions() { return array(); }
}
class expLayoutsLayout extends vwObject
{
    public static $layouts = array();
    public static function fetch( $id ) { return isset( self::$layouts[$id] ) ? self::$layouts[$id] : false; }
    public static function fetchList( $status ) { return array(); }
    public static function create( $identifier ) { return new self( array( 'identifier' => $identifier, 'status' => 0, 'layout_type' => '' ) ); }
    public function publish() { $this->values['status'] = 2; vwLog::$writes[] = 'publish'; }
}
class expLayoutsZone extends vwObject
{
    public static $zones = array();
    public static function fetchByLayout( $layoutId, $status = null ) { return array_values( array_filter( self::$zones, function( $z ) use ( $layoutId ) { return (int)$z->attribute( 'layout_id' ) === (int)$layoutId; } ) ); }
    public static function fetch( $id ) { foreach ( self::$zones as $z ) if ( (int)$z->attribute( 'id' ) === (int)$id ) return $z; return false; }
    public static function create( $layoutId, $identifier, $status = 1 ) { return new self( array( 'layout_id' => $layoutId, 'identifier' => $identifier, 'status' => $status ) ); }
}
class expLayoutsBlock extends vwObject
{
    public static $created = array();
    public static $blocks = array();
    public static function fetchByZone( $zoneId, $status = null ) { return array(); }
    public static function fetch( $id ) { return isset( self::$blocks[$id] ) ? self::$blocks[$id] : false; }
    public static function create( $zoneId, $layoutId, $definition, $name = '' ) { self::$created[] = $definition; return new self( array( 'zone_id' => $zoneId, 'layout_id' => $layoutId, 'definition_identifier' => $definition ) ); }
}
class expLayoutsBlockParameter
{
    public static function fetchByBlock( $id ) { return array(); }
    public static function set( $blockId, $name, $value ) {}
}
class expLayoutsCollection
{
    public static function fetchByBlock( $id ) { return false; }
}
class expLayoutsBlockHandlerFactory
{
    public static function getAvailableBlocks() { return array( 'title' ); }
    public static function get( $identifier )
    {
        return $identifier === 'title' ? new class { public function getParameters() { return array( 'title' => array( 'type' => 'string' ) ); } } : false;
    }
    public static function getBlockInfo( $identifier )
    {
        return $identifier === 'title' ? array( 'identifier' => 'title', 'name' => 'Title', 'view_types' => array( 'title', 'title_big' ), 'has_collection' => false ) : false;
    }
}
class expLayoutsLayoutType
{
    public static function getTypeInfo( $identifier ) { return in_array( $identifier, array( 'layout_1', 'layout_2' ), true ) ? array( 'zones' => array() ) : false; }
    public static function getZones( $identifier ) { return array(); }
    public static function getAvailableTypes() { return array(); }
    public static function isAdminType( $identifier ) { return false; }
}
class expLayoutsCoreLayoutService
{
    public static $loaded = array();
    public function load( $id ) { self::$loaded[] = $id; return false; }
    public function listAll() { return array(); }
}
class expLayoutsCoreRuleService
{
    public static $asked = array();
    public function listByLayout( $id ) { self::$asked[] = $id; return array(); }
}

require_once $root . '/classes/explayoutsresolver.php';
foreach ( array( 'rule_edit', 'rule_list', 'layout_edit', 'block_edit', 'layout_list' ) as $view )
    require_once $root . '/classes/runnable/views/explayouts/' . $view . '.php';

$sandbox = ( getenv( 'EXPLAYOUTS_TEST_TMP' ) ?: sys_get_temp_dir() ) . '/explayouts_views_' . bin2hex( random_bytes( 4 ) );
mkdir( $sandbox, 0777, true );
eZINI::$values = array( 'FileSettings' => array( 'VarDir' => $sandbox ) );

$run = function( $class, array $params, array $post )
{
    eZHTTPTool::$post = $post;
    vwLog::$writes = array();
    vwLog::$generationBumps = 0;
    $params['Module'] = new vwModule();
    $view = new $class();
    return $view->run( array( 'Params' => $params ) );
};
$ns = '\\Exponential\\View\\Extension\\Explayouts\\Explayouts\\';

try
{
    // VW-01, VW-02: rule_edit
    expLayoutsRule::$all = array( new expLayoutsRule( array( 'id' => 7, 'layout_id' => 3, 'priority' => 10, 'enabled' => 1 ) ) );
    $run( $ns . 'RuleEdit', array( 'RuleID' => 7 ), array( 'SaveRule' => 1, 'LayoutID' => 3, 'Priority' => 10, 'Enabled' => 1,
        'TargetType' => array( 'path', 'evil', 'path_regex' ), 'TargetValue' => array( 'news', 'x', '^a/b' ),
        'ConditionType' => array( 'siteaccess', 'sitacess' ), 'ConditionValue' => array( '["site"]', '["site"]' ) ) );
    vwCheck( vwLog::$writes === array() && expLayoutsRuleTarget::$stored === array() && vwLog::$generationBumps === 0,
             'VW-02 with an unknown type nothing is saved or removed' );
    vwCheck( strpos( (string)eZTemplate::$last['error'], 'evil' ) !== false && strpos( (string)eZTemplate::$last['error'], 'sitacess' ) !== false
             && (string)eZTemplate::$last['message'] === '', 'VW-02 the refused types are named, no "saved"' );
    $run( $ns . 'RuleEdit', array( 'RuleID' => 7 ), array( 'SaveRule' => 1, 'LayoutID' => 3, 'Priority' => 10, 'Enabled' => 1 ) );
    vwCheck( vwLog::$writes === array() && strpos( (string)eZTemplate::$last['error'], 'without a target' ) !== false, 'VW-02 an enabled rule without a target is not saved' );
    eZDB::$log = array();
    $run( $ns . 'RuleEdit', array( 'RuleID' => 7 ), array( 'SaveRule' => 1, 'LayoutID' => 3, 'Priority' => 10, 'Enabled' => 1,
        'TargetType' => array( 'path', 'path_regex' ), 'TargetValue' => array( 'news', '^a/b' ),
        'ConditionType' => array( 'siteaccess' ), 'ConditionValue' => array( '["site"]' ) ) );
    vwCheck( vwLog::$generationBumps === 1, 'VW-01 rule_edit Save empties the resolver cache' );
    $types = array_map( function( $t ) { return $t['target_type']; }, expLayoutsRuleTarget::$stored );
    $conditionTypes = array_map( function( $c ) { return $c['condition_type']; }, expLayoutsRuleCondition::$stored );
    vwCheck( $types === array( 'path', 'path_regex' ) && $conditionTypes === array( 'siteaccess' ) && eZDB::$log === array( 'begin', 'commit' ),
             'VW-02 a valid form is saved in one transaction' );
    expLayoutsRuleTarget::$stored = array();
    $thrown = null;
    try
    {
        $run( $ns . 'RuleEdit', array( 'RuleID' => 7 ), array( 'SaveRule' => 1, 'LayoutID' => 3, 'Priority' => 10, 'TargetType' => 'path', 'TargetValue' => 'x' ) );
    }
    catch ( Throwable $e )
    {
        $thrown = $e;
    }
    vwCheck( $thrown === null && expLayoutsRuleTarget::$stored === array(), 'VW-02 a type posted as a single value is no TypeError and stores nothing'
             . ( $thrown ? ' (' . get_class( $thrown ) . ': ' . $thrown->getMessage() . ')' : '' ) );

    // VW-01, VW-03: rule_list
    expLayoutsRule::$all = array( new expLayoutsRule( array( 'id' => 7, 'enabled' => 1 ) ), new expLayoutsRule( array( 'id' => 8, 'enabled' => 0 ) ) );
    $run( $ns . 'RuleList', array(), array() );
    vwCheck( count( eZTemplate::$last['rules'] ) === 2, 'VW-03 rule_list lists the disabled rule too' );
    $run( $ns . 'RuleList', array(), array( 'DeleteRule' => 1, 'DeleteRuleID' => 8 ) );
    vwCheck( vwLog::$generationBumps === 1, 'VW-01 rule_list Delete empties the resolver cache' );
    $run( $ns . 'RuleList', array(), array( 'CopyRule' => 1, 'CopyRuleID' => 7 ) );
    vwCheck( vwLog::$generationBumps === 1, 'VW-01 rule_list Copy empties the resolver cache' );

    // VW-01, VW-04: layout_edit
    expLayoutsLayout::$layouts = array( 3 => new expLayoutsLayout( array( 'id' => 3, 'identifier' => 'l3', 'status' => 1, 'layout_type' => 'layout_1' ) ) );
    expLayoutsZone::$zones = array( new expLayoutsZone( array( 'id' => 31, 'layout_id' => 3, 'identifier' => 'main' ) ) );
    $run( $ns . 'LayoutEdit', array( 'LayoutID' => 3 ), array( 'Publish' => 1 ) );
    vwCheck( vwLog::$generationBumps === 1, 'VW-01 layout_edit Publish empties the resolver cache' );
    expLayoutsBlock::$created = array();
    $run( $ns . 'LayoutEdit', array( 'LayoutID' => 3 ), array( 'AddBlock' => 1, 'ZoneID' => 31, 'DefinitionIdentifier' => '../../evil' ) );
    vwCheck( expLayoutsBlock::$created === array(), 'VW-04 layout_edit adds no block of an unknown definition' );
    $run( $ns . 'LayoutEdit', array( 'LayoutID' => 3 ), array( 'AddBlock' => 1, 'ZoneID' => 31, 'DefinitionIdentifier' => 'title' ) );
    vwCheck( expLayoutsBlock::$created === array( 'title' ), 'VW-04 layout_edit adds a block of the settings' );
    $run( $ns . 'LayoutEdit', array( 'LayoutID' => 3 ), array( 'SaveDraft' => 1, 'Identifier' => 'l3', 'Name' => 'L', 'LayoutType' => 'no_such_type' ) );
    vwCheck( expLayoutsLayout::$layouts[3]->attribute( 'layout_type' ) === 'layout_1' && vwLog::$writes === array()
             && (string)eZTemplate::$last['message'] === '', 'VW-04 layout_edit saves no draft with an unknown layout type' );
    $run( $ns . 'LayoutEdit', array( 'LayoutID' => 3 ), array( 'SaveDraft' => 1, 'Identifier' => 'l3', 'Name' => 'L', 'LayoutType' => 'layout_2' ) );
    vwCheck( expLayoutsLayout::$layouts[3]->attribute( 'layout_type' ) === 'layout_2', 'VW-04 layout_edit takes a known layout type' );

    // VW-04: block_edit
    expLayoutsBlock::$blocks = array( 5 => new expLayoutsBlock( array( 'id' => 5, 'definition_identifier' => 'title', 'view_type' => 'title' ) ) );
    $run( $ns . 'BlockEdit', array( 'BlockID' => 5 ), array( 'SaveBlock' => 1, 'Name' => 'B', 'ViewType' => '../../x' ) );
    vwCheck( expLayoutsBlock::$blocks[5]->attribute( 'view_type' ) === 'title' && vwLog::$writes === array()
             && (string)eZTemplate::$last['message'] === '', 'VW-04 block_edit saves nothing for an unknown view type' );
    $run( $ns . 'BlockEdit', array( 'BlockID' => 5 ), array( 'SaveBlock' => 1, 'Name' => 'Changed', 'ViewType' => 'title_big', 'Parameter_title' => array( 'x' ) ) );
    vwCheck( expLayoutsBlock::$blocks[5]->attribute( 'name' ) === null && vwLog::$writes === array() && (string)eZTemplate::$last['error'] !== '',
             'VW-04 block_edit saves nothing for a parameter posted as a list' );
    $run( $ns . 'BlockEdit', array( 'BlockID' => 5 ), array( 'SaveBlock' => 1, 'Name' => 'B', 'ViewType' => 'title_big' ) );
    vwCheck( expLayoutsBlock::$blocks[5]->attribute( 'view_type' ) === 'title_big', 'VW-04 block_edit takes a view type of the block' );

    // VW-01: layout_list
    $run( $ns . 'LayoutList', array(), array( 'DeleteLayout' => 1, 'DeleteLayoutID' => 3 ) );
    vwCheck( vwLog::$generationBumps === 1, 'VW-01 layout_list Delete empties the resolver cache' );

    // VW-05
    $tabIni = (string)file_get_contents( $root . '/settings/admininterface.ini.append.php' );
    vwCheck( (bool)preg_match( '/\[AdditionalTab_layouts\][^\[]*HeaderTemplate=explayouts_header\.tpl/', $tabIni ), 'VW-05 the tab has a header template' );
    $header = (string)@file_get_contents( $root . '/design/admin/templates/tabs/explayouts_header.tpl' );
    $access = "fetch( 'user', 'has_access_to', hash( 'module', 'explayouts', 'function', 'read' ) )";
    vwCheck( strpos( $header, $access ) !== false && strpos( $header, '<li id="node-tab-{$tab}"' ) > strpos( $header, $access ),
             'VW-05 the header draws the tab only with explayouts/read' );
    foreach ( array( 'admin', 'admin4' ) as $design )
    {
        $tab = (string)file_get_contents( $root . "/design/$design/templates/tabs/explayouts.tpl" );
        vwCheck( strpos( $tab, $access ) !== false && strpos( $tab, $access ) < strpos( $tab, 'layout_summary_for_node' ),
                 "VW-05 the $design tab fetches nothing without explayouts/read" );
    }

    // VW-06
    foreach ( array( 'layout_info.php' => 'expLayoutsCoreLayoutService', 'rule_list.php' => 'expLayoutsCoreRuleService' ) as $scriptName => $service )
    {
        $vwScriptPath = $root . '/bin/php/' . $scriptName;
        $options = array( 'arguments' => array( $vwScriptPath, '42' ) );
        expLayoutsCoreLayoutService::$loaded = array();
        expLayoutsCoreRuleService::$asked = array();
        $runScript = function() use ( $vwScriptPath, $options ) { ob_start(); $result = include $vwScriptPath; return array( $result, ob_get_clean() ); };
        list( , $output ) = $runScript();
        $asked = $service === 'expLayoutsCoreLayoutService' ? expLayoutsCoreLayoutService::$loaded : expLayoutsCoreRuleService::$asked;
        vwCheck( $asked === array( 42 ) && strpos( $output, 'Usage' ) === false, "VW-06 $scriptName reads its argument under ezexec" );
    }
    $vwScriptPath = $root . '/bin/php/block_list.php';
    $options = array( 'arguments' => array( $vwScriptPath, '3' ) );
    $runScript = function() use ( $vwScriptPath, &$options ) { ob_start(); include $vwScriptPath; return ob_get_clean(); };
    vwCheck( strpos( $runScript(), 'Usage' ) === false, 'VW-06 block_list.php reads its argument under ezexec' );
    $options = array( 'arguments' => array( $vwScriptPath ) );
    vwCheck( strpos( $runScript(), 'Usage' ) !== false, 'VW-06 without an argument it says how to call it' );

    // VW-07
    preg_match_all( '/^\[BlockDefinition_([a-z0-9_]+)\]/m', (string)file_get_contents( $root . '/settings/explayouts.ini.append.php' ), $matches );
    $missing = array();
    foreach ( $matches[1] as $definition )
    {
        if ( strpos( $definition, 'admin_' ) !== 0 && !is_file( $root . '/design/standard/templates/explayouts/block/' . $definition . '.tpl' ) )
            $missing[] = $definition;
    }
    vwCheck( count( $matches[1] ) > 40 && $missing === array(), 'VW-07 every site block has a standard template' . ( $missing ? ' (missing: ' . implode( ', ', $missing ) . ')' : '' ) );
    $component = (string)@file_get_contents( $root . '/design/standard/templates/explayouts/block/exp_component.tpl' );
    vwCheck( strpos( $component, '{$block.definition_identifier|wash}' ) !== false && strpos( $component, '{$block.view_type|wash}' ) !== false,
             'VW-07 the component template prints only washed values' );
}
finally
{
    $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sandbox, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $it as $file )
        $file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
    rmdir( $sandbox );
}

echo $failures ? "$failures failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
