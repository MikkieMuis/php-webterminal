<?php
// ============================================================
//  filesystem commands: ls, cd, mkdir, touch, rm, cat
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)
// ============================================================

switch ($cmd) {

    // ── ls ──
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

    // ── cd ──
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

    // ── mkdir ──
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

    // ── touch ──
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

    // ── rm ──
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

    // ── cat ──
    case 'cat':
        if ($args === '') err('cat: missing operand');
        $target = res_path($args);
        if (!isset($_SESSION['fs'][$target])) err('cat: ' . $args . ': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] === 'dir') err('cat: ' . $args . ': Is a directory');
        out($_SESSION['fs'][$target]['content']);
}
