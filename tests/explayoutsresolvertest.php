<?php
/**
 * The rule matching and the cache of expLayoutsResolver, without the kernel: eZINI, eZSiteAccess, eZSys, eZDB, eZFile,
 * eZDebug and the rule and layout classes are stand-ins; the cache is written to a sandbox directory.
 *
 * Run directly with:
 *   php tests/explayoutsresolvertest.php
 *
 *  RS-01 - a condition the resolver does not know is not met (it was, so a mistyped condition made a catch-all)
 *  RS-02 - a rule without a target matches no page
 *  RS-03 - path_regex keeps the escapes of its pattern ("\/", "#"), refuses a broken pattern without a PHP warning
 *  RS-04 - a rule with a condition on the query string is not cached: the next request with another query string
 *          gets its own answer, for a match as for a non-match; a time rule whose range lies in the past is cached
 *  RS-05 - rules on the path alone are cached as before
 *  RS-06 - over CacheMaxEntries the cache loses its expired files (by modification time), then the oldest down to
 *          80 %; paths longer than CACHE_MAX_PATH_LENGTH are not written
 *  RS-08 - only a request-dependent rule whose targets name the page keeps the answer out of the cache
 *  RS-09 - a time rule naming the page: the answer is cached until its next start or end, not for CacheTTL, and the
 *          page caches are told to keep the page no longer (keepPageUntil(), on a hit too)
 *  RS-10 - a route target names the module view the path addresses: content/search, content/* for a URL alias of a
 *          content page, the front page by IndexPage, a leading siteaccess segment dropped
 *  RS-07 - the type lists name every type ruleMatches()/conditionMatches() know, also ibexa_content_type of the
 *          shipped media site data (a condition the resolver did not know was ignored, now it would never be met)
 */

$failures = 0;
function rsCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

class eZINI
{
    public static $values = array();
    public static function instance( $name = 'site.ini' ) { return new self(); }
    public function variable( $group, $name ) { return isset( self::$values[$group][$name] ) ? self::$values[$group][$name] : false; }
    public function hasVariable( $group, $name ) { return isset( self::$values[$group][$name] ); }
}
class eZSiteAccess
{
    public static function current() { return array( 'name' => 'site' ); }
}
class eZURLAliasML
{
    public static $aliases = array();
    public static function translate( &$uri ) { if ( isset( self::$aliases[$uri] ) ) { $uri = self::$aliases[$uri]; return true; } return false; }
}
class ezpRequestRuleKernel
{
    public static $calls = 0;
    public static function keepOutOfSharedCaches() { self::$calls++; }
}
class eZSys
{
    public static function requestURI() { return ''; }
}
class eZDebug
{
    public static $debug = array();
    public static function writeDebug( $message, $label = '' ) { self::$debug[] = $message; }
    public static function writeWarning( $message, $label = '' ) {}
}
class eZDB
{
    public static function instance() { return new self(); }
    public function isConnected() { return true; }
    public function errorNumber() { return 0; }
}
class eZFile
{
    public static function create( $file, $dir, $data, $atomic = false )
    {
        if ( !is_dir( $dir ) )
            mkdir( $dir, 0777, true );
        file_put_contents( $dir . '/' . $file, $data );
    }
}
class expLayoutsLayoutType
{
    public static function isAdminType( $type ) { return false; }
}
class expLayoutsLayout
{
    public $id;
    public function __construct( $id ) { $this->id = $id; }
    public static function fetch( $id ) { return $id > 0 ? new self( (int)$id ) : false; }
    public static function fetchByIdentifier( $identifier, $status ) { return false; }
    public function attribute( $name )
    {
        if ( $name === 'status' ) return 2;
        if ( $name === 'layout_type' ) return 'layout_1';
        if ( $name === 'identifier' ) return 'layout' . $this->id;
        return $this->id;
    }
}
class rsItem
{
    public $values;
    public function __construct( array $values ) { $this->values = $values; }
    public function attribute( $name ) { return $this->values[$name]; }
}
class expLayoutsRule
{
    public static $rules = array();
    public $values, $targets, $conditions;
    public function __construct( $id, $layoutId, array $targets, array $conditions = array() )
    {
        $this->values = array( 'id' => $id, 'layout_id' => $layoutId );
        $this->targets = array();
        foreach ( $targets as $t )
            $this->targets[] = new rsItem( array( 'target_type' => $t[0], 'target_value' => $t[1] ) );
        $this->conditions = array();
        foreach ( $conditions as $c )
            $this->conditions[] = new rsItem( array( 'condition_type' => $c[0], 'condition_value' => $c[1] ) );
    }
    public static function fetchEnabled() { return self::$rules; }
    public function attribute( $name ) { return $this->values[$name]; }
    public function targets() { return $this->targets; }
    public function conditions() { return $this->conditions; }
}

require_once dirname( __DIR__ ) . '/classes/explayoutsresolver.php';

$sandbox = ( getenv( 'EXPLAYOUTS_TEST_TMP' ) ?: sys_get_temp_dir() ) . '/explayouts_resolver_' . bin2hex( random_bytes( 4 ) );
mkdir( $sandbox, 0777, true );
eZINI::$values = array( 'FileSettings' => array( 'VarDir' => $sandbox ), 'ResolverSettings' => array( 'CacheTTL' => 3600 ) );
$cacheDir = $sandbox . '/cache/explayouts/resolver';
$cachedFiles = function() use ( $cacheDir ) { $f = glob( $cacheDir . '/*.php' ); return is_array( $f ) ? count( $f ) : 0; };
$layoutId = function( $answer ) { return $answer ? $answer->id : 0; };

try
{
    // RS-01
    $rule = new expLayoutsRule( 1, 5, array( array( 'path', 'news' ) ), array( array( 'sitacess', '["site"]' ) ) );
    rsCheck( expLayoutsResolver::ruleMatches( $rule, 'news' ) === false, 'RS-01 an unknown condition type is not met' );
    $rule = new expLayoutsRule( 1, 5, array( array( 'path', 'news' ) ), array( array( 'siteaccess', '["site"]' ) ) );
    rsCheck( expLayoutsResolver::ruleMatches( $rule, 'news' ) === true, 'RS-01 a known condition still decides' );

    // RS-02
    rsCheck( expLayoutsResolver::ruleMatches( new expLayoutsRule( 1, 5, array() ), 'anything' ) === false, 'RS-02 a rule without a target matches nothing' );
    rsCheck( expLayoutsResolver::ruleMatches( new expLayoutsRule( 1, 5, array( array( 'path_info_prefix', '/' ) ) ), 'anything' ) === true,
             'RS-02 the catch-all target still matches every page' );

    // RS-03
    $regex = function( $pattern, $path ) { return expLayoutsResolver::ruleMatches( new expLayoutsRule( 1, 5, array( array( 'path_regex', $pattern ) ) ), $path ); };
    rsCheck( $regex( '^news/\d+$', 'news/12' ) && !$regex( '^news/\d+$', 'news/x' ), 'RS-03 a plain pattern with a slash' );
    rsCheck( $regex( '^news\/\d+$', 'news/12' ), 'RS-03 an escaped slash keeps working' );
    rsCheck( $regex( '^a#b/c', 'a#b/c' ), 'RS-03 a pattern with # gets another delimiter' );
    $warnings = array();
    set_error_handler( function( $no, $message ) use ( &$warnings ) { $warnings[] = $message; return true; } );
    $broken = $regex( '^news/(', 'news/(' );
    restore_error_handler();
    rsCheck( $broken === false && $warnings === array(), 'RS-03 a broken pattern matches nothing and raises no warning' );
    rsCheck( !$regex( '', 'x' ), 'RS-03 an empty pattern matches nothing' );

    // RS-04
    expLayoutsResolver::clearCache();
    expLayoutsRule::$rules = array(
        new expLayoutsRule( 2, 20, array( array( 'path', 'shop' ) ), array( array( 'query_parameter', '{"parameter_name":"preview","parameter_values":["1"]}' ) ) ),
        new expLayoutsRule( 1, 10, array( array( 'path', 'shop' ) ) ),
    );
    $_GET = array();
    $first = $layoutId( expLayoutsResolver::resolve( 'shop' ) );
    $_GET = array( 'preview' => '1' );
    $second = $layoutId( expLayoutsResolver::resolve( 'shop' ) );
    rsCheck( $first === 10 && $second === 20, "RS-04 with ?preview=1 the query rule applies after a request without it ($first, $second)" );
    $_GET = array();
    rsCheck( $layoutId( expLayoutsResolver::resolve( 'shop' ) ) === 10, 'RS-04 and without it again the path rule' );
    rsCheck( $cachedFiles() === 0, 'RS-04 nothing was cached' );

    expLayoutsResolver::clearCache();
    expLayoutsRule::$rules = array(
        new expLayoutsRule( 3, 30, array( array( 'path', 'sale' ) ), array( array( 'time', '{"from":"2000-01-01","to":"2001-01-01"}' ) ) ),
    );
    rsCheck( expLayoutsResolver::resolve( 'sale' ) === false && $cachedFiles() === 1, 'RS-04 a time rule whose range is over decides nothing new: cached' );

    // RS-05
    expLayoutsResolver::clearCache();
    expLayoutsRule::$rules = array( new expLayoutsRule( 1, 10, array( array( 'path', 'about' ) ) ) );
    rsCheck( $layoutId( expLayoutsResolver::resolve( 'about' ) ) === 10 && $cachedFiles() === 1, 'RS-05 a path rule is cached' );

    // RS-06
    expLayoutsResolver::clearCache();
    expLayoutsRule::$rules = array();
    rsCheck( expLayoutsResolver::resolve( str_repeat( 'x', expLayoutsResolver::CACHE_MAX_PATH_LENGTH + 1 ) ) === false && $cachedFiles() === 0,
             'RS-06 a very long path is not cached' );
    for ( $i = 0; $i < 12; $i++ )
    {
        // the content is not read: the age comes from the modification time
        eZFile::create( "entry$i.php", $cacheDir, '<?php return array();' );
        touch( $cacheDir . "/entry$i.php", $i < 4 ? time() - 10000 : time() - 100 + $i );
    }
    $removed = expLayoutsResolver::pruneCache( 5, 3600 );
    $left = array_map( 'basename', glob( $cacheDir . '/*.php' ) );
    sort( $left );
    rsCheck( $removed === 8 && $left === array( 'entry10.php', 'entry11.php', 'entry8.php', 'entry9.php' ),
             'RS-06 over 5: the expired ones by modification time, then the oldest down to 80 % (' . implode( ',', $left ) . ')' );
    rsCheck( expLayoutsResolver::pruneCache( 5, 3600 ) === 0, 'RS-06 nothing to prune under the limit' );

    // RS-08
    expLayoutsResolver::clearCache();
    expLayoutsRule::$rules = array(
        new expLayoutsRule( 4, 40, array( array( 'path', 'campaign' ) ), array( array( 'time', '{"from":"2000-01-01","to":"2001-01-01"}' ) ) ),
        new expLayoutsRule( 1, 10, array( array( 'path', 'about' ) ) ),
    );
    rsCheck( $layoutId( expLayoutsResolver::resolve( 'about' ) ) === 10 && $cachedFiles() === 1,
             'RS-08 a time rule for another page does not keep this page from being cached' );
    expLayoutsResolver::clearCache();
    rsCheck( expLayoutsResolver::resolve( 'campaign' ) === false && $cachedFiles() === 1, 'RS-08 the page of a time rule that is over is cached' );

    // RS-09
    expLayoutsResolver::clearCache();
    $start = time() + 120;
    expLayoutsRule::$rules = array(
        new expLayoutsRule( 5, 50, array( array( 'path', 'spring' ) ), array( array( 'time', json_encode( array( 'from' => date( 'Y-m-d H:i:s', $start ), 'to' => null ) ) ) ) ),
        new expLayoutsRule( 1, 10, array( array( 'path', 'spring' ) ) ),
    );
    ezpRequestRuleKernel::$calls = 0;
    rsCheck( $layoutId( expLayoutsResolver::resolve( 'spring' ) ) === 10 && $cachedFiles() === 1, 'RS-09 before the start: the regular rule, cached' );
    $files = glob( $cacheDir . '/*.php' );
    $entry = include $files[0];
    rsCheck( $entry['expires'] === $start && $entry['changes'] === $start, 'RS-09 the entry expires at the start of the time condition, not after CacheTTL' );
    rsCheck( ezpRequestRuleKernel::$calls === 1, 'RS-09 the page caches keep the page no longer than the start' );
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime( true ) + 1;
    rsCheck( $layoutId( expLayoutsResolver::resolve( 'spring' ) ) === 10 && ezpRequestRuleKernel::$calls === 2, 'RS-09 a cache hit tells the page caches too' );
    $rule = new expLayoutsRule( 6, 60, array( array( 'path', 'x' ) ), array( array( 'time', '{"from":"2000-01-01 00:00:00","to":"' . date( 'Y-m-d H:i:s', $start ) . '"}' ) ) );
    rsCheck( expLayoutsResolver::nextConditionChange( $rule, time() ) === $start + 1, 'RS-09 a range ends a second after its "to"' );
    expLayoutsResolver::clearCache();
    $far = time() + 30 * 86400;
    expLayoutsRule::$rules = array(
        new expLayoutsRule( 5, 50, array( array( 'path', 'summer' ) ), array( array( 'time', json_encode( array( 'from' => date( 'Y-m-d H:i:s', $far ) ) ) ) ) ),
    );
    ezpRequestRuleKernel::$calls = 0;
    expLayoutsResolver::resolve( 'summer' );
    rsCheck( ezpRequestRuleKernel::$calls === 0, 'RS-09 a start further away than the page cache keeps a page leaves the page caches alone' );
    unset( $_SERVER['REQUEST_TIME_FLOAT'] );

    // RS-10
    eZINI::$values['SiteSettings'] = array( 'IndexPage' => '/content/view/full/2' );
    eZINI::$values['SiteAccessSettings'] = array( 'AvailableSiteAccessList' => array( 'site', 'admin' ) );
    eZURLAliasML::$aliases = array( 'fitness/running' => 'content/view/full/80' );
    $route = function( $value, $path ) { return expLayoutsResolver::ruleMatches( new expLayoutsRule( 1, 5, array( array( 'route', $value ) ) ), $path ); };
    rsCheck( $route( 'content/search', 'content/search' ) && !$route( 'content/search', 'content/view/full/2' ), 'RS-10 content/search names the search page only' );
    rsCheck( $route( 'content/view', 'fitness/running' ) && $route( '/content/*', 'fitness/running' ), 'RS-10 a URL alias is the content view it stands for' );
    rsCheck( $route( 'content/view', 'home' ) && $route( 'content/view', '' ), 'RS-10 the front page is its IndexPage' );
    rsCheck( $route( 'user/*', 'site/user/login' ) && !$route( 'user/*', 'fitness/running' ), 'RS-10 a siteaccess segment is dropped' );
    rsCheck( !$route( '', 'content/search' ) && $route( '*', 'content/search' ), 'RS-10 an empty value names nothing, * every module' );

    // RS-07
    foreach ( array( 'path', 'path_prefix', 'path_info_prefix', 'path_regex', 'node', 'subtree', 'route', 'module', 'module_view' ) as $type )
        rsCheck( in_array( $type, expLayoutsResolver::TARGET_TYPES, true ), "RS-07 target type $type listed" );
    foreach ( array( 'siteaccess', 'class', 'content_type', 'ibexa_content_type', 'query_parameter', 'route_parameter', 'time' ) as $type )
        rsCheck( in_array( $type, expLayoutsResolver::CONDITION_TYPES, true ), "RS-07 condition type $type listed" );
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
