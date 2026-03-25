<?php
//  system-info commands: whoami, pwd, hostname, uname, uptime, date,
//                        df, free, ps, top, htop, id, env, printenv,
//                        which, exa, fastfetch, neofetch
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // whoami
    case 'whoami':
        out($user);

    // pwd
    case 'pwd':
        out($_SESSION['cwd']);

    // hostname
    case 'hostname':
        out(CONF_HOSTNAME);

    // uname
    case 'uname':
        if (strpos($args, '-a') !== false) {
            out('Linux ' . CONF_HOSTNAME . ' ' . SYS_KERNEL . ' #1 SMP ' . SYS_ARCH . ' ' . SYS_ARCH . ' ' . SYS_ARCH . ' GNU/Linux');
        }
        out('Linux');

    // uptime
    case 'uptime':
        $secs  = time() - $_SESSION['boot'];
        $days  = floor($secs / 86400);
        $hours = floor(($secs % 86400) / 3600);
        $mins  = floor(($secs % 3600)  / 60);
        $load  = number_format(CONF_LOAD_1,  2) . ', '
               . number_format(CONF_LOAD_5,  2) . ', '
               . number_format(CONF_LOAD_15, 2);
        out(sprintf(' %s up %d days, %d:%02d,  1 user,  load average: %s',
            date('H:i:s'), $days, $hours, $mins, $load));

    // date
    case 'date':
        out(date('D M j H:i:s T Y'));

    // df
    case 'df':
        $free  = CONF_DISK_FREE;
        $total = CONF_DISK_TOTAL;
        $used  = CONF_DISK_USED;
        $pct   = round(($used/$total)*100);

        if (strpos($args, '-h') !== false) {
            $fmt = function($b) {
                if ($b >= 1099511627776) return round($b/1099511627776,1).'T';
                if ($b >= 1073741824)    return round($b/1073741824,1).'G';
                if ($b >= 1048576)       return round($b/1048576,1).'M';
                return $b;
            };
            out(
                "Filesystem      Size  Used Avail Use% Mounted on\n"
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sda1', $fmt($total),   $fmt($used),  $fmt($free),  $pct, '/')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdb1', '2.0T',         '1.4T',       '600G',       73,   '/mnt/db')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdc1', '4.0T',         '2.1T',       '1.9T',       53,   '/mnt/backup')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdd1', '500G',         '87G',        '413G',       18,   '/home')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", 'tmpfs',     '7.9G',         '1.2M',       '7.9G',       1,    '/dev/shm')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sda2', '512M',         '42M',        '470M',       9,    '/boot')
            );
        } else {
            out(
                "Filesystem     1K-blocks       Used  Available Use% Mounted on\n"
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sda1', $total/1024,   $used/1024,   $free/1024,   $pct, '/')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdb1', 2147483648,    1468006400,   629145600,    73,   '/mnt/db')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdc1', 4294967296,    2264924160,   1992294400,   53,   '/mnt/backup')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdd1', 524288000,     91750400,     432537600,    18,   '/home')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", 'tmpfs',     8192000,       1204,         8190796,      1,    '/dev/shm')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sda2', 524288,        42984,        481304,       9,    '/boot')
            );
        }

    // free
    case 'free':
        if (strpos($args, '-h') !== false) {
            out("               total        used        free      shared  buff/cache   available\n"
              . "Mem:            15Gi       3.2Gi       8.4Gi        12Mi       3.8Gi        11Gi\n"
              . "Swap:          2.0Gi          0B       2.0Gi");
        } else {
            out("               total        used        free      shared  buff/cache   available\n"
              . "Mem:        16252928     3276800     8847360      12288    4128768   11534336\n"
              . "Swap:        2097152           0     2097152");
        }

    // ps
    case 'ps':
        if (strpos($args, 'aux') !== false) {
            out("USER         PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\n"
              . "root           1  0.0  0.1 169440 11264 ?        Ss   Mar08   0:04 /sbin/init\n"
              . "root         432  0.0  0.1  28356  9832 ?        Ss   Mar08   0:00 /lib/systemd/systemd-journald\n"
              . "root         914  0.0  0.1  15428  8732 ?        Ss   Mar08   0:00 sshd: /usr/sbin/sshd\n"
              . "www-data    1105  0.0  0.3 256440 24688 ?        S    Mar08   0:01 /usr/sbin/apache2\n"
              . "mysql       1212  0.1  1.4 1823440 118344 ?      Sl   Mar08   2:14 /usr/sbin/mysqld\n"
              . "root        2048  0.0  0.0  14532  2048 pts/0    Ss   " . date('H:i') . "   0:00 -bash\n"
              . "root        2091  0.0  0.0  17640  1948 pts/0    R+   " . date('H:i') . "   0:00 ps aux");
        }
        out("  PID TTY          TIME CMD\n 2048 pts/0    00:00:00 bash");

    // top
    case 'top':
        $load   = [CONF_LOAD_1, CONF_LOAD_5, CONF_LOAD_15];
        $upSecs = time() - $_SESSION['boot'];
        $upH    = floor($upSecs/3600);
        $upM    = floor(($upSecs%3600)/60);
        $procs  = [
            ['pid'=>1,    'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>169440,  'res'=>11264, 'shr'=>8192,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:04.12','cmd'=>'systemd'],
            ['pid'=>432,  'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>28356,   'res'=>9832,  'shr'=>7680,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:00.43','cmd'=>'systemd-journald'],
            ['pid'=>914,  'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>15428,   'res'=>8732,  'shr'=>6144,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:00.11','cmd'=>'sshd'],
            ['pid'=>1105, 'user'=>'www-data', 'pr'=>20,'ni'=>0, 'virt'=>256440,  'res'=>24688, 'shr'=>18432, 's'=>'S','cpu'=>0.3,'mem'=>0.3,'time'=>'0:01.77','cmd'=>'apache2'],
            ['pid'=>1212, 'user'=>'mysql',    'pr'=>20,'ni'=>0, 'virt'=>1823440, 'res'=>118344,'shr'=>12288, 's'=>'S','cpu'=>0.7,'mem'=>1.4,'time'=>'2:14.55','cmd'=>'mysqld'],
            ['pid'=>1380, 'user'=>'redis',    'pr'=>20,'ni'=>0, 'virt'=>62840,   'res'=>4096,  'shr'=>2048,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:02.34','cmd'=>'redis-server'],
            ['pid'=>1512, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>11440,   'res'=>2048,  'shr'=>1536,  's'=>'S','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.06','cmd'=>'crond'],
            ['pid'=>1890, 'user'=>'php-fpm',  'pr'=>20,'ni'=>0, 'virt'=>194560,  'res'=>32768, 'shr'=>16384, 's'=>'S','cpu'=>0.1,'mem'=>0.4,'time'=>'0:03.22','cmd'=>'php-fpm: pool www'],
            ['pid'=>2048, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>14532,   'res'=>2048,  'shr'=>1536,  's'=>'S','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.02','cmd'=>'-bash'],
            ['pid'=>2091, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>17640,   'res'=>1948,  'shr'=>1280,  's'=>'R','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.00','cmd'=>'top'],
        ];
        echo json_encode([
            'output' => '',
            'top'    => true,
            'uptime' => sprintf('%d:%02d', $upH, $upM),
            'load'   => [round($load[0],2), round($load[1],2), round($load[2],2)],
            'procs'  => $procs,
            'time'   => date('H:i:s'),
        ]);
        exit;

    // htop
    case 'htop':
        $load   = [CONF_LOAD_1, CONF_LOAD_5, CONF_LOAD_15];
        $upSecs = time() - $_SESSION['boot'];
        $upH    = floor($upSecs/3600);
        $upM    = floor(($upSecs%3600)/60);
        $procs  = [
            ['pid'=>1,    'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>169440,  'res'=>11264, 'shr'=>8192,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:04.12','cmd'=>'systemd'],
            ['pid'=>432,  'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>28356,   'res'=>9832,  'shr'=>7680,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:00.43','cmd'=>'systemd-journald'],
            ['pid'=>914,  'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>15428,   'res'=>8732,  'shr'=>6144,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:00.11','cmd'=>'sshd'],
            ['pid'=>1105, 'user'=>'www-data', 'pr'=>20,'ni'=>0, 'virt'=>256440,  'res'=>24688, 'shr'=>18432, 's'=>'S','cpu'=>0.3,'mem'=>0.3,'time'=>'0:01.77','cmd'=>'apache2'],
            ['pid'=>1212, 'user'=>'mysql',    'pr'=>20,'ni'=>0, 'virt'=>1823440, 'res'=>118344,'shr'=>12288, 's'=>'S','cpu'=>0.7,'mem'=>1.4,'time'=>'2:14.55','cmd'=>'mysqld'],
            ['pid'=>1380, 'user'=>'redis',    'pr'=>20,'ni'=>0, 'virt'=>62840,   'res'=>4096,  'shr'=>2048,  's'=>'S','cpu'=>0.0,'mem'=>0.1,'time'=>'0:02.34','cmd'=>'redis-server'],
            ['pid'=>1512, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>11440,   'res'=>2048,  'shr'=>1536,  's'=>'S','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.06','cmd'=>'crond'],
            ['pid'=>1890, 'user'=>'php-fpm',  'pr'=>20,'ni'=>0, 'virt'=>194560,  'res'=>32768, 'shr'=>16384, 's'=>'S','cpu'=>0.1,'mem'=>0.4,'time'=>'0:03.22','cmd'=>'php-fpm: pool www'],
            ['pid'=>2048, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>14532,   'res'=>2048,  'shr'=>1536,  's'=>'S','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.02','cmd'=>'-bash'],
            ['pid'=>2092, 'user'=>'root',     'pr'=>20,'ni'=>0, 'virt'=>17640,   'res'=>1948,  'shr'=>1280,  's'=>'R','cpu'=>0.0,'mem'=>0.0,'time'=>'0:00.00','cmd'=>'htop'],
        ];
        // memory bars (fake, fixed fractions of total)
        $memTotal  = 15872;   // MiB
        $memUsed   = 3277;
        $swapTotal = 2048;
        $swapUsed  = 0;
        echo json_encode([
            'output'    => '',
            'htop'      => true,
            'uptime'    => sprintf('%d:%02d', $upH, $upM),
            'load'      => [round($load[0],2), round($load[1],2), round($load[2],2)],
            'procs'     => $procs,
            'time'      => date('H:i:s'),
            'memTotal'  => $memTotal,
            'memUsed'   => $memUsed,
            'swapTotal' => $swapTotal,
            'swapUsed'  => $swapUsed,
            'cpuCount'  => 4,
        ]);
        exit;

    // id
    case 'id':
        if ($user === 'root') {
            out('uid=0(root) gid=0(root) groups=0(root),1(bin),2(daemon),3(sys),4(adm),6(disk),10(wheel)');
        } elseif ($user === 'deploy') {
            out('uid=1001(deploy) gid=1001(deploy) groups=1001(deploy),10(wheel),999(docker)');
        } else {
            out('uid=1002(' . $user . ') gid=1002(' . $user . ') groups=1002(' . $user . '),10(wheel)');
        }

    // env / printenv
    case 'env':
    case 'printenv':
        $home = ($user === 'root') ? '/root' : '/home/' . $user;
        $path = ($user === 'root')
            ? 'PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
            : 'PATH=/usr/local/bin:/usr/bin:/bin:/home/' . $user . '/.local/bin';
        out("SHELL=/bin/bash\n"
          . "TERM=xterm-256color\n"
          . "USER=" . $user . "\n"
          . "MAIL=/var/mail/" . $user . "\n"
          . $path . "\n"
          . "PWD=" . $_SESSION['cwd'] . "\n"
          . "LANG=en_US.UTF-8\n"
          . "HOME=" . $home . "\n"
          . "LOGNAME=" . $user . "\n"
          . "HISTSIZE=1000\n"
          . "HISTFILESIZE=2000\n"
          . "LESSOPEN=||/usr/bin/lesspipe %s\n"
          . "DISPLAY=:0\n"
          . "_=/usr/bin/env");

    // which
    case 'which':
        if ($args === '') err('which: missing argument');
        $bins = [
            'bash'=>'/bin/bash','sh'=>'/bin/sh','ls'=>'/bin/ls','cat'=>'/bin/cat',
            'rm'=>'/bin/rm','cp'=>'/bin/cp','mv'=>'/bin/mv','mkdir'=>'/bin/mkdir',
            'touch'=>'/bin/touch','ln'=>'/bin/ln','chmod'=>'/bin/chmod','chown'=>'/bin/chown',
            'grep'=>'/bin/grep','find'=>'/usr/bin/find','awk'=>'/usr/bin/awk',
            'sed'=>'/usr/bin/sed','sort'=>'/usr/bin/sort','uniq'=>'/usr/bin/uniq',
            'wc'=>'/usr/bin/wc','head'=>'/usr/bin/head','tail'=>'/usr/bin/tail',
            'curl'=>'/usr/bin/curl','wget'=>'/usr/bin/wget','ssh'=>'/usr/bin/ssh',
            'scp'=>'/usr/bin/scp','rsync'=>'/usr/bin/rsync',
            'git'=>'/usr/bin/git','php'=>'/usr/bin/php','python3'=>'/usr/bin/python3',
            'perl'=>'/usr/bin/perl','ruby'=>'/usr/bin/ruby','node'=>'/usr/bin/node',
            'npm'=>'/usr/bin/npm','pip3'=>'/usr/bin/pip3',
            'mysql'=>'/usr/bin/mysql','mariadb'=>'/usr/bin/mariadb','redis-cli'=>'/usr/bin/redis-cli',
            'top'=>'/usr/bin/top','htop'=>'/usr/bin/htop','ps'=>'/bin/ps',
            'kill'=>'/bin/kill','df'=>'/bin/df','free'=>'/usr/bin/free',
            'ifconfig'=>'/sbin/ifconfig','ip'=>'/sbin/ip','ping'=>'/usr/bin/ping',
            'netstat'=>'/usr/bin/netstat','ss'=>'/usr/sbin/ss','nmap'=>'/usr/bin/nmap',
            'vim'=>'/usr/bin/vim','nano'=>'/usr/bin/nano','joe'=>'/usr/bin/joe',
            'tar'=>'/bin/tar','gzip'=>'/bin/gzip','zip'=>'/usr/bin/zip',
            'uname'=>'/bin/uname','hostname'=>'/bin/hostname','date'=>'/bin/date',
            'echo'=>'/bin/echo','printf'=>'/usr/bin/printf','env'=>'/usr/bin/env',
            'sudo'=>'/usr/bin/sudo','su'=>'/bin/su','passwd'=>'/usr/bin/passwd',
            'useradd'=>'/usr/sbin/useradd','usermod'=>'/usr/sbin/usermod',
            'crontab'=>'/usr/bin/crontab','systemctl'=>'/usr/bin/systemctl',
            'journalctl'=>'/usr/bin/journalctl','man'=>'/usr/bin/man',
            'dnf'=>'/usr/bin/dnf','yum'=>'/usr/bin/yum','rpm'=>'/usr/bin/rpm',
            'fastfetch'=>'/usr/bin/fastfetch','neofetch'=>'/usr/bin/neofetch',
            'rmdir'=>'/bin/rmdir','du'=>'/usr/bin/du',
            'diff'=>'/usr/bin/diff','unzip'=>'/usr/bin/unzip',
            'base64'=>'/usr/bin/base64','bc'=>'/usr/bin/bc',
            'exa'=>'/usr/bin/exa','firewall-cmd'=>'/usr/bin/firewall-cmd',
            'telnet'=>'/usr/bin/telnet','sendmail'=>'/usr/sbin/sendmail',
            'lsblk'=>'/bin/lsblk','blkid'=>'/sbin/blkid','dmesg'=>'/bin/dmesg',
            'vmstat'=>'/usr/bin/vmstat','iostat'=>'/usr/bin/iostat',
            'hostnamectl'=>'/usr/bin/hostnamectl','timedatectl'=>'/usr/bin/timedatectl',
            'chgrp'=>'/bin/chgrp','logger'=>'/usr/bin/logger',
            'dig'=>'/usr/bin/dig','host'=>'/usr/bin/host',
            'lsof'=>'/usr/sbin/lsof',
        ];
        $results = [];
        foreach (explode(' ', $args) as $w) {
            $w = trim($w);
            if ($w === '') continue;
            if (isset($bins[$w])) $results[] = $bins[$w];
            else { err('/usr/bin/which: no ' . $w . ' in (/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin)'); }
        }
        out(implode("\n", $results));

    // exa
    case 'exa': {
        // Supported flags: --long / -l, --tree / -T, --git, --icons, --all / -a, -h (header)
        $isLong  = preg_match('/\b(--long|-[a-zA-Z]*l[a-zA-Z]*)/', $args) || strpos($args, '--long') !== false
                   || (strpos($args, '-l') !== false);
        $isTree  = strpos($args, '--tree') !== false || strpos($args, '-T') !== false;
        $isAll   = strpos($args, '--all') !== false || strpos($args, '-a') !== false;
        $hasGit  = strpos($args, '--git') !== false;
        $hasIcons= strpos($args, '--icons') !== false;

        // Determine target path
        $targetPath = $_SESSION['cwd'];
        foreach ($argv as $av) {
            if ($av[0] !== '-') { $targetPath = res_path($av); break; }
        }

        $fs = $_SESSION['fs'];
        if (!isset($fs[$targetPath]) || $fs[$targetPath]['type'] !== 'dir') {
            // could be a file
            if (isset($fs[$targetPath])) {
                out(basename($targetPath));
            }
            err('exa: ' . $targetPath . ': No such file or directory');
        }

        $prefix  = rtrim($targetPath, '/');
        $entries = [];
        foreach ($fs as $p => $node) {
            if ($p === $targetPath) continue;
            $dir = dirname($p);
            $parent = ($prefix === '') ? '/' : $prefix;
            if ($dir !== $parent) continue;
            $name  = basename($p);
            if (!$isAll && $name[0] === '.') continue;
            $isDir = ($node['type'] === 'dir');
            $size  = $isDir ? '-' : str_pad(isset($node['content']) ? strlen($node['content']) : 0, 6);
            $mtime = isset($node['mtime']) ? date('Y-m-d H:i', $node['mtime']) : '2026-03-09 08:11';
            $perm  = $isDir ? 'drwxr-xr-x' : '-rw-r--r--';
            $entries[] = [
                'name'  => $name,
                'isDir' => $isDir,
                'size'  => $size,
                'mtime' => $mtime,
                'perm'  => $perm,
            ];
        }

        if (empty($entries)) { out($isLong ? 'total 0' : ''); }
        usort($entries, function($a,$b){ return strcmp($a['name'],$b['name']); });

        if ($isTree) {
            $lines = ["\x1b[1;34m" . basename($targetPath) . "/\x1b[0m"];
            $cnt = count($entries);
            foreach ($entries as $i => $e) {
                $prefix2 = ($i === $cnt - 1) ? '└── ' : '├── ';
                $lines[] = $prefix2 . ($e['isDir']
                    ? "\x1b[1;34m" . $e['name'] . "/\x1b[0m"
                    : $e['name']);
            }
            out(implode("\n", $lines));
        }

        if ($isLong) {
            $header = '';
            if ($hasGit) {
                $header = sprintf("%-10s  %s  %-6s  %-16s  %-4s  %s\n",
                    'Permissions', 'Size', 'User', 'Date Modified', ' Git', 'Name');
            } else {
                $header = sprintf("%-10s  %s  %-6s  %-16s  %s\n",
                    'Permissions', 'Size', 'User', 'Date Modified', 'Name');
            }
            $lines = [$header];
            foreach ($entries as $e) {
                $gitCol = $hasGit ? sprintf('  %-4s', '--') : '';
                $sizeCol = $e['isDir'] ? sprintf('%6s', '-') : sprintf('%6d', is_numeric(trim($e['size'])) ? (int)trim($e['size']) : 0);
                $coloredName = $e['isDir']
                    ? "\x1b[1;34m" . $e['name'] . "/\x1b[0m"
                    : $e['name'];
                $lines[] = sprintf('%-10s  %s  %-6s  %-16s%s  %s',
                    $e['perm'], $sizeCol, 'root', $e['mtime'], $gitCol, $coloredName);
            }
            out(implode("\n", $lines));
        }

        // short format — column layout
        $rawNames = array_map(function($e){ return $e['isDir'] ? $e['name'].'/' : $e['name']; }, $entries);
        $coloredNames = array_map(function($e){
            return $e['isDir']
                ? "\x1b[1;34m" . $e['name'] . "/\x1b[0m"
                : $e['name'];
        }, $entries);
        $maxLen   = max(array_map('strlen', $rawNames));
        $colWidth = $maxLen + 2;
        $numCols  = max(1, (int)floor(($cols ?: 80) / $colWidth));
        $rows     = (int)ceil(count($rawNames) / $numCols);
        $out = [];
        for ($r = 0; $r < $rows; $r++) {
            $parts = [];
            for ($c = 0; $c < $numCols; $c++) {
                $idx = $c * $rows + $r;
                if ($idx >= count($rawNames)) break;
                $isLast2 = ($c === $numCols - 1) || (($idx + $rows) >= count($rawNames));
                $pad = $isLast2 ? 0 : ($colWidth - strlen($rawNames[$idx]));
                $parts[] = $coloredNames[$idx] . str_repeat(' ', max(0, $pad));
            }
            $out[] = implode('', $parts);
        }
        out(implode("\n", $out));
        break;
    }

    // fastfetch / neofetch
    case 'fastfetch':
    case 'neofetch': {
        $upSecs  = time() - $_SESSION['boot'];
        $upDays  = floor($upSecs / 86400);
        $upHours = floor(($upSecs % 86400) / 3600);
        $upMins  = floor(($upSecs % 3600) / 60);
        $upStr   = '';
        if ($upDays  > 0) $upStr .= $upDays  . ' day'  . ($upDays  !== 1 ? 's' : '') . ', ';
        if ($upHours > 0) $upStr .= $upHours . ' hour' . ($upHours !== 1 ? 's' : '') . ', ';
        $upStr .= $upMins . ' min' . ($upMins !== 1 ? 's' : '');

        // Disk — from config, formatted as GiB
        $diskTotal = CONF_DISK_TOTAL;
        $diskUsed  = CONF_DISK_USED;
        $diskPct   = round(($diskUsed / $diskTotal) * 100);
        $fmtGiB = function($b) { return number_format($b / 1073741824, 2) . ' GiB'; };

        // All fake — never derived from real server
        $memUsed  = '3.17 GiB';
        $memTotal = '15.51 GiB';
        $memPct   = '20%';
        $pkgCount = '1121 (rpm)';
        $shell    = 'bash 5.1.8';
        $terminal = 'tmux 3.2a';
        $cpu      = 'Intel(R) Xeon(R) E5-2670 v3 (24) @ 2.300 GHz';
        $gpu      = 'ASPEED Technology, Inc. ASPEED Graphics Family';
        $display  = '1024x768 @ 60 Hz [Built-in]';
        $localip  = '192.168.1.10/24';
        $locale   = 'en_US.UTF-8';

        // AlmaLinux logo with $1–$5 colour placeholders (real fastfetch source)
        // $1=red $2=light-yellow $3=blue $4=light-green $5=cyan
        $logoRaw = [
            '$1         \'c:.                   ',
            '$1        lkkkx, ..       $2..   ,cc,',
            '$1        okkkk:ckkx\'  $2.lxkkx.okkkkd',
            '$1        .:llcokkx\'  $2:kkkxkko:xkkd, ',
            '$1      .xkkkkdood:  $2;kx,  .lkxlll;  ',
            '$1       xkkx.       $2xk\'     xkkkkk:  ',
            '$1       \'xkx.       $2xd      .....,.  ',
            '$3      .. $1:xkl\'     $2:c      ..\'\'..   ',
            '$3    .dkx\'  $1.:ldl:\'. $2\'  $4\':lollldkkxo;',
            '$3  .\'\'lkkko\'                     $4ckkkx.',
            '$3\'xkkkd:kkd.       ..  $5;\'        $4:kkxo.',
            '$3,xkkkd;kk\'      ,d;    $5ld.   $4\':dkd::cc,',
            '$3 .,,.;xkko\'.\';lxo.      $5dx,  $4:kkk\'xkkkkc',
            '$3     \'dkkkkkxo:.        $5;kx  $4.kkk:;xkkd. ',
            '$3       .....   $5.;dk:.   $5lkk.  $4:;,          ',
            '             $5:kkkkkkkdoxkkx               ',
            '              $5,c,,;;;:xkkd.               ',
            '                $5;kkkkl.                   ',
            '                 $5,od;                     ',
        ];

        // Colour map for logo placeholders
        $logoColors = [
            '1' => '#cc3333',
            '2' => '#cccc00',
            '3' => '#3355cc',
            '4' => '#55cc55',
            '5' => '#00bbbb',
        ];

        // Helper: convert a raw logo line (with $N tokens) into an HTML string.
        // Returns [html_string, plain_length] where plain_length is the visible char count.
        $renderLogoLine = function(string $raw) use ($logoColors): array {
            // Split on colour tokens like $1 .. $5
            $parts = preg_split('/(\$[1-5])/', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
            $html       = '';
            $plainLen   = 0;
            $curColor   = '#e0e0e0';
            foreach ($parts as $part) {
                if (preg_match('/^\$([1-5])$/', $part, $m)) {
                    $curColor = $logoColors[$m[1]];
                } else {
                    $plainLen += strlen($part);
                    $escaped   = htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $html     .= '<span style="color:' . $curColor . '">' . $escaped . '</span>';
                }
            }
            return [$html, $plainLen];
        };

        // Pre-render logo lines and find the maximum plain width
        $renderedLogo = [];
        $logoWidth    = 0;
        foreach ($logoRaw as $line) {
            [$html, $len] = $renderLogoLine($line);
            $renderedLogo[] = [$html, $len];
            if ($len > $logoWidth) $logoWidth = $len;
        }

        // Info block — header red, separator white, keys yellow, values white
        $header     = htmlspecialchars($user . '@' . CONF_HOSTNAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sepPlain   = str_repeat('-', strlen($user . '@' . CONF_HOSTNAME));
        $infoRaw = [
            ['header', $user . '@' . CONF_HOSTNAME],
            ['sep',    $sepPlain],
            ['kv',     'OS',       CONF_OS . ' x86_64'],
            ['kv',     'Kernel',   'Linux ' . CONF_KERNEL],
            ['kv',     'Uptime',   $upStr],
            ['kv',     'Packages', $pkgCount],
            ['kv',     'Shell',    $shell],
            ['kv',     'Display',  $display],
            ['kv',     'Terminal', $terminal],
            ['kv',     'CPU',      $cpu],
            ['kv',     'GPU',      $gpu],
            ['kv',     'Memory',   $memUsed . ' / ' . $memTotal . ' (' . $memPct . ')'],
            ['kv',     'Disk (/)', $fmtGiB($diskUsed) . ' / ' . $fmtGiB($diskTotal) . ' (' . $diskPct . '%) - xfs'],
            ['kv',     'Local IP', $localip],
            ['kv',     'Locale',   $locale],
        ];

        $renderInfoLine = function(array $row): string {
            $type = $row[0];
            if ($type === 'header') {
                $h = htmlspecialchars($row[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return '<span style="color:#cc3333;font-weight:bold">' . $h . '</span>';
            }
            if ($type === 'sep') {
                $s = htmlspecialchars($row[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return '<span style="color:#e0e0e0">' . $s . '</span>';
            }
            // kv
            $key = htmlspecialchars($row[1] . ':', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $val = htmlspecialchars($row[2],        ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            // key column: 10 chars wide (pad with spaces inside span)
            $keyPadded = str_pad($row[1] . ':', 10);
            $keyEsc    = htmlspecialchars($keyPadded, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<span style="color:#cccc00">' . $keyEsc . '</span>'
                 . '<span style="color:#e0e0e0"> ' . $val . '</span>';
        };

        // Build combined HTML lines
        $totalLines = max(count($renderedLogo), count($infoRaw));
        $htmlLines  = [];
        for ($i = 0; $i < $totalLines; $i++) {
            $logoHtml  = '';
            $logoLen   = 0;
            if (isset($renderedLogo[$i])) {
                [$logoHtml, $logoLen] = $renderedLogo[$i];
            }
            // Pad to logoWidth with spaces
            $pad      = $logoWidth - $logoLen;
            $padHtml  = $pad > 0 ? str_repeat(' ', $pad) : '';
            $infoHtml = isset($infoRaw[$i]) ? $renderInfoLine($infoRaw[$i]) : '';
            $htmlLines[] = $logoHtml . $padHtml . '  ' . $infoHtml;
        }

        // Colour palette strip — 16 blocks (8 normal + 8 bright)
        $palNormal = ['#000000','#cc3333','#33cc33','#cccc00','#3355cc','#cc33cc','#00cccc','#cccccc'];
        $palBright = ['#555555','#ff5555','#55ff55','#ffff55','#5555ff','#ff55ff','#55ffff','#ffffff'];
        $indent    = str_repeat(' ', $logoWidth + 2);

        $buildBar = function(array $pal) use ($indent): string {
            $s = $indent;
            foreach ($pal as $bg) {
                $s .= '<span style="background:' . $bg . ';color:' . $bg . '">   </span>';
            }
            return $s;
        };

        $htmlLines[] = '';
        $htmlLines[] = $buildBar($palNormal);
        $htmlLines[] = $buildBar($palBright);

        $html = implode("\n", $htmlLines);
        echo json_encode(['fastfetch' => true, 'html' => $html]);
        exit;
    }
}
