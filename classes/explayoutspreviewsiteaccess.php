<?php
/**
 * The public siteaccess an administration renders layouts in: the layout preview (explayouts_ui/layout_preview) and
 * the Load more of a list block called from the administration (expLayoutsAjaxLoadMoreServer).
 *
 * It was site.ini [SiteSettings] DefaultAccess. In an installation that serves several sites from one tree that is
 * the default of the whole installation, not the site of the administration in use: the preview of one site's
 * layout ran with another site's settings, design and var directory, while the database connection stayed the one
 * of the administration (two installations mixed in one request, and templates that do not exist there).
 *
 * In this order, the first that qualifies:
 *
 *   1. explayouts.ini [PreviewSettings] SiteAccess of the administration (when set, it must qualify, or nothing does)
 *   2. site.ini [SiteSettings] FrontSiteAccess of the administration
 *   3. site.ini [SiteAccessSettings] RelatedSiteAccessList[] of the administration: the first that is not the
 *      administration itself and not an administration (a SiteDesign beginning with "admin")
 *   4. site.ini [SiteSettings] DefaultAccess
 *
 * A siteaccess qualifies when it is not the current one, is not an administration, and uses the same database
 * (DatabaseSettings Server, Port, Database and DatabaseImplementation): the preview switches settings and design, not
 * the database connection. None qualifies: false, and the caller says so instead of rendering.
 *
 * @license GPL-2.0-or-later
 */
class expLayoutsPreviewSiteAccess
{
    /** The database settings that must be the same for the preview siteaccess */
    const DATABASE_KEYS = array( 'DatabaseImplementation', 'Server', 'Port', 'Database' );

    /** Why resolve() found nothing, for the message of the caller */
    public static $lastError = '';

    /** AvailableSiteAccessList[] as read from the settings; set in place of them (tests) */
    public static $availableSiteAccesses = null;

    /**
     * @return string|false the name of the siteaccess, or false (see $lastError)
     */
    public static function resolve()
    {
        $current = isset( $GLOBALS['eZCurrentAccess']['name'] ) ? (string)$GLOBALS['eZCurrentAccess']['name'] : '';
        $siteIni = eZINI::instance( 'site.ini' );
        $layoutsIni = eZINI::instance( 'explayouts.ini' );

        $configured = $layoutsIni->hasVariable( 'PreviewSettings', 'SiteAccess' )
            ? trim( (string)$layoutsIni->variable( 'PreviewSettings', 'SiteAccess' ) ) : '';

        $candidates = array();
        if ( $configured === '' )
        {
            if ( $siteIni->hasVariable( 'SiteSettings', 'FrontSiteAccess' ) )
                $candidates[] = $siteIni->variable( 'SiteSettings', 'FrontSiteAccess' );
            if ( $siteIni->hasVariable( 'SiteAccessSettings', 'RelatedSiteAccessList' ) )
                $candidates = array_merge( $candidates, (array)$siteIni->variable( 'SiteAccessSettings', 'RelatedSiteAccessList' ) );
            if ( $siteIni->hasVariable( 'SiteSettings', 'DefaultAccess' ) )
                $candidates[] = $siteIni->variable( 'SiteSettings', 'DefaultAccess' );
        }
        else
        {
            $candidates[] = $configured;
        }

        $currentDatabase = self::databaseOf( $siteIni );
        return self::choose( $candidates, $current, $currentDatabase, function( $name ) {
            $ini = eZSiteAccess::getIni( $name, 'site.ini' );
            return array(
                'admin' => strpos( (string)$ini->variable( 'DesignSettings', 'SiteDesign' ), 'admin' ) === 0,
                'database' => self::databaseOf( $ini ),
            );
        }, $configured !== '' );
    }

    /**
     * The choice itself, without reading settings (testable on its own).
     *
     * @param array $candidates names in the order of preference
     * @param string $current the current siteaccess
     * @param array $currentDatabase see databaseOf()
     * @param callable $describe name => array( 'admin' => bool, 'database' => array )
     * @param bool $configured whether the one candidate is the configured one
     * @return string|false
     */
    public static function choose( array $candidates, $current, array $currentDatabase, $describe, $configured = false )
    {
        self::$lastError = '';
        $available = self::availableSiteAccesses();
        $seen = array();
        foreach ( $candidates as $name )
        {
            $name = is_scalar( $name ) ? trim( (string)$name ) : '';
            if ( $name === '' || $name === $current || isset( $seen[$name] ) )
                continue;
            $seen[$name] = true;
            if ( $available !== null && !in_array( $name, $available, true ) )
            {
                self::$lastError = "The siteaccess '$name' is not in AvailableSiteAccessList.";
                continue;
            }
            $info = call_user_func( $describe, $name );
            if ( !empty( $info['admin'] ) )
            {
                self::$lastError = "The siteaccess '$name' is an administration.";
                continue;
            }
            if ( $info['database'] !== $currentDatabase )
            {
                self::$lastError = "The siteaccess '$name' uses another database than '$current'.";
                continue;
            }
            self::$lastError = '';
            return $name;
        }
        if ( self::$lastError === '' )
            self::$lastError = $configured
                ? 'explayouts.ini [PreviewSettings] SiteAccess names the current siteaccess.'
                : "No public siteaccess found for '$current' (explayouts.ini [PreviewSettings] SiteAccess).";
        return false;
    }

    /**
     * @param eZINI $ini a site.ini
     * @return array the database settings of DATABASE_KEYS
     */
    public static function databaseOf( $ini )
    {
        $database = array();
        foreach ( self::DATABASE_KEYS as $key )
            $database[$key] = $ini->hasVariable( 'DatabaseSettings', $key ) ? (string)$ini->variable( 'DatabaseSettings', $key ) : '';
        return $database;
    }

    /**
     * AvailableSiteAccessList[] without siteaccess overrides (as eZSiteAccess::match() reads it).
     *
     * @return string[]
     */
    protected static function availableSiteAccesses()
    {
        if ( self::$availableSiteAccesses !== null )
            return self::$availableSiteAccesses;
        $global = new eZINI( 'site.ini', 'settings', null, null, true );
        return array_map( 'strval', (array)$global->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' ) );
    }
}
