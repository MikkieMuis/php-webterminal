// interactive.js — animated command renderers: ping, top, htop, wget, curl, dnf
// Depends on globals: print, updateTitleAndPrompt, hidePrompt, curline, scr
// and overlay state vars: topInterval, topEl, topActive, htopInterval, htopEl, htopActive

// top overlay state
var topInterval = null;
var topEl = null;
var topActive = false;

// htop overlay state
var htopInterval = null;
var htopEl = null;
var htopActive = false;

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

function doTop(data) {
  topActive = true;
  topEl = document.createElement('div');
  topEl.id = 'top-overlay';
  topEl.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:#0a0a0a;color:#e0e0e0;font-family:"JetBrains Mono","Courier New",monospace;font-size:13px;padding:10px;box-sizing:border-box;overflow:hidden;z-index:100;white-space:pre;';
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

// htop overlay — interactive process viewer with colour bars
function doHtop(data) {
  htopActive = true;
  htopEl = document.createElement('div');
  htopEl.id = 'htop-overlay';
  htopEl.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:#0a0a0a;color:#e0e0e0;font-family:"JetBrains Mono","Courier New",monospace;font-size:13px;padding:10px;box-sizing:border-box;overflow:hidden;z-index:100;white-space:pre;';
  document.getElementById('terminal').appendChild(htopEl);
  hidePrompt();

  var sortField = 'cpu'; // default sort
  var cpuCount  = data.cpuCount || 4;

  function barStr(used, total, width) {
    var filled = Math.round((used / total) * width);
    filled = Math.max(0, Math.min(filled, width));
    return '[' + '|'.repeat(filled) + ' '.repeat(width - filled) + ']';
  }

  function render() {
    var load = data.load.map(function(l){ return (l + (Math.random()*0.05-0.025)).toFixed(2); });
    var now  = new Date();
    var hh   = String(now.getHours()).padStart(2,'0');
    var mm   = String(now.getMinutes()).padStart(2,'0');
    var ss   = String(now.getSeconds()).padStart(2,'0');

    // CPU bars — fake per-core based on load average
    var cpuLines = '';
    for (var c = 0; c < cpuCount; c++) {
      var pct = Math.min(99, Math.max(0, Math.round(parseFloat(load[0]) * 10 + (Math.random()*5-2.5))));
      cpuLines += '  CPU' + (c+1) + ' ' + barStr(pct, 100, 20) + ' ' + String(pct).padStart(2) + '%\n';
    }

    var memBar  = '  Mem  ' + barStr(data.memUsed,  data.memTotal,  20) + ' ' + data.memUsed  + 'M/' + data.memTotal  + 'M\n';
    var swapBar = '  Swap ' + barStr(data.swapUsed, data.swapTotal, 20) + ' ' + data.swapUsed + 'M/' + data.swapTotal + 'M\n';

    var header =
      cpuLines +
      memBar +
      swapBar +
      '\n' +
      '  Tasks: ' + data.procs.length + ' total; 1 running, ' + (data.procs.length-1) + ' sleeping\n' +
      '  Load average: ' + load.join(' ') + '   Uptime: ' + data.uptime + '   ' + hh+':'+mm+':'+ss + '\n' +
      '\n' +
      '  PID   USER       PRI  NI  VIRT   RES   SHR S  CPU%  MEM%   TIME+  COMMAND\n';

    // sort by cpu descending
    var procs = data.procs.slice().sort(function(a,b){ return b.cpu - a.cpu; });

    var rows = procs.map(function(p, i) {
      var cpu = Math.max(0, p.cpu + (Math.random()*0.3-0.15)).toFixed(1);
      var row =
        String(p.pid).padStart(5) + '  ' +
        p.user.padEnd(10) + ' ' +
        String(p.pr).padStart(3) + '  ' +
        String(p.ni).padStart(2) + ' ' +
        String(p.virt).padStart(6) + ' ' +
        String(p.res).padStart(5) + ' ' +
        String(p.shr).padStart(5) + ' ' +
        p.s + ' ' +
        String(cpu).padStart(5) + ' ' +
        String(p.mem.toFixed(1)).padStart(5) + ' ' +
        p.time.padStart(9) + '  ' +
        p.cmd;
      // highlight the selected (first) row
      return i === 0 ? '\x1b[7m' + row + '\x1b[0m' : row;
    });

    htopEl.textContent = header + rows.join('\n') + '\n\n';

    // footer bar
    var footer = document.createElement('div');
    footer.style.cssText = 'position:absolute;bottom:0;left:0;width:100%;background:#e0e0e0;color:#0a0a0a;font-family:"JetBrains Mono","Courier New",monospace;font-size:13px;padding:2px 10px;box-sizing:border-box;';
    footer.textContent = ' F1Help  F2Setup  F3Search  F5Tree  F6SortBy  F9Kill  F10Quit  q Quit';
    htopEl.appendChild(footer);
  }

  render();
  htopInterval = setInterval(render, 2000);

  function htopKey(e) {
    var key = (e.data && e.data.type === 'keydown') ? e.data.key : null;
    if (key === 'q' || key === 'F10') { exitHtop(); }
  }
  function htopKeyNative(e) {
    if (e.key === 'q' || e.key === 'F10') { e.preventDefault(); exitHtop(); }
  }
  function exitHtop() {
    htopActive = false;
    clearInterval(htopInterval); htopInterval = null;
    if (htopEl) { htopEl.remove(); htopEl = null; }
    window.removeEventListener('message', htopKey);
    if (window.self === window.top) document.removeEventListener('keydown', htopKeyNative);
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  }
  window.addEventListener('message', htopKey);
  if (window.self === window.top) document.addEventListener('keydown', htopKeyNative);
}

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

// curl animation
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

// dnf animation
function doDnf(data) {
  if (data.dnfcmd === 'install') {
    var pkgs  = data.pkgs;
    var steps = data.steps;
    var totalSize = steps.reduce(function(acc, s){ return acc + s.size; }, 0);
    var totalSizeFmt = totalSize >= 1024 ? (totalSize/1024).toFixed(1) + ' MB' : totalSize + ' kB';

    // dependency + transaction check header
    print('Last metadata expiration check: 0:12:14 ago on ' + new Date().toString().slice(0,24) + '.', 'n');
    print('Dependencies resolved.', 'n');
    print('', 'n');
    print('=' .repeat(70), 'n');
    print(' Package' + ' '.repeat(22) + 'Arch   Version                   Repository   Size', 'n');
    print('=' .repeat(70), 'n');
    print('Installing:', 'n');
    steps.forEach(function(s) {
      print(' ' + s.pkg.padEnd(28).slice(0,28) + ' x86_64 ' + s.ver.padEnd(26).slice(0,26) + ' ' + s.repo + '  ' + s.sizeFmt, 'n');
    });
    print('', 'n');
    print('Transaction Summary', 'n');
    print('=' .repeat(70), 'n');
    print('Install  ' + pkgs.length + ' Package' + (pkgs.length !== 1 ? 's' : ''), 'n');
    print('', 'n');
    print('Total download size: ' + totalSizeFmt, 'n');
    print('Installed size: ' + totalSizeFmt, 'n');

    // Animate each package download then install
    var i = 0;
    function nextPkg() {
      if (i >= steps.length) {
        print('', 'n');
        print('Installed:', 'n');
        steps.forEach(function(s) {
          print('  ' + s.pkg + '-' + s.ver + '.x86_64', 'n');
        });
        print('', 'n');
        print('Complete!', 'n');
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
        scr.scrollTop = scr.scrollHeight;
        return;
      }
      var s = steps[i];
      i++;
      // downloading
      var dlSteps = 10; var dlStep = 0;
      function dlTick() {
        dlStep++;
        var pct = Math.round((dlStep/dlSteps)*100);
        var last = scr.querySelector('.ln.dnfprogress');
        if (last) last.remove();
        var bar = '='.repeat(Math.round(pct/5)) + (pct<100?'>':'') + ' '.repeat(Math.max(0,19-Math.round(pct/5)));
        var el = document.createElement('span');
        el.className = 'ln n dnfprogress';
        el.textContent = s.pkg.slice(0,18).padEnd(18) + ' [' + bar + '] ' + String(pct).padStart(3) + '%';
        scr.insertBefore(el, curline);
        scr.scrollTop = scr.scrollHeight;
        if (dlStep < dlSteps) { setTimeout(dlTick, 60); }
        else {
          var fin = scr.querySelector('.ln.dnfprogress');
          if (fin) fin.remove();
          print('(' + i + '/' + steps.length + '): ' + s.pkg.padEnd(30).slice(0,30) + ' ' + s.sizeFmt.padStart(8) + ' B/s | ' + s.sizeFmt.padStart(8) + ' ' + s.sizeFmt, 'n');
          setTimeout(nextPkg, 150);
        }
      }
      dlTick();
    }
    print('', 'n');
    print('Downloading Packages:', 'n');
    nextPkg();
    return;
  }

  if (data.dnfcmd === 'remove') {
    var pkgs = data.pkgs;
    print('Dependencies resolved.', 'n');
    print('', 'n');
    print('=' .repeat(70), 'n');
    print(' Package' + ' '.repeat(22) + 'Arch       Version              Repository     Size', 'n');
    print('=' .repeat(70), 'n');
    print('Removing:', 'n');
    pkgs.forEach(function(p) {
      print(' ' + p.padEnd(28).slice(0,28) + ' x86_64    (installed)', 'n');
    });
    print('', 'n');
    print('Transaction Summary', 'n');
    print('=' .repeat(70), 'n');
    print('Remove  ' + pkgs.length + ' Package' + (pkgs.length !== 1 ? 's' : ''), 'n');
    print('', 'n');

    var i = 0;
    function nextRemove() {
      if (i >= pkgs.length) {
        print('', 'n');
        print('Removed:', 'n');
        pkgs.forEach(function(p) { print('  ' + p + '.x86_64', 'n'); });
        print('', 'n');
        print('Complete!', 'n');
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
        scr.scrollTop = scr.scrollHeight;
        return;
      }
      var pkg = pkgs[i]; i++;
      print('  Erasing     : ' + pkg, 'n');
      setTimeout(nextRemove, 300);
    }
    print('Running transaction', 'n');
    setTimeout(nextRemove, 400);
    return;
  }

  if (data.dnfcmd === 'upgrade') {
    var pkgs = data.pkgs;
    print('Last metadata expiration check: 0:12:14 ago on ' + new Date().toString().slice(0,24) + '.', 'n');
    print('Dependencies resolved.', 'n');
    print('', 'n');
    print('=' .repeat(70), 'n');
    print(' Package' + ' '.repeat(22) + 'Arch   Version (old → new)            Repository', 'n');
    print('=' .repeat(70), 'n');
    print('Upgrading:', 'n');
    pkgs.forEach(function(p) {
      print(' ' + p.name.padEnd(28).slice(0,28) + ' x86_64 ' + (p.old + ' → ' + p.new).slice(0,28).padEnd(28) + ' baseos', 'n');
    });
    print('', 'n');
    print('Transaction Summary', 'n');
    print('=' .repeat(70), 'n');
    print('Upgrade  ' + pkgs.length + ' Packages', 'n');
    print('', 'n');
    print('Downloading Packages:', 'n');

    var i = 0;
    function nextUpgrade() {
      if (i >= pkgs.length) {
        print('', 'n');
        print('Upgraded:', 'n');
        pkgs.forEach(function(p) { print('  ' + p.name + '-' + p.new + '.x86_64', 'n'); });
        print('', 'n');
        print('Complete!', 'n');
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
        scr.scrollTop = scr.scrollHeight;
        return;
      }
      var p = pkgs[i]; i++;
      print('(' + i + '/' + pkgs.length + '): ' + p.name.padEnd(30).slice(0,30) + ' ' + p.size.padStart(8), 'n');
      setTimeout(nextUpgrade, 200);
    }
    setTimeout(nextUpgrade, 300);
    return;
  }
}
