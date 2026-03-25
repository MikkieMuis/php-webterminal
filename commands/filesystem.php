<?php
//  filesystem commands: ls, cd, mkdir, rmdir, touch, rm, cat, wc, cp, mv,
//                       du, chmod, chown, ln, more, less
//  Search commands (grep, head, tail, diff, find) live in commands/search.php
//  Text processing (sort, uniq, cut, tr, awk, sed) live in commands/text.php
//  Archive commands (zip, unzip, tar) live in commands/archive.php
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

// Return ANSI-coloured name for ls output.
// Dirs → bold blue, symlinks → cyan, executables → green, rest → plain.
function ls_color(string $name, string $type, string $path): string {
    $ESC = "\x1b";
    if ($type === 'dir') {
        return $ESC . '[1;34m' . $name . $ESC . '[0m';
    }
    // symlink: name suffix convention — strip the ~link suffix for display
    if (substr($name, -5) === '~link') {
        return $ESC . '[0;36m' . substr($name, 0, -5) . $ESC . '[0m';
    }
    // executables: paths under bin/sbin dirs, or .sh extension
    $execDirs = ['/bin/', '/sbin/', '/usr/bin/', '/usr/sbin/', '/usr/local/bin/', '/usr/libexec/'];
    foreach ($execDirs as $d) {
        if (strpos($path, $d) === 0) {
            return $ESC . '[0;32m' . $name . $ESC . '[0m';
        }
    }
    if (substr($name, -3) === '.sh') {
        return $ESC . '[0;32m' . $name . $ESC . '[0m';
    }
    return $name;
}

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
            $colored = ls_color(basename($target), 'file', $target);
            out($long ? sprintf('-rw-r--r--  1 root root %6d  %s  %s', $sz, $mt, $colored) : $colored);
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
            $isLink    = (substr($name, -5) === '~link');
            $dispName  = $isLink ? substr($name, 0, -5) : $name;
            if (!$all && $dispName[0] === '.') continue;
            $nodeType  = $isLink ? 'symlink' : $node['type'];
            $perm      = $nodeType === 'dir' ? 'drwxr-xr-x' : ($nodeType === 'symlink' ? 'lrwxrwxrwx' : '-rw-r--r--');
            $size      = isset($node['content']) ? strlen($node['content']) : 4096;
            $mtime_raw = isset($node['mtime']) ? $node['mtime'] : 1741507200;
            $mtime     = date('M d H:i', $mtime_raw);
            $linkTarget = $isLink ? ($node['content'] ?? '') : '';
            $colored   = ls_color($name, $nodeType, $p);
            $entries[] = ['name'=>$dispName,'colored'=>$colored,'perm'=>$perm,'size'=>$size,'mtime'=>$mtime,'mtime_raw'=>$mtime_raw,'type'=>$nodeType,'linktarget'=>$linkTarget];
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
            // use raw names for column-width calculation (ANSI codes inflate strlen)
            $names    = array_map(function($e){ return $e['name']; }, $entries);
            $colored  = array_map(function($e){ return $e['colored']; }, $entries);
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
                    // pad using raw name length so columns stay aligned
                    $pad = $isLast ? 0 : ($colWidth - strlen($names[$idx]));
                    $parts[] = $colored[$idx] . str_repeat(' ', max(0, $pad));
                }
                $lines[] = implode('', $parts);
            }
            out(implode("\n", $lines));
        }
        $lines = ['total ' . (count($entries) * 8)];
        foreach ($entries as $e) {
            $suffix = ($e['type'] === 'symlink' && $e['linktarget'] !== '') ? ' ' . $e['linktarget'] : '';
            $lines[] = sprintf('%s  2 root root %6d  %s  %s%s', $e['perm'], $e['size'], $e['mtime'], $e['colored'], $suffix);
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
        if (!can_write($target, $user)) err('mkdir: cannot create directory \'' . $args . '\': Permission denied');
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
        if (!can_write($target, $user)) err('touch: cannot touch \'' . $args . '\': Permission denied');
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
        if (!can_write($path, $user)) err('rm: cannot remove \'' . $target . '\': Permission denied');
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
        if ($args === '' && $stdin !== null) out($stdin);
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
        if ($wcfile === '' && $stdin !== null) {
            $content = $stdin;
        } elseif ($wcfile === '') {
            err('wc: missing operand');
        } else {
            $target = res_path($wcfile);
            if (!isset($_SESSION['fs'][$target])) err('wc: ' . $wcfile . ': No such file or directory');
            if ($_SESSION['fs'][$target]['type'] === 'dir') err('wc: ' . $wcfile . ': Is a directory');
            $content = $_SESSION['fs'][$target]['content'] ?? '';
        }
        $name    = $wcfile !== '' ? basename($wcfile) : '';
        $lines   = $content === '' ? 0 : substr_count($content, "\n") + (substr($content, -1) !== "\n" ? 1 : 0);
        $words   = $content === '' ? 0 : str_word_count($content);
        $bytes   = strlen($content);
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
        if (!can_write($dest, $user)) err('cp: cannot create \'' . $cpargs[1] . '\': Permission denied');
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
        if (!can_write($src, $user)) err('mv: cannot move \'' . $mvargs[0] . '\': Permission denied');
        // if dest is an existing directory, move into it
        if (isset($_SESSION['fs'][$dest]) && $_SESSION['fs'][$dest]['type'] === 'dir') {
            $dest = rtrim($dest, '/') . '/' . basename($src);
        }
        $destParent = dirname($dest);
        if (!isset($_SESSION['fs'][$destParent]) || $_SESSION['fs'][$destParent]['type'] !== 'dir') {
            err('mv: cannot move \'' . $mvargs[0] . '\' to \'' . $mvargs[1] . '\': No such file or directory');
        }
        if (!can_write($dest, $user)) err('mv: cannot move \'' . $mvargs[0] . '\' to \'' . $mvargs[1] . '\': Permission denied');
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

    // rmdir
    case 'rmdir':
        if ($args === '') err('rmdir: missing operand');
        $target = res_path($args);
        if (!isset($_SESSION['fs'][$target])) err('rmdir: failed to remove \'' . $args . '\': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] !== 'dir') err('rmdir: failed to remove \'' . $args . '\': Not a directory');
        if (!can_write($target, $user)) err('rmdir: failed to remove \'' . $args . '\': Permission denied');
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

    // more / less
     case 'more':
     case 'less':
         // strip any leading flags (e.g. less -N)
         $pagerfile = '';
         foreach ($argv as $a) {
             if ($a[0] !== '-') { $pagerfile = $a; break; }
         }
         // if no file given, fall back to stdin (pipe support)
         if ($pagerfile === '') {
             if ($stdin !== null) {
                 echo json_encode([
                     'pager'    => $stdin,
                     'pagercmd' => $cmd,
                     'filename' => 'stdin',
                 ]);
                 exit;
             }
             err($cmd . ': missing operand — try \'' . $cmd . ' <file>\'');
         }
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

    // ln
    case 'ln': {
        // Only symbolic links are supported: ln -s TARGET LINK
        $flags  = '';
        $lnargs = [];
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $lnargs[] = $a; }
        }
        if (strpos($flags, 's') === false) {
            err('ln: hard links are not supported in this terminal — use ln -s');
        }
        if (count($lnargs) < 2) {
            err('ln: missing operand' . (count($lnargs) === 1 ? ' after \'' . $lnargs[0] . '\'' : ''));
        }
        $target   = $lnargs[0];           // what the symlink points to
        $linkPath = res_path($lnargs[1]); // where the symlink is created
        // if linkPath is an existing directory, create link inside it
        if (isset($_SESSION['fs'][$linkPath]) && $_SESSION['fs'][$linkPath]['type'] === 'dir') {
            $linkPath = rtrim($linkPath, '/') . '/' . basename($target);
        }
        $linkKey = $linkPath . '~link';
        if (isset($_SESSION['fs'][$linkPath]) || isset($_SESSION['fs'][$linkKey])) {
            err('ln: failed to create symbolic link \'' . $lnargs[1] . '\': File exists');
        }
        $parent = dirname($linkPath);
        if (!isset($_SESSION['fs'][$parent]) || $_SESSION['fs'][$parent]['type'] !== 'dir') {
            err('ln: failed to create symbolic link \'' . $lnargs[1] . '\': No such file or directory');
        }
        if (!can_write($linkPath, $user)) {
            err('ln: failed to create symbolic link \'' . $lnargs[1] . '\': Permission denied');
        }
        $_SESSION['fs'][$linkKey] = ['type' => 'file', 'content' => '-> ' . $target, 'mtime' => time()];
        out('');
    }
}
