<?php
//  filesystem commands: ls, cd, mkdir, touch, rm, cat, wc, more, less
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
        $target = ($args === '' || $args === '~') ? '/root' : res_path($args);
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
}
