<?php
/**
 * The values of the blocks that go into markup where |wash is not enough (expLayoutsSafeValue), without the kernel.
 *
 * Run directly with:
 *   php tests/explayoutssafevaluetest.php
 *
 *  SV-01 - links: http(s), mailto, tel, relative addresses, #anchors and ?queries stay; javascript:, data:, vbscript:
 *          go, also written with upper case, white space or control characters inside the scheme
 *  SV-02 - embeds (iframe src): http(s), protocol relative and /paths stay; anything else goes
 *  SV-03 - colours: hex, rgb()/hsl() and names stay; anything that could close the declaration goes
 *  SV-04 - the element of a title: h1-h6, p and div, else h<level>, h2 for a level outside 1-6
 *  SV-05 - template names of the template block: letters, digits and _ only
 *  SV-06 - the handlers of title, video, map, image, card, badge, button, progress and template block hand the
 *          templates checked values; the link parameter checks the href after its suffix
 *  SV-07 - the standard templates check again with the template operators, and the operators are registered
 *  SV-09 - class names: letters, digits, _ and - only; the component blocks (hero, features, lead, about) check
 *          link_url and css_class, and their templates print the link unquoted through |ezurl and the text washed
 *  SV-08 - every include of a block template by its definition_identifier (zones, containers) checks the name first:
 *          a stored identifier is not validated (rows from before the block forms checked it, imports)
 */

$failures = 0;
function svCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$root = dirname( __DIR__ );
require_once $root . '/classes/explayoutssafevalue.php';
require_once $root . '/classes/explayoutsblockhandlerinterface.php';
require_once $root . '/classes/explayoutslinkparameter.php';
foreach ( array( 'title', 'video', 'map', 'image', 'card', 'badge', 'button', 'progress', 'tpl', 'component' ) as $kind )
    require_once $root . '/classes/explayouts' . $kind . 'blockhandler.php';

// SV-01
foreach ( array( 'https://example.org/a?b=1#c', 'http://example.org', 'mailto:info@example.org', 'tel:+49 30 1234',
                 '/about/us', 'about/us', '#top', '?page=2', '//cdn.example.org/x.png', '' ) as $kept )
    svCheck( expLayoutsSafeValue::url( $kept ) === $kept, "SV-01 link kept: '$kept'" );
foreach ( array( 'javascript:alert(1)', 'JavaScript:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)",
                 "java\nscript:alert(1)", "\x01javascript:alert(1)", 'data:text/html;base64,PHNjcmlwdD4=', 'vbscript:msgbox',
                 'file:///etc/passwd', '\\\\evil.example/x', ':javascript', '&#106;avascript:alert(1)', 'jav&#x61;script:x',
                 '&amp;#106;avascript:alert(1)', 'javascript&colon;alert(1)' ) as $refused )
    svCheck( expLayoutsSafeValue::url( $refused ) === '', 'SV-01 link refused: ' . json_encode( $refused ) );
svCheck( expLayoutsSafeValue::url( array( 'x' ) ) === '', 'SV-01 a non-scalar is no link' );

// SV-02
foreach ( array( 'https://www.youtube.com/embed/x', 'http://maps.example.org/?q=1', '//player.example.org/1', '/media/video/1' ) as $kept )
    svCheck( expLayoutsSafeValue::embedUrl( $kept ) === $kept, "SV-02 embed kept: '$kept'" );
foreach ( array( 'javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', 'mailto:a@b', '#x', 'video/1', 'tel:1' ) as $refused )
    svCheck( expLayoutsSafeValue::embedUrl( $refused ) === '', 'SV-02 embed refused: ' . json_encode( $refused ) );

// SV-03
foreach ( array( '#38bdf8', '#fff', '#ffff', '#11223344', 'rgb(1, 2, 3)', 'rgba(1,2,3,0.5)', 'hsl(200 50% 40%)', 'hsla(200deg, 50%, 40%, .5)',
                 'var(--brand-color)', 'red', 'transparent' ) as $kept )
    svCheck( expLayoutsSafeValue::color( $kept ) === $kept, "SV-03 colour kept: '$kept'" );
foreach ( array( 'red;background:url(//evil)', '#38bdf8"onmouseover="x', 'expression(alert(1))', 'url(x)', '#12345', 'rgb(1,2,3);x:y',
                 'var(--x);color:red', 'var(--x, url(y))', 'hsl(1 2 3)url(x)' ) as $refused )
    svCheck( expLayoutsSafeValue::color( $refused, '#38bdf8' ) === '#38bdf8', 'SV-03 colour refused, default instead: ' . json_encode( $refused ) );

// SV-04
svCheck( expLayoutsSafeValue::headingTag( 'h3', 2 ) === 'h3', 'SV-04 h3 kept' );
svCheck( expLayoutsSafeValue::headingTag( 'DIV', 2 ) === 'div', 'SV-04 DIV is div' );
svCheck( expLayoutsSafeValue::headingTag( 'script', 4 ) === 'h4', 'SV-04 script is the heading of the level' );
svCheck( expLayoutsSafeValue::headingTag( 'img src=x onerror=alert(1)', 9 ) === 'h2', 'SV-04 markup with a level outside 1-6 is h2' );
svCheck( expLayoutsSafeValue::headingTag( '', 1 ) === 'h1', 'SV-04 no tag: the level' );

// SV-05
svCheck( expLayoutsSafeValue::templateName( 'header_nav2' ) === 'header_nav2', 'SV-05 a template name is kept' );
foreach ( array( '../../../../settings/site.ini', 'a/b', 'Header', 'x.tpl', "a\0b", '' ) as $refused )
    svCheck( expLayoutsSafeValue::templateName( $refused ) === '', 'SV-05 template name refused: ' . json_encode( $refused ) );

// SV-06
$values = function( $handler, array $parameters ) { return $handler->getValues( array( 'parameters' => $parameters ) ); };
$title = $values( new expLayoutsTitleBlockHandler(), array( 'title' => 'T', 'tag' => 'script', 'level' => 3,
                                                            'use_link' => '1', 'link' => 'javascript:alert(1)', 'link:link_type' => 'url' ) );
svCheck( $title['tag'] === 'h3' && $title['link'] === '' && $title['link_href'] === '' && !$title['has_link'], 'SV-06 title: tag and link checked' );
$title = $values( new expLayoutsTitleBlockHandler(), array( 'title' => 'T', 'level' => 12 ) );
svCheck( $title['tag'] === 'h2' && $title['level'] === 2, 'SV-06 title: a level outside 1-6 is 2' );
$video = $values( new expLayoutsVideoBlockHandler(), array( 'video_url' => 'javascript:alert(1)' ) );
svCheck( $video['video_url'] === '', 'SV-06 video: script address refused' );
$video = $values( new expLayoutsVideoBlockHandler(), array( 'video_url' => 'https://www.youtube.com/embed/x' ) );
svCheck( $video['video_url'] === 'https://www.youtube.com/embed/x', 'SV-06 video: https kept' );
$map = $values( new expLayoutsMapBlockHandler(), array( 'embed_url' => 'data:text/html,x' ) );
svCheck( $map['embed_url'] === '', 'SV-06 map: data address refused' );
$image = $values( new expLayoutsImageBlockHandler(), array( 'image_url' => '/x.png', 'link' => 'javascript:alert(1)' ) );
svCheck( $image['link'] === '', 'SV-06 image: link checked' );
$card = $values( new expLayoutsCardBlockHandler(), array( 'link_url' => 'JAVASCRIPT:alert(1)' ) );
svCheck( $card['link_url'] === '', 'SV-06 card: link checked' );
$badge = $values( new expLayoutsBadgeBlockHandler(), array( 'link' => 'vbscript:x' ) );
svCheck( $badge['link'] === '', 'SV-06 badge: link checked' );
$button = $values( new expLayoutsButtonBlockHandler(), array( 'text' => 'Go', 'url' => 'javascript:alert(1)' ) );
svCheck( $button['link'] === '' && $button['url'] === '', 'SV-06 button: legacy url checked' );
$button = $values( new expLayoutsButtonBlockHandler(), array( 'text' => 'Go', 'use_link' => '1', 'link' => 'java', 'link:link_type' => 'url',
                                                             'link:link_suffix' => 'script:alert(1)' ) );
svCheck( $button['link'] === '', 'SV-06 button: value and suffix that make a scheme together are refused' );
$button = $values( new expLayoutsButtonBlockHandler(), array( 'text' => 'Go', 'use_link' => '1', 'link' => 'https://example.org', 'link:link_type' => 'url' ) );
svCheck( $button['link'] === 'https://example.org', 'SV-06 button: https kept' );
svCheck( expLayoutsLinkParameter::href( 'email', 'a@example.org' ) === 'mailto:a@example.org'
         && expLayoutsLinkParameter::href( 'phone', '+49 1' ) === 'tel:+49 1', 'SV-06 link parameter: mailto and tel kept' );
$progress = $values( new expLayoutsProgressBlockHandler(), array( 'value' => 40, 'color' => 'red;background:url(x)' ) );
svCheck( $progress['color'] === '#38bdf8', 'SV-06 progress: a colour or the default' );
$tpl = $values( new expLayoutsTplBlockHandler(), array( 'block_name' => '../../pagelayout' ) );
svCheck( $tpl['block_name'] === '', 'SV-06 template block: no path' );

// SV-07
$templates = $root . '/design/standard/templates/explayouts/block/';
$read = function( $name ) use ( $templates ) { return (string)file_get_contents( $templates . $name . '.tpl' ); };
svCheck( strpos( $read( 'title' ), 'explayouts_safe_tag' ) !== false && strpos( $read( 'title' ), '<{$block.values.tag}' ) === false, 'SV-07 title.tpl checks the element' );
svCheck( strpos( $read( 'tpl_block' ), 'explayouts_safe_template_name' ) !== false, 'SV-07 tpl_block.tpl checks the template name' );
foreach ( array( 'video' => 'explayouts_safe_embed_url', 'map' => 'explayouts_safe_embed_url', 'image' => 'explayouts_safe_url',
                 'card' => 'explayouts_safe_url', 'badge' => 'explayouts_safe_url', 'button' => 'explayouts_safe_url',
                 'progress' => 'explayouts_safe_color' ) as $name => $operator )
    svCheck( strpos( $read( $name ), $operator ) !== false, "SV-07 $name.tpl uses $operator" );
$eZTemplateOperatorArray = array();
include $root . '/autoloads/eztemplateautoload.php';
$registered = array();
foreach ( $eZTemplateOperatorArray as $entry )
{
    if ( $entry['class'] === 'expLayoutsSafeValue' )
        $registered = $entry['operator_names'];
}
$operator = new expLayoutsSafeValue();
svCheck( $registered === $operator->operatorList(), 'SV-07 every operator of expLayoutsSafeValue is registered' );
$value = 'javascript:alert(1)';
$operator->modify( null, 'explayouts_safe_url', array(), '', '', $value, array() );
svCheck( $value === '', 'SV-07 explayouts_safe_url empties a script address' );
$value = 'script';
$operator->modify( null, 'explayouts_safe_tag', array(), '', '', $value, array( 'level' => 5 ) );
svCheck( $value === 'h5', 'SV-07 explayouts_safe_tag falls back to the level' );

// SV-09
svCheck( expLayoutsSafeValue::cssClass( ' hero  is-dark_2 ' ) === 'hero is-dark_2', 'SV-09 class names kept, spaces normalised' );
svCheck( expLayoutsSafeValue::cssClass( 'x" onmouseover="alert(1) y>z ok' ) === 'ok', 'SV-09 tokens with other characters dropped' );
$component = $values( new expLayoutsComponentBlockHandler(), array( 'link_url' => 'javascript:alert(1)', 'css_class' => 'a" b', 'content' => '<b>x</b>' ) );
svCheck( $component['link_url'] === '' && $component['css_class'] === 'b', 'SV-09 component handler: link and class names checked' );
foreach ( array( 'hero', 'features', 'lead', 'about' ) as $name )
{
    $tpl = $read( $name );
    svCheck( strpos( $tpl, 'href={$block.values.link_url|explayouts_safe_url|ezurl}' ) !== false && strpos( $tpl, 'href="{$block.values.link_url' ) === false,
             "SV-09 $name.tpl: link checked, printed by ezurl without extra quotes" );
    svCheck( strpos( $tpl, '{$block.values.css_class|explayouts_safe_css_class|wash}' ) !== false && strpos( $tpl, ' {$block.values.css_class}' ) === false,
             "SV-09 $name.tpl: class names checked and washed" );
}
foreach ( array( 'hero', 'features', 'lead', 'about', 'text' ) as $name )
    svCheck( strpos( $read( $name ), '|wash|nl2br' ) !== false && !preg_match( '/content\|nl2br/', $read( $name ) ), "SV-09 $name.tpl: plain text washed before nl2br" );

// SV-08
$unguarded = array();
$guarded = 0;
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/design/standard/templates/explayouts', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file )
{
    if ( substr( $file->getFilename(), -4 ) !== '.tpl' )
        continue;
    foreach ( file( $file->getPathname() ) as $number => $line )
    {
        if ( !preg_match( "#'design:explayouts/block/'\\s*,\\s*\\$(\\w+)\\.definition_identifier#", $line ) )
            continue;
        if ( preg_match( "#\\{if \\$(\\w+)\\.definition_identifier\\|explayouts_safe_template_name\\}\\{include uri=concat\\( 'design:explayouts/block/', \\$\\1\\.definition_identifier\\|explayouts_safe_template_name, '\\.tpl' \\)#", $line ) )
            $guarded++;
        else
            $unguarded[] = basename( $file->getPathname() ) . ':' . ( $number + 1 );
    }
}
svCheck( $guarded >= 12 && $unguarded === array(), "SV-08 every block include checks the identifier ($guarded guarded)" . ( $unguarded ? ': ' . implode( ', ', $unguarded ) : '' ) );

echo $failures ? "$failures failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
