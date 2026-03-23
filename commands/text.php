<?php
//  text-processing commands: sort, uniq, cut, tr
//  Moved from filesystem.php (sort/uniq/cut/tr/find).
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
}
