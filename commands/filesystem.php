<?php
//  filesystem commands: ls, cd, mkdir, rmdir, touch, rm, cat, wc, cp, mv, grep,
//                       head, tail, du, chmod, chown, diff, more, less, sort, uniq
//  Archive commands (zip, unzip, tar) live in commands/archive.php
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // ls
    case 'ls':
        // parse flags — collect all chars from flag args (e.g. -ltr → l,t,r)
        $flags = '';
        $target = $_SESSION['cwd'];
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $target = res_path($a); }
        }
        $long    = (strpos($flags, 'l') !== false);
        $all     = (strpos($flags, 'a') !== false);
        $sortTime = (strpos($flags, 't') !== false);
        $sortSize = (strpos($flags, 'S') !== false);
        $reverse  = (strpos($flags, 'r') !== false);

        $target = res_path($target);
        if (!isset($_SESSION['fs'][$target])) {
            err('ls: cannot access \'' . $target . '\': No such file or directory');
        }
        if ($_SESSION['fs'][$target]['type'] === 'file') {
            $node = $_SESSION['fs'][$target];
            $sz   = isset($node['content']) ? strlen($node['content']) : 0;
            $mt   = isset($node['mtime']) ? date('M d H:i', $node['mtime']) : 'Mar  9 08:11';
            out($long ? sprintf('-rw-r--r--  1 root root %6d  %s  %s', $sz, $mt, basename($target)) : basename($target));
        }
        $fs      = $_SESSION['fs'];
        $prefix  = rtrim($target, '/');
        $entries = [];
        foreach ($fs as $p => $node) {
            if ($p === $target) continue;
            $parent = ($prefix === '') ? '/' : $prefix;
            if (dirname($p) !== $parent) continue;
            $name = basename($p);
            if (!$all && $name[0] === '.') continue;
            $perm  = $node['type'] === 'dir' ? 'drwxr-xr-x' : '-rw-r--r--';
            $size  = isset($node['content']) ? strlen($node['content']) : 4096;
            $mtime_raw = isset($node['mtime']) ? $node['mtime'] : 1741507200;
            $mtime = date('M d H:i', $mtime_raw);
            $entries[] = ['name'=>$name,'perm'=>$perm,'size'=>$size,'mtime'=>$mtime,'mtime_raw'=>$mtime_raw,'type'=>$node['type']];
        }
        if (empty($entries)) { out(''); }

        // sort
        if ($sortTime) {
            usort($entries, function($a,$b){ return $b['mtime_raw'] - $a['mtime_raw']; }); // newest first
        } elseif ($sortSize) {
            usort($entries, function($a,$b){ return $b['size'] - $a['size']; });            // largest first
        } else {
            usort($entries, function($a,$b){ return strcmp($a['name'],$b['name']); });      // alpha
        }
        if ($reverse) { $entries = array_reverse($entries); }

        if (!$long) {
            $names    = array_map(function($e){ return $e['name']; }, $entries);
            $maxLen   = max(array_map('strlen', $names) ?: [0]);
            $colWidth = $maxLen + 2;
            $termW    = isset($cols) ? $cols : 80;
            $numCols  = max(1, (int)floor($termW / $colWidth));
            $rows     = (int)ceil(count($names) / $numCols);
            $lines    = [];
            for ($r = 0; $r < $rows; $r++) {
                $parts = [];
                for ($c = 0; $c < $numCols; $c++) {
                    $idx = $c * $rows + $r;
                    if ($idx >= count($names)) break;
                    $isLast = ($c === $numCols - 1) || (($idx + $rows) >= count($names));
                    $parts[] = $isLast ? $names[$idx] : str_pad($names[$idx], $colWidth);
                }
                $lines[] = implode('', $parts);
            }
            out(implode("\n", $lines));
        }
        $lines = ['total ' . (count($entries) * 8)];
        foreach ($entries as $e) {
            $lines[] = sprintf('%s  2 root root %6d  %s  %s', $e['perm'], $e['size'], $e['mtime'], $e['name']);
        }
        out(implode("\n", $lines));

    // cd
    case 'cd':
        $home   = ($user === 'root') ? '/root' : '/home/' . $user;
        $target = ($args === '' || $args === '~') ? $home : res_path(str_replace('~', $home, $args));
        if (!isset($_SESSION['fs'][$target])) {
            err('bash: cd: ' . $args . ': No such file or directory');
        }
        if ($_SESSION['fs'][$target]['type'] !== 'dir') {
            err('bash: cd: ' . $args . ': Not a directory');
        }
        $_SESSION['cwd'] = $target;
        echo json_encode(['output'=>'', 'cwd'=> $target]);
        exit;

    // mkdir
    case 'mkdir':
        if ($args === '') err('mkdir: missing operand');
        $target = res_path($args);
        if (isset($_SESSION['fs'][$target])) err('mkdir: cannot create directory \'' . $args . '\': File exists');
        $parent = dirname($target);
        if (!isset($_SESSION['fs'][$parent]) || $_SESSION['fs'][$parent]['type'] !== 'dir') {
            err('mkdir: cannot create directory \'' . $args . '\': No such file or directory');
        }
        $_SESSION['fs'][$target] = ['type'=>'dir', 'mtime'=>time()];
        out('');

    // touch
    case 'touch':
        if ($args === '') err('touch: missing file operand');
        $target = res_path($args);
        if (!isset($_SESSION['fs'][$target])) {
            $parent = dirname($target);
            if (!isset($_SESSION['fs'][$parent])) err('touch: cannot touch \'' . $args . '\': No such file or directory');
            $_SESSION['fs'][$target] = ['type'=>'file','content'=>'','mtime'=>time()];
        } else {
            $_SESSION['fs'][$target]['mtime'] = time();
        }
        out('');

    // rm
    case 'rm':
        if (strpos($args, '-rf') !== false || strpos($args, '-r') !== false) {
            $path = trim(preg_replace('/-r\S*\s*/', '', $args));
            if ($path === '/' || $path === '') {
                echo json_encode(['output'=>'', 'rmrf'=>true]);
                exit;
            }
        }
        if ($args === '') err('rm: missing operand');
        $flags  = '';
        $target = '';
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= $a; } else { $target = $a; }
        }
        $path = res_path($target);
        if (!isset($_SESSION['fs'][$path])) err('rm: cannot remove \'' . $target . '\': No such file or directory');
        if ($_SESSION['fs'][$path]['type'] === 'dir' && strpos($flags,'-r') === false) {
            err('rm: cannot remove \'' . $target . '\': Is a directory');
        }
        foreach (array_keys($_SESSION['fs']) as $k) {
            if ($k === $path || strpos($k, rtrim($path,'/').'/')  === 0) {
                unset($_SESSION['fs'][$k]);
            }
        }
        out('');

    // cat
    case 'cat':
        if ($args === '') err('cat: missing operand');
        $target = res_path($args);
        if (!isset($_SESSION['fs'][$target])) err('cat: ' . $args . ': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] === 'dir') err('cat: ' . $args . ': Is a directory');
        out($_SESSION['fs'][$target]['content']);

    // wc
    case 'wc':
        // parse flags and filename
        $flags   = '';
        $wcfile  = '';
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $wcfile = $a; }
        }
        if ($wcfile === '') err('wc: missing operand');
        $target = res_path($wcfile);
        if (!isset($_SESSION['fs'][$target])) err('wc: ' . $wcfile . ': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] === 'dir') err('wc: ' . $wcfile . ': Is a directory');
        $content = $_SESSION['fs'][$target]['content'] ?? '';
        $lines   = $content === '' ? 0 : substr_count($content, "\n") + (substr($content, -1) !== "\n" ? 1 : 0);
        $words   = $content === '' ? 0 : str_word_count($content);
        $bytes   = strlen($content);
        $name    = basename($target);
        // decide what to show based on flags
        if ($flags === '') {
            out(sprintf(' %4d %4d %4d %s', $lines, $words, $bytes, $name));
        } elseif (strpos($flags, 'l') !== false && strpos($flags, 'w') === false && strpos($flags, 'c') === false && strpos($flags, 'm') === false) {
            out(sprintf(' %4d %s', $lines, $name));
        } elseif (strpos($flags, 'w') !== false && strpos($flags, 'l') === false && strpos($flags, 'c') === false) {
            out(sprintf(' %4d %s', $words, $name));
        } elseif (strpos($flags, 'c') !== false || strpos($flags, 'm') !== false) {
            out(sprintf(' %4d %s', $bytes, $name));
        } else {
            // multiple flags — show each requested column
            $parts = [];
            if (strpos($flags, 'l') !== false) $parts[] = sprintf('%4d', $lines);
            if (strpos($flags, 'w') !== false) $parts[] = sprintf('%4d', $words);
            if (strpos($flags, 'c') !== false || strpos($flags, 'm') !== false) $parts[] = sprintf('%4d', $bytes);
            out(implode(' ', $parts) . ' ' . $name);
        }

    // cp
    case 'cp':
        // Usage: cp [-r] SOURCE DEST
        if (count($argv) < 2) err('cp: missing file operand');
        $flags  = '';
        $cpargs = [];
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $cpargs[] = $a; }
        }
        if (count($cpargs) < 2) err('cp: missing destination file operand after \'' . $cpargs[0] . '\'');
        $recursive = (strpos($flags, 'r') !== false || strpos($flags, 'R') !== false);
        $src  = res_path($cpargs[0]);
        $dest = res_path($cpargs[1]);
        if (!isset($_SESSION['fs'][$src])) err('cp: cannot stat \'' . $cpargs[0] . '\': No such file or directory');
        $srcType = $_SESSION['fs'][$src]['type'];
        if ($srcType === 'dir' && !$recursive) err('cp: omitting directory \'' . $cpargs[0] . '\' — use -r');
        // if dest is an existing directory, copy into it
        if (isset($_SESSION['fs'][$dest]) && $_SESSION['fs'][$dest]['type'] === 'dir') {
            $dest = rtrim($dest, '/') . '/' . basename($src);
        }
        $destParent = dirname($dest);
        if (!isset($_SESSION['fs'][$destParent]) || $_SESSION['fs'][$destParent]['type'] !== 'dir') {
            err('cp: cannot create \'' . $cpargs[1] . '\': No such file or directory');
        }
        if ($srcType === 'file') {
            $_SESSION['fs'][$dest] = array_merge($_SESSION['fs'][$src], ['mtime' => time()]);
        } else {
            // recursive copy of directory tree
            $srcPrefix  = rtrim($src, '/');
            $destPrefix = rtrim($dest, '/');
            $_SESSION['fs'][$dest] = ['type' => 'dir', 'mtime' => time()];
            foreach ($_SESSION['fs'] as $p => $node) {
                if (strpos($p, $srcPrefix . '/') === 0) {
                    $rel  = substr($p, strlen($srcPrefix));
                    $_SESSION['fs'][$destPrefix . $rel] = array_merge($node, ['mtime' => time()]);
                }
            }
        }
        out('');

    // mv
    case 'mv':
        // Usage: mv SOURCE DEST
        if (count($argv) < 2) err('mv: missing file operand');
        $mvargs = [];
        foreach ($argv as $a) {
            if ($a[0] !== '-') $mvargs[] = $a;
        }
        if (count($mvargs) < 2) err('mv: missing destination file operand after \'' . $mvargs[0] . '\'');
        $src  = res_path($mvargs[0]);
        $dest = res_path($mvargs[1]);
        if (!isset($_SESSION['fs'][$src])) err('mv: cannot stat \'' . $mvargs[0] . '\': No such file or directory');
        // if dest is an existing directory, move into it
        if (isset($_SESSION['fs'][$dest]) && $_SESSION['fs'][$dest]['type'] === 'dir') {
            $dest = rtrim($dest, '/') . '/' . basename($src);
        }
        $destParent = dirname($dest);
        if (!isset($_SESSION['fs'][$destParent]) || $_SESSION['fs'][$destParent]['type'] !== 'dir') {
            err('mv: cannot move \'' . $mvargs[0] . '\' to \'' . $mvargs[1] . '\': No such file or directory');
        }
        // move: copy all matching keys to new prefix, then remove old ones
        $srcPrefix  = rtrim($src, '/');
        $destPrefix = rtrim($dest, '/');
        $toAdd = [];
        $toRemove = [];
        foreach ($_SESSION['fs'] as $p => $node) {
            if ($p === $src) {
                $toAdd[$dest]    = array_merge($node, ['mtime' => time()]);
                $toRemove[]      = $p;
            } elseif (strpos($p, $srcPrefix . '/') === 0) {
                $rel             = substr($p, strlen($srcPrefix));
                $toAdd[$destPrefix . $rel] = array_merge($node, ['mtime' => time()]);
                $toRemove[]      = $p;
            }
        }
        foreach ($toRemove as $k) unset($_SESSION['fs'][$k]);
        foreach ($toAdd    as $k => $v) $_SESSION['fs'][$k] = $v;
        out('');

    // grep
    case 'grep':
        // Usage: grep [OPTIONS] PATTERN [FILE...]
        // Supported flags: -i (ignore case), -r (recursive), -n (line numbers),
        //                  -v (invert), -c (count), -l (filenames only), -E (extended regex)
        if (empty($argv)) err("grep: missing operand\nUsage: grep [OPTION...] PATTERN [FILE...]");

        // parse flags and collect non-flag args (pattern + files)
        $flags       = '';
        $nonFlags    = [];
        $skipNext    = false;
        foreach ($argv as $a) {
            if ($skipNext) { $skipNext = false; continue; }
            if ($a[0] === '-' && strlen($a) > 1) { $flags .= ltrim($a, '-'); }
            else { $nonFlags[] = $a; }
        }
        $ignoreCase = (strpos($flags, 'i') !== false);
        $recursive  = (strpos($flags, 'r') !== false || strpos($flags, 'R') !== false);
        $lineNums   = (strpos($flags, 'n') !== false);
        $invert     = (strpos($flags, 'v') !== false);
        $countOnly  = (strpos($flags, 'c') !== false);
        $listFiles  = (strpos($flags, 'l') !== false);

        if (empty($nonFlags)) err("grep: missing pattern\nUsage: grep [OPTION...] PATTERN [FILE...]");

        $pattern = array_shift($nonFlags);  // first non-flag arg is the pattern
        $targets = $nonFlags;               // remaining are file/dir paths

        // validate regex — escape if it looks like a plain string (no regex special chars)
        // We support both plain strings and basic ERE patterns.
        // Build the PCRE pattern
        $pcre = '/' . str_replace('/', '\/', $pattern) . '/';
        if ($ignoreCase) $pcre .= 'i';
        // quick validity check
        if (@preg_match($pcre, '') === false) {
            // pattern is invalid regex — treat as literal string
            $pcre = '/' . str_replace('/', '\/', preg_quote($pattern, '/')) . '/';
            if ($ignoreCase) $pcre .= 'i';
        }

        // collect files to search
        // if no files given and not recursive → error (we don't support stdin)
        if (empty($targets)) {
            if ($recursive) {
                $targets = ['/'];  // grep -r with no path defaults to current dir
                $targets = [$_SESSION['cwd']];
            } else {
                err('grep: no file operand — reading from stdin is not supported in this terminal');
            }
        }

        // expand targets: resolve paths; if -r, collect all files under dir
        function grep_collect_files(array $targets, bool $recursive): array {
            $files = [];
            foreach ($targets as $t) {
                $p = res_path($t);
                if (!isset($_SESSION['fs'][$p])) continue;
                if ($_SESSION['fs'][$p]['type'] === 'file') {
                    $files[] = $p;
                } elseif ($_SESSION['fs'][$p]['type'] === 'dir') {
                    if ($recursive) {
                        $prefix = rtrim($p, '/');
                        foreach ($_SESSION['fs'] as $fp => $fn) {
                            if ($fn['type'] === 'file' && strpos($fp, $prefix . '/') === 0) {
                                $files[] = $fp;
                            }
                        }
                    }
                    // non-recursive dir: grep will say "Is a directory" — handled below
                }
            }
            return $files;
        }

        // check for non-recursive directory targets
        foreach ($targets as $t) {
            $p = res_path($t);
            if (isset($_SESSION['fs'][$p]) && $_SESSION['fs'][$p]['type'] === 'dir' && !$recursive) {
                err('grep: ' . $t . ': Is a directory');
            }
            if (!isset($_SESSION['fs'][$p])) {
                err('grep: ' . $t . ': No such file or directory');
            }
        }

        $files       = grep_collect_files($targets, $recursive);
        $multiFile   = count($files) > 1 || $recursive;
        $outputLines = [];
        $totalCount  = 0;

        foreach ($files as $fp) {
            $content  = $_SESSION['fs'][$fp]['content'] ?? '';
            $rawLines = explode("\n", $content);
            // strip trailing empty entry from files ending with \n
            if (end($rawLines) === '') array_pop($rawLines);

            $matchCount = 0;
            $fileHits   = [];

            foreach ($rawLines as $i => $line) {
                $matched = (preg_match($pcre, $line) === 1);
                if ($invert) $matched = !$matched;
                if (!$matched) continue;
                $matchCount++;
                if (!$countOnly && !$listFiles) {
                    $prefix = '';
                    if ($multiFile)  $prefix .= $fp . ':';
                    if ($lineNums)   $prefix .= ($i + 1) . ':';
                    $fileHits[] = $prefix . $line;
                }
            }

            if ($countOnly) {
                $outputLines[] = ($multiFile ? $fp . ':' : '') . $matchCount;
                $totalCount += $matchCount;
            } elseif ($listFiles) {
                if ($matchCount > 0) $outputLines[] = $fp;
            } else {
                foreach ($fileHits as $h) $outputLines[] = $h;
                $totalCount += $matchCount;
            }
        }

        if ($totalCount === 0 && !$countOnly && !$listFiles) {
            // grep returns exit 1 (no match) — we signal this with error=true but no message
            echo json_encode(['output' => '', 'error' => true]);
            exit;
        }

        out(implode("\n", $outputLines));

    // head
    case 'head':
        // Usage: head [-n N] [-c N] [-q] [-v] FILE [FILE...]
        // -n N   : output first N lines (default 10)
        // -c N   : output first N bytes instead of lines
        // -q     : never print file headers
        // -v     : always print file headers
        {
            $n         = 10;        // default lines
            $bytes     = -1;        // -1 = use lines
            $quiet     = false;     // -q
            $verbose   = false;     // -v
            $headfiles = [];
            $skipNext  = false;
            foreach ($argv as $idx => $a) {
                if ($skipNext) { $skipNext = false; continue; }
                if ($a[0] === '-' && strlen($a) > 1) {
                    $flag = ltrim($a, '-');
                    if ($flag === 'n' || $flag === 'c') {
                        // next arg is the number
                        $nextIdx = array_search($a, $argv) + 1;
                        if (isset($argv[$nextIdx]) && is_numeric($argv[$nextIdx])) {
                            if ($flag === 'n') $n     = (int)$argv[$nextIdx];
                            else               $bytes = (int)$argv[$nextIdx];
                            $skipNext = true;
                        }
                    } elseif (preg_match('/^n(\d+)$/', $flag, $m)) {
                        $n = (int)$m[1];
                    } elseif (preg_match('/^c(\d+)$/', $flag, $m)) {
                        $bytes = (int)$m[1];
                    } elseif (strpos($flag, 'q') !== false) { $quiet   = true; }
                    elseif (strpos($flag, 'v') !== false) { $verbose = true; }
                    elseif (is_numeric($flag))             { $n = (int)$flag; }
                } else {
                    $headfiles[] = $a;
                }
            }
            if (empty($headfiles)) err('head: missing operand');
            $multiFile = count($headfiles) > 1;
            $outParts  = [];
            foreach ($headfiles as $hf) {
                $target = res_path($hf);
                if (!isset($_SESSION['fs'][$target])) { err('head: cannot open \'' . $hf . '\' for reading: No such file or directory'); }
                if ($_SESSION['fs'][$target]['type'] === 'dir') { err('head: error reading \'' . $hf . '\': Is a directory'); }
                $content = $_SESSION['fs'][$target]['content'] ?? '';
                if ($bytes >= 0) {
                    $result = substr($content, 0, $bytes);
                } else {
                    $lines  = explode("\n", $content);
                    $result = implode("\n", array_slice($lines, 0, $n));
                }
                $showHeader = ($multiFile && !$quiet) || $verbose;
                if ($showHeader) {
                    if (!empty($outParts)) $outParts[] = '';
                    $outParts[] = '==> ' . $hf . ' <==';
                }
                $outParts[] = $result;
            }
            out(implode("\n", $outParts));
        }

    // tail
    case 'tail':
        // Usage: tail [-n N] [-c N] [-f] [-q] [-v] FILE [FILE...]
        // -n N   : output last N lines (default 10); +N = from line N onwards
        // -c N   : output last N bytes instead of lines; +N = from byte N onwards
        // -f     : simulated follow (shows last 10 lines + note)
        // -q     : never print file headers
        // -v     : always print file headers
        {
            $n         = 10;
            $bytes     = -1;
            $follow    = false;
            $fromStart = false;   // when N is prefixed with +
            $quiet     = false;
            $verbose   = false;
            $tailfiles = [];
            $skipNext  = false;
            foreach ($argv as $idx => $a) {
                if ($skipNext) { $skipNext = false; continue; }
                if ($a[0] === '-' && strlen($a) > 1) {
                    $flag = ltrim($a, '-');
                    if ($flag === 'n' || $flag === 'c') {
                        $nextIdx = array_search($a, $argv) + 1;
                        if (isset($argv[$nextIdx])) {
                            $val = $argv[$nextIdx];
                            if ($val[0] === '+') { $fromStart = true; $val = substr($val, 1); }
                            if (is_numeric($val)) {
                                if ($flag === 'n') $n     = (int)$val;
                                else               $bytes = (int)$val;
                            }
                            $skipNext = true;
                        }
                    } elseif (preg_match('/^n\+(\d+)$/', $flag, $m)) {
                        $n = (int)$m[1]; $fromStart = true;
                    } elseif (preg_match('/^n(\d+)$/', $flag, $m)) {
                        $n = (int)$m[1];
                    } elseif (preg_match('/^c\+(\d+)$/', $flag, $m)) {
                        $bytes = (int)$m[1]; $fromStart = true;
                    } elseif (preg_match('/^c(\d+)$/', $flag, $m)) {
                        $bytes = (int)$m[1];
                    } elseif (strpos($flag, 'f') !== false) { $follow  = true; }
                    elseif (strpos($flag, 'q') !== false) { $quiet   = true; }
                    elseif (strpos($flag, 'v') !== false) { $verbose = true; }
                    elseif (is_numeric($flag))             { $n = (int)$flag; }
                } else {
                    $tailfiles[] = $a;
                }
            }
            if (empty($tailfiles)) err('tail: missing operand');
            $multiFile = count($tailfiles) > 1;
            $outParts  = [];
            foreach ($tailfiles as $tf) {
                $target = res_path($tf);
                if (!isset($_SESSION['fs'][$target])) { err('tail: cannot open \'' . $tf . '\' for reading: No such file or directory'); }
                if ($_SESSION['fs'][$target]['type'] === 'dir') { err('tail: error reading \'' . $tf . '\': Is a directory'); }
                $content = $_SESSION['fs'][$target]['content'] ?? '';
                if ($bytes >= 0) {
                    $result = $fromStart
                        ? substr($content, $bytes - 1)
                        : substr($content, -$bytes);
                } else {
                    $lines = explode("\n", $content);
                    // strip trailing empty element from files ending with \n
                    if (end($lines) === '') array_pop($lines);
                    $result = $fromStart
                        ? implode("\n", array_slice($lines, $n - 1))
                        : implode("\n", array_slice($lines, -$n));
                }
                $showHeader = ($multiFile && !$quiet) || $verbose;
                if ($showHeader) {
                    if (!empty($outParts)) $outParts[] = '';
                    $outParts[] = '==> ' . $tf . ' <==';
                }
                $outParts[] = $result;
                if ($follow) {
                    $outParts[] = '';
                    $outParts[] = '(tail -f: static filesystem — showing last ' . $n . ' lines)';
                }
            }
            out(implode("\n", $outParts));
        }

    // rmdir
    case 'rmdir':
        if ($args === '') err('rmdir: missing operand');
        $target = res_path($args);
        if (!isset($_SESSION['fs'][$target])) err('rmdir: failed to remove \'' . $args . '\': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] !== 'dir') err('rmdir: failed to remove \'' . $args . '\': Not a directory');
        // check empty: any key that starts with target/ is a child
        $prefix = rtrim($target, '/') . '/';
        foreach (array_keys($_SESSION['fs']) as $k) {
            if (strpos($k, $prefix) === 0) err('rmdir: failed to remove \'' . $args . '\': Directory not empty');
        }
        unset($_SESSION['fs'][$target]);
        out('');

    // du
    case 'du': {
        // Usage: du [-s] [-h] [PATH...]
        $showHuman = (strpos($args, 'h') !== false);
        $summarise = (strpos($args, 's') !== false);
        $paths = [];
        foreach ($argv as $a) {
            if ($a[0] !== '-') $paths[] = $a;
        }
        if (empty($paths)) $paths = [$_SESSION['cwd']];

        $fmtSize = function(int $bytes) use ($showHuman): string {
            if (!$showHuman) {
                // output in 1K-blocks (like du default)
                return (string)max(4, (int)ceil($bytes / 1024) * 4);
            }
            if ($bytes >= 1073741824) return number_format($bytes/1073741824, 1) . 'G';
            if ($bytes >= 1048576)    return number_format($bytes/1048576,    1) . 'M';
            if ($bytes >= 1024)       return number_format($bytes/1024,       1) . 'K';
            return $bytes . 'B';
        };

        $lines = [];
        foreach ($paths as $rawPath) {
            $base = res_path($rawPath);
            if (!isset($_SESSION['fs'][$base])) {
                err('du: cannot access \'' . $rawPath . '\': No such file or directory');
            }
            // walk all nodes under $base
            $prefix = rtrim($base, '/');
            $dirTotals = [$base => 0];
            foreach ($_SESSION['fs'] as $p => $node) {
                if ($p !== $base && strpos($p, $prefix . '/') !== 0) continue;
                if ($node['type'] === 'file') {
                    $sz = strlen($node['content'] ?? '');
                    // add to every ancestor dir total
                    $cur = dirname($p);
                    while (true) {
                        if (!isset($dirTotals[$cur])) $dirTotals[$cur] = 0;
                        $dirTotals[$cur] += $sz;
                        if ($cur === $base || $cur === '/' || $cur === '') break;
                        $cur = dirname($cur);
                    }
                } elseif ($node['type'] === 'dir') {
                    if (!isset($dirTotals[$p])) $dirTotals[$p] = 0;
                }
            }
            if ($summarise) {
                $total = $dirTotals[$base] ?? 0;
                $lines[] = $fmtSize($total) . "\t" . $rawPath;
            } else {
                // output deepest dirs first, then parent
                $sorted = array_keys($dirTotals);
                rsort($sorted);
                foreach ($sorted as $d) {
                    $rel = ($d === $base) ? $rawPath : $rawPath . substr($d, strlen($prefix));
                    $lines[] = $fmtSize($dirTotals[$d]) . "\t" . $rel;
                }
            }
        }
        out(implode("\n", $lines));
    }

    // chmod / chown — cosmetic: accept args, echo nothing
    case 'chmod':
        if (count($argv) < 2) err('chmod: missing operand');
        out('');

    case 'chown':
        if ($user !== 'root') { err('chown: changing ownership: Operation not permitted'); break; }
        if (count($argv) < 2) err('chown: missing operand');
        out('');

    // diff
    case 'diff': {
        // Usage: diff [-u] [-i] FILE1 FILE2
        $flags    = '';
        $diffargs = [];
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $diffargs[] = $a; }
        }
        if (count($diffargs) < 2) err("diff: missing operand\nUsage: diff [OPTION]... FILES");
        $unified  = (strpos($flags, 'u') !== false);
        $ignCase  = (strpos($flags, 'i') !== false);

        $f1 = res_path($diffargs[0]);
        $f2 = res_path($diffargs[1]);
        if (!isset($_SESSION['fs'][$f1])) err('diff: ' . $diffargs[0] . ': No such file or directory');
        if (!isset($_SESSION['fs'][$f2])) err('diff: ' . $diffargs[1] . ': No such file or directory');
        if ($_SESSION['fs'][$f1]['type'] === 'dir') err('diff: ' . $diffargs[0] . ': Is a directory');
        if ($_SESSION['fs'][$f2]['type'] === 'dir') err('diff: ' . $diffargs[1] . ': Is a directory');

        $c1 = $_SESSION['fs'][$f1]['content'] ?? '';
        $c2 = $_SESSION['fs'][$f2]['content'] ?? '';
        if ($ignCase) { $c1 = strtolower($c1); $c2 = strtolower($c2); }

        if ($c1 === $c2) { out(''); }   // identical — no output

        $lines1 = explode("\n", $c1);
        $lines2 = explode("\n", $c2);
        // strip trailing empty from files ending \n
        if (end($lines1) === '') array_pop($lines1);
        if (end($lines2) === '') array_pop($lines2);

        // simple LCS-based diff
        $n1 = count($lines1);
        $n2 = count($lines2);

        // Build edit script using Myers-style forward scan (basic implementation)
        // For simplicity: produce unified or normal diff via tracking changed ranges
        $hunks = [];
        $i = 0; $j = 0;
        while ($i < $n1 || $j < $n2) {
            if ($i < $n1 && $j < $n2 && $lines1[$i] === $lines2[$j]) {
                $i++; $j++; continue;
            }
            // find the extents of this changed block
            $i0 = $i; $j0 = $j;
            // advance until we find a common line or exhaust both
            while ($i < $n1 || $j < $n2) {
                $found = false;
                for ($di = 0; $di <= min(5, $n1-$i); $di++) {
                    for ($dj = 0; $dj <= min(5, $n2-$j); $dj++) {
                        if ($di===0 && $dj===0) continue;
                        if (($i+$di < $n1 || $di===0) && ($j+$dj < $n2 || $dj===0)) {
                            $li = ($i+$di < $n1) ? $lines1[$i+$di] : null;
                            $lj = ($j+$dj < $n2) ? $lines2[$j+$dj] : null;
                            if ($li !== null && $lj !== null && $li === $lj) {
                                $i += $di; $j += $dj; $found = true; break 2;
                            }
                        }
                    }
                }
                if (!$found) {
                    if ($i < $n1) $i++;
                    if ($j < $n2) $j++;
                }
            }
            $hunks[] = ['i0'=>$i0,'i1'=>$i,'j0'=>$j0,'j1'=>$j];
        }

        if (empty($hunks)) { out(''); }

        $out = [];
        if ($unified) {
            $mt1 = isset($_SESSION['fs'][$f1]['mtime']) ? date('Y-m-d H:i:s', $_SESSION['fs'][$f1]['mtime']) : '1970-01-01 00:00:00';
            $mt2 = isset($_SESSION['fs'][$f2]['mtime']) ? date('Y-m-d H:i:s', $_SESSION['fs'][$f2]['mtime']) : '1970-01-01 00:00:00';
            $out[] = '--- ' . $diffargs[0] . "\t" . $mt1;
            $out[] = '+++ ' . $diffargs[1] . "\t" . $mt2;
            $ctx = 3;
            foreach ($hunks as $h) {
                $a1 = max(0, $h['i0']-$ctx); $a2 = min($n1, $h['i1']+$ctx);
                $b1 = max(0, $h['j0']-$ctx); $b2 = min($n2, $h['j1']+$ctx);
                $out[] = '@@ -' . ($a1+1) . ',' . ($a2-$a1) . ' +' . ($b1+1) . ',' . ($b2-$b1) . ' @@';
                for ($k=$a1; $k<$h['i0']; $k++) $out[] = ' ' . $lines1[$k];
                for ($k=$h['i0']; $k<$h['i1']; $k++) $out[] = '-' . $lines1[$k];
                for ($k=$h['j0']; $k<$h['j1']; $k++) $out[] = '+' . $lines2[$k];
                for ($k=$h['i1']; $k<$a2; $k++) $out[] = ' ' . $lines1[$k];
            }
        } else {
            foreach ($hunks as $h) {
                $r1 = ($h['i0']+1) . (($h['i1']-$h['i0']>1) ? ',' . $h['i1'] : '');
                $r2 = ($h['j0']+1) . (($h['j1']-$h['j0']>1) ? ',' . $h['j1'] : '');
                if ($h['i0'] === $h['i1'])      { $out[] = $h['i0'] . 'a' . $r2; }
                elseif ($h['j0'] === $h['j1'])  { $out[] = $r1 . 'd' . $h['j0']; }
                else                            { $out[] = $r1 . 'c' . $r2; }
                for ($k=$h['i0']; $k<$h['i1']; $k++) $out[] = '< ' . $lines1[$k];
                if ($h['i0'] !== $h['i1'] && $h['j0'] !== $h['j1']) $out[] = '---';
                for ($k=$h['j0']; $k<$h['j1']; $k++) $out[] = '> ' . $lines2[$k];
            }
        }
        out(implode("\n", $out));
    }

    // more / less
    case 'more':
    case 'less':
        if ($args === '') err($cmd . ': missing operand — try \'' . $cmd . ' <file>\'');
        // strip any leading flags (e.g. less -N)
        $pagerfile = '';
        foreach ($argv as $a) {
            if ($a[0] !== '-') { $pagerfile = $a; break; }
        }
        if ($pagerfile === '') err($cmd . ': missing filename');
        $target = res_path($pagerfile);
        if (!isset($_SESSION['fs'][$target])) err($cmd . ': ' . $pagerfile . ': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] === 'dir') err($cmd . ': ' . $pagerfile . ': Is a directory');
        $content = $_SESSION['fs'][$target]['content'] ?? '';
        // return pager payload — JS will handle rendering + scrolling
        echo json_encode([
            'pager'    => $content,
            'pagercmd' => $cmd,
            'filename' => basename($target),
        ]);
        exit;

    // sort
    case 'sort': {
        // Usage: sort [-r] [-n] [-u] [-k N] [-t SEP] [-f] [FILE...]
        // Flags
        $reverse   = false;
        $numeric   = false;
        $unique    = false;
        $ignCase   = false;
        $field     = null;   // 1-based field index (-k)
        $fieldSep  = null;   // field separator (-t)
        $files     = [];

        $i = 0;
        while ($i < count($argv)) {
            $a = $argv[$i];
            if ($a === '-r')                    { $reverse  = true; }
            elseif ($a === '-n')                { $numeric  = true; }
            elseif ($a === '-u')                { $unique   = true; }
            elseif ($a === '-f')                { $ignCase  = true; }
            elseif ($a === '-rn' || $a === '-nr') { $reverse = true; $numeric = true; }
            elseif ($a === '-ru' || $a === '-ur') { $reverse = true; $unique  = true; }
            elseif ($a === '-nu' || $a === '-un') { $numeric = true; $unique  = true; }
            elseif (preg_match('/^-k(\d+)$/', $a, $m))            { $field = (int)$m[1]; }
            elseif ($a === '-k' && isset($argv[$i+1]))             { $field = (int)$argv[++$i]; }
            elseif (preg_match('/^-t(.)$/', $a, $m))              { $fieldSep = $m[1]; }
            elseif ($a === '-t' && isset($argv[$i+1]))             { $fieldSep = $argv[++$i]; }
            elseif ($a[0] !== '-')              { $files[] = $a; }
            $i++;
        }

        // collect lines from file(s)
        $lines = [];
        if (empty($files)) {
            err('sort: no input — reading from stdin not supported; provide a file');
        }
        foreach ($files as $f) {
            $path = res_path($f);
            if (!isset($_SESSION['fs'][$path]))
                err('sort: cannot read \'' . $f . '\': No such file or directory');
            if ($_SESSION['fs'][$path]['type'] === 'dir')
                err('sort: read failed \'' . $f . '\': Is a directory');
            $content = $_SESSION['fs'][$path]['content'] ?? '';
            $fileLines = explode("\n", $content);
            // remove trailing empty line from files ending in \n
            if (end($fileLines) === '') array_pop($fileLines);
            $lines = array_merge($lines, $fileLines);
        }

        // sort comparator
        usort($lines, function($a, $b) use ($numeric, $ignCase, $field, $fieldSep) {
            $va = $a; $vb = $b;
            // field extraction
            if ($field !== null) {
                $sep  = $fieldSep ?? null;
                $partsA = $sep ? explode($sep, $a) : preg_split('/\s+/', ltrim($a));
                $partsB = $sep ? explode($sep, $b) : preg_split('/\s+/', ltrim($b));
                $va = $partsA[$field - 1] ?? '';
                $vb = $partsB[$field - 1] ?? '';
            }
            if ($ignCase) { $va = strtolower($va); $vb = strtolower($vb); }
            if ($numeric) {
                $na = (float)$va; $nb = (float)$vb;
                return $na <=> $nb;
            }
            return strcmp($va, $vb);
        });

        if ($reverse) $lines = array_reverse($lines);
        if ($unique)  $lines = array_values(array_unique($lines));

        out(implode("\n", $lines));
    }

    // uniq
    case 'uniq': {
        // Usage: uniq [-c] [-d] [-u] [-i] [FILE]
        // -c   prefix lines with occurrence count
        // -d   only print duplicate lines (lines that appear more than once)
        // -u   only print unique lines (lines that appear exactly once)
        // -i   ignore case when comparing
        $count     = false;
        $dupOnly   = false;
        $uniqOnly  = false;
        $ignCase   = false;
        $uniqfile  = '';

        foreach ($argv as $a) {
            if ($a[0] === '-' && strlen($a) > 1) {
                $flags = ltrim($a, '-');
                if (strpos($flags, 'c') !== false) $count    = true;
                if (strpos($flags, 'd') !== false) $dupOnly  = true;
                if (strpos($flags, 'u') !== false) $uniqOnly = true;
                if (strpos($flags, 'i') !== false) $ignCase  = true;
            } else {
                $uniqfile = $a;
            }
        }

        if ($uniqfile === '') {
            err('uniq: no input — reading from stdin not supported; provide a file');
        }

        $path = res_path($uniqfile);
        if (!isset($_SESSION['fs'][$path]))
            err('uniq: ' . $uniqfile . ': No such file or directory');
        if ($_SESSION['fs'][$path]['type'] === 'dir')
            err('uniq: ' . $uniqfile . ': Is a directory');

        $content = $_SESSION['fs'][$path]['content'] ?? '';
        $lines   = explode("\n", $content);
        // strip trailing empty line from files ending in \n
        if (end($lines) === '') array_pop($lines);

        if (empty($lines)) { out(''); }

        // Run-length encode adjacent identical lines (optionally case-insensitive)
        $runs = [];
        foreach ($lines as $line) {
            $key = $ignCase ? strtolower($line) : $line;
            if (!empty($runs) && $runs[count($runs)-1]['key'] === $key) {
                $runs[count($runs)-1]['count']++;
            } else {
                $runs[] = ['line' => $line, 'key' => $key, 'count' => 1];
            }
        }

        $out = [];
        foreach ($runs as $run) {
            if ($dupOnly  && $run['count'] < 2) continue;
            if ($uniqOnly && $run['count'] > 1) continue;
            if ($count) {
                $out[] = sprintf('%7d %s', $run['count'], $run['line']);
            } else {
                $out[] = $run['line'];
            }
        }

        out(implode("\n", $out));
    }
}
