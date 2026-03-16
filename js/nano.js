// nano editor
var nanoActive = false;
var nanoData   = { path:'', filename:'', lines:[], curRow:0, curCol:0, modified:false, cutBuffer:'' };
var nanoMode   = 'edit';   // edit | confirm_exit | search | writename

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

  // confirm exit mode
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

  // write name mode (Ctrl+O or confirm save)
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

  // search mode
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

  // normal edit mode
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
