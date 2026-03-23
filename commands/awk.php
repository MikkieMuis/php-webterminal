<?php
//  awk command — simulated awk interpreter
//  Receives: $cmd, $args, $argv, $user, $body, $stdin  (from terminal.php scope)

switch ($cmd) {

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
}
