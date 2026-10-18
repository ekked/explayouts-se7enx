<?php
class expLayoutsButtonBlockHandler implements expLayoutsBlockHandlerInterface
{
    public function getParameters()
    {
        $parameters = array(
            'text' => array(
                'name' => 'Text',
                'type' => 'string',
                'default' => '',
            ),
            'label' => array(
                'name' => 'Label (legacy)',
                'type' => 'string',
                'default' => '',
            ),
            'url' => array(
                'name' => 'URL (legacy)',
                'type' => 'string',
                'default' => '',
            ),
            // the styles of the reference (block.button.style.*)
            'style' => array(
                'name' => 'Style',
                'type' => 'select',
                'options' => array(
                    'default_button' => 'Default button',
                    'highlighted_button' => 'Highlighted button',
                    'link' => 'Link',
                ),
                'default' => 'default_button',
            ),
            // the older way to open the link in a new window; the compound link has its own switch, which wins
            'target' => array(
                'name' => 'Target',
                'type' => 'select',
                'options' => array(
                    '_self' => 'Same window',
                    '_blank' => 'New window',
                ),
                'default' => '_self',
            ),
        );

        // Same compound link the title block uses. Declared as a string it
        // drew a text input holding the stored JSON.
        return array_merge( $parameters, expLayoutsLinkParameter::definition( 'Use link' ) );
    }

    public function getValues( $block )
    {
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();

        $text = '';
        if ( isset( $params['text'] ) && $params['text'] !== '' )
            $text = $params['text'];
        elseif ( isset( $params['label'] ) )
            $text = $params['label'];

        // The compound link wins; the legacy url parameter is the fallback for
        // a button that predates it.
        $resolved = expLayoutsLinkParameter::resolve( $params );
        $link = $resolved['href'];
        if ( $link === '' && isset( $params['url'] ) )
            $link = expLayoutsSafeValue::url( $params['url'] );

        $style = isset( $params['style'] ) ? $params['style'] : 'default_button';
        $class = '';
        switch ( $style )
        {
            case 'default_button':
                $class = 'btn btn-default';
                break;
            case 'highlighted_button':
            case 'primary':
                $class = 'btn btn-primary';
                break;
            case 'link':
                $class = 'link';
                break;
            default:
                // a style stored before the select (free text): class names only
                $class = expLayoutsSafeValue::cssClass( $style );
        }

        return array(
            'text' => $text,
            'label' => $text,
            'link' => $link,
            'url' => $link,
            'style' => $style,
            'class' => $class,
            // "Open in new window" on the compound link wins over the older
            // free-text target, which stays for buttons that still carry one.
            'target' => $resolved['target'] !== ''
                ? $resolved['target']
                : ( isset( $params['target'] ) && $params['target'] === '_blank' ? '_blank' : '_self' ),
        );
    }
}
