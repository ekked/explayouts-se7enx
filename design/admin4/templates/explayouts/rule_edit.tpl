{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): Edit rule (explayouts_ui/rule_edit), the form version of a mapping. Same fields as design/admin's
   template: three empty rows for new targets and conditions. *}
{ezcss_require( 'explayouts_admin4.css' )}
<div class="context-block a4-page explayouts-a4">

<div class="box-header"><div class="box-ml">
<h1 class="context-title">{if gt( $rule.id, 0 )}{'Edit Rule'|i18n( 'design/admin/explayouts/rule_edit' )}{else}{'New Rule'|i18n( 'design/admin/explayouts/rule_edit' )}{/if}</h1>
</div></div>

<div class="box-bc"><div class="box-ml"><div class="box-content">

{if $message}<div class="a4-notice is-ok" role="status">{$message|wash}</div>{/if}
{if $error}<div class="a4-notice is-bad" role="alert">{$error|wash}</div>{/if}

<form class="a4-form is-wide" method="post" action={concat( 'explayouts/rule_edit/', $rule.id )|ezurl}>
    <div class="a4-field">
        <label class="a4-label" for="layout_id">{'Layout'|i18n( 'design/admin/explayouts/rule_edit' )}</label>
        <select class="a4-input" id="layout_id" name="LayoutID">
        {foreach $layouts as $layout}
            <option value="{$layout.id|wash}"{if eq( $rule.layout_id, $layout.id )} selected="selected"{/if}>{$layout.name|wash} ({$layout.identifier|wash})</option>
        {/foreach}
        </select>
    </div>
    <div class="a4-field">
        <label class="a4-label" for="priority">{'Priority'|i18n( 'design/admin/explayouts/rule_edit' )}</label>
        <input class="a4-input is-auto" id="priority" type="text" name="Priority" value="{$rule.priority|wash}" size="10" inputmode="numeric" />
        <label class="a4-check"><input type="checkbox" name="Enabled" value="1"{if $rule.enabled} checked="checked"{/if} /> {'Enabled'|i18n( 'design/admin/explayouts/rule_edit' )}</label>
    </div>

    <section class="a4-section a4-field-wide">
        <h2 class="a4-h3">{'Targets'|i18n( 'design/admin/explayouts/rule_edit' )}</h2>
        <p class="a4-help">{'First matching target wins. Types: <code>path_prefix</code>, <code>path</code>, <code>path_regex</code> (pattern without delimiters), <code>node</code> (node ID or URL alias).'|i18n( 'design/admin/explayouts/rule_edit' )}</p>
        <div class="a4-table-wrap"><table class="a4-table is-compact">
            <thead><tr><th scope="col">{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}</th><th scope="col">{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}</th></tr></thead>
            <tbody>
            {foreach $targets as $t}
            <tr><td><input class="a4-input" type="text" name="TargetType[]" value="{$t.target_type|wash}" aria-label="{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td><td><input class="a4-input" type="text" name="TargetValue[]" value="{$t.target_value|wash}" aria-label="{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td></tr>
            {/foreach}
            {for 0 to 2 as $i}
            <tr><td><input class="a4-input" type="text" name="TargetType[]" value="" aria-label="{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td><td><input class="a4-input" type="text" name="TargetValue[]" value="" aria-label="{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td></tr>
            {/for}
            </tbody>
        </table></div>
    </section>

    <section class="a4-section a4-field-wide">
        <h2 class="a4-h3">{'Conditions'|i18n( 'design/admin/explayouts/rule_edit' )}</h2>
        <p class="a4-help">{'All conditions must match. Types: <code>siteaccess</code>.'|i18n( 'design/admin/explayouts/rule_edit' )}</p>
        <div class="a4-table-wrap"><table class="a4-table is-compact">
            <thead><tr><th scope="col">{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}</th><th scope="col">{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}</th></tr></thead>
            <tbody>
            {foreach $conditions as $c}
            <tr><td><input class="a4-input" type="text" name="ConditionType[]" value="{$c.condition_type|wash}" aria-label="{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td><td><input class="a4-input" type="text" name="ConditionValue[]" value="{$c.condition_value|wash}" aria-label="{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td></tr>
            {/foreach}
            {for 0 to 2 as $i}
            <tr><td><input class="a4-input" type="text" name="ConditionType[]" value="" aria-label="{'Type'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td><td><input class="a4-input" type="text" name="ConditionValue[]" value="" aria-label="{'Value'|i18n( 'design/admin/explayouts/rule_edit' )}" /></td></tr>
            {/for}
            </tbody>
        </table></div>
    </section>

    <div class="a4-actions">
        <button class="a4-btn a4-btn-primary" type="submit" name="SaveRule">{'Save rule'|i18n( 'design/admin/explayouts/rule_edit' )}</button>
        <a class="a4-btn" href={'explayouts/rule_list'|ezurl}>{'Back'|i18n( 'design/admin/explayouts/rule_edit' )}</a>
    </div>
</form>

</div></div></div>
</div>
