<?php
/**
 * The code of extension/explayouts/modules/explayouts/template_editor.php, moved into a class (#207 stage 1). The file extension/explayouts/modules/explayouts/template_editor.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 *
 * The page is standalone (no admin pagelayout): design:explayouts/template_editor.tpl is
 * rendered through the template engine, and every value the script reads (file list,
 * translated messages, urls, form token) reaches it as JSON encoded here.
 */

namespace
{
if ( !function_exists( 'expTeJsonResponse' ) ) {
function expTeJsonResponse( $data )
{
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
    echo json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
    eZExecution::cleanExit();
}
}
}

namespace Exponential\View\Extension\Explayouts\Explayouts
{

class TemplateEditor extends \Exponential\Runnable\ModuleView
{
    const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $context = 'design/admin/explayouts/template_editor';

        if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' )
        {
            if ( isset( $_GET['file'] ) && is_string( $_GET['file'] ) && $_GET['file'] !== '' )
            {
                $selectedPath = $_GET['file'];
                $content = \expLayoutsTemplateEditor::read( $selectedPath );
                if ( $content === false )
                    expTeJsonResponse( array( 'success' => false, 'error' => \ezpI18n::tr( $context, 'Unable to read template: %path', null, array( '%path' => $selectedPath ) ) ) );
                expTeJsonResponse( array( 'success' => true, 'content' => $content ) );
            }

            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['SaveTemplate'] ) )
            {
                $selectedPath = isset( $_POST['SelectedPath'] ) && is_string( $_POST['SelectedPath'] ) ? $_POST['SelectedPath'] : '';
                $content = isset( $_POST['TemplateContent'] ) && is_string( $_POST['TemplateContent'] ) ? $_POST['TemplateContent'] : '';
                $content = str_replace( "\r\n", "\n", $content );
                $ok = \expLayoutsTemplateEditor::save( $selectedPath, $content );
                expTeJsonResponse( array(
                    'success' => $ok,
                    'message' => $ok ? \ezpI18n::tr( $context, 'Saved' ) : \ezpI18n::tr( $context, 'Save failed' ),
                ) );
            }

            expTeJsonResponse( array( 'success' => false, 'error' => \ezpI18n::tr( $context, 'Unknown request' ) ) );
        }

        $baseUrl = 'explayouts/template_editor';
        \eZURI::transformURI( $baseUrl, false, 'relative', false );
        $exitUrl = '/';
        \eZURI::transformURI( $exitUrl, false, 'relative', false );

        $i18n = array(
            'not_array'      => \ezpI18n::tr( $context, 'TPL_FILES is not an array. Check the server response.' ),
            'no_match'       => \ezpI18n::tr( $context, 'No templates match.' ),
            'render_error'   => \ezpI18n::tr( $context, 'Render error: %message' ),
            'load_error'     => \ezpI18n::tr( $context, 'Load error: %message' ),
            'no_file'        => \ezpI18n::tr( $context, 'No file selected' ),
            'saved'          => \ezpI18n::tr( $context, 'Saved' ),
            'save_failed'    => \ezpI18n::tr( $context, 'Save failed: %message' ),
            'save_error'     => \ezpI18n::tr( $context, 'Save error: %message' ),
            'new_path'       => \ezpI18n::tr( $context, 'New template path (relative to design/ or extension/, must end in .tpl):' ),
            'must_end_tpl'   => \ezpI18n::tr( $context, 'Path must end in .tpl' ),
            'count'          => \ezpI18n::tr( $context, '(%count templates)' ),
            'new_file_error' => \ezpI18n::tr( $context, 'New file error: %message' ),
        );

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'files_json', self::json( \expLayoutsTemplateEditor::listTemplates(), '[]' ) );
        $tpl->setVariable( 'i18n_json', self::json( $i18n, '{}' ) );
        $tpl->setVariable( 'base_url_json', self::json( $baseUrl, '""' ) );
        $tpl->setVariable( 'exit_url_json', self::json( $exitUrl, '"/"' ) );
        $tpl->setVariable( 'csrf_token_json', self::json( class_exists( 'ezxFormToken' ) ? \ezxFormToken::getToken() : '', '""' ) );
        $tpl->setVariable( 'language_code', \eZLocale::instance()->httpLocaleCode() );

        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'Expires: Thu, 1 Jan 1970 00:00:00 GMT' );

        $Result = array();
        $Result['pagelayout'] = false;
        $Result['content'] = $tpl->fetch( 'design:explayouts/template_editor.tpl' );

        return $this->viewResult( $Result, $Result );
    }

    /**
     * A value as JSON that is safe inside a <script> element (no <, >, &, ' or " survive raw).
     */
    protected static function json( $value, $fallback )
    {
        $json = json_encode( $value, self::JSON_FLAGS );
        return $json === false ? $fallback : $json;
    }
}

}
