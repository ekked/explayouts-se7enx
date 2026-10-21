<?php
/**
 * The lists other extensions add to start in explayouts' settings without a reset line, so an extension's
 * AvailableBlocks[]=myblock (and AvailableQueries[], SiteAccessMatch[], AllowedTemplateRoots[]) is kept wherever the
 * extension is listed in ActiveExtensions[]. The extensions are read in the reverse order of ActiveExtensions[]; the
 * reset line emptied what the extensions read before explayouts had added (the media design's product_highlight
 * block was never offered). The merge below is the one eZINI makes: files in load order, "Name[]" empties the list.
 *
 * No kernel:
 *   php extension/explayouts/tests/explayoutssettingslistresettest.php
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

/** Group => variable => values of an INI text, applied on top of $into the way eZINI applies a later file */
function mergeIni( $text, array $into )
{
    $group = null;
    foreach ( preg_split( '/\R/', $text ) as $line )
    {
        $line = trim( $line );
        if ( $line === '' || $line[0] === '#' )
            continue;
        if ( preg_match( '/^\[(.+)\]$/', $line, $m ) )
        {
            $group = $m[1];
            continue;
        }
        if ( $group !== null && preg_match( '/^([A-Za-z0-9_]+)\[\](?:=(.*))?$/', $line, $m ) )
        {
            if ( !isset( $m[2] ) )
                $into[$group][$m[1]] = array();
            else
                $into[$group][$m[1]][] = $m[2];
        }
    }
    return $into;
}

$own = file_get_contents( dirname( __DIR__ ) . '/settings/explayouts.ini.append.php' );
$lists = array( 'BlockSettings' => 'AvailableBlocks', 'AdminBlockSettings' => 'AvailableBlocks',
                'QuerySettings' => 'AvailableQueries', 'AdminLayoutSettings' => 'SiteAccessMatch',
                'TemplateEditorSettings' => 'AllowedTemplateRoots' );
$shipped = mergeIni( $own, array() );
foreach ( $lists as $group => $name )
{
    check( !empty( $shipped[$group][$name] ), "[$group] $name is still shipped with its values" );
    check( !preg_match( '/^\[' . preg_quote( $group, '/' ) . '\]\R(?:(?!^\[).*\R)*?^' . $name . '\[\]\s*$/m', $own ), "[$group] has no {$name}[] reset line" );
}

// an extension read before explayouts (listed after it in ActiveExtensions[]) and one read after it
$before = "[BlockSettings]\nAvailableBlocks[]=product_highlight\n[QuerySettings]\nAvailableQueries[]=my_query\n";
$after = "[BlockSettings]\nAvailableBlocks[]=notice\nAvailableBlocks[]=title\n";
$merged = mergeIni( $after, mergeIni( $own, mergeIni( $before, array() ) ) );
check( in_array( 'product_highlight', $merged['BlockSettings']['AvailableBlocks'], true ), 'a block of an extension read before explayouts is kept' );
check( in_array( 'my_query', $merged['QuerySettings']['AvailableQueries'], true ), 'a query of an extension read before explayouts is kept' );
check( in_array( 'notice', $merged['BlockSettings']['AvailableBlocks'], true ), 'a block of an extension read after explayouts is kept' );

// a reset in the override replaces the list
$replaced = mergeIni( "[BlockSettings]\nAvailableBlocks[]\nAvailableBlocks[]=title\n", $merged );
check( $replaced['BlockSettings']['AvailableBlocks'] === array( 'title' ), 'a reset where it is read last still replaces the list' );

// a block listed twice is offered once
$factory = file_get_contents( dirname( __DIR__ ) . '/classes/explayoutsblockhandlerfactory.php' );
check( strpos( $factory, "array_unique( (array)\$ini->variable( 'BlockSettings', 'AvailableBlocks' ) )" ) !== false, 'getAvailableBlocks() lists a block once' );

if ( $failures )
{
    echo "$failures failure(s)\n";
    exit( 1 );
}
echo "PASS: the lists of explayouts.ini keep what every extension adds, in any order\n";
