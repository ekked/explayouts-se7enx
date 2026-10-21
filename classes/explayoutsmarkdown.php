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
 * of this site only). Everything else stays text. It is a scanner without backtracking patterns, every piece of text
 * is escaped as it is written, and attributes are built from the raw values, so nothing but the elements this class
 * writes reaches the page and no text makes it slow.
 *
 * Line breaks: as in CommonMark a single line break is a soft one; with explayouts.ini [MarkdownSettings]
 * HardBreaks=enabled it is a <br /> (the block printed the text with nl2br before it rendered Markdown).
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
        // control characters other than tab and line breaks are no text
        $text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text );
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
            // [MarkdownSettings] HardBreaks=enabled: a single line break is a <br /> (as the block's nl2br was)
            'renderer' => array( 'soft_break' => self::hardBreaks() ? "<br />\n" : "\n" ),
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
                // a link to the image, unless it stands in a link already (links do not nest): then its text
                $replacement = expLayoutsSafeValue::url( $url ) !== '' && !self::inLink( $node )
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

    /** Does a link (one that stays a link) hold $node? */
    protected static function inLink( $node )
    {
        for ( $parent = $node->parent(); $parent; $parent = $parent->parent() )
        {
            if ( $parent instanceof League\CommonMark\Extension\CommonMark\Node\Inline\Link
                 && expLayoutsSafeValue::url( (string)$parent->getUrl() ) !== '' )
                return true;
        }
        return false;
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
                $html[] = '<p>' . self::inline( rtrim( implode( "\n", $paragraph ) ) ) . '</p>';
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

            if ( ( $heading = self::heading( $line ) ) !== false )
            {
                $flush();
                $html[] = '<h' . $heading[0] . '>' . self::inline( $heading[1] ) . '</h' . $heading[0] . '>';
                continue;
            }

            if ( strspn( $line, ' ' ) <= 3 && preg_match( '/^([-*_])\1{2,}$/', str_replace( array( ' ', "\t" ), '', $line ) ) )
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
                        $item[] = ltrim( $next );
                        continue;
                    }
                    break;
                }
                $items[] = $item;
                $i--;
                $tag = $ordered ? 'ol' : 'ul';
                $out = '<' . $tag . ( $ordered && $start !== 1 ? ' start="' . $start . '"' : '' ) . '>';
                foreach ( $items as $parts )
                    $out .= '<li>' . self::inline( rtrim( implode( "\n", $parts ) ) ) . '</li>';
                $html[] = $out . '</' . $tag . '>';
                continue;
            }

            // trailing spaces stay: two of them make a hard break
            $paragraph[] = ltrim( $line );
        }
        $flush();

        return implode( "\n", $html ) . "\n";
    }

    /**
     * An ATX heading: array( level, text ) or false. Without patterns that backtrack: a line of many spaces made the
     * former pattern slow.
     */
    protected static function heading( $line )
    {
        $indent = strspn( $line, ' ' );
        if ( $indent > 3 )
            return false;
        $level = strspn( $line, '#', $indent );
        if ( $level < 1 || $level > 6 )
            return false;
        $rest = (string)substr( $line, $indent + $level );
        if ( $rest !== '' && $rest[0] !== ' ' && $rest[0] !== "\t" )
            return false;
        $rest = rtrim( $rest, " \t" );
        // a closing sequence of # after a space
        $hashes = strlen( $rest ) - strlen( rtrim( $rest, '#' ) );
        if ( $hashes > 0 && ( strlen( $rest ) === $hashes || in_array( $rest[strlen( $rest ) - $hashes - 1], array( ' ', "\t" ), true ) ) )
            $rest = substr( $rest, 0, -$hashes );
        return array( $level, trim( $rest, " \t" ) );
    }

    protected static function escape( $text )
    {
        return htmlspecialchars( (string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    }

    /** ASCII punctuation, the characters a backslash may escape */
    const PUNCTUATION = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

    /** The longest link label, address and title looked at; longer is text */
    const MAX_LINK_PART = 2000;

    /**
     * The inline part of a block: backslash escapes, code spans, links, images, automatic links, emphasis, strong and
     * line breaks. A scanner, no backtracking patterns: every character is looked at a bounded number of times, so no
     * text makes it slow (the patterns it replaced took seconds on 100 KB of "**a "). Text is escaped as it is
     * written; attributes are built from the raw address and title (backslash escapes resolved) and escaped.
     *
     * @param string $text
     * @param bool $inLink inside the label of a link: no further links, an image of another host is its text
     * @param int $depth labels within labels (an image in a link) are parsed at most this deep
     */
    protected static function inline( $text, $inLink = false, $depth = 0 )
    {
        $text = (string)$text;
        $length = strlen( $text );
        $hardBreaks = self::hardBreaks();
        // the matching ] of each [ outside code spans and escapes, found once with a stack
        $close = self::bracketPairs( $text );

        $pieces = array();   // strings (HTML) and delimiter runs (arrays)
        $buffer = '';
        $flush = function () use ( &$buffer, &$pieces )
        {
            if ( $buffer !== '' )
                $pieces[] = $buffer;
            $buffer = '';
        };
        $noCloser = array(); // backtick run lengths with no closing run after the last search

        for ( $i = 0; $i < $length; )
        {
            $c = $text[$i];

            if ( $c === '\\' && $i + 1 < $length )
            {
                $next = $text[$i + 1];
                if ( $next === "\n" )
                {
                    $buffer .= "<br />\n";
                    $i += 2;
                    continue;
                }
                if ( strpos( self::PUNCTUATION, $next ) !== false )
                {
                    $buffer .= self::escape( $next );
                    $i += 2;
                    continue;
                }
            }

            if ( $c === '`' )
            {
                $run = strspn( $text, '`', $i );
                $end = isset( $noCloser[$run] ) && $noCloser[$run] <= $i ? false : self::closingBackticks( $text, $i + $run, $run );
                if ( $end === false )
                {
                    $noCloser[$run] = $i;
                    $buffer .= str_repeat( '`', $run );
                    $i += $run;
                    continue;
                }
                $code = str_replace( "\n", ' ', substr( $text, $i + $run, $end - $i - $run ) );
                if ( strlen( $code ) > 2 && $code[0] === ' ' && substr( $code, -1 ) === ' ' && trim( $code ) !== '' )
                    $code = substr( $code, 1, -1 );
                $buffer .= '<code>' . self::escape( $code ) . '</code>';
                $i = $end + $run;
                continue;
            }

            if ( ( $c === '[' || ( $c === '!' && $i + 1 < $length && $text[$i + 1] === '[' ) ) && $depth < 2 )
            {
                $isImage = $c === '!';
                $open = $isImage ? $i + 1 : $i;
                $link = isset( $close[$open] ) ? self::linkAt( $text, $open, $close[$open] ) : false;
                if ( $link !== false && ( $isImage || !$inLink ) )
                {
                    $flush();
                    $pieces[] = self::renderLink( $isImage, $link, $inLink, $depth );
                    $i = $link['end'];
                    continue;
                }
            }

            if ( $c === '<' && !$inLink && preg_match( '/\Ghttps?:\/\/[^\s<>]{1,' . self::MAX_LINK_PART . '}>/i', $text, $m, 0, $i + 1 ) )
            {
                $url = substr( $m[0], 0, -1 );
                $buffer .= '<a href="' . self::escape( $url ) . '">' . self::escape( $url ) . '</a>';
                $i += strlen( $m[0] ) + 1;
                continue;
            }

            if ( $c === '*' || $c === '_' )
            {
                $run = strspn( $text, $c, $i );
                $before = $i > 0 ? $text[$i - 1] : "\n";
                $after = $i + $run < $length ? $text[$i + $run] : "\n";
                $left = !ctype_space( $after ) && ( strpos( self::PUNCTUATION, $after ) === false || ctype_space( $before ) || strpos( self::PUNCTUATION, $before ) !== false );
                $right = !ctype_space( $before ) && ( strpos( self::PUNCTUATION, $before ) === false || ctype_space( $after ) || strpos( self::PUNCTUATION, $after ) !== false );
                if ( $c === '*' )
                {
                    $canOpen = $left;
                    $canClose = $right;
                }
                else
                {
                    $canOpen = $left && ( !$right || strpos( self::PUNCTUATION, $before ) !== false );
                    $canClose = $right && ( !$left || strpos( self::PUNCTUATION, $after ) !== false );
                }
                $flush();
                $pieces[] = array( 'char' => $c, 'count' => $run, 'open' => $canOpen, 'close' => $canClose, 'before' => '', 'after' => '' );
                $i += $run;
                continue;
            }

            if ( $c === "\n" )
            {
                // two spaces or more at the end of a line: a hard break; otherwise a soft one
                $trimmed = rtrim( $buffer, ' ' );
                $hard = strlen( $buffer ) - strlen( $trimmed ) >= 2;
                $buffer = $trimmed . ( $hard || $hardBreaks ? "<br />\n" : "\n" );
                $i++;
                continue;
            }

            // plain text up to the next character with a meaning
            $span = strcspn( $text, "\\`[!<*_\n", $i );
            if ( $span === 0 )
                $span = 1;
            $buffer .= self::escape( substr( $text, $i, $span ) );
            $i += $span;
        }
        $flush();

        return self::emphasis( $pieces );
    }

    /** explayouts.ini [MarkdownSettings] HardBreaks: a single line break of the text is a <br /> */
    protected static function hardBreaks()
    {
        if ( self::$hardBreaks === null )
        {
            self::$hardBreaks = false;
            if ( class_exists( 'eZINI', false ) )
            {
                $ini = eZINI::instance( 'explayouts.ini' );
                self::$hardBreaks = $ini->hasVariable( 'MarkdownSettings', 'HardBreaks' ) && $ini->variable( 'MarkdownSettings', 'HardBreaks' ) === 'enabled';
            }
        }
        return self::$hardBreaks;
    }

    /** @var bool|null HardBreaks, read once; tests set it */
    public static $hardBreaks = null;

    /** The position of the closing run of exactly $run backticks from $from, or false */
    protected static function closingBackticks( $text, $from, $run )
    {
        $length = strlen( $text );
        for ( $p = strpos( $text, '`', $from ); $p !== false; $p = strpos( $text, '`', $p ) )
        {
            $found = strspn( $text, '`', $p );
            if ( $found === $run )
                return $p;
            $p += $found;
            if ( $p >= $length )
                break;
        }
        return false;
    }

    /**
     * For each [ the position of its ], skipping backslash escapes and code spans, in one pass with a stack.
     *
     * @return array position of [ => position of ]
     */
    protected static function bracketPairs( $text )
    {
        $pairs = array();
        $stack = array();
        $length = strlen( $text );
        $noCloser = array();
        for ( $i = 0; $i < $length; $i++ )
        {
            $c = $text[$i];
            if ( $c === '\\' )
            {
                $i++;
                continue;
            }
            if ( $c === '`' )
            {
                $run = strspn( $text, '`', $i );
                $end = isset( $noCloser[$run] ) && $noCloser[$run] <= $i ? false : self::closingBackticks( $text, $i + $run, $run );
                if ( $end === false )
                    $noCloser[$run] = $i;
                $i = ( $end === false ? $i + $run : $end + $run ) - 1;
                continue;
            }
            if ( $c === '[' )
                $stack[] = $i;
            elseif ( $c === ']' && $stack )
                $pairs[array_pop( $stack )] = $i;
        }
        return $pairs;
    }

    /**
     * A link at $open (the [) whose label ends at $close (the ]): ( address "title" ) must follow. The address and
     * title come back raw with their backslash escapes resolved.
     *
     * @return array|false label, url, title, end (the position after the closing parenthesis)
     */
    protected static function linkAt( $text, $open, $close )
    {
        $length = strlen( $text );
        if ( $close - $open - 1 > self::MAX_LINK_PART || $close + 1 >= $length || $text[$close + 1] !== '(' )
            return false;
        $p = $close + 2;
        $limit = min( $length, $p + 2 * self::MAX_LINK_PART );
        $p += strspn( $text, " \n", $p );

        $url = '';
        if ( $p < $limit && $text[$p] === '<' )
        {
            for ( $p++; $p < $limit && $text[$p] !== '>'; $p++ )
            {
                if ( $text[$p] === "\n" || $text[$p] === '<' )
                    return false;
                if ( $text[$p] === '\\' && $p + 1 < $limit && strpos( self::PUNCTUATION, $text[$p + 1] ) !== false )
                    $p++;
                $url .= $text[$p];
            }
            if ( $p >= $limit )
                return false;
            $p++;
        }
        else
        {
            $parens = 0;
            for ( ; $p < $limit; $p++ )
            {
                $c = $text[$p];
                if ( ctype_space( $c ) || ctype_cntrl( $c ) )
                    break;
                if ( $c === '\\' && $p + 1 < $limit && strpos( self::PUNCTUATION, $text[$p + 1] ) !== false )
                {
                    $url .= $text[++$p];
                    continue;
                }
                // one level of parentheses in an address: a second ( ends it, so a run of link starts is not read
                // again and again to the end
                if ( $c === '(' )
                {
                    if ( $parens > 0 )
                        break;
                    $parens++;
                }
                elseif ( $c === ')' )
                {
                    if ( $parens === 0 )
                        break;
                    $parens--;
                }
                $url .= $c;
            }
        }

        $title = '';
        $space = strspn( $text, " \n", $p );
        if ( $space > 0 && $p + $space < $limit && strpos( '"\'(', $text[$p + $space] ) !== false )
        {
            $p += $space;
            $quote = $text[$p] === '(' ? ')' : $text[$p];
            for ( $p++; $p < $limit && $text[$p] !== $quote; $p++ )
            {
                // a title in parentheses holds no unescaped (
                if ( $quote === ')' && $text[$p] === '(' )
                    return false;
                if ( $text[$p] === '\\' && $p + 1 < $limit && strpos( self::PUNCTUATION, $text[$p + 1] ) !== false )
                    $p++;
                $title .= $text[$p];
            }
            if ( $p >= $limit )
                return false;
            $p++;
        }
        $p += strspn( $text, " \n", $p );
        if ( $p >= $length || $text[$p] !== ')' )
            return false;

        return array( 'label' => substr( $text, $open + 1, $close - $open - 1 ), 'url' => $url, 'title' => $title, 'end' => $p + 1 );
    }

    /**
     * An address percent-encoded as CommonMark does: characters that may not stand in a URL (space, quotes, <, >, `,
     * non-ASCII ...) and a % that starts no escape.
     */
    protected static function encodeUrl( $url )
    {
        return preg_replace_callback( '/%(?![0-9A-Fa-f]{2})|[^A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]/', function ( $m )
        {
            return rawurlencode( $m[0] );
        }, $url );
    }

    /** The HTML of a link or image; a link with an address that is not allowed is its label */
    protected static function renderLink( $isImage, array $link, $inLink, $depth )
    {
        $url = self::encodeUrl( $link['url'] );
        $title = $link['title'] !== '' ? ' title="' . self::escape( $link['title'] ) . '"' : '';
        if ( $isImage )
        {
            $alt = trim( html_entity_decode( strip_tags( self::inline( $link['label'], true, $depth + 1 ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            if ( self::isLocalPath( $url ) )
                return '<img src="' . self::escape( $url ) . '" alt="' . self::escape( $alt ) . '"' . $title . ' />';
            // an image of another host: a link to it, or its text inside a link (links do not nest)
            if ( $inLink || expLayoutsSafeValue::url( $url ) === '' )
                return self::escape( $alt );
            return '<a href="' . self::escape( $url ) . '"' . $title . '>' . self::escape( $alt ) . '</a>';
        }
        $label = self::inline( $link['label'], true, $depth + 1 );
        if ( expLayoutsSafeValue::url( $url ) === '' )
            return $label;
        return '<a href="' . self::escape( $url ) . '"' . $title . '>' . $label . '</a>';
    }

    /**
     * Emphasis and strong from the delimiter runs, as in CommonMark's "process emphasis" (without its rule of three):
     * a closer is matched with the nearest opener of its character; a search that finds none is not repeated below
     * that point (openers bottom), so the work stays linear.
     */
    protected static function emphasis( array $pieces )
    {
        $stack = array();
        $bottom = array( '*' => 0, '_' => 0 );
        foreach ( $pieces as $index => $piece )
        {
            if ( !is_array( $piece ) )
                continue;
            if ( $piece['close'] )
            {
                while ( $pieces[$index]['count'] > 0 )
                {
                    $char = $pieces[$index]['char'];
                    $found = -1;
                    for ( $s = count( $stack ) - 1; $s >= $bottom[$char]; $s-- )
                    {
                        if ( $pieces[$stack[$s]]['char'] === $char )
                        {
                            $found = $s;
                            break;
                        }
                    }
                    if ( $found < 0 )
                    {
                        $bottom[$char] = count( $stack );
                        break;
                    }
                    $opener = $stack[$found];
                    $use = ( $pieces[$index]['count'] >= 2 && $pieces[$opener]['count'] >= 2 ) ? 2 : 1;
                    $tag = $use === 2 ? 'strong' : 'em';
                    $pieces[$opener]['count'] -= $use;
                    $pieces[$opener]['after'] = '<' . $tag . '>' . $pieces[$opener]['after'];
                    $pieces[$index]['count'] -= $use;
                    $pieces[$index]['before'] .= '</' . $tag . '>';
                    // delimiters between the two stay text
                    array_splice( $stack, $found + 1 );
                    foreach ( array( '*', '_' ) as $ch )
                        $bottom[$ch] = min( $bottom[$ch], count( $stack ) );
                    if ( $pieces[$opener]['count'] === 0 )
                    {
                        array_pop( $stack );
                        foreach ( array( '*', '_' ) as $ch )
                            $bottom[$ch] = min( $bottom[$ch], count( $stack ) );
                    }
                }
            }
            if ( $pieces[$index]['count'] > 0 && $piece['open'] )
                $stack[] = $index;
        }

        $html = '';
        foreach ( $pieces as $piece )
        {
            if ( is_array( $piece ) )
                $html .= $piece['before'] . str_repeat( $piece['char'], $piece['count'] ) . $piece['after'];
            else
                $html .= $piece;
        }
        return $html;
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
