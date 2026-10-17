<?php
/**
 * The admin templates of explayouts carry no inline CSS (a Content-Security-Policy without 'unsafe-inline' for
 * style-src-attr drops it); their styles are in design/admin/stylesheets/explayouts_admin.css, which design.ini
 * loads for the admin. Reads the files only, without the kernel.
 *
 * Run directly with:
 *   php tests/explayoutsadmininlinecsstest.php
 *
 *  IC-01 - no <style> block and no style="" attribute in design/admin/templates (template comments left out)
 *  IC-02 - design.ini [StylesheetSettings] BackendCSSFileList[] names explayouts_admin.css, and the file exists
 *  IC-03 - every expl-* class of the admin templates is defined in explayouts_admin.css
 *  IC-04 - the Authors tab keeps its dates and names on one line with the shared a4-nowrap class
 */

$failures = 0;
function icCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$root = dirname( __DIR__ );
$templates = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/design/admin/templates', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file )
    if ( substr( (string)$file, -4 ) === '.tpl' )
        $templates[substr( (string)$file, strlen( $root ) + 1 )] = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( (string)$file ) );
ksort( $templates );

// IC-01
$inline = array();
foreach ( $templates as $path => $source )
    if ( preg_match_all( '/<style\b/i', $source ) + preg_match_all( '/(?<![\w.\-])style\s*=\s*["\'{]/i', $source ) )
        $inline[] = $path;
icCheck( count( $templates ) > 0 && !$inline, 'IC-01 no inline CSS in ' . count( $templates ) . ' admin templates' . ( $inline ? ': ' . implode( ', ', $inline ) : '' ) );

// IC-02
$ini = file_get_contents( $root . '/settings/design.ini.append.php' );
$css = $root . '/design/admin/stylesheets/explayouts_admin.css';
icCheck( strpos( $ini, "[StylesheetSettings]\n" ) !== false && strpos( $ini, "BackendCSSFileList[]=explayouts_admin.css\n" ) !== false && is_file( $css ),
         'IC-02 design.ini loads explayouts_admin.css for the admin' );

// IC-03
$sheet = is_file( $css ) ? file_get_contents( $css ) : '';
$missing = array();
foreach ( $templates as $path => $source )
    if ( preg_match_all( '/class="([^"]*)"/', $source, $m ) )
        foreach ( $m[1] as $list )
            foreach ( preg_split( '/\s+/', trim( $list ) ) as $class )
                if ( strpos( $class, 'expl-' ) === 0 && !preg_match( '/\.' . preg_quote( $class, '/' ) . '(?![\w-])/', $sheet ) )
                    $missing[$class] = true;
icCheck( !$missing, 'IC-03 every expl-* class is defined' . ( $missing ? ': ' . implode( ', ', array_keys( $missing ) ) : '' ) );

// IC-04
$authors = isset( $templates['design/admin/templates/tabs/authors.tpl'] ) ? $templates['design/admin/templates/tabs/authors.tpl'] : '';
icCheck( substr_count( $authors, '<td class="a4-nowrap">' ) === 4 && strpos( $authors, 'white-space' ) === false,
         'IC-04 the Authors tab uses a4-nowrap for its four one-line cells' );

echo $failures ? "FAIL: $failures\n" : "PASS: all\n";
exit( $failures ? 1 : 0 );
