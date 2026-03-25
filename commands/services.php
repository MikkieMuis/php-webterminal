<?php
//  services commands: systemctl, firewall-cmd, journalctl
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // systemctl
    case 'systemctl': {
        if ($user !== 'root') {
            err('Failed to connect to bus: Permission denied');
            break;
        }
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

        // Services that are stopped/disabled by default
        if (!isset($_SESSION['svc_stopped'])) {
            $_SESSION['svc_stopped']  = ['nginx'];      // inactive by default
            $_SESSION['svc_disabled'] = ['nginx'];      // disabled by default
        }
        $stopped  = $_SESSION['svc_stopped'];
        $disabled = $_SESSION['svc_disabled'];

        // no subcommand
        if ($subcmd === '') {
            out("Usage: systemctl [OPTIONS...] COMMAND ...\n\nQuery or send control commands to the system manager.\n\nUnit Commands:\n  start NAME...             Start (activate) one or more units\n  stop NAME...              Stop (deactivate) one or more units\n  restart NAME...           Start or restart one or more units\n  status [NAME...|PID...]   Show runtime status of one or more units\n  enable NAME...            Enable one or more unit files\n  disable NAME...           Disable one or more unit files\n  is-active PATTERN...      Check whether units are active\n  list-units [PATTERN...]   List loaded units\n\nSee 'man systemctl' for details.");
        }

        // list-units
        if ($subcmd === 'list-units') {
            $lines = ['  UNIT                    LOAD   ACTIVE SUB     DESCRIPTION'];
            // Output all known services in a fixed display order
            $listOrder = ['crond','firewalld','httpd','mariadb','php-fpm','sshd','nginx'];
            foreach ($listOrder as $sn) {
                if (!isset($services[$sn])) continue;
                $svc    = $services[$sn];
                $unit   = str_pad($svc['label'], 23);
                if (in_array($sn, $stopped)) {
                    $lines[] = '  ' . $unit . ' loaded active exited  ' . $svc['desc'];
                } else {
                    $lines[] = '  ' . $unit . ' loaded active running ' . $svc['desc'];
                }
            }
            $lines[] = '';
            $lines[] = 'LOAD   = Reflects whether the unit definition was properly loaded.';
            $lines[] = 'ACTIVE = The high-level unit activation state.';
            $lines[] = 'SUB    = The low-level unit activation state.';
            $lines[] = '';
            $lines[] = count($listOrder) . ' loaded units listed.';
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

            $svc       = $services[$service];
            $isStopped  = in_array($service, $stopped);
            $isDisabled = in_array($service, $disabled);

            if ($subcmd === 'status') {
                $active  = $isStopped ? 'inactive (dead)' : 'active (running)';
                $since   = date('D Y-m-d H:i:s T', time() - rand(3600, 86400));

                // ANSI colour codes matching real systemd output
                $bold       = "\e[1m";
                $reset      = "\e[0m";
                $green      = "\e[32m";
                $red        = "\e[31m";
                $white      = "\e[37m";
                $boldGreen  = "\e[1;32m";
                $boldWhite  = "\e[1;37m";

                // dot + service name line
                if ($isStopped) {
                    $dotLine = $white . '●' . $reset . ' ' . $bold . $svc['label'] . ' - ' . $svc['desc'] . $reset;
                } else {
                    $dotLine = $green . '●' . $reset . ' ' . $bold . $svc['label'] . ' - ' . $svc['desc'] . $reset;
                }

                // Loaded line — enabled/disabled state from session
                $enabledStr = $isDisabled
                    ? $white . 'disabled' . $reset
                    : $green . 'enabled'  . $reset;
                $loadedLine = '   Loaded: loaded (/usr/lib/systemd/system/' . $svc['label'] . '; '
                    . $enabledStr
                    . '; vendor preset: ' . $white . 'disabled' . $reset . ')';

                // Active line — colour depends on state
                if ($isStopped) {
                    $activeLine = '   Active: ' . $white . $active . $reset . ' since ' . $since;
                } else {
                    $activeLine = '   Active: ' . $boldGreen . $active . $reset . ' since ' . $since;
                }

                $lines = [ $dotLine, $loadedLine, $activeLine ];

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
                $_SESSION['svc_stopped'] = array_values(array_diff($_SESSION['svc_stopped'], [$service]));
                out('');
            }

            if ($subcmd === 'stop') {
                if (!in_array($service, $_SESSION['svc_stopped'])) {
                    $_SESSION['svc_stopped'][] = $service;
                }
                out('');
            }

            if ($subcmd === 'restart') {
                $_SESSION['svc_stopped'] = array_values(array_diff($_SESSION['svc_stopped'], [$service]));
                out('');
            }

            if ($subcmd === 'enable') {
                $_SESSION['svc_disabled'] = array_values(array_diff($_SESSION['svc_disabled'], [$service]));
                out('Created symlink /etc/systemd/system/multi-user.target.wants/' . $svc['label'] . ' → /usr/lib/systemd/system/' . $svc['label'] . '.');
            }

            if ($subcmd === 'disable') {
                if (!in_array($service, $_SESSION['svc_disabled'])) {
                    $_SESSION['svc_disabled'][] = $service;
                }
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

    // firewall-cmd
    case 'firewall-cmd': {
        if ($user !== 'root') {
            err('Authorization failed.');
            break;
        }
        // Supported: --state, --list-all, --add-port=PORT/proto, --remove-port=PORT/proto, --reload
        if ($args === '' || $args === '--help') {
            out("Usage: firewall-cmd [OPTIONS...]\n\nGeneral Options:\n  --state                  Return and print firewalld state\n  --reload                 Reload firewall and keep state information\n\nZone Options (default zone: public):\n  --list-all               List everything added for or enabled in the zone\n  --add-port=PORT/PROTO    Add the port to the zone\n  --remove-port=PORT/PROTO Remove the port from the zone\n  --list-ports             List ports added to the zone\n\nSee 'man firewall-cmd' for full documentation.");
        }

        if (strpos($args, '--state') !== false) {
            out('running');
        }

        if (strpos($args, '--reload') !== false) {
            out('success');
        }

        if (strpos($args, '--list-all') !== false) {
            out("public (active)\n  target: default\n  icmp-block-inversion: no\n  interfaces: eth0\n  sources:\n  services: cockpit dhcpv6-client http https ssh\n  ports: 80/tcp 443/tcp 8080/tcp\n  protocols:\n  forward: yes\n  masquerade: no\n  forward-ports:\n  source-ports:\n  icmp-blocks:\n  rich rules:");
        }

        if (strpos($args, '--list-ports') !== false) {
            out('80/tcp 443/tcp 8080/tcp');
        }

        if (preg_match('/--add-port=([0-9\-]+\/(?:tcp|udp))/i', $args, $m)) {
            out('success');
        }

        if (preg_match('/--remove-port=([0-9\-]+\/(?:tcp|udp))/i', $args, $m)) {
            out('success');
        }

        err('firewall-cmd: Unknown option ' . $args);
        break;
    }

    // journalctl
    case 'journalctl': {
        $follow   = (strpos($args, 'f') !== false && strpos($args, '-f') !== false);
        $reverse  = (strpos($args, 'r') !== false && strpos($args, '-r') !== false);
        $unit     = '';
        $since    = '';
        $lines    = -1;

        for ($ji = 0; $ji < count($argv); $ji++) {
            $a = $argv[$ji];
            if (($a === '-u' || $a === '--unit') && isset($argv[$ji+1])) {
                $unit = $argv[++$ji];
                $unit = preg_replace('/\.service$/', '', $unit);
            } elseif (preg_match('/^-u(.+)$/', $a, $m)) {
                $unit = preg_replace('/\.service$/', '', $m[1]);
            } elseif (($a === '-n' || $a === '--lines') && isset($argv[$ji+1])) {
                $lines = (int)$argv[++$ji];
            } elseif (preg_match('/^-n(\d+)$/', $a, $m)) {
                $lines = (int)$m[1];
            } elseif ($a === '--since' && isset($argv[$ji+1])) {
                $since = $argv[++$ji];
                $ji++; // skip next token (time part)
            }
        }

        // fake log entries per service
        $serviceLogs = [
            'httpd' => [
                "[httpd] AH00558: httpd: Could not reliably determine the server's fully qualified domain name",
                "[httpd] AH00094: Command line: '/usr/sbin/httpd -D FOREGROUND'",
                "[httpd] AH00163: Apache/2.4.57 (AlmaLinux) configured -- resuming normal operations",
                "[httpd] 192.168.1.42 - - GET / HTTP/1.1 200 1234",
                "[httpd] 93.184.216.34 - - GET /index.php HTTP/1.1 200 4567",
            ],
            'mariadb' => [
                "[mysqld] InnoDB: Buffer pool(s) load completed at " . date('y-m-d H:i:s'),
                "[mysqld] Server socket created on IP: '127.0.0.1'.",
                "[mysqld] mariadbd: ready for connections.",
                "[mysqld] Version: '10.5.22-MariaDB'  socket: '/var/lib/mysql/mysql.sock'",
            ],
            'mysqld' => [
                "[mysqld] InnoDB: Buffer pool(s) load completed at " . date('y-m-d H:i:s'),
                "[mysqld] Server socket created on IP: '127.0.0.1'.",
                "[mysqld] ready for connections.",
            ],
            'sshd' => [
                "[sshd] Server listening on 0.0.0.0 port 22.",
                "[sshd] Accepted publickey for root from 192.168.1.42 port 54321 ssh2",
                "[sshd] Disconnected from 192.168.1.42 port 54321",
            ],
            'php-fpm' => [
                "[php-fpm] pool www: pm started with " . rand(5,8) . " processes",
                "[php-fpm] NOTICE: fpm is running, pid " . rand(2000,2300),
                "[php-fpm] NOTICE: ready to handle connections",
            ],
            'crond' => [
                "[crond] crond startup succeeded",
                "[CROND] (root) CMD (/usr/bin/run-parts /etc/cron.hourly)",
                "[CROND] (root) CMD (/usr/lib/sa/sa1 1 1)",
            ],
            'firewalld' => [
                "[firewalld] FirewallD reloaded.",
                "[firewalld] Firewall zone 'public' set to be used as default zone",
                "[firewalld] ACCEPT: IN=eth0 OUT= MAC= SRC=192.168.1.42 DST=192.168.1.10 PROTO=TCP DPT=22",
            ],
        ];

        // Build log lines
        $allLines = [];
        $ts = time() - 3600;
        if ($unit !== '' && isset($serviceLogs[$unit])) {
            foreach ($serviceLogs[$unit] as $msg) {
                $ts += rand(30, 300);
                $allLines[] = date('M d H:i:s', $ts) . ' ' . CONF_HOSTNAME . ' ' . $msg;
            }
        } elseif ($unit !== '') {
            // Unknown unit
            $allLines[] = date('M d H:i:s') . ' ' . CONF_HOSTNAME . " systemd[1]: " . $unit . ".service: Unit not found.";
        } else {
            // General log
            $generalLogs = [
                "[kernel] Linux version " . SYS_KERNEL . " (mockbuild@build.almalinux.org)",
                "[systemd] Reached target Basic System.",
                "[systemd] Started OpenSSH server daemon.",
                "[sshd] Server listening on 0.0.0.0 port 22.",
                "[systemd] Started The Apache HTTP Server.",
                "[httpd] AH00163: Apache/2.4.57 configured -- resuming normal operations",
                "[systemd] Started MariaDB 10.5 database server.",
                "[mysqld] ready for connections. Version: '10.5.22-MariaDB'",
                "[systemd] Started PHP FastCGI Process Manager.",
                "[php-fpm] NOTICE: ready to handle connections",
                "[systemd] Startup finished in 2.417s (kernel) + 4.831s (userspace) = 7.248s.",
                "[CROND] (root) CMD (/usr/lib/sa/sa1 1 1)",
                "[sshd] Accepted publickey for root from 192.168.1.42 port 54321 ssh2",
                "[sudo] pam_unix(sudo:session): session opened for user root",
                "[systemd] Starting dnf makecache...",
                "[dnf] Metadata cache refreshed.",
            ];
            $ts = time() - count($generalLogs) * 180;
            foreach ($generalLogs as $msg) {
                $ts += rand(60, 360);
                $allLines[] = date('M d H:i:s', $ts) . ' ' . CONF_HOSTNAME . ' ' . $msg;
            }
        }

        if ($reverse) $allLines = array_reverse($allLines);
        if ($lines > 0) $allLines = array_slice($allLines, -$lines);

        $header = "-- Logs begin at " . date('D Y-m-d H:i:s T', time() - 86400) . " --";
        out($header . "\n" . implode("\n", $allLines));
    }
}
