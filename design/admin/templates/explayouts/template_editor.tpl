<!DOCTYPE html>
{* Standalone page (the view sets no pagelayout). Every value the script reads is JSON encoded by the view; the CSS and the script are literal. *}
<html lang="{$language_code|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash}</title>
{literal}
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --panel-2: #334155;
            --fg: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --accent-2: #818cf8;
            --ok: #4ade80;
            --warn: #fbbf24;
            --danger: #f87171;
            --font: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; background: var(--bg); color: var(--fg); font-family: var(--font); font-size: 14px; }
        .app { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 420px; min-width: 260px; max-width: 80vw; background: var(--panel); border-right: 1px solid var(--panel-2); display: flex; flex-direction: column; position: relative; }
        .resizer { width: 6px; cursor: col-resize; background: var(--panel-2); position: absolute; right: 0; top: 0; bottom: 0; z-index: 10; }
        .sidebar-header { padding: 1rem; border-bottom: 1px solid var(--panel-2); }
        .sidebar-header h1 { margin: 0 0 .5rem; font-size: 1.1rem; color: var(--accent); }
        .sidebar-header h1 small { color: var(--muted); font-weight: 400; }
        .sidebar input[type="text"] { width: 100%; padding: .5rem; background: var(--bg); color: var(--fg); border: 1px solid var(--panel-2); border-radius: .25rem; }
        .flat-list { flex: 1; overflow: auto; padding: .5rem; }
        .flat-list .empty { color: var(--muted); padding: 1rem .5rem; text-align: center; }
        .flat-list button { display: block; width: 100%; text-align: left; background: transparent; color: var(--fg); border: none; border-radius: .25rem; padding: .3rem .5rem; cursor: pointer; font-family: inherit; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .flat-list button:hover { background: var(--panel-2); }
        .flat-list button.active { background: var(--accent-2); color: #fff; }
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .toolbar { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem; background: var(--panel); border-bottom: 1px solid var(--panel-2); }
        .exit-btn { margin-left: auto; }
        .toolbar .path { flex: 1; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .toolbar .status { padding: .25rem .75rem; border-radius: .25rem; font-size: .85rem; opacity: 0; transition: opacity .2s; }
        .toolbar .status.show { opacity: 1; }
        .toolbar .status.ok { background: rgba(74,222,128,.2); color: var(--ok); }
        .toolbar .status.err { background: rgba(248,113,113,.2); color: var(--danger); }
        .btn { padding: .45rem .9rem; border: none; border-radius: .25rem; background: var(--accent); color: #0f172a; cursor: pointer; font-weight: 600; font-family: inherit; }
        .btn:hover { filter: brightness(1.1); }
        .btn.secondary { background: var(--panel-2); color: var(--fg); }
        .editor-wrap { flex: 1; display: flex; overflow: hidden; position: relative; }
        .line-numbers { width: 3.5rem; padding: 1rem .5rem; background: var(--panel); color: var(--muted); text-align: right; overflow: hidden; white-space: pre; line-height: 1.5; }
        textarea#tpl-editor { flex: 1; padding: 1rem; background: var(--bg); color: var(--fg); border: none; outline: none; resize: none; line-height: 1.5; font-family: var(--font); font-size: 14px; tab-size: 2; white-space: pre; overflow: auto; }
        .empty { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 1.2rem; }
        .error-box { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--danger); padding: 1rem; text-align: center; }
    </style>
{/literal}
</head>
<body>
{include uri='design:explayouts/parts/template_editor_body.tpl' i18n_json=$i18n_json files_json=$files_json base_url_json=$base_url_json exit_url_json=$exit_url_json csrf_token_json=$csrf_token_json}
</body>
</html>
