<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terminal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
/* reset */
* { margin:0; padding:0; box-sizing:border-box; }

/* page fills whatever container embeds it */
html, body {
  width:100%; height:100%;
  background:#0a0a0a;
  font-family:'JetBrains Mono', 'Courier New', monospace;
  overflow:hidden;
}

/* terminal frame */
#terminal {
  width:100%; height:100%;
  display:flex;
  flex-direction:column;
}



/* screen area */
#screen {
  flex:1;
  overflow-y:auto;
  padding:14px 16px;
  background:#0a0a0a;
  font-size:15px;
  user-select:text;
  -webkit-user-select:text;
  line-height:1.6;
  color:#e0e0e0;
  cursor:text;
}
#screen::-webkit-scrollbar { width:6px; }
#screen::-webkit-scrollbar-track { background:#0a0a0a; }
#screen::-webkit-scrollbar-thumb { background:#444444; border-radius:3px; }

/* output lines */
.ln {
  display:block;
  white-space:pre-wrap;
  word-break:break-word;
  font-family:'JetBrains Mono', 'Courier New', monospace;
}
.n { color:#e0e0e0; }
.d { color:#808080; }
.b { color:#ffffff; }
.e { color:#ff4444; }
.w { color:#ffaa00; }

/* input line */
#curline {
  display:flex;
  align-items:center;
  font-family:'JetBrains Mono', 'Courier New', monospace;
  font-size:15px;
  color:#e0e0e0;
  margin-top:2px;
  gap:0;
}
#curprompt  { white-space:nowrap; color:#e0e0e0; }
#curtyped   { color:#e0e0e0; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
#curcursor  {
  display:inline-block;
  width:0.6ch; height:1.1em;
  background:#e0e0e0;
  animation:blink 1s infinite;
}

/* nano overlay */
#nano-overlay {
  position:absolute; top:0; left:0; width:100%; height:100%;
  background:#0a0a0a; color:#e0e0e0;
  font-family:'JetBrains Mono','Courier New',monospace; font-size:13px;
  display:flex; flex-direction:column;
  z-index:100; overflow:hidden;
}
#nano-titlebar {
  background:#e0e0e0; color:#0a0a0a;
  padding:2px 4px; text-align:center;
  font-weight:bold; flex-shrink:0;
  white-space:nowrap; overflow:hidden;
}
#nano-content {
  flex:1; overflow:hidden; position:relative;
  padding:2px 4px;
  white-space:pre; font-family:'JetBrains Mono','Courier New',monospace;
}
#nano-status {
  background:#0a0a0a; color:#e0e0e0;
  padding:1px 4px; min-height:1.4em; flex-shrink:0;
  font-size:13px;
}
#nano-shortcuts {
  flex-shrink:0;
}
.nano-shortcut-row {
  display:flex; flex-wrap:wrap;
  background:#e0e0e0; color:#0a0a0a;
  font-size:12px; padding:1px 2px;
}
.nano-sc {
  display:inline-flex; margin-right:8px; white-space:nowrap;
}
.nano-sc-key {
  background:#0a0a0a; color:#e0e0e0;
  padding:0 3px; margin-right:2px;
}
#nano-search-bar {
  background:#0a0a0a; color:#e0e0e0;
  padding:1px 4px; flex-shrink:0;
  display:none;
}

/* mysql overlay */
#mysql-overlay {
  position:absolute; top:0; left:0; width:100%; height:100%;
  background:#0a0a0a; color:#e0e0e0;
  font-family:'JetBrains Mono','Courier New',monospace; font-size:13px;
  display:flex; flex-direction:column;
  z-index:102; overflow:hidden;
}
#mysql-titlebar {
  background:#e0e0e0; color:#0a0a0a;
  padding:1px 4px; flex-shrink:0;
  font-size:13px; font-weight:bold;
  white-space:nowrap; overflow:hidden;
}
#mysql-output {
  flex:1; overflow-y:auto; padding:4px 6px;
  white-space:pre; word-break:break-all;
}
.mysql-error { color:#ff5555; }
#mysql-inputline {
  display:flex; align-items:center; padding:2px 6px;
  flex-shrink:0; border-top:1px solid #333;
  white-space:pre;
}
#mysql-prompt-label { color:#e0e0e0; }
#mysql-prompt-db    { color:#7ec8e3; }
#mysql-input        { color:#e0e0e0; flex:1; }
#mysql-cursor {
  display:inline-block; width:0.6ch; height:1.1em;
  background:#e0e0e0; animation:blink 1s infinite;
}

/* vim overlay — styled after real Vim 9 */
#vim-overlay {
  position:absolute; top:0; left:0; width:100%; height:100%;
  background:#0a0a0a; color:#e0e0e0;
  font-family:'JetBrains Mono','Courier New',monospace; font-size:13px;
  display:flex; flex-direction:column;
  z-index:103; overflow:hidden;
}
/* Content area: fills the space between top and status/cmdline */
#vim-content {
  flex:1; overflow:hidden; position:relative;
  padding:0; white-space:pre;
  font-family:'JetBrains Mono','Courier New',monospace;
}
/* Block cursor in normal mode */
.vim-cur {
  background:#e0e0e0; color:#0a0a0a;
}
/* Thin cursor in insert mode */
.vim-cur-insert {
  border-left:2px solid #e0e0e0;
}
/* Tilde lines (lines past end of file) */
.vim-tilde {
  color:#5555cc;
}
/* Line number gutter */
.vim-gutter {
  color:#555555; user-select:none; -webkit-user-select:none;
  display:inline-block;
}
/* Visual selection highlight */
.vim-visual {
  background:#264f78; color:#e0e0e0;
}
/* Status line — reverse video, bottom of content area */
#vim-status {
  background:#e0e0e0; color:#0a0a0a;
  padding:1px 4px; flex-shrink:0;
  font-size:13px; font-weight:bold;
  display:flex; justify-content:space-between;
  white-space:nowrap; overflow:hidden;
}
/* Cmdline bar — very bottom, shows : and / commands, flash messages */
#vim-cmdline {
  background:#0a0a0a; color:#e0e0e0;
  padding:1px 4px; min-height:1.4em; flex-shrink:0;
  font-size:13px;
}

/* joe overlay — styled after real JOE 4.6 */
#joe-overlay {
  position:absolute; top:0; left:0; width:100%; height:100%;
  background:#0a0a0a; color:#e0e0e0;
  font-family:'JetBrains Mono','Courier New',monospace; font-size:13px;
  display:flex; flex-direction:column;
  z-index:101; overflow:hidden;
}
/* Help rows — shown at top when ^KH / F1 pressed */
#joe-help {
  display:none;
  background:#e0e0e0; color:#0a0a0a;
  padding:1px 4px; flex-shrink:0;
  font-size:12px; white-space:pre;
  font-weight:bold;
}
/* Edit content area — fills available space */
#joe-content {
  flex:1; overflow:auto;
  padding:2px 4px;
  white-space:pre; font-family:'JetBrains Mono','Courier New',monospace;
}
/* Block cursor style */
.joe-cur {
  background:#e0e0e0; color:#0a0a0a;
}
/* Top status bar — reverse video, left/right split (filename + Row/Col) */
#joe-status-top {
  background:#e0e0e0; color:#0a0a0a;
  padding:1px 4px; flex-shrink:0;
  font-size:13px; font-weight:bold;
  display:flex; justify-content:space-between;
  white-space:nowrap; overflow:hidden;
}
#joe-status-top span:last-child {
  padding-left:8px; flex-shrink:0;
}
/* Bottom notice/prompt bar — reverse video */
#joe-status-bottom {
  background:#e0e0e0; color:#0a0a0a;
  padding:1px 4px; flex-shrink:0;
  font-size:13px; font-weight:bold;
  white-space:nowrap; overflow:hidden;
}
</style>
</head>
<body>
<div id="terminal">
  <!-- hidden input for mobile soft keyboard -->
  <input id="mobileInput" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
    style="position:fixed;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;border:none;padding:0;margin:0;font-size:16px;">

  <div id="screen">
    <div id="curline"><span id="curprompt"></span><span id="curtyped"></span><span id="curcursor"></span></div>
  </div>
  <!-- nano overlay (hidden until nano command runs) -->
  <div id="nano-overlay" style="display:none;">
    <div id="nano-titlebar"></div>
    <div id="nano-content"></div>
    <div id="nano-status"></div>
    <div id="nano-search-bar"></div>
    <div id="nano-shortcuts">
      <div class="nano-shortcut-row">
        <span class="nano-sc"><span class="nano-sc-key">^G</span>Help</span>
        <span class="nano-sc"><span class="nano-sc-key">^S</span>Write Out</span>
        <span class="nano-sc"><span class="nano-sc-key">^F</span>Where Is</span>
        <span class="nano-sc"><span class="nano-sc-key">^K</span>Cut</span>
        <span class="nano-sc"><span class="nano-sc-key">^U</span>Paste</span>
        <span class="nano-sc"><span class="nano-sc-key">^T</span>Execute</span>
      </div>
      <div class="nano-shortcut-row">
        <span class="nano-sc"><span class="nano-sc-key">^X</span>Exit</span>
        <span class="nano-sc"><span class="nano-sc-key">^R</span>Read File</span>
        <span class="nano-sc"><span class="nano-sc-key">^\</span>Replace</span>
        <span class="nano-sc"><span class="nano-sc-key">^U</span>Paste</span>
        <span class="nano-sc"><span class="nano-sc-key">^J</span>Justify</span>
        <span class="nano-sc"><span class="nano-sc-key">^C</span>Location</span>
      </div>
    </div>
  </div>
  <!-- joe overlay (hidden until joe command runs) -->
  <!-- Layout (top→bottom): help rows | top status bar | content | bottom notice bar -->
  <div id="joe-overlay" style="display:none;">
    <div id="joe-help"></div>
    <div id="joe-status-top"><span></span><span></span></div>
    <div id="joe-content"></div>
    <div id="joe-status-bottom"></div>
  </div>

  <!-- vim overlay (hidden until vim/vi command runs) -->
  <!-- Layout (top→bottom): content area | status line | cmdline bar -->
  <div id="vim-overlay" style="display:none;">
    <div id="vim-content"></div>
    <div id="vim-status"><span></span><span></span></div>
    <div id="vim-cmdline"></div>
  </div>

  <!-- mysql overlay (hidden until mysql/mariadb command runs) -->
  <div id="mysql-overlay" style="display:none;">
    <div id="mysql-titlebar">MariaDB 10.5.22</div>
    <div id="mysql-output"></div>
    <div id="mysql-inputline">
      <span id="mysql-prompt-label">MariaDB [</span><span id="mysql-prompt-db">none</span><span id="mysql-prompt-label">]&gt;&nbsp;</span><span id="mysql-input"></span><span id="mysql-cursor">&nbsp;</span>
    </div>
  </div>
</div>

<script src="js/pager.js?v=<?php echo filemtime(__DIR__.'/js/pager.js'); ?>"></script>
<script src="js/nano.js?v=<?php echo filemtime(__DIR__.'/js/nano.js'); ?>"></script>
<script src="js/joe.js?v=<?php echo filemtime(__DIR__.'/js/joe.js'); ?>"></script>
<script src="js/vim.js?v=<?php echo filemtime(__DIR__.'/js/vim.js'); ?>"></script>
<script src="js/mysql.js?v=<?php echo filemtime(__DIR__.'/js/mysql.js'); ?>"></script>
<script src="js/interactive.js?v=<?php echo filemtime(__DIR__.'/js/interactive.js'); ?>"></script>
<script src="js/rsearch.js?v=<?php echo filemtime(__DIR__.'/js/rsearch.js'); ?>"></script>
<script src="js/overlays.js?v=<?php echo filemtime(__DIR__.'/js/overlays.js'); ?>"></script>
<script>
// DOM refs
var scr       = document.getElementById('screen');
var curline   = document.getElementById('curline');
var curprompt = document.getElementById('curprompt');
var curtyped  = document.getElementById('curtyped');
var curcursor = document.getElementById('curcursor');
var mobileInput = document.getElementById('mobileInput');

// --- Mobile soft-keyboard support ---
// mobileActive = true while the hidden input is focused (i.e. soft keyboard is up).
// When active, the document keydown handler skips printable characters so only
// the 'input' event processes them — preventing double-insertion.
var mobileActive = false;

mobileInput.addEventListener('focus', function() { mobileActive = true;  });
mobileInput.addEventListener('blur',  function() { mobileActive = false; });

// Tap anywhere on the terminal → focus the hidden input so the soft keyboard appears.
document.getElementById('terminal').addEventListener('click', function() {
  if (mode === 'boot' || mode === 'dead') return;
  mobileInput.focus();
});

// 'input' event: fired by Android soft keyboard for every character typed.
// We read whatever landed in the field, feed it to handleKey(), then clear the field.
mobileInput.addEventListener('input', function() {
  if (mode === 'boot' || mode === 'dead') return;
  var val = mobileInput.value;
  mobileInput.value = '';
  if (!val) return;
  for (var i = 0; i < val.length; i++) {
    handleKey(val[i], false, false, false);
  }
});

// 'keydown' on the hidden input: used for control keys (Enter, Backspace, arrows).
// stopPropagation() prevents the document keydown handler from also firing.
mobileInput.addEventListener('keydown', function(e) {
  if (mode === 'boot' || mode === 'dead') return;
  var ctrl = e.ctrlKey || e.metaKey;
  var isControl = e.key === 'Backspace' || e.key === 'Delete' ||
                  e.key === 'Enter' ||
                  e.key === 'ArrowLeft' || e.key === 'ArrowRight' ||
                  e.key === 'ArrowUp'   || e.key === 'ArrowDown' ||
                  e.key === 'Home' || e.key === 'End' || ctrl;
  // Always stop propagation so document keydown never double-fires while mobile is active.
  e.stopPropagation();
  if (isControl) {
    e.preventDefault();
    mobileInput.value = '';
    handleKey(e.key, e.ctrlKey, e.altKey, e.metaKey);
  }
  // Printable keys: do nothing here — the 'input' event handles them.
});


// system info (populated before boot)
var sysKernel   = '5.14.0-1-default';
var sysArch     = 'x86_64';
var sysOS       = 'Linux';
var sysHostname = 'localhost';

// terminal column count (measured from actual screen width)
function termCols() {
  var testEl = document.createElement('span');
  testEl.style.cssText = 'visibility:hidden;position:absolute;white-space:pre;font-family:"JetBrains Mono","Courier New",monospace;font-size:15px;';
  testEl.textContent = 'X';
  document.body.appendChild(testEl);
  var charW = testEl.getBoundingClientRect().width;
  document.body.removeChild(testEl);
  var screenW = document.getElementById('screen').getBoundingClientRect().width;
  return charW > 0 ? Math.floor(screenW / charW) : 80;
}

// state
var mode      = 'boot';   // boot | command | sudo_password | su_password | passwd
var typed     = '';
var cursorPos = 0;        // insertion point within typed (0 = before first char)
var masked    = false;
var loginUser = 'guest';
var cwd       = '/home/guest';
var cmdLog    = [];
var histIdx   = -1;

// reverse history search state
var rsearchActive = false;
var rsearchQuery  = '';   // what the user is typing to search for
var rsearchIdx    = 0;    // which match we are on (0 = most recent)
var rsearchMatch  = '';   // the currently matched command

// tab-completion state
var tabLastTyped  = null; // typed string at last Tab press (to detect double-Tab)
var tabBusy       = false; // debounce: ignore Tab while a fetch is in flight

// ANSI colour map — SGR codes → CSS colour strings
// Supports: 0 reset, 1 bold, 30-37 fg, 90-97 bright fg, 38;5;N 256-colour fg
var _ansiColors = {
  '30':'#2e2e2e','31':'#ff4444','32':'#44cc44','33':'#cccc00',
  '34':'#5588ff','35':'#cc44cc','36':'#44cccc','37':'#e0e0e0',
  '90':'#808080','91':'#ff6666','92':'#66ff66','93':'#ffff66',
  '94':'#6699ff','95':'#ff66ff','96':'#66ffff','97':'#ffffff'
};

// Convert a string containing ANSI SGR escape sequences to safe HTML.
// Each segment between escapes becomes a <span> with inline style.
function ansiToHtml(text) {
  // fast path — no escape sequences
  if (text.indexOf('\x1b') === -1) {
    return escHtml(text);
  }
  var out = '';
  var curStyle = '';   // current CSS style string
  var open = false;    // whether a <span> is open
  // split on ESC[ ... m  sequences
  var re = /\x1b\[([0-9;]*)m/g;
  var last = 0; var m;
  while ((m = re.exec(text)) !== null) {
    // text before this escape
    var seg = text.slice(last, m.index);
    if (seg) {
      if (open) out += escHtml(seg);
      else if (curStyle) { out += '<span style="' + curStyle + '">' + escHtml(seg) + '</span>'; }
      else out += escHtml(seg);
    }
    last = re.lastIndex;
    // parse SGR params
    var params = m[1] === '' ? ['0'] : m[1].split(';');
    var i = 0; var bold = false; var fg = '';
    // close current span
    if (open) { out += '</span>'; open = false; }
    curStyle = '';
    while (i < params.length) {
      var p = params[i];
      if (p === '0' || p === '') { bold = false; fg = ''; }
      else if (p === '1') { bold = true; }
      else if (_ansiColors[p]) { fg = _ansiColors[p]; }
      else if (p === '38' && params[i+1] === '5' && params[i+2] !== undefined) {
        // 256-colour: map to nearest named colour (simplified)
        var n = parseInt(params[i+2], 10);
        if (n < 8)  fg = _ansiColors[String(30+n)];
        else if (n < 16) fg = _ansiColors[String(90+(n-8))];
        else fg = '#e0e0e0'; // punt on 216-cube
        i += 2;
      }
      i++;
    }
    var parts = [];
    if (fg) parts.push('color:' + fg);
    if (bold) parts.push('font-weight:bold');
    if (parts.length) { curStyle = parts.join(';'); open = true; out += '<span style="' + curStyle + '">'; }
  }
  // remainder
  var tail = text.slice(last);
  if (tail) out += escHtml(tail);
  if (open) out += '</span>';
  return out;
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// output helpers
function print(text, cls) {
  var s = document.createElement('span');
  s.className = 'ln ' + (cls || 'n');
  if (text.indexOf('\x1b') !== -1) {
    s.innerHTML = ansiToHtml(text);
  } else {
    s.textContent = text;
  }
  scr.insertBefore(s, curline);
  scr.scrollTop = scr.scrollHeight;
}

function clearScr() {
  scr.querySelectorAll('.ln').forEach(function(l){ l.remove(); });
}

function setPrompt(text, isMasked) {
  curprompt.textContent = text;
  masked = !!isMasked;
  typed     = '';
  cursorPos = 0;
  renderLine();
  curline.style.display = 'flex';
  scr.scrollTop = scr.scrollHeight;
}

function hidePrompt() {
  curline.style.display = 'none';
}

function updateTitleAndPrompt() {
  var userHome  = (loginUser === 'root') ? '/root' : '/home/' + loginUser;
  var shortCwd  = cwd === userHome ? '~' : cwd.replace(userHome + '/', '~/');
  var sigil     = (loginUser === 'root') ? '#' : '$';
  var p = loginUser + '@' + sysHostname + ':' + shortCwd + sigil + '\u00a0';
  curprompt.textContent = p;
  renderLine();
}

// keyboard

// Render typed buffer into the three DOM spans:
//   #curtyped  = text before cursor
//   #curcursor = block that sits on the character at cursor (blinking)
//   #curafter  = text after cursor
// When masked (password), just show stars and keep cursor at end.
function renderLine() {
  var afterEl = document.getElementById('curafter');
  if (!afterEl) {
    afterEl = document.createElement('span');
    afterEl.id = 'curafter';
    afterEl.style.color = '#e0e0e0';
    curcursor.parentNode.insertBefore(afterEl, curcursor.nextSibling);
  }

  if (masked) {
    curtyped.textContent  = '*'.repeat(typed.length);
    curcursor.textContent = ' ';
    afterEl.textContent   = '';
    return;
  }

  var pos    = Math.min(cursorPos, typed.length);
  var before = typed.slice(0, pos);
  var atCur  = typed.length > pos ? typed[pos] : ' ';   // char under cursor, or space at EOL
  var after  = typed.length > pos ? typed.slice(pos + 1) : '';

  // nbsp trick: flex collapses trailing ASCII space in text nodes
  curtyped.textContent  = before.replace(/ $/, '\u00a0');
  curcursor.textContent = atCur;
  afterEl.textContent   = after;
}


function handleKey(key, ctrlKey, altKey, metaKey) {
  if (mode === 'boot') return;
  if (nanoActive)  { nanoKey(key, ctrlKey); return; }
  if (joeActive)   { joeKey(key, ctrlKey, altKey); return; }
  if (vimActive)   { vimKey(key, ctrlKey, altKey); return; }
  if (mysqlActive) { mysqlKey(key, ctrlKey); return; }
  if (pagerActive) { pagerKey(key); return; }
  if (topActive) return;
  if (htopActive) return;

  // Delegate to reverse-search handler when active
  if (rsearchActive) { rsearchKey(key, ctrlKey); return; }

  // Enter
  if (key === 'Enter') {
    var val   = typed;
    typed     = '';
    cursorPos = 0;
    renderLine();
    handleEnter(val);
    return;
  }

  // Ctrl shortcuts
  if (ctrlKey) {
    if (key === 'a' || key === 'A') { cursorPos = 0;            renderLine(); return; }  // Ctrl+A: start
    if (key === 'e' || key === 'E') { cursorPos = typed.length; renderLine(); return; }  // Ctrl+E: end
    if (key === 'l' || key === 'L') { clearScr(); scr.scrollTop = 0; return; }           // Ctrl+L: clear screen
    if (key === 'r' || key === 'R') {                                                     // Ctrl+R: reverse history search
      if (mode === 'command' && cmdLog.length > 0) {
        rsearchActive = true;
        rsearchQuery  = '';
        rsearchIdx    = 0;
        rsearchMatch  = '';
        rsearchRender();
      }
      return;
    }
    if (key === 'u' || key === 'U') { typed = typed.slice(cursorPos); cursorPos = 0; renderLine(); return; } // Ctrl+U: kill to start
    if (key === 'k' || key === 'K') { typed = typed.slice(0, cursorPos);              renderLine(); return; } // Ctrl+K: kill to end
    if (key === 'w' || key === 'W') {  // Ctrl+W: delete word before cursor
      var i = cursorPos;
      while (i > 0 && typed[i-1] === ' ') i--;
      while (i > 0 && typed[i-1] !== ' ') i--;
      typed = typed.slice(0, i) + typed.slice(cursorPos);
      cursorPos = i;
      renderLine();
      return;
    }
    return; // swallow other Ctrl combos
  }

  // Backspace
  if (key === 'Backspace') {
    if (cursorPos > 0) {
      typed = typed.slice(0, cursorPos - 1) + typed.slice(cursorPos);
      cursorPos--;
      renderLine();
    }
    return;
  }

  // Delete
  if (key === 'Delete') {
    if (cursorPos < typed.length) {
      typed = typed.slice(0, cursorPos) + typed.slice(cursorPos + 1);
      renderLine();
    }
    return;
  }

  // Arrow keys (left / right / up / down)
  if (key === 'ArrowLeft') {
    if (cursorPos > 0) { cursorPos--; renderLine(); }
    return;
  }
  if (key === 'ArrowRight') {
    if (cursorPos < typed.length) { cursorPos++; renderLine(); }
    return;
  }
  if (key === 'Home') { cursorPos = 0;            renderLine(); return; }
  if (key === 'End')  { cursorPos = typed.length; renderLine(); return; }

  if (mode === 'command') {
    if (key === 'ArrowUp') {
      if (histIdx < cmdLog.length - 1) {
        histIdx++;
        typed     = cmdLog[histIdx];
        cursorPos = typed.length;
        renderLine();
      }
      return;
    }
    if (key === 'ArrowDown') {
      if (histIdx > 0) { histIdx--; typed = cmdLog[histIdx]; }
      else             { histIdx = -1; typed = ''; }
      cursorPos = typed.length;
      renderLine();
      return;
    }
  }

  // Tab — path/command completion
  if (key === 'Tab') {
    if (mode !== 'command' || tabBusy) return;
    var lineUpToCursor = typed.slice(0, cursorPos);
    // Split into tokens; the last token is what we complete
    var tokens  = lineUpToCursor.match(/\S+/g) || [];
    var lastTok = tokens.length ? tokens[tokens.length - 1] : '';
    // isCmd: completing first token AND it has no path separator
    var isCmd   = (tokens.length <= 1) && lastTok.indexOf('/') === -1;
    // detect double-Tab (same typed string as last Tab press)
    var dblTab  = (typed === tabLastTyped);
    tabLastTyped = typed;
    tabBusy = true;
    fetch('terminal.php?complete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({prefix: lastTok, cwd: cwd, isCmd: isCmd})
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      tabBusy = false;
      var matches = data.matches || [];
      var isDirs  = data.isDirs  || [];
      if (matches.length === 0) {
        // no match — do nothing (real bash also stays silent)
        return;
      }
      if (matches.length === 1) {
        // single match — complete it
        var completion = matches[0] + (isDirs[0] ? '/' : ' ');
        // replace the last token in typed up to cursor
        var before     = lineUpToCursor.slice(0, lineUpToCursor.length - lastTok.length);
        var after      = typed.slice(cursorPos);
        typed     = before + completion + after;
        cursorPos = before.length + completion.length;
        tabLastTyped = typed; // reset so next Tab is fresh
        renderLine();
      } else {
        // multiple matches
        if (!dblTab) {
          // first Tab: complete the longest common prefix if it extends what we have
          var lcp = matches[0];
          for (var mi = 1; mi < matches.length; mi++) {
            var m = matches[mi];
            var k = 0;
            while (k < lcp.length && k < m.length && lcp[k] === m[k]) k++;
            lcp = lcp.slice(0, k);
          }
          if (lcp.length > lastTok.length) {
            var before2 = lineUpToCursor.slice(0, lineUpToCursor.length - lastTok.length);
            var after2  = typed.slice(cursorPos);
            typed     = before2 + lcp + after2;
            cursorPos = before2.length + lcp.length;
            tabLastTyped = typed;
            renderLine();
          }
          // else: nothing to extend — wait for double-Tab to show list
        } else {
          // double-Tab: show list of matches below current line
          var promptText = curprompt.textContent;
          print(promptText + typed, 'n');
          // display matches in columns (simple: one per line if few, else columns)
          var names = matches.map(function(m, i){ return m + (isDirs[i] ? '/' : ''); });
          var cols  = termCols();
          var maxW  = names.reduce(function(a,b){ return Math.max(a, b.length); }, 0) + 2;
          var numC  = Math.max(1, Math.floor(cols / maxW));
          var rows  = Math.ceil(names.length / numC);
          var lines = [];
          for (var ri = 0; ri < rows; ri++) {
            var parts = [];
            for (var ci = 0; ci < numC; ci++) {
              var idx = ci * rows + ri;
              if (idx >= names.length) break;
              var isLast = (ci === numC - 1) || ((idx + rows) >= names.length);
              parts.push(isLast ? names[idx] : names[idx] + ' '.repeat(Math.max(0, maxW - names[idx].length)));
            }
            lines.push(parts.join(''));
          }
          print(lines.join('\n'), 'n');
          updateTitleAndPrompt();
          renderLine();
          curline.style.display = 'flex';
          scr.scrollTop = scr.scrollHeight;
        }
      }
    })
    .catch(function(){ tabBusy = false; });
    return;
  }

  // Alt+Left / Alt+Right — word-jump (readline Alt+B / Alt+F)
  if (altKey && (key === 'ArrowLeft' || key === 'b' || key === 'B')) {
    var i = cursorPos;
    while (i > 0 && typed[i - 1] === ' ') i--;          // skip spaces
    while (i > 0 && typed[i - 1] !== ' ') i--;          // skip word chars
    cursorPos = i;
    renderLine();
    return;
  }
  if (altKey && (key === 'ArrowRight' || key === 'f' || key === 'F')) {
    var i = cursorPos;
    while (i < typed.length && typed[i] === ' ') i++;   // skip spaces
    while (i < typed.length && typed[i] !== ' ') i++;   // skip word chars
    cursorPos = i;
    renderLine();
    return;
  }

  // Printable character — insert at cursor
  if (key.length === 1 && !altKey && !metaKey) {
    typed = typed.slice(0, cursorPos) + key + typed.slice(cursorPos);
    cursorPos++;
    renderLine();
    scr.scrollTop = scr.scrollHeight;
    return;
  }
}

// native keydown — always active (handles both standalone and focused-iframe)
document.addEventListener('keydown', function(e) {
  if (mode === 'boot' || mode === 'dead') return;

  // When mobile input is focused, it handles everything via its own keydown+input
  // listeners (with stopPropagation). Nothing should reach here from that path.
  // But as a safety net, skip printable characters if mobileActive to avoid
  // double-insertion when a physical keyboard is used on a touch device.
  if (mobileActive && e.key.length === 1 && !e.ctrlKey && !e.metaKey) return;

  // Ctrl+Shift+C — copy selected text to clipboard, or typed line if nothing selected
  if (e.ctrlKey && e.shiftKey && (e.key === 'c' || e.key === 'C')) {
    e.preventDefault();
    var sel = window.getSelection ? window.getSelection().toString() : '';
    var toCopy = sel || typed;
    if (toCopy && navigator.clipboard) {
      navigator.clipboard.writeText(toCopy).catch(function(){});
    }
    return;
  }

  // When joe is active, prevent ALL browser Ctrl shortcuts so they reach joe
  if (joeActive && e.ctrlKey && !e.shiftKey) {
    e.preventDefault();
    handleKey(e.key, e.ctrlKey, e.altKey, e.metaKey);
    return;
  }

  // When vim is active, prevent browser Ctrl shortcuts so they reach vim
  if (vimActive && e.ctrlKey && !e.shiftKey) {
    e.preventDefault();
    handleKey(e.key, e.ctrlKey, e.altKey, e.metaKey);
    return;
  }

  // Ctrl+C — cancel typed line (SIGINT), show ^C like a real terminal
  if (e.ctrlKey && !e.shiftKey && (e.key === 'c' || e.key === 'C')) {
    if (nanoActive) return;  // let nano handle it
    if (pagerActive) { pagerExit(); return; }  // Ctrl+C exits pager
    e.preventDefault();
    if (mode === 'command') {
      var cancelled = typed;
      typed     = '';
      cursorPos = 0;
      renderLine();
      // print the prompt + typed text + ^C as a cancelled line
      var promptText = curprompt.textContent;
      print(promptText + cancelled + '^C', 'n');
      print('', 'n');
      if (mode === 'command') updateTitleAndPrompt();
      curline.style.display = 'flex';
      scr.scrollTop = scr.scrollHeight;
    }
    return;
  }

  // Ctrl+V / Ctrl+Shift+V — let the browser paste event fire naturally
  if (e.ctrlKey && (e.key === 'v' || e.key === 'V')) return;

  e.preventDefault();
  handleKey(e.key, e.ctrlKey, e.altKey, e.metaKey);
});

// paste event — insert clipboard text into the current input or nano buffer
document.addEventListener('paste', function(e) {
  if (mode === 'boot' || mode === 'dead') return;
  var text = (e.clipboardData || window.clipboardData).getData('text');
  if (!text) return;
  e.preventDefault();
  if (nanoActive) {
    // insert pasted text into nano at cursor position
    var lines = text.split('\n');
    var cur = nanoData.lines[nanoData.curRow];
    var before = cur.slice(0, nanoData.curCol);
    var after  = cur.slice(nanoData.curCol);
    if (lines.length === 1) {
      nanoData.lines[nanoData.curRow] = before + lines[0] + after;
      nanoData.curCol += lines[0].length;
    } else {
      nanoData.lines[nanoData.curRow] = before + lines[0];
      for (var i = 1; i < lines.length - 1; i++) {
        nanoData.curRow++;
        nanoData.lines.splice(nanoData.curRow, 0, lines[i]);
      }
      nanoData.curRow++;
      nanoData.lines.splice(nanoData.curRow, 0, lines[lines.length-1] + after);
      nanoData.curCol = lines[lines.length-1].length;
    }
    nanoData.modified = true;
    nanoRender();
  } else if (joeActive) {
    // insert pasted text into joe at cursor position
    var lines = text.split('\n');
    var cur = joeData.lines[joeData.curRow];
    var before = cur.slice(0, joeData.curCol);
    var after  = cur.slice(joeData.curCol);
    if (lines.length === 1) {
      joeData.lines[joeData.curRow] = before + lines[0] + after;
      joeData.curCol += lines[0].length;
    } else {
      joeData.lines[joeData.curRow] = before + lines[0];
      for (var pi = 1; pi < lines.length - 1; pi++) {
        joeData.curRow++;
        joeData.lines.splice(joeData.curRow, 0, lines[pi]);
      }
      joeData.curRow++;
      joeData.lines.splice(joeData.curRow, 0, lines[lines.length-1] + after);
      joeData.curCol = lines[lines.length-1].length;
    }
    joeData.modified = true;
    joeRender();
  } else if (vimActive) {
    // insert pasted text into vim at cursor position (in insert mode only; otherwise ignore)
    if (vimData.mode === 'insert') {
      var lines = text.split('\n');
      var cur = vimData.lines[vimData.curRow];
      var before = cur.slice(0, vimData.curCol);
      var after  = cur.slice(vimData.curCol);
      if (lines.length === 1) {
        vimData.lines[vimData.curRow] = before + lines[0] + after;
        vimData.curCol += lines[0].length;
      } else {
        vimData.lines[vimData.curRow] = before + lines[0];
        for (var vi = 1; vi < lines.length - 1; vi++) {
          vimData.curRow++;
          vimData.lines.splice(vimData.curRow, 0, lines[vi]);
        }
        vimData.curRow++;
        vimData.lines.splice(vimData.curRow, 0, lines[lines.length-1] + after);
        vimData.curCol = lines[lines.length-1].length;
      }
      vimData.modified = true;
      vimRender();
    }
  } else if (mode === 'command') {
    // paste into the command line at cursor position (strip newlines — just take first line)
    var firstLine = text.split('\n')[0];
    typed = typed.slice(0, cursorPos) + firstLine + typed.slice(cursorPos);
    cursorPos += firstLine.length;
    renderLine();
    scr.scrollTop = scr.scrollHeight;
  }
});

// postMessage from parent iframe wrapper — only fires when iframe does NOT have focus
window.addEventListener('message', function(e) {
  // reject messages from any origin other than our own page
  if (e.origin !== window.location.origin) return;
  if (e.data && e.data.type === 'keydown') {
    // if this document has focus, the native keydown above already handled it
    if (document.hasFocus()) return;
    // Ctrl+Shift+C forwarded from outer page — copy selection or typed line
    if (e.data.ctrlKey && e.data.shiftKey && (e.data.key === 'c' || e.data.key === 'C')) {
      var sel = window.getSelection ? window.getSelection().toString() : '';
      var toCopy = sel || typed;
      if (toCopy && navigator.clipboard) {
        navigator.clipboard.writeText(toCopy).catch(function(){});
      }
      return;
    }
    handleKey(e.data.key, e.data.ctrlKey, e.data.altKey, e.data.metaKey);
  }
  // paste forwarded from outer page
  if (e.data && e.data.type === 'paste') {
    if (mode === 'boot' || mode === 'dead') return;
    var text = e.data.text;
    if (!text) return;
    if (nanoActive) {
      var lines = text.split('\n');
      var cur = nanoData.lines[nanoData.curRow];
      var before = cur.slice(0, nanoData.curCol);
      var after  = cur.slice(nanoData.curCol);
      if (lines.length === 1) {
        nanoData.lines[nanoData.curRow] = before + lines[0] + after;
        nanoData.curCol += lines[0].length;
      } else {
        nanoData.lines[nanoData.curRow] = before + lines[0];
        for (var pi = 1; pi < lines.length - 1; pi++) {
          nanoData.curRow++;
          nanoData.lines.splice(nanoData.curRow, 0, lines[pi]);
        }
        nanoData.curRow++;
        nanoData.lines.splice(nanoData.curRow, 0, lines[lines.length-1] + after);
        nanoData.curCol = lines[lines.length-1].length;
      }
      nanoData.modified = true;
      nanoRender();
    } else if (joeActive) {
      var lines = text.split('\n');
      var cur = joeData.lines[joeData.curRow];
      var before = cur.slice(0, joeData.curCol);
      var after  = cur.slice(joeData.curCol);
      if (lines.length === 1) {
        joeData.lines[joeData.curRow] = before + lines[0] + after;
        joeData.curCol += lines[0].length;
      } else {
        joeData.lines[joeData.curRow] = before + lines[0];
        for (var qi = 1; qi < lines.length - 1; qi++) {
          joeData.curRow++;
          joeData.lines.splice(joeData.curRow, 0, lines[qi]);
        }
        joeData.curRow++;
        joeData.lines.splice(joeData.curRow, 0, lines[lines.length-1] + after);
        joeData.curCol = lines[lines.length-1].length;
      }
      joeData.modified = true;
      joeRender();
    } else if (vimActive) {
      // insert pasted text into vim at cursor position (in insert mode only)
      if (vimData.mode === 'insert') {
        var lines = text.split('\n');
        var cur = vimData.lines[vimData.curRow];
        var before = cur.slice(0, vimData.curCol);
        var after  = cur.slice(vimData.curCol);
        if (lines.length === 1) {
          vimData.lines[vimData.curRow] = before + lines[0] + after;
          vimData.curCol += lines[0].length;
        } else {
          vimData.lines[vimData.curRow] = before + lines[0];
          for (var wi = 1; wi < lines.length - 1; wi++) {
            vimData.curRow++;
            vimData.lines.splice(vimData.curRow, 0, lines[wi]);
          }
          vimData.curRow++;
          vimData.lines.splice(vimData.curRow, 0, lines[lines.length-1] + after);
          vimData.curCol = lines[lines.length-1].length;
        }
        vimData.modified = true;
        vimRender();
      }
    } else if (mode === 'command') {
      var firstLine = text.split('\n')[0];
      typed = typed.slice(0, cursorPos) + firstLine + typed.slice(cursorPos);
      cursorPos += firstLine.length;
      renderLine();
      scr.scrollTop = scr.scrollHeight;
    }
  }
});

// enter handler
function handleEnter(val) {
  if (mode === 'command') {
    runCmd(val);
  }
}

// login — auto-login as guest, no prompt needed
function doGuestLogin() {
  clearScr();
  cmdLog    = [];
  histIdx   = -1;
  loginUser = 'guest';
  cwd       = '/home/guest';

  // fetch and display /etc/motd
  fetch('terminal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({cmd: 'cat /etc/motd', user: 'guest', cols: 80})
  })
  .then(r => r.json())
  .then(data => {
    if (data.output && data.output.trim() !== '') {
      print(data.output, 'n');
      print('', 'n');
    }
  })
  .catch(() => {})
  .finally(() => {
    mode      = 'command';
    masked    = false;
    typed     = '';
    cursorPos = 0;
    updateTitleAndPrompt();
    renderLine();
    curline.style.display = 'flex';
  });
}

// shared response dispatcher — used by runCmd and doSudoPrompt
function handleResponse(data) {
  if (data.clear) {
    clearScr();
  } else if (data.rmrf) {
    doRmRf(); return;
  } else if (data.logout) {
    print(data.output, 'n');
    print('', 'n');
    setTimeout(doGuestLogin, 800);
    return;
  } else if (data.ping) {
    doPing(data); return;
  } else if (data.top) {
    doTop(data); return;
  } else if (data.htop) {
    doHtop(data); return;
  } else if (data.fastfetch) {
    var ffEl = document.createElement('span');
    ffEl.className = 'ln n';
    ffEl.style.whiteSpace = 'pre';
    ffEl.style.display = 'block';
    ffEl.innerHTML = data.html;
    scr.insertBefore(ffEl, curline);
  } else if (data.wget) {
    doWget(data); return;
  } else if (data.curl) {
    doCurl(data); return;
  } else if (data.dnf) {
    doDnf(data); return;
  } else if (data.nano) {
    doNano(data); return;
  } else if (data.joe) {
    doJoe(data); return;
  } else if (data.vim) {
    doVim(data); return;
  } else if (data.mysql) {
    doMysql(data); return;
  } else if (data.pager !== undefined) {
    doPager(data); return;
  } else if (data.sudo_prompt) {
    doSudoPrompt(data.sudo_cmd); return;
  } else if (data.su_prompt !== undefined) {
    doSu(data.su_target, data.su_prompt); return;
  } else if (data.passwd_prompt) {
    doPasswd(data.passwd_target); return;
  } else if (data.output !== undefined && data.output !== '') {
    print(data.output, data.error ? 'e' : 'n');
  }

  if (data.cwd) cwd = data.cwd;

  print('', 'n');
  updateTitleAndPrompt();
  renderLine();
  curline.style.display = 'flex';
  scr.scrollTop = scr.scrollHeight;
}

// run command via AJAX
function runCmd(raw) {
  var trimmed = raw.trim();
  var userHome  = (loginUser === 'root') ? '/root' : '/home/' + loginUser;
  var shortCwd  = cwd === userHome ? '~' : cwd.replace(userHome + '/', '~/');
  var sigil     = (loginUser === 'root') ? '#' : '$';
  print(loginUser + '@' + sysHostname + ':' + shortCwd + sigil + ' ' + trimmed, 'n');

  if (!trimmed) { print('','n'); updateTitleAndPrompt(); curline.style.display = 'flex'; return; }

  cmdLog.unshift(trimmed);
  histIdx = -1;

  // expand shell variables and ~ before sending to PHP
  var userHome2 = userHome; // closure-safe alias
  var envPath = (loginUser === 'root')
    ? '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/root/bin'
    : '/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/home/' + loginUser + '/.local/bin';
  var expanded = trimmed
    .replace(/\$\{?HOME\}?/g,     userHome2)
    .replace(/\$\{?USER\}?/g,     loginUser)
    .replace(/\$\{?LOGNAME\}?/g,  loginUser)
    .replace(/\$\{?PWD\}?/g,      cwd)
    .replace(/\$\{?OLDPWD\}?/g,   cwd)
    .replace(/\$\{?HOSTNAME\}?/g, sysHostname)
    .replace(/\$\{?PATH\}?/g,     envPath)
    .replace(/\$\{?SHELL\}?/g,    '/bin/bash')
    .replace(/\$\{?TERM\}?/g,     'xterm-256color')
    .replace(/\$\?/g,             '0')
    .replace(/(^|\s)~\//g,        '$1' + userHome2 + '/')
    .replace(/(^|\s)~$/g,         '$1' + userHome2);

  hidePrompt();

  // pipe support: split on | and run segments sequentially
  // Quoted strings are respected — only split on unquoted pipes
  var segments = (function(cmd) {
    var parts = []; var cur = ''; var inSingle = false; var inDouble = false;
    for (var ci = 0; ci < cmd.length; ci++) {
      var ch = cmd[ci];
      if (ch === "'" && !inDouble) { inSingle = !inSingle; cur += ch; }
      else if (ch === '"' && !inSingle) { inDouble = !inDouble; cur += ch; }
      else if (ch === '|' && !inSingle && !inDouble) { parts.push(cur.trim()); cur = ''; }
      else { cur += ch; }
    }
    parts.push(cur.trim());
    return parts.filter(function(p){ return p !== ''; });
  })(expanded);

  if (segments.length === 1) {
    // no pipe — normal single request
    fetch('terminal.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ cmd: expanded, user: loginUser, cols: termCols() })
    })
    .then(function(r){ return r.json(); })
    .then(function(data) { handleResponse(data); })
    .catch(function() {
      print('bash: connection error', 'e');
      print('', 'n');
      updateTitleAndPrompt();
      renderLine();
      curline.style.display = 'flex';
    });
  } else {
    // pipe chain — run segments sequentially, passing output as stdin
    var pipeStdin = null;
    var segIdx = 0;
    function runSegment() {
      if (segIdx >= segments.length) { return; }
      var seg = segments[segIdx];
      var isLast = (segIdx === segments.length - 1);
      segIdx++;
      var payload = { cmd: seg, user: loginUser, cols: termCols() };
      if (pipeStdin !== null) payload.stdin = pipeStdin;
      fetch('terminal.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
      })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (isLast) {
          handleResponse(data);
        } else {
          // intermediate: capture output as stdin for next segment
          // only plain output can be piped; special responses (fastfetch, dnf, etc.) terminate the pipe
          if (data.output !== undefined && !data.error && !data.fastfetch && !data.sudo_prompt) {
            pipeStdin = data.output;
            if (data.cwd) cwd = data.cwd;
            runSegment();
          } else {
            // something special or an error — just display it and stop
            handleResponse(data);
          }
        }
      })
      .catch(function() {
        print('bash: connection error', 'e');
        print('', 'n');
        updateTitleAndPrompt();
        renderLine();
        curline.style.display = 'flex';
      });
    }
    runSegment();
  }
}

// ping animation

// boot sequence
function boot() {
  hidePrompt();
  mode = 'boot';

  fetch('terminal.php?sysinfo')
    .then(function(r){ return r.json(); })
    .then(function(info) {
      sysKernel   = info.kernel   || sysKernel;
      sysArch     = info.arch     || sysArch;
      sysOS       = info.os       || sysOS;
      sysHostname = info.hostname || sysHostname;
    })
    .catch(function(){})  // keep defaults on failure
    .finally(function() {
      var BOOT = [
        'BIOS v2.41 ... OK',
        'Detecting hardware ...',
        '  CPU: Intel(R) Xeon(R) E5-2670 @ 2.60GHz x 8',
        '  RAM: 16384 MB  DISK: /dev/sda 500GB OK',
        'Loading kernel ' + sysKernel + ' ...',
        'Starting udev ... OK',
        'Mounting filesystems ... OK',
        'Starting network interfaces ... OK',
        'Starting SSH daemon ... OK',
        'Starting cron daemon ... OK',
        'System initialised.',
        '',
        sysOS,
        'Kernel ' + sysKernel + ' on an ' + sysArch,
        ''
      ];
      var i = 0;
      function next() {
        if (i < BOOT.length) { print(BOOT[i], 'd'); i++; setTimeout(next, 60); }
        else { doGuestLogin(); }
      }
      next();
    });
}

boot();
</script>
</body>
</html>
