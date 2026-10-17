<?php
/**
 * The template editor (expLayoutsTemplateEditor) under the shipped settings, without the kernel: eZINI reads
 * settings/explayouts.ini.append.php of this extension, eZSys and eZDir are stand-ins on a sandbox installation.
 *
 * Run directly with:
 *   php tests/explayoutstemplateeditortest.php
 *
 *  TE-01 - the shipped roots do not reach the administration's page layout: not listed, not read, not saved
 *  TE-02 - a layout template inside the shipped roots is saved; its previous version is in the var directory,
 *          not as .tpl.bak next to it
 *  TE-03 - a template that does not exist yet is created only with AllowNewTemplates=enabled
 *  TE-04 - an empty AllowedTemplateRoots[] allows nothing (no fallback to design/ and extension/)
 *  TE-05 - paths outside the roots, with .., or not ending in .tpl are refused
 *  TE-06 - a symbolic link inside a root is not listed, read or written, also one that points nowhere and with
 *          AllowNewTemplates=enabled (the write would go wherever it points)
 *
 * The sandbox is made under EXPLAYOUTS_TEST_TMP, or the system temporary directory, and removed afterwards.
 */

$failures = 0;
function teCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

/** The [TemplateEditorSettings] of the shipped settings file, as eZINI reads it, plus overrides of the test. */
class eZINI
{
    public static $overrides = array();
    protected $values = array();

    public static function instance( $name )
    {
        $ini = new self();
        $ini->values = self::parse( dirname( __DIR__ ) . '/settings/' . $name . '.append.php' );
        foreach ( self::$overrides as $group => $variables )
            foreach ( $variables as $variable => $value )
                $ini->values[$group][$variable] = $value;
        return $ini;
    }

    protected static function parse( $file )
    {
        $values = array();
        $group = null;
        foreach ( preg_split( '/\r?\n/', (string)file_get_contents( $file ) ) as $line )
        {
            $line = trim( $line );
            if ( $line === '' || $line[0] === '#' )
                continue;
            if ( preg_match( '/^\[(.+)\]$/', $line, $m ) )
            {
                $group = $m[1];
                continue;
            }
            if ( $group === null || !preg_match( '/^([A-Za-z0-9_]+)(\[\])?(?:=(.*))?$/', $line, $m ) )
                continue;
            if ( !empty( $m[2] ) )
            {
                if ( !isset( $m[3] ) )
                    $values[$group][$m[1]] = array();
                else
                    $values[$group][$m[1]][] = $m[3];
            }
            else if ( isset( $m[3] ) )
                $values[$group][$m[1]] = $m[3];
        }
        return $values;
    }

    public function hasVariable( $group, $variable )
    {
        return isset( $this->values[$group] ) && array_key_exists( $variable, $this->values[$group] );
    }

    public function variable( $group, $variable )
    {
        return $this->hasVariable( $group, $variable ) ? $this->values[$group][$variable] : false;
    }
}

class eZSys
{
    public static $root;
    public static function rootDir() { return self::$root; }
    public static function varDirectory() { return 'var/site'; }
}

class eZDir
{
    public static function mkdir( $dir, $perm = false, $parents = false ) { return mkdir( $dir, 0777, $parents ); }
}

require_once __DIR__ . '/../classes/explayoutstemplateeditor.php';

function teRemove( $path )
{
    if ( is_dir( $path ) && !is_link( $path ) )
    {
        foreach ( scandir( $path ) as $entry )
            if ( $entry !== '.' && $entry !== '..' )
                teRemove( $path . '/' . $entry );
        rmdir( $path );
    }
    else if ( file_exists( $path ) || is_link( $path ) )
        unlink( $path );
}

function tePut( $relative, $content )
{
    $file = eZSys::$root . '/' . $relative;
    if ( !is_dir( dirname( $file ) ) )
        mkdir( dirname( $file ), 0777, true );
    file_put_contents( $file, $content );
}

$tmp = getenv( 'EXPLAYOUTS_TEST_TMP' ) ?: sys_get_temp_dir();
eZSys::$root = realpath( $tmp ) . '/explayouts-template-editor-' . getmypid();
teRemove( eZSys::$root );
mkdir( eZSys::$root, 0777, true );

$pagelayout = 'design/admin4/templates/pagelayout.tpl';
$extensionPagelayout = 'extension/someadmin/design/admin4/templates/pagelayout.tpl';
$layout = 'extension/explayouts/design/standard/templates/explayouts/layouts/1_column.tpl';
$block = 'extension/explayouts/design/standard/templates/explayouts/block/text.tpl';
tePut( $pagelayout, 'admin page layout' );
tePut( $extensionPagelayout, 'admin page layout of an extension' );
tePut( $layout, 'old layout' );
tePut( $block, 'block' );
tePut( 'settings/site.ini', 'not a template' );

try
{
    // TE-01
    $list = expLayoutsTemplateEditor::listTemplates();
    teCheck( $list === array( $block, $layout ), 'TE-01 the shipped roots list the layout and block templates only: ' . json_encode( $list ) );
    teCheck( expLayoutsTemplateEditor::read( $pagelayout ) === false, 'TE-01 design/admin4/templates/pagelayout.tpl is not read' );
    teCheck( expLayoutsTemplateEditor::save( $pagelayout, 'changed' ) === false, 'TE-01 design/admin4/templates/pagelayout.tpl is not saved' );
    teCheck( expLayoutsTemplateEditor::save( $extensionPagelayout, 'changed' ) === false, 'TE-01 an admin page layout under extension/ is not saved' );
    teCheck( file_get_contents( eZSys::$root . '/' . $pagelayout ) === 'admin page layout'
             && file_get_contents( eZSys::$root . '/' . $extensionPagelayout ) === 'admin page layout of an extension', 'TE-01 both page layouts are unchanged' );

    // TE-02
    teCheck( expLayoutsTemplateEditor::read( $layout ) === 'old layout', 'TE-02 a layout template is read' );
    teCheck( expLayoutsTemplateEditor::save( $layout, 'new layout' ) === true, 'TE-02 a layout template is saved' );
    teCheck( file_get_contents( eZSys::$root . '/' . $layout ) === 'new layout', 'TE-02 the template has the new content' );
    teCheck( !file_exists( eZSys::$root . '/' . $layout . '.bak' ), 'TE-02 no .tpl.bak next to the template' );
    $backup = eZSys::$root . '/var/site/explayouts/template_backup/' . $layout;
    teCheck( is_file( $backup ) && file_get_contents( $backup ) === 'old layout', 'TE-02 the previous version is in var/site/explayouts/template_backup/' );

    // TE-03
    $new = 'extension/explayouts/design/standard/templates/explayouts/layouts/new_layout.tpl';
    teCheck( expLayoutsTemplateEditor::newTemplatesAllowed() === false, 'TE-03 new templates are off as shipped' );
    teCheck( expLayoutsTemplateEditor::save( $new, 'x' ) === false && !file_exists( eZSys::$root . '/' . $new ), 'TE-03 a new template is not created as shipped' );
    eZINI::$overrides = array( 'TemplateEditorSettings' => array( 'AllowNewTemplates' => 'enabled' ) );
    teCheck( expLayoutsTemplateEditor::save( $new, 'x' ) === true && file_get_contents( eZSys::$root . '/' . $new ) === 'x', 'TE-03 a new template is created with AllowNewTemplates=enabled' );
    teCheck( expLayoutsTemplateEditor::save( 'design/admin4/templates/new.tpl', 'x' ) === false, 'TE-03 not outside the roots, even with AllowNewTemplates=enabled' );

    // TE-04
    eZINI::$overrides = array( 'TemplateEditorSettings' => array( 'AllowedTemplateRoots' => array() ) );
    teCheck( expLayoutsTemplateEditor::listTemplates() === array(), 'TE-04 an empty root list lists nothing' );
    teCheck( expLayoutsTemplateEditor::save( $layout, 'x' ) === false && expLayoutsTemplateEditor::save( $pagelayout, 'x' ) === false, 'TE-04 an empty root list saves nothing' );
    eZINI::$overrides = array();

    // TE-05
    teCheck( expLayoutsTemplateEditor::read( 'extension/explayouts/design/standard/templates/explayouts/layouts/../../../../../../../' . $pagelayout ) === false, 'TE-05 .. out of a root is refused' );
    teCheck( expLayoutsTemplateEditor::save( 'extension/explayouts/design/standard/templates/explayouts/layouts/x.php', 'x' ) === false, 'TE-05 not a .tpl' );
    teCheck( expLayoutsTemplateEditor::read( 'settings/site.ini' ) === false, 'TE-05 settings are not read' );

    // TE-06
    $layoutsDir = eZSys::$root . '/extension/explayouts/design/standard/templates/explayouts/layouts';
    $outside = eZSys::$root . '/design/admin4/templates/planted.tpl';
    symlink( $outside, $layoutsDir . '/dangling.tpl' );
    symlink( eZSys::$root . '/' . $pagelayout, $layoutsDir . '/to_pagelayout.tpl' );
    eZINI::$overrides = array( 'TemplateEditorSettings' => array( 'AllowNewTemplates' => 'enabled' ) );
    teCheck( expLayoutsTemplateEditor::save( 'extension/explayouts/design/standard/templates/explayouts/layouts/dangling.tpl', 'planted' ) === false
             && !file_exists( $outside ), 'TE-06 a link that points nowhere is not written through, even with AllowNewTemplates=enabled' );
    teCheck( expLayoutsTemplateEditor::read( 'extension/explayouts/design/standard/templates/explayouts/layouts/to_pagelayout.tpl' ) === false, 'TE-06 a link to a template outside the roots is not read' );
    teCheck( expLayoutsTemplateEditor::save( 'extension/explayouts/design/standard/templates/explayouts/layouts/to_pagelayout.tpl', 'x' ) === false
             && file_get_contents( eZSys::$root . '/' . $pagelayout ) === 'admin page layout', 'TE-06 nor written' );
    $listed = expLayoutsTemplateEditor::listTemplates();
    teCheck( !in_array( 'extension/explayouts/design/standard/templates/explayouts/layouts/to_pagelayout.tpl', $listed, true )
             && !in_array( 'extension/explayouts/design/standard/templates/explayouts/layouts/dangling.tpl', $listed, true ), 'TE-06 links are not listed' );
    eZINI::$overrides = array();
}
finally
{
    teRemove( eZSys::$root );
}

echo $failures ? "$failures failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
