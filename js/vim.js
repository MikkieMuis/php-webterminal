// vim editor overlay — Vim 9.1 look-alike
//
// Layout (top → bottom):
//   [file content area]    ← plain text, fills the middle; line numbers in left gutter
//   [status line]          ← reverse video: filename, modified flag, mode indicator
//   [cmdline bar]          ← : commands and / search; or empty in Normal mode
//
// Modes: splash | normal | insert | cmdline | visual
//
// Splash: fake "Bram Moolenaar" welcome screen shown for ~2 s on open,
//         or dismissed immediately on any keypress.

var vimActive = false;
var vimData = {
    path: '', filename: '', lines: [], curRow: 0, curCol: 0,
    modified: false,
    mode: 'splash',       // splash | normal | insert | cmdline | visual
    cmdline: '',          // text typed after : or /
    cmdlineType: '',      // ':' or '/'
    searchTerm: '',       // last committed search term
    searchDir: 1,         // 1 = forward, -1 = backward
    yankBuffer: [],       // lines yanked/cut
    undoStack: [],        // array of {lines, curRow, curCol} snapshots
    isnew: false,
    gPending: false,      // waiting for second 'g' (for gg)
    dPending: false,      // waiting for second 'd' (for dd)
    yPending: false,      // waiting for second 'y' (for yy)
    visualStart: null,    // {row, col} where visual selection started
    lineNumbers: false,   // :set number toggle
    splashTimer: null,
    _exitAfterSave: false,
};

// ── HTML escape ────────────────────────────────────────────────────────────
function vimEsc(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ── Splash screen content ──────────────────────────────────────────────────
// Deliberately slightly wrong: version 9.1.0413 (real patchlevel is different),
// "Bram Moolenaer" typo in the middle of the splash, charity URL tweaked.
function vimSplashHtml(filename, isnew) {
    var lines = [
        '',
        '',
        '~',
        '~                              VIM - Vi IMproved',
        '~',
        '~                               version 9.1.0413',
        '~                           by Bram Moolenaer et al.',
        '~             Vim is open source and freely distributable',
        '~',
        '~                    Help poor children in Uganda!',
        '~           type  :help iccf<Enter>       for information',
        '~',
        '~           type  :q<Enter>               to exit',
        '~           type  :help<Enter>  or  <F1>  for on-line help',
        '~           type  :help version9<Enter>   for version info',
        '~',
        '~',
    ];
    if (!isnew) {
        lines.push('~             "' + filename + '"');
        lines.push('~');
    }
    return lines;
}

// ── Render ─────────────────────────────────────────────────────────────────
function vimRender() {
    var overlay = document.getElementById('vim-overlay');
    if (!overlay) return;
    var d = vimData;

    if (d.mode === 'splash') {
        vimRenderSplash();
        return;
    }

    // ── Content area ────────────────────────────────────────────────────────
    var contentEl = document.getElementById('vim-content');
    var gutterWidth = d.lineNumbers ? String(d.lines.length).length + 1 : 0;
    var html = '';

    // visual selection range
    var visStartRow = -1, visStartCol = -1, visEndRow = -1, visEndCol = -1;
    if (d.mode === 'visual' && d.visualStart !== null) {
        var vsr = d.visualStart.row, vsc = d.visualStart.col;
        var ver = d.curRow, vec = d.curCol;
        if (vsr > ver || (vsr === ver && vsc > vec)) {
            visStartRow = ver; visStartCol = vec;
            visEndRow   = vsr; visEndCol   = vsc;
        } else {
            visStartRow = vsr; visStartCol = vsc;
            visEndRow   = ver; visEndCol   = vec;
        }
    }

    for (var r = 0; r < d.lines.length; r++) {
        var line = d.lines[r];
        var lineHtml = '';

        // line number gutter
        if (d.lineNumbers) {
            var numStr = String(r + 1);
            while (numStr.length < gutterWidth - 1) numStr = ' ' + numStr;
            lineHtml += '<span class="vim-gutter">' + numStr + ' </span>';
        }

        if (r === d.curRow && d.mode !== 'visual') {
            // cursor on this line
            var col = Math.min(d.curCol, line.length);
            var pre  = vimEsc(line.slice(0, col));
            var atCur = col < line.length ? line[col] : ' ';
            var post = col < line.length ? vimEsc(line.slice(col + 1)) : '';
            var curClass = (d.mode === 'insert') ? 'vim-cur-insert' : 'vim-cur';
            lineHtml += pre + '<span class="' + curClass + '">' + vimEsc(atCur) + '</span>' + post;
        } else if (d.mode === 'visual' && r >= visStartRow && r <= visEndRow) {
            // visual selection
            var selStart = (r === visStartRow) ? visStartCol : 0;
            var selEnd   = (r === visEndRow)   ? visEndCol   : line.length - 1;
            if (selEnd < selStart) selEnd = selStart;
            lineHtml += vimEsc(line.slice(0, selStart))
                     + '<span class="vim-visual">' + vimEsc(line.slice(selStart, selEnd + 1) || ' ') + '</span>'
                     + vimEsc(line.slice(selEnd + 1));
            // also render cursor in visual mode
            if (r === d.curRow) {
                // already handled via visual span
            }
        } else {
            lineHtml += vimEsc(line);
        }

        html += lineHtml + '\n';
    }

    // trailing ~ lines (like real vim) — fill remaining visible space
    // We add a fixed number; CSS overflow:hidden hides excess
    for (var t = 0; t < 30; t++) {
        html += '<span class="vim-tilde">~</span>\n';
    }
    contentEl.innerHTML = html;

    // scroll cursor into view
    var lineH = parseFloat(window.getComputedStyle(contentEl).lineHeight) || 18;
    var visibleRows = Math.floor(contentEl.clientHeight / lineH);
    var scrollRow = contentEl.scrollTop / lineH;
    if (d.curRow < scrollRow + 2) {
        contentEl.scrollTop = Math.max(0, (d.curRow - 2)) * lineH;
    } else if (d.curRow > scrollRow + visibleRows - 3) {
        contentEl.scrollTop = (d.curRow - visibleRows + 3) * lineH;
    }

    // ── Status line (reverse video) ─────────────────────────────────────────
    var statusEl = document.getElementById('vim-status');
    var modeLabel = '';
    if      (d.mode === 'insert') modeLabel = '-- INSERT --';
    else if (d.mode === 'visual') modeLabel = '-- VISUAL --';

    var fileInfo = '"' + d.filename + '"' + (d.isnew ? ' [New File]' : '') + (d.modified ? ' [Modified]' : '');
    var posInfo  = (d.curRow + 1) + ',' + (d.curCol + 1);
    statusEl.innerHTML =
        '<span class="vim-status-left">' + vimEsc(modeLabel + '  ' + fileInfo) + '</span>' +
        '<span class="vim-status-right">' + vimEsc(posInfo) + '</span>';

    // ── Cmdline bar ──────────────────────────────────────────────────────────
    var cmdEl = document.getElementById('vim-cmdline');
    if (d.mode === 'cmdline') {
        cmdEl.textContent = d.cmdlineType + d.cmdline + '_';
    } else {
        cmdEl.textContent = '';
    }
}

function vimRenderSplash() {
    var d = vimData;
    var contentEl = document.getElementById('vim-content');
    var lines = vimSplashHtml(d.filename, d.isnew);
    var html = '';
    for (var i = 0; i < lines.length; i++) {
        var l = lines[i];
        if (l === '~') {
            html += '<span class="vim-tilde">~</span>\n';
        } else if (l.slice(0, 1) === '~') {
            html += '<span class="vim-tilde">~</span>' + vimEsc(l.slice(1)) + '\n';
        } else {
            html += '\n';
        }
    }
    contentEl.innerHTML = html;
    document.getElementById('vim-status').innerHTML =
        '<span class="vim-status-left">' + vimEsc(d.filename + (d.isnew ? ' [New File]' : '')) + '</span>' +
        '<span class="vim-status-right">0,0</span>';
    document.getElementById('vim-cmdline').textContent = '';
}

// ── Entry point ────────────────────────────────────────────────────────────
function doVim(data) {
    var d = vimData;
    d.path           = data.path;
    d.filename       = data.filename;
    d.lines          = (data.content || '').split('\n');
    if (!d.lines.length) d.lines = [''];
    d.curRow         = 0;
    d.curCol         = 0;
    d.modified       = false;
    d.mode           = 'splash';
    d.cmdline        = '';
    d.cmdlineType    = '';
    d.searchTerm     = '';
    d.searchDir      = 1;
    d.yankBuffer     = [];
    d.undoStack      = [];
    d.isnew          = data.isnew;
    d.gPending       = false;
    d.dPending       = false;
    d.yPending       = false;
    d.visualStart    = null;
    d.lineNumbers    = false;
    d._exitAfterSave = false;

    vimActive = true;
    document.getElementById('vim-overlay').style.display = 'flex';
    hidePrompt();
    vimRender();

    // Focus the hidden textarea so browser extensions (Vimium C etc.) don't
    // intercept keystrokes intended for vim.
    var inp = document.getElementById('vimInput');
    if (inp) { inp.value = ''; inp.focus(); }

    // Auto-dismiss splash after 2 seconds
    if (d.splashTimer) clearTimeout(d.splashTimer);
    d.splashTimer = setTimeout(function() {
        if (vimActive && vimData.mode === 'splash') {
            vimData.mode = 'normal';
            vimRender();
        }
    }, 2000);
}

// ── Undo snapshot ──────────────────────────────────────────────────────────
function vimSnapshot() {
    var d = vimData;
    d.undoStack.push({
        lines:  d.lines.slice(),
        curRow: d.curRow,
        curCol: d.curCol,
    });
    if (d.undoStack.length > 100) d.undoStack.shift();
}

// ── Key handler ────────────────────────────────────────────────────────────
function vimKey(key, ctrlKey, altKey) {
    if (!vimActive) return;
    var d = vimData;

    // ── Splash: any key dismisses it ────────────────────────────────────────
    if (d.mode === 'splash') {
        if (d.splashTimer) { clearTimeout(d.splashTimer); d.splashTimer = null; }
        d.mode = 'normal';
        vimRender();
        return;
    }

    // ── Cmdline mode (:  or  /) ─────────────────────────────────────────────
    if (d.mode === 'cmdline') {
        if (key === 'Enter') {
            var raw = d.cmdline;
            d.mode = 'normal'; d.cmdline = '';
            if (d.cmdlineType === '/') {
                d.searchTerm = raw; d.searchDir = 1;
                vimDoSearch(raw, 0, 1);
            } else {
                vimExCommand(raw);
            }
            vimRender(); return;
        }
        if (key === 'Escape') { d.mode = 'normal'; d.cmdline = ''; vimRender(); return; }
        if (key === 'Backspace') {
            if (d.cmdline.length > 0) { d.cmdline = d.cmdline.slice(0, -1); }
            else { d.mode = 'normal'; }   // backspace past the : → cancel
            vimRender(); return;
        }
        if (key.length === 1 && !ctrlKey) { d.cmdline += key; vimRender(); return; }
        return;
    }

    // ── Visual mode ─────────────────────────────────────────────────────────
    if (d.mode === 'visual') {
        if (key === 'Escape') { d.mode = 'normal'; d.visualStart = null; vimRender(); return; }
        if (key === 'h' || key === 'ArrowLeft')  { vimMovLeft();  vimRender(); return; }
        if (key === 'j' || key === 'ArrowDown')  { vimMovDown();  vimRender(); return; }
        if (key === 'k' || key === 'ArrowUp')    { vimMovUp();    vimRender(); return; }
        if (key === 'l' || key === 'ArrowRight') { vimMovRight(); vimRender(); return; }
        if (key === 'y') { vimVisualYank(); d.mode = 'normal'; d.visualStart = null; vimRender(); return; }
        if (key === 'd' || key === 'x') { vimVisualDelete(); d.mode = 'normal'; d.visualStart = null; vimRender(); return; }
        // allow cursor word moves in visual too
        if (key === 'w') { vimWordForward(); vimRender(); return; }
        if (key === 'b') { vimWordBackward(); vimRender(); return; }
        if (key === 'e') { vimWordEnd(); vimRender(); return; }
        return;
    }

    // ── Insert mode ─────────────────────────────────────────────────────────
    if (d.mode === 'insert') {
        if (key === 'Escape') {
            d.mode = 'normal';
            // move cursor left one when leaving insert (like real vim)
            if (d.curCol > 0) d.curCol--;
            vimRender(); return;
        }
        if (ctrlKey) { vimRender(); return; } // swallow Ctrl in insert
        if (key === 'Enter') {
            vimSnapshot();
            var ln = d.lines[d.curRow];
            d.lines.splice(d.curRow, 1, ln.slice(0, d.curCol), ln.slice(d.curCol));
            d.curRow++; d.curCol = 0; d.modified = true;
            vimRender(); return;
        }
        if (key === 'Backspace') {
            vimSnapshot();
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
            vimRender(); return;
        }
        if (key === 'Delete') {
            vimSnapshot();
            var l2 = d.lines[d.curRow];
            if (d.curCol < l2.length) {
                d.lines[d.curRow] = l2.slice(0, d.curCol) + l2.slice(d.curCol + 1);
                d.modified = true;
            } else if (d.curRow < d.lines.length - 1) {
                d.lines[d.curRow] += d.lines[d.curRow + 1];
                d.lines.splice(d.curRow + 1, 1);
                d.modified = true;
            }
            vimRender(); return;
        }
        if (key === 'ArrowLeft')  { vimMovLeft();  vimRender(); return; }
        if (key === 'ArrowRight') { vimMovRight(); vimRender(); return; }
        if (key === 'ArrowUp')    { vimMovUp();    vimRender(); return; }
        if (key === 'ArrowDown')  { vimMovDown();  vimRender(); return; }
        if (key === 'Home') { d.curCol = 0; vimRender(); return; }
        if (key === 'End')  { d.curCol = d.lines[d.curRow].length; vimRender(); return; }
        if (key === 'Tab')  {
            vimSnapshot();
            vimInsertChar('\t'); vimRender(); return;
        }
        if (key.length === 1) {
            vimSnapshot();
            vimInsertChar(key); vimRender(); return;
        }
        return;
    }

    // ── Normal mode ──────────────────────────────────────────────────────────
    // Escape — clear any pending prefixes
    if (key === 'Escape') {
        d.gPending = false; d.dPending = false; d.yPending = false;
        vimRender(); return;
    }

    // Arrow keys work in normal mode too
    if (key === 'ArrowLeft')  { d.gPending=false;d.dPending=false;d.yPending=false; vimMovLeft();  vimRender(); return; }
    if (key === 'ArrowRight') { d.gPending=false;d.dPending=false;d.yPending=false; vimMovRight(); vimRender(); return; }
    if (key === 'ArrowUp')    { d.gPending=false;d.dPending=false;d.yPending=false; vimMovUp();    vimRender(); return; }
    if (key === 'ArrowDown')  { d.gPending=false;d.dPending=false;d.yPending=false; vimMovDown();  vimRender(); return; }
    if (key === 'Home')       { d.curCol = 0;                 vimRender(); return; }
    if (key === 'End')        { d.curCol = d.lines[d.curRow].length > 0 ? d.lines[d.curRow].length - 1 : 0; vimRender(); return; }
    if (key === 'PageUp')     { d.curRow = Math.max(0, d.curRow - 20); d.curCol = Math.min(d.curCol, Math.max(0,d.lines[d.curRow].length-1)); vimRender(); return; }
    if (key === 'PageDown')   { d.curRow = Math.min(d.lines.length-1, d.curRow + 20); d.curCol = Math.min(d.curCol, Math.max(0,d.lines[d.curRow].length-1)); vimRender(); return; }

    if (ctrlKey) {
        // Ctrl+F = page down, Ctrl+B = page up (classic vim scrolling)
        if (key === 'f' || key === 'F') { d.curRow = Math.min(d.lines.length-1, d.curRow+20); vimRender(); return; }
        if (key === 'b' || key === 'B') { d.curRow = Math.max(0, d.curRow-20);                vimRender(); return; }
        return; // swallow other Ctrl in normal mode
    }

    // ── Pending 'd' prefix (dd) ─────────────────────────────────────────────
    if (d.dPending) {
        d.dPending = false;
        if (key === 'd') {
            vimSnapshot();
            d.yankBuffer = [d.lines[d.curRow]];
            d.lines.splice(d.curRow, 1);
            if (!d.lines.length) d.lines = [''];
            if (d.curRow >= d.lines.length) d.curRow = d.lines.length - 1;
            d.curCol = 0; d.modified = true;
        }
        vimRender(); return;
    }

    // ── Pending 'y' prefix (yy) ─────────────────────────────────────────────
    if (d.yPending) {
        d.yPending = false;
        if (key === 'y') {
            d.yankBuffer = [d.lines[d.curRow]];
            vimFlash('1 line yanked');
        }
        vimRender(); return;
    }

    // ── Pending 'g' prefix (gg) ─────────────────────────────────────────────
    if (d.gPending) {
        d.gPending = false;
        if (key === 'g') {
            d.curRow = 0; d.curCol = 0;
        }
        vimRender(); return;
    }

    // ── Single-key normal mode commands ────────────────────────────────────
    switch (key) {
        // cursor
        case 'h': vimMovLeft();    break;
        case 'j': vimMovDown();    break;
        case 'k': vimMovUp();      break;
        case 'l': vimMovRight();   break;
        case 'w': vimWordForward(); break;
        case 'b': vimWordBackward(); break;
        case 'e': vimWordEnd();    break;
        case '0': d.curCol = 0;   break;
        case '$': d.curCol = Math.max(0, d.lines[d.curRow].length - 1); break;
        case '^': {
            var ln0 = d.lines[d.curRow];
            var fc = 0; while (fc < ln0.length && (ln0[fc] === ' ' || ln0[fc] === '\t')) fc++;
            d.curCol = fc; break;
        }
        case 'G': d.curRow = d.lines.length - 1; d.curCol = Math.max(0, d.lines[d.curRow].length - 1); break;
        case 'g': d.gPending = true; vimRender(); return;

        // enter insert
        case 'i': d.mode = 'insert'; break;
        case 'a':
            d.mode = 'insert';
            if (d.curCol < d.lines[d.curRow].length) d.curCol++;
            break;
        case 'I':
            d.mode = 'insert'; d.curCol = 0; break;
        case 'A':
            d.mode = 'insert'; d.curCol = d.lines[d.curRow].length; break;
        case 'o':
            vimSnapshot();
            d.lines.splice(d.curRow + 1, 0, '');
            d.curRow++; d.curCol = 0; d.modified = true; d.mode = 'insert'; break;
        case 'O':
            vimSnapshot();
            d.lines.splice(d.curRow, 0, '');
            d.curCol = 0; d.modified = true; d.mode = 'insert'; break;

        // delete char
        case 'x':
            if (d.lines[d.curRow].length > 0) {
                vimSnapshot();
                var lx = d.lines[d.curRow];
                d.lines[d.curRow] = lx.slice(0, d.curCol) + lx.slice(d.curCol + 1);
                if (d.curCol >= d.lines[d.curRow].length && d.curCol > 0) d.curCol--;
                d.modified = true;
            }
            break;

        // delete line prefix
        case 'd': d.dPending = true; vimRender(); return;

        // yank line prefix
        case 'y': d.yPending = true; vimRender(); return;

        // paste
        case 'p':
            if (d.yankBuffer.length) {
                vimSnapshot();
                var insRow = d.curRow + 1;
                for (var pi = 0; pi < d.yankBuffer.length; pi++) {
                    d.lines.splice(insRow + pi, 0, d.yankBuffer[pi]);
                }
                d.curRow = insRow; d.curCol = 0; d.modified = true;
            }
            break;
        case 'P':
            if (d.yankBuffer.length) {
                vimSnapshot();
                for (var qi = 0; qi < d.yankBuffer.length; qi++) {
                    d.lines.splice(d.curRow + qi, 0, d.yankBuffer[qi]);
                }
                d.curCol = 0; d.modified = true;
            }
            break;

        // undo
        case 'u':
            if (d.undoStack.length) {
                var snap = d.undoStack.pop();
                d.lines  = snap.lines;
                d.curRow = snap.curRow;
                d.curCol = snap.curCol;
                d.modified = true;
                vimFlash('1 change; before #' + (d.undoStack.length + 1));
            } else {
                vimFlash('Already at oldest change');
            }
            break;

        // search
        case '/': d.mode = 'cmdline'; d.cmdlineType = '/'; d.cmdline = ''; break;
        case '?': d.mode = 'cmdline'; d.cmdlineType = '?'; d.cmdline = ''; break;
        case 'n': if (d.searchTerm) vimDoSearch(d.searchTerm, 1, d.searchDir);  break;
        case 'N': if (d.searchTerm) vimDoSearch(d.searchTerm, 1, -d.searchDir); break;

        // ex command line
        case ':': d.mode = 'cmdline'; d.cmdlineType = ':'; d.cmdline = ''; break;

        // visual mode
        case 'v': d.mode = 'visual'; d.visualStart = {row: d.curRow, col: d.curCol}; break;

        // replace single char (r)
        // not implemented for simplicity — just ignore
        case 'r': break;

        // J — join lines
        case 'J':
            if (d.curRow < d.lines.length - 1) {
                vimSnapshot();
                d.lines[d.curRow] = d.lines[d.curRow] + ' ' + d.lines[d.curRow + 1].trimStart();
                d.lines.splice(d.curRow + 1, 1);
                d.modified = true;
            }
            break;

        default: return; // unknown key — no render
    }

    vimRender();
}

// ── Cursor movement helpers ────────────────────────────────────────────────
function vimClampCol() {
    var d = vimData;
    var maxCol = d.mode === 'insert' ? d.lines[d.curRow].length : Math.max(0, d.lines[d.curRow].length - 1);
    if (d.curCol > maxCol) d.curCol = maxCol;
    if (d.curCol < 0) d.curCol = 0;
}
function vimMovLeft() {
    var d = vimData;
    if (d.curCol > 0) d.curCol--;
}
function vimMovRight() {
    var d = vimData;
    var maxCol = d.mode === 'insert' ? d.lines[d.curRow].length : Math.max(0, d.lines[d.curRow].length - 1);
    if (d.curCol < maxCol) d.curCol++;
}
function vimMovUp() {
    var d = vimData;
    if (d.curRow > 0) { d.curRow--; vimClampCol(); }
}
function vimMovDown() {
    var d = vimData;
    if (d.curRow < d.lines.length - 1) { d.curRow++; vimClampCol(); }
}
function vimWordForward() {
    var d = vimData, line = d.lines[d.curRow], col = d.curCol;
    // skip current word chars
    var isWord = function(c) { return /\w/.test(c); };
    if (col < line.length && isWord(line[col])) {
        while (col < line.length && isWord(line[col])) col++;
    } else {
        while (col < line.length && !isWord(line[col])) col++;
    }
    // skip whitespace
    while (col < line.length && line[col] === ' ') col++;
    if (col >= line.length && d.curRow < d.lines.length - 1) {
        d.curRow++; col = 0;
    }
    d.curCol = col;
}
function vimWordBackward() {
    var d = vimData, line = d.lines[d.curRow], col = d.curCol;
    if (col === 0) {
        if (d.curRow > 0) { d.curRow--; d.curCol = d.lines[d.curRow].length > 0 ? d.lines[d.curRow].length - 1 : 0; }
        return;
    }
    col--;
    while (col > 0 && d.lines[d.curRow][col] === ' ') col--;
    var isWord = function(c) { return /\w/.test(c); };
    if (isWord(line[col])) {
        while (col > 0 && isWord(line[col - 1])) col--;
    } else {
        while (col > 0 && !isWord(line[col - 1]) && line[col - 1] !== ' ') col--;
    }
    d.curCol = col;
}
function vimWordEnd() {
    var d = vimData, line = d.lines[d.curRow], col = d.curCol;
    if (col < line.length - 1) col++;
    while (col < line.length - 1 && line[col] === ' ') col++;
    var isWord = function(c) { return /\w/.test(c); };
    if (isWord(line[col])) {
        while (col < line.length - 1 && isWord(line[col + 1])) col++;
    } else {
        while (col < line.length - 1 && !isWord(line[col + 1]) && line[col + 1] !== ' ') col++;
    }
    d.curCol = col;
}

// ── Insert a character at cursor ───────────────────────────────────────────
function vimInsertChar(ch) {
    var d = vimData, l = d.lines[d.curRow];
    d.lines[d.curRow] = l.slice(0, d.curCol) + ch + l.slice(d.curCol);
    d.curCol++; d.modified = true;
}

// ── Visual yank / delete ───────────────────────────────────────────────────
function vimVisualRange() {
    var d = vimData;
    var vsr = d.visualStart.row, vsc = d.visualStart.col;
    var ver = d.curRow, vec = d.curCol;
    if (vsr > ver || (vsr === ver && vsc > vec)) {
        return { startRow: ver, startCol: vec, endRow: vsr, endCol: vsc };
    }
    return { startRow: vsr, startCol: vsc, endRow: ver, endCol: vec };
}
function vimVisualYank() {
    var d = vimData;
    var r = vimVisualRange();
    d.yankBuffer = [];
    for (var i = r.startRow; i <= r.endRow; i++) {
        var l = d.lines[i];
        var s = (i === r.startRow) ? r.startCol : 0;
        var e = (i === r.endRow)   ? r.endCol + 1 : l.length;
        d.yankBuffer.push(l.slice(s, e));
    }
    vimFlash(d.yankBuffer.length + ' line' + (d.yankBuffer.length === 1 ? '' : 's') + ' yanked');
}
function vimVisualDelete() {
    var d = vimData;
    var r = vimVisualRange();
    vimSnapshot();
    d.yankBuffer = [];
    if (r.startRow === r.endRow) {
        var l = d.lines[r.startRow];
        d.yankBuffer.push(l.slice(r.startCol, r.endCol + 1));
        d.lines[r.startRow] = l.slice(0, r.startCol) + l.slice(r.endCol + 1);
        d.curCol = r.startCol;
    } else {
        for (var i = r.startRow; i <= r.endRow; i++) {
            d.yankBuffer.push(d.lines[i]);
        }
        d.lines.splice(r.startRow, r.endRow - r.startRow + 1);
        if (!d.lines.length) d.lines = [''];
        d.curRow = r.startRow;
        if (d.curRow >= d.lines.length) d.curRow = d.lines.length - 1;
        d.curCol = 0;
    }
    d.modified = true;
}

// ── Search ────────────────────────────────────────────────────────────────
// skip=0 means search from current position (inclusive, used for first search)
// skip=1 means skip current match (for n/N)
function vimDoSearch(term, skip, dir) {
    var d = vimData;
    if (!term) return;
    var startRow = d.curRow;
    var startCol = d.curCol + (skip ? dir : 0);
    var total = d.lines.length;

    for (var step = 0; step < total; step++) {
        var r = ((startRow + step * dir) % total + total) % total;
        var line = d.lines[r];
        var idx = (dir > 0)
            ? line.indexOf(term, step === 0 ? Math.max(0, startCol) : 0)
            : line.lastIndexOf(term, step === 0 ? Math.max(0, startCol) : line.length);
        if (idx !== -1) {
            d.curRow = r; d.curCol = idx;
            d.searchTerm = term; d.searchDir = dir;
            return;
        }
    }
    vimFlash('Pattern not found: ' + term);
}

// ── Ex commands (:w :q :wq :x etc.) ───────────────────────────────────────
function vimExCommand(raw) {
    var d = vimData;
    raw = raw.trim();
    if (raw === '' ) return;

    // :set number / :set nonumber
    if (raw === 'set number' || raw === 'set nu') { d.lineNumbers = true; return; }
    if (raw === 'set nonumber' || raw === 'set nonu') { d.lineNumbers = false; return; }

    // :w [filename]
    if (raw === 'w' || raw.slice(0, 2) === 'w ') {
        var saveName = raw.length > 2 ? raw.slice(2).trim() : d.filename;
        vimSave(saveName, false);
        return;
    }
    // :wq  :x  :wq!
    if (raw === 'wq' || raw === 'wq!' || raw === 'x') {
        var saveName2 = d.filename;
        vimSave(saveName2, true);
        return;
    }
    // :q
    if (raw === 'q') {
        if (d.modified) {
            vimFlash('No write since last change (add ! to override)');
        } else {
            vimClose();
        }
        return;
    }
    // :q!
    if (raw === 'q!') {
        vimClose();
        return;
    }
    // :N (go to line number)
    if (/^\d+$/.test(raw)) {
        var ln = parseInt(raw, 10) - 1;
        d.curRow = Math.max(0, Math.min(d.lines.length - 1, ln));
        d.curCol = 0;
        return;
    }
    // unknown
    vimFlash('Not an editor command: ' + raw);
}

// ── Save ──────────────────────────────────────────────────────────────────
function vimSave(filename, exitAfter) {
    var d = vimData;
    d._exitAfterSave = exitAfter;
    var savePath = d.path.indexOf('/') !== -1
        ? d.path.replace(/[^/]+$/, '') + filename
        : filename;
    fetch('terminal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cmd: '__nano_save',
            path: savePath,
            content: d.lines.join('\n'),
            user: loginUser
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        d.modified = false;
        d.filename = filename;
        d.path = savePath;
        if (d._exitAfterSave) {
            vimClose();
        } else {
            vimFlash('"' + filename + '" ' + (resp.lines || 0) + 'L written');
            vimRender();
        }
    })
    .catch(function() {
        vimFlash('Error: could not save file');
        vimRender();
    });
}

// ── Flash message in cmdline bar ───────────────────────────────────────────
function vimFlash(msg) {
    var el = document.getElementById('vim-cmdline');
    if (!el) return;
    el.textContent = msg;
    setTimeout(function() {
        if (vimActive && vimData.mode === 'normal') {
            el.textContent = '';
        }
    }, 2500);
}

// ── Close ─────────────────────────────────────────────────────────────────
function vimClose() {
    vimActive = false;
    if (vimData.splashTimer) { clearTimeout(vimData.splashTimer); vimData.splashTimer = null; }
    document.getElementById('vim-overlay').style.display = 'none';
    var inp = document.getElementById('vimInput');
    if (inp) inp.blur();
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
}

// ── vimInput keyboard handling ────────────────────────────────────────────
// The hidden #vimInput textarea holds focus while vim is open so browser
// extensions (Vimium C, etc.) cannot intercept vim key bindings.
// We wire up keydown + input events here, mirroring mobileInput in index.php.
(function() {
    function getInp() { return document.getElementById('vimInput'); }

    // keydown: handle every key (control keys AND printable) directly.
    // stopPropagation prevents the document-level keydown from also firing.
    document.addEventListener('DOMContentLoaded', function() {
        var inp = getInp();
        if (!inp) return;

        inp.addEventListener('keydown', function(e) {
            if (!vimActive) return;
            e.stopPropagation();
            e.preventDefault();
            inp.value = '';
            vimKey(e.key, e.ctrlKey, e.altKey);
        });

        // 'input' event fires on Android / some mobile keyboards for printable
        // chars before keydown. Feed each character individually.
        inp.addEventListener('input', function() {
            if (!vimActive) return;
            var val = inp.value;
            inp.value = '';
            if (!val) return;
            for (var i = 0; i < val.length; i++) {
                vimKey(val[i], false, false);
            }
        });

        // If the user clicks anywhere on the vim overlay, refocus vimInput
        var overlay = document.getElementById('vim-overlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                if (vimActive) { inp.value = ''; inp.focus(); }
            });
        }
    });
})();
