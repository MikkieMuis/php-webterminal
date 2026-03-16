<?php
//  archive commands: zip, unzip, tar
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // zip
    case 'zip': {
        // Usage: zip [-r] archive.zip file1 [file2 ...]
        if (empty($argv)) err("zip: missing operand\nUsage: zip [-r] archive.zip file1 [file2 ...]");
        $flags   = '';
        $zipargs = [];
        foreach ($argv as $a) {
            if ($a[0] === '-') { $flags .= ltrim($a, '-'); }
            else { $zipargs[] = $a; }
        }
        if (count($zipargs) < 2) err('zip: missing source file(s)');
        $recursive  = (strpos($flags, 'r') !== false || strpos($flags, 'R') !== false);
        $archiveName = $zipargs[0];
        $sources     = array_slice($zipargs, 1);
        $archivePath = res_path($archiveName);
        $archiveParent = dirname($archivePath);
        if (!isset($_SESSION['fs'][$archiveParent]) || $_SESSION['fs'][$archiveParent]['type'] !== 'dir') {
            err('zip: cannot create ' . $archiveName . ': No such file or directory');
        }
        // collect entries
        $entries = [];
        $addLines = [];
        foreach ($sources as $src) {
            $srcPath = res_path($src);
            if (!isset($_SESSION['fs'][$srcPath])) {
                err('zip: cannot find or open ' . $src . ': No such file or directory');
            }
            $node = $_SESSION['fs'][$srcPath];
            if ($node['type'] === 'dir') {
                if (!$recursive) err('zip: ' . $src . ' is a directory (use -r to recurse)');
                $prefix = rtrim($srcPath, '/');
                $entries[] = ['name' => ltrim($src, '/'), 'type' => 'dir', 'content' => ''];
                $addLines[] = '  adding: ' . rtrim($src, '/') . '/ (stored 0%)';
                foreach ($_SESSION['fs'] as $p => $n) {
                    if (strpos($p, $prefix . '/') === 0) {
                        $rel  = ltrim(substr($p, strlen($prefix)), '/');
                        $rel  = rtrim($src, '/') . '/' . $rel;
                        $cont = ($n['type'] === 'file') ? ($n['content'] ?? '') : '';
                        $entries[]  = ['name' => $rel, 'type' => $n['type'], 'content' => $cont];
                        $addLines[] = '  adding: ' . $rel . ($n['type'] === 'dir' ? '/ (stored 0%)' : ' (deflated ' . rand(30, 75) . '%)');
                    }
                }
            } else {
                $cont = $node['content'] ?? '';
                $entries[]  = ['name' => basename($srcPath), 'type' => 'file', 'content' => $cont];
                $addLines[] = '  adding: ' . basename($srcPath) . ' (deflated ' . rand(30, 75) . '%)';
            }
        }
        $_SESSION['fs'][$archivePath] = [
            'type'    => 'file',
            'content' => json_encode(['__archive__' => true, 'format' => 'zip', 'entries' => $entries]),
            'mtime'   => time(),
        ];
        out(implode("\n", $addLines));
    }

    // unzip
    case 'unzip': {
        // Usage: unzip [-l] archive.zip [-d destdir]
        if (empty($argv)) err("unzip: missing operand\nUsage: unzip [-l] archive.zip [-d dir]");
        $listOnly = false;
        $destDir  = null;
        $unzipFile = '';
        $skipNext  = false;
        foreach ($argv as $idx => $a) {
            if ($skipNext) { $skipNext = false; continue; }
            if ($a === '-l') { $listOnly = true; }
            elseif ($a === '-d') {
                if (isset($argv[$idx + 1])) { $destDir = $argv[$idx + 1]; $skipNext = true; }
            } elseif ($a[0] !== '-') { $unzipFile = $a; }
        }
        if ($unzipFile === '') err('unzip: missing archive filename');
        $archivePath = res_path($unzipFile);
        if (!isset($_SESSION['fs'][$archivePath])) err('unzip: cannot find or open ' . $unzipFile . ': No such file or directory');
        if ($_SESSION['fs'][$archivePath]['type'] === 'dir') err('unzip: ' . $unzipFile . ': Is a directory');
        $manifest = @json_decode($_SESSION['fs'][$archivePath]['content'] ?? '', true);
        if (!is_array($manifest) || empty($manifest['__archive__']) || ($manifest['format'] ?? '') !== 'zip') {
            err('unzip: ' . $unzipFile . ': end of central directory signature not found');
        }
        $entries = $manifest['entries'] ?? [];
        if ($listOnly) {
            $lines = [];
            $lines[] = sprintf("  %-8s  %-19s   %-s", 'Length', 'Date/Time', 'Name');
            $lines[] = str_repeat('-', 45);
            foreach ($entries as $e) {
                if ($e['type'] === 'dir') continue;
                $sz = strlen($e['content'] ?? '');
                $lines[] = sprintf('  %-8d  %s   %s', $sz, date('m-d-Y H:i'), $e['name']);
            }
            $lines[] = str_repeat('-', 45);
            $fileCount = count(array_filter($entries, fn($e) => $e['type'] === 'file'));
            $lines[] = sprintf('  %-8d                     %d file%s', array_sum(array_map(fn($e) => strlen($e['content'] ?? ''), $entries)), $fileCount, $fileCount === 1 ? '' : 's');
            out(implode("\n", $lines));
        }
        $base = ($destDir !== null) ? res_path($destDir) : dirname($archivePath);
        if (!isset($_SESSION['fs'][$base]) || $_SESSION['fs'][$base]['type'] !== 'dir') {
            err('unzip: cannot create extraction directory: ' . ($destDir ?? '.'));
        }
        $outLines = ['Archive:  ' . $unzipFile];
        foreach ($entries as $e) {
            $target = rtrim($base, '/') . '/' . $e['name'];
            if ($e['type'] === 'dir') {
                $_SESSION['fs'][$target] = ['type' => 'dir', 'mtime' => time()];
                $outLines[] = '   creating: ' . $e['name'] . '/';
            } else {
                $parent = dirname($target);
                if (!isset($_SESSION['fs'][$parent])) {
                    $_SESSION['fs'][$parent] = ['type' => 'dir', 'mtime' => time()];
                }
                $_SESSION['fs'][$target] = ['type' => 'file', 'content' => $e['content'] ?? '', 'mtime' => time()];
                $outLines[] = '  inflating: ' . $e['name'];
            }
        }
        out(implode("\n", $outLines));
    }

    // tar
    case 'tar': {
        // Usage: tar [-c|-x|-t] [-z|-j] [-v] [-f archive] [sources...]
        if (empty($argv)) err("tar: You must specify one of the '-Acdtrux', '--delete' or '--test-label' options\nTry 'tar --help' for more information.");
        // parse flags — support combined e.g. -czf or separate
        $flags    = '';
        $archFile = '';
        $tarSrcs  = [];
        $skipNext = false;
        foreach ($argv as $idx => $a) {
            if ($skipNext) { $skipNext = false; continue; }
            if ($a === '-f') {
                if (isset($argv[$idx + 1])) { $archFile = $argv[$idx + 1]; $skipNext = true; }
            } elseif ($a[0] === '-') {
                $flag = ltrim($a, '-');
                // if flag contains 'f', next non-flag char group after 'f' is the filename
                if (strpos($flag, 'f') !== false) {
                    $fPos = strpos($flag, 'f');
                    $flags .= substr($flag, 0, $fPos);
                    $rest   = substr($flag, $fPos + 1);
                    if ($rest !== '') { $archFile = $rest; }
                    elseif (isset($argv[$idx + 1]) && $argv[$idx + 1][0] !== '-') {
                        $archFile = $argv[$idx + 1];
                        $skipNext = true;
                    }
                } else {
                    $flags .= $flag;
                }
            } else {
                $tarSrcs[] = $a;
            }
        }
        $create   = (strpos($flags, 'c') !== false);
        $extract  = (strpos($flags, 'x') !== false);
        $list     = (strpos($flags, 't') !== false);
        $verbose  = (strpos($flags, 'v') !== false);

        if (!$create && !$extract && !$list) {
            err("tar: You must specify one of the '-Acdtrux', '--delete' or '--test-label' options\nTry 'tar --help' for more information.");
        }
        if ($archFile === '') err('tar: Archive file must be specified');

        $archivePath = res_path($archFile);

        if ($create) {
            if (empty($tarSrcs)) err('tar: Cowardly refusing to create an empty archive');
            $archParent = dirname($archivePath);
            if (!isset($_SESSION['fs'][$archParent]) || $_SESSION['fs'][$archParent]['type'] !== 'dir') {
                err('tar: ' . $archFile . ': Cannot open: No such file or directory');
            }
            $entries  = [];
            $outLines = [];
            foreach ($tarSrcs as $src) {
                $srcPath = res_path($src);
                if (!isset($_SESSION['fs'][$srcPath])) {
                    err('tar: ' . $src . ': Cannot stat: No such file or directory');
                }
                $node = $_SESSION['fs'][$srcPath];
                if ($node['type'] === 'dir') {
                    $entries[] = ['name' => rtrim($src, '/'), 'type' => 'dir', 'content' => ''];
                    if ($verbose) $outLines[] = rtrim($src, '/') . '/';
                    $prefix = rtrim($srcPath, '/');
                    foreach ($_SESSION['fs'] as $p => $n) {
                        if (strpos($p, $prefix . '/') === 0) {
                            $rel  = rtrim($src, '/') . '/' . ltrim(substr($p, strlen($prefix)), '/');
                            $cont = ($n['type'] === 'file') ? ($n['content'] ?? '') : '';
                            $entries[]  = ['name' => $rel, 'type' => $n['type'], 'content' => $cont];
                            if ($verbose) $outLines[] = $rel . ($n['type'] === 'dir' ? '/' : '');
                        }
                    }
                } else {
                    $cont = $node['content'] ?? '';
                    $entries[]  = ['name' => basename($srcPath), 'type' => 'file', 'content' => $cont];
                    if ($verbose) $outLines[] = basename($srcPath);
                }
            }
            $format = (strpos($flags, 'j') !== false) ? 'tar.bz2' : 'tar.gz';
            $_SESSION['fs'][$archivePath] = [
                'type'    => 'file',
                'content' => json_encode(['__archive__' => true, 'format' => $format, 'entries' => $entries]),
                'mtime'   => time(),
            ];
            out($verbose ? implode("\n", $outLines) : '');
        }

        if ($extract || $list) {
            if (!isset($_SESSION['fs'][$archivePath])) err('tar: ' . $archFile . ': Cannot open: No such file or directory');
            if ($_SESSION['fs'][$archivePath]['type'] === 'dir') err('tar: ' . $archFile . ': Is a directory');
            $manifest = @json_decode($_SESSION['fs'][$archivePath]['content'] ?? '', true);
            if (!is_array($manifest) || empty($manifest['__archive__'])) {
                err('tar: ' . $archFile . ': Cannot open: Unrecognized archive format');
            }
            $entries  = $manifest['entries'] ?? [];
            $outLines = [];
            $base = dirname($archivePath);
            foreach ($entries as $e) {
                if ($list) {
                    $sz   = ($e['type'] === 'file') ? strlen($e['content'] ?? '') : 0;
                    $name = $e['name'] . ($e['type'] === 'dir' ? '/' : '');
                    $outLines[] = $verbose
                        ? sprintf('%s root/root %8d %s %s', ($e['type'] === 'dir' ? 'drwxr-xr-x' : '-rw-r--r--'), $sz, date('Y-m-d H:i'), $name)
                        : $name;
                } else {
                    $target = rtrim($base, '/') . '/' . $e['name'];
                    if ($e['type'] === 'dir') {
                        $_SESSION['fs'][$target] = ['type' => 'dir', 'mtime' => time()];
                    } else {
                        $parent = dirname($target);
                        if (!isset($_SESSION['fs'][$parent])) {
                            $_SESSION['fs'][$parent] = ['type' => 'dir', 'mtime' => time()];
                        }
                        $_SESSION['fs'][$target] = ['type' => 'file', 'content' => $e['content'] ?? '', 'mtime' => time()];
                    }
                    if ($verbose) $outLines[] = $e['name'] . ($e['type'] === 'dir' ? '/' : '');
                }
            }
            out(implode("\n", $outLines));
        }
    }
}
