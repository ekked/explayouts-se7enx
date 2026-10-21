<?php
/**
 * The features block: the fields of the other component blocks (title, text, image, link) and a list of features,
 * one per line as "Title|Text", which the templates show as a grid of short items.
 *
 * @license GPL-2.0-or-later
 */
class expLayoutsFeaturesBlockHandler extends expLayoutsComponentBlockHandler
{
    /** The most features shown */
    const MAX_ITEMS = 24;

    public function getParameters()
    {
        $parameters = parent::getParameters();
        $items = array(
            'items' => array(
                'name' => 'Features (Title|Text per line)',
                'type' => 'textarea',
                'default' => '',
            ),
        );
        // after the text, before the image
        $position = array_search( 'content', array_keys( $parameters ), true );
        return array_slice( $parameters, 0, $position + 1, true ) + $items + array_slice( $parameters, $position + 1, null, true );
    }

    /**
     * The features of the text: one per non-empty line, "Title|Text" or a title alone; plain text, the templates
     * escape it.
     *
     * @return array of array( 'title' => string, 'text' => string )
     */
    public static function parseItems( $raw )
    {
        $items = array();
        foreach ( preg_split( '/\r\n|\r|\n/', is_scalar( $raw ) ? (string)$raw : '' ) as $line )
        {
            if ( trim( $line ) === '' )
                continue;
            $parts = explode( '|', $line, 2 );
            $items[] = array(
                'title' => trim( $parts[0] ),
                'text' => isset( $parts[1] ) ? trim( $parts[1] ) : '',
            );
            if ( count( $items ) >= self::MAX_ITEMS )
                break;
        }
        return $items;
    }

    public function getValues( $block )
    {
        $values = parent::getValues( $block );
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
        $values['items'] = self::parseItems( isset( $params['items'] ) ? $params['items'] : '' );
        return $values;
    }
}
