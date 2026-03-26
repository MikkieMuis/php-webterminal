<?php
//  hardware/process commands: php, kill, pkill, lsblk, blkid, dmesg,
//                             vmstat, iostat, hostnamectl, timedatectl,
//                             chgrp, logger, lsof
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // php
    case 'php': {
        $flag = isset($argv[0]) ? $argv[0] : '';

        // php --version / php -v
        if ($flag === '-v' || $flag === '--version') {
            out("PHP 8.2.28 (cli) (built: Feb  4 2026 12:00:00) (NTS)\nCopyright (c) The PHP Group\nZend Engine v4.2.28, Copyright (c) Zend Technologies\n    with Zend OPcache v8.2.28, Copyright (c), by Zend Technologies");
        }

        // php -r "code"
        if ($flag === '-r') {
            $code = isset($argv[1]) ? implode(' ', array_slice($argv, 1)) : '';
            // strip surrounding quotes
            $code = trim($code, '"\'');

            // basic arithmetic: echo 1+1; or echo 2*3;
            if (preg_match('/^echo\s+(\d+)\s*([+\-*\/])\s*(\d+)\s*;?$/', $code, $m)) {
                $a = (float)$m[1]; $op = $m[2]; $b = (float)$m[3];
                $res = $op==='+' ? $a+$b : ($op==='-' ? $a-$b : ($op==='*' ? $a*$b : ($b!=0 ? $a/$b : null)));
                if ($res === null) err('php: Division by zero');
                out(rtrim(rtrim(number_format($res, 10), '0'), '.'));
            }
            // echo "string";
            if (preg_match('/^echo\s+["\'](.+)["\'];?$/', $code, $m)) {
                out($m[1]);
            }
            // phpinfo();
            if (preg_match('/^phpinfo\s*\(\s*\)\s*;?$/', $code)) {
                out("phpinfo()\nPHP Version => 8.2.28\n\nSystem => Linux " . CONF_HOSTNAME . " " . SYS_KERNEL . " #1 SMP " . SYS_ARCH . " GNU/Linux\nBuild Date => Feb  4 2026 12:00:00\nConfigure Command => './configure' '--build=x86_64-redhat-linux-gnu'\nServer API => Command Line Interface\nVirtual Directory Support => disabled\nConfiguration File (php.ini) Path => /etc/php.ini\nLoaded Configuration File => /etc/php.ini\nPHP API => 20220829\nPHP Extension => 20220829\nZend Extension => 420220829\nZend Extension Build => API420220829,NTS\nPHP Extension Build => API20220829,NTS\nDebug Build => no\nThread Safety => disabled\nZend Signal Handling => enabled\nZend Memory Manager => enabled\nZend Multibyte Support => provided by mbstring\nIPv6 Support => enabled\nDTrace Support => disabled");
            }
            // unrecognised -r expression
            err('');
        }

        // php -i (phpinfo as text)
        if ($flag === '-i') {
            out("phpinfo()\nPHP Version => 8.2.28\n\nSystem => Linux " . CONF_HOSTNAME . " " . SYS_KERNEL . " #1 SMP " . SYS_ARCH . " GNU/Linux\nBuild Date => Feb  4 2026 12:00:00\nServer API => Command Line Interface\nConfiguration File (php.ini) Path => /etc/php.ini\nLoaded Configuration File => /etc/php.ini\nextension_dir => /usr/lib64/php/modules\n\ndate.timezone => Europe/Amsterdam\nmemory_limit => 256M\nmax_execution_time => 30\nupload_max_filesize => 64M\npost_max_size => 64M\n\nopcache.enable => 1\nopcache.memory_consumption => 128\n\nCore\nPHP Version => 8.2.28\n\nbcmath\ncalendar\nctype\ncurl\ndate\ndom\nexif\nfileinfo\nfilter\ngd\ngettext\nhash\niconv\njson\nlibxml\nmbstring\nmysqlnd\nopenssl\npcre\nPDO\npdo_mysql\npdo_sqlite\nphar\nposix\nReflection\nsession\nSimpleXML\nsodium\nSPL\nsqlite3\nstandard\ntokenizer\nxml\nxmlreader\nxmlwriter\nxsl\nzip\nZend OPcache");
        }

        // php -m (list modules)
        if ($flag === '-m') {
            out("[PHP Modules]\nbcmath\ncalendar\nctype\ncurl\ndate\ndom\nexif\nfileinfo\nfilter\ngd\ngettext\nhash\niconv\njson\nlibxml\nmbstring\nmysqlnd\nopenssl\npcre\nPDO\npdo_mysql\npdo_sqlite\nphar\nposix\nReflection\nsession\nSimpleXML\nsodium\nSPL\nsqlite3\nstandard\ntokenizer\nxml\nxmlreader\nxmlwriter\nxsl\nzip\n\n[Zend Modules]\nZend OPcache");
        }

        // php with no args or unrecognised flag
        if ($flag === '') {
            err("Interactive mode is not supported in this terminal.\nUse: php -r 'code'  or  php -v  or  php -i");
        }

        err("php: invalid option -- '" . ltrim($flag, '-') . "'\nUsage: php [options] [-r code] [--] [args...]\n       php [options] [-] [args...]\nUse --help to get this help.");
        break;
    }

    // kill
    case 'kill': {
        // fake process table — same PIDs as ps/top
        $known = [1=>'systemd', 432=>'systemd-journald', 914=>'sshd',
                  1105=>'apache2', 1212=>'mysqld', 1380=>'redis-server',
                  1512=>'crond', 1890=>'php-fpm', 2048=>'-bash'];

        // parse: kill [-SIGNAL] PID [PID...]
        $signal  = 15; // SIGTERM default
        $targets = [];
        foreach ($argv as $a) {
            if (preg_match('/^-(\d+)$/', $a, $m))        { $signal = (int)$m[1]; }
            elseif (preg_match('/^-([A-Z]+)$/i', $a, $m)){ /* named signal — ignored */ }
            elseif (ctype_digit($a))                      { $targets[] = (int)$a; }
        }

        if (empty($targets)) err('kill: usage: kill [-s sigspec | -n signum | -sigspec] pid | jobspec ... or kill -l [sigspec]');

        foreach ($targets as $pid) {
            if ($pid === 2048) {
                err('kill: (' . $pid . ') - Operation not permitted');
            }
            if (!isset($known[$pid])) {
                err('kill: (' . $pid . ') - No such process');
            }
            // success — bash kill prints nothing on success
        }
        out('');
        break;
    }

    // pkill
    case 'pkill': {
        $known = [
            ['pid'=>1,    'cmd'=>'systemd'],
            ['pid'=>432,  'cmd'=>'systemd-journald'],
            ['pid'=>914,  'cmd'=>'sshd'],
            ['pid'=>1105, 'cmd'=>'apache2'],
            ['pid'=>1212, 'cmd'=>'mysqld'],
            ['pid'=>1380, 'cmd'=>'redis-server'],
            ['pid'=>1512, 'cmd'=>'crond'],
            ['pid'=>1890, 'cmd'=>'php-fpm'],
            ['pid'=>2048, 'cmd'=>'-bash'],
        ];

        // strip signal flags, collect pattern
        $pattern = '';
        foreach ($argv as $a) {
            if ($a[0] === '-') continue;
            $pattern = $a;
            break;
        }

        if ($pattern === '') err('pkill: no matching processes found');

        $matched = false;
        foreach ($known as $p) {
            if (stripos($p['cmd'], $pattern) !== false) {
                if ($p['pid'] === 2048) err('pkill: operation not permitted');
                $matched = true;
            }
        }

        if (!$matched) err('pkill: no matching processes found');
        out('');
        break;
    }

    // lsblk
    case 'lsblk':
        out("NAME   MAJ:MIN RM   SIZE RO TYPE MOUNTPOINTS\n"
          . "sda      8:0    0   500G  0 disk\n"
          . "├─sda1   8:1    0   499G  0 part /\n"
          . "└─sda2   8:2    0   512M  0 part /boot\n"
          . "sdb      8:16   0     2T  0 disk\n"
          . "└─sdb1   8:17   0     2T  0 part /mnt/db\n"
          . "sdc      8:32   0     4T  0 disk\n"
          . "└─sdc1   8:33   0     4T  0 part /mnt/backup\n"
          . "sdd      8:48   0   500G  0 disk\n"
          . "└─sdd1   8:49   0   500G  0 part /home\n"
          . "sr0     11:0    1  1024M  0 rom");

    // blkid
    case 'blkid':
        out('/dev/sda1: UUID="a1b2c3d4-e5f6-7890-abcd-ef1234567890" BLOCK_SIZE="512" TYPE="xfs" PARTUUID="00000001-01"\n'
          . '/dev/sda2: UUID="b2c3d4e5-f6a7-8901-bcde-f12345678901" BLOCK_SIZE="512" TYPE="xfs" PARTUUID="00000001-02"\n'
          . '/dev/sdb1: UUID="c3d4e5f6-a7b8-9012-cdef-123456789012" BLOCK_SIZE="512" TYPE="xfs" PARTUUID="00000002-01"\n'
          . '/dev/sdc1: UUID="d4e5f6a7-b8c9-0123-defa-234567890123" BLOCK_SIZE="512" TYPE="xfs" PARTUUID="00000003-01"\n'
          . '/dev/sdd1: UUID="e5f6a7b8-c9d0-1234-efab-345678901234" BLOCK_SIZE="512" TYPE="xfs" PARTUUID="00000004-01"');

    // dmesg
    case 'dmesg': {
        $boot = date('H:i:s', $_SESSION['boot']);
        $lines = [
            '[    0.000000] Linux version ' . SYS_KERNEL . ' (mockbuild@mock.alma.example.com) (gcc version 11.4.1) #1 SMP',
            '[    0.000000] Command line: BOOT_IMAGE=/vmlinuz-' . SYS_KERNEL . ' root=/dev/sda1 ro rhgb quiet',
            '[    0.000000] BIOS-provided physical RAM map:',
            '[    0.000000] BIOS-e820: [mem 0x0000000000000000-0x000000000009fbff] usable',
            '[    0.000000] ACPI: IRQ0 used by override.',
            '[    0.296462] PCI: Using configuration type 1 for base access',
            '[    1.024381] SCSI subsystem initialized',
            '[    1.238710] ata1: SATA max UDMA/133 abar m2048@0xf0814000 port 0xf0814100 irq 29',
            '[    1.431022] scsi 0:0:0:0: Direct-Access     ATA      SAMSUNG MZNLN512 MAV2 PQ: 0 ANSI: 5',
            '[    1.892034] sd 0:0:0:0: [sda] 1048576000 512-byte logical blocks: (537 GB/500 GiB)',
            '[    2.013456] EXT4-fs (sda1): mounted filesystem with ordered data mode',
            '[    2.341987] NET: Registered PF_INET6 protocol family',
            '[    3.012345] e1000e: Intel(R) PRO/1000 Network Driver',
            '[    3.567891] e1000e 0000:00:19.0 eth0: (PCI Express:2.5GT/s:Width x1)',
            '[    4.123456] RPC: Registered named UNIX socket transport module.',
            '[    5.234567] systemd[1]: Detected virtualization none.',
            '[    5.891234] systemd[1]: Detected architecture x86-64.',
            '[    6.012345] systemd[1]: Running in system mode.',
            '[    7.123456] Started dracut-pre-udev.service - dracut pre-udev hook.',
            '[    8.234567] dracut-pre-udev[312]: Starting udev.',
            '[    9.345678] systemd-udevd[432]: starting version 249',
            '[   10.456789] random: crng init done',
            '[   11.567890] NET: Registered PF_PACKET protocol family',
            '[   12.678901] audit: type=1404 audit(0.000:2): enforcing=1 old_enforcing=0 auid=4294967295 ses=4294967295 res=1',
            '[   15.890123] SELinux: policy loaded with name "targeted"',
            '[   18.012345] Started NetworkManager.service - Network Manager.',
            '[   19.123456] Started sshd.service - OpenSSH server daemon.',
            '[   20.234567] Started httpd.service - The Apache HTTP Server.',
            '[   21.345678] Started mariadb.service - MariaDB 10.5 database server.',
        ];
        // Apply -n flag: last N lines
        if (preg_match('/-n\s*(\d+)/', $args, $m)) {
            $lines = array_slice($lines, -(int)$m[1]);
        }
        // -T: human-readable timestamps (replace [N.N] with date-like string)
        if (strpos($args, '-T') !== false) {
            $bootTs = $_SESSION['boot'];
            $lines = array_map(function($l) use ($bootTs) {
                return preg_replace_callback('/^\[\s*([\d.]+)\]/', function($m) use ($bootTs) {
                    return '[' . date('Y-m-d H:i:s', $bootTs + (int)$m[1]) . ']';
                }, $l);
            }, $lines);
        }
        out(implode("\n", $lines));
    }

    // vmstat
    case 'vmstat': {
        $load = CONF_LOAD_1;
        out("procs -----------memory---------- ---swap-- -----io---- -system-- ------cpu-----\n"
          . " r  b   swpd   free   buff  cache   si   so    bi    bo   in   cs us sy id wa st\n"
          . " 1  0      0 8847360   4096 4128768   0    0    12    24  312  641  3  1 95  1  0");
    }

    // iostat
    case 'iostat': {
        out("Linux " . SYS_KERNEL . " (" . CONF_HOSTNAME . ")  " . date('m/d/Y') . "  _x86_64_  (4 CPU)\n"
          . "\navg-cpu:  %user   %nice %system %iowait  %steal   %idle\n"
          . "           2.84    0.00    0.92    0.42    0.00   95.82\n"
          . "\nDevice             tps    kB_read/s    kB_wrtn/s    kB_dscd/s    kB_read    kB_wrtn    kB_dscd\n"
          . "sda               4.21        28.34        94.12         0.00     104293     346102          0\n"
          . "sdb               1.03         8.11        22.44         0.00      29834      82519          0\n"
          . "sdc               0.31         2.04         8.77         0.00       7503      32261          0\n"
          . "sdd               0.18         1.22         3.10         0.00       4489      11402          0");
    }

    // hostnamectl
    case 'hostnamectl':
        out(" Static hostname: " . CONF_HOSTNAME . "\n"
          . "       Icon name: computer-server\n"
          . "         Chassis: server\n"
          . "      Machine ID: a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6\n"
          . "         Boot ID: f1e2d3c4b5a6978869504d3c2b1a0f9e\n"
          . "Operating System: " . CONF_OS . "\n"
          . "     CPE OS Name: cpe:/o:almalinux:almalinux:9::baseos\n"
          . "          Kernel: Linux " . SYS_KERNEL . "\n"
          . "    Architecture: x86-64\n"
          . " Hardware Vendor: Dell Inc.\n"
          . "  Hardware Model: PowerEdge R640");

    // timedatectl
    case 'timedatectl':
        out("               Local time: " . date('D Y-m-d H:i:s T') . "\n"
          . "           Universal time: " . gmdate('D Y-m-d H:i:s') . " UTC\n"
          . "                 RTC time: " . gmdate('D Y-m-d H:i:s') . "\n"
          . "                Time zone: Europe/Amsterdam (CET, +0100)\n"
          . "System clock synchronized: yes\n"
          . "              NTP service: active\n"
          . "          RTC in local TZ: no");

    // chgrp — cosmetic stub
    case 'chgrp':
        if (count($argv) < 2) err('chgrp: missing operand');
        out('');

    // logger
    case 'logger':
        if ($args === '') err('logger: missing message operand');
        out('');  // silently accepted — real logger writes to syslog, no stdout

    // lsof
    case 'lsof': {
        $pidFilter  = '';
        $userFilter = '';
        $portFilter = '';
        $fileFilter = '';

        for ($li = 0; $li < count($argv); $li++) {
            $a = $argv[$li];
            if ($a === '-p' && isset($argv[$li+1])) { $pidFilter  = $argv[++$li]; }
            elseif ($a === '-u' && isset($argv[$li+1])) { $userFilter = $argv[++$li]; }
            elseif ($a === '-i' && isset($argv[$li+1])) { $portFilter = $argv[++$li]; }
            elseif (preg_match('/^-i(.+)$/', $a, $m))   { $portFilter = $m[1]; }
            elseif ($a[0] !== '-' && $fileFilter === '') { $fileFilter = $a; }
        }

        $procs = [
            // cmd,    pid,  user,    fd,   type, device, size, node, name
            ['httpd',   1100, 'apache', '4u',  'IPv4', '18291', '0t0', 'TCP', '0.0.0.0:http (LISTEN)'],
            ['httpd',   1100, 'apache', '6u',  'IPv4', '18293', '0t0', 'TCP', '0.0.0.0:https (LISTEN)'],
            ['httpd',   1101, 'apache', '4u',  'IPv4', '18291', '0t0', 'TCP', '0.0.0.0:http (LISTEN)'],
            ['sshd',    1234, 'root',   '3u',  'IPv4', '20001', '0t0', 'TCP', '0.0.0.0:ssh (LISTEN)'],
            ['sshd',    1235, 'root',   '3u',  'IPv4', '20002', '0t0', 'TCP', '192.168.1.10:ssh->192.168.1.42:54321 (ESTABLISHED)'],
            ['mysqld',  2001, 'mysql',  '19u', 'IPv4', '22001', '0t0', 'TCP', '127.0.0.1:mysql (LISTEN)'],
            ['php-fpm', 2222, 'apache', '5u',  'IPv4', '23001', '0t0', 'TCP', '127.0.0.1:9000 (LISTEN)'],
            ['chronyd',  777, 'chrony', '1u',  'IPv4', '15001', '0t0', 'UDP', '127.0.0.1:323'],
            ['dhclient', 888, 'root',   '6u',  'IPv4', '16001', '0t0', 'UDP', '*:bootpc'],
            ['php-fpm', 2223, 'apache', '5u',  'IPv4', '23002', '0t0', 'TCP', '127.0.0.1:9000->127.0.0.1:54900 (ESTABLISHED)'],
        ];

        // filter by port if -i given
        if ($portFilter !== '') {
            $pf = ltrim($portFilter, ':');
            // map port numbers to service names used in NAME column
            $portAliases = ['80'=>'http','443'=>'https','22'=>'ssh','3306'=>'mysql',
                            '9000'=>'9000','68'=>'bootpc','323'=>'323','21'=>'ftp','25'=>'smtp'];
            $pfName = $portAliases[$pf] ?? $pf;
            $procs = array_filter($procs, function($p) use ($pf, $pfName) {
                return stripos($p[8], $pf) !== false || stripos($p[8], $pfName) !== false;
            });
        }
        if ($pidFilter !== '') {
            $procs = array_filter($procs, function($p) use ($pidFilter) {
                return $p[1] == $pidFilter;
            });
        }
        if ($userFilter !== '') {
            $procs = array_filter($procs, function($p) use ($userFilter) {
                return $p[2] === $userFilter;
            });
        }

        $header = sprintf("%-10s %5s %-8s %4s %-5s %-8s %5s %-4s %s",
            'COMMAND','PID','USER','FD','TYPE','DEVICE','SIZE','NODE','NAME');
        $lines = [$header];
        foreach ($procs as $p) {
            $lines[] = sprintf("%-10s %5d %-8s %4s %-5s %-8s %5s %-4s %s",
                $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]);
        }
        out(implode("\n", $lines));
    }

    // strace
    case 'strace': {
        // Usage: strace [-p PID] [-e trace=SYSCALLS] [CMD [ARGS...]]
        $tracePid  = null;
        $traceCmd  = '';
        $traceArgs = [];
        for ($si = 0; $si < count($argv); $si++) {
            if ($argv[$si] === '-p' && isset($argv[$si+1])) {
                $tracePid = (int)$argv[++$si];
            } elseif ($argv[$si] === '-e' && isset($argv[$si+1])) {
                $si++; // skip -e trace=xxx
            } elseif ($argv[$si][0] !== '-' && $traceCmd === '') {
                $traceCmd = $argv[$si];
                $traceArgs = array_slice($argv, $si + 1);
                break;
            }
        }

        if ($tracePid === null && $traceCmd === '') {
            err("strace: must have PROG [ARGS] or -p PID\nTry 'strace -h' for more information.");
        }

        // Fake PID
        $pid = $tracePid ?? rand(1000, 9999);

        if ($traceCmd !== '') {
            $execArgs = array_merge([$traceCmd], $traceArgs);
            $execStr  = '"' . $traceCmd . '", ["' . implode('", "', $execArgs) . '"], /* envp */';
            $header   = 'execve("' . '/usr/bin/' . $traceCmd . '", ' . $execStr . ') = 0';
        } else {
            $header = 'Process ' . $pid . ' attached';
        }

        $syscalls = [
            'brk(NULL)                               = 0x' . dechex(rand(0x55a000000, 0x55affffff)),
            'access("/etc/ld.so.preload", R_OK)      = -1 ENOENT (No such file or directory)',
            'openat(AT_FDCWD, "/etc/ld.so.cache", O_RDONLY|O_CLOEXEC) = 3',
            'fstat(3, {st_mode=S_IFREG|0644, st_size=' . rand(80000, 200000) . ', ...}) = 0',
            'mmap(NULL, ' . rand(80000, 200000) . ', PROT_READ, MAP_PRIVATE, 3, 0) = 0x7f' . bin2hex(random_bytes(3)),
            'close(3)                                = 0',
            'openat(AT_FDCWD, "/lib/x86_64-linux-gnu/libc.so.6", O_RDONLY|O_CLOEXEC) = 3',
            'read(3, "\177ELF\2\1\1\3\0\0\0\0\0\0\0\0\3\0>\0\1\0\0\0P"..., 832) = 832',
            'fstat(3, {st_mode=S_IFREG|0755, st_size=1839792, ...}) = 0',
            'mmap(NULL, 8192, PROT_READ|PROT_WRITE, MAP_PRIVATE|MAP_ANONYMOUS, -1, 0) = 0x7f' . bin2hex(random_bytes(3)),
            'close(3)                                = 0',
            'arch_prctl(ARCH_SET_FS, 0x7f' . bin2hex(random_bytes(3)) . ') = 0',
            'munmap(0x7f' . bin2hex(random_bytes(3)) . ', ' . rand(80000, 200000) . ') = 0',
            'write(1, "' . ($traceCmd ?: 'output') . '\\n", ' . rand(5, 20) . ') = ' . rand(5, 20),
            'exit_group(0)                           = ?',
            '+++ exited with 0 +++',
        ];

        out($header . "\n" . implode("\n", $syscalls));
    }
}
