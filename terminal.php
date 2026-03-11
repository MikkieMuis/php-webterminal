<?php
// ============================================================
//  php-webterminal — command handler
//  POST { cmd: "ls -la", user: "root" }  →  JSON { output: "..." }
// ============================================================

require_once __DIR__ . '/config.php';

session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── real system info ───────────────────────────────────────
define('SYS_KERNEL', trim(shell_exec('uname -r') ?: '5.14.0-1-default'));
define('SYS_ARCH',   trim(shell_exec('uname -m') ?: 'x86_64'));

$_os_raw = @file_get_contents('/etc/os-release') ?: '';
preg_match('/^PRETTY_NAME="?([^"\n]+)"?/m', $_os_raw, $_os_m);
define('SYS_OS', isset($_os_m[1]) ? $_os_m[1] : 'Linux');
unset($_os_raw, $_os_m);

// ── sysinfo endpoint (GET ?sysinfo) ───────────────────────
if (isset($_GET['sysinfo'])) {
    echo json_encode([
        'kernel'   => SYS_KERNEL,
        'arch'     => SYS_ARCH,
        'os'       => SYS_OS,
        'hostname' => CONF_HOSTNAME,
    ]);
    exit;
}

// ── initialise session filesystem ──────────────────────────
// Bump this version string whenever fs_data.php changes to force a session reset.
define('FS_VERSION', '3');

if (!isset($_SESSION['fs']) || ($_SESSION['fs_version'] ?? '') !== FS_VERSION) {
    require_once __DIR__ . '/fs_data.php';
    $_SESSION['fs']         = fs_get_data();
    $_SESSION['fs_version'] = FS_VERSION;
    $_SESSION['cwd']        = '/root';
    $_SESSION['cmdlog']     = [];
    $_SESSION['boot']       = time();
}
if (!isset($_SESSION['cwd']))     $_SESSION['cwd']     = '/root';
if (!isset($_SESSION['cmdlog']))  $_SESSION['cmdlog']  = [];
if (!isset($_SESSION['boot']))    $_SESSION['boot']    = time();

// ── helpers ────────────────────────────────────────────────
function out($text) {
    echo json_encode(['output' => $text]);
    exit;
}

function err($text) {
    echo json_encode(['output' => $text, 'error' => true]);
    exit;
}

function res_path($path) {
    // resolve a path against cwd, handling . and ..
    if ($path === '' || $path === null) return $_SESSION['cwd'];
    if ($path[0] !== '/') $path = $_SESSION['cwd'] . '/' . $path;
    $parts  = explode('/', $path);
    $stack  = [];
    foreach ($parts as $p) {
        if ($p === '' || $p === '.') continue;
        if ($p === '..') { array_pop($stack); continue; }
        $stack[] = $p;
    }
    return '/' . implode('/', $stack);
}

function ls_dir($path, $long) {
    $fs      = $_SESSION['fs'];
    $prefix  = rtrim($path, '/');
    $entries = [];
    foreach ($fs as $p => $node) {
        if ($p === $path) continue;
        $parent = ($prefix === '') ? '/' : $prefix;
        $dir    = dirname($p);
        if ($dir !== $parent) continue;
        $name   = basename($p);
        if ($node['type'] === 'dir') {
            $perm = 'drwxr-xr-x';
        } else {
            $perm = '-rw-r--r--';
        }
        $size  = isset($node['content']) ? strlen($node['content']) : 4096;
        $mtime = isset($node['mtime'])   ? date('M d H:i', $node['mtime']) : 'Mar  9 08:11';
        $entries[] = ['name'=>$name,'perm'=>$perm,'size'=>$size,'mtime'=>$mtime,'type'=>$node['type']];
    }
    if (empty($entries)) {
        return $long ? 'total 0' : '';
    }
    usort($entries, function($a,$b){ return strcmp($a['name'],$b['name']); });
    if (!$long) {
        return implode('  ', array_map(function($e){ return $e['name']; }, $entries));
    }
    $lines = ['total ' . (count($entries)*8)];
    foreach ($entries as $e) {
        $lines[] = sprintf('%s  2 root root %6d  %s  %s', $e['perm'], $e['size'], $e['mtime'], $e['name']);
    }
    return implode("\n", $lines);
}

// ── read input ─────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
$raw  = isset($body['cmd'])  ? trim($body['cmd'])  : '';
$user = isset($body['user']) ? trim($body['user']) : 'user';

if ($raw === '') out('');

// log command
$_SESSION['cmdlog'][] = $raw;
if (count($_SESSION['cmdlog']) > 100) {
    $_SESSION['cmdlog'] = array_slice($_SESSION['cmdlog'], -100);
}

// parse
$parts = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
$cmd   = strtolower($parts[0]);
$args  = implode(' ', array_slice($parts, 1));
$argv  = array_slice($parts, 1);   // individual args as array

// ── command dispatch ────────────────────────────────────────
switch ($cmd) {

    // ── whoami ──
    case 'whoami':
        out($user);

    // ── pwd ──
    case 'pwd':
        out($_SESSION['cwd']);

    // ── hostname ──
    case 'hostname':
        out(CONF_HOSTNAME);

    // ── uname ──
    case 'uname':
        if (strpos($args, '-a') !== false) {
            out('Linux ' . CONF_HOSTNAME . ' ' . SYS_KERNEL . ' #1 SMP ' . SYS_ARCH . ' ' . SYS_ARCH . ' ' . SYS_ARCH . ' GNU/Linux');
        }
        out('Linux');

    // ── uptime ──
    case 'uptime':
        $secs   = time() - $_SESSION['boot'];
        $days   = floor($secs / 86400);
        $hours  = floor(($secs % 86400) / 3600);
        $mins   = floor(($secs % 3600)  / 60);
        $load   = number_format(sys_getloadavg()[0], 2) . ', '
                . number_format(sys_getloadavg()[1], 2) . ', '
                . number_format(sys_getloadavg()[2], 2);
        out(sprintf(' %s up %d days, %d:%02d,  1 user,  load average: %s',
            date('H:i:s'), $days, $hours, $mins, $load));

    // ── date ──
    case 'date':
        out(date('D M j H:i:s T Y'));

    // ── echo ──
    case 'echo':
        out($args);

    // ── clear ──
    case 'clear':
        echo json_encode(['output'=>'', 'clear'=>true]);
        exit;

    // ── exit / logout ──
    case 'exit':
    case 'logout':
        echo json_encode(['output'=>"logout\n\nConnection to " . CONF_HOSTNAME . " closed.", 'logout'=>true]);
        exit;

    // ── history ──
    case 'history':
        $base = [
            '    1  apt-get update',
            '    2  apt-get upgrade -y',
            '    3  df -h',
            '    4  free -h',
            '    5  ps aux',
        ];
        $log = $_SESSION['cmdlog'];
        $offset = count($base) + 1;
        foreach ($log as $i => $c) {
            $base[] = sprintf('%5d  %s', $offset + $i, $c);
        }
        out(implode("\n", $base));

    // ── ls ──
    case 'ls':
        $long   = (strpos($args, '-l') !== false);
        $all    = (strpos($args, '-a') !== false);
        // find path arg (non-flag tokens)
        $target = $_SESSION['cwd'];
        foreach ($argv as $a) {
            if ($a[0] !== '-') { $target = res_path($a); break; }
        }
        $target = res_path($target);
        if (!isset($_SESSION['fs'][$target])) {
            err('ls: cannot access \'' . $target . '\': No such file or directory');
        }
        if ($_SESSION['fs'][$target]['type'] === 'file') {
            out($long ? sprintf('-rw-r--r--  1 root root %6d  Mar  9 08:11  %s',
                strlen($_SESSION['fs'][$target]['content']), basename($target)) : basename($target));
        }
        // build entry list from filesystem
        $fs     = $_SESSION['fs'];
        $prefix = rtrim($target, '/');
        $entries = [];
        foreach ($fs as $p => $node) {
            if ($p === $target) continue;
            $parent = ($prefix === '') ? '/' : $prefix;
            if (dirname($p) !== $parent) continue;
            $name = basename($p);
            if (!$all && $name[0] === '.') continue;  // hide dotfiles unless -a
            $perm  = $node['type'] === 'dir' ? 'drwxr-xr-x' : '-rw-r--r--';
            $size  = isset($node['content']) ? strlen($node['content']) : 4096;
            $mtime = isset($node['mtime']) ? date('M d H:i', $node['mtime']) : 'Mar  9 08:11';
            $entries[] = ['name'=>$name,'perm'=>$perm,'size'=>$size,'mtime'=>$mtime,'type'=>$node['type']];
        }
        if (empty($entries)) { out(''); }
        usort($entries, function($a,$b){ return strcmp($a['name'],$b['name']); });
        if (!$long) {
            out(implode('  ', array_map(function($e){ return $e['name']; }, $entries)));
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
            // easter egg: sudo rm -rf / is handled client-side; direct rm -rf / gets here
            $path = trim(preg_replace('/-r\S*\s*/', '', $args));
            if ($path === '/' || $path === '') {
                // just let the easter egg trigger
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
        // remove entry and all children
        foreach (array_keys($_SESSION['fs']) as $k) {
            if ($k === $path || strpos($k, rtrim($path,'/').'/')  === 0) {
                unset($_SESSION['fs'][$k]);
            }
        }
        out('');

    // ── cat ──
    case 'cat':
        $target = res_path($args);
        if ($args === '') err('cat: missing operand');
        if (!isset($_SESSION['fs'][$target])) err('cat: ' . $args . ': No such file or directory');
        if ($_SESSION['fs'][$target]['type'] === 'dir') err('cat: ' . $args . ': Is a directory');
        out($_SESSION['fs'][$target]['content']);

    // ── df ──
    case 'df':
        // sda1 — real data from server
        $free  = @disk_free_space('/')  ?: 408893440000;
        $total = @disk_total_space('/') ?: 532070400000;
        $used  = $total - $free;
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
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sda1', $fmt($total),   $fmt($used),        $fmt($free),        $pct,  '/')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdb1', '2.0T',         '1.4T',             '600G',             73,    '/mnt/db')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdc1', '4.0T',         '2.1T',             '1.9T',             53,    '/mnt/backup')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sdd1', '500G',         '87G',              '413G',             18,    '/home')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", 'tmpfs',     '7.9G',         '1.2M',             '7.9G',             1,     '/dev/shm')
              . sprintf("%-15s %5s %5s %5s %4d%% %s\n", '/dev/sda2', '512M',         '42M',              '470M',             9,     '/boot')
            );
        } else {
            out(
                "Filesystem     1K-blocks       Used  Available Use% Mounted on\n"
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sda1', $total/1024,      $used/1024,         $free/1024,         $pct,  '/')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdb1', 2147483648,       1468006400,         629145600,          73,    '/mnt/db')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdc1', 4294967296,       2264924160,         1992294400,         53,    '/mnt/backup')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sdd1', 524288000,        91750400,           432537600,          18,    '/home')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", 'tmpfs',     8192000,          1204,               8190796,            1,     '/dev/shm')
              . sprintf("%-15s %12d %10d %10d %4d%% %s\n", '/dev/sda2', 524288,           42984,              481304,             9,     '/boot')
            );
        }

    // ── free ──
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

    // ── ps ──
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

    // ── ifconfig ──
    case 'ifconfig':
        out("eth0: flags=4163<UP,BROADCAST,RUNNING,MULTICAST>  mtu 1500\n"
          . "        inet 192.168.1.10  netmask 255.255.255.0  broadcast 192.168.1.255\n"
          . "        inet6 fe80::215:5dff:fe00:1  prefixlen 64  scopeid 0x20<link>\n"
          . "        ether 00:15:5d:00:00:01  txqueuelen 1000  (Ethernet)\n"
          . "        RX packets 184291  bytes 241084773 (229.9 MiB)\n"
          . "        TX packets 89042  bytes 13872048 (13.2 MiB)\n\n"
          . "lo: flags=73<UP,LOOPBACK,RUNNING>  mtu 65536\n"
          . "        inet 127.0.0.1  netmask 255.0.0.0\n"
          . "        loop  txqueuelen 1000  (Local Loopback)");

    // ── ip ──
    case 'ip':
        if (strpos($args, 'a') !== false) {
            out("1: lo: <LOOPBACK,UP,LOWER_UP> mtu 65536 qdisc noqueue state UNKNOWN\n"
              . "    link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00\n"
              . "    inet 127.0.0.1/8 scope host lo\n\n"
              . "2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc mq state UP\n"
              . "    link/ether 00:15:5d:00:00:01 brd ff:ff:ff:ff:ff:ff\n"
              . "    inet 192.168.1.10/24 brd 192.168.1.255 scope global eth0");
        }
        out('Usage: ip [ OPTIONS ] OBJECT { COMMAND | help }');

    // ── sudo ──
    case 'sudo':
        if ($user === 'root') {
            // root: easter egg fires immediately, all other commands no-op
            if (preg_match('/rm\s.*-rf\s.*\/|rm\s.*\/\s.*-rf/', $args) || $args === 'rm -rf /') {
                echo json_encode(['output'=>'', 'rmrf'=>true]);
                exit;
            }
            out('sudo: you are already root.');
        }
        // non-root: always ask for password first; inner command handled client-side after auth
        echo json_encode(['output'=>'', 'sudo_prompt'=>true, 'sudo_cmd'=>$args]);
        exit;

    // ── help ──
    case 'help':
        out("GNU bash, version 5.1.8(1)-release (x86_64-redhat-linux-gnu)\n"
          . "These shell commands are defined internally.  Type `help' to see this list.\n"
          . "Type `help name' to find out more about the function `name'.\n"
          . "Use `info bash' to find out more about the shell in general.\n"
          . "Use `man -k' or `info' to find out more about commands not in this list.\n"
          . "\n"
          . "A star (*) next to a name means that the command is disabled.\n"
          . "\n"
          . " job_spec [&]                                                                    history [-c] [-d offset] [n] or history -anrw [filename] or history -ps arg\n"
          . " (( expression ))                                                                if COMMANDS; then COMMANDS; [ elif COMMANDS; then COMMANDS; ]... [ else ] fi\n"
          . " . filename [arguments]                                                          jobs [-lnprs] [jobspec ...] or jobs -x command [args]\n"
          . " :                                                                               kill [-s sigspec | -n signum | -sigspec] pid | jobspec ... or kill -l [sigspec]\n"
          . " [ arg... ]                                                                      let arg [arg ...]\n"
          . " [[ expression ]]                                                                local [option] name[=value] ...\n"
          . " alias [-p] [name[=value] ... ]                                                  logout [n]\n"
          . " bg [job_spec ...]                                                               mapfile [-d delim] [-n count] [-O origin] [-s count] [-t] [-u fd] [array]\n"
          . " bind [-lpsvPSVX] [-m keymap] [-f filename] [-q name] [-u name] [-r keyseq]     popd [-n] [+N | -N]\n"
          . " break [n]                                                                       printf [-v var] format [arguments]\n"
          . " builtin [shell-builtin [arg ...]]                                               pushd [-n] [+N | -N | dir]\n"
          . " caller [expr]                                                                   pwd [-LP]\n"
          . " case WORD in [PATTERN [| PATTERN]...) COMMANDS ;;]... esac                     read [-ers] [-a array] [-d delim] [-i text] [-n nchars] [-p prompt] [-t timeout]\n"
          . " cd [-L|[-P [-e]] [-@]] [dir]                                                   readarray [-d delim] [-n count] [-O origin] [-s count] [-t] [-u fd] [array]\n"
          . " command [-pVv] command [arg ...]                                                readonly [-aAf] [name[=value] ...] or readonly -p\n"
          . " compgen [-abcdefgjksuv] [-o option] [-A action] [-G globpat] [-W wordlist]     return [n]\n"
          . " complete [-abcdefgjksuv] [-pr] [-DEI] [-o option] [-A action] [-G globpat]     select NAME [in WORDS ... ;] do COMMANDS; done\n"
          . " compopt [-o|+o option] [-DEI] [name ...]                                       set [-abefhkmnptuvxBCHP] [-o option-name] [--] [arg ...]\n"
          . " continue [n]                                                                    shift [n]\n"
          . " coproc [NAME] command [redirections]                                            shopt [-pqsu] [-o] [optname ...]\n"
          . " declare [-aAfFgiIlnrtux] [-p] [name[=value] ...]                               source filename [arguments]\n"
          . " dirs [-clpv] [+N] [-N]                                                         suspend [-f]\n"
          . " disown [-h] [-ar] [jobspec ... | pid ...]                                      test [expr]\n"
          . " echo [-neE] [arg ...]                                                           time [-p] pipeline\n"
          . " enable [-a] [-dnps] [-f filename] [name ...]                                   times\n"
          . " eval [arg ...]                                                                  trap [-lp] [[arg] signal_spec ...]\n"
          . " exec [-cl] [-a name] [command [argument ...]] [redirection ...]                true\n"
          . " exit [n]                                                                        type [-afptP] name [name ...]\n"
          . " export [-fn] [name[=value] ...] or export -p                                   typeset [-aAfFgiIlnrtux] [-p] name[=value] ...\n"
          . " false                                                                           ulimit [-SHabcdefiklmnpqrstuvxPT] [limit]\n"
          . " fc [-e ename] [-lnr] [first] [last] or fc -s [pat=rep] [command]               umask [-p] [-S] [mode]\n"
          . " fg [job_spec]                                                                   unalias [-a] name [name ...]\n"
          . " for NAME [in WORDS ... ] ; do COMMANDS; done                                   unset [-f] [-v] [-n] [name ...]\n"
          . " for (( exp1; exp2; exp3 )); do COMMANDS; done                                  until COMMANDS; do COMMANDS; done\n"
          . " function name { COMMANDS ; } or name () { COMMANDS ; }                         variables - Names and meanings of some shell variables\n"
          . " getopts optstring name [arg ...]                                                wait [-fn] [-p var] [id ...]\n"
          . " hash [-lr] [-p pathname] [-dt] [name ...]                                      while COMMANDS; do COMMANDS; done\n"
          . " help [-dms] [pattern ...]                                                       { COMMANDS ; }");

    // ── id ──
    case 'id':
        $u = ($args !== '') ? $args : $user;
        out('uid=0(root) gid=0(root) groups=0(root),1(bin),2(daemon),3(sys),4(adm),6(disk),10(wheel)');

    // ── which ──
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
        ];
        $results = [];
        foreach (explode(' ', $args) as $w) {
            $w = trim($w);
            if ($w === '') continue;
            if (isset($bins[$w])) $results[] = $bins[$w];
            else { err('/usr/bin/which: no ' . $w . ' in (/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin)'); }
        }
        out(implode("\n", $results));

    // ── env ──
    case 'env':
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

    // ── alias ──
    case 'alias':
        if ($args === '') {
            out("alias egrep='egrep --color=auto'\n"
              . "alias fgrep='fgrep --color=auto'\n"
              . "alias grep='grep --color=auto'\n"
              . "alias l='ls -CF'\n"
              . "alias la='ls -A'\n"
              . "alias ll='ls -alF'\n"
              . "alias ls='ls --color=auto'\n"
              . "alias vi='vim'");
        }
        out('');   // alias name=value: accepted silently

    // ── last ──
    case 'last':
        $prev = date('D M j H:i', time() - 86400);
        $prev2= date('D M j H:i', time() - 172800);
        out("root     pts/0        192.168.1.42     " . date('D M j H:i') . "   still logged in\n"
          . "root     pts/0        192.168.1.42     " . $prev . "  - " . date('H:i', time()-82800) . "  (23:00)\n"
          . "root     pts/1        10.0.0.5         " . $prev2 . "  - " . date('H:i', time()-169200) . "  (01:12)\n"
          . "deploy   pts/2        10.0.0.12        " . $prev2 . "  - " . date('H:i', time()-168000) . "  (00:48)\n"
          . "\nwtmp begins " . date('D M j', strtotime('-30 days')) . " 00:00");

    // ── top ──
    case 'top':
        $load = sys_getloadavg();
        $upSecs = time() - $_SESSION['boot'];
        $upH = floor($upSecs/3600); $upM = floor(($upSecs%3600)/60);
        $procs = [
            ['pid'=>1,    'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>169440,  'res'=>11264, 'shr'=>8192,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.1, 'time'=>'0:04.12', 'cmd'=>'systemd'],
            ['pid'=>432,  'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>28356,   'res'=>9832,  'shr'=>7680,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.1, 'time'=>'0:00.43', 'cmd'=>'systemd-journald'],
            ['pid'=>914,  'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>15428,   'res'=>8732,  'shr'=>6144,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.1, 'time'=>'0:00.11', 'cmd'=>'sshd'],
            ['pid'=>1105, 'user'=>'www-data', 'pr'=>20, 'ni'=>0,  'virt'=>256440,  'res'=>24688, 'shr'=>18432, 's'=>'S', 'cpu'=>0.3, 'mem'=>0.3, 'time'=>'0:01.77', 'cmd'=>'apache2'],
            ['pid'=>1212, 'user'=>'mysql',    'pr'=>20, 'ni'=>0,  'virt'=>1823440, 'res'=>118344,'shr'=>12288, 's'=>'S', 'cpu'=>0.7, 'mem'=>1.4, 'time'=>'2:14.55', 'cmd'=>'mysqld'],
            ['pid'=>1380, 'user'=>'redis',    'pr'=>20, 'ni'=>0,  'virt'=>62840,   'res'=>4096,  'shr'=>2048,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.1, 'time'=>'0:02.34', 'cmd'=>'redis-server'],
            ['pid'=>1512, 'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>11440,   'res'=>2048,  'shr'=>1536,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.0, 'time'=>'0:00.06', 'cmd'=>'crond'],
            ['pid'=>1890, 'user'=>'php-fpm',  'pr'=>20, 'ni'=>0,  'virt'=>194560,  'res'=>32768, 'shr'=>16384, 's'=>'S', 'cpu'=>0.1, 'mem'=>0.4, 'time'=>'0:03.22', 'cmd'=>'php-fpm: pool www'],
            ['pid'=>2048, 'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>14532,   'res'=>2048,  'shr'=>1536,  's'=>'S', 'cpu'=>0.0, 'mem'=>0.0, 'time'=>'0:00.02', 'cmd'=>'-bash'],
            ['pid'=>2091, 'user'=>'root',     'pr'=>20, 'ni'=>0,  'virt'=>17640,   'res'=>1948,  'shr'=>1280,  's'=>'R', 'cpu'=>0.0, 'mem'=>0.0, 'time'=>'0:00.00', 'cmd'=>'top'],
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

    // ── wget ──
    case 'wget':
        if ($args === '') err('wget: missing URL');
        $wUrl = '';
        $wOut = '';
        for ($wi = 0; $wi < count($argv); $wi++) {
            if (($argv[$wi] === '-O' || $argv[$wi] === '--output-document') && isset($argv[$wi+1])) {
                $wOut = $argv[$wi+1]; $wi++;
            } elseif ($argv[$wi][0] !== '-') {
                $wUrl = $argv[$wi];
            }
        }
        if ($wUrl === '') err('wget: missing URL');
        preg_match('#^(?:https?://)?([^/]+)(/.+)?$#', $wUrl, $wm);
        $wHost = isset($wm[1]) ? $wm[1] : $wUrl;
        $wPath = isset($wm[2]) ? $wm[2] : '/';
        $wFile = $wOut ?: basename($wPath) ?: 'index.html';
        $wSize = rand(512, 8192);
        echo json_encode([
            'output'    => '',
            'wget'      => true,
            'url'       => $wUrl,
            'host'      => $wHost,
            'file'      => $wFile,
            'size'      => $wSize,
        ]);
        exit;

    // ── curl ──
    case 'curl':
        if ($args === '') err('curl: try \'curl --help\' for more information');
        $cUrl  = '';
        $cOut  = '';
        $cSilent = false;
        for ($ci = 0; $ci < count($argv); $ci++) {
            if (($argv[$ci] === '-o' || $argv[$ci] === '--output') && isset($argv[$ci+1])) {
                $cOut = $argv[$ci+1]; $ci++;
            } elseif ($argv[$ci] === '-O' || $argv[$ci] === '--remote-name') {
                $cOut = '__remote__';
            } elseif ($argv[$ci] === '-s' || $argv[$ci] === '--silent') {
                $cSilent = true;
            } elseif ($argv[$ci][0] !== '-') {
                $cUrl = $argv[$ci];
            }
        }
        if ($cUrl === '') err('curl: try \'curl --help\' for more information');
        preg_match('#^(?:https?://)?([^/]+)(/.+)?$#', $cUrl, $cm);
        $cHost = isset($cm[1]) ? $cm[1] : $cUrl;
        $cPath = isset($cm[2]) ? $cm[2] : '/';
        if ($cOut === '__remote__') $cOut = basename($cPath) ?: 'index.html';
        $cSize = rand(1024, 16384);
        echo json_encode([
            'output'  => '',
            'curl'    => true,
            'url'     => $cUrl,
            'host'    => $cHost,
            'file'    => $cOut,
            'size'    => $cSize,
            'silent'  => $cSilent,
        ]);
        exit;

    // ── ping ──
    case 'ping':
        if ($args === '') err('ping: usage error: Destination address required');
        // strip flags, grab host
        $pingHost = '';
        $pingCount = 4;
        $av = $argv;
        for ($pi = 0; $pi < count($av); $pi++) {
            if ($av[$pi] === '-c' && isset($av[$pi+1])) {
                $pingCount = max(1, min(10, (int)$av[$pi+1]));
                $pi++;
            } elseif ($av[$pi][0] !== '-') {
                $pingHost = $av[$pi];
            }
        }
        if ($pingHost === '') err('ping: usage error: Destination address required');
        // fake IP mapping
        $knownHosts = [
            'localhost'       => '127.0.0.1',
            '127.0.0.1'       => '127.0.0.1',
            'google.com'      => '142.250.185.46',
            'www.google.com'  => '142.250.185.46',
            'github.com'      => '140.82.121.4',
            'cloudflare.com'  => '104.16.132.229',
            '1.1.1.1'         => '1.1.1.1',
            '8.8.8.8'         => '8.8.8.8',
            'amazon.com'      => '205.251.242.103',
            'microsoft.com'   => '20.76.201.171',
        ];
        $ip = isset($knownHosts[$pingHost]) ? $knownHosts[$pingHost]
            : implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)]);
        // build lines
        $ttl = ($ip === '127.0.0.1') ? 64 : 55;
        $packets = [];
        for ($pi = 1; $pi <= $pingCount; $pi++) {
            $ms = ($ip === '127.0.0.1') ? round(0.04 + lcg_value()*0.05, 3)
                                        : round(8 + lcg_value()*20, 3);
            $packets[] = sprintf('64 bytes from %s (%s): icmp_seq=%d ttl=%d time=%.3f ms', $pingHost, $ip, $pi, $ttl, $ms);
        }
        $times = array_map(function($l){ preg_match('/time=([\d.]+)/',$l,$m); return (float)$m[1]; }, $packets);
        $summary = sprintf("--- %s ping statistics ---\n%d packets transmitted, %d received, 0%% packet loss, time %dms\nrtt min/avg/max/mdev = %.3f/%.3f/%.3f/%.3f ms",
            $pingHost, $pingCount, $pingCount,
            (int)(array_sum($times)*1.1),
            min($times), array_sum($times)/count($times), max($times),
            (max($times)-min($times))/2
        );
        echo json_encode([
            'output'   => '',
            'ping'     => true,
            'header'   => 'PING ' . $pingHost . ' (' . $ip . ') 56(84) bytes of data.',
            'packets'  => $packets,
            'summary'  => $summary,
        ]);
        exit;

    // ── man ──
    case 'man':
        if ($args === '') {
            out("What manual page do you want?\nFor example, try 'man ls'.");
        }
        $topic = strtolower(preg_split('/\s+/', $args)[0]);
        $pages = [
            'ls'       => "LS(1)                     User Commands                    LS(1)\n\nNAME\n       ls - list directory contents\n\nSYNOPSIS\n       ls [OPTION]... [FILE]...\n\nDESCRIPTION\n       List  information  about  the FILEs (the current directory by default).\n       Sort entries alphabetically if none of -cftuvSUX nor --sort  is  speci-\n       fied.\n\n       -a, --all\n              do not ignore entries starting with .\n\n       -l     use a long listing format\n\n       -h, --human-readable\n              with -l and -s, print sizes like 1K 234M 2G etc.\n\n       -r, --reverse\n              reverse order while sorting\n\n       -t     sort by time, newest first\n\nAUTHOR\n       Written by Richard M. Stallman and David MacKenzie.\n\nGNU coreutils                    March 2026                          LS(1)",

            'cat'      => "CAT(1)                    User Commands                   CAT(1)\n\nNAME\n       cat - concatenate files and print on the standard output\n\nSYNOPSIS\n       cat [OPTION]... [FILE]...\n\nDESCRIPTION\n       Concatenate FILE(s) to standard output.\n\n       -n, --number\n              number all output lines\n\n       -v, --show-nonprinting\n              use ^ and M- notation, except for LFD and TAB\n\nAUTHOR\n       Written by Torbjorn Granlund and Richard M. Stallman.\n\nGNU coreutils                    March 2026                         CAT(1)",

            'cd'       => "CD(1)                     bash built-in                    CD(1)\n\nNAME\n       cd - change the shell working directory\n\nSYNOPSIS\n       cd [-L|[-P [-e]] [-@]] [dir]\n\nDESCRIPTION\n       Change  the  current  directory  to dir.  The default DIR is the value\n       of the HOME shell variable.  The variable CDPATH defines  the  search\n       path for the directory containing dir.\n\n       -L     force symbolic links to be followed\n       -P     use the physical directory structure\n\nGNU bash                         March 2026                          CD(1)",

            'pwd'      => "PWD(1)                    User Commands                   PWD(1)\n\nNAME\n       pwd - print name of current/working directory\n\nSYNOPSIS\n       pwd [OPTION]...\n\nDESCRIPTION\n       Print the full filename of the current working directory.\n\n       -L, --logical\n              use PWD from environment, even if it contains symlinks\n\n       -P, --physical\n              avoid all symlinks\n\nAUTHOR\n       Written by Jim Meyering.\n\nGNU coreutils                    March 2026                         PWD(1)",

            'mkdir'    => "MKDIR(1)                  User Commands                 MKDIR(1)\n\nNAME\n       mkdir - make directories\n\nSYNOPSIS\n       mkdir [OPTION]... DIRECTORY...\n\nDESCRIPTION\n       Create the DIRECTORY(ies), if they do not already exist.\n\n       -m, --mode=MODE\n              set file mode (as in chmod), not a=rwx - umask\n\n       -p, --parents\n              no error if existing, make parent directories as needed\n\n       -v, --verbose\n              print a message for each created directory\n\nAUTHOR\n       Written by David MacKenzie.\n\nGNU coreutils                    March 2026                       MKDIR(1)",

            'rm'       => "RM(1)                     User Commands                    RM(1)\n\nNAME\n       rm - remove files or directories\n\nSYNOPSIS\n       rm [OPTION]... [FILE]...\n\nDESCRIPTION\n       This manual page documents the GNU version of rm.  rm removes each\n       specified file.  By default, it does not remove directories.\n\n       -f, --force\n              ignore nonexistent files and arguments, never prompt\n\n       -r, -R, --recursive\n              remove directories and their contents recursively\n\n       -v, --verbose\n              explain what is being done\n\nWARNING\n       If you use rm to remove a file, it might be possible to  recover  some\n       of its contents. If you want more assurance that the contents are truly\n       unrecoverable,  consider  using  shred(1).\n\nAUTHOR\n       Written by Paul Rubin, David MacKenzie, Richard Stallman, Jim Meyering.\n\nGNU coreutils                    March 2026                          RM(1)",

            'touch'    => "TOUCH(1)                  User Commands                 TOUCH(1)\n\nNAME\n       touch - change file timestamps\n\nSYNOPSIS\n       touch [OPTION]... FILE...\n\nDESCRIPTION\n       Update the access and modification times of each FILE to the current\n       time.  A FILE argument that does not exist is created empty.\n\n       -a     change only the access time\n\n       -m     change only the modification time\n\n       -t STAMP\n              use [[CC]YY]MMDDhhmm[.ss] instead of current time\n\nAUTHOR\n       Written by Paul Rubin, Arnold Robbins, Jim Kingdon, David MacKenzie,\n       and Randy Smith.\n\nGNU coreutils                    March 2026                       TOUCH(1)",

            'grep'     => "GREP(1)                   User Commands                  GREP(1)\n\nNAME\n       grep, egrep, fgrep - print lines that match patterns\n\nSYNOPSIS\n       grep [OPTION...] PATTERNS [FILE...]\n\nDESCRIPTION\n       grep  searches  for  PATTERNS  in  each FILE. PATTERNS is one or more\n       patterns separated by newline characters, and grep prints each line\n       that matches a pattern.\n\n       -i, --ignore-case\n              ignore case distinctions in patterns and data\n\n       -r, --recursive\n              read all files under each directory, recursively\n\n       -n, --line-number\n              print line number with output lines\n\n       -v, --invert-match\n              select non-matching lines\n\n       -c, --count\n              print only a count of selected lines per FILE\n\nAUTHOR\n       Written by Mike Haertel and others.\n\nGNU grep                         March 2026                        GREP(1)",

            'ssh'      => "SSH(1)                    User Commands                   SSH(1)\n\nNAME\n       ssh - OpenSSH remote login client\n\nSYNOPSIS\n       ssh [-46AaCfGgKkMNnqsTtVvXxYy] [-B bind_interface]\n           [-b bind_address] [-c cipher_spec] [-D [bind_address:]port]\n           [-E log_file] [-e escape_char] [-F configfile] [-I pkcs11]\n           [-i identity_file] [-J [user@]host[:port]] [-L address]\n           [-l login_name] [-m mac_spec] [-O ctl_cmd] [-o option]\n           [-p port] [-Q query_option] [-R address] [-S ctl_path]\n           [-W host:port] [-w local_tun[:remote_tun]] destination\n           [command [argument ...]]\n\nDESCRIPTION\n       ssh  (SSH client) is a program for logging into a remote machine and\n       for executing commands on a remote machine.\n\n       -p port\n              Port to connect to on the remote host.\n\n       -i identity_file\n              Selects a file from which the identity (private key) for\n              public key authentication is read.\n\n       -v     Verbose mode.\n\nOpenSSH                          March 2026                         SSH(1)",

            'ping'     => "PING(8)               System Manager's Manual              PING(8)\n\nNAME\n       ping - send ICMP ECHO_REQUEST to network hosts\n\nSYNOPSIS\n       ping [-aAbBdDfhLnOqrRUvV46] [-c count] [-F flowlabel]\n            [-i interval] [-I interface] [-l preload] [-m mark]\n            [-M pmtudisc_option] [-N nodeinfo_option]\n            [-w deadline] [-W timeout] {destination}\n\nDESCRIPTION\n       ping uses the ICMP protocol's mandatory ECHO_REQUEST datagram\n       to elicit an ICMP ECHO_RESPONSE from a host or gateway.\n\n       -c count\n              Stop after sending count ECHO_REQUEST packets.\n\n       -i interval\n              Wait interval seconds between sending each packet.\n\n       -t ttl\n              Set the IP Time to Live.\n\nIPutils                          March 2026                        PING(8)",

            'df'       => "DF(1)                     User Commands                    DF(1)\n\nNAME\n       df - report file system space usage\n\nSYNOPSIS\n       df [OPTION]... [FILE]...\n\nDESCRIPTION\n       This  manual  page  documents  the  GNU version of df.  df displays\n       the amount of space available on the file system containing each  file\n       name argument.\n\n       -h, --human-readable\n              print sizes in powers of 1024 (e.g., 1023M)\n\n       -T, --print-type\n              print file system type\n\n       -i, --inodes\n              list inode information instead of block usage\n\nAUTHOR\n       Written by Torbjorn Granlund, David MacKenzie, and Paul Eggert.\n\nGNU coreutils                    March 2026                          DF(1)",

            'free'     => "FREE(1)                   User Commands                  FREE(1)\n\nNAME\n       free - Display amount of free and used memory in the system\n\nSYNOPSIS\n       free [options]\n\nDESCRIPTION\n       free displays the total amount of free and used physical and swap\n       memory in the system, as well as the buffers and caches used by the\n       kernel.\n\n       -b, --bytes\n              Display the amount of memory in bytes.\n\n       -h, --human\n              Show all output fields automatically scaled to shortest three\n              digit unit.\n\n       -s delay, --seconds delay\n              Continuously display the result delay seconds apart.\n\nAUTHOR\n       Written by Brian Edmonds.\n\nprocps-ng                        March 2026                        FREE(1)",

            'ps'       => "PS(1)                     User Commands                    PS(1)\n\nNAME\n       ps - report a snapshot of the current processes\n\nSYNOPSIS\n       ps [options]\n\nDESCRIPTION\n       ps displays information about a selection of the active processes.\n\n       a      Lift the BSD-style \"only yourself\" restriction.\n       u      Display user-oriented format.\n       x      Lift the BSD-style \"must have a tty\" restriction.\n\n       -e     Select all processes.\n       -f     Full-format listing.\n\nAUTHOR\n       ps was originally written by Branko Lankester.\n\nprocps-ng                        March 2026                          PS(1)",

            'top'      => "TOP(1)                    User Commands                   TOP(1)\n\nNAME\n       top - display Linux processes\n\nSYNOPSIS\n       top -hv|-bcEeHiOSs1 -d secs -n max -u|U user -p pids -o field\n           -w [cols]\n\nDESCRIPTION\n       The top program provides a dynamic real-time view of a running system.\n\nINTERACTIVE COMMANDS\n       q      Quit\n       k      Kill a task\n       r      Renice a task\n       1      Toggle single/multiple CPU states\n       m      Toggle memory information\n       SPACE  Refresh display\n\nAUTHOR\n       Written by Roger Binns.\n\nprocps-ng                        March 2026                         TOP(1)",

            'uname'    => "UNAME(1)                  User Commands                 UNAME(1)\n\nNAME\n       uname - print system information\n\nSYNOPSIS\n       uname [OPTION]...\n\nDESCRIPTION\n       Print certain system information.  With no OPTION, same as -s.\n\n       -a, --all\n              print all information, in the following order, except omit\n              -p and -i if unknown:\n\n       -s, --kernel-name\n              print the kernel name\n\n       -n, --nodename\n              print the network node hostname\n\n       -r, --kernel-release\n              print the kernel release\n\n       -m, --machine\n              print the machine hardware name\n\nAUTHOR\n       Written by David MacKenzie.\n\nGNU coreutils                    March 2026                       UNAME(1)",

            'chmod'    => "CHMOD(1)                  User Commands                 CHMOD(1)\n\nNAME\n       chmod - change file mode bits\n\nSYNOPSIS\n       chmod [OPTION]... MODE[,MODE]... FILE...\n       chmod [OPTION]... OCTAL-MODE FILE...\n       chmod [OPTION]... --reference=RFILE FILE...\n\nDESCRIPTION\n       This  manual  page  documents  the  GNU  version of chmod. chmod\n       changes the file mode bits of each given file according to mode.\n\n       -R, --recursive\n              change files and directories recursively\n\n       -v, --verbose\n              output a diagnostic for every file processed\n\nAUTHOR\n       Written by David MacKenzie and Jim Meyering.\n\nGNU coreutils                    March 2026                       CHMOD(1)",

            'curl'     => "CURL(1)                   User Commands                  CURL(1)\n\nNAME\n       curl - transfer a URL\n\nSYNOPSIS\n       curl [options / URLs]\n\nDESCRIPTION\n       curl  is  a tool for transferring data from or to a server using URLs.\n\n       -o, --output <file>\n              Write output to <file> instead of stdout.\n\n       -O, --remote-name\n              Write output to a local file named like the remote file.\n\n       -s, --silent\n              Silent mode. Don't show progress meter or error messages.\n\n       -L, --location\n              If the server reports that the requested page has moved,\n              redo the request on the new place.\n\n       -v, --verbose\n              Makes curl verbose during the operation.\n\nAUTHOR\n       Written by Daniel Stenberg.\n\ncurl                             March 2026                        CURL(1)",

            'wget'     => "WGET(1)                   User Commands                  WGET(1)\n\nNAME\n       wget - The non-interactive network downloader.\n\nSYNOPSIS\n       wget [option]... [URL]...\n\nDESCRIPTION\n       GNU  Wget  is  a  free  utility  for  non-interactive download of\n       files from the Web.\n\n       -q, --quiet\n              Turn off Wget's output.\n\n       -O file, --output-document=file\n              The documents will not be written to the appropriate files,\n              but all will be concatenated together and written to file.\n\n       -r, --recursive\n              Turn on recursive retrieving.\n\n       -c, --continue\n              Continue getting a partially-downloaded file.\n\nAUTHOR\n       Written by Hrvoje Niksic.\n\nGNU Wget                         March 2026                        WGET(1)",

            'sudo'     => "SUDO(8)               System Manager's Manual              SUDO(8)\n\nNAME\n       sudo, sudoedit — execute a command as another user\n\nSYNOPSIS\n       sudo -h | -K | -k | -V\n       sudo -v [-ABkNnS] [-g group] [-h host] [-p prompt] [-u user]\n       sudo [-ABbEHnPS] [-C num] [-D directory] [-g group] [-h host]\n           [-R directory] [-T timeout] [-u user] [VAR=value]\n           [-i | -s] [command [argument ...]]\n\nDESCRIPTION\n       sudo allows a permitted user to execute a command as the superuser\n       or another user.\n\n       -u user, --user=user\n              Run the command as a user other than the default target\n              user (usually root).\n\n       -l, --list\n              If no command is specified, list the allowed (and forbidden)\n              commands for the invoking user.\n\nTODD C. MILLER                   March 2026                        SUDO(8)",

            'history'  => "HISTORY(3)             bash built-in                 HISTORY(3)\n\nNAME\n       history - GNU History Library\n\nSYNOPSIS\n       history [-c] [-d offset] [n]\n       history -anrw [filename]\n       history -ps arg [arg...]\n\nDESCRIPTION\n       Without options, display the command history list with line numbers.\n\n       -c     Clear the history list by deleting all the entries.\n\n       -d offset\n              Delete the history entry at position offset.\n\n       -a     Append the 'new' history lines to the history file.\n\n       -r     Read the history file and append the contents to the history\n              list.\n\nGNU bash                         March 2026                     HISTORY(3)",

            'echo'     => "ECHO(1)                   User Commands                  ECHO(1)\n\nNAME\n       echo - display a line of text\n\nSYNOPSIS\n       echo [SHORT-OPTION]... [STRING]...\n       echo LONG-OPTION\n\nDESCRIPTION\n       Echo the STRING(s) to standard output.\n\n       -n     do not output the trailing newline\n\n       -e     enable interpretation of backslash escapes\n\n       -E     disable interpretation of backslash escapes (default)\n\n       If -e is in effect, the following sequences are recognized:\n\n       \\\\     backslash\n       \\n     new line\n       \\t     horizontal tab\n\nAUTHOR\n       Written by Brian Fox and Chet Ramey.\n\nGNU coreutils                    March 2026                        ECHO(1)",
        ];
        if (isset($pages[$topic])) {
            out($pages[$topic]);
        }
        err('No manual entry for ' . $topic);

    // ── unknown ──
    default:
        err('bash: ' . $cmd . ': command not found');
}
