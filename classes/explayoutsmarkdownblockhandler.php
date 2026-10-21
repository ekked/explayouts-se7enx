<?php
class expLayoutsMarkdownBlockHandler implements expLayoutsBlockHandlerInterface
{
    public function getParameters()
    {
        return array(
            'content' => array(
                'name' => 'Markdown content',
                'type' => 'textarea',
                'default' => '',
            ),
        );
    }

    public function getValues( $block )
    {
        $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
        $content = isset( $params['content'] ) ? (string)$params['content'] : '';
        return array(
            'content' => $content,
            // rendered as the reference renders it (markdown_to_html), with HTML in the text escaped and unsafe links
            // dropped (expLayoutsMarkdown): the templates print it as it is
            'html' => expLayoutsMarkdown::toHtml( $content ),
        );
    }
}
