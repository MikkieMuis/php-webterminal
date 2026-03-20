<?php
//  text-processing commands: sort, uniq, cut, tr, find, awk, sed
//  Moved from filesystem.php (sort/uniq/cut/tr/find) and newly implemented (awk/sed).
//  Receives: $cmd, $args, $argv, $user, $body, $stdin  (from terminal.php scope)

switch ($cmd) {

    // sort
    case 'sort': {
        // Usage: sort [-r] [-n] [-u] [-k N] [-t SEP] [-f] [FILE...]
        $reverse   = false;
        $numeric   = false;
        $unique    = false;
        $ignCase   = false;
        $field     = null;
        $fieldSep  = null;
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

        $lines = [];
        if (empty($files)) {
            if ($stdin !== null) {
                $lines = explode("\n", $stdin);
                if (end($lines) === '') array_pop($lines);
            } else {
                err('sort: no input — reading from stdin not supported; provide a file');
            }
        }
        foreach ($files as $f) {
            $path = res_path($f);
            if (!isset($_SESSION['fs'][$path]))
                err('sort: cannot read \'' . $f . '\': No such file or directory');
            if ($_SESSION['fs'][$path]['type'] === 'dir')
                err('sort: read failed \'' . $f . '\': Is a directory');
            $content = $_SESSION['fs'][$path]['content'] ?? '';
            $fileLines = explode("\n", $content);
            if (end($fileLines) === '') array_pop($fileLines);
            $lines = array_merge($lines, $fileLines);
        }

        usort($lines, function($a, $b) use ($numeric, $ignCase, $field, $fieldSep) {
            $va = $a; $vb = $b;
            if ($field !== null) {
                $sep    = $fieldSep ?? null;
                $partsA = $sep ? explode($sep, $a) : preg_split('/\s+/', ltrim($a));
                $partsB = $sep ? explode($sep, $b) : preg_split('/\s+/', ltrim($b));
                $va = $partsA[$field - 1] ?? '';
                $vb = $partsB[$field - 1] ?? '';
            }
            if ($ignCase) { $va = strtolower($va); $vb = strtolower($vb); }
            if ($numeric) { return (float)$va <=> (float)$vb; }
            return strcmp($va, $vb);
        });

        if ($reverse) $lines = array_reverse($lines);
        if ($unique)  $lines = array_values(array_unique($lines));

        out(implode("\n", $lines));
    }

    // uniq
    case 'uniq': {
        // Usage: uniq [-c] [-d] [-u] [-i] [FILE]
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
            if ($stdin !== null) { $content = $stdin; }
            else { err('uniq: no input — reading from stdin not supported; provide a file'); }
        } else {
            $path = res_path($uniqfile);
            if (!isset($_SESSION['fs'][$path]))
                err('uniq: ' . $uniqfile . ': No such file or directory');
            if ($_SESSION['fs'][$path]['type'] === 'dir')
                err('uniq: ' . $uniqfile . ': Is a directory');
            $content = $_SESSION['fs'][$path]['content'] ?? '';
        }
        $lines = explode("\n", $content);
        if (end($lines) === '') array_pop($lines);
        if (empty($lines)) { out(''); }

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
            $out[] = $count ? sprintf('%7d %s', $run['count'], $run['line']) : $run['line'];
        }
        out(implode("\n", $out));
    }

    // cut
    case 'cut': {
        // Flags: -d DELIM, -f FIELDS, -c CHARS, -s
        $delim    = "\t";
        $fields   = null;
        $chars    = null;
        $suppress = false;
        $cutfile  = '';

        $i = 0;
        while ($i < count($argv)) {
            $a = $argv[$i];
            if ($a === '-d' || $a === '--delimiter') {
                $delim = isset($argv[$i+1]) ? $argv[$i+1] : "\t";
                $delim = str_replace('\t', "\t", $delim);
                $i += 2; continue;
            }
            if (preg_match('/^-d(.+)$/', $a, $m)) { $delim = str_replace('\t', "\t", $m[1]); $i++; continue; }
            if ($a === '-f' || $a === '--fields') {
                $fspec  = isset($argv[$i+1]) ? $argv[$i+1] : '';
                $fields = [];
                foreach (explode(',', $fspec) as $part) {
                    if (strpos($part, '-') !== false) {
                        [$lo, $hi] = explode('-', $part, 2);
                        $lo = ($lo === '') ? 1 : (int)$lo;
                        $hi = ($hi === '') ? 999 : (int)$hi;
                        for ($n = $lo; $n <= $hi; $n++) $fields[] = $n;
                    } else { $fields[] = (int)$part; }
                }
                $i += 2; continue;
            }
            if (preg_match('/^-f(.+)$/', $a, $m)) {
                $fspec = $m[1]; $fields = [];
                foreach (explode(',', $fspec) as $part) {
                    if (strpos($part, '-') !== false) {
                        [$lo, $hi] = explode('-', $part, 2);
                        $lo = ($lo === '') ? 1 : (int)$lo;
                        $hi = ($hi === '') ? 999 : (int)$hi;
                        for ($n = $lo; $n <= $hi; $n++) $fields[] = $n;
                    } else { $fields[] = (int)$part; }
                }
                $i++; continue;
            }
            if ($a === '-c' || $a === '--characters') {
                $cspec = isset($argv[$i+1]) ? $argv[$i+1] : '';
                $chars = [];
                foreach (explode(',', $cspec) as $part) {
                    if (strpos($part, '-') !== false) {
                        [$lo, $hi] = explode('-', $part, 2);
                        $lo = ($lo === '') ? 1 : (int)$lo;
                        $hi = ($hi === '') ? 999 : (int)$hi;
                        for ($n = $lo; $n <= $hi; $n++) $chars[] = $n;
                    } else { $chars[] = (int)$part; }
                }
                $i += 2; continue;
            }
            if (preg_match('/^-c(.+)$/', $a, $m)) {
                $cspec = $m[1]; $chars = [];
                foreach (explode(',', $cspec) as $part) {
                    if (strpos($part, '-') !== false) {
                        [$lo, $hi] = explode('-', $part, 2);
                        $lo = ($lo === '') ? 1 : (int)$lo;
                        $hi = ($hi === '') ? 999 : (int)$hi;
                        for ($n = $lo; $n <= $hi; $n++) $chars[] = $n;
                    } else { $chars[] = (int)$part; }
                }
                $i++; continue;
            }
            if ($a === '-s' || $a === '--only-delimited') { $suppress = true; $i++; continue; }
            if ($a[0] !== '-') { $cutfile = $a; }
            $i++;
        }

        if ($fields === null && $chars === null)
            err('cut: you must specify a list of bytes, characters, or fields');

        if ($cutfile !== '') {
            $path = res_path($cutfile);
            if (!isset($_SESSION['fs'][$path])) err('cut: ' . $cutfile . ': No such file or directory');
            if ($_SESSION['fs'][$path]['type'] === 'dir') err('cut: ' . $cutfile . ': Is a directory');
            $content = $_SESSION['fs'][$path]['content'] ?? '';
        } elseif ($stdin !== null) {
            $content = $stdin;
        } else {
            err('cut: no input — provide a file or pipe input');
        }

        $lines  = explode("\n", $content);
        if (end($lines) === '') array_pop($lines);
        $result = [];
        foreach ($lines as $line) {
            if ($chars !== null) {
                $out_chars = '';
                foreach ($chars as $pos) {
                    if ($pos >= 1 && $pos <= strlen($line)) $out_chars .= $line[$pos-1];
                }
                $result[] = $out_chars;
            } else {
                if (strpos($line, $delim) === false) {
                    if ($suppress) continue;
                    $result[] = $line;
                } else {
                    $parts    = explode($delim, $line);
                    $selected = [];
                    foreach ($fields as $f) {
                        if (isset($parts[$f-1])) $selected[] = $parts[$f-1];
                    }
                    $result[] = implode($delim, $selected);
                }
            }
        }
        out(implode("\n", $result));
    }

    // tr
    case 'tr': {
        // Usage: tr [OPTION]... SET1 [SET2]
        $delete     = false;
        $squeeze    = false;
        $complement = false;
        $sets       = [];

        foreach ($argv as $a) {
            if ($a === '-d' || $a === '--delete')            { $delete = true; continue; }
            if ($a === '-s' || $a === '--squeeze-repeats')   { $squeeze = true; continue; }
            if ($a === '-c' || $a === '-C' || $a === '--complement') { $complement = true; continue; }
            if (preg_match('/^-[dscC]+$/', $a)) {
                if (strpos($a, 'd') !== false) $delete = true;
                if (strpos($a, 's') !== false) $squeeze = true;
                if (strpos($a, 'c') !== false || strpos($a, 'C') !== false) $complement = true;
                continue;
            }
            $sets[] = $a;
        }

        $expand_set = function(string $s): array {
            $s = str_replace('\n', "\n", $s);
            $s = str_replace('\t', "\t", $s);
            $s = str_replace('\r', "\r", $s);
            $chars = [];
            $len = strlen($s);
            $i = 0;
            while ($i < $len) {
                if ($i+2 < $len && $s[$i+1] === '-') {
                    $lo = ord($s[$i]); $hi = ord($s[$i+2]);
                    if ($lo <= $hi) { for ($c = $lo; $c <= $hi; $c++) $chars[] = chr($c); }
                    $i += 3;
                } else { $chars[] = $s[$i]; $i++; }
            }
            return $chars;
        };

        if ($stdin !== null) { $content = $stdin; }
        else { err('tr: no input — tr reads from stdin; pipe input to it'); }

        $set1 = isset($sets[0]) ? $expand_set($sets[0]) : [];
        $set2 = isset($sets[1]) ? $expand_set($sets[1]) : [];

        if ($delete) {
            $del_chars = array_flip($set1);
            $out = '';
            for ($i = 0; $i < strlen($content); $i++) {
                $c = $content[$i];
                if (!isset($del_chars[$c])) $out .= $c;
            }
            $content = $out;
        } else {
            if (!empty($set1) && !empty($set2)) {
                $last = end($set2);
                while (count($set2) < count($set1)) $set2[] = $last;
                $map = [];
                foreach ($set1 as $idx => $ch) $map[$ch] = $set2[$idx];
                $out = '';
                for ($i = 0; $i < strlen($content); $i++) {
                    $c = $content[$i];
                    $out .= isset($map[$c]) ? $map[$c] : $c;
                }
                $content = $out;
            }
        }

        if ($squeeze && !empty($set2)) {
            $sq_chars = array_flip($set2);
            $out = ''; $prev = '';
            for ($i = 0; $i < strlen($content); $i++) {
                $c = $content[$i];
                if ($c === $prev && isset($sq_chars[$c])) continue;
                $out .= $c; $prev = $c;
            }
            $content = $out;
        } elseif ($squeeze && $delete && !empty($set1)) {
            $sq_chars = array_flip($set1);
            $out = ''; $prev = '';
            for ($i = 0; $i < strlen($content); $i++) {
                $c = $content[$i];
                if ($c === $prev && isset($sq_chars[$c])) continue;
                $out .= $c; $prev = $c;
            }
            $content = $out;
        }

        out(rtrim($content, "\n"));
    }

    // find
    case 'find': {
        // Usage: find [PATH] [EXPRESSION...]
        // Supported: -name, -type (f/d), -maxdepth, -mindepth, -size, -delete, -print
        $namePattern = null;
        $typeFilter  = null;
        $maxDepth    = PHP_INT_MAX;
        $minDepth    = 0;
        $sizeTest    = null;
        $doDelete    = false;
        $doNot       = false;
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
                        $mult = 512;
                        if ($unit === 'c') $mult = 1;
                        elseif ($unit === 'k') $mult = 1024;
                        elseif ($unit === 'm') $mult = 1048576;
                        elseif ($unit === 'g') $mult = 1073741824;
                        $sizeTest = ['op' => $op, 'bytes' => (int)($num * $mult)];
                    }
                    break;
                case '-delete': $doDelete = true; break;
                case '-print':  break;
                case '-not': case '!':
                    $doNot = !$doNot; $i++; continue 2;
                case '-and': case '-a': case '-or': case '-o': break;
                default:
                    if ($a[0] !== '-') $searchRoots[] = $a;
                    break;
            }
            $i++;
        }

        if (empty($searchRoots)) $searchRoots[] = '.';

        function glob_to_pcre(string $glob): string {
            $re = preg_quote($glob, '/');
            $re = str_replace('\*', '.*', $re);
            $re = str_replace('\?', '.', $re);
            return '/^' . $re . '$/i';
        }

        $namePcre = ($namePattern !== null) ? glob_to_pcre($namePattern) : null;

        $results = [];
        foreach ($searchRoots as $rawRoot) {
            $home     = ($user === 'root') ? '/root' : '/home/' . $user;
            $rootPath = res_path(str_replace('~', $home, $rawRoot));
            if (!isset($_SESSION['fs'][$rootPath])) {
                err('find: \'' . $rawRoot . '\': No such file or directory');
            }
            $rootDepth = substr_count(rtrim($rootPath, '/'), '/');

            foreach ($_SESSION['fs'] as $p => $node) {
                if ($p !== $rootPath && strpos($p, rtrim($rootPath, '/') . '/') !== 0) continue;
                $depth = substr_count(rtrim($p, '/'), '/') - $rootDepth;
                if ($depth < $minDepth || $depth > $maxDepth) continue;

                $typeOk = true;
                if ($typeFilter !== null) {
                    if ($typeFilter === 'f') $typeOk = ($node['type'] === 'file');
                    elseif ($typeFilter === 'd') $typeOk = ($node['type'] === 'dir');
                }
                $nameOk = ($namePcre !== null) ? (bool)preg_match($namePcre, basename($p)) : true;
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
                    $relPath = ($rawRoot === '.' || $rawRoot === '')
                        ? '.' . substr($p, strlen(rtrim($rootPath, '/')))
                        : rtrim($rawRoot, '/') . substr($p, strlen(rtrim($rootPath, '/')));
                    $results[] = $relPath;
                }
            }
        }
        out(implode("\n", $results));
    }

    // awk
    case 'awk': {
        // Usage: awk [-F SEP] 'PROGRAM' [FILE...]
        // Supported: field printing ($1..$NF, $0), NR, NF, FS, BEGIN/END blocks,
        //            print, printf (basic), if, arithmetic, string functions (length, substr, split, gsub, sub, index, toupper, tolower, sprintf)
        //            Comparison operators, pattern /regex/ { action }

        $fieldSep  = ' ';   // default FS
        $program   = '';
        $awkFiles  = [];
        $assignVars = [];   // -v VAR=val

        $i = 0;
        while ($i < count($argv)) {
            $a = $argv[$i];
            if (($a === '-F' || $a === '--field-separator') && isset($argv[$i+1])) {
                $fieldSep = $argv[++$i];
                $fieldSep = str_replace('\t', "\t", $fieldSep);
            } elseif (preg_match('/^-F(.+)$/', $a, $m)) {
                $fieldSep = str_replace('\t', "\t", $m[1]);
            } elseif ($a === '-v' && isset($argv[$i+1])) {
                $assignVars[] = $argv[++$i];
            } elseif ($a[0] !== '-' && $program === '') {
                $program = $a;
            } elseif ($a[0] !== '-') {
                $awkFiles[] = $a;
            }
            $i++;
        }

        if ($program === '') err('awk: no program specified');

        // get input lines
        $inputLines = [];
        if (empty($awkFiles)) {
            if ($stdin !== null) {
                $inputLines = explode("\n", $stdin);
                if (end($inputLines) === '') array_pop($inputLines);
            } else {
                err('awk: no input — provide a file or pipe input');
            }
        } else {
            foreach ($awkFiles as $f) {
                $path = res_path($f);
                if (!isset($_SESSION['fs'][$path])) err('awk: ' . $f . ': No such file or directory');
                if ($_SESSION['fs'][$path]['type'] === 'dir') err('awk: ' . $f . ': Is a directory');
                $content = $_SESSION['fs'][$path]['content'] ?? '';
                $flines  = explode("\n", $content);
                if (end($flines) === '') array_pop($flines);
                $inputLines = array_merge($inputLines, $flines);
            }
        }

        // Parse -v assignments into initial variables
        $awkVars = [];
        foreach ($assignVars as $av) {
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $av, $m)) {
                $awkVars[$m[1]] = $m[2];
            }
        }

        // Split program into BEGIN, main rules, END
        // Rules format: [/pattern/|condition] { action }  or  BEGIN { } or END { }
        function awk_split_rules(string $prog): array {
            $rules = [];
            $prog  = trim($prog);
            $len   = strlen($prog);
            $i     = 0;
            while ($i < $len) {
                // skip whitespace
                while ($i < $len && ctype_space($prog[$i])) $i++;
                if ($i >= $len) break;

                // read pattern (everything up to opening brace, or the brace itself)
                $pattern = '';
                while ($i < $len && $prog[$i] !== '{') {
                    $pattern .= $prog[$i];
                    $i++;
                }
                $pattern = trim($pattern);

                // read action block (balanced braces)
                $action = '';
                if ($i < $len && $prog[$i] === '{') {
                    $depth = 0;
                    while ($i < $len) {
                        $ch = $prog[$i];
                        if ($ch === '{') $depth++;
                        elseif ($ch === '}') { $depth--; if ($depth === 0) { $i++; break; } }
                        $action .= $ch;
                        $i++;
                    }
                    // strip outer braces
                    $action = trim(substr($action, 1));
                }
                $rules[] = ['pattern' => $pattern, 'action' => $action];
            }
            return $rules;
        }

        $rules = awk_split_rules($program);

        // Evaluate a single awk action against current record state
        // Returns string output (may be multiline)
        function awk_exec(string $action, array &$vars): string {
            $out    = '';
            // Split action into statements on ; or newline (basic)
            $stmts  = preg_split('/;|\n/', $action);
            foreach ($stmts as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') continue;
                $out .= awk_exec_stmt($stmt, $vars);
            }
            return $out;
        }

        function awk_exec_stmt(string $stmt, array &$vars): string {
            // if ( condition ) { ... }   — simplified single-level
            if (preg_match('/^if\s*\((.+?)\)\s*\{(.+?)\}(?:\s*else\s*\{(.+?)\})?$/s', $stmt, $m)) {
                $cond  = awk_eval_expr(trim($m[1]), $vars);
                $then  = $m[2];
                $else  = isset($m[3]) ? $m[3] : '';
                return awk_exec(awk_truthy($cond) ? $then : $else, $vars);
            }
            // if ( condition ) stmt  — no braces
            if (preg_match('/^if\s*\((.+?)\)\s+(.+)$/s', $stmt, $m)) {
                $cond = awk_eval_expr(trim($m[1]), $vars);
                return awk_exec(awk_truthy($cond) ? $m[2] : '', $vars);
            }
            // printf "fmt", args...
            if (preg_match('/^printf\s+(.+)$/s', $stmt, $m)) {
                return awk_printf(trim($m[1]), $vars);
            }
            // print expr, expr, ...
            if (preg_match('/^print\s*(.*)$/s', $stmt, $m)) {
                $exprList = trim($m[1]);
                if ($exprList === '') {
                    return $vars['$0'] . "\n";
                }
                // split on commas not inside strings/parens
                $parts = awk_split_args($exprList);
                $vals  = array_map(function($p) use (&$vars) {
                    return awk_eval_expr(trim($p), $vars);
                }, $parts);
                return implode($vars['OFS'] ?? ' ', $vals) . "\n";
            }
            // variable assignment: VAR = expr  (not == or !=)
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=(?!=)\s*(.+)$/s', $stmt, $m)) {
                $vars[$m[1]] = awk_eval_expr(trim($m[2]), $vars);
                return '';
            }
            // field assignment: $N = expr
            if (preg_match('/^\$(\d+)\s*=(?!=)\s*(.+)$/s', $stmt, $m)) {
                $idx = (int)$m[1];
                $val = awk_eval_expr(trim($m[2]), $vars);
                // rebuild $0 and fields
                $fs = $vars['FS'] ?? ' ';
                $fields = ($fs === ' ') ? preg_split('/\s+/', ltrim($vars['$0'])) : explode($fs, $vars['$0']);
                while (count($fields) < $idx) $fields[] = '';
                $fields[$idx - 1] = $val;
                $vars['$0'] = implode($vars['OFS'] ?? ' ', $fields);
                $vars['NF'] = count($fields);
                foreach ($fields as $fi => $fv) $vars['$' . ($fi+1)] = $fv;
                return '';
            }
            // next — skip to next record (signal via exception-like flag)
            if ($stmt === 'next') { throw new \RuntimeException('awk_next'); }
            // gsub/sub as statement
            if (preg_match('/^(g?sub)\s*\((.+)\)$/s', $stmt, $m)) {
                awk_eval_expr($stmt, $vars);
                return '';
            }
            return '';
        }

        function awk_truthy($val): bool {
            if (is_numeric($val)) return (float)$val != 0;
            return $val !== '' && $val !== '0';
        }

        function awk_split_args(string $s): array {
            $parts  = [];
            $cur    = '';
            $depth  = 0;
            $inStr  = false;
            $strCh  = '';
            for ($i = 0; $i < strlen($s); $i++) {
                $c = $s[$i];
                if ($inStr) {
                    $cur .= $c;
                    if ($c === $strCh && ($i === 0 || $s[$i-1] !== '\\')) $inStr = false;
                } elseif ($c === '"' || $c === "'") {
                    $inStr = true; $strCh = $c; $cur .= $c;
                } elseif ($c === '(') { $depth++; $cur .= $c; }
                elseif ($c === ')') { $depth--; $cur .= $c; }
                elseif ($c === ',' && $depth === 0) { $parts[] = $cur; $cur = ''; }
                else { $cur .= $c; }
            }
            if ($cur !== '') $parts[] = $cur;
            return $parts;
        }

        function awk_printf(string $args, array &$vars): string {
            $parts  = awk_split_args($args);
            $fmt    = awk_eval_expr(trim($parts[0]), $vars);
            $fargs  = array_slice($parts, 1);
            // strip surrounding quotes from fmt
            $fmt = trim($fmt, '"\'');
            // replace awk escape sequences
            $fmt = str_replace('\n', "\n", $fmt);
            $fmt = str_replace('\t', "\t", $fmt);
            // collect evaluated args
            $pargs = array_map(function($p) use (&$vars) { return awk_eval_expr(trim($p), $vars); }, $fargs);
            // basic sprintf-style formatting: %s %d %f %g %-Ns %Nd
            $result = '';
            $ai     = 0;
            $fi     = 0;
            while ($fi < strlen($fmt)) {
                $ch = $fmt[$fi];
                if ($ch === '%' && $fi+1 < strlen($fmt)) {
                    $fi++;
                    $spec = '';
                    while ($fi < strlen($fmt) && !ctype_alpha($fmt[$fi])) { $spec .= $fmt[$fi]; $fi++; }
                    $type = $fi < strlen($fmt) ? $fmt[$fi] : 's';
                    $val  = isset($pargs[$ai]) ? $pargs[$ai] : '';
                    $ai++;
                    $phpFmt = '%' . $spec . $type;
                    if ($type === 's') $result .= sprintf($phpFmt, (string)$val);
                    elseif ($type === 'd') $result .= sprintf($phpFmt, (int)$val);
                    elseif ($type === 'f' || $type === 'g' || $type === 'e') $result .= sprintf($phpFmt, (float)$val);
                    else $result .= sprintf('%s', $val);
                } else {
                    $result .= $ch;
                }
                $fi++;
            }
            return $result;
        }

        function awk_eval_expr(string $expr, array &$vars): string {
            $expr = trim($expr);

            // string literal
            if (preg_match('/^"(.*)"$/s', $expr, $m)) return str_replace(['\n','\t'], ["\n","\t"], $m[1]);
            if (preg_match("/^'(.*)'$/s", $expr, $m)) return $m[1];

            // length(expr)
            if (preg_match('/^length\s*\(([^)]*)\)$/i', $expr, $m)) {
                $inner = trim($m[1]);
                return (string)strlen($inner === '' ? $vars['$0'] : awk_eval_expr($inner, $vars));
            }
            // substr(s, start [, len])
            if (preg_match('/^substr\s*\((.+)\)$/si', $expr, $m)) {
                $p = awk_split_args($m[1]);
                $s = awk_eval_expr(trim($p[0]), $vars);
                $start = isset($p[1]) ? max(1, (int)awk_eval_expr(trim($p[1]), $vars)) - 1 : 0;
                $len   = isset($p[2]) ? (int)awk_eval_expr(trim($p[2]), $vars) : null;
                return $len !== null ? substr($s, $start, $len) : substr($s, $start);
            }
            // index(s, t)
            if (preg_match('/^index\s*\((.+)\)$/si', $expr, $m)) {
                $p = awk_split_args($m[1]);
                $s = awk_eval_expr(trim($p[0]), $vars);
                $t = awk_eval_expr(trim($p[1] ?? ''), $vars);
                $pos = strpos($s, $t);
                return (string)($pos === false ? 0 : $pos + 1);
            }
            // split(s, arr, sep)  — we can't return an array through expr; return count
            if (preg_match('/^split\s*\((.+)\)$/si', $expr, $m)) {
                $p   = awk_split_args($m[1]);
                $s   = awk_eval_expr(trim($p[0]), $vars);
                $sep = isset($p[2]) ? awk_eval_expr(trim($p[2]), $vars) : ($vars['FS'] ?? ' ');
                $parts = ($sep === ' ') ? preg_split('/\s+/', trim($s)) : explode($sep, $s);
                // store in vars as arr[1], arr[2]... using arr name
                $arrName = trim($p[1] ?? 'arr');
                foreach ($parts as $pi => $pv) $vars[$arrName . '[' . ($pi+1) . ']'] = $pv;
                return (string)count($parts);
            }
            // gsub(re, repl [, target])
            if (preg_match('/^gsub\s*\((.+)\)$/si', $expr, $m)) {
                $p    = awk_split_args($m[1]);
                $re   = trim(trim($p[0] ?? ''), '/');
                $repl = awk_eval_expr(trim($p[1] ?? ''), $vars);
                $tgt  = isset($p[2]) ? trim($p[2]) : '$0';
                $src  = awk_eval_expr($tgt, $vars);
                $count = 0;
                $result2 = preg_replace_callback('/' . str_replace('/', '\/', $re) . '/', function($mm) use ($repl, &$count) {
                    $count++;
                    return str_replace('&', $mm[0], $repl);
                }, $src);
                if ($tgt === '$0' || $tgt === '') {
                    $vars['$0'] = $result2;
                } elseif (isset($vars[$tgt])) {
                    $vars[$tgt] = $result2;
                }
                return (string)$count;
            }
            // sub(re, repl [, target])
            if (preg_match('/^sub\s*\((.+)\)$/si', $expr, $m)) {
                $p    = awk_split_args($m[1]);
                $re   = trim(trim($p[0] ?? ''), '/');
                $repl = awk_eval_expr(trim($p[1] ?? ''), $vars);
                $tgt  = isset($p[2]) ? trim($p[2]) : '$0';
                $src  = awk_eval_expr($tgt, $vars);
                $result2 = preg_replace('/' . str_replace('/', '\/', $re) . '/', $repl, $src, 1);
                if ($tgt === '$0' || $tgt === '') $vars['$0'] = $result2;
                elseif (isset($vars[$tgt])) $vars[$tgt] = $result2;
                return '1';
            }
            // toupper / tolower
            if (preg_match('/^toupper\s*\((.+)\)$/si', $expr, $m))
                return strtoupper(awk_eval_expr(trim($m[1]), $vars));
            if (preg_match('/^tolower\s*\((.+)\)$/si', $expr, $m))
                return strtolower(awk_eval_expr(trim($m[1]), $vars));
            // sprintf
            if (preg_match('/^sprintf\s*\((.+)\)$/si', $expr, $m))
                return awk_printf($m[1], $vars);

            // comparison: ==, !=, >=, <=, >, <  (before arithmetic to avoid confusion)
            if (preg_match('/^(.+?)\s*(==|!=|>=|<=|>|<)\s*(.+)$/s', $expr, $m)) {
                $lv = awk_eval_expr(trim($m[1]), $vars);
                $rv = awk_eval_expr(trim($m[3]), $vars);
                $op = $m[2];
                // numeric if both look numeric
                if (is_numeric($lv) && is_numeric($rv)) {
                    $l = (float)$lv; $r = (float)$rv;
                    $res = match($op) { '==' => $l==$r, '!=' => $l!=$r, '>=' => $l>=$r, '<=' => $l<=$r, '>' => $l>$r, '<' => $l<$r };
                } else {
                    $res = match($op) { '==' => $lv===$rv, '!=' => $lv!==$rv, '>=' => $lv>=$rv, '<=' => $lv<=$rv, '>' => $lv>$rv, '<' => $lv<$rv };
                }
                return $res ? '1' : '0';
            }
            // arithmetic: +, -, *, /, %  (right-to-left is fine for basic cases)
            if (preg_match('/^(.+?)\s*(\+\+|--|\+=|-=|\*=|\/=)\s*(.*)$/', $expr, $m) && $m[3] === '') {
                // post-increment/decrement on variable
                $varName = trim($m[1]);
                $old = isset($vars[$varName]) ? (float)$vars[$varName] : 0;
                $vars[$varName] = (string)($m[2] === '++' ? $old+1 : $old-1);
                return (string)$old;
            }
            if (preg_match('/^(.+?)\s*([+\-*\/%])\s*(.+)$/', $expr, $m)) {
                $lv = awk_eval_expr(trim($m[1]), $vars);
                $rv = awk_eval_expr(trim($m[3]), $vars);
                $op = $m[2];
                if (is_numeric($lv) && is_numeric($rv)) {
                    $l = (float)$lv; $r = (float)$rv;
                    if ($op === '+') $res = $l + $r;
                    elseif ($op === '-') $res = $l - $r;
                    elseif ($op === '*') $res = $l * $r;
                    elseif ($op === '/') $res = ($r != 0) ? $l / $r : 0;
                    elseif ($op === '%') $res = fmod($l, $r);
                    else $res = 0;
                    // return int if whole number
                    return (floor($res) == $res) ? (string)(int)$res : (string)$res;
                }
                // string concatenation (awk does this implicitly — only + is arithmetic)
                if ($op === '+') return '0';
                return $lv . $rv;  // implicit concat for other ops on strings
            }

            // field reference: $N or $var
            if (preg_match('/^\$([0-9]+)$/', $expr, $m)) {
                $n = (int)$m[1];
                return $n === 0 ? ($vars['$0'] ?? '') : ($vars['$' . $n] ?? '');
            }
            if (preg_match('/^\$([A-Za-z_][A-Za-z0-9_]*)$/', $expr, $m)) {
                $n = (int)($vars[$m[1]] ?? 0);
                return $n === 0 ? ($vars['$0'] ?? '') : ($vars['$' . $n] ?? '');
            }

            // NF, NR, $NF
            if ($expr === '$NF') {
                $nf = (int)($vars['NF'] ?? 0);
                return $vars['$' . $nf] ?? '';
            }

            // bare number
            if (is_numeric($expr)) return $expr;

            // variable lookup
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_\[\]]*$/', $expr)) {
                return $vars[$expr] ?? '';
            }

            // string concatenation (space between tokens — awk implicit)
            if (preg_match('/^(.+?)\s+(.+)$/', $expr, $m)) {
                return awk_eval_expr($m[1], $vars) . awk_eval_expr($m[2], $vars);
            }

            return $expr;
        }

        function awk_match_pattern(string $pattern, array &$vars): bool {
            if ($pattern === '' || $pattern === 'BEGIN' || $pattern === 'END') return false;
            // /regex/ pattern
            if (preg_match('/^\/(.+)\/$/', $pattern, $m)) {
                $re = '/' . str_replace('/', '\/', $m[1]) . '/';
                return (bool)@preg_match($re, $vars['$0']);
            }
            // expression pattern
            $val = awk_eval_expr($pattern, $vars);
            return awk_truthy($val);
        }

        // Execute BEGIN blocks
        $outputLines = [];
        foreach ($rules as $rule) {
            if ($rule['pattern'] === 'BEGIN') {
                $awkVars['FS']  = $fieldSep;
                $awkVars['OFS'] = ' ';
                $awkVars['NR']  = 0;
                $result = awk_exec($rule['action'], $awkVars);
                if ($result !== '') $outputLines[] = rtrim($result, "\n");
            }
        }

        // Process each input line
        $NR = 0;
        foreach ($inputLines as $line) {
            $NR++;
            $fs = $awkVars['FS'] ?? $fieldSep;
            $fields = ($fs === ' ') ? preg_split('/\s+/', ltrim($line)) : explode($fs, $line);
            // remove empty first element from ltrim split
            if ($fs === ' ' && isset($fields[0]) && $fields[0] === '' && $line !== '') array_shift($fields);

            $awkVars['$0'] = $line;
            $awkVars['NR'] = $NR;
            $awkVars['NF'] = count($fields);
            foreach ($fields as $fi => $fv) $awkVars['$' . ($fi+1)] = $fv;
            // clear leftover fields from previous record
            for ($fi = count($fields)+1; $fi <= 99; $fi++) {
                if (!isset($awkVars['$' . $fi])) break;
                unset($awkVars['$' . $fi]);
            }

            foreach ($rules as $rule) {
                if ($rule['pattern'] === 'BEGIN' || $rule['pattern'] === 'END') continue;
                if ($rule['pattern'] !== '' && !awk_match_pattern($rule['pattern'], $awkVars)) continue;
                try {
                    $result = awk_exec($rule['action'], $awkVars);
                    if ($result !== '') $outputLines[] = rtrim($result, "\n");
                } catch (\RuntimeException $e) {
                    if ($e->getMessage() === 'awk_next') break;
                    throw $e;
                }
            }
        }

        // Execute END blocks
        foreach ($rules as $rule) {
            if ($rule['pattern'] === 'END') {
                $result = awk_exec($rule['action'], $awkVars);
                if ($result !== '') $outputLines[] = rtrim($result, "\n");
            }
        }

        out(implode("\n", $outputLines));
    }

    // sed
    case 'sed': {
        // Usage: sed [-n] [-e SCRIPT] [-i] 's/FIND/REPLACE/[flags]' [FILE...]
        // Supported commands: s (substitute), d (delete), p (print), q (quit),
        //                     = (print line number), y (transliterate), a\ (append), i\ (insert)
        // Flags on s: g (global), i (case-insensitive), p (print if substituted), N (Nth occurrence)

        $suppressDefault = false;  // -n
        $scripts         = [];     // -e SCRIPT or bare script
        $inPlace         = false;  // -i (cosmetic — we update session fs)
        $sedFiles        = [];

        $i = 0;
        while ($i < count($argv)) {
            $a = $argv[$i];
            if ($a === '-n') { $suppressDefault = true; }
            elseif ($a === '-e' && isset($argv[$i+1])) { $scripts[] = $argv[++$i]; }
            elseif (preg_match('/^-e(.+)$/', $a, $m)) { $scripts[] = $m[1]; }
            elseif ($a === '-i' || $a === '--in-place') { $inPlace = true; }
            elseif ($a[0] !== '-' && empty($scripts)) { $scripts[] = $a; }  // bare script
            elseif ($a[0] !== '-') { $sedFiles[] = $a; }
            $i++;
        }

        if (empty($scripts)) err('sed: no script specified');

        $fullScript = implode("\n", $scripts);

        // get input
        if (empty($sedFiles)) {
            if ($stdin !== null) {
                $inputLines = explode("\n", $stdin);
                if (end($inputLines) === '') array_pop($inputLines);
            } else {
                err('sed: no input — provide a file or pipe input');
            }
        } else {
            $inputLines = [];
            foreach ($sedFiles as $f) {
                $path = res_path($f);
                if (!isset($_SESSION['fs'][$path])) err('sed: ' . $f . ': No such file or directory');
                if ($_SESSION['fs'][$path]['type'] === 'dir') err('sed: ' . $f . ': Is a directory');
                $content = $_SESSION['fs'][$path]['content'] ?? '';
                $flines  = explode("\n", $content);
                if (end($flines) === '') array_pop($flines);
                $inputLines = array_merge($inputLines, $flines);
            }
        }

        // Parse sed commands from script
        // Each command: [addr[,addr]] cmd [args]
        // address: N (line number), $ (last), /re/
        function sed_parse_commands(string $script): array {
            $cmds = [];
            $lines = preg_split('/\n|;/', $script);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;

                $addr1 = null; $addr2 = null;
                $i = 0;

                // parse optional address(es)
                $parseAddr = function(string $s, int &$i): ?string {
                    if ($i >= strlen($s)) return null;
                    if ($s[$i] === '$') { $i++; return '$'; }
                    if ($s[$i] === '/') {
                        $i++; $re = '';
                        while ($i < strlen($s) && $s[$i] !== '/') {
                            if ($s[$i] === '\\' && $i+1 < strlen($s)) { $re .= $s[$i+1]; $i += 2; continue; }
                            $re .= $s[$i]; $i++;
                        }
                        if ($i < strlen($s)) $i++; // skip closing /
                        return '/' . $re . '/';
                    }
                    if (ctype_digit($s[$i])) {
                        $num = '';
                        while ($i < strlen($s) && ctype_digit($s[$i])) { $num .= $s[$i]; $i++; }
                        return $num;
                    }
                    return null;
                };

                $a1 = $parseAddr($line, $i);
                if ($a1 !== null) {
                    $addr1 = $a1;
                    // skip whitespace
                    while ($i < strlen($line) && $line[$i] === ' ') $i++;
                    // optional second address
                    if ($i < strlen($line) && $line[$i] === ',') {
                        $i++;
                        while ($i < strlen($line) && $line[$i] === ' ') $i++;
                        $a2 = $parseAddr($line, $i);
                        if ($a2 !== null) $addr2 = $a2;
                    }
                    while ($i < strlen($line) && $line[$i] === ' ') $i++;
                }

                if ($i >= strlen($line)) continue;
                $cmdChar = $line[$i]; $i++;
                $rest = substr($line, $i);

                $cmds[] = ['addr1'=>$addr1, 'addr2'=>$addr2, 'cmd'=>$cmdChar, 'args'=>trim($rest)];
            }
            return $cmds;
        }

        $sedCmds = sed_parse_commands($fullScript);

        // Check if line number matches an address
        function sed_addr_match($addr, int $lineNum, int $totalLines, string $line): bool {
            if ($addr === null) return true;
            if ($addr === '$') return ($lineNum === $totalLines);
            if (is_numeric($addr)) return ($lineNum === (int)$addr);
            if (preg_match('/^\/(.+)\/$/', $addr, $m)) {
                return (bool)@preg_match('/' . str_replace('/', '\/', $m[1]) . '/', $line);
            }
            return false;
        }

        $totalLines  = count($inputLines);
        $outputLines = [];
        $quit        = false;

        foreach ($inputLines as $lineIdx => $line) {
            if ($quit) break;
            $lineNum    = $lineIdx + 1;
            $printLine  = !$suppressDefault;
            $deleted    = false;

            foreach ($sedCmds as $sedCmd) {
                $addr1 = $sedCmd['addr1'];
                $addr2 = $sedCmd['addr2'];

                // determine if this command applies to this line
                $applies = false;
                if ($addr1 === null) {
                    $applies = true;
                } elseif ($addr2 !== null) {
                    // range: addr1,addr2
                    $inRange = sed_addr_match($addr1, $lineNum, $totalLines, $line)
                            || sed_addr_match($addr2, $lineNum, $totalLines, $line);
                    // simplified: check if lineNum is between first match of addr1 and addr2
                    $startMatch = sed_addr_match($addr1, $lineNum, $totalLines, $line);
                    $endMatch   = sed_addr_match($addr2, $lineNum, $totalLines, $line);
                    $applies = $startMatch || ($addr1 !== null && is_numeric($addr1) && $lineNum >= (int)$addr1
                               && ($addr2 === '$' || (is_numeric($addr2) && $lineNum <= (int)$addr2)));
                } else {
                    $applies = sed_addr_match($addr1, $lineNum, $totalLines, $line);
                }

                if (!$applies) continue;

                $cmd  = $sedCmd['cmd'];
                $args = $sedCmd['args'];

                if ($cmd === 'd') {
                    $deleted   = true;
                    $printLine = false;
                    break;
                }

                if ($cmd === 'p') {
                    $outputLines[] = $line;
                }

                if ($cmd === '=') {
                    $outputLines[] = (string)$lineNum;
                }

                if ($cmd === 'q') {
                    $printLine = true;
                    $quit      = true;
                    break;
                }

                if ($cmd === 's') {
                    // parse s/FIND/REPLACE/flags — delimiter can be any char
                    if (strlen($args) < 2) continue;
                    $delim  = $args[0];
                    $dq     = preg_quote($delim, '/');
                    // split on unescaped delimiters: find, replace, flags
                    if (!preg_match('/^' . $dq . '((?:[^' . $dq . '\\\\]|\\\\.)*)' . $dq . '((?:[^' . $dq . '\\\\]|\\\\.)*)' . $dq . '([giIp0-9]*)$/', $args, $sm)) continue;
                    $find    = $sm[1];
                    $replace = $sm[2];
                    $flags   = $sm[3];

                    $global  = (strpos($flags, 'g') !== false);
                    $icase   = (strpos($flags, 'i') !== false || strpos($flags, 'I') !== false);
                    $printSub= (strpos($flags, 'p') !== false);
                    $nthStr  = preg_replace('/[^0-9]/', '', $flags);
                    $nth     = ($nthStr !== '') ? max(1, (int)$nthStr) : 0;

                    // build PCRE
                    $pcre = '/' . str_replace('/', '\/', $find) . '/';
                    if ($icase) $pcre .= 'i';

                    // convert & and \1..\9 in replacement to PHP preg format
                    $phpReplace = str_replace('&', '$0', $replace);
                    $phpReplace = preg_replace('/\\\\([1-9])/', '\$$1', $phpReplace);

                    if (@preg_match($pcre, '') === false) continue; // invalid regex

                    $substituted = false;
                    if ($nth > 0) {
                        // replace Nth occurrence only
                        $count = 0;
                        $newLine = preg_replace_callback(str_replace('/', '\/', $pcre), function($m2) use (&$count, $nth, $phpReplace, &$substituted) {
                            $count++;
                            if ($count === $nth) { $substituted = true; return preg_replace('/\$([0-9])/', $m2[(int)substr('$0',1)] ?? $m2[0], $phpReplace); }
                            return $m2[0];
                        }, $line);
                        if ($newLine !== null) $line = $newLine;
                    } elseif ($global) {
                        $newLine = preg_replace($pcre, $phpReplace, $line);
                        if ($newLine !== $line) $substituted = true;
                        if ($newLine !== null) $line = $newLine;
                    } else {
                        $newLine = preg_replace($pcre, $phpReplace, $line, 1);
                        if ($newLine !== $line) $substituted = true;
                        if ($newLine !== null) $line = $newLine;
                    }

                    if ($substituted && $printSub) $outputLines[] = $line;
                }

                if ($cmd === 'y') {
                    // y/SET1/SET2/ — transliterate
                    if (strlen($args) < 2) continue;
                    $delim2 = $args[0];
                    $dq2 = preg_quote($delim2, '/');
                    if (!preg_match('/^' . $dq2 . '([^' . $dq2 . ']*)' . $dq2 . '([^' . $dq2 . ']*)' . $dq2 . '$/', $args, $ym)) continue;
                    $s1 = $ym[1]; $s2 = $ym[2];
                    $map2 = [];
                    $minLen = min(strlen($s1), strlen($s2));
                    for ($ci = 0; $ci < $minLen; $ci++) $map2[$s1[$ci]] = $s2[$ci];
                    $newLine = '';
                    for ($ci = 0; $ci < strlen($line); $ci++) {
                        $newLine .= isset($map2[$line[$ci]]) ? $map2[$line[$ci]] : $line[$ci];
                    }
                    $line = $newLine;
                }

                if ($cmd === 'a') {
                    // append text after current line
                    $outputLines[] = $line;
                    $outputLines[] = ltrim($args, '\\');
                    $printLine = false;
                    break;
                }

                if ($cmd === 'i') {
                    // insert text before current line
                    $outputLines[] = ltrim($args, '\\');
                }
            }

            if (!$deleted && $printLine) $outputLines[] = $line;
        }

        // -i: write result back to session fs
        if ($inPlace && !empty($sedFiles)) {
            foreach ($sedFiles as $f) {
                $path = res_path($f);
                if (isset($_SESSION['fs'][$path])) {
                    $_SESSION['fs'][$path]['content'] = implode("\n", $outputLines) . "\n";
                    $_SESSION['fs'][$path]['mtime']   = time();
                }
            }
            out('');
        }

        out(implode("\n", $outputLines));
    }
}
