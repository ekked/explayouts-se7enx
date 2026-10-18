<?php
/**
 * Markdown of the markdown block, as HTML.
 *
 * With league/commonmark installed (the reference renders the block with twig/markdown-extra, which uses it), the
 * CommonMark converter renders it: HTML in the text is escaped (html_input=escape), links with a scheme a browser
 * runs as script are dropped (allow_unsafe_links=false), the nesting is limited, and afterwards a link whose address
 * is not one expLayoutsSafeValue::url() allows becomes its text and an image from another host becomes a link to it
 * (an image is a request to that host as the page loads; a link is one only when somebody follows it).
 *
 * Without the library a small parser of its own renders the common part of Markdown: ATX headings, paragraphs, lists
 * (one level), block quotes, fenced and indented code, rules, emphasis, strong, code spans, links and images (images
 * of this site only). Everything else stays text. Every piece of text is escaped before markup is added, so nothing
 * but the elements this class writes reaches the page.
 *
 * @license GPL-2.0-or-later
 */
class expLayoutsMarkdown
{
    /** How deep blocks may nest (lists in quotes in lists ...) for the CommonMark converter */
    const MAX_NESTING_LEVEL = 10;

    /** The longest text rendered; the rest is cut off (a block is not a document) */
    const MAX_LENGTH = 100000;

    /**
     * @param mixed $text
     * @param bool|null $useLibrary null: league/commonmark when it is installed; false: always the own parser
     * @return string HTML
     */
    public static function toHtml( $text, $useLibrary = null )
    {
        $text = is_scalar( $text ) ? (string)$text : '';
        if ( strlen( $text ) > self::MAX_LENGTH )
            $text = substr( $text, 0, self::MAX_LENGTH );
        // invalid UTF-8 would make htmlspecialchars() return '' and the converter throw
        if ( function_exists( 'mb_convert_encoding' ) && !mb_check_encoding( $text, 'UTF-8' ) )
            $text = mb_convert_encoding( $text, 'UTF-8', 'UTF-8' );
        if ( trim( $text ) === '' )
            return '';

        if ( $useLibrary !== false && self::hasLibrary() )
        {
            try
            {
                return self::withLibrary( $text );
            }
            catch ( Throwable $e )
            {
                if ( class_exists( 'eZDebug', false ) )
                    eZDebug::writeError( 'Markdown block: ' . $e->getMessage(), __METHOD__ );
            }
        }
        return self::withOwnParser( $text );
    }

    public static function hasLibrary()
    {
        return class_exists( 'League\\CommonMark\\CommonMarkConverter' );
    }

    // ------------------------------------------------------------------
    // league/commonmark
    // ------------------------------------------------------------------

    protected static function withLibrary( $text )
    {
        $converter = new League\CommonMark\CommonMarkConverter( array(
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => self::MAX_NESTING_LEVEL,
        ) );
        $converter->getEnvironment()->addEventListener(
            'League\\CommonMark\\Event\\DocumentParsedEvent',
            array( __CLASS__, 'checkLinksAndImages' )
        );
        return (string)$converter->convert( $text );
    }

    /**
     * After parsing: a link with an address expLayoutsSafeValue::url() does not allow is replaced by its text, an
     * image whose address is not a path on this site by a link to it with the image's text.
     *
     * @param League\CommonMark\Event\DocumentParsedEvent $event
     */
    public static function checkLinksAndImages( $event )
    {
        $found = array();
        $walker = $event->getDocument()->walker();
        while ( $step = $walker->next() )
        {
            $node = $step->getNode();
            if ( $step->isEntering()
                 && ( $node instanceof League\CommonMark\Extension\CommonMark\Node\Inline\Link
                      || $node instanceof League\CommonMark\Extension\CommonMark\Node\Inline\Image ) )
                $found[] = $node;
        }

        foreach ( $found as $node )
        {
            $url = (string)$node->getUrl();
            if ( $node instanceof League\CommonMark\Extension\CommonMark\Node\Inline\Image )
            {
                if ( self::isLocalPath( $url ) )
                    continue;
                $replacement = expLayoutsSafeValue::url( $url ) !== ''
                    ? new League\CommonMark\Extension\CommonMark\Node\Inline\Link( $url )
                    : null;
            }
            else
            {
                if ( expLayoutsSafeValue::url( $url ) !== '' )
                    continue;
                $replacement = null;
            }
            self::replaceWithChildren( $node, $replacement );
        }
    }

    /** $node replaced by $replacement holding its children, or by its children alone */
    protected static function replaceWithChildren( $node, $replacement )
    {
        $children = $node->children();
        if ( $replacement )
        {
            foreach ( $children as $child )
                $replacement->appendChild( $child );
            $node->replaceWith( $replacement );
            return;
        }
        foreach ( $children as $child )
            $node->insertBefore( $child );
        $node->detach();
    }

    // ------------------------------------------------------------------
    // Own parser
    // ------------------------------------------------------------------

    protected static function withOwnParser( $text )
    {
        $lines = preg_split( '/\r\n|\r|\n/', str_replace( "\t", '    ', $text ) );
        $html = array();
        $paragraph = array();
        $count = count( $lines );

        $flush = function () use ( &$paragraph, &$html )
        {
            if ( $paragraph )
                $html[] = '<p>' . self::inline( implode( "\n", $paragraph ) ) . '</p>';
            $paragraph = array();
        };

        for ( $i = 0; $i < $count; $i++ )
        {
            $line = $lines[$i];

            // fenced code: ``` or ~~~ up to the same fence (or the end)
            if ( preg_match( '/^ {0,3}(`{3,}|~{3,})/', $line, $m ) )
            {
                $flush();
                $fence = $m[1];
                $code = array();
                for ( $i++; $i < $count && !preg_match( '/^ {0,3}' . preg_quote( $fence[0], '/' ) . '{' . strlen( $fence ) . ',}\s*$/', $lines[$i] ); $i++ )
                    $code[] = $lines[$i];
                $html[] = '<pre><code>' . self::escape( implode( "\n", $code ) ) . '</code></pre>';
                continue;
            }

            if ( trim( $line ) === '' )
            {
                $flush();
                continue;
            }

            // indented code (only where no paragraph is open, as in CommonMark)
            if ( !$paragraph && preg_match( '/^ {4}/', $line ) )
            {
                $code = array();
                for ( ; $i < $count && ( preg_match( '/^ {4}/', $lines[$i] ) || trim( $lines[$i] ) === '' ); $i++ )
                    $code[] = (string)substr( $lines[$i], 4 );
                $i--;
                while ( $code && trim( end( $code ) ) === '' )
                    array_pop( $code );
                $html[] = '<pre><code>' . self::escape( implode( "\n", $code ) ) . '</code></pre>';
                continue;
            }

            if ( preg_match( '/^ {0,3}(#{1,6})(?:[ \t]+(.*?))?(?:[ \t]+#+)?[ \t]*$/', $line, $m ) )
            {
                $flush();
                $level = strlen( $m[1] );
                $html[] = '<h' . $level . '>' . self::inline( isset( $m[2] ) ? $m[2] : '' ) . '</h' . $level . '>';
                continue;
            }

            if ( preg_match( '/^ {0,3}([-*_])(?:[ \t]*\1){2,}[ \t]*$/', $line ) )
            {
                $flush();
                $html[] = '<hr />';
                continue;
            }

            if ( preg_match( '/^ {0,3}> ?(.*)$/', $line ) )
            {
                $flush();
                $quoted = array();
                for ( ; $i < $count && preg_match( '/^ {0,3}> ?(.*)$/', $lines[$i], $q ); $i++ )
                    $quoted[] = $q[1];
                $i--;
                // one level: the quote's lines are paragraphs, not nested blocks
                $parts = array();
                foreach ( preg_split( '/\n\s*\n/', implode( "\n", $quoted ) ) as $part )
                {
                    if ( trim( $part ) !== '' )
                        $parts[] = '<p>' . self::inline( trim( $part ) ) . '</p>';
                }
                $html[] = '<blockquote>' . implode( '', $parts ) . '</blockquote>';
                continue;
            }

            if ( preg_match( '/^ {0,3}([-+*]|\d{1,9}[.)])[ \t]+(.*)$/', $line, $m ) )
            {
                $flush();
                $ordered = ctype_digit( substr( $m[1], 0, 1 ) );
                $items = array();
                $start = $ordered ? (int)$m[1] : 1;
                $item = array( $m[2] );
                for ( $i++; $i < $count; $i++ )
                {
                    $next = $lines[$i];
                    if ( preg_match( '/^ {0,3}([-+*]|\d{1,9}[.)])[ \t]+(.*)$/', $next, $n )
                         && ctype_digit( substr( $n[1], 0, 1 ) ) === $ordered )
                    {
                        $items[] = $item;
                        $item = array( $n[2] );
                        continue;
                    }
                    // a continuation line: indented, or text right after the item
                    if ( trim( $next ) !== '' && ( preg_match( '/^ {2,}\S/', $next ) || !preg_match( '/^ {0,3}(#|>|[-*_]{3}|`{3}|~{3})/', $next ) ) )
                    {
                        $item[] = trim( $next );
                        continue;
                    }
                    break;
                }
                $items[] = $item;
                $i--;
                $tag = $ordered ? 'ol' : 'ul';
                $out = '<' . $tag . ( $ordered && $start !== 1 ? ' start="' . $start . '"' : '' ) . '>';
                foreach ( $items as $parts )
                    $out .= '<li>' . self::inline( implode( "\n", $parts ) ) . '</li>';
                $html[] = $out . '</' . $tag . '>';
                continue;
            }

            $paragraph[] = trim( $line );
        }
        $flush();

        return implode( "\n", $html ) . "\n";
    }

    protected static function escape( $text )
    {
        return htmlspecialchars( (string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    }

    /**
     * The inline part of a block: code spans, images, links, strong, emphasis and hard line breaks (two spaces or a
     * backslash at the end of a line). The text is escaped first; the patterns work on the escaped text.
     */
    protected static function inline( $text )
    {
        $tokens = array();
        $keep = function ( $html ) use ( &$tokens )
        {
            $tokens[] = $html;
            return "\x1A" . ( count( $tokens ) - 1 ) . "\x1A";
        };

        // backslash escapes of punctuation: the character as text
        $text = preg_replace_callback( '/\\\\([!"#$%&\'()*+,\-.\/:;<=>?@\[\\\\\]^_`{|}~])/', function ( $m ) use ( $keep )
        {
            return $keep( self::escape( $m[1] ) );
        }, $text );

        // code spans before everything else: their content is text
        $text = preg_replace_callback( '/(`+)(?!`)(.+?)(?<!`)\1(?!`)/s', function ( $m ) use ( $keep )
        {
            $code = $m[2];
            if ( strlen( $code ) > 2 && $code[0] === ' ' && substr( $code, -1 ) === ' ' )
                $code = substr( $code, 1, -1 );
            return $keep( '<code>' . self::escape( $code ) . '</code>' );
        }, $text );

        // images and links: [text](address "title"); the address is checked unescaped, then escaped
        $text = preg_replace_callback( '/(!?)\[([^\[\]\x1A]*(?:\x1A\d+\x1A[^\[\]\x1A]*)*)\]\(\s*<?([^\s()<>]*)>?(?:\s+"([^"]*)")?\s*\)/', function ( $m ) use ( $keep, &$tokens )
        {
            $isImage = $m[1] === '!';
            $label = $m[2];
            $url = $m[3];
            $title = isset( $m[4] ) && $m[4] !== '' ? ' title="' . self::escape( $m[4] ) . '"' : '';
            if ( $isImage && self::isLocalPath( $url ) )
                return $keep( '<img src="' . self::escape( $url ) . '" alt="' . self::escape( self::plain( $label, $tokens ) ) . '"' . $title . ' />' );
            if ( expLayoutsSafeValue::url( $url ) === '' )
                return $keep( self::inlineText( $label, $tokens ) );
            return $keep( '<a href="' . self::escape( $url ) . '"' . $title . '>' . self::inlineText( $label, $tokens ) . '</a>' );
        }, $text );

        // automatic links <https://...>
        $text = preg_replace_callback( '/<(https?:\/\/[^\s<>]+)>/i', function ( $m ) use ( $keep )
        {
            return $keep( '<a href="' . self::escape( $m[1] ) . '">' . self::escape( $m[1] ) . '</a>' );
        }, $text );

        return self::inlineText( $text, $tokens );
    }

    /** Escaped text with strong, emphasis and line breaks; the kept pieces put back when $tokens is given */
    protected static function inlineText( $text, ?array $tokens = null )
    {
        $text = self::escape( $text );
        $text = preg_replace( '/(\*\*|__)(?=\S)(.+?)(?<=\S)\1/s', '<strong>$2</strong>', $text );
        $text = preg_replace( '/(?<![\w*])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![\w*])/s', '<em>$1</em>', $text );
        $text = preg_replace( '/(?<![\w_])_(?=[^\s_])(.+?)(?<=[^\s_])_(?![\w_])/s', '<em>$1</em>', $text );
        $text = preg_replace( '/(?: {2,}|\\\\)\n/', "<br />\n", $text );

        if ( $tokens === null )
            return $text;
        // pieces kept inside pieces (a code span in a link text) need more than one pass
        for ( $pass = 0; $pass < 3 && strpos( $text, "\x1A" ) !== false; $pass++ )
        {
            $text = preg_replace_callback( '/\x1A(\d+)\x1A/', function ( $m ) use ( $tokens )
            {
                return isset( $tokens[(int)$m[1]] ) ? $tokens[(int)$m[1]] : '';
            }, $text );
        }
        return str_replace( "\x1A", '', $text );
    }

    /** The text of a label without its markup, for an alt attribute */
    protected static function plain( $label, array $tokens )
    {
        $label = preg_replace_callback( '/\x1A(\d+)\x1A/', function ( $m ) use ( $tokens )
        {
            return isset( $tokens[(int)$m[1]] ) ? html_entity_decode( strip_tags( $tokens[(int)$m[1]] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
        }, $label );
        return trim( str_replace( array( '*', '_' ), '', $label ) );
    }

    /**
     * An address on this site: a path starting with one slash (not //host), no scheme, no backslash.
     */
    public static function isLocalPath( $url )
    {
        $url = trim( (string)$url );
        return $url !== '' && $url[0] === '/' && ( !isset( $url[1] ) || ( $url[1] !== '/' && $url[1] !== '\\' ) )
            && strpos( $url, '\\' ) === false && expLayoutsSafeValue::url( $url ) !== '';
    }
}
