<?php
/**
 * Tests the admin layouts the extension ships (share/fragments/admin_layouts_db_data.dba) against its own settings
 * (settings/explayouts.ini.append.php), without a kernel or a database:
 *
 * - every block of the stock admin layouts has a block definition and is listed in [AdminBlockSettings] AvailableBlocks[];
 * - the tab menu sits in the topmenu zone of every stock layout, so admin4l draws its header from the zones;
 * - the right column of admin_3col is the toolbars admin_right and admin_developer, in admin4's order;
 * - SiteAccessMatch[] covers the admin siteaccesses named <site>_admin, and not the public siteaccesses.
 *
 * Run directly with:
 *   php tests/explayoutsadminseedtest.php
 */

$failures = 0;
$checks = 0;
function check( $condition, $message )
{
    global $failures, $checks;
    ++$checks;
    if ( !$condition )
    {
        echo "FAIL: $message\n";
        $failures++;
    }
}

/** The settings of an INI file as eZINI reads them: a 'Key[]' line without a value empties the array. */
function readIni( $file )
{
    $settings = array();
    $section = false;
    foreach ( file( $file, FILE_IGNORE_NEW_LINES ) as $line )
    {
        $line = trim( $line );
        if ( $line === '' || $line[0] === '#' )
            continue;
        if ( preg_match( '/^\[(.+)\]$/', $line, $m ) )
        {
            $section = $m[1];
            continue;
        }
        if ( $section === false || !preg_match( '/^([A-Za-z0-9_\-]+)(\[\])?=?(.*)$/', $line, $m ) )
            continue;
        if ( $m[2] === '[]' )
        {
            if ( strpos( $line, '=' ) === false )
                $settings[$section][$m[1]] = array();
            else
                $settings[$section][$m[1]][] = $m[3];
        }
        else
            $settings[$section][$m[1]] = $m[3];
    }
    return $settings;
}

$ini = readIni( __DIR__ . '/../settings/explayouts.ini.append.php' );
include __DIR__ . '/../share/fragments/admin_layouts_db_data.dba';

/** The rows of a table of the fragment as arrays keyed by field name */
function rows( $data, $table )
{
    $rows = array();
    foreach ( $data[$table]['rows'] as $row )
        $rows[] = array_combine( $data[$table]['fields'], $row );
    return $rows;
}

$layouts = array();
foreach ( rows( $data, 'explayouts_layout' ) as $layout )
    $layouts[$layout['id']] = $layout['identifier'];
$zones = array();
foreach ( rows( $data, 'explayouts_zone' ) as $zone )
    $zones[$zone['id']] = $zone;
$blocksByZone = array();
$available = isset( $ini['AdminBlockSettings']['AvailableBlocks'] ) ? $ini['AdminBlockSettings']['AvailableBlocks'] : array();
check( count( $available ) > 0, '[AdminBlockSettings] AvailableBlocks[] is read from the settings' );
foreach ( rows( $data, 'explayouts_block' ) as $block )
{
    $identifier = $block['definition_identifier'];
    check( isset( $ini['BlockDefinition_' . $identifier] ), "block $identifier has a [BlockDefinition_$identifier]" );
    check( in_array( $identifier, $available, true ), "block $identifier is in [AdminBlockSettings] AvailableBlocks[]" );
    check( isset( $zones[$block['zone_id']] ), "block {$block['id']} is in a shipped zone" );
    check( isset( $zones[$block['zone_id']] ) && $zones[$block['zone_id']]['layout_id'] === $block['layout_id'], "block {$block['id']} is in a zone of its own layout" );
    $blocksByZone[$layouts[$block['layout_id']]][$zones[$block['zone_id']]['identifier']][(int)$block['position']] = $identifier;
}
foreach ( $blocksByZone as &$byZone )
    foreach ( $byZone as &$byPosition )
        ksort( $byPosition );
unset( $byZone, $byPosition );

foreach ( array( 'admin_3col', 'admin_2col', 'admin_full' ) as $layout )
{
    check( isset( $blocksByZone[$layout]['topmenu'] ) && array_values( $blocksByZone[$layout]['topmenu'] ) === array( 'admin_tab_menu' ), "$layout: the topmenu zone holds the tab menu" );
    check( isset( $blocksByZone[$layout]['header'] ) && !in_array( 'admin_tab_menu', $blocksByZone[$layout]['header'], true ), "$layout: the tab menu is not in the header zone" );
}
check( isset( $blocksByZone['admin_3col']['right'] ) && array_values( $blocksByZone['admin_3col']['right'] ) === array( 'admin_toolbar_right', 'admin_toolbar_developer' ), 'admin_3col: the right column is admin_right, then admin_developer' );
foreach ( array( 'admin_toolbar_right', 'admin_toolbar_developer' ) as $identifier )
{
    check( isset( $ini['BlockDefinition_' . $identifier]['Handler'] ) && $ini['BlockDefinition_' . $identifier]['Handler'] === 'expLayoutsAdminPartBlockHandler', "$identifier is an admin part block" );
    check( isset( $ini['BlockDefinition_' . $identifier]['Group'] ) && $ini['BlockDefinition_' . $identifier]['Group'] === 'admin', "$identifier belongs to the admin group" );
}

// The matching of expLayoutsResolver::isAdminSiteAccess(): fnmatch over SiteAccessMatch[].
$patterns = isset( $ini['AdminLayoutSettings']['SiteAccessMatch'] ) ? $ini['AdminLayoutSettings']['SiteAccessMatch'] : array();
$matches = function( $name ) use ( $patterns )
{
    foreach ( $patterns as $pattern )
        if ( $pattern !== '' && fnmatch( $pattern, $name ) )
            return true;
    return false;
};
foreach ( array( 'admin', 'admin_eng', 'site_admin', 'shop_admin', 'admintest_admin4l', 'editor' ) as $name )
    check( $matches( $name ), "SiteAccessMatch[] covers '$name'" );
foreach ( array( 'site', 'eng', 'shop', 'administrator', 'siteadmin' ) as $name )
    check( !$matches( $name ), "SiteAccessMatch[] leaves out '$name'" );

echo $failures ? "FAIL: $failures of $checks checks\n" : "PASS: $checks admin layout seed checks\n";
exit( $failures ? 1 : 0 );
