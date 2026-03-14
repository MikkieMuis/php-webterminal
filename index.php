<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terminal</title>
<style>
/* ── reset ───────────────────────────────────────────────── */
* { margin:0; padding:0; box-sizing:border-box; }

/* ── page fills whatever container embeds it ─────────────── */
html, body {
  width:100%; height:100%;
  background:#0a0a0a;
  font-family:'Courier New', Courier, monospace;
  overflow:hidden;
}

/* ── terminal frame ──────────────────────────────────────── */
#terminal {
  width:100%; height:100%;
  display:flex;
  flex-direction:column;
}



/* ── screen area ─────────────────────────────────────────── */
#screen {
  flex:1;
  overflow-y:auto;
  padding:14px 16px;
  background:#0a0a0a;
  font-size:15px;
  user-select:text;
  -webkit-user-select:text;
  line-height:1.6;
  color:#39ff14;
  cursor:text;
}
#screen::-webkit-scrollbar { width:6px; }
#screen::-webkit-scrollbar-track { background:#0a0a0a; }
#screen::-webkit-scrollbar-thumb { background:#1a5c1a; border-radius:3px; }

/* ── output lines ────────────────────────────────────────── */
.ln {
  display:block;
  white-space:pre-wrap;
  word-break:break-word;
  font-family:'Courier New', Courier, monospace;
}
.n { color:#39ff14; }
.d { color:#00aa00; }
.b { color:#aaff00; }
.e { color:#ff4444; }
.w { color:#ffaa00; }

/* ── input line ──────────────────────────────────────────── */
#curline {
  display:flex;
  align-items:center;
  font-family:'Courier New', Courier, monospace;
  font-size:15px;
  color:#39ff14;
  margin-top:2px;
  gap:0.5ch;
}
#curprompt  { white-space:nowrap; color:#39ff14; }
#curtyped   { color:#39ff14; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
#curcursor  {
  display:inline-block;
  width:0.6ch; height:1.1em;
  background:#39ff14;
  animation:blink 1s infinite;
}

/* ── nano overlay ────────────────────────────────────────── */
#nano-overlay {
  position:absolute; top:0; left:0; width:100%; height:100%;
  background:#0a0a0a; color:#39ff14;
  font-family:'Courier New',Courier,monospace; font-size:13px;
  display:flex; flex-direction:column;
  z-index:100; overflow:hidden;
}
#nano-titlebar {
  background:#39ff14; color:#0a0a0a;
  padding:2px 4px; text-align:center;
  font-weight:bold; flex-shrink:0;
  white-space:nowrap; overflow:hidden;
}
#nano-content {
  flex:1; overflow:hidden; position:relative;
  padding:2px 4px;
  white-space:pre; font-family:'Courier New',Courier,monospace;
}
#nano-status {
  background:#0a0a0a; color:#39ff14;
  padding:1px 4px; min-height:1.4em; flex-shrink:0;
  font-size:13px;
}
#nano-shortcuts {
  flex-shrink:0;
}
.nano-shortcut-row {
  display:flex; flex-wrap:wrap;
  background:#39ff14; color:#0a0a0a;
  font-size:12px; padding:1px 2px;
}
.nano-sc {
  display:inline-flex; margin-right:8px; white-space:nowrap;
}
.nano-sc-key {
  background:#0a0a0a; color:#39ff14;
  padding:0 3px; margin-right:2px;
}
#nano-search-bar {
  background:#0a0a0a; color:#39ff14;
  padding:1px 4px; flex-shrink:0;
  display:none;
}
</style>
</head>
<body>
<div id="terminal">

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
</div>

<script>
// ── DOM refs ──────────────────────────────────────────────
var scr       = document.getElementById('screen');
var curline   = document.getElementById('curline');
var curprompt = document.getElementById('curprompt');
var curtyped  = document.getElementById('curtyped');
var curcursor = document.getElementById('curcursor');


// ── system info (populated before boot) ───────────────────
var sysKernel   = '5.14.0-1-default';
var sysArch     = 'x86_64';
var sysOS       = 'Linux';
var sysHostname = 'localhost';

// ── terminal column count (measured from actual screen width) ─
function termCols() {
  var testEl = document.createElement('span');
  testEl.style.cssText = 'visibility:hidden;position:absolute;white-space:pre;font-family:"Courier New",Courier,monospace;font-size:15px;';
  testEl.textContent = 'X';
  document.body.appendChild(testEl);
  var charW = testEl.getBoundingClientRect().width;
  document.body.removeChild(testEl);
  var screenW = document.getElementById('screen').getBoundingClientRect().width;
  return charW > 0 ? Math.floor(screenW / charW) : 80;
}

// ── state ─────────────────────────────────────────────────
var mode      = 'boot';   // boot | username | password | command
var typed     = '';
var cursorPos = 0;        // insertion point within typed (0 = before first char)
var masked    = false;
var loginUser = '';
var cwd       = '/root';
var cmdLog    = [];
var histIdx   = -1;

// ── output helpers ────────────────────────────────────────
function print(text, cls) {
  var s = document.createElement('span');
  s.className = 'ln ' + (cls || 'n');
  s.textContent = text;
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
  var shortCwd = cwd.replace('/root', '~');
  var p = loginUser + '@' + sysHostname + ':' + shortCwd + '#';
  curprompt.textContent = p;
  renderLine();
}

// ── keyboard ──────────────────────────────────────────────

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
    afterEl.style.color = '#39ff14';
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
  if (nanoActive) { nanoKey(key, ctrlKey); return; }
  if (pagerActive) { pagerKey(key); return; }
  if (topActive) return;

  // ── Enter ──────────────────────────────────────────────
  if (key === 'Enter') {
    var val   = typed;
    typed     = '';
    cursorPos = 0;
    renderLine();
    handleEnter(val);
    return;
  }

  // ── Ctrl shortcuts ─────────────────────────────────────
  if (ctrlKey) {
    if (key === 'a' || key === 'A') { cursorPos = 0;            renderLine(); return; }  // Ctrl+A: start
    if (key === 'e' || key === 'E') { cursorPos = typed.length; renderLine(); return; }  // Ctrl+E: end
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

  // ── Backspace ──────────────────────────────────────────
  if (key === 'Backspace') {
    if (cursorPos > 0) {
      typed = typed.slice(0, cursorPos - 1) + typed.slice(cursorPos);
      cursorPos--;
      renderLine();
    }
    return;
  }

  // ── Delete ─────────────────────────────────────────────
  if (key === 'Delete') {
    if (cursorPos < typed.length) {
      typed = typed.slice(0, cursorPos) + typed.slice(cursorPos + 1);
      renderLine();
    }
    return;
  }

  // ── Arrow keys (left / right / up / down) ──────────────
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

  // ── Printable character — insert at cursor ──────────────
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

  // Ctrl+C — cancel typed line (SIGINT), show ^C like a real terminal
  if (e.ctrlKey && !e.shiftKey && (e.key === 'c' || e.key === 'C')) {
    if (nanoActive) return;  // let nano handle it
    if (pagerActive) { pagerExit(); return; }  // Ctrl+C exits pager
    e.preventDefault();
    if (mode === 'command' || mode === 'username' || mode === 'password') {
      var cancelled = typed;
      typed     = '';
      cursorPos = 0;
      renderLine();
      // print the prompt + typed text + ^C as a cancelled line
      var promptText = curprompt.textContent;
      print(promptText + ' ' + cancelled + '^C', 'n');
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
  } else if (mode === 'command' || mode === 'username' || mode === 'password') {
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
    } else if (mode === 'command' || mode === 'username' || mode === 'password') {
      var firstLine = text.split('\n')[0];
      typed = typed.slice(0, cursorPos) + firstLine + typed.slice(cursorPos);
      cursorPos += firstLine.length;
      renderLine();
      scr.scrollTop = scr.scrollHeight;
    }
  }
});

// ── enter handler ─────────────────────────────────────────
function handleEnter(val) {
  if (mode === 'username') {
    loginUser = val.trim() || 'user';
    print('login: ' + loginUser, 'n');
    mode = 'password';
    setPrompt('Password:', true);

  } else if (mode === 'password') {
    print('Password: ', 'n');
    if (val.length > 8) {
      doLoginSuccess();
    } else {
      print('Login incorrect', 'e');
      print('', 'n');
      mode = 'username';
      setPrompt('login:', false);
    }

  } else if (mode === 'command') {
    runCmd(val);
  }
}

// ── login ─────────────────────────────────────────────────
function startLogin() {
  clearScr();
  cmdLog  = [];
  histIdx = -1;
  cwd     = '/root';
  mode    = 'username';
  print(sysOS, 'b');
  print('Kernel ' + sysKernel + ' on an ' + sysArch, 'd');
  print('', 'n');
    setPrompt('login:', false);
}

function doLoginSuccess() {
  print('', 'n');
  print('Last login: ' + new Date(Date.now() - 86400000).toString().slice(0,24) + ' from 192.168.1.42', 'd');
  print('', 'n');
  mode      = 'command';
  masked    = false;
  cwd       = '/root';
  typed     = '';
  cursorPos = 0;
  updateTitleAndPrompt();
  renderLine();
  curline.style.display = 'flex';
}

// ── run command via AJAX ──────────────────────────────────
function runCmd(raw) {
  var trimmed = raw.trim();
  print(loginUser + '@' + sysHostname + ':' + cwd.replace('/root','~') + '# ' + trimmed, 'n');

  if (!trimmed) { print('','n'); updateTitleAndPrompt(); curline.style.display = 'flex'; return; }

  cmdLog.unshift(trimmed);
  histIdx = -1;

  hidePrompt();

  fetch('terminal.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ cmd: trimmed, user: loginUser, cols: termCols() })
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (data.clear) {
      clearScr();
    } else if (data.rmrf) {
      doRmRf(); return;
    } else if (data.logout) {
      print(data.output, 'n');
      print('', 'n');
      setTimeout(startLogin, 800);
      return;
    } else if (data.ping) {
      doPing(data); return;
    } else if (data.top) {
      doTop(data); return;
    } else if (data.wget) {
      doWget(data); return;
    } else if (data.curl) {
      doCurl(data); return;
    } else if (data.nano) {
      doNano(data); return;
    } else if (data.pager !== undefined) {
      doPager(data); return;
    } else if (data.sudo_prompt) {
      doSudoPrompt(data.sudo_cmd); return;
    } else if (data.output !== undefined && data.output !== '') {
      print(data.output, data.error ? 'e' : 'n');
    }

    if (data.cwd) {
      cwd = data.cwd;
    }

    print('', 'n');
    updateTitleAndPrompt();
    renderLine();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  })
  .catch(function() {
    print('bash: connection error', 'e');
    print('', 'n');
    updateTitleAndPrompt();
    renderLine();
    curline.style.display = 'flex';
  });
}

// ── easter egg ────────────────────────────────────────────
function doRmRf() {
  hidePrompt();
  var files = [
    // ── core binaries ──
    '/bin/bash','/bin/sh','/bin/ls','/bin/cp','/bin/mv','/bin/rm',
    '/bin/cat','/bin/chmod','/bin/chown','/bin/kill','/bin/ps',
    '/usr/bin/python3','/usr/bin/perl','/usr/bin/php',
    '/usr/bin/wget','/usr/bin/curl','/usr/bin/ssh','/usr/bin/scp',
    '/usr/sbin/apache2','/usr/sbin/nginx','/usr/sbin/sshd',
    '/usr/sbin/cron','/usr/sbin/rsyslogd',

    // ── boot & kernel ──
    '/boot/vmlinuz-' + sysKernel,
    '/boot/initrd.img-' + sysKernel,
    '/boot/grub/grub.cfg',
    '/boot/grub/i386-pc/core.img',

    // ── system config ──
    '/etc/passwd','/etc/shadow','/etc/group','/etc/sudoers',
    '/etc/hosts','/etc/hostname','/etc/fstab','/etc/crontab',
    '/etc/ssh/sshd_config','/etc/ssh/ssh_host_rsa_key',
    '/etc/ssl/private/server.key','/etc/ssl/certs/server.crt',
    '/etc/apache2/apache2.conf','/etc/nginx/nginx.conf',
    '/etc/my.cnf','/etc/php.ini',

    // ── disk 1 — web & app data (/dev/sda) ──
    '/var/www/html/index.php','/var/www/html/wp-config.php',
    '/var/www/html/wp-content/uploads/2025/01/backup.tar.gz',
    '/var/www/html/app/config/database.yml',
    '/var/www/html/app/config/secrets.yml',
    '/var/log/apache2/access.log','/var/log/apache2/error.log',
    '/var/log/nginx/access.log','/var/log/auth.log',
    '/var/log/syslog','/var/log/kern.log',
    '/var/spool/cron/crontabs/root',

    // ── disk 2 — database (/dev/sdb) ──
    '/mnt/db/mysql/ibdata1','/mnt/db/mysql/ib_logfile0',
    '/mnt/db/mysql/ib_logfile1',
    '/mnt/db/mysql/production/users.ibd',
    '/mnt/db/mysql/production/orders.ibd',
    '/mnt/db/mysql/production/payments.ibd',
    '/mnt/db/mysql/production/sessions.ibd',
    '/mnt/db/mysql/binlog.000042','/mnt/db/mysql/binlog.000043',
    '/mnt/db/postgres/base/16384/PG_VERSION',
    '/mnt/db/postgres/global/pg_control',
    '/mnt/db/redis/dump.rdb',

    // ── disk 3 — backups (/dev/sdc) ──
    '/mnt/backup/daily/db-2026-03-09.sql.gz',
    '/mnt/backup/daily/db-2026-03-08.sql.gz',
    '/mnt/backup/daily/db-2026-03-07.sql.gz',
    '/mnt/backup/weekly/full-2026-03-01.tar.gz',
    '/mnt/backup/weekly/full-2026-02-22.tar.gz',
    '/mnt/backup/config/etc-2026-03-09.tar.gz',
    '/mnt/backup/offsite/.credentials',

    // ── disk 4 — user data & home (/dev/sdd) ──
    '/home/deploy/.ssh/authorized_keys',
    '/home/deploy/.ssh/id_rsa',
    '/home/deploy/.bash_history',
    '/root/.ssh/authorized_keys','/root/.ssh/id_rsa',
    '/root/.bash_history','/root/.aws/credentials',
    '/root/.docker/config.json',

    // ── systemd & init ──
    '/lib/systemd/systemd',
    '/lib/systemd/system/apache2.service',
    '/lib/systemd/system/mysql.service',
    '/lib/systemd/system/sshd.service',
    '/lib/systemd/system/cron.service',
  ];

  var i = 0;
  function next() {
    if (i >= files.length) {
      print('', 'n');
      print("rm: cannot remove '/proc/kcore': Operation not permitted", 'w');
      print("rm: cannot remove '/proc/sysrq-trigger': Operation not permitted", 'w');
      print("rm: cannot remove '/sys/kernel/security': Operation not permitted", 'w');
      print('', 'n');
      print('*** KERNEL PANIC - not syncing: Attempted to kill init! ***', 'e');
      print('*** CPU: 0 PID: 1 Comm: systemd Tainted: G D ' + sysKernel + ' ***', 'e');
      print('*** Call Trace: panic+0x15c/0x328 ***', 'e');
      print('*** System is going down NOW! ***', 'e');
      print('*** Sending SIGTERM to all processes ***', 'e');
      print('*** Sending SIGKILL to all processes ***', 'e');
      // terminal is dead — hide prompt, lock all input, never recover
      setTimeout(function() {
        hidePrompt();
        mode = 'dead';
      }, 800);
      return;
    }
    print("removed '" + files[i] + "'", 'e');
    i++;
    // vary speed slightly for realism
    setTimeout(next, 40 + Math.floor(Math.random() * 60));
  }
  next();
}

// ── sudo password prompt ──────────────────────────────────
function doSudoPrompt(sudoCmd) {
  // temporarily switch to a masked password prompt
  var prevMode = mode;
  mode = 'sudo_password';
  setPrompt('[sudo] password for ' + loginUser + ':', true);

  // hijack handleEnter for one cycle
  var _origEnter = handleEnter;
  handleEnter = function(val) {
    // restore immediately
    handleEnter = _origEnter;
    print('[sudo] password for ' + loginUser + ': ', 'n');

    if (val.length > 8) {
      // password accepted — re-run the inner command as root
      mode = 'command';
      masked = false;
      fetch('terminal.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cmd: sudoCmd, user: 'root', cols: termCols() })
      })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (data.rmrf) {
          doRmRf(); return;
        }
        if (data.output !== undefined && data.output !== '') {
          print(data.output, data.error ? 'e' : 'n');
        }
        if (data.cwd) cwd = data.cwd;
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
        scr.scrollTop = scr.scrollHeight;
      })
      .catch(function() {
        print('sudo: connection error', 'e');
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
      });
    } else {
      // wrong password
      print('sudo: 1 incorrect password attempt', 'e');
      print('', 'n');
      mode = 'command';
      masked = false;
      updateTitleAndPrompt();
      curline.style.display = 'flex';
    }
  };
}

// ── ping animation ────────────────────────────────────────
function doPing(data) {
  print(data.header, 'n');
  var i = 0;
  function nextPacket() {
    if (i >= data.packets.length) {
      print('', 'n');
      data.summary.split('\n').forEach(function(l){ print(l, 'd'); });
      print('', 'n');
      updateTitleAndPrompt();
      curline.style.display = 'flex';
      scr.scrollTop = scr.scrollHeight;
      return;
    }
    print(data.packets[i], 'n');
    i++;
    setTimeout(nextPacket, 700);
  }
  nextPacket();
}

// ── top overlay ───────────────────────────────────────────
var topInterval = null;
var topEl = null;
var topActive = false;

function doTop(data) {
  // build full-screen overlay
  topActive = true;
  topEl = document.createElement('div');
  topEl.id = 'top-overlay';
  topEl.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:#0a0a0a;color:#39ff14;font-family:"Courier New",Courier,monospace;font-size:13px;padding:10px;box-sizing:border-box;overflow:hidden;z-index:100;white-space:pre;';
  document.getElementById('terminal').appendChild(topEl);
  hidePrompt();

  var tick = 0;
  function render() {
    var load = data.load.map(function(l){ return (l + (Math.random()*0.05-0.025)).toFixed(2); });
    var now  = new Date();
    var hh   = String(now.getHours()).padStart(2,'0');
    var mm   = String(now.getMinutes()).padStart(2,'0');
    var ss   = String(now.getSeconds()).padStart(2,'0');

    var header =
      'top - ' + hh+':'+mm+':'+ss + ' up ' + data.uptime + ',  1 user,  load average: ' + load.join(', ') + '\n' +
      'Tasks:  ' + data.procs.length + ' total,   1 running,  ' + (data.procs.length-1) + ' sleeping,   0 stopped,   0 zombie\n' +
      '%Cpu(s):  ' + (load[0]*10).toFixed(1) + ' us,  0.3 sy,  0.0 ni, ' + (100-load[0]*10-0.3).toFixed(1) + ' id,  0.1 wa,  0.0 hi,  0.0 si\n' +
      'MiB Mem :  15872.0 total,   8601.4 free,   3276.8 used,   3993.8 buff/cache\n' +
      'MiB Swap:   2048.0 total,   2048.0 free,      0.0 used.  11534.3 avail Mem\n' +
      '\n' +
      '  PID USER      PR  NI    VIRT    RES    SHR S  %CPU  %MEM     TIME+ COMMAND\n';

    var rows = data.procs.map(function(p) {
      var cpu = (p.cpu + (Math.random()*0.2-0.1)).toFixed(1);
      return String(p.pid).padStart(5) + ' ' +
        p.user.padEnd(9) + ' ' +
        String(p.pr).padStart(2) + '  ' +
        String(p.ni).padStart(2) + ' ' +
        String(p.virt).padStart(7) + ' ' +
        String(p.res).padStart(6) + ' ' +
        String(p.shr).padStart(6) + ' ' +
        p.s + ' ' +
        String(cpu).padStart(5) + ' ' +
        String(p.mem.toFixed(1)).padStart(5) + ' ' +
        p.time.padStart(9) + ' ' +
        p.cmd;
    });

    topEl.textContent = header + rows.join('\n') + '\n\n(Press q to quit)';
    tick++;
  }

  render();
  topInterval = setInterval(render, 2000);

  // listen for 'q' to quit
  function topKey(e) {
    var key = (e.data && e.data.type === 'keydown') ? e.data.key : null;
    if (key === 'q') { exitTop(); }
  }
  function topKeyNative(e) {
    if (e.key === 'q') { e.preventDefault(); exitTop(); }
  }
  function exitTop() {
    topActive = false;
    clearInterval(topInterval); topInterval = null;
    if (topEl) { topEl.remove(); topEl = null; }
    window.removeEventListener('message', topKey);
    if (window.self === window.top) document.removeEventListener('keydown', topKeyNative);
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  }
  window.addEventListener('message', topKey);
  if (window.self === window.top) document.addEventListener('keydown', topKeyNative);
}

// ── wget animation ────────────────────────────────────────
function doWget(data) {
  var date = new Date().toString().slice(0,24);
  print('--' + date + '--  ' + data.url, 'n');
  print('Resolving ' + data.host + '... done.', 'd');
  print('Connecting to ' + data.host + '|resolved|:443... connected.', 'd');
  print('HTTP request sent, awaiting response... 200 OK', 'd');
  print('Length: ' + data.size + ' (' + (data.size/1024).toFixed(1) + 'K) [application/octet-stream]', 'n');
  print("Saving to: '" + data.file + "'", 'n');
  print('', 'n');

  var steps = 20;
  var step  = 0;
  function nextBar() {
    step++;
    var pct   = Math.round((step / steps) * 100);
    var done  = Math.round((step / steps) * 20);
    var bar   = '='.repeat(done) + (done < 20 ? '>' : '') + ' '.repeat(Math.max(0, 19-done));
    var kbs   = (data.size/1024 * step/steps).toFixed(0) + 'K';
    var speed = (100 + Math.floor(Math.random()*400)) + 'KB/s';
    var eta   = step < steps ? 'eta ' + (steps-step) + 's' : '    ';
    // overwrite last progress line by removing it
    var last = scr.querySelector('.ln.progress');
    if (last) last.remove();
    var s = document.createElement('span');
    s.className = 'ln n progress';
    s.textContent = data.file.slice(0,15).padEnd(15) + ' [' + bar + '] ' +
      String(pct).padStart(3) + '% ' + kbs.padStart(6) + ' ' + speed.padStart(9) + ' ' + eta;
    scr.insertBefore(s, curline);
    scr.scrollTop = scr.scrollHeight;
    if (step < steps) { setTimeout(nextBar, 80); return; }
    print('', 'n');
    print("'" + data.file + "' saved [" + data.size + '/' + data.size + ']', 'n');
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  }
  nextBar();
}

// ── curl animation ────────────────────────────────────────
function doCurl(data) {
  if (data.silent && !data.file) {
    // silent + no -o: print fake body
    print('{"status":"ok","message":"pong"}', 'n');
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    return;
  }
  if (!data.file) {
    // no -o/-O: dump fake response body
    print('  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current', 'd');
    print('                                 Dload  Upload   Total   Spent    Left  Speed', 'd');
    print('100  ' + data.size + '  100  ' + data.size + '    0     0   ' + Math.floor(data.size/0.8) + '      0 --:--:-- --:--:-- --:--:-- ' + Math.floor(data.size/0.8), 'd');
    print('', 'n');
    print('{"status":"ok","host":"' + data.host + '","time":"' + new Date().toISOString() + '"}', 'n');
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    return;
  }
  // -o or -O: show progress bar like wget
  print('  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current', 'd');
  print('                                 Dload  Upload   Total   Spent    Left  Speed', 'd');
  var steps = 10; var step = 0;
  function nextBar() {
    step++;
    var pct = Math.round((step/steps)*100);
    var speed = (200 + Math.floor(Math.random()*800)) + 'k';
    var last = scr.querySelector('.ln.cprogress');
    if (last) last.remove();
    var s = document.createElement('span');
    s.className = 'ln d cprogress';
    s.textContent = String(pct).padStart(3) + ' ' + String(data.size).padStart(6) +
      '  ' + String(pct).padStart(3) + ' ' + String(Math.round(data.size*pct/100)).padStart(6) +
      '    0     0  ' + speed.padStart(6) + '      0 --:--:-- --:--:-- --:--:-- ' + speed;
    scr.insertBefore(s, curline);
    scr.scrollTop = scr.scrollHeight;
    if (step < steps) { setTimeout(nextBar, 100); return; }
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  }
  nextBar();
}

// ── nano editor ───────────────────────────────────────────
var nanoActive = false;
var nanoData   = { path:'', filename:'', lines:[], curRow:0, curCol:0, modified:false, cutBuffer:'' };
var nanoMode   = 'edit';   // edit | confirm_exit | search | writename

// ── pager (more / less) ───────────────────────────────────
var pagerActive = false;
var pagerLines  = [];
var pagerTop    = 0;
var pagerCmd    = 'more';
var pagerFile   = '';
var pagerEl     = null;

function doPager(data) {
  pagerActive = true;
  pagerCmd    = data.pagercmd || 'more';
  pagerFile   = data.filename || '';
  pagerLines  = (data.pager || '').split('\n');
  pagerTop    = 0;
  mode        = 'pager';
  if (pagerEl) { pagerEl.remove(); pagerEl = null; }
  pagerEl = document.createElement('div');
  pagerEl.id = 'pager-status';
  pagerEl.style.cssText = 'position:sticky;bottom:0;background:#0a0a0a;color:#39ff14;padding:0 4px;';
  scr.appendChild(pagerEl);
  curline.style.display = 'none';
  pagerRender();
}

function pagerPageHeight() {
  return Math.max(5, Math.floor(scr.clientHeight / parseFloat(getComputedStyle(scr).lineHeight || 22)) - 1);
}

function pagerRender() {
  scr.querySelectorAll('.pager-line').forEach(function(el){ el.remove(); });
  var ph = pagerPageHeight(), total = pagerLines.length;
  var end = Math.min(pagerTop + ph, total);
  var frag = document.createDocumentFragment();
  for (var i = pagerTop; i < end; i++) {
    var div = document.createElement('div');
    div.className = 'pager-line';
    div.textContent = pagerLines[i];
    frag.appendChild(div);
  }
  scr.insertBefore(frag, pagerEl);
  var pct = total === 0 ? 100 : Math.round((end / total) * 100);
  if (pagerCmd === 'less') {
    pagerEl.textContent = end >= total ? '(END)  -- press q to quit --' : pagerFile + ' ' + pct + '%';
  } else {
    pagerEl.textContent = end >= total ? '(END)' : '--More--(' + pct + '%)';
  }
  scr.scrollTop = scr.scrollHeight;
}

function pagerKey(key) {
  var ph = pagerPageHeight(), total = pagerLines.length;
  var atEnd = (pagerTop + ph) >= total;
  if (key === 'q' || key === 'Q') { pagerExit(); return; }
  if (key === ' ' || key === 'f' || key === 'PageDown') {
    if (atEnd) { pagerExit(); return; }
    pagerTop = Math.min(pagerTop + ph, total - 1); pagerRender(); return;
  }
  if (key === 'Enter' || key === 'e' || key === 'ArrowDown') {
    if (atEnd) { if (pagerCmd === 'more') pagerExit(); return; }
    pagerTop = Math.min(pagerTop + 1, total - 1); pagerRender(); return;
  }
  if (pagerCmd === 'less') {
    if (key === 'b' || key === 'PageUp')  { pagerTop = Math.max(0, pagerTop - ph); pagerRender(); return; }
    if (key === 'y' || key === 'ArrowUp') { pagerTop = Math.max(0, pagerTop - 1);  pagerRender(); return; }
    if (key === 'g' || key === 'Home')    { pagerTop = 0;                           pagerRender(); return; }
    if (key === 'G' || key === 'End')     { pagerTop = Math.max(0, total - ph);     pagerRender(); return; }
  }
}

function pagerExit() {
  pagerActive = false;
  mode = 'command';
  if (pagerEl) { pagerEl.remove(); pagerEl = null; }
  scr.querySelectorAll('.pager-line').forEach(function(el){ el.remove(); });
  print('', 'n');
  updateTitleAndPrompt();
  renderLine();
  curline.style.display = 'flex';
  scr.scrollTop = scr.scrollHeight;
}

function doNano(data) {
  nanoMode   = 'edit';
  nanoData.path     = data.path;
  nanoData.filename = data.filename;
  nanoData.lines    = data.content.split('\n');
  nanoData.curRow   = 0;
  nanoData.curCol   = 0;
  nanoData.modified = false;
  nanoData.cutBuffer = '';
  nanoData.isnew    = data.isnew;

  nanoActive = true;
  document.getElementById('nano-overlay').style.display = 'flex';
  hidePrompt();
  nanoRender();
}

function nanoRender() {
  // title bar
  var modified = nanoData.modified ? ' Modified' : '';
  document.getElementById('nano-titlebar').textContent =
    'GNU nano 5.6.1          ' + nanoData.filename + modified;

  // content — show lines, insert block cursor
  var contentEl = document.getElementById('nano-content');
  var html = '';
  for (var r = 0; r < nanoData.lines.length; r++) {
    var line = nanoData.lines[r];
    if (r === nanoData.curRow) {
      var col = Math.min(nanoData.curCol, line.length);
      var before = escHtml(line.slice(0, col));
      var cursor = escHtml(col < line.length ? line[col] : ' ');
      var after  = escHtml(line.slice(col + 1));
      html += before + '<span style="background:#39ff14;color:#0a0a0a;">' + cursor + '</span>' + after + '\n';
    } else {
      html += escHtml(line) + '\n';
    }
  }
  contentEl.innerHTML = html;

  // status bar
  var statusEl = document.getElementById('nano-status');
  if (nanoMode === 'confirm_exit') {
    statusEl.textContent = 'Save modified buffer? (Answering "No" will DISCARD changes.)  Y/N/?';
  } else if (nanoMode === 'writename') {
    statusEl.textContent = 'File Name to Write: ' + nanoData.writeNameTyped;
  } else if (nanoMode === 'search') {
    statusEl.textContent = 'Search: ' + nanoData.searchTyped;
  } else {
    statusEl.textContent = '';
  }
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function nanoKey(key, ctrlKey) {
  if (!nanoActive) return;

  // ── confirm exit mode ──
  if (nanoMode === 'confirm_exit') {
    if (key === 'y' || key === 'Y') {
      nanoMode = 'writename';
      nanoData.writeNameTyped = nanoData.filename;
      nanoData._exitAfterSave = true;
      nanoRender();
    } else if (key === 'n' || key === 'N') {
      nanoClose(false);
    } else if (key === 'Escape' || (ctrlKey && key === 'c') || key === '?') {
      nanoMode = 'edit';
      nanoRender();
    }
    return;
  }

  // ── write name mode (Ctrl+O or confirm save) ──
  if (nanoMode === 'writename') {
    if (key === 'Enter') {
      nanoSave(nanoData.writeNameTyped);
    } else if (key === 'Escape') {
      nanoMode = 'edit';
      nanoRender();
    } else if (key === 'Backspace') {
      nanoData.writeNameTyped = nanoData.writeNameTyped.slice(0,-1);
      nanoRender();
    } else if (key.length === 1 && !ctrlKey) {
      nanoData.writeNameTyped += key;
      nanoRender();
    }
    return;
  }

  // ── search mode ──
  if (nanoMode === 'search') {
    if (key === 'Enter') {
      nanoDoSearch(nanoData.searchTyped);
      nanoMode = 'edit';
      nanoRender();
    } else if (key === 'Escape') {
      nanoMode = 'edit';
      nanoRender();
    } else if (key === 'Backspace') {
      nanoData.searchTyped = nanoData.searchTyped.slice(0,-1);
      nanoRender();
    } else if (key.length === 1 && !ctrlKey) {
      nanoData.searchTyped += key;
      nanoRender();
    }
    return;
  }

  // ── normal edit mode ──
  if (ctrlKey) {
    switch (key.toLowerCase()) {
      case 'x':  // Exit
        if (nanoData.modified) {
          nanoMode = 'confirm_exit';
        } else {
          nanoClose(false);
        }
        nanoRender();
        return;
      case 's':  // Write Out (Ctrl+S — replaces Ctrl+O which opens browser file dialog)
        nanoMode = 'writename';
        nanoData.writeNameTyped = nanoData.filename;
        nanoData._exitAfterSave = false;
        nanoRender();
        return;
      case 'o':  // also accept Ctrl+O in case browser doesn't intercept (e.g. inside iframe)
        nanoMode = 'writename';
        nanoData.writeNameTyped = nanoData.filename;
        nanoData._exitAfterSave = false;
        nanoRender();
        return;
      case 'f':  // Where Is / Search (Ctrl+F — replaces Ctrl+W which closes the browser tab)
        nanoMode = 'search';
        nanoData.searchTyped = '';
        nanoRender();
        return;
      case 'w':  // also accept Ctrl+W inside iframe where browser won't intercept it
        nanoMode = 'search';
        nanoData.searchTyped = '';
        nanoRender();
        return;
      case 'k':  // Cut line
        nanoData.cutBuffer = nanoData.lines[nanoData.curRow];
        nanoData.lines.splice(nanoData.curRow, 1);
        if (nanoData.lines.length === 0) nanoData.lines = [''];
        if (nanoData.curRow >= nanoData.lines.length) nanoData.curRow = nanoData.lines.length - 1;
        nanoData.curCol  = 0;
        nanoData.modified = true;
        nanoRender();
        return;
      case 'u':  // Paste
        nanoData.lines.splice(nanoData.curRow, 0, nanoData.cutBuffer);
        nanoData.modified = true;
        nanoRender();
        return;
      case 'g':  // Help — show brief help in status
        document.getElementById('nano-status').textContent =
          '^X=Exit  ^S=Save  ^F=Search  ^K=Cut  ^U=Paste  ^C=Location';
        return;
      case 'c':  // Location
        document.getElementById('nano-status').textContent =
          'line ' + (nanoData.curRow+1) + '/' + nanoData.lines.length +
          ', col ' + (nanoData.curCol+1);
        return;
    }
    return;
  }

  // non-ctrl keys
  switch (key) {
    case 'ArrowUp':
      if (nanoData.curRow > 0) {
        nanoData.curRow--;
        nanoData.curCol = Math.min(nanoData.curCol, nanoData.lines[nanoData.curRow].length);
      }
      break;
    case 'ArrowDown':
      if (nanoData.curRow < nanoData.lines.length - 1) {
        nanoData.curRow++;
        nanoData.curCol = Math.min(nanoData.curCol, nanoData.lines[nanoData.curRow].length);
      }
      break;
    case 'ArrowLeft':
      if (nanoData.curCol > 0) {
        nanoData.curCol--;
      } else if (nanoData.curRow > 0) {
        nanoData.curRow--;
        nanoData.curCol = nanoData.lines[nanoData.curRow].length;
      }
      break;
    case 'ArrowRight':
      if (nanoData.curCol < nanoData.lines[nanoData.curRow].length) {
        nanoData.curCol++;
      } else if (nanoData.curRow < nanoData.lines.length - 1) {
        nanoData.curRow++;
        nanoData.curCol = 0;
      }
      break;
    case 'Home':
      nanoData.curCol = 0;
      break;
    case 'End':
      nanoData.curCol = nanoData.lines[nanoData.curRow].length;
      break;
    case 'Enter':
      var line = nanoData.lines[nanoData.curRow];
      var before = line.slice(0, nanoData.curCol);
      var after  = line.slice(nanoData.curCol);
      nanoData.lines.splice(nanoData.curRow, 1, before, after);
      nanoData.curRow++;
      nanoData.curCol   = 0;
      nanoData.modified = true;
      break;
    case 'Backspace':
      if (nanoData.curCol > 0) {
        var l = nanoData.lines[nanoData.curRow];
        nanoData.lines[nanoData.curRow] = l.slice(0, nanoData.curCol-1) + l.slice(nanoData.curCol);
        nanoData.curCol--;
        nanoData.modified = true;
      } else if (nanoData.curRow > 0) {
        var prevLen = nanoData.lines[nanoData.curRow-1].length;
        nanoData.lines[nanoData.curRow-1] += nanoData.lines[nanoData.curRow];
        nanoData.lines.splice(nanoData.curRow, 1);
        nanoData.curRow--;
        nanoData.curCol   = prevLen;
        nanoData.modified = true;
      }
      break;
    case 'Delete':
      var l = nanoData.lines[nanoData.curRow];
      if (nanoData.curCol < l.length) {
        nanoData.lines[nanoData.curRow] = l.slice(0, nanoData.curCol) + l.slice(nanoData.curCol+1);
        nanoData.modified = true;
      } else if (nanoData.curRow < nanoData.lines.length - 1) {
        nanoData.lines[nanoData.curRow] += nanoData.lines[nanoData.curRow+1];
        nanoData.lines.splice(nanoData.curRow+1, 1);
        nanoData.modified = true;
      }
      break;
    default:
      if (key.length === 1) {
        var l = nanoData.lines[nanoData.curRow];
        nanoData.lines[nanoData.curRow] = l.slice(0, nanoData.curCol) + key + l.slice(nanoData.curCol);
        nanoData.curCol++;
        nanoData.modified = true;
      }
  }
  nanoRender();
}

function nanoDoSearch(term) {
  if (!term) return;
  var total = nanoData.lines.length;
  for (var i = 0; i < total; i++) {
    var r   = (nanoData.curRow + i + 1) % total;
    var idx = nanoData.lines[r].indexOf(term);
    if (idx !== -1) {
      nanoData.curRow = r;
      nanoData.curCol = idx;
      document.getElementById('nano-status').textContent = '';
      return;
    }
  }
  document.getElementById('nano-status').textContent = '"' + term + '": Not found';
}

function nanoSave(filename) {
  var savePath = nanoData.path.replace(/[^/]+$/, '') + filename;
  var content  = nanoData.lines.join('\n');
  fetch('terminal.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ cmd: '__nano_save', path: savePath, content: content, user: loginUser })
  })
  .then(function(r){ return r.json(); })
  .then(function(d) {
    nanoData.modified = false;
    nanoData.filename = filename;
    nanoData.path     = savePath;
    // if we were saving on exit, close; otherwise stay open and show confirmation
    if (nanoMode === 'writename' && nanoData._exitAfterSave) {
      nanoClose(true, d.lines);
    } else {
      nanoMode = 'edit';
      document.getElementById('nano-status').textContent =
        'Wrote ' + d.lines + ' line' + (d.lines === 1 ? '' : 's');
      nanoRender();
      // clear status after 2s
      setTimeout(function(){
        if (nanoActive) document.getElementById('nano-status').textContent = '';
      }, 2000);
    }
  });
}

function nanoClose(saved, lines) {
  nanoActive = false;
  document.getElementById('nano-overlay').style.display = 'none';
  if (saved) {
    print('', 'n');
  }
  print('', 'n');
  updateTitleAndPrompt();
  curline.style.display = 'flex';
  scr.scrollTop = scr.scrollHeight;
}

// ── boot sequence ─────────────────────────────────────────
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
        else { startLogin(); }
      }
      next();
    });
}

boot();
</script>
</body>
</html>
