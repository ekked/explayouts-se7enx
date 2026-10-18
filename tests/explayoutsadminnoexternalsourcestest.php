<?php
/**
 * The admin templates of explayouts load nothing from another host: no <script src>, <link href>, <iframe src>,
 * <img src> or CSS @import with an absolute http(s):// or protocol-relative // address, so the administration's
 * Content Security Policy needs no external source for them. The fonts of the layouts tab (Roboto, Material Icons)
 * come with layouts-admin.css. Reads the files only, without the kernel.
 *
 * Run directly with:
 *   php tests/explayoutsadminnoexternalsourcestest.php
 *
 *  ES-01 - no admin template (template comments left out) loads a resource from an absolute or protocol-relative URL
 *  ES-02 - the layouts tab loads no Google Fonts stylesheet
 */

$failures = 0;
function esCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$root = dirname( __DIR__ );
$bad = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/design/admin/templates', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file )
{
    if ( substr( (string)$file, -4 ) !== '.tpl' )
        continue;
    $source = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( (string)$file ) );
    if ( preg_match_all( '#<(script|link|iframe|img|source|video|audio|embed)\b[^>]*\b(src|href)\s*=\s*["\']?(https?:)?//#i', $source, $m )
         || preg_match_all( '#@import\s+(url\()?["\']?(https?:)?//#i', $source, $m ) )
        $bad[] = substr( (string)$file, strlen( $root ) + 1 );
}
esCheck( !$bad, 'ES-01 no admin template loads a resource from another host' . ( $bad ? ': ' . implode( ', ', $bad ) : '' ) );

$tab = (string)@file_get_contents( $root . '/design/admin/templates/tabs/explayouts.tpl' );
esCheck( $tab !== '' && strpos( $tab, 'fonts.googleapis.com' ) === false, 'ES-02 the layouts tab loads no Google Fonts stylesheet' );

echo $failures ? "FAIL: $failures\n" : "PASS: all\n";
exit( $failures ? 1 : 0 );
