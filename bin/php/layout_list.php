<?php
// @description List all layouts with id, identifier, name, type and status
if ( !isset( $script ) && !isset( $options ) ) { require __DIR__ . '/inc/standalone.php'; return; }

$service = new expLayoutsCoreLayoutService();
$layouts = $service->listAll();

if ( !is_array( $layouts ) || count( $layouts ) === 0 )
{
    echo "No layouts found.\n";
    return;
}

foreach ( $layouts as $layout )
{
    if ( $layout instanceof expLayoutsLayout )
    {
        echo $layout->attribute( 'id' ) . "\t"
            . $layout->attribute( 'identifier' ) . "\t"
            . $layout->attribute( 'name' ) . "\t"
            . $layout->attribute( 'layout_type' ) . "\t"
            . ( $layout->attribute( 'status' ) == 2 ? 'published' : 'draft' ) . "\n";
    }
}