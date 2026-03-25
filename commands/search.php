<?php
//  search commands: grep, head, tail, diff, find
//  Includes helper functions: grep_collect_files(), glob_to_pcre()
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

// Collect files for grep: resolve paths; if -r, collect all files under dir
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

// Convert shell glob pattern to PCRE (only * and ?)
function glob_to_pcre(string $glob): string {
    $re = preg_quote($glob, '/');
    $re = str_replace('\*', '.*', $re);
    $re = str_replace('\?', '.', $re);
    return '/^' . $re . '$/i';
}

switch ($cmd) {

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

        // collect files to search; fall back to stdin if available
        if (empty($targets)) {
            if ($recursive) {
                $targets = [$_SESSION['cwd']];
            } elseif ($stdin !== null) {
                // grep on piped stdin — match directly, no file collection needed
                $stdinLines = explode("\n", $stdin);
                $out = [];
                foreach ($stdinLines as $lnum => $line) {
                    $matches = (bool)preg_match($pcre, $line);
                    if ($invert) $matches = !$matches;
                    if ($matches) {
                        if ($countOnly) { /* counted below */ }
                        elseif ($lineNums) $out[] = ($lnum + 1) . ':' . $line;
                        else               $out[] = $line;
                    }
                }
                if ($countOnly) out((string)count($out));
                else out(implode("\n", $out));
            } else {
                err('grep: no file operand — reading from stdin is not supported in this terminal');
            }
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
            if (empty($headfiles)) {
                if ($stdin !== null) {
                    $content = $stdin;
                    if ($bytes >= 0) { out(substr($content, 0, $bytes)); }
                    else { $ls = explode("\n", $content); out(implode("\n", array_slice($ls, 0, $n))); }
                }
                err('head: missing operand');
            }
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
            if (empty($tailfiles)) {
                if ($stdin !== null) {
                    $ls = explode("\n", $stdin);
                    if ($bytes >= 0) { out(substr($stdin, max(0, strlen($stdin) - $bytes))); }
                    else { out(implode("\n", array_slice($ls, -$n))); }
                }
                err('tail: missing operand');
            }
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
            $out[] = "\x1b[1m--- " . $diffargs[0] . "\t" . $mt1 . "\x1b[0m";
            $out[] = "\x1b[1m+++ " . $diffargs[1] . "\t" . $mt2 . "\x1b[0m";
            $ctx = 3;
            foreach ($hunks as $h) {
                $a1 = max(0, $h['i0']-$ctx); $a2 = min($n1, $h['i1']+$ctx);
                $b1 = max(0, $h['j0']-$ctx); $b2 = min($n2, $h['j1']+$ctx);
                $out[] = "\x1b[0;36m@@ -" . ($a1+1) . ',' . ($a2-$a1) . ' +' . ($b1+1) . ',' . ($b2-$b1) . " @@\x1b[0m";
                for ($k=$a1; $k<$h['i0']; $k++) $out[] = ' ' . $lines1[$k];
                for ($k=$h['i0']; $k<$h['i1']; $k++) $out[] = "\x1b[0;31m-" . $lines1[$k] . "\x1b[0m";
                for ($k=$h['j0']; $k<$h['j1']; $k++) $out[] = "\x1b[0;32m+" . $lines2[$k] . "\x1b[0m";
                for ($k=$h['i1']; $k<$a2; $k++) $out[] = ' ' . $lines1[$k];
            }
        } else {
            foreach ($hunks as $h) {
                $r1 = ($h['i0']+1) . (($h['i1']-$h['i0']>1) ? ',' . $h['i1'] : '');
                $r2 = ($h['j0']+1) . (($h['j1']-$h['j0']>1) ? ',' . $h['j1'] : '');
                if ($h['i0'] === $h['i1'])      { $out[] = "\x1b[0;36m" . $h['i0'] . 'a' . $r2 . "\x1b[0m"; }
                elseif ($h['j0'] === $h['j1'])  { $out[] = "\x1b[0;36m" . $r1 . 'd' . $h['j0'] . "\x1b[0m"; }
                else                            { $out[] = "\x1b[0;36m" . $r1 . 'c' . $r2 . "\x1b[0m"; }
                for ($k=$h['i0']; $k<$h['i1']; $k++) $out[] = "\x1b[0;31m< " . $lines1[$k] . "\x1b[0m";
                if ($h['i0'] !== $h['i1'] && $h['j0'] !== $h['j1']) $out[] = '---';
                for ($k=$h['j0']; $k<$h['j1']; $k++) $out[] = "\x1b[0;32m> " . $lines2[$k] . "\x1b[0m";
            }
        }
        out(implode("\n", $out));
    }

    // find
    case 'find': {
        // Usage: find [PATH] [EXPRESSION...]
        // Supported tests: -name, -type (f/d), -maxdepth, -mindepth, -size (+/-N[ckMG])
        // Supported actions: -print (default), -delete
        // Supported logic: -not / !, -and / -a, -or / -o  (implicit -and between tests)

        // Parse argv
        $searchRoot = null;   // starting path(s)
        $namePattern = null;  // -name PATTERN (shell glob → PCRE)
        $typeFilter  = null;  // 'f' or 'd'
        $maxDepth    = PHP_INT_MAX;
        $minDepth    = 0;
        $sizeTest    = null;  // ['op'=>'+'/'-'/'=', 'bytes'=>N]
        $doDelete    = false;
        $doNot       = false; // flip next test
        $searchRoots = [];

        $i = 0;
        while ($i < count($argv)) {
            $a = $argv[$i];
            switch ($a) {
                case '-name':
                    $namePattern = isset($argv[$i+1]) ? trim($argv[++$i], '"\'') : '';
                    break;
                case '-type':
                    $typeFilter = isset($argv[$i+1]) ? $argv[++$i] : '';
                    break;
                case '-maxdepth':
                    $maxDepth = isset($argv[$i+1]) ? max(0, (int)$argv[++$i]) : 0;
                    break;
                case '-mindepth':
                    $minDepth = isset($argv[$i+1]) ? max(0, (int)$argv[++$i]) : 0;
                    break;
                case '-size':
                    if (isset($argv[$i+1])) {
                        $sv = $argv[++$i];
                        $op = '=';
                        if ($sv[0] === '+') { $op = '+'; $sv = substr($sv, 1); }
                        elseif ($sv[0] === '-') { $op = '-'; $sv = substr($sv, 1); }
                        $unit = strtolower(substr($sv, -1));
                        $num  = (float)$sv;
                        $mult = 512; // default: 512-byte blocks
                        if ($unit === 'c') $mult = 1;
                        elseif ($unit === 'k') $mult = 1024;
                        elseif ($unit === 'm') $mult = 1048576;
                        elseif ($unit === 'g') $mult = 1073741824;
                        $sizeTest = ['op' => $op, 'bytes' => (int)($num * $mult)];
                    }
                    break;
                case '-delete':
                    $doDelete = true;
                    break;
                case '-print':
                    break; // default action — ignore
                case '-not': case '!':
                    // simple toggle; we track it per-iteration — apply to namePattern/typeFilter check
                    $doNot = !$doNot;
                    $i++;
                    continue 2;
                case '-and': case '-a': case '-or': case '-o':
                    break; // we don't support short-circuit logic fully, just skip
                default:
                    // if it doesn't start with - it's a path argument
                    if ($a[0] !== '-') {
                        $searchRoots[] = $a;
                    }
                    break;
            }
            $i++;
        }

        if (empty($searchRoots)) {
            $searchRoots[] = '.';
        }

        $namePcre = ($namePattern !== null) ? glob_to_pcre($namePattern) : null;

        // Walk the virtual filesystem under each root
        $results = [];
        foreach ($searchRoots as $rawRoot) {
            $home = ($user === 'root') ? '/root' : '/home/' . $user;
            $rootPath = res_path(str_replace('~', $home, $rawRoot));
            if (!isset($_SESSION['fs'][$rootPath])) {
                err('find: \'' . $rawRoot . '\': No such file or directory');
            }
            $rootDepth = substr_count(rtrim($rootPath, '/'), '/');

            foreach ($_SESSION['fs'] as $p => $node) {
                // must be at or under rootPath
                if ($p !== $rootPath && strpos($p, rtrim($rootPath, '/') . '/') !== 0) continue;

                $depth = substr_count(rtrim($p, '/'), '/') - $rootDepth;

                if ($depth < $minDepth || $depth > $maxDepth) continue;

                // -type test
                $typeOk = true;
                if ($typeFilter !== null) {
                    if ($typeFilter === 'f') $typeOk = ($node['type'] === 'file');
                    elseif ($typeFilter === 'd') $typeOk = ($node['type'] === 'dir');
                }

                // -name test
                $nameOk = true;
                if ($namePcre !== null) {
                    $nameOk = (bool)preg_match($namePcre, basename($p));
                }

                // -size test
                $sizeOk = true;
                if ($sizeTest !== null) {
                    $fileBytes = isset($node['content']) ? strlen($node['content']) : 0;
                    if ($sizeTest['op'] === '+') $sizeOk = ($fileBytes > $sizeTest['bytes']);
                    elseif ($sizeTest['op'] === '-') $sizeOk = ($fileBytes < $sizeTest['bytes']);
                    else $sizeOk = ($fileBytes === $sizeTest['bytes']);
                }

                $match = $typeOk && $nameOk && $sizeOk;
                if ($doNot) $match = !$match;

                if (!$match) continue;

                if ($doDelete) {
                    if ($p !== $rootPath) unset($_SESSION['fs'][$p]);
                } else {
                    // format output path relative to the search root arg
                    $relPath = ($rawRoot === '.' || $rawRoot === '')
                        ? '.' . substr($p, strlen(rtrim($rootPath, '/')))
                        : rtrim($rawRoot, '/') . substr($p, strlen(rtrim($rootPath, '/')));
                    $results[] = $relPath;
                }
            }
        }

        out(implode("\n", $results));
    }
}
