// pager (more / less)
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
