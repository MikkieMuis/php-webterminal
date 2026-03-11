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
  border:1px solid #1a5c1a;
  box-shadow:0 0 30px rgba(57,255,20,0.12);
}

/* ── title bar ───────────────────────────────────────────── */
#titlebar {
  background:#1a1a1a;
  padding:8px 14px;
  display:flex;
  align-items:center;
  gap:8px;
  border-bottom:1px solid #1a5c1a;
  flex-shrink:0;
}
.dot { width:12px; height:12px; border-radius:50%; display:inline-block; }
.r { background:#ff5f56; }
.y { background:#ffbd2e; }
.g { background:#27c93f; }
#ttitle { color:#4a4a4a; font-size:13px; flex:1; text-align:center; }

/* ── screen area ─────────────────────────────────────────── */
#screen {
  flex:1;
  overflow-y:auto;
  padding:14px 16px;
  background:#0a0a0a;
  font-size:14px;
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
  font-size:14px;
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
</style>
</head>
<body>
<div id="terminal">
  <div id="titlebar">
    <span class="dot r"></span>
    <span class="dot y"></span>
    <span class="dot g"></span>
    <span id="ttitle">root@localhost: ~</span>
  </div>
  <div id="screen">
    <div id="curline"><span id="curprompt"></span><span id="curtyped"></span><span id="curcursor"></span></div>
  </div>
</div>

<script>
// ── DOM refs ──────────────────────────────────────────────
var scr       = document.getElementById('screen');
var curline   = document.getElementById('curline');
var curprompt = document.getElementById('curprompt');
var curtyped  = document.getElementById('curtyped');
var curcursor = document.getElementById('curcursor');
var ttitle    = document.getElementById('ttitle');

// ── system info (populated before boot) ───────────────────
var sysKernel   = '5.14.0-1-default';
var sysArch     = 'x86_64';
var sysOS       = 'Linux';
var sysHostname = 'localhost';

// ── state ─────────────────────────────────────────────────
var mode      = 'boot';   // boot | username | password | command
var typed     = '';
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
  typed  = '';
  curtyped.textContent = '';
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
  ttitle.textContent    = loginUser + '@' + sysHostname + ': ' + shortCwd;
}

// ── keyboard ──────────────────────────────────────────────
// preserve trailing spaces visually by replacing them with non-breaking spaces
function renderTyped(str) {
  return str.replace(/ $/, '\u00a0');
}

function handleKey(key, ctrlKey, altKey, metaKey) {
  if (mode === 'boot') return;
  if (topActive) return;

  if (key === 'Enter') {
    var val = typed;
    typed = '';
    curtyped.textContent = '';
    handleEnter(val);
    return;
  }

  if (key === 'Backspace') {
    if (typed.length > 0) {
      typed = typed.slice(0, -1);
      curtyped.textContent = masked ? '*'.repeat(typed.length) : renderTyped(typed);
    }
    return;
  }

  if (key.length === 1 && !ctrlKey && !altKey && !metaKey) {
    typed += key;
    curtyped.textContent = masked ? '*'.repeat(typed.length) : renderTyped(typed);
    scr.scrollTop = scr.scrollHeight;
    return;
  }

  if (mode === 'command') {
    if (key === 'ArrowUp') {
      if (histIdx < cmdLog.length - 1) { histIdx++; typed = cmdLog[histIdx]; curtyped.textContent = renderTyped(typed); }
    } else if (key === 'ArrowDown') {
      if (histIdx > 0) { histIdx--; typed = cmdLog[histIdx]; }
      else             { histIdx = -1; typed = ''; }
      curtyped.textContent = renderTyped(typed);
    }
  }
}

// native keydown — always active (handles both standalone and focused-iframe)
document.addEventListener('keydown', function(e) {
  if (mode === 'boot' || mode === 'dead') return;
  e.preventDefault();
  handleKey(e.key, e.ctrlKey, e.altKey, e.metaKey);
});

// postMessage from parent iframe wrapper — only fires when iframe does NOT have focus
window.addEventListener('message', function(e) {
  // reject messages from any origin other than our own page
  if (e.origin !== window.location.origin) return;
  if (e.data && e.data.type === 'keydown') {
    // if this document has focus, the native keydown above already handled it
    if (document.hasFocus()) return;
    handleKey(e.data.key, e.data.ctrlKey, e.data.altKey, e.data.metaKey);
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
  mode   = 'command';
  masked = false;
  cwd    = '/root';
  updateTitleAndPrompt();
  curline.style.display = 'flex';
}

// ── run command via AJAX ──────────────────────────────────
function runCmd(raw) {
  var trimmed = raw.trim();
  print(loginUser + '@' + sysHostname + ':' + cwd.replace('/root','~') + '# ' + trimmed, 'n');

  if (!trimmed) { print('','n'); return; }

  cmdLog.unshift(trimmed);
  histIdx = -1;

  hidePrompt();

  fetch('terminal.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ cmd: trimmed, user: loginUser })
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
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  })
  .catch(function() {
    print('bash: connection error', 'e');
    print('', 'n');
    updateTitleAndPrompt();
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
        body:    JSON.stringify({ cmd: sudoCmd, user: 'root' })
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
