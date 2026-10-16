<?php
class expLayoutsTemplateEditor
{
    public static function listTemplates( $root = 'design' )
    {
        $list = array();
        $base = eZSys::rootDir();
        $allowedRoots = self::allowedRoots();

        foreach ( $allowedRoots as $allowedRoot )
        {
            $full = $base . '/' . $allowedRoot;
            if ( !is_dir( $full ) )
                continue;
            self::scan( $full, $allowedRoot, $list );
        }
        sort( $list );
        return $list;
    }

    /**
     * The folders (relative to the installation) whose templates the editor may read and write:
     * [TemplateEditorSettings] AllowedTemplateRoots[]. None set means none: earlier versions fell back to
     * all of design/ and extension/ for an empty list, which includes the administration's page layout.
     */
    protected static function allowedRoots()
    {
        $ini = eZINI::instance( 'explayouts.ini' );
        $roots = $ini->hasVariable( 'TemplateEditorSettings', 'AllowedTemplateRoots' )
            ? $ini->variable( 'TemplateEditorSettings', 'AllowedTemplateRoots' )
            : array();
        if ( !is_array( $roots ) )
            return array();
        $clean = array();
        foreach ( $roots as $root )
        {
            $root = trim( str_replace( '\\', '/', (string)$root ), '/' );
            if ( $root !== '' )
                $clean[] = $root;
        }
        return $clean;
    }

    /**
     * Whether save() may create a template that does not exist yet:
     * [TemplateEditorSettings] AllowNewTemplates=enabled. Off unless set.
     */
    public static function newTemplatesAllowed()
    {
        $ini = eZINI::instance( 'explayouts.ini' );
        return $ini->hasVariable( 'TemplateEditorSettings', 'AllowNewTemplates' )
            && $ini->variable( 'TemplateEditorSettings', 'AllowNewTemplates' ) === 'enabled';
    }

    protected static function scan( $dir, $relative, &$list )
    {
        $d = @dir( $dir );
        if ( !$d )
            return;
        while ( ( $entry = $d->read() ) !== false )
        {
            if ( $entry === '.' || $entry === '..' )
                continue;
            $full = $dir . '/' . $entry;
            $rel  = $relative . '/' . $entry;
            // resolve() refuses symbolic links, so they are not offered either.
            if ( is_link( $full ) )
                continue;
            if ( is_dir( $full ) )
            {
                self::scan( $full, $rel, $list );
            }
            else if ( is_file( $full ) && substr( $entry, -4 ) === '.tpl' )
            {
                $list[] = $rel;
            }
        }
        $d->close();
    }

    public static function read( $path )
    {
        $file = self::resolve( $path );
        if ( $file === false || !is_readable( $file ) )
            return false;
        return file_get_contents( $file );
    }

    /**
     * Writes a template inside the allowed roots. A file that does not exist yet is only created with
     * AllowNewTemplates=enabled. The previous version is kept in the var directory
     * (<var>/explayouts/template_backup/<path>), not as <file>.tpl.bak next to the template: a backup in
     * the design folders is deployed, listed and found by the design resolution like any other file.
     */
    public static function save( $path, $content )
    {
        $file = self::resolve( $path );
        if ( $file === false || is_link( $file ) )
            return false;

        $exists = is_file( $file );
        if ( !$exists && !self::newTemplatesAllowed() )
            return false;
        if ( $exists ? !is_writable( $file ) : !is_writable( dirname( $file ) ) )
            return false;

        if ( $exists && !self::backup( $file ) )
            return false;

        return file_put_contents( $file, $content ) !== false;
    }

    /**
     * Copies a template to <var>/explayouts/template_backup/<path relative to the installation>. One
     * generation: each save replaces the copy of the save before. False when the copy could not be made,
     * so nothing is overwritten without one.
     */
    protected static function backup( $file )
    {
        $base = realpath( eZSys::rootDir() );
        if ( $base === false || strpos( $file, $base . '/' ) !== 0 )
            return false;

        $varDir = eZSys::varDirectory();
        if ( $varDir === '' || $varDir[0] !== '/' )
            $varDir = $base . '/' . $varDir;

        $target = rtrim( $varDir, '/' ) . '/explayouts/template_backup/' . substr( $file, strlen( $base ) + 1 );
        $dir = dirname( $target );
        if ( !is_dir( $dir ) && !eZDir::mkdir( $dir, false, true ) )
            return false;

        return copy( $file, $target );
    }

    /**
     * The absolute file of a template path, or false. Only .tpl files inside one of the
     * AllowedTemplateRoots are accepted (also after symbolic links are followed), so the
     * editor can neither read nor write settings, PHP code or anything outside those roots.
     */
    protected static function resolve( $path )
    {
        if ( !is_string( $path ) || $path === '' || strpos( $path, "\0" ) !== false )
            return false;

        $cleanPath = ltrim( str_replace( '\\', '/', $path ), '/' );
        if ( substr( $cleanPath, -4 ) !== '.tpl' || strlen( basename( $cleanPath ) ) <= 4 )
            return false;

        $base = realpath( eZSys::rootDir() );
        $candidate = $base . '/' . $cleanPath;
        $dir = @realpath( dirname( $candidate ) );
        if ( $dir === false )
            return false;

        $real = $dir . '/' . basename( $candidate );
        // A symbolic link is never a template here: one that points nowhere passes
        // file_exists() as false, skipped the realpath() below, and save() then
        // wrote through it to wherever it points, outside every root.
        if ( is_link( $real ) )
            return false;
        if ( file_exists( $real ) )
        {
            $real = @realpath( $real );
            if ( $real === false || substr( $real, -4 ) !== '.tpl' )
                return false;
        }

        foreach ( self::allowedRoots() as $allowedRoot )
        {
            $rootReal = @realpath( $base . '/' . $allowedRoot );
            if ( $rootReal !== false && strpos( $real, $rootReal . '/' ) === 0 )
                return $real;
        }
        return false;
    }
}
