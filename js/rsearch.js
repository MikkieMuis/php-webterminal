// rsearch.js — Ctrl+R reverse history search
// Depends on globals: rsearchActive, rsearchQuery, rsearchIdx, rsearchMatch,
//                     cmdLog, typed, cursorPos, histIdx,
//                     curprompt, renderLine, updateTitleAndPrompt, handleEnter, scr

// Render the reverse-search prompt line.
// Format: (reverse-i-search)`<query>': <match>
// The cursor sits at the end of the match (standard bash behaviour).
function rsearchRender() {
  var label  = "(reverse-i-search)`" + rsearchQuery + "': ";
  curprompt.textContent = label;
  // highlight the matching portion inside the match
  var matchText = rsearchMatch;
  typed     = matchText;
  cursorPos = matchText.length;
  renderLine();
  scr.scrollTop = scr.scrollHeight;
}

// Find the Nth (0-based) match for rsearchQuery in cmdLog (most-recent first).
// Returns '' if none found.
function rsearchFind(query, startIdx) {
  if (!query) return '';
  var lq = query.toLowerCase();
  var count = 0;
  for (var i = 0; i < cmdLog.length; i++) {
    if (cmdLog[i].toLowerCase().indexOf(lq) !== -1) {
      if (count === startIdx) return cmdLog[i];
      count++;
    }
  }
  return '';
}

// Cancel reverse search — restore original typed content and normal prompt.
function rsearchCancel(restoreTyped) {
  rsearchActive = false;
  histIdx       = -1;
  typed         = restoreTyped;
  cursorPos     = typed.length;
  updateTitleAndPrompt();
  renderLine();
}

// Accept the current match — put it on the command line, leave rsearch mode.
function rsearchAccept() {
  var accepted  = rsearchMatch;
  rsearchActive = false;
  histIdx       = -1;
  typed         = accepted;
  cursorPos     = typed.length;
  updateTitleAndPrompt();
  renderLine();
}

// Handle a key while reverse-i-search is active.
function rsearchKey(key, ctrlKey) {
  // Ctrl+R — cycle to next (older) match
  if (ctrlKey && (key === 'r' || key === 'R')) {
    rsearchIdx++;
    rsearchMatch = rsearchFind(rsearchQuery, rsearchIdx);
    if (rsearchMatch === '') {
      // no more matches — stay at last valid idx
      rsearchIdx = Math.max(0, rsearchIdx - 1);
      rsearchMatch = rsearchFind(rsearchQuery, rsearchIdx);
    }
    rsearchRender();
    return;
  }
  // Ctrl+G or Escape — cancel, restore empty line
  if ((ctrlKey && (key === 'g' || key === 'G')) || key === 'Escape') {
    rsearchCancel('');
    return;
  }
  // Ctrl+C — cancel like a real SIGINT
  if (ctrlKey && (key === 'c' || key === 'C')) {
    rsearchCancel('');
    return;
  }
  // Enter — accept and execute
  if (key === 'Enter') {
    rsearchAccept();
    // run the accepted command
    var val = typed;
    typed     = '';
    cursorPos = 0;
    renderLine();
    handleEnter(val);
    return;
  }
  // Backspace — remove last char from query
  if (key === 'Backspace') {
    if (rsearchQuery.length > 0) {
      rsearchQuery = rsearchQuery.slice(0, -1);
      rsearchIdx   = 0;
      rsearchMatch = rsearchFind(rsearchQuery, 0);
    }
    rsearchRender();
    return;
  }
  // Any printable character — append to query
  if (key.length === 1 && !ctrlKey) {
    rsearchQuery += key;
    rsearchIdx   = 0;
    rsearchMatch = rsearchFind(rsearchQuery, 0);
    rsearchRender();
    return;
  }
  // Anything else (arrows, Tab, etc.) — accept current match and pass key through
  rsearchAccept();
}
