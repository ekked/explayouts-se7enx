<?php
/**
 * The values of a block that go into markup where escaping is not enough: a link or an embed address (|wash keeps
 * "javascript:" working), a colour inside a style attribute, an element name, a template name. Each method returns
 * the value when it is of an allowed form, else a safe replacement ('' or the given fallback).
 *
 * The block handlers normalise their values with it, so every design's templates get safe values; the standard
 * templates check again with the template operators of this class:
 *
 *   {$url|explayouts_safe_url}          a link: http(s), mailto, tel, a relative address, #anchor, ?query
 *   {$url|explayouts_safe_embed_url}    an iframe address: http(s), protocol relative or a path on this site
 *   {$color|explayouts_safe_color}      a CSS colour, else ''
 *   {$tag|explayouts_safe_tag( 2 )}     h1-h6, p or div, else h<level> (h2 for a level outside 1-6)
 *   {$name|explayouts_safe_template_name}  letters, digits and _, else ''
 *
 * @license GPL-2.0-or-later
 */
class expLayoutsSafeValue
{
    /** Schemes a link may use */
    const LINK_SCHEMES = array( 'http', 'https', 'mailto', 'tel' );

    /** Schemes an embedded frame may use */
    const EMBED_SCHEMES = array( 'http', 'https' );

    /** The elements a title block may be */
    const HEADING_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' );

    /**
     * A link address, or '' when it uses a scheme other than http, https, mailto and tel (javascript:, data:,
     * vbscript:, ...). Relative addresses, #anchors and ?queries are kept.
     *
     * @param mixed $value
     * @return string
     */
    public static function url( $value )
    {
        return self::checkedUrl( $value, self::LINK_SCHEMES, true );
    }

    /**
     * An address for an iframe: http(s), protocol relative (//host/...) or an absolute path on this site; '' for
     * anything else, also #anchors and relative paths.
     *
     * @param mixed $value
     * @return string
     */
    public static function embedUrl( $value )
    {
        return self::checkedUrl( $value, self::EMBED_SCHEMES, false );
    }

    protected static function checkedUrl( $value, array $schemes, $allowRelative )
    {
        if ( !is_scalar( $value ) )
            return '';
        $value = trim( (string)$value );
        if ( $value === '' )
            return '';

        // Browsers ignore control characters and white space inside a scheme ("java\tscript:"), so the scheme is
        // read from the value without them.
        $probe = preg_replace( '/[\x00-\x20\x7F]+/', '', $value );
        if ( preg_match( '#^([a-zA-Z][a-zA-Z0-9+.\-]*):#', $probe, $matches ) )
            return in_array( strtolower( $matches[1] ), $schemes, true ) ? $value : '';

        // No scheme: a backslash at the start is read as a slash by browsers ("\\host" is "//host").
        if ( $probe !== '' && $probe[0] === '\\' )
            return '';
        // A colon before the first slash, ? or # that did not make a scheme above ("1javascript:" is not one, but
        // ":x" or a scheme with characters PHP does not match) is not a relative address either.
        $head = strcspn( $probe, '/?#' );
        if ( strpos( substr( $probe, 0, $head ), ':' ) !== false )
            return '';

        if ( $allowRelative )
            return $value;
        // embeds: a path on this site or a protocol relative address
        return ( $probe !== '' && $probe[0] === '/' ) ? $value : '';
    }

    /**
     * A CSS colour: #rgb, #rgba, #rrggbb, #rrggbbaa, rgb()/rgba()/hsl()/hsla() with numbers, or a colour name;
     * $fallback for anything else (so nothing but a colour reaches a style attribute).
     *
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    public static function color( $value, $fallback = '' )
    {
        if ( !is_scalar( $value ) )
            return $fallback;
        $value = trim( (string)$value );
        if ( preg_match( '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value )
             || preg_match( '/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.%\s,\/+\-]+\)$/i', $value )
             || preg_match( '/^[a-zA-Z]{3,30}$/', $value ) )
            return $value;
        return $fallback;
    }

    /**
     * The element of a title: $tag when it is h1-h6, p or div; else h<$level>, and h2 for a level outside 1-6.
     *
     * @param mixed $tag
     * @param mixed $level
     * @return string
     */
    public static function headingTag( $tag, $level = 2 )
    {
        $tag = is_scalar( $tag ) ? strtolower( trim( (string)$tag ) ) : '';
        if ( in_array( $tag, self::HEADING_TAGS, true ) )
            return $tag;
        $level = is_numeric( $level ) ? (int)$level : 2;
        return ( $level >= 1 && $level <= 6 ) ? 'h' . $level : 'h2';
    }

    /**
     * A template name of the template block (design:explayouts/tpl_block/<name>.tpl): lower case letters, digits
     * and _ only, else ''.
     *
     * @param mixed $name
     * @return string
     */
    public static function templateName( $name )
    {
        $name = is_scalar( $name ) ? trim( (string)$name ) : '';
        return preg_match( '/^[a-z0-9_]+$/', $name ) ? $name : '';
    }

    // ------------------------------------------------------------------
    // Template operators
    // ------------------------------------------------------------------

    public function operatorList()
    {
        return array( 'explayouts_safe_url', 'explayouts_safe_embed_url', 'explayouts_safe_color', 'explayouts_safe_tag',
                      'explayouts_safe_template_name' );
    }

    public function namedParameterPerOperator()
    {
        return true;
    }

    public function namedParameterList()
    {
        return array(
            'explayouts_safe_url' => array(),
            'explayouts_safe_embed_url' => array(),
            'explayouts_safe_template_name' => array(),
            'explayouts_safe_color' => array( 'fallback' => array( 'type' => 'string', 'required' => false, 'default' => '' ) ),
            'explayouts_safe_tag' => array( 'level' => array( 'type' => 'integer', 'required' => false, 'default' => 2 ) ),
        );
    }

    public function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'explayouts_safe_url':
                $operatorValue = self::url( $operatorValue );
                break;
            case 'explayouts_safe_embed_url':
                $operatorValue = self::embedUrl( $operatorValue );
                break;
            case 'explayouts_safe_color':
                $operatorValue = self::color( $operatorValue, (string)$namedParameters['fallback'] );
                break;
            case 'explayouts_safe_template_name':
                $operatorValue = self::templateName( $operatorValue );
                break;
            case 'explayouts_safe_tag':
                $operatorValue = self::headingTag( $operatorValue, $namedParameters['level'] );
                break;
        }
    }
}
