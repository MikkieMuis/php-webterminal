<?php
//  sed command — simulated sed stream editor
//  Receives: $cmd, $args, $argv, $user, $body, $stdin  (from terminal.php scope)

switch ($cmd) {

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
