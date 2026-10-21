<?php
/**
 * Runs a helper of bin/php on its own: ./console ext:explayouts:<name>, or
 * php extension/explayouts/bin/php/<name>.php [-s <siteaccess>] [arguments].
 *
 * The helpers were written for bin/php/ezexec.php, which starts Exponential and then includes them. Run on their own,
 * nothing was started and the first class a helper used was not found. A helper therefore begins with
 *
 *   if ( !isset( $script ) ) { require __DIR__ . '/inc/standalone.php'; return; }
 *
 * Under ezexec $script is set and the line does nothing. On its own, this file starts Exponential the way ezexec does,
 * runs the helper once more (now with $script, $cli and $options set, as under ezexec: $options['arguments'] starts with
 * the helper's file, followed by the arguments given) and shuts down with the exit code the helper set
 * ($script->setExitCode()). A helper ends with return, never exit: an exit skips the shutdown.
 *
 * The directory has no command of its own: ./console lists bin/php/*.php only.
 */

$standaloneHelper = isset( $_SERVER['SCRIPT_FILENAME'] ) ? realpath( (string)$_SERVER['SCRIPT_FILENAME'] ) : false;
if ( $standaloneHelper === false )
{
    fwrite( STDERR, "Run this helper as a script: php extension/explayouts/bin/php/<name>.php\n" );
    exit( 1 );
}

$standaloneRoot = dirname( __DIR__, 5 );
if ( !file_exists( 'autoload.php' ) && file_exists( $standaloneRoot . '/autoload.php' ) )
    chdir( $standaloneRoot );
unset( $standaloneRoot );

require_once 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => 'Exponential Layouts helper: ' . basename( $standaloneHelper, '.php' ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( '', '[arguments*]', array() );
$script->initialize();

$options['arguments'] = array_merge( array( $standaloneHelper ),
                                     isset( $options['arguments'] ) && is_array( $options['arguments'] ) ? $options['arguments'] : array() );

$standaloneResult = include $standaloneHelper;
if ( $standaloneResult === false )
    $script->setExitCode( 1 );
unset( $standaloneHelper, $standaloneResult );

$script->shutdown();
