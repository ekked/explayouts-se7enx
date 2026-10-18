<?php
/**
 * The blocks and layout types brought to the state of netgen/layouts-standard: markdown, view types of the containers,
 * layout types, external video, map, gallery design parameters, title, button and the component blocks. Without the
 * kernel: the handlers are loaded from this extension, the settings are read from settings/explayouts.ini.append.php
 * as a file.
 *
 * Run with:
 *   vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php extension/explayouts/tests/NetgenParityBlocksTest.php
 *
 * @license GPL-2.0-or-later
 */
use PHPUnit\Framework\TestCase;

class NetgenParityBlocksTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $classes = dirname( __DIR__ ) . '/classes/';
        if ( !interface_exists( 'expLayoutsBlockHandlerInterface', false ) )
            require_once $classes . 'explayoutsblockhandlerinterface.php';
        foreach ( array(
            'expLayoutsSafeValue' => 'explayoutssafevalue.php',
            'expLayoutsMarkdown' => 'explayoutsmarkdown.php',
            'expLayoutsMarkdownBlockHandler' => 'explayoutsmarkdownblockhandler.php',
            'expLayoutsVideoBlockHandler' => 'explayoutsvideoblockhandler.php',
            'expLayoutsMapBlockHandler' => 'explayoutsmapblockhandler.php',
            'expLayoutsLinkParameter' => 'explayoutslinkparameter.php',
            'expLayoutsTitleBlockHandler' => 'explayoutstitleblockhandler.php',
            'expLayoutsButtonBlockHandler' => 'explayoutsbuttonblockhandler.php',
            'expLayoutsComponentBlockHandler' => 'explayoutscomponentblockhandler.php',
            'expLayoutsFeaturesBlockHandler' => 'explayoutsfeaturesblockhandler.php',
        ) as $class => $file )
        {
            if ( !class_exists( $class, false ) )
                require_once $classes . $file;
        }
    }

    protected static function ini()
    {
        return file_get_contents( dirname( __DIR__ ) . '/settings/explayouts.ini.append.php' );
    }

    /** The lines of one INI group */
    protected static function group( $name )
    {
        if ( !preg_match( '/^\[' . preg_quote( $name, '/' ) . '\]\n(.*?)(?=^\[|\*\/)/ms', self::ini(), $m ) )
            return null;
        return $m[1];
    }

    protected static function listOf( $group, $key )
    {
        preg_match_all( '/^' . preg_quote( $key, '/' ) . '\[\]=(.+)$/m', (string)self::group( $group ), $m );
        return $m[1];
    }

    // ------------------------------------------------------------------
    // Markdown
    // ------------------------------------------------------------------

    public function testTheOwnParserRendersTheCommonPartOfMarkdown()
    {
        $html = expLayoutsMarkdown::toHtml( "### Heading\n\nSome *em* and **strong** and `code`.\n\n- one\n- two [link](https://example.org/?a=1&b=2)\n\n1. first\n2. second\n\n> quoted\n\n```\n<b>code</b>\n```", false );

        $this->assertStringContainsString( '<h3>Heading</h3>', $html );
        $this->assertStringContainsString( '<p>Some <em>em</em> and <strong>strong</strong> and <code>code</code>.</p>', $html );
        $this->assertStringContainsString( '<ul><li>one</li><li>two <a href="https://example.org/?a=1&amp;b=2">link</a></li></ul>', $html );
        $this->assertStringContainsString( '<ol><li>first</li><li>second</li></ol>', $html );
        $this->assertStringContainsString( '<blockquote><p>quoted</p></blockquote>', $html );
        $this->assertStringContainsString( '<pre><code>&lt;b&gt;code&lt;/b&gt;</code></pre>', $html );
    }

    public static function unsafeMarkdown()
    {
        return array(
            'html' => array( '<script>alert(1)</script> <img src=x onerror=alert(1)>' ),
            'javascript link' => array( '[x](javascript:alert)' ),
            'entity link' => array( '[x](&#106;avascript:alert)' ),
            'data link' => array( '[x](data:text/html;base64,PHN2Zz4=)' ),
            'attribute break' => array( '[x](https://a.example/"onmouseover="alert)' ),
            'image from another host' => array( '![x](https://tracker.example/pixel.png)' ),
            'protocol relative image' => array( '![x](//tracker.example/pixel.png)' ),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'unsafeMarkdown' )]
    public function testTheOwnParserWritesNoMarkupFromTheText( $text )
    {
        $html = expLayoutsMarkdown::toHtml( $text, false );

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringNotContainsString( '<img src="x"', $html );
        $this->assertDoesNotMatchRegularExpression( '/\son[a-z]+="/i', $html );
        $this->assertDoesNotMatchRegularExpression( '/href="(?:javascript|data|&#)/i', $html );
        $this->assertDoesNotMatchRegularExpression( '/<img[^>]+src="(?:https?:)?\/\//i', $html, 'no image from another host' );
    }

    public function testAnImageOfTheSiteIsShownOneOfAnotherHostIsALink()
    {
        $this->assertStringContainsString( '<img src="/var/storage/a.png" alt="A" />', expLayoutsMarkdown::toHtml( '![A](/var/storage/a.png)', false ) );
        $this->assertStringContainsString( '<a href="https://example.org/a.png">A</a>', expLayoutsMarkdown::toHtml( '![A](https://example.org/a.png)', false ) );
    }

    public function testTheLibraryIsConfiguredSafelyWhenItIsInstalled()
    {
        if ( !expLayoutsMarkdown::hasLibrary() )
            $this->markTestSkipped( 'league/commonmark is not installed' );

        foreach ( self::unsafeMarkdown() as $case )
        {
            $html = expLayoutsMarkdown::toHtml( $case[0] );
            $this->assertStringNotContainsString( '<script', $html );
            $this->assertDoesNotMatchRegularExpression( '/href="(?:javascript|data|&#)/i', $html );
            $this->assertDoesNotMatchRegularExpression( '/<img[^>]+src="(?:https?:)?\/\//i', $html );
        }
        $this->assertStringContainsString( '<h2>Heading</h2>', expLayoutsMarkdown::toHtml( '## Heading' ) );
    }

    public function testTheMarkdownBlockGivesTheRenderedHtml()
    {
        $values = ( new expLayoutsMarkdownBlockHandler() )->getValues( array( 'parameters' => array( 'content' => "# T\n\n*x*" ) ) );
        $this->assertSame( "# T\n\n*x*", $values['content'] );
        $this->assertStringContainsString( '<h1>T</h1>', $values['html'] );
        $this->assertStringContainsString( '<em>x</em>', $values['html'] );
        $this->assertSame( '', ( new expLayoutsMarkdownBlockHandler() )->getValues( array() )['html'] );
    }

    // ------------------------------------------------------------------
    // Containers and layout types
    // ------------------------------------------------------------------

    public function testTheContainersHaveTheViewTypesOfTheReference()
    {
        $this->assertSame( array( 'two_columns_50_50', 'two_columns_33_66', 'two_columns_66_33' ), self::listOf( 'BlockDefinition_two_columns', 'ViewTypes' ) );
        $this->assertSame( array( 'three_columns_33_33_33', 'three_columns_50_25_25', 'three_columns_25_50_25', 'three_columns_25_25_50' ),
            self::listOf( 'BlockDefinition_three_columns', 'ViewTypes' ) );
        $this->assertSame( array( 'four_columns' ), self::listOf( 'BlockDefinition_four_columns', 'ViewTypes' ) );
        foreach ( array( 'two_columns', 'three_columns', 'four_columns' ) as $definition )
        {
            foreach ( self::listOf( 'BlockDefinition_' . $definition, 'ViewTypes' ) as $viewType )
                $this->assertMatchesRegularExpression( '/^ViewTypeNames\[' . $viewType . '\]=\d+(?::\d+)+$/m', self::group( 'BlockDefinition_' . $definition ), $viewType );
        }
    }

    public function testTheColumnTemplatesKnowEveryViewType()
    {
        $dir = dirname( __DIR__ ) . '/design/standard/templates/explayouts/block/';
        foreach ( array( 'two_columns', 'three_columns' ) as $definition )
        {
            $template = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( $dir . $definition . '.tpl' ) );
            $this->assertStringNotContainsString( '{def', $template, "$definition: containers nest, a {def} would be shared" );
            foreach ( self::listOf( 'BlockDefinition_' . $definition, 'ViewTypes' ) as $viewType )
            {
                if ( !in_array( $viewType, array( 'two_columns_50_50', 'three_columns_33_33_33' ), true ) )
                    $this->assertStringContainsString( "'$viewType'", $template, $viewType );
            }
        }
    }

    public function testTheSixLayoutTypesOfTheReferenceWithTheirZonesAndTemplates()
    {
        $zones = array(
            'layout_1' => array( 'main' ),
            'layout_2' => array( 'header', 'post_header', 'main', 'pre_footer', 'footer' ),
            'layout_3' => array( 'header', 'post_header', 'left', 'right', 'pre_footer', 'footer' ),
            'layout_4' => array( 'header', 'post_header', 'left', 'right', 'pre_footer', 'footer' ),
            'layout_5' => array( 'header', 'post_header', 'left', 'right', 'pre_footer', 'footer' ),
            'layout_6' => array( 'header', 'post_header', 'top', 'left', 'center', 'right', 'pre_footer', 'footer' ),
        );
        $grid = array( 'layout_3' => array( 'col-lg-4', 'col-lg-8' ), 'layout_4' => array( 'col-lg-8', 'col-lg-4' ), 'layout_5' => array( 'col-lg-6', 'col-lg-6' ) );
        foreach ( $zones as $type => $expected )
        {
            $this->assertSame( $expected, self::listOf( 'LayoutType_' . $type, 'Zones' ), $type );
            $this->assertSame( 1, preg_match( '/^Template=(.+)$/m', self::group( 'LayoutType_' . $type ), $m ), $type );
            $template = file_get_contents( dirname( __DIR__ ) . '/design/standard/templates/' . $m[1] );
            foreach ( $expected as $zone )
                $this->assertStringContainsString( "identifier='$zone'", $template, "$type: $zone" );
            if ( isset( $grid[$type] ) )
            {
                preg_match_all( '/class="(col-lg-\d+)"/', $template, $columns );
                $this->assertSame( $grid[$type], $columns[1], $type );
            }
        }
    }

    // ------------------------------------------------------------------
    // External video and map
    // ------------------------------------------------------------------

    public function testAServiceAndAVideoIdGiveThePlayer()
    {
        $this->assertSame( 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', expLayoutsVideoBlockHandler::serviceUrl( 'youtube', 'dQw4w9WgXcQ' ) );
        $this->assertSame( 'https://player.vimeo.com/video/76979871?dnt=1', expLayoutsVideoBlockHandler::serviceUrl( 'vimeo', '76979871' ) );
        $this->assertSame( 'https://www.dailymotion.com/embed/video/x7tgad0', expLayoutsVideoBlockHandler::serviceUrl( 'dailymotion', 'x7tgad0' ) );
        foreach ( array( array( 'youtube', '../x' ), array( 'youtube', 'a?b' ), array( 'vimeo', 'abc' ), array( 'other', 'abc' ), array( 'youtube', '' ) ) as $case )
            $this->assertSame( '', expLayoutsVideoBlockHandler::serviceUrl( $case[0], $case[1] ), implode( ' ', $case ) );

        $handler = new expLayoutsVideoBlockHandler();
        $values = $handler->getValues( array( 'parameters' => array( 'service' => 'vimeo', 'video_id' => '42', 'caption' => 'C', 'video_url' => 'https://example.org/old' ) ) );
        $this->assertSame( 'https://player.vimeo.com/video/42?dnt=1', $values['video_url'], 'the service wins over the address' );
        $this->assertSame( 'C', $values['caption'] );
        $values = $handler->getValues( array( 'parameters' => array( 'video_url' => 'javascript:alert(1)' ) ) );
        $this->assertSame( '', $values['video_url'] );
        $values = $handler->getValues( array( 'parameters' => array( 'video_url' => 'https://example.org/player' ) ) );
        $this->assertSame( 'https://example.org/player', $values['video_url'], 'the address of an existing block stays' );

        $parameters = $handler->getParameters();
        $this->assertSame( array( '', 'youtube', 'vimeo', 'dailymotion' ), array_map( 'strval', array_keys( $parameters['service']['options'] ) ) );
    }

    public function testAMapAroundAPoint()
    {
        $handler = new expLayoutsMapBlockHandler();
        $values = $handler->getValues( array( 'parameters' => array( 'latitude' => '54,0921', 'longitude' => '12.129', 'zoom' => '15', 'map_type' => 'SATELLITE', 'show_marker' => '1' ) ) );
        $this->assertSame( 'openstreetmap', $values['provider'] );
        $this->assertStringStartsWith( 'https://www.openstreetmap.org/export/embed.html?bbox=', $values['embed_url'] );
        $this->assertStringContainsString( '&marker=54.0921%2C12.129', $values['embed_url'] );
        $this->assertSame( 15, $values['zoom'] );

        $values = $handler->getValues( array( 'parameters' => array( 'latitude' => '54.0921', 'longitude' => '12.129', 'show_marker' => '0' ) ) );
        $this->assertStringNotContainsString( 'marker=', $values['embed_url'] );

        $google = expLayoutsMapBlockHandler::pointUrl( 54.0921, 12.129, 12, 'HYBRID', true, 'k&y' );
        $this->assertSame( 'https://www.google.com/maps/embed/v1/place?key=k%26y&q=54.0921,12.129&zoom=12&maptype=satellite', $google );
        $this->assertStringStartsWith( 'https://www.google.com/maps/embed/v1/view?', expLayoutsMapBlockHandler::pointUrl( 1, 2, 3, 'TERRAIN', false, 'k' ) );
        $this->assertStringContainsString( 'maptype=roadmap', expLayoutsMapBlockHandler::pointUrl( 1, 2, 3, 'TERRAIN', false, 'k' ) );

        foreach ( array( '91', 'x', '1e3', '12.3.4', '' ) as $bad )
            $this->assertNull( expLayoutsMapBlockHandler::coordinate( $bad, 90 ), $bad );
        $values = $handler->getValues( array( 'parameters' => array( 'latitude' => '"><script>', 'longitude' => '1', 'zoom' => '99' ) ) );
        $this->assertSame( '', $values['embed_url'] );
        $this->assertSame( 20, $values['zoom'] );

        $values = $handler->getValues( array( 'parameters' => array( 'embed_url' => 'https://www.openstreetmap.org/export/embed.html?bbox=1,2,3,4' ) ) );
        $this->assertSame( 'embed', $values['provider'], 'the address of an existing block stays' );
    }

    public function testTheStandardTemplatesLoadVideoAndMapAfterAClick()
    {
        $dir = dirname( __DIR__ ) . '/design/standard/templates/explayouts/block/';
        foreach ( array( 'video.tpl', 'map.tpl' ) as $file )
        {
            $template = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( $dir . $file ) );
            $this->assertStringNotContainsString( '<iframe', $template, $file );
            $this->assertStringNotContainsString( 'style=', $template, $file );
            $this->assertStringContainsString( "design:explayouts/block/parts/embed.tpl", $template, $file );
        }
        $embed = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( $dir . 'parts/embed.tpl' ) );
        $this->assertStringNotContainsString( '<iframe', $embed );
        $this->assertDoesNotMatchRegularExpression( '/\son[a-z]+\s*=/i', $embed );
        $script = file_get_contents( dirname( __DIR__ ) . '/design/standard/javascript/explayouts_embed.js' );
        $this->assertStringContainsString( "'sandbox'", $script );
        $this->assertStringNotContainsString( "allow-top-navigation'", $script );
        $this->assertStringContainsString( "var SANDBOX = 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation';", $script );
    }

    // ------------------------------------------------------------------
    // Title, button, component blocks
    // ------------------------------------------------------------------

    public function testTheTitleOffersTheElementsAsASelect()
    {
        $parameters = ( new expLayoutsTitleBlockHandler() )->getParameters();
        $this->assertSame( 'select', $parameters['tag']['type'] );
        foreach ( array_keys( $parameters['tag']['options'] ) as $tag )
        {
            if ( $tag !== '' )
                $this->assertContains( $tag, expLayoutsSafeValue::HEADING_TAGS );
        }
        $this->assertSame( array( 1, 6 ), array( $parameters['level']['min'], $parameters['level']['max'] ) );
    }

    public function testTheButtonOffersTheStylesOfTheReference()
    {
        $handler = new expLayoutsButtonBlockHandler();
        $parameters = $handler->getParameters();
        $this->assertSame( array( 'default_button', 'highlighted_button', 'link' ), array_keys( $parameters['style']['options'] ) );
        $this->assertSame( array( '_self', '_blank' ), array_keys( $parameters['target']['options'] ) );

        $values = $handler->getValues( array( 'parameters' => array( 'text' => 'T', 'style' => 'x" onclick="y', 'target' => 'evil' ) ) );
        $this->assertSame( '', $values['class'], 'class names only' );
        $this->assertSame( '_self', $values['target'] );
        $this->assertSame( 'btn btn-primary', $handler->getValues( array( 'parameters' => array( 'style' => 'highlighted_button' ) ) )['class'] );
    }

    public function testTheComponentBlocksHaveAHeadingLevel()
    {
        $handler = new expLayoutsComponentBlockHandler();
        $this->assertSame( 'select', $handler->getParameters()['level']['type'] );
        $this->assertSame( 3, $handler->getValues( array( 'parameters' => array( 'level' => '3' ) ) )['level'] );
        $this->assertSame( 0, $handler->getValues( array( 'parameters' => array( 'level' => '9' ) ) )['level'] );
        $this->assertSame( 0, $handler->getValues( array() )['level'] );
    }

    public function testTheFeaturesBlockReadsItsList()
    {
        $this->assertSame( 'Handler=expLayoutsFeaturesBlockHandler', trim( preg_replace( '/^(?!Handler=).*$/m', '', self::group( 'BlockDefinition_features' ) ) ) );
        $handler = new expLayoutsFeaturesBlockHandler();
        $keys = array_keys( $handler->getParameters() );
        $this->assertSame( array_search( 'content', $keys ) + 1, array_search( 'items', $keys ) );

        $values = $handler->getValues( array( 'parameters' => array( 'title' => 'F', 'items' => "One|First\r\n\nTwo\nThree | Third | more" ) ) );
        $this->assertSame( array(
            array( 'title' => 'One', 'text' => 'First' ),
            array( 'title' => 'Two', 'text' => '' ),
            array( 'title' => 'Three', 'text' => 'Third | more' ),
        ), $values['items'] );
        $this->assertSame( 'F', $values['title'] );
    }
}
