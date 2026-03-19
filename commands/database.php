<?php
// database.php — mysql / mariadb interactive client
// Receives: $cmd, $args, $argv, $user, $body (from terminal.php / terminal_runner.php scope)

// Parse common flags: -u USER, -p, -h HOST, -P PORT, [DATABASE]
$dbUser = 'root';
$dbHost = 'localhost';
$dbName = null;
$awaitPassword = false;

$i = 0;
while ($i < count($argv)) {
    $a = $argv[$i];
    if ($a === '-u' && isset($argv[$i+1])) { $dbUser = $argv[++$i]; }
    elseif (preg_match('/^-u(.+)$/', $a, $m)) { $dbUser = $m[1]; }
    elseif ($a === '-p' || preg_match('/^-p/', $a)) { $awaitPassword = true; }
    elseif ($a === '-h' && isset($argv[$i+1])) { $dbHost = $argv[++$i]; }
    elseif ($a === '-P' && isset($argv[$i+1])) { $i++; /* skip port */ }
    elseif ($a === '-e' && isset($argv[$i+1])) { $i++; /* skip inline query for now */ }
    elseif ($a[0] !== '-') { $dbName = $a; }
    $i++;
}

// Non-root cannot connect as root (cosmetic)
if ($user !== 'root' && $dbUser === 'root') {
    err('ERROR 1045 (28000): Access denied for user \'root\'@\'localhost\' (using password: NO)');
}

echo json_encode([
    'output' => '',
    'mysql'  => true,
    'user'   => $dbUser,
    'host'   => $dbHost,
    'db'     => $dbName,
]);
exit;
