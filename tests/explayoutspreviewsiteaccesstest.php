<?php
/**
 * The siteaccess an administration renders layout previews in (expLayoutsPreviewSiteAccess), without the kernel:
 * eZINI and eZSiteAccess are stand-ins holding the settings of three sites in one installation.
 *
 * Run directly with:
 *   php tests/explayoutspreviewsiteaccesstest.php
 *
 *  PV-01 - [PreviewSettings] SiteAccess of the administration wins
 *  PV-02 - without it FrontSiteAccess, then the first of RelatedSiteAccessList[] that is not an administration, then
 *          DefaultAccess - not DefaultAccess first (the default of the whole installation, another site's)
 *  PV-03 - a siteaccess with another database is never chosen; a configured one with another database is an error
 *          with a reason, not a fall back to another site
 *  PV-04 - the current siteaccess and administrations are skipped
 */

$failures = 0;
function pvCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

class eZINI
{
    /** settings per siteaccess: name => file => group => variable => value */
    public static $settings = array();
    public $siteAccess, $file;
    public static function instance( $file = 'site.ini' ) { return new self( $GLOBALS['eZCurrentAccess']['name'], $file ); }
    public function __construct( $siteAccess, $file = 'site.ini' ) { $this->siteAccess = $siteAccess; $this->file = $file; }
    public function hasVariable( $group, $name ) { return isset( self::$settings[$this->siteAccess][$this->file][$group][$name] ); }
    public function variable( $group, $name ) { return $this->hasVariable( $group, $name ) ? self::$settings[$this->siteAccess][$this->file][$group][$name] : false; }
}
class eZSiteAccess
{
    public static function getIni( $name, $file = 'site.ini' ) { return new eZINI( $name, $file ); }
}

require_once dirname( __DIR__ ) . '/classes/explayoutspreviewsiteaccess.php';

$site = function( $database, $design, array $extra = array() )
{
    return array( 'site.ini' => array_replace_recursive( array(
        'DatabaseSettings' => array( 'DatabaseImplementation' => 'ezmysqli', 'Server' => 'localhost', 'Port' => '', 'Database' => $database ),
        'DesignSettings' => array( 'SiteDesign' => $design ),
        'SiteSettings' => array( 'DefaultAccess' => 'blog' ),
    ), $extra ) );
};
$reset = function() use ( $site )
{
    eZINI::$settings = array(
        'blog' => $site( 'db_blog', 'bootstrap5' ),
        'blog_admin' => $site( 'db_blog', 'admin4' ),
        'shop' => $site( 'db_shop', 'shop' ),
        'shop_en' => $site( 'db_shop', 'shop' ),
        'shop_admin' => $site( 'db_shop', 'admin4', array( 'SiteAccessSettings' => array( 'RelatedSiteAccessList' => array( 'shop_admin', 'shop', 'shop_en' ) ),
                                                           'SiteSettings' => array( 'FrontSiteAccess' => 'shop_en' ) ) ),
    );
    $GLOBALS['eZCurrentAccess'] = array( 'name' => 'shop_admin' );
    expLayoutsPreviewSiteAccess::$availableSiteAccesses = array_keys( eZINI::$settings );
};
$settings = function( $siteAccess, $file, $group, $name, $value ) { eZINI::$settings[$siteAccess][$file][$group][$name] = $value; };

// PV-02
$reset();
pvCheck( expLayoutsPreviewSiteAccess::resolve() === 'shop_en', 'PV-02 FrontSiteAccess, not the installation default blog' );
unset( eZINI::$settings['shop_admin']['site.ini']['SiteSettings']['FrontSiteAccess'] );
pvCheck( expLayoutsPreviewSiteAccess::resolve() === 'shop', 'PV-02 then the first related siteaccess that is not an administration' );
eZINI::$settings['shop_admin']['site.ini']['SiteAccessSettings']['RelatedSiteAccessList'] = array();
$answer = expLayoutsPreviewSiteAccess::resolve();
pvCheck( $answer === false && strpos( expLayoutsPreviewSiteAccess::$lastError, 'another database' ) !== false,
         'PV-03 DefaultAccess blog has another database: nothing, with the reason (' . expLayoutsPreviewSiteAccess::$lastError . ')' );

// PV-01, PV-03
$reset();
$settings( 'shop_admin', 'explayouts.ini', 'PreviewSettings', 'SiteAccess', 'shop' );
pvCheck( expLayoutsPreviewSiteAccess::resolve() === 'shop', 'PV-01 the configured siteaccess wins over FrontSiteAccess' );
$settings( 'shop_admin', 'explayouts.ini', 'PreviewSettings', 'SiteAccess', 'blog' );
pvCheck( expLayoutsPreviewSiteAccess::resolve() === false && strpos( expLayoutsPreviewSiteAccess::$lastError, "'blog'" ) !== false,
         'PV-03 a configured siteaccess with another database is an error, no fall back' );

// PV-04
$reset();
$settings( 'shop_admin', 'explayouts.ini', 'PreviewSettings', 'SiteAccess', 'shop_admin' );
pvCheck( expLayoutsPreviewSiteAccess::resolve() === false, 'PV-04 the current siteaccess is not its own preview' );
$reset();
$GLOBALS['eZCurrentAccess'] = array( 'name' => 'blog_admin' );
pvCheck( expLayoutsPreviewSiteAccess::resolve() === 'blog', 'PV-04 blog_admin previews in blog (DefaultAccess, same database)' );
pvCheck( expLayoutsPreviewSiteAccess::choose( array( 'blog_admin', 'shop_admin', 'blog' ), 'x', expLayoutsPreviewSiteAccess::databaseOf( new eZINI( 'blog' ) ),
                                              function( $name ) { return array( 'admin' => strpos( $name, '_admin' ) !== false,
                                                                                'database' => expLayoutsPreviewSiteAccess::databaseOf( new eZINI( $name ) ) ); } ) === 'blog',
         'PV-04 administrations are skipped' );

echo $failures ? "$failures failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
