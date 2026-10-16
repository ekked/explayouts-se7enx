{* The template editor itself (module explayouts, view template_editor): file list, toolbar, editing area and
   script. One file for every admin design; design/admin's and design/admin4's template_editor.tpl draw the page
   around it. *}
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash} <small id="tpl-count"></small></h1>
            <input type="text" id="filter" placeholder="{'Filter templates...'|i18n( 'design/admin/explayouts/template_editor' )|wash}" autocomplete="off">
        </div>
        <nav class="flat-list" id="tree"></nav>
        <div class="resizer" id="resizer"></div>
    </aside>
    <main class="main">
        <div class="toolbar">
            {* New only with [TemplateEditorSettings] AllowNewTemplates=enabled; the server refuses to create a file otherwise. *}
            {if $allow_new_templates}<button class="btn secondary" id="new-file">{'New'|i18n( 'design/admin/explayouts/template_editor' )|wash}</button>{/if}
            <button class="btn" id="save-file">{'Save'|i18n( 'design/admin/explayouts/template_editor' )|wash}</button>
            <button class="btn secondary exit-btn" id="exit-editor" title="{'Return to admin'|i18n( 'design/admin/explayouts/template_editor' )|wash}">×</button>
            <span class="path" id="path">{'No file selected'|i18n( 'design/admin/explayouts/template_editor' )|wash}</span>
            <span class="status" id="status"></span>
        </div>
        <div class="editor-wrap" id="editor-wrap">
            <div class="empty">{'Select a template from the list to start editing.'|i18n( 'design/admin/explayouts/template_editor' )|wash}</div>
        </div>
    </main>
</div>

<script>
window.TPL_I18N = {$i18n_json};
window.TPL_FILES = {$files_json};
window.BASE_URL = {$base_url_json};
window.EXIT_URL = {$exit_url_json};
window.CSRF_TOKEN = {$csrf_token_json};
</script>
{literal}
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!Array.isArray(window.TPL_FILES)) {
        window.TPL_FILES = [];
    }

    var tree = document.getElementById('tree');
    var filter = document.getElementById('filter');
    var saveBtn = document.getElementById('save-file');
    var newBtn = document.getElementById('new-file');
    var exitBtn = document.getElementById('exit-editor');
    var pathEl = document.getElementById('path');
    var statusEl = document.getElementById('status');
    var editorWrap = document.getElementById('editor-wrap');
    var countEl = document.getElementById('tpl-count');
    var sidebar = document.getElementById('sidebar');
    var resizer = document.getElementById('resizer');

    var currentPath = '';
    var editor = null;
    var lineNumbers = null;
    var startX = 0;
    var startWidth = 0;

    function doDrag(e) {
        var newWidth = startWidth + e.clientX - startX;
        if ( newWidth < 260 ) newWidth = 260;
        if ( newWidth > window.innerWidth * 0.8 ) newWidth = window.innerWidth * 0.8;
        sidebar.style.width = newWidth + 'px';
    }

    function stopDrag() {
        document.documentElement.removeEventListener('mousemove', doDrag, false);
        document.documentElement.removeEventListener('mouseup', stopDrag, false);
    }

    resizer.addEventListener('mousedown', function(e) {
        startX = e.clientX;
        startWidth = parseInt( document.defaultView.getComputedStyle( sidebar ).width, 10 );
        document.documentElement.addEventListener('mousemove', doDrag, false);
        document.documentElement.addEventListener('mouseup', stopDrag, false);
        e.preventDefault();
    });

    function showError(where, msg) {
        var box = document.createElement('div');
        box.className = 'error-box';
        box.textContent = msg;
        where.innerHTML = '';
        where.appendChild(box);
        console.error(msg);
    }

    function countText(n) {
        return TPL_I18N.count.replace('%count', n);
    }

    function renderFlat(term) {
        try {
            tree.innerHTML = '';
            if (!Array.isArray(window.TPL_FILES)) {
                showError(tree, TPL_I18N.not_array);
                return;
            }
            var lower = (term || '').toLowerCase();
            var files = lower
                ? window.TPL_FILES.filter(function(p) { return p.toLowerCase().indexOf(lower) !== -1; })
                : window.TPL_FILES;
            if (!files.length) {
                var empty = document.createElement('p');
                empty.className = 'empty';
                empty.textContent = TPL_I18N.no_match;
                tree.appendChild(empty);
                return;
            }
            files.forEach(function(file) {
                var btn = document.createElement('button');
                btn.textContent = file;
                btn.title = file;
                btn.className = 'flat-item';
                if (file === currentPath) btn.classList.add('active');
                btn.addEventListener('click', function() { loadFile(file); });
                tree.appendChild(btn);
            });
        } catch (e) {
            showError(tree, TPL_I18N.render_error.replace('%message', e.message));
        }
    }

    function createEditor() {
        editorWrap.innerHTML = '';
        lineNumbers = document.createElement('div');
        lineNumbers.className = 'line-numbers';
        editor = document.createElement('textarea');
        editor.id = 'tpl-editor';
        editor.spellcheck = false;
        editorWrap.appendChild(lineNumbers);
        editorWrap.appendChild(editor);

        editor.addEventListener('input', updateLineNumbers);
        editor.addEventListener('scroll', function() { lineNumbers.scrollTop = editor.scrollTop; });
        editor.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                var start = editor.selectionStart;
                var end = editor.selectionEnd;
                editor.value = editor.value.substring(0, start) + '  ' + editor.value.substring(end);
                editor.selectionStart = editor.selectionEnd = start + 2;
                updateLineNumbers();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveFile();
            }
        });
    }

    function updateLineNumbers() {
        var lines = editor.value.split('\n').length;
        var lineTexts = [];
        for (var i = 1; i <= lines; i++) {
            lineTexts.push(i);
        }
        lineNumbers.textContent = lineTexts.join('\n');
    }

    function showStatus(msg, ok) {
        statusEl.textContent = msg;
        statusEl.className = 'status show ' + (ok ? 'ok' : 'err');
        setTimeout(function() { statusEl.classList.remove('show'); }, 2500);
    }

    function loadFile(path) {
        try {
            currentPath = path;
            createEditor();
            pathEl.textContent = path;
            renderFlat(filter.value);
            fetch(BASE_URL + '?file=' + encodeURIComponent(path), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) throw new Error(data.error);
                editor.value = data.content || '';
                updateLineNumbers();
                editor.focus();
            })
            .catch(function(err) { showStatus(err.message, false); });
        } catch (e) {
            showStatus(TPL_I18N.load_error.replace('%message', e.message), false);
        }
    }

    function saveFile() {
        try {
            if (!currentPath) { showStatus(TPL_I18N.no_file, false); return; }
            var body = 'SaveTemplate=1&ezxform_token=' + encodeURIComponent(CSRF_TOKEN)
                       + '&SelectedPath=' + encodeURIComponent(currentPath)
                       + '&TemplateContent=' + encodeURIComponent(editor.value);
            fetch(BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showStatus(data.message || TPL_I18N.saved, data.success);
            })
            .catch(function(err) { showStatus(TPL_I18N.save_failed.replace('%message', err.message), false); });
        } catch (e) {
            showStatus(TPL_I18N.save_error.replace('%message', e.message), false);
        }
    }

    saveBtn.addEventListener('click', saveFile);

    exitBtn.addEventListener('click', function() {
        var target = window.EXIT_URL || '/';
        try {
            if (document.referrer && new URL(document.referrer).origin === window.location.origin) {
                target = document.referrer;
            }
        } catch (e) {}
        window.location.href = target;
    });

    if (newBtn) newBtn.addEventListener('click', function() {
        try {
            var p = prompt(TPL_I18N.new_path);
            if (!p) return;
            var endsWithTpl = p.length >= 4 && p.lastIndexOf('.tpl') === p.length - 4;
            if (!endsWithTpl) { showStatus(TPL_I18N.must_end_tpl, false); return; }
            currentPath = p;
            createEditor();
            pathEl.textContent = p;
            editor.value = '';
            updateLineNumbers();
            if (!Array.isArray(window.TPL_FILES)) window.TPL_FILES = [];
            window.TPL_FILES.push(p);
            window.TPL_FILES.sort();
            countEl.textContent = countText(window.TPL_FILES.length);
            renderFlat(filter.value);
        } catch (e) {
            showStatus(TPL_I18N.new_file_error.replace('%message', e.message), false);
        }
    });

    filter.addEventListener('input', function() { renderFlat(filter.value); });

    countEl.textContent = countText(window.TPL_FILES.length);

    renderFlat('');
});
</script>
{/literal}
