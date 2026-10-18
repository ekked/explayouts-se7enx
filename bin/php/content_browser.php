<?php
// @description List content browser items: content_browser.php [provider] [parent node id] [search text]
// Run through bin/php/ezexec.php, which includes this file from inside a method: $argv is not in scope there, and
// the arguments are in $options['arguments'], the first being this file. Run directly, $argv holds them.
$scriptArguments = isset( $options['arguments'] ) && is_array( $options['arguments'] )
    ? array_slice( $options['arguments'], 1 )
    : ( isset( $argv ) && is_array( $argv ) ? array_slice( $argv, 1 ) : array() );
$provider = isset( $scriptArguments[0] ) ? trim( $scriptArguments[0] ) : 'content';
$parentNodeId = isset( $scriptArguments[1] ) ? (int)$scriptArguments[1] : 2;
$search = isset( $scriptArguments[2] ) ? trim( $scriptArguments[2] ) : '';

$browser = new expLayoutsContentBrowserProvider();
$result = $browser->getItems( $provider, $parentNodeId, $search, 0, 25 );

if ( !isset( $result['items'] ) || count( $result['items'] ) === 0 )
{
    echo "No items found.\n";
    return;
}

foreach ( $result['items'] as $item )
{
    echo $item['id'] . "\t"
        . $item['class_identifier'] . "\t"
        . $item['name'] . "\t"
        . $item['url_alias'] . "\n";
}
echo "Count: " . (int)$result['count'] . "\n";