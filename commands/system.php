<?php
//  system commands: whoami, pwd, hostname, uname, uptime, date,
//                   df, free, ps, top, id, env, which, fastfetch, neofetch,
//                   systemctl
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

    // id
    case 'id':
        out('uid=0(root) gid=0(root) groups=0(root),1(bin),2(daemon),3(sys),4(adm),6(disk),10(wheel)');

    // env / printenv
    case 'env':
    case 'printenv':
        out("SHELL=/bin/bash\n"
          . "TERM=xterm-256color\n"
          . "USER=" . $user . "\n"
          . "MAIL=/var/mail/" . $user . "\n"
          . "PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\n"
          . "PWD=" . $_SESSION['cwd'] . "\n"
          . "LANG=en_US.UTF-8\n"
          . "HOME=/root\n"
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
            'touch'=>'/bin/touch','chmod'=>'/bin/chmod','chown'=>'/bin/chown',
            'grep'=>'/bin/grep','find'=>'/usr/bin/find','awk'=>'/usr/bin/awk',
            'sed'=>'/usr/bin/sed','sort'=>'/usr/bin/sort','uniq'=>'/usr/bin/uniq',
            'wc'=>'/usr/bin/wc','head'=>'/usr/bin/head','tail'=>'/usr/bin/tail',
            'curl'=>'/usr/bin/curl','wget'=>'/usr/bin/wget','ssh'=>'/usr/bin/ssh',
            'scp'=>'/usr/bin/scp','rsync'=>'/usr/bin/rsync',
            'git'=>'/usr/bin/git','php'=>'/usr/bin/php','python3'=>'/usr/bin/python3',
            'perl'=>'/usr/bin/perl','ruby'=>'/usr/bin/ruby','node'=>'/usr/bin/node',
            'npm'=>'/usr/bin/npm','pip3'=>'/usr/bin/pip3',
            'mysql'=>'/usr/bin/mysql','redis-cli'=>'/usr/bin/redis-cli',
            'top'=>'/usr/bin/top','htop'=>'/usr/bin/htop','ps'=>'/bin/ps',
            'kill'=>'/bin/kill','df'=>'/bin/df','free'=>'/usr/bin/free',
            'ifconfig'=>'/sbin/ifconfig','ip'=>'/sbin/ip','ping'=>'/usr/bin/ping',
            'netstat'=>'/bin/netstat','ss'=>'/sbin/ss','nmap'=>'/usr/bin/nmap',
            'vim'=>'/usr/bin/vim','nano'=>'/usr/bin/nano',
            'tar'=>'/bin/tar','gzip'=>'/bin/gzip','zip'=>'/usr/bin/zip',
            'uname'=>'/bin/uname','hostname'=>'/bin/hostname','date'=>'/bin/date',
            'echo'=>'/bin/echo','printf'=>'/usr/bin/printf','env'=>'/usr/bin/env',
            'sudo'=>'/usr/bin/sudo','su'=>'/bin/su','passwd'=>'/usr/bin/passwd',
            'useradd'=>'/usr/sbin/useradd','usermod'=>'/usr/sbin/usermod',
            'crontab'=>'/usr/bin/crontab','systemctl'=>'/usr/bin/systemctl',
            'journalctl'=>'/usr/bin/journalctl','man'=>'/usr/bin/man',
            'dnf'=>'/usr/bin/dnf','yum'=>'/usr/bin/yum','rpm'=>'/usr/bin/rpm',
            'fastfetch'=>'/usr/bin/fastfetch','neofetch'=>'/usr/bin/neofetch',
        ];
        $results = [];
        foreach (explode(' ', $args) as $w) {
            $w = trim($w);
            if ($w === '') continue;
            if (isset($bins[$w])) $results[] = $bins[$w];
            else { err('/usr/bin/which: no ' . $w . ' in (/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin)'); }
        }
        out(implode("\n", $results));

    // fastfetch / neofetch
    case 'fastfetch':
    case 'neofetch':
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

        // Real fastfetch AlmaLinux logo — 19 lines, 36 chars wide
        $logo = [
            "         'c:.                   ",
            "        lkkkx, ..       ..   ,cc,",
            "        okkkk:ckkx'  .lxkkx.okkkkd",
            "        .:llcokkx'  :kkkxkko:xkkd, ",
            "      .xkkkkdood:  ;kx,  .lkxlll;  ",
            "       xkkx.       xk'     xkkkkk:  ",
            "       'xkx.       xd      .....,.  ",
            "      .. :xkl'     :c      ..''..   ",
            "    .dkx'  .:ldl:'. '  ':lollldkkxo;",
            "  .''lkkko'                     ckkkx.",
            "'xkkkd:kkd.       ..  ;'        :kkxo.",
            ",xkkkd;kk'      ,d;    ld.   ':dkd::cc,",
            " .,,.;xkko'.';lxo.      dx,  :kkk'xkkkkc",
            "     'dkkkkkxo:.        ;kx  .kkk:;xkkd. ",
            "       .....   .;dk:.   lkk.  :;,          ",
            "             :kkkkkkkdoxkkx               ",
            "              ,c,,;;;:xkkd.               ",
            "                ;kkkkl.                   ",
            "                 ,od;                     ",
        ];

        // Info block
        $header = $user . '@' . CONF_HOSTNAME;
        $sep    = str_repeat('-', strlen($header));
        $info = [
            $header,
            $sep,
            'OS:         ' . CONF_OS . ' x86_64',
            'Kernel:     Linux ' . CONF_KERNEL,
            'Uptime:     ' . $upStr,
            'Packages:   ' . $pkgCount,
            'Shell:      ' . $shell,
            'Display:    ' . $display,
            'Terminal:   ' . $terminal,
            'CPU:        ' . $cpu,
            'GPU:        ' . $gpu,
            'Memory:     ' . $memUsed . ' / ' . $memTotal . ' (' . $memPct . ')',
            'Disk (/):   ' . $fmtGiB($diskUsed) . ' / ' . $fmtGiB($diskTotal) . ' (' . $diskPct . '%) - xfs',
            'Local IP:   ' . $localip,
            'Locale:     ' . $locale,
        ];

        // Combine: logo left, info right
        // Logo lines vary in length — pad each to the longest
        $logoWidth = max(array_map('strlen', $logo));
        $totalLines = max(count($logo), count($info));
        $out = [];
        for ($i = 0; $i < $totalLines; $i++) {
            $l = isset($logo[$i]) ? $logo[$i] : '';
            $r = isset($info[$i]) ? $info[$i] : '';
            $out[] = str_pad($l, $logoWidth) . '  ' . $r;
        }
        // Colour palette strip (two rows: normal then bright, using block chars)
        $blocks  = str_repeat('   ', 8);   // 8 colour blocks, 3 spaces each
        $out[] = '';
        $out[] = str_pad('', $logoWidth) . '  ' . $blocks;
        $out[] = str_pad('', $logoWidth) . '  ' . $blocks;
        out(implode("\n", $out));

    // systemctl
    case 'systemctl': {
        $subcmd  = isset($argv[0]) ? strtolower($argv[0]) : '';
        $service = isset($argv[1]) ? strtolower($argv[1]) : '';

        // Strip .service suffix if provided (systemctl status httpd.service → httpd)
        $service = preg_replace('/\.service$/', '', $service);

        // Known services and their display names / ports / pids
        $services = [
            'httpd'    => ['label' => 'httpd.service',    'desc' => 'The Apache HTTP Server',                    'pid' => 1187, 'port' => '0.0.0.0:80'],
            'mariadb'  => ['label' => 'mariadb.service',  'desc' => 'MariaDB 10.5 database server',              'pid' => 1243, 'port' => '127.0.0.1:3306'],
            'php-fpm'  => ['label' => 'php-fpm.service',  'desc' => 'The PHP FastCGI Process Manager',           'pid' => 1301, 'port' => '127.0.0.1:9000'],
            'mysqld'   => ['label' => 'mariadb.service',  'desc' => 'MariaDB 10.5 database server',              'pid' => 1243, 'port' => '127.0.0.1:3306'],
            'nginx'    => ['label' => 'nginx.service',    'desc' => 'The nginx HTTP and reverse proxy server',   'pid' => 0,    'port' => ''],
            'sshd'     => ['label' => 'sshd.service',     'desc' => 'OpenSSH server daemon',                     'pid' => 978,  'port' => '0.0.0.0:22'],
            'firewalld'=> ['label' => 'firewalld.service','desc' => 'firewalld - dynamic firewall daemon',       'pid' => 812,  'port' => ''],
            'crond'    => ['label' => 'crond.service',    'desc' => 'Command Scheduler',                         'pid' => 1089, 'port' => ''],
        ];

        // Services that are stopped by default
        $stopped = ['nginx'];

        // no subcommand
        if ($subcmd === '') {
            out("Usage: systemctl [OPTIONS...] COMMAND ...\n\nQuery or send control commands to the system manager.\n\nUnit Commands:\n  start NAME...             Start (activate) one or more units\n  stop NAME...              Stop (deactivate) one or more units\n  restart NAME...           Start or restart one or more units\n  status [NAME...|PID...]   Show runtime status of one or more units\n  enable NAME...            Enable one or more unit files\n  disable NAME...           Disable one or more unit files\n  is-active PATTERN...      Check whether units are active\n  list-units [PATTERN...]   List loaded units\n\nSee 'man systemctl' for details.");
        }

        // list-units
        if ($subcmd === 'list-units') {
            $lines = [
                '  UNIT                    LOAD   ACTIVE SUB     DESCRIPTION',
                '  crond.service           loaded active running Command Scheduler',
                '  firewalld.service       loaded active running firewalld - dynamic firewall daemon',
                '  httpd.service           loaded active running The Apache HTTP Server',
                '  mariadb.service         loaded active running MariaDB 10.5 database server',
                '  php-fpm.service         loaded active running The PHP FastCGI Process Manager',
                '  sshd.service            loaded active running OpenSSH server daemon',
                '  nginx.service           loaded active exited  The nginx HTTP and reverse proxy server',
                '',
                'LOAD   = Reflects whether the unit definition was properly loaded.',
                'ACTIVE = The high-level unit activation state.',
                'SUB    = The low-level unit activation state.',
                '',
                '7 loaded units listed.',
            ];
            out(implode("\n", $lines));
        }

        // subcommands that require a service name
        if (in_array($subcmd, ['start','stop','restart','status','enable','disable','is-active'])) {
            if ($service === '') {
                err('systemctl: ' . $subcmd . ': no service specified');
            }

            if (!isset($services[$service])) {
                err('Failed to ' . $subcmd . ' ' . $service . '.service: Unit ' . $service . '.service not found.');
            }

            $svc    = $services[$service];
            $isStopped = in_array($service, $stopped);

            if ($subcmd === 'status') {
                $active  = $isStopped ? 'inactive (dead)' : 'active (running)';
                $dotchar = $isStopped ? 'x' : '*';
                $since   = date('D Y-m-d H:i:s T', time() - rand(3600, 86400));
                $lines   = [
                    $dotchar . ' ' . $svc['label'] . ' - ' . $svc['desc'],
                    '   Loaded: loaded (/usr/lib/systemd/system/' . $svc['label'] . '; enabled; vendor preset: disabled)',
                    '   Active: ' . $active . ' since ' . $since,
                ];
                if (!$isStopped) {
                    $lines[] = '  Process: ' . $svc['pid'] . ' ExecStart=/usr/sbin/' . $service . ' (code=exited, status=0/SUCCESS)';
                    $lines[] = ' Main PID: ' . $svc['pid'] . ' (' . $service . ')';
                    $lines[] = '    Tasks: ' . rand(2,8) . ' (limit: 23480)';
                    $lines[] = '   Memory: ' . rand(10,80) . '.' . rand(0,9) . 'M';
                    $lines[] = '   CGroup: /system.slice/' . $svc['label'];
                    $lines[] = '           `-' . $svc['pid'] . ' /usr/sbin/' . $service . ' -DFOREGROUND';
                }
                out(implode("\n", $lines));
            }

            if ($subcmd === 'start') {
                if ($isStopped) {
                    // Remove from stopped list (cosmetic — session state not tracked per service here)
                    out('');
                }
                out(''); // already running — systemctl start is silent on success
            }

            if ($subcmd === 'stop') {
                out(''); // silent on success
            }

            if ($subcmd === 'restart') {
                out(''); // silent on success
            }

            if ($subcmd === 'enable') {
                out('Created symlink /etc/systemd/system/multi-user.target.wants/' . $svc['label'] . ' → /usr/lib/systemd/system/' . $svc['label'] . '.');
            }

            if ($subcmd === 'disable') {
                out('Removed /etc/systemd/system/multi-user.target.wants/' . $svc['label'] . '.');
            }

            if ($subcmd === 'is-active') {
                if ($isStopped) err('inactive');
                out('active');
            }
        }

        err('systemctl: Unknown operation \'' . $subcmd . '\'.');
        break;
    }
}
