<?php
class expLayoutsMapBlockHandler implements expLayoutsBlockHandlerInterface
{
    /** The map types of the reference (block.map.map_type.*) */
    public static $mapTypes = array( 'ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN' );

    const MIN_ZOOM = 0;
    const MAX_ZOOM = 20;

    public function getParameters()
    {
        return array(
            // The parameters of the reference: a point, a zoom level, a map type and a marker.
            'latitude' => array(
                'name' => 'Latitude (-90 to 90)',
                'type' => 'string',
                'default' => '',
            ),
            'longitude' => array(
                'name' => 'Longitude (-180 to 180)',
                'type' => 'string',
                'default' => '',
            ),
            'zoom' => array(
                'name' => 'Zoom (0-20)',
                'type' => 'integer',
                'default' => 5,
                'min' => self::MIN_ZOOM,
                'max' => self::MAX_ZOOM,
            ),
            'map_type' => array(
                'name' => 'Map type',
                'type' => 'select',
                'options' => array(
                    'ROADMAP' => 'Roadmap',
                    'SATELLITE' => 'Satellite',
                    'HYBRID' => 'Hybrid',
                    'TERRAIN' => 'Terrain',
                ),
                'default' => 'ROADMAP',
            ),
            'show_marker' => array(
                'name' => 'Show marker',
                'type' => 'checkbox',
                'default' => 1,
            ),
            // The older way: the address of a map to embed. Used when no point is given.
            'embed_url' => array(
                'name' => 'Map embed URL',
                'type' => 'string',
                'default' => '',
            ),
            'width' => array(
                'name' => 'Width',
                'type' => 'string',
                'default' => '100%',
            ),
            'height' => array(
                'name' => 'Height (px)',
                'type' => 'integer',
                'default' => 400,
                'min' => 1,
            ),
        );
    }

    /**
     * A coordinate as a number within -$limit..$limit, or null for anything else (a decimal comma is read as a point).
     */
    public static function coordinate( $value, $limit )
    {
        if ( !is_scalar( $value ) )
            return null;
        $value = str_replace( ',', '.', trim( (string)$value ) );
        if ( !preg_match( '/^[-+]?(?:\d+(?:\.\d*)?|\.\d+)$/', $value ) )
            return null;
        $number = (float)$value;
        return ( $number >= -$limit && $number <= $limit ) ? $number : null;
    }

    /**
     * The address of a map around a point. With explayouts.ini [MapSettings] GoogleMapsApiKey: the Maps Embed API of
     * Google, the service of the reference (its map types roadmap and satellite: TERRAIN shows the roadmap, HYBRID the
     * satellite map); without a key: OpenStreetMap, whose embed has one map type, with the zoom as the area shown.
     */
    public static function pointUrl( $latitude, $longitude, $zoom, $mapType, $showMarker, $apiKey = '' )
    {
        $lat = self::number( $latitude );
        $lng = self::number( $longitude );
        $apiKey = trim( (string)$apiKey );
        if ( $apiKey !== '' )
        {
            $type = in_array( $mapType, array( 'SATELLITE', 'HYBRID' ), true ) ? 'satellite' : 'roadmap';
            if ( $showMarker )
                return 'https://www.google.com/maps/embed/v1/place?key=' . rawurlencode( $apiKey ) . '&q=' . $lat . ',' . $lng
                    . '&zoom=' . $zoom . '&maptype=' . $type;
            return 'https://www.google.com/maps/embed/v1/view?key=' . rawurlencode( $apiKey ) . '&center=' . $lat . ',' . $lng
                . '&zoom=' . $zoom . '&maptype=' . $type;
        }

        // The area of about 640 x 400 pixels around the point at this zoom: 256 * 2^zoom pixels are 360 degrees.
        $halfLng = 450 / pow( 2, $zoom );
        $halfLat = 281.25 / pow( 2, $zoom ) * max( 0.05, cos( deg2rad( $latitude ) ) );
        $bbox = array(
            self::number( max( -180, $longitude - $halfLng ) ),
            self::number( max( -85, $latitude - $halfLat ) ),
            self::number( min( 180, $longitude + $halfLng ) ),
            self::number( min( 85, $latitude + $halfLat ) ),
        );
        return 'https://www.openstreetmap.org/export/embed.html?bbox=' . implode( '%2C', $bbox ) . '&layer=mapnik'
            . ( $showMarker ? '&marker=' . $lat . '%2C' . $lng : '' );
    }

    protected static function number( $value )
    {
        $text = rtrim( rtrim( sprintf( '%.6F', $value ), '0' ), '.' );
        return $text === '-0' ? '0' : $text;
    }

    protected static function googleMapsApiKey()
    {
        if ( !class_exists( 'eZINI', false ) )
            return '';
        $ini = eZINI::instance( 'explayouts.ini' );
        return $ini->hasVariable( 'MapSettings', 'GoogleMapsApiKey' ) ? trim( (string)$ini->variable( 'MapSettings', 'GoogleMapsApiKey' ) ) : '';
    }

    public function getValues( $block )
    {
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
        $latitude = self::coordinate( isset( $params['latitude'] ) ? $params['latitude'] : '', 90 );
        $longitude = self::coordinate( isset( $params['longitude'] ) ? $params['longitude'] : '', 180 );
        $zoom = isset( $params['zoom'] ) && is_numeric( $params['zoom'] ) ? (int)$params['zoom'] : 5;
        $zoom = max( self::MIN_ZOOM, min( self::MAX_ZOOM, $zoom ) );
        $mapType = isset( $params['map_type'] ) && in_array( (string)$params['map_type'], self::$mapTypes, true ) ? (string)$params['map_type'] : 'ROADMAP';
        $showMarker = !isset( $params['show_marker'] ) || ( (string)$params['show_marker'] !== '' && (string)$params['show_marker'] !== '0' );

        // A point, as in the reference, comes first; the address is the older way and stays for the blocks that
        // carry one.
        $hasPoint = $latitude !== null && $longitude !== null;
        $embedUrl = '';
        $provider = '';
        if ( $hasPoint )
        {
            $apiKey = self::googleMapsApiKey();
            $embedUrl = self::pointUrl( $latitude, $longitude, $zoom, $mapType, $showMarker, $apiKey );
            $provider = $apiKey !== '' ? 'google' : 'openstreetmap';
        }
        elseif ( isset( $params['embed_url'] ) )
        {
            // the src of an iframe: http(s) or a path on this site, nothing a browser runs as script
            $embedUrl = expLayoutsSafeValue::embedUrl( $params['embed_url'] );
            $provider = $embedUrl !== '' ? 'embed' : '';
        }

        return array(
            'latitude' => $hasPoint ? $latitude : '',
            'longitude' => $hasPoint ? $longitude : '',
            'zoom' => $zoom,
            'map_type' => $mapType,
            'show_marker' => $showMarker ? 1 : 0,
            'provider' => $provider,
            'embed_url' => $embedUrl,
            'width' => isset( $params['width'] ) ? $params['width'] : '100%',
            'height' => isset( $params['height'] ) && (int)$params['height'] > 0 ? (int)$params['height'] : 400,
        );
    }
}
