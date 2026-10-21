<?php
class expLayoutsVideoBlockHandler implements expLayoutsBlockHandlerInterface
{
    /**
     * The services of the reference (block.external_video.service.*) and the player address of a video id. YouTube is
     * embedded from youtube-nocookie.com and Vimeo with dnt=1: the same players, without tracking cookies before the
     * video is played. The reference embeds youtube.com/embed and player.vimeo.com/video.
     */
    public static $services = array(
        'youtube' => 'https://www.youtube-nocookie.com/embed/%s',
        'vimeo' => 'https://player.vimeo.com/video/%s?dnt=1',
        'dailymotion' => 'https://www.dailymotion.com/embed/video/%s',
    );

    public function getParameters()
    {
        return array(
            'service' => array(
                'name' => 'Service',
                'type' => 'select',
                'options' => array(
                    '' => 'Video address below',
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                    'dailymotion' => 'Dailymotion',
                ),
                'default' => '',
            ),
            'video_id' => array(
                'name' => 'Video ID',
                'type' => 'string',
                'default' => '',
            ),
            'caption' => array(
                'name' => 'Caption',
                'type' => 'string',
                'default' => '',
            ),
            'video_url' => array(
                'name' => 'Video URL or embed src',
                'type' => 'string',
                'default' => '',
            ),
            'width' => array(
                'name' => 'Width (px or %)',
                'type' => 'string',
                'default' => '100%',
            ),
            'height' => array(
                'name' => 'Height (px)',
                'type' => 'integer',
                'default' => 360,
                'min' => 1,
            ),
            'autoplay' => array(
                'name' => 'Autoplay',
                'type' => 'checkbox',
                'default' => 0,
            ),
        );
    }

    /**
     * The player address of a service and a video id, or '' for an unknown service or an id that is not one: letters,
     * digits, - and _ (YouTube, Dailymotion), digits only for Vimeo. The id goes into the path of the address, so
     * nothing else may reach it.
     */
    public static function serviceUrl( $service, $videoId )
    {
        $service = is_scalar( $service ) ? (string)$service : '';
        $videoId = is_scalar( $videoId ) ? trim( (string)$videoId ) : '';
        if ( !isset( self::$services[$service] ) )
            return '';
        $pattern = $service === 'vimeo' ? '/^[0-9]{1,12}$/' : '/^[A-Za-z0-9_-]{1,64}$/';
        if ( !preg_match( $pattern, $videoId ) )
            return '';
        return sprintf( self::$services[$service], $videoId );
    }

    public function getValues( $block )
    {
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
        $service = isset( $params['service'] ) && isset( self::$services[(string)$params['service']] ) ? (string)$params['service'] : '';
        $videoId = isset( $params['video_id'] ) ? trim( (string)$params['video_id'] ) : '';

        // A service with a video id, as in the reference, comes first; the address is the older way and stays for the
        // blocks that carry one.
        $url = self::serviceUrl( $service, $videoId );
        if ( $url === '' && isset( $params['video_url'] ) )
            // the src of a frame of another host: http(s), nothing a browser runs as script, no page of this site
            $url = expLayoutsSafeValue::externalEmbedUrl( $params['video_url'] );

        return array(
            'service' => $service,
            'video_id' => $videoId,
            'caption' => isset( $params['caption'] ) ? (string)$params['caption'] : '',
            'video_url' => $url,
            'embed_host' => expLayoutsSafeValue::host( $url ),
            'width' => isset( $params['width'] ) ? $params['width'] : '100%',
            'height' => isset( $params['height'] ) && (int)$params['height'] > 0 ? (int)$params['height'] : 360,
            'autoplay' => isset( $params['autoplay'] ) ? (int)$params['autoplay'] : 0,
        );
    }
}
