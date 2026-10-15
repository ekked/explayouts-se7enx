{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): rules. A disabled rule
   is muted and says so. Same forms and button names as design/admin's template. *}
{ezcss_require( 'explayouts_admin4.css' )}
{ezscript_require( 'explayouts_admin4.js' )}
<div class="context-block a4-page explayouts-a4">
<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Layout Rules'|i18n( 'design/admin/explayouts/rule_list' )}</h1>
</div></div>
<div class="box-bc"><div class="box-ml"><div class="box-content">
{if $message}<div class="a4-notice is-ok" role="status">{$message|wash}</div>{/if}
{if $error}<div class="a4-notice is-bad" role="alert">{$error|wash}</div>{/if}
<div class="a4-toolbar"><div class="a4-actions"><a class="a4-btn a4-btn-primary" href={'explayouts/rule_edit/'|ezurl}>{'New rule'|i18n( 'design/admin/explayouts/rule_list' )}</a></div></div>
{if count( $rules )}
<div class="a4-table-wrap"><table class="a4-table">
    <thead><tr><th scope="col" class="a4-num">ID</th><th scope="col" class="a4-num">{'Priority'|i18n( 'design/admin/explayouts/rule_list' )}</th><th scope="col">{'Enabled'|i18n( 'design/admin/explayouts/rule_list' )}</th><th scope="col" class="a4-num">{'Layout ID'|i18n( 'design/admin/explayouts/rule_list' )}</th><th scope="col"><span class="a4-sr-only">{'Edit'|i18n( 'design/admin/explayouts/rule_list' )}</span></th></tr></thead>
    <tbody>
    {foreach $rules as $rule}
    <tr{if not( $rule.enabled )} class="explayouts-a4-muted-row"{/if}>
        <td class="a4-num">{$rule.id|wash}</td>
        <td class="a4-num">{$rule.priority|wash}</td>
        <td>{if $rule.enabled}<span class="a4-badge is-ok">{'Yes'|i18n( 'design/admin/explayouts/rule_list' )}</span>{else}<span class="a4-badge is-warn">{'No'|i18n( 'design/admin/explayouts/rule_list' )}</span>{/if}</td>
        <td class="a4-num">{$rule.layout_id|wash}</td>
        <td class="a4-end"><div class="a4-actions">
            <a class="a4-btn a4-btn-small" href={concat( 'explayouts/rule_edit/', $rule.id )|ezurl}>{'Edit'|i18n( 'design/admin/explayouts/rule_list' )}</a>
            <form method="post" action={'explayouts/rule_list'|ezurl} class="explayouts-a4-inline-form"><input type="hidden" name="CopyRuleID" value="{$rule.id|wash}" /><button type="submit" name="CopyRule" class="a4-btn a4-btn-small">{'Copy'|i18n( 'design/admin/explayouts/rule_list' )}</button></form>
            <form method="post" action={'explayouts/rule_list'|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Delete this rule?'|i18n( 'design/admin/explayouts/rule_list' )|wash}"><input type="hidden" name="DeleteRuleID" value="{$rule.id|wash}" /><button type="submit" name="DeleteRule" class="a4-btn a4-btn-small a4-btn-outline-danger">{'Delete'|i18n( 'design/admin/explayouts/rule_list' )}</button></form>
        </div></td>
    </tr>
    {/foreach}
    </tbody>
</table></div>
{/if}
</div></div></div>
</div>
