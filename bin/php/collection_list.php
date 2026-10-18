<?php
// @description List the collection items of a block: collection_list.php <block_id>
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
if ( !isset( $scriptArguments[0] ) )
{
    echo "Usage: collection_list.php <block_id>\n";
    if ( isset( $script ) ) $script->setExitCode( 1 );
    return;
}

$blockId = (int)$scriptArguments[0];
$collection = expLayoutsCollection::fetchByBlock( $blockId );

if ( !$collection )
{
    echo "No collection for block.\n";
    return;
}

$items = expLayoutsCollectionItem::fetchByCollection( $collection->attribute( 'id' ) );
if ( !is_array( $items ) || count( $items ) === 0 )
{
    echo "No collection items.\n";
    return;
}

foreach ( $items as $item )
{
    if ( $item instanceof expLayoutsCollectionItem )
    {
        echo $item->attribute( 'id' ) . "\t"
            . $item->attribute( 'value_type' ) . "\t"
            . $item->attribute( 'value_id' ) . "\t"
            . $item->attribute( 'position' ) . "\t"
            . $item->attribute( 'item_type' ) . "\n";
    }
}