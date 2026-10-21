<?php
/**
 * Tests that every helper of bin/php can run on its own (./console ext:explayouts:<name>) as well as through
 * bin/php/ezexec.php. The helpers written for ezexec ended with 'Class "..." not found' when ./console ran them,
 * because nothing had started Exponential. Each helper either starts it itself (requires autoload.php) or begins with
 * the line that hands it to bin/php/inc/standalone.php, before its first statement, and never ends with exit (which
 * would skip the shutdown).
 *
 * No kernel, no database:
 *   php extension/explayouts/tests/explayoutsbinhelperstest.php
 */

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

$guard = "if ( !isset( \$script ) ) { require __DIR__ . '/inc/standalone.php'; return; }";
$dir = dirname( __DIR__ ) . '/bin/php';
$helpers = glob( $dir . '/*.php' );
check( count( $helpers ) > 10, 'the helpers are found' );

foreach ( $helpers as $file )
{
    $name = basename( $file );
    $source = file_get_contents( $file );
    if ( strpos( $source, "'autoload.php'" ) !== false )
        continue;

    check( strpos( $source, $guard ) !== false, "$name hands itself to inc/standalone.php" );

    // the guard comes before the first statement: only the opening tag, comments and blank space precede it
    $before = (string)substr( $source, 0, (int)strpos( $source, $guard ) );
    $code = '';
    foreach ( token_get_all( $before ) as $token )
    {
        if ( is_array( $token ) && in_array( $token[0], array( T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_INLINE_HTML ), true ) )
            continue;
        $code .= is_array( $token ) ? $token[1] : $token;
    }
    check( trim( $code ) === '', "$name: nothing runs before the guard" );

    foreach ( token_get_all( $source ) as $token )
    {
        if ( is_array( $token ) && $token[0] === T_EXIT )
            check( false, "$name ends with exit, which skips the shutdown" );
    }
}

$standalone = file_get_contents( $dir . '/inc/standalone.php' );
check( $standalone !== false, 'inc/standalone.php exists' );
check( strpos( $standalone, "require_once 'autoload.php'" ) !== false, 'inc/standalone.php starts Exponential' );
check( strpos( $standalone, 'include $standaloneHelper' ) !== false, 'inc/standalone.php runs the helper' );
check( strpos( $standalone, '$script->shutdown()' ) !== false, 'inc/standalone.php shuts down' );
check( count( glob( $dir . '/inc/*.php' ) ) === 1, 'inc/ holds the bootstrap only' );

if ( $failures )
{
    echo "$failures failure(s)\n";
    exit( 1 );
}
echo "PASS: " . count( $helpers ) . " helpers of bin/php run on their own and through ezexec\n";
