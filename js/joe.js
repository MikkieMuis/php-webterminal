// joe editor overlay — JOE 4.6 look-alike
//
// Real joe layout (top to bottom):
//   [help rows]          ← only when ^KH / F1 toggled on  (bold text, no bg color)
//   [status bar]         ← reverse video: "    IW   filename     Row N    Col N   "
//   [file content area]  ← plain text, fills the middle
//   [bottom notice bar]  ← reverse video: startup notice, then empty / prompts
//
// Key bindings: ^K prefix for most commands (^KS save, ^KX exit, ^KQ quit, ^KH help, ^KF find)

var joeActive = false;
var joeData = {
    path: '', filename: '', lines: [], curRow: 0, curCol: 0,
    modified: false, cutBuffer: [],
    mode: 'edit',           // edit | saveas | confirm_exit | search
    searchTyped: '',
    saveNameTyped: '',
    _exitAfterSave: false,
    kPrefix: false,
    helpOpen: false,
    startupNotice: true,    // show xmsg on first open until first keypress
    isnew: false,
};

// ── Help text (matches real joerc {Basic} section, bold key names) ─────────
// Rendered as plain text with bold spans for the key names
var JOE_HELP_ROWS = [
' \x02REGION\x02        \x02GO TO\x02             \x02GO TO\x02             \x02DELETE\x02    \x02EXIT\x02       \x02SEARCH\x02    ',
' \x01^Arrow\x01 Select \x01^Z\x01 Prev. word   \x01^U/^V\x01 PgUp/PgDn \x01^D\x01 Char.  \x01^KX\x01 Save   \x01^KF\x01 Find  ',
' \x01^KB\x01 Begin     \x01^X\x01 Next word    \x02MISC\x02            \x01^Y\x01 Line   \x01^C\x01  Abort  \x01^L\x01  Next  ',
' \x01^KK\x01 End       \x01^KU\x01 Top of file \x01^KJ\x01 Paragraph   \x01^W\x01 >Word  \x01^KQ\x01 All    \x02HELP\x02      ',
' \x01^KC\x01 Copy      \x01^KV\x01 End of file \x01^KA\x01 Center line \x01^O\x01 Word<  \x02FILE\x02       \x01Esc .\x01 Next ',
' \x01^KM\x01 Move      \x01^A\x01 Beg. of line \x01^K Space\x01 Status \x01^J\x01 >Line  \x01^KE\x01 Edit   \x01Esc ,\x01 Prev ',
' \x01^KW\x01 File      \x01^E\x01 End of line  \x02SPELL\x02           \x01^[O\x01 Line< \x01^KR\x01 Insert \x01^KH\x01 Off   ',
' \x01^KY\x01 Delete    \x01^KL\x01 To line no. \x01Esc N\x01 Word      \x01^_\x01 Undo   \x01^KD\x01 Save   \x01^T\x01  Menu  ',
' \x01^K/\x01 Filter    \x01^G\x01  Matching (  \x01Esc L\x01 File      \x01^^\x01 Redo   \x01^K`\x01 Revert',
];
// \x01 = toggle bold, \x02 = toggle underline in the renderer

// ── HTML escape ────────────────────────────────────────────────────────────
function joeEscHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// render a help row string (handles \x01 bold toggle and \x02 underline toggle)
function joeRenderHelpRow(row) {
    var html = '';
    var bold = false, uline = false;
    for (var i = 0; i < row.length; i++) {
        var c = row[i];
        if (c === '\x01') { bold = !bold; continue; }
        if (c === '\x02') { uline = !uline; continue; }
        var ch = joeEscHtml(c);
        if (bold)  ch = '<b>' + ch + '</b>';
        if (uline) ch = '<u>' + ch + '</u>';
        html += ch;
    }
    return html;
}

// ── Status bar (top) ───────────────────────────────────────────────────────
function joeTopStatus() {
    var d = joeData;
    var flags = ' IW  ';   // Insert + Wordwrap (fixed flags like real joe)
    var rowcol = ' Row ' + (d.curRow + 1) + '    Col ' + (d.curCol + 1) + '   ';
    // left part: flags + filename + modified marker
    var left = flags + ' ' + d.filename + (d.modified ? ' *' : '  ');
    return { left: left, right: rowcol };
}

// ── Bottom notice bar ──────────────────────────────────────────────────────
function joeBottomText() {
    var d = joeData;
    if (d.startupNotice) {
        return " Joe's Own Editor 4.6 (utf-8) ** Type Ctrl-K Q to exit or Ctrl-K H for help **";
    }
    switch (d.mode) {
        case 'saveas':       return ' File name to save: ' + d.saveNameTyped + '_';
        case 'confirm_exit': return ' File has been modified.  Save (y/n/^C)?';
        case 'search':       return ' Find: ' + d.searchTyped + '_';
        default:
            if (d.kPrefix) return ' ^K- (press command key: H=help S=save X=save+exit Q=quit F=find)';
            return '';
    }
}

// ── Render ─────────────────────────────────────────────────────────────────
function joeRender() {
    var overlay = document.getElementById('joe-overlay');
    if (!overlay) return;
    var d = joeData;

    // ── Help rows (top, before status, only when helpOpen) ─────────────────
    var helpEl = document.getElementById('joe-help');
    if (d.helpOpen) {
        helpEl.innerHTML = JOE_HELP_ROWS.map(joeRenderHelpRow).join('\n');
        helpEl.style.display = 'block';
    } else {
        helpEl.style.display = 'none';
        helpEl.innerHTML = '';
    }

    // ── Top status bar ─────────────────────────────────────────────────────
    var ts = joeTopStatus();
    var statusTopEl = document.getElementById('joe-status-top');
    statusTopEl.innerHTML =
        '<span>' + joeEscHtml(ts.left) + '</span>' +
        '<span>' + joeEscHtml(ts.right) + '</span>';

    // ── File content ────────────────────────────────────────────────────────
    var contentEl = document.getElementById('joe-content');
    var html = '';
    for (var r = 0; r < d.lines.length; r++) {
        var line = d.lines[r];
        if (r === d.curRow) {
            var col  = Math.min(d.curCol, line.length);
            var pre  = joeEscHtml(line.slice(0, col));
            var cur  = joeEscHtml(col < line.length ? line[col] : ' ');
            var post = joeEscHtml(line.slice(col + 1));
            html += pre + '<span class="joe-cur">' + cur + '</span>' + post + '\n';
        } else {
            html += joeEscHtml(line) + '\n';
        }
    }
    contentEl.innerHTML = html;

    // scroll cursor into view
    var lineH = contentEl.scrollHeight / Math.max(1, d.lines.length);
    contentEl.scrollTop = Math.max(0, (d.curRow - 10) * lineH);

    // ── Bottom notice / prompt bar ──────────────────────────────────────────
    document.getElementById('joe-status-bottom').textContent = joeBottomText();
}

// ── Entry point ────────────────────────────────────────────────────────────
function doJoe(data) {
    var d = joeData;
    d.path           = data.path;
    d.filename       = data.filename;
    d.lines          = data.content.split('\n');
    d.curRow         = 0;
    d.curCol         = 0;
    d.modified       = false;
    d.cutBuffer      = [];
    d.mode           = 'edit';
    d.searchTyped    = '';
    d.saveNameTyped  = '';
    d._exitAfterSave = false;
    d.kPrefix        = false;
    d.helpOpen       = false;
    d.startupNotice  = true;
    d.isnew          = data.isnew;

    joeActive = true;
    document.getElementById('joe-overlay').style.display = 'flex';
    hidePrompt();
    joeRender();
}

// ── Key handler ────────────────────────────────────────────────────────────
function joeKey(key, ctrlKey, altKey) {
    if (!joeActive) return;
    var d = joeData;

    // First keypress dismisses the startup notice
    if (d.startupNotice) {
        d.startupNotice = false;
        // still process the key below — don't return
    }

    // ── confirm-exit prompt ────────────────────────────────────────────────
    if (d.mode === 'confirm_exit') {
        if (key === 'y' || key === 'Y') {
            d.mode = 'saveas';
            d.saveNameTyped  = d.filename;
            d._exitAfterSave = true;
        } else if (key === 'n' || key === 'N') {
            joeClose(); return;
        } else if (key === 'Escape' || (ctrlKey && (key === 'c' || key === 'C'))) {
            d.mode = 'edit';
        }
        joeRender(); return;
    }

    // ── save-as prompt ─────────────────────────────────────────────────────
    if (d.mode === 'saveas') {
        if (key === 'Enter') {
            joeSave(d.saveNameTyped);
        } else if (key === 'Escape' || (ctrlKey && (key === 'c' || key === 'C'))) {
            d.mode = 'edit'; joeRender();
        } else if (key === 'Backspace') {
            d.saveNameTyped = d.saveNameTyped.slice(0, -1); joeRender();
        } else if (key.length === 1 && !ctrlKey) {
            d.saveNameTyped += key; joeRender();
        }
        return;
    }

    // ── search prompt ──────────────────────────────────────────────────────
    if (d.mode === 'search') {
        if (key === 'Enter') {
            joeDoSearch(d.searchTyped); d.mode = 'edit'; joeRender();
        } else if (key === 'Escape' || (ctrlKey && (key === 'c' || key === 'C'))) {
            d.mode = 'edit'; joeRender();
        } else if (key === 'Backspace') {
            d.searchTyped = d.searchTyped.slice(0, -1); joeRender();
        } else if (key.length === 1 && !ctrlKey) {
            d.searchTyped += key; joeRender();
        }
        return;
    }

    // ── F1 — toggle help ───────────────────────────────────────────────────
    if (key === 'F1') {
        d.helpOpen = !d.helpOpen; d.kPrefix = false; joeRender(); return;
    }

    // ── ^K — set prefix ────────────────────────────────────────────────────
    if (ctrlKey && (key === 'k' || key === 'K') && !d.kPrefix) {
        d.kPrefix = true; joeRender(); return;
    }

    // ── ^K prefix: second key ─────────────────────────────────────────────
    if (d.kPrefix) {
        d.kPrefix = false;
        switch (key.toLowerCase()) {
            case 'h': d.helpOpen = !d.helpOpen; break;
            case 's': d.mode = 'saveas'; d.saveNameTyped = d.filename; d._exitAfterSave = false; break;
            case 'x': d.mode = 'saveas'; d.saveNameTyped = d.filename; d._exitAfterSave = true;  break;
            case 'd': d.mode = 'saveas'; d.saveNameTyped = d.filename; d._exitAfterSave = false; break;
            case 'q': if (d.modified) { d.mode = 'confirm_exit'; } else { joeClose(); return; } break;
            case 'u': d.curRow = 0; d.curCol = 0; break;
            case 'v': d.curRow = d.lines.length - 1; d.curCol = d.lines[d.curRow].length; break;
            case 'f': d.mode = 'search'; d.searchTyped = ''; break;
            case 'l': d.curRow = 0; d.curCol = 0; break;
            case 'y': joeCutLine(); break;
            case 'c': joePaste(); break;
            case 'm': joePaste(); break;
            // ignore unknown ^K combos
        }
        joeRender(); return;
    }

    // ── Ctrl shortcuts ─────────────────────────────────────────────────────
    if (ctrlKey) {
        switch (key.toLowerCase()) {
            case 'h': joeBackspace();   break;
            case 'd': joeDelete();      break;
            case 'f': joeMoveRight();   break;
            case 'b': joeMoveLeft();    break;
            case 'n': joeMoveDown();    break;
            case 'p': joeMoveUp();      break;
            case 'a': d.curCol = 0;    break;
            case 'e': d.curCol = d.lines[d.curRow].length; break;
            case 'y': joeCutLine();     break;
            case 'w': joeDeleteWordRight(); break;
            case 'l': if (d.searchTyped) joeDoSearch(d.searchTyped); break;
            default:  return;  // unknown ctrl — no render
        }
        joeRender(); return;
    }

    // ── Alt shortcuts ─────────────────────────────────────────────────────
    if (altKey) {
        if (key === 'f' || key === 'F') { joeWordRight(); joeRender(); }
        if (key === 'b' || key === 'B') { joeWordLeft();  joeRender(); }
        return;
    }

    // ── Regular keys ──────────────────────────────────────────────────────
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
            var ln = d.lines[d.curRow];
            d.lines.splice(d.curRow, 1, ln.slice(0, d.curCol), ln.slice(d.curCol));
            d.curRow++; d.curCol = 0; d.modified = true;
            break;
        case 'Backspace': joeBackspace(); break;
        case 'Delete':    joeDelete();    break;
        case 'Tab':       joeInsert('\t'); break;
        default:
            if (key.length === 1) { joeInsert(key); }
            else { return; }  // unknown special key — skip render
    }
    joeRender();
}

// ── Cursor movement ────────────────────────────────────────────────────────
function joeMoveUp() {
    var d = joeData;
    if (d.curRow > 0) { d.curRow--; d.curCol = Math.min(d.curCol, d.lines[d.curRow].length); }
}
function joeMoveDown() {
    var d = joeData;
    if (d.curRow < d.lines.length - 1) { d.curRow++; d.curCol = Math.min(d.curCol, d.lines[d.curRow].length); }
}
function joeMoveLeft() {
    var d = joeData;
    if (d.curCol > 0) { d.curCol--; }
    else if (d.curRow > 0) { d.curRow--; d.curCol = d.lines[d.curRow].length; }
}
function joeMoveRight() {
    var d = joeData;
    if (d.curCol < d.lines[d.curRow].length) { d.curCol++; }
    else if (d.curRow < d.lines.length - 1) { d.curRow++; d.curCol = 0; }
}
function joeWordRight() {
    var d = joeData, line = d.lines[d.curRow], col = d.curCol;
    while (col < line.length && line[col] !== ' ') col++;
    while (col < line.length && line[col] === ' ') col++;
    d.curCol = col;
}
function joeWordLeft() {
    var d = joeData, line = d.lines[d.curRow], col = d.curCol;
    if (!col) return;
    col--;
    while (col > 0 && line[col - 1] === ' ') col--;
    while (col > 0 && line[col - 1] !== ' ') col--;
    d.curCol = col;
}

// ── Edit operations ────────────────────────────────────────────────────────
function joeInsert(ch) {
    var d = joeData, l = d.lines[d.curRow];
    d.lines[d.curRow] = l.slice(0, d.curCol) + ch + l.slice(d.curCol);
    d.curCol++; d.modified = true;
}
function joeBackspace() {
    var d = joeData;
    if (d.curCol > 0) {
        var l = d.lines[d.curRow];
        d.lines[d.curRow] = l.slice(0, d.curCol - 1) + l.slice(d.curCol);
        d.curCol--; d.modified = true;
    } else if (d.curRow > 0) {
        var prev = d.lines[d.curRow - 1].length;
        d.lines[d.curRow - 1] += d.lines[d.curRow];
        d.lines.splice(d.curRow, 1);
        d.curRow--; d.curCol = prev; d.modified = true;
    }
}
function joeDelete() {
    var d = joeData, l = d.lines[d.curRow];
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
    if (!d.lines.length) d.lines = [''];
    if (d.curRow >= d.lines.length) d.curRow = d.lines.length - 1;
    d.curCol = 0; d.modified = true;
}
function joePaste() {
    var d = joeData;
    if (!d.cutBuffer.length) return;
    for (var i = 0; i < d.cutBuffer.length; i++) d.lines.splice(d.curRow + i, 0, d.cutBuffer[i]);
    d.modified = true;
}
function joeDeleteWordRight() {
    var d = joeData, line = d.lines[d.curRow], col = d.curCol;
    while (col < line.length && line[col] === ' ') col++;
    while (col < line.length && line[col] !== ' ') col++;
    d.lines[d.curRow] = line.slice(0, d.curCol) + line.slice(col);
    d.modified = true;
}

// ── Search ────────────────────────────────────────────────────────────────
function joeDoSearch(term) {
    var d = joeData;
    if (!term) return;
    for (var i = 0; i < d.lines.length; i++) {
        var r = (d.curRow + i + 1) % d.lines.length;
        var idx = d.lines[r].indexOf(term);
        if (idx !== -1) { d.curRow = r; d.curCol = idx; d.searchTyped = term; return; }
    }
    // not found — flash message in bottom bar
    var el = document.getElementById('joe-status-bottom');
    if (el) { el.textContent = ' "' + term + '": Not found'; }
}

// ── Save ──────────────────────────────────────────────────────────────────
function joeSave(filename) {
    var d = joeData;
    var savePath = d.path.replace(/[^/]+$/, '') + filename;
    fetch('terminal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cmd: '__nano_save', path: savePath, content: d.lines.join('\n'), user: loginUser })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        d.modified = false; d.filename = filename; d.path = savePath;
        if (d._exitAfterSave) {
            joeClose();
        } else {
            d.mode = 'edit';
            var el = document.getElementById('joe-status-bottom');
            if (el) el.textContent = ' Wrote ' + resp.lines + ' line' + (resp.lines === 1 ? '' : 's');
            setTimeout(function() { if (joeActive) joeRender(); }, 2000);
        }
    });
}

// ── Close ─────────────────────────────────────────────────────────────────
function joeClose() {
    joeActive = false;
    document.getElementById('joe-overlay').style.display = 'none';
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
}
