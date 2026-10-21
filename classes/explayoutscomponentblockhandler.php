<?php
class expLayoutsComponentBlockHandler implements expLayoutsBlockHandlerInterface
{
    public function getParameters()
    {
        return array(
            'title' => array(
                'name' => 'Title',
                'type' => 'text',
                'default' => '',
            ),
            // The heading of the title, h1-h6. Empty: the one of the block's template (h1 for a hero, h2 for the
            // others; a design may set its own, as cjw_bootstrap5 does with HeroHeadingLevel).
            'level' => array(
                'name' => 'Heading level',
                'type' => 'select',
                'options' => array(
                    '' => 'Default of the design',
                    '1' => 'Heading 1',
                    '2' => 'Heading 2',
                    '3' => 'Heading 3',
                    '4' => 'Heading 4',
                    '5' => 'Heading 5',
                    '6' => 'Heading 6',
                ),
                'default' => '',
            ),
            'content' => array(
                'name' => 'Content',
                'type' => 'textarea',
                'default' => '',
            ),
            'image_url' => array(
                'name' => 'Image URL',
                'type' => 'string',
                'default' => '',
            ),
            'image_alt' => array(
                'name' => 'Image alt text',
                'type' => 'string',
                'default' => '',
            ),
            'link_text' => array(
                'name' => 'Link text',
                'type' => 'string',
                'default' => '',
            ),
            'link_url' => array(
                'name' => 'Link URL',
                'type' => 'string',
                'default' => '',
            ),
            'css_class' => array(
                'name' => 'CSS class',
                'type' => 'string',
                'default' => '',
            ),
            'css_id' => array(
                'name' => 'CSS ID',
                'type' => 'string',
                'default' => '',
            ),
        );
    }

    public function getValues( $block )
    {
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
        return array(
            'title' => isset( $params['title'] ) ? $params['title'] : '',
            // 1-6, or 0: the template's own heading
            'level' => isset( $params['level'] ) && in_array( (int)$params['level'], array( 1, 2, 3, 4, 5, 6 ), true ) ? (int)$params['level'] : 0,
            'content' => isset( $params['content'] ) ? $params['content'] : '',
            'image_url' => isset( $params['image_url'] ) ? $params['image_url'] : '',
            'image_alt' => isset( $params['image_alt'] ) ? $params['image_alt'] : '',
            'link_text' => isset( $params['link_text'] ) ? $params['link_text'] : '',
            'link_url' => isset( $params['link_url'] ) ? expLayoutsSafeValue::url( $params['link_url'] ) : '',
            // printed into class="...": class names only
            'css_class' => isset( $params['css_class'] ) ? expLayoutsSafeValue::cssClass( $params['css_class'] ) : '',
            'css_id' => isset( $params['css_id'] ) ? $params['css_id'] : '',
        );
    }
}
