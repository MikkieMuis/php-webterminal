<?php
//  php-webterminal — command handler
//  POST { cmd: "ls -la", user: "root" }  →  JSON { output: "..." }

require_once __DIR__ . '/config.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// system info (from config — never use shell_exec/os-release)
define('SYS_KERNEL', CONF_KERNEL);
define('SYS_ARCH',   CONF_ARCH);
define('SYS_OS',     CONF_OS);

// sysinfo endpoint (GET ?sysinfo)
if (isset($_GET['sysinfo'])) {
    echo json_encode([
        'kernel'   => SYS_KERNEL,
        'arch'     => SYS_ARCH,
        'os'       => SYS_OS,
        'hostname' => CONF_HOSTNAME,
    ]);
    exit;
}

// initialise session filesystem
// Bump this version string whenever fs_data.php changes to force a session reset.
define('FS_VERSION', '11');

if (!isset($_SESSION['fs']) || ($_SESSION['fs_version'] ?? '') !== FS_VERSION) {
    require_once __DIR__ . '/fs_data.php';
    $_SESSION['fs']         = fs_get_data();
    $_SESSION['fs_version'] = FS_VERSION;
    $_SESSION['cwd']        = '/root';
    $_SESSION['cmdlog']     = [];
    $_SESSION['boot']       = time();
}
if (!isset($_SESSION['cwd']))    $_SESSION['cwd']    = '/root';
if (!isset($_SESSION['cmdlog'])) $_SESSION['cmdlog'] = [];
if (!isset($_SESSION['boot']))   $_SESSION['boot']   = time();

// helpers
function out($text) {
    echo json_encode(['output' => $text]);
    exit;
}

function err($text) {
    echo json_encode(['output' => $text, 'error' => true]);
    exit;
}

function res_path($path) {
    if ($path === '' || $path === null) return $_SESSION['cwd'];
    if ($path[0] !== '/') $path = $_SESSION['cwd'] . '/' . $path;
    $parts = explode('/', $path);
    $stack = [];
    foreach ($parts as $p) {
        if ($p === '' || $p === '.') continue;
        if ($p === '..') { array_pop($stack); continue; }
        $stack[] = $p;
    }
    return '/' . implode('/', $stack);
}

function ls_dir($path, $long, $cols = 80) {
    $fs      = $_SESSION['fs'];
    $prefix  = rtrim($path, '/');
    $entries = [];
    foreach ($fs as $p => $node) {
        if ($p === $path) continue;
        $parent = ($prefix === '') ? '/' : $prefix;
        $dir    = dirname($p);
        if ($dir !== $parent) continue;
        $name  = basename($p);
        $perm  = $node['type'] === 'dir' ? 'drwxr-xr-x' : '-rw-r--r--';
        $size  = isset($node['content']) ? strlen($node['content']) : 4096;
        $mtime = isset($node['mtime'])   ? date('M d H:i', $node['mtime']) : 'Mar  9 08:11';
        $entries[] = ['name'=>$name,'perm'=>$perm,'size'=>$size,'mtime'=>$mtime,'type'=>$node['type']];
    }
    if (empty($entries)) return $long ? 'total 0' : '';
    usort($entries, function($a,$b){ return strcmp($a['name'],$b['name']); });
    if (!$long) {
        $names    = array_map(function($e){ return $e['name']; }, $entries);
        $maxLen   = max(array_map('strlen', $names));
        $colWidth = $maxLen + 2;
        $numCols  = max(1, (int)floor($cols / $colWidth));
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
        return implode("\n", $lines);
    }
    $lines = ['total ' . (count($entries)*8)];
    foreach ($entries as $e) {
        $lines[] = sprintf('%s  2 root root %6d  %s  %s', $e['perm'], $e['size'], $e['mtime'], $e['name']);
    }
    return implode("\n", $lines);
}

// read input
$raw_body = file_get_contents('php://input', false, null, 0, 4096);  // cap at 4 KB
$body = json_decode($raw_body, true);
$raw  = isset($body['cmd'])  ? substr(trim($body['cmd']),  0, 1024) : '';
$user = isset($body['user']) ? substr(trim($body['user']), 0, 64)   : 'user';
$cols = isset($body['cols']) ? max(20, min(500, (int)$body['cols'])) : 80;

// Determine home directory for this user
$userHome = ($user === 'root') ? '/root' : '/home/' . $user;

// If the session's cwd is still the default /root but we are not root, reset to user's home.
// This handles the case where a non-root user logs in fresh.
if ($_SESSION['cwd'] === '/root' && $user !== 'root') {
    $_SESSION['cwd'] = $userHome;
}

if ($raw === '') out('');

// log command (cap entry length and total entries)
$_SESSION['cmdlog'][] = substr($raw, 0, 1024);
if (count($_SESSION['cmdlog']) > 100) {
    $_SESSION['cmdlog'] = array_slice($_SESSION['cmdlog'], -100);
}

// parse
$parts = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
$cmd   = strtolower($parts[0]);
$args  = implode(' ', array_slice($parts, 1));
$argv  = array_slice($parts, 1);   // individual args as array

// alias expansion
if ($cmd === 'll') { $cmd = 'ls'; array_unshift($argv, '-la'); $args = implode(' ', $argv); }

// command dispatch
switch ($cmd) {
    case 'ls': case 'cd': case 'mkdir': case 'rmdir': case 'touch': case 'rm': case 'cat':
    case 'wc': case 'more': case 'less': case 'grep': case 'cp': case 'mv':
    case 'head': case 'tail': case 'du': case 'chmod': case 'chown': case 'diff': case 'sort':
    case 'uniq':
        require __DIR__ . '/commands/filesystem.php';
        break;

    case 'zip': case 'unzip': case 'tar':
        require __DIR__ . '/commands/archive.php';
        break;

    case 'whoami': case 'pwd': case 'hostname': case 'uname': case 'uptime':
    case 'date': case 'df': case 'free': case 'ps': case 'top': case 'htop':
    case 'id': case 'env': case 'printenv': case 'which':
    case 'fastfetch': case 'neofetch': case 'systemctl': case 'php':
    case 'exa': case 'firewall-cmd':
        require __DIR__ . '/commands/system.php';
        break;

    case 'ifconfig': case 'ip': case 'ping': case 'wget': case 'curl':
    case 'telnet': case 'sendmail':
        require __DIR__ . '/commands/network.php';
        break;

    case 'echo': case 'clear': case 'exit': case 'logout': case 'history':
    case 'help': case 'alias': case 'last': case 'sudo': case 'man':
    case 'passwd': case 'base64': case 'bc':
        require __DIR__ . '/commands/shell.php';
        break;

    case 'nano': case 'joe': case '__nano_save':
        require __DIR__ . '/commands/editors.php';
        break;

    case 'dnf':
        require __DIR__ . '/commands/package.php';
        break;

    default:
        err('bash: ' . $cmd . ': command not found');
}
