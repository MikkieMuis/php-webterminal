// joe editor overlay
// Real joe uses ^K-prefixed commands.  Key bindings match JOE 4.x defaults.
// Ctrl+H (or F1) opens the in-editor help screen — the centrepiece of this impl.

var joeActive = false;
var joeData   = {
    path: '', filename: '', lines: [], curRow: 0, curCol: 0,
    modified: false, cutBuffer: [], markStart: null,
    mode: 'edit',           // edit | help | saveas | confirm_exit | search
    searchTyped: '',
    saveNameTyped: '',
    _exitAfterSave: false,
    kPrefix: false,         // true while waiting for second key of ^K combo
};

// ── Help screen content ────────────────────────────────────────────────────
var JOE_HELP = [
    ' JOE\'S OWN EDITOR  Help  (^KH or F1 to close)',
    '',
    ' CURSOR MOVEMENT',
    '  ^F / ^B         Forward / Backward one character',
    '  ^N / ^P         Next / Previous line',
    '  ^A              Go to beginning of line',
    '  ^E              Go to end of line',
    '  ^[ ^F / ^[ ^B   Word right / left  (Alt+F / Alt+B)',
    '  ^KU             Go to top of file',
    '  ^KV             Go to bottom of file',
    '  ^KL             Go to line number',
    '',
    ' DELETION',
    '  ^D              Delete character under cursor',
    '  ^H / Backspace  Delete character to left',
    '  ^Y              Delete entire line',
    '  ^W              Delete word to the right',
    '',
    ' BLOCK COMMANDS  (^K prefix)',
    '  ^KB             Mark beginning of block',
    '  ^KK             Mark end of block',
    '  ^KC             Copy marked block',
    '  ^KM             Move marked block',
    '  ^KY             Delete marked block',
    '',
    ' FILE COMMANDS',
    '  ^KS / ^KX       Save file (stay / save & exit)',
    '  ^KD             Save file with new name',
    '  ^KR             Read (insert) another file',
    '  ^KQ             Quit without saving',
    '',
    ' SEARCH & REPLACE',
    '  ^KF             Find string',
    '  ^L              Repeat last search',
    '',
    ' MISCELLANEOUS',
    '  ^KH / F1        Toggle this help screen',
    '  ^T              Options menu (stubbed)',
    '  ^_              Undo last change',
    '  ^@              Set mark',
    '',
    ' Press ^KH or F1 to close help.',
];

// ── Helpers ────────────────────────────────────────────────────────────────
function joeEscHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function joeStatusText() {
    var d = joeData;
    switch (d.mode) {
        case 'help':         return '(^KH or F1 to close help)';
        case 'saveas':       return 'File name to save: ' + d.saveNameTyped + '_';
        case 'confirm_exit': return 'File has been modified.  Save (y/n/^C)?';
        case 'search':       return 'Find: ' + d.searchTyped + '_';
        default:
            return 'Line: ' + (d.curRow + 1) + '  Col: ' + (d.curCol + 1) +
                   (d.modified ? '  *Modified*' : '');
    }
}

// ── Render ─────────────────────────────────────────────────────────────────
function joeRender() {
    var overlay = document.getElementById('joe-overlay');
    if (!overlay) return;

    var d = joeData;

    // title bar
    document.getElementById('joe-titlebar').textContent =
        ' ** JOE ** joe ' + d.filename + (d.modified ? ' *' : '  ');

    // status bar
    document.getElementById('joe-status').textContent = joeStatusText();

    // content area
    var contentEl = document.getElementById('joe-content');

    if (d.mode === 'help') {
        // render help text (no cursor)
        var html = '';
        for (var i = 0; i < JOE_HELP.length; i++) {
            html += joeEscHtml(JOE_HELP[i]) + '\n';
        }
        contentEl.innerHTML = html;
        return;
    }

    // normal edit render — show file content with block cursor on current char
    var html = '';
    for (var r = 0; r < d.lines.length; r++) {
        var line = d.lines[r];
        if (r === d.curRow) {
            var col    = Math.min(d.curCol, line.length);
            var before = joeEscHtml(line.slice(0, col));
            var atCur  = joeEscHtml(col < line.length ? line[col] : ' ');
            var after  = joeEscHtml(line.slice(col + 1));
            html += before +
                    '<span style="background:#39ff14;color:#0a0a0a;">' + atCur + '</span>' +
                    after + '\n';
        } else {
            html += joeEscHtml(line) + '\n';
        }
    }
    contentEl.innerHTML = html;

    // scroll so cursor row is visible
    var lineH = contentEl.scrollHeight / Math.max(1, d.lines.length);
    contentEl.scrollTop = Math.max(0, (d.curRow - 10) * lineH);
}

// ── Entry point ────────────────────────────────────────────────────────────
function doJoe(data) {
    var d = joeData;
    d.path          = data.path;
    d.filename      = data.filename;
    d.lines         = data.content.split('\n');
    d.curRow        = 0;
    d.curCol        = 0;
    d.modified      = false;
    d.cutBuffer     = [];
    d.markStart     = null;
    d.mode          = 'edit';
    d.searchTyped   = '';
    d.saveNameTyped = '';
    d._exitAfterSave = false;
    d.kPrefix       = false;
    d.isnew         = data.isnew;

    joeActive = true;
    document.getElementById('joe-overlay').style.display = 'flex';
    hidePrompt();
    joeRender();
}

// ── Key handler ────────────────────────────────────────────────────────────
function joeKey(key, ctrlKey, altKey) {
    if (!joeActive) return;
    var d = joeData;

    // ── confirm exit mode ──────────────────────────────────────────────────
    if (d.mode === 'confirm_exit') {
        if (key === 'y' || key === 'Y') {
            d.mode = 'saveas';
            d.saveNameTyped = d.filename;
            d._exitAfterSave = true;
            joeRender();
        } else if (key === 'n' || key === 'N') {
            joeClose(false);
        } else if (key === 'Escape' || (ctrlKey && (key === 'c' || key === 'C'))) {
            d.mode = 'edit';
            joeRender();
        }
        return;
    }

    // ── save-as mode ───────────────────────────────────────────────────────
    if (d.mode === 'saveas') {
        if (key === 'Enter') {
            joeSave(d.saveNameTyped);
        } else if (key === 'Escape') {
            d.mode = 'edit';
            joeRender();
        } else if (key === 'Backspace') {
            d.saveNameTyped = d.saveNameTyped.slice(0, -1);
            joeRender();
        } else if (key.length === 1 && !ctrlKey) {
            d.saveNameTyped += key;
            joeRender();
        }
        return;
    }

    // ── search mode ────────────────────────────────────────────────────────
    if (d.mode === 'search') {
        if (key === 'Enter') {
            joeDoSearch(d.searchTyped);
            d.mode = 'edit';
            joeRender();
        } else if (key === 'Escape') {
            d.mode = 'edit';
            joeRender();
        } else if (key === 'Backspace') {
            d.searchTyped = d.searchTyped.slice(0, -1);
            joeRender();
        } else if (key.length === 1 && !ctrlKey) {
            d.searchTyped += key;
            joeRender();
        }
        return;
    }

    // ── help mode — any key except ^KH / F1 closes it ─────────────────────
    if (d.mode === 'help') {
        if (key === 'F1') { d.mode = 'edit'; joeRender(); return; }
        if (ctrlKey && (key === 'h' || key === 'H') && d.kPrefix) {
            d.kPrefix = false; d.mode = 'edit'; joeRender(); return;
        }
        if (ctrlKey && (key === 'k' || key === 'K')) { d.kPrefix = true; return; }
        if (d.kPrefix) { d.kPrefix = false; d.mode = 'edit'; joeRender(); return; }
        // arrow keys scroll help
        if (key === 'ArrowUp' || key === 'ArrowDown') {
            var contentEl = document.getElementById('joe-content');
            contentEl.scrollTop += (key === 'ArrowDown' ? 20 : -20);
        }
        return;
    }

    // ── F1 — toggle help ───────────────────────────────────────────────────
    if (key === 'F1') {
        d.mode = 'help';
        d.kPrefix = false;
        joeRender();
        return;
    }

    // ── ^K prefix handling ─────────────────────────────────────────────────
    if (ctrlKey && (key === 'k' || key === 'K') && !d.kPrefix) {
        d.kPrefix = true;
        document.getElementById('joe-status').textContent = '^K- (waiting for command...)';
        return;
    }

    if (d.kPrefix) {
        d.kPrefix = false;
        var k = key.toLowerCase();

        switch (k) {
            case 'h':  // ^KH — toggle help
                d.mode = 'help';
                joeRender();
                return;

            case 's':  // ^KS — save (stay in editor)
                d.mode = 'saveas';
                d.saveNameTyped = d.filename;
                d._exitAfterSave = false;
                joeRender();
                return;

            case 'x':  // ^KX — save & exit
                d.mode = 'saveas';
                d.saveNameTyped = d.filename;
                d._exitAfterSave = true;
                joeRender();
                return;

            case 'd':  // ^KD — save as (new name)
                d.mode = 'saveas';
                d.saveNameTyped = d.filename;
                d._exitAfterSave = false;
                joeRender();
                return;

            case 'q':  // ^KQ — quit without saving
                if (d.modified) {
                    d.mode = 'confirm_exit';
                    joeRender();
                } else {
                    joeClose(false);
                }
                return;

            case 'u':  // ^KU — go to top of file
                d.curRow = 0; d.curCol = 0;
                joeRender();
                return;

            case 'v':  // ^KV — go to bottom of file
                d.curRow = d.lines.length - 1;
                d.curCol = d.lines[d.curRow].length;
                joeRender();
                return;

            case 'f':  // ^KF — find/search
                d.mode = 'search';
                d.searchTyped = '';
                joeRender();
                return;

            case 'l':  // ^KL — go to line number (stub: prompt not implemented, go to line 1)
                d.curRow = 0; d.curCol = 0;
                document.getElementById('joe-status').textContent =
                    '(^KL: go to line — enter line number not yet supported; moved to top)';
                return;

            case 'y':  // ^KY — delete marked block (or current line as fallback)
                joeCutLine();
                return;

            case 'c':  // ^KC — copy (paste cut buffer at cursor)
                joePaste();
                return;

            case 'm':  // ^KM — move (paste cut buffer at cursor)
                joePaste();
                return;

            default:
                document.getElementById('joe-status').textContent =
                    '^K' + key.toUpperCase() + ' — unknown command';
                return;
        }
    }

    // ── Ctrl shortcuts (non-^K) ────────────────────────────────────────────
    if (ctrlKey) {
        var k = key.toLowerCase();
        switch (k) {
            case 'h':  // ^H — backspace (real joe maps ^H to backspace)
                joeBackspace();
                break;
            case 'd':  // ^D — delete char under cursor
                joeDelete();
                break;
            case 'f':  // ^F — forward char
                joeMoveRight();
                break;
            case 'b':  // ^B — backward char
                joeMoveLeft();
                break;
            case 'n':  // ^N — next line
                joeMoveDown();
                break;
            case 'p':  // ^P — previous line
                joeMoveUp();
                break;
            case 'a':  // ^A — beginning of line
                d.curCol = 0;
                break;
            case 'e':  // ^E — end of line
                d.curCol = d.lines[d.curRow].length;
                break;
            case 'y':  // ^Y — delete entire line
                joeCutLine();
                break;
            case 'w':  // ^W — delete word right
                joeDeleteWordRight();
                break;
            case 'l':  // ^L — repeat last search
                if (d.searchTyped) joeDoSearch(d.searchTyped);
                break;
            case 'z':  // ^Z — scroll down half page (joe default)
            case '_':  // ^_ — undo (stub)
                document.getElementById('joe-status').textContent = '(undo not yet supported)';
                return;
            default:
                return;
        }
        joeRender();
        return;
    }

    // ── Alt shortcuts ──────────────────────────────────────────────────────
    if (altKey) {
        if (key === 'f' || key === 'F') { joeWordRight(); joeRender(); return; }
        if (key === 'b' || key === 'B') { joeWordLeft();  joeRender(); return; }
        return;
    }

    // ── Non-ctrl printable & navigation ───────────────────────────────────
    switch (key) {
        case 'ArrowUp':    joeMoveUp();    break;
        case 'ArrowDown':  joeMoveDown();  break;
        case 'ArrowLeft':  joeMoveLeft();  break;
        case 'ArrowRight': joeMoveRight(); break;
        case 'Home':       d.curCol = 0;  break;
        case 'End':        d.curCol = d.lines[d.curRow].length; break;
        case 'PageUp':
            d.curRow = Math.max(0, d.curRow - 20);
            d.curCol = Math.min(d.curCol, d.lines[d.curRow].length);
            break;
        case 'PageDown':
            d.curRow = Math.min(d.lines.length - 1, d.curRow + 20);
            d.curCol = Math.min(d.curCol, d.lines[d.curRow].length);
            break;
        case 'Enter':
            var line = d.lines[d.curRow];
            var before = line.slice(0, d.curCol);
            var after  = line.slice(d.curCol);
            d.lines.splice(d.curRow, 1, before, after);
            d.curRow++;
            d.curCol   = 0;
            d.modified = true;
            break;
        case 'Backspace':
            joeBackspace();
            break;
        case 'Delete':
            joeDelete();
            break;
        case 'Tab':
            joeInsert('\t');
            break;
        default:
            if (key.length === 1) {
                joeInsert(key);
            }
            return;  // unknown key — no render needed
    }
    joeRender();
}

// ── Cursor movement helpers ────────────────────────────────────────────────
function joeMoveUp() {
    var d = joeData;
    if (d.curRow > 0) {
        d.curRow--;
        d.curCol = Math.min(d.curCol, d.lines[d.curRow].length);
    }
}
function joeMoveDown() {
    var d = joeData;
    if (d.curRow < d.lines.length - 1) {
        d.curRow++;
        d.curCol = Math.min(d.curCol, d.lines[d.curRow].length);
    }
}
function joeMoveLeft() {
    var d = joeData;
    if (d.curCol > 0) {
        d.curCol--;
    } else if (d.curRow > 0) {
        d.curRow--;
        d.curCol = d.lines[d.curRow].length;
    }
}
function joeMoveRight() {
    var d = joeData;
    if (d.curCol < d.lines[d.curRow].length) {
        d.curCol++;
    } else if (d.curRow < d.lines.length - 1) {
        d.curRow++;
        d.curCol = 0;
    }
}
function joeWordRight() {
    var d = joeData;
    var line = d.lines[d.curRow];
    var col  = d.curCol;
    // skip current word chars
    while (col < line.length && line[col] !== ' ') col++;
    // skip spaces
    while (col < line.length && line[col] === ' ') col++;
    d.curCol = col;
}
function joeWordLeft() {
    var d = joeData;
    var line = d.lines[d.curRow];
    var col  = d.curCol;
    if (col === 0) return;
    col--;
    while (col > 0 && line[col - 1] === ' ') col--;
    while (col > 0 && line[col - 1] !== ' ') col--;
    d.curCol = col;
}

// ── Edit helpers ──────────────────────────────────────────────────────────
function joeInsert(ch) {
    var d = joeData;
    var l = d.lines[d.curRow];
    d.lines[d.curRow] = l.slice(0, d.curCol) + ch + l.slice(d.curCol);
    d.curCol++;
    d.modified = true;
}
function joeBackspace() {
    var d = joeData;
    if (d.curCol > 0) {
        var l = d.lines[d.curRow];
        d.lines[d.curRow] = l.slice(0, d.curCol - 1) + l.slice(d.curCol);
        d.curCol--;
        d.modified = true;
    } else if (d.curRow > 0) {
        var prevLen = d.lines[d.curRow - 1].length;
        d.lines[d.curRow - 1] += d.lines[d.curRow];
        d.lines.splice(d.curRow, 1);
        d.curRow--;
        d.curCol   = prevLen;
        d.modified = true;
    }
}
function joeDelete() {
    var d = joeData;
    var l = d.lines[d.curRow];
    if (d.curCol < l.length) {
        d.lines[d.curRow] = l.slice(0, d.curCol) + l.slice(d.curCol + 1);
        d.modified = true;
    } else if (d.curRow < d.lines.length - 1) {
        d.lines[d.curRow] += d.lines[d.curRow + 1];
        d.lines.splice(d.curRow + 1, 1);
        d.modified = true;
    }
}
function joeCutLine() {
    var d = joeData;
    d.cutBuffer = [d.lines[d.curRow]];
    d.lines.splice(d.curRow, 1);
    if (d.lines.length === 0) d.lines = [''];
    if (d.curRow >= d.lines.length) d.curRow = d.lines.length - 1;
    d.curCol   = 0;
    d.modified = true;
}
function joePaste() {
    var d = joeData;
    if (!d.cutBuffer.length) return;
    for (var i = 0; i < d.cutBuffer.length; i++) {
        d.lines.splice(d.curRow + i, 0, d.cutBuffer[i]);
    }
    d.modified = true;
}
function joeDeleteWordRight() {
    var d = joeData;
    var line = d.lines[d.curRow];
    var col  = d.curCol;
    // delete to end of next word
    while (col < line.length && line[col] === ' ') col++;
    while (col < line.length && line[col] !== ' ') col++;
    d.lines[d.curRow] = line.slice(0, d.curCol) + line.slice(col);
    d.modified = true;
}

// ── Search ────────────────────────────────────────────────────────────────
function joeDoSearch(term) {
    var d = joeData;
    if (!term) return;
    var total = d.lines.length;
    for (var i = 0; i < total; i++) {
        var r   = (d.curRow + i + 1) % total;
        var idx = d.lines[r].indexOf(term);
        if (idx !== -1) {
            d.curRow = r;
            d.curCol = idx;
            d.searchTyped = term;
            return;
        }
    }
    document.getElementById('joe-status').textContent = '"' + term + '": Not found';
}

// ── Save ──────────────────────────────────────────────────────────────────
function joeSave(filename) {
    var d        = joeData;
    var savePath = d.path.replace(/[^/]+$/, '') + filename;
    var content  = d.lines.join('\n');

    fetch('terminal.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cmd: '__nano_save', path: savePath, content: content, user: loginUser })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        d.modified  = false;
        d.filename  = filename;
        d.path      = savePath;
        if (d.mode === 'saveas' && d._exitAfterSave) {
            joeClose(true, resp.lines);
        } else {
            d.mode = 'edit';
            document.getElementById('joe-status').textContent =
                'Wrote ' + resp.lines + ' line' + (resp.lines === 1 ? '' : 's');
            joeRender();
            setTimeout(function() {
                if (joeActive) {
                    document.getElementById('joe-status').textContent = joeStatusText();
                }
            }, 2000);
        }
    });
}

// ── Close ─────────────────────────────────────────────────────────────────
function joeClose(saved) {
    joeActive = false;
    document.getElementById('joe-overlay').style.display = 'none';
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
}
