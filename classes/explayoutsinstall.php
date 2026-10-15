<?php
class expLayoutsInstall
{
    /**
     * The statements of a schema file that belong to this extension: CREATE TABLE / ALTER TABLE / DROP TABLE of an
     * explayouts_* table and CREATE INDEX on one. The schema files also describe kernel and sibling tables (eztags*,
     * ezurl*, exp_info_collection) for the database consistency check; an installer must never create or change
     * those, so they are left out here.
     *
     * @param string $sql contents of a schema file
     * @return string[] the statements, in file order, without the trailing semicolon
     */
    static function ownStatements( $sql )
    {
        $own = array();
        foreach ( preg_split( '/;[\s]*$/m', (string)$sql ) as $query )
        {
            $query = trim( $query );
            if ( $query === '' )
                continue;
            // strip leading SQL comments before looking at the statement
            $statement = trim( preg_replace( '/^(\s*--[^\n]*\n)+/', '', $query . "\n" ) );
            $patterns = array(
                // tables (ALTER TABLE ONLY in PostgreSQL)
                '/^(CREATE|ALTER|DROP)\s+TABLE\s+(ONLY\s+)?(IF\s+(NOT\s+)?EXISTS\s+)?["`]?explayouts_\w+/i',
                // indexes on them
                '/^CREATE\s+(UNIQUE\s+)?INDEX\s+\S+\s+ON\s+["`]?explayouts_\w+/i',
                // their id sequences (PostgreSQL explayouts_*_seq, Oracle se_explayouts_*)
                '/^CREATE\s+SEQUENCE\s+(IF\s+NOT\s+EXISTS\s+)?["`]?(se_)?explayouts_\w+/i',
                // the Oracle id triggers
                '/^CREATE\s+(OR\s+REPLACE\s+)?TRIGGER\s+\S+\s+BEFORE\s+INSERT\s+ON\s+["`]?explayouts_\w+/i',
            );
            foreach ( $patterns as $pattern )
            {
                if ( preg_match( $pattern, $statement ) )
                {
                    $own[] = $statement;
                    break;
                }
            }
        }
        return $own;
    }

    static function runSqlFile( $sqlFile )
    {
        $db = eZDB::instance();
        $path = eZSys::rootDir() . '/' . ltrim( $sqlFile, '/' );

        if ( !file_exists( $path ) )
            return array( 'error' => 'SQL file not found: ' . $sqlFile );

        $sql = file_get_contents( $path );
        $queries = self::ownStatements( $sql );

        $executed = 0;
        $failed = 0;
        $lastError = '';

        foreach ( $queries as $query )
        {
            if ( $query === '' )
                continue;

            $result = $db->query( $query );
            if ( $result === false )
            {
                $failed++;
                $lastError = $db->error() ? $db->error() : 'Unknown error';
            }
            else
            {
                $executed++;
            }
        }

        if ( $failed > 0 )
            return array( 'error' => 'Executed ' . $executed . ' queries, ' . $failed . ' failed. Last error: ' . $lastError );

        return array( 'message' => 'Executed ' . $executed . ' SQL queries.' );
    }
}
