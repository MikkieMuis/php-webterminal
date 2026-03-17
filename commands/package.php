<?php
//  package commands: dnf
//  Receives: $cmd, $args, $argv, $user  (from terminal.php scope)

switch ($cmd) {

    // dnf
    case 'dnf':
        if ($user !== 'root') {
            err('Error: This command has to be run with superuser privileges (under the root user on most systems).');
            break;
        }
        $sub = isset($argv[0]) ? strtolower($argv[0]) : '';

        // dnf (no subcommand)
        if ($sub === '') {
            out("usage: dnf [options] COMMAND\n\n"
              . "List of commands:\n"
              . "  install   Install one or more packages\n"
              . "  remove    Remove one or more packages\n"
              . "  update    Update all installed packages\n"
              . "  upgrade   Alias for update\n"
              . "  list      List packages (use 'installed' or 'available')\n"
              . "  search    Search package names and summaries\n"
              . "  info      Show detailed information about a package\n"
              . "  history   Show transaction history\n"
              . "  check-update  Check for available package updates\n"
              . "  clean     Remove cached data (use 'all')\n\n"
              . "Optional arguments:\n"
              . "  -h, --help    Show this help message");
        }

        // dnf install <pkg ...>
        if ($sub === 'install') {
            if ($user !== 'root') {
                err('Error: This command has to be run with superuser privileges (under the root user on most systems).');
            }
            $pkgs = array_slice($argv, 1);
            if (empty($pkgs)) {
                err('Error: No packages specified for install.');
            }
            // Build per-package steps for the JS animation
            $steps = [];
            foreach ($pkgs as $pkg) {
                $size   = rand(40, 8200);
                $sizeKb = $size . ' k';
                if ($size >= 1000) {
                    $sizeFmt = round($size / 1024, 1) . ' M';
                } else {
                    $sizeFmt = $size . ' k';
                }
                $steps[] = [
                    'pkg'    => $pkg,
                    'ver'    => '1.' . rand(0,9) . '.' . rand(0,9) . '-1.el9',
                    'repo'   => 'appstream',
                    'size'   => $size,
                    'sizeFmt'=> $sizeFmt,
                ];
            }
            echo json_encode([
                'output'  => '',
                'dnf'     => true,
                'dnfcmd'  => 'install',
                'pkgs'    => $pkgs,
                'steps'   => $steps,
            ]);
            exit;
        }

        // dnf remove <pkg ...>
        if ($sub === 'remove') {
            if ($user !== 'root') {
                err('Error: This command has to be run with superuser privileges (under the root user on most systems).');
            }
            $pkgs = array_slice($argv, 1);
            if (empty($pkgs)) {
                err('Error: No packages specified for remove.');
            }
            echo json_encode([
                'output'  => '',
                'dnf'     => true,
                'dnfcmd'  => 'remove',
                'pkgs'    => $pkgs,
            ]);
            exit;
        }

        // dnf update / upgrade
        if ($sub === 'update' || $sub === 'upgrade') {
            if ($user !== 'root') {
                err('Error: This command has to be run with superuser privileges (under the root user on most systems).');
            }
            $upgradePkgs = [
                ['name'=>'bash',         'old'=>'5.1.8-6.el9',      'new'=>'5.1.8-9.el9',       'size'=>'7.1 M'],
                ['name'=>'curl',         'old'=>'7.76.1-23.el9',    'new'=>'7.76.1-26.el9',      'size'=>'294 k'],
                ['name'=>'glibc',        'old'=>'2.34-60.el9',      'new'=>'2.34-68.el9',        'size'=>'5.6 M'],
                ['name'=>'openssl-libs', 'old'=>'3.0.7-17.el9',     'new'=>'3.0.7-24.el9',       'size'=>'2.1 M'],
                ['name'=>'php',          'old'=>'8.0.27-1.el9',     'new'=>'8.0.30-1.el9',       'size'=>'3.8 M'],
                ['name'=>'systemd',      'old'=>'252-13.el9_1',     'new'=>'252-18.el9_3',       'size'=>'4.3 M'],
                ['name'=>'tzdata',       'old'=>'2023c-1.el9',      'new'=>'2024a-1.el9',        'size'=>'433 k'],
            ];
            echo json_encode([
                'output'  => '',
                'dnf'     => true,
                'dnfcmd'  => 'upgrade',
                'pkgs'    => $upgradePkgs,
            ]);
            exit;
        }

        // dnf list
        if ($sub === 'list') {
            $which = isset($argv[1]) ? strtolower($argv[1]) : '';

            if ($which === 'installed') {
                $installed = [
                    'AlmaLinux-release.noarch'          => '9.3-1.el9',
                    'bash.x86_64'                       => '5.1.8-6.el9',
                    'bash-completion.noarch'            => '1:2.11-4.el9',
                    'ca-certificates.noarch'            => '2023.2.60_v7.0.306-90.0.el9_2',
                    'coreutils.x86_64'                  => '8.32-34.el9',
                    'crontabs.noarch'                   => '1.11-27.20190603git.el9',
                    'curl.x86_64'                       => '7.76.1-23.el9',
                    'dnf.noarch'                        => '4.14.0-2.el9',
                    'firewalld.noarch'                  => '1.2.0-2.el9_1',
                    'glibc.x86_64'                      => '2.34-60.el9',
                    'grep.x86_64'                       => '3.6-5.el9',
                    'httpd.x86_64'                      => '2.4.53-11.el9_2.5',
                    'httpd-tools.x86_64'                => '2.4.53-11.el9_2.5',
                    'kernel.x86_64'                     => '5.14.0-362.8.1.el9_3',
                    'less.x86_64'                       => '590-2.el9',
                    'libselinux.x86_64'                 => '3.5-1.el9',
                    'logrotate.x86_64'                  => '3.18.0-8.el9',
                    'mariadb.x86_64'                    => '3:10.5.22-1.el9',
                    'mariadb-server.x86_64'             => '3:10.5.22-1.el9',
                    'nano.x86_64'                       => '5.6.1-5.el9',
                    'net-tools.x86_64'                  => '2.0-0.62.20160912git.el9',
                    'nginx.x86_64'                      => '1:1.22.1-3.el9',
                    'openssh.x86_64'                    => '8.7p1-34.el9',
                    'openssh-server.x86_64'             => '8.7p1-34.el9',
                    'openssl.x86_64'                    => '1:3.0.7-17.el9',
                    'openssl-libs.x86_64'               => '1:3.0.7-17.el9',
                    'php.x86_64'                        => '8.0.27-1.el9',
                    'php-cli.x86_64'                    => '8.0.27-1.el9',
                    'php-fpm.x86_64'                    => '8.0.27-1.el9',
                    'php-mysqlnd.x86_64'                => '8.0.27-1.el9',
                    'python3.x86_64'                    => '3.9.18-1.el9_2',
                    'rsync.x86_64'                      => '3.2.3-19.el9',
                    'sed.x86_64'                        => '4.8-9.el9',
                    'shadow-utils.x86_64'               => '2:4.9-6.el9',
                    'sudo.x86_64'                       => '1.9.5p2-9.el9',
                    'systemd.x86_64'                    => '252-13.el9_1',
                    'tar.x86_64'                        => '2:1.34-6.el9_0',
                    'tzdata.noarch'                     => '2023c-1.el9',
                    'util-linux.x86_64'                 => '2.37.4-15.el9',
                    'vim-enhanced.x86_64'               => '2:8.2.2637-20.el9',
                    'wget.x86_64'                       => '1.21.1-7.el9',
                    'xfsprogs.x86_64'                   => '5.19.0-4.el9',
                    'yum.noarch'                        => '4.14.0-2.el9',
                    'zlib.x86_64'                       => '1.2.11-40.el9',
                ];
                $lines = ["Installed Packages"];
                foreach ($installed as $name => $ver) {
                    $lines[] = str_pad($name, 44) . ' ' . str_pad($ver, 30) . ' @baseos';
                }
                out(implode("\n", $lines));
            }

            if ($which === 'available') {
                $available = [
                    'bind.x86_64'              => '32:9.16.23-14.el9',
                    'certbot.noarch'           => '2.6.0-1.el9',
                    'composer.noarch'          => '2.6.5-1.el9',
                    'docker-ce.x86_64'         => '24.0.7-1.el9',
                    'fail2ban.noarch'          => '1.0.2-1.el9',
                    'git.x86_64'               => '2.39.3-1.el9',
                    'golang.x86_64'            => '1.20.12-1.el9',
                    'htop.x86_64'              => '3.2.1-1.el9',
                    'jq.x86_64'                => '1.6-10.el9',
                    'mod_ssl.x86_64'           => '1:2.4.53-11.el9_2.5',
                    'nodejs.x86_64'            => '1:18.18.2-2.el9_3',
                    'nmap.x86_64'              => '7.92-1.el9',
                    'php-mbstring.x86_64'      => '8.0.27-1.el9',
                    'php-pdo.x86_64'           => '8.0.27-1.el9',
                    'php-xml.x86_64'           => '8.0.27-1.el9',
                    'redis.x86_64'             => '7.0.13-1.el9',
                    'strace.x86_64'            => '6.1-2.el9',
                    'tcpdump.x86_64'           => '14:4.99.0-7.el9',
                    'unzip.x86_64'             => '6.0-56.el9',
                    'vim-common.x86_64'        => '2:8.2.2637-20.el9',
                    'zip.x86_64'               => '3.0-35.el9',
                ];
                $lines = ["Available Packages"];
                foreach ($available as $name => $ver) {
                    $lines[] = str_pad($name, 38) . ' ' . str_pad($ver, 26) . ' appstream';
                }
                out(implode("\n", $lines));
            }

            // plain 'dnf list' — show installed + available combined
            $lines = ["Last metadata expiration check: 0:12:14 ago on " . date('D d M Y H:i:s') . " UTC."];
            $lines[] = "Installed Packages";
            $lines[] = str_pad("bash.x86_64", 38) . ' ' . str_pad("5.1.8-6.el9", 26) . ' @baseos';
            $lines[] = str_pad("httpd.x86_64", 38) . ' ' . str_pad("2.4.53-11.el9_2.5", 26) . ' @appstream';
            $lines[] = str_pad("php.x86_64", 38) . ' ' . str_pad("8.0.27-1.el9", 26) . ' @appstream';
            $lines[] = str_pad("mariadb-server.x86_64", 38) . ' ' . str_pad("3:10.5.22-1.el9", 26) . ' @appstream';
            $lines[] = str_pad("nginx.x86_64", 38) . ' ' . str_pad("1:1.22.1-3.el9", 26) . ' @appstream';
            $lines[] = "Available Packages";
            $lines[] = str_pad("git.x86_64", 38) . ' ' . str_pad("2.39.3-1.el9", 26) . ' appstream';
            $lines[] = str_pad("nodejs.x86_64", 38) . ' ' . str_pad("1:18.18.2-2.el9_3", 26) . ' appstream';
            $lines[] = str_pad("redis.x86_64", 38) . ' ' . str_pad("7.0.13-1.el9", 26) . ' appstream';
            out(implode("\n", $lines));
        }

        // dnf search <term>
        if ($sub === 'search') {
            $term = isset($argv[1]) ? $argv[1] : '';
            if ($term === '') {
                err('Error: No search argument provided.');
            }
            $term_l = strtolower($term);
            // Searchable package database
            $db = [
                'php'          => 'PHP scripting language for creating dynamic web pages',
                'php-cli'      => 'Command-line interface for PHP',
                'php-fpm'      => 'PHP FastCGI Process Manager',
                'php-mbstring' => 'A module for PHP applications which need multi-byte string handling',
                'php-mysqlnd'  => 'A module for PHP applications that use MySQL databases',
                'php-pdo'      => 'A database access abstraction module for PHP',
                'php-xml'      => 'A module for PHP applications which use XML',
                'php-json'     => 'JavaScript Object Notation extension for PHP',
                'nginx'        => 'A high performance web server and reverse proxy',
                'httpd'        => 'Apache HTTP Server',
                'httpd-tools'  => 'Tools for use with the Apache HTTP Server',
                'mod_ssl'      => 'SSL/TLS module for the Apache HTTP Server',
                'mariadb'      => 'A community developed branch of MySQL',
                'mariadb-server'=> 'The MariaDB server and related files',
                'redis'        => 'A persistent key-value database',
                'nodejs'       => 'JavaScript runtime built on Chrome V8 engine',
                'npm'          => 'Node Package Manager',
                'git'          => 'Fast Version Control System',
                'curl'         => 'A utility for getting files from remote servers',
                'wget'         => 'A file retrieval utility which can use HTTP or FTP',
                'vim-enhanced' => 'A version of the VIM editor which includes recent enhancements',
                'nano'         => 'A small text editor',
                'python3'      => 'Interpreter of the Python 3 programming language',
                'python3-pip'  => 'A tool for installing and managing Python 3 packages',
                'docker-ce'    => 'Docker Engine - Community Edition',
                'certbot'      => 'A free, automated certificate authority client',
                'fail2ban'     => 'Daemon to ban hosts that cause multiple authentication errors',
                'rsync'        => 'A program for synchronizing files over a network',
                'nmap'         => 'Network exploration tool and security scanner',
                'htop'         => 'Interactive process viewer',
                'jq'           => 'Command-line JSON processor',
                'unzip'        => 'A utility for unpacking zip files',
                'zip'          => 'A file compression and packaging utility',
                'golang'       => 'The Go Programming Language',
                'strace'       => 'Tracks and displays system calls associated with a running process',
                'tcpdump'      => 'A network traffic monitoring tool',
                'bind'         => 'The Berkeley Internet Name Domain (BIND) DNS server',
            ];
            $matches = [];
            foreach ($db as $name => $desc) {
                if (strpos(strtolower($name), $term_l) !== false
                    || strpos(strtolower($desc), $term_l) !== false) {
                    $matches[$name] = $desc;
                }
            }
            if (empty($matches)) {
                out('No matches found.');
            }
            $lines = ["Last metadata expiration check: 0:12:14 ago on " . date('D d M Y H:i:s') . " UTC.",
                      "========================= Name & Summary Matched: {$term} ========================="];
            foreach ($matches as $name => $desc) {
                $lines[] = str_pad($name . '.x86_64', 30) . ' : ' . $desc;
            }
            out(implode("\n", $lines));
        }

        // dnf info <pkg>
        if ($sub === 'info') {
            $pkg = isset($argv[1]) ? $argv[1] : '';
            if ($pkg === '') {
                err('Error: No package specified.');
            }
            // Strip arch suffix if present
            $pkgBase = preg_replace('/\.(x86_64|noarch|i686)$/', '', $pkg);
            $infoMap = [
                'bash'          => ['ver'=>'5.1.8',  'rel'=>'6.el9',  'arch'=>'x86_64', 'size'=>'7.6 M',  'repo'=>'@baseos',   'summary'=>'The GNU Bourne Again shell'],
                'curl'          => ['ver'=>'7.76.1', 'rel'=>'23.el9', 'arch'=>'x86_64', 'size'=>'294 k',  'repo'=>'@baseos',   'summary'=>'A utility for getting files from remote servers'],
                'httpd'         => ['ver'=>'2.4.53', 'rel'=>'11.el9_2.5', 'arch'=>'x86_64', 'size'=>'4.5 M', 'repo'=>'@appstream', 'summary'=>'Apache HTTP Server'],
                'nginx'         => ['ver'=>'1.22.1', 'rel'=>'3.el9',  'arch'=>'x86_64', 'size'=>'1.7 M',  'repo'=>'@appstream', 'summary'=>'A high performance web server and reverse proxy'],
                'php'           => ['ver'=>'8.0.27', 'rel'=>'1.el9',  'arch'=>'x86_64', 'size'=>'3.8 M',  'repo'=>'@appstream', 'summary'=>'PHP scripting language for creating dynamic web pages'],
                'mariadb'       => ['ver'=>'10.5.22','rel'=>'1.el9',  'arch'=>'x86_64', 'size'=>'6.1 M',  'repo'=>'@appstream', 'summary'=>'A community developed branch of MySQL'],
                'mariadb-server'=> ['ver'=>'10.5.22','rel'=>'1.el9',  'arch'=>'x86_64', 'size'=>'27.1 M', 'repo'=>'@appstream', 'summary'=>'The MariaDB server and related files'],
                'git'           => ['ver'=>'2.39.3', 'rel'=>'1.el9',  'arch'=>'x86_64', 'size'=>'15.1 M', 'repo'=>'appstream', 'summary'=>'Fast Version Control System'],
                'nano'          => ['ver'=>'5.6.1',  'rel'=>'5.el9',  'arch'=>'x86_64', 'size'=>'2.8 M',  'repo'=>'@baseos',   'summary'=>'A small text editor'],
                'vim-enhanced'  => ['ver'=>'8.2.2637','rel'=>'20.el9','arch'=>'x86_64', 'size'=>'3.7 M',  'repo'=>'@appstream', 'summary'=>'A version of the VIM editor which includes recent enhancements'],
                'python3'       => ['ver'=>'3.9.18', 'rel'=>'1.el9_2','arch'=>'x86_64', 'size'=>'31 k',   'repo'=>'@baseos',   'summary'=>'Python 3 programming language'],
                'wget'          => ['ver'=>'1.21.1', 'rel'=>'7.el9',  'arch'=>'x86_64', 'size'=>'785 k',  'repo'=>'@appstream', 'summary'=>'A file retrieval utility which can use HTTP or FTP'],
                'redis'         => ['ver'=>'7.0.13', 'rel'=>'1.el9',  'arch'=>'x86_64', 'size'=>'1.4 M',  'repo'=>'appstream', 'summary'=>'A persistent key-value database'],
                'nodejs'        => ['ver'=>'18.18.2','rel'=>'2.el9_3','arch'=>'x86_64', 'size'=>'12.4 M', 'repo'=>'appstream', 'summary'=>'JavaScript runtime built on Chrome V8 engine'],
            ];
            if (isset($infoMap[$pkgBase])) {
                $i = $infoMap[$pkgBase];
                out(
                    "Name         : {$pkgBase}\n"
                  . "Version      : {$i['ver']}\n"
                  . "Release      : {$i['rel']}\n"
                  . "Architecture : {$i['arch']}\n"
                  . "Size         : {$i['size']}\n"
                  . "Source       : {$pkgBase}-{$i['ver']}-{$i['rel']}.src.rpm\n"
                  . "Repository   : {$i['repo']}\n"
                  . "Summary      : {$i['summary']}\n"
                  . "License      : GPLv2+\n"
                  . "Description  : {$i['summary']}."
                );
            }
            // Unknown package — make something up based on the name
            $fakeVer = rand(1,3) . '.' . rand(0,9) . '.' . rand(0,9) . '-1.el9';
            out(
                "Name         : {$pkgBase}\n"
              . "Version      : {$fakeVer}\n"
              . "Release      : 1.el9\n"
              . "Architecture : x86_64\n"
              . "Size         : " . rand(50,5000) . " k\n"
              . "Repository   : appstream\n"
              . "Summary      : {$pkgBase} utility package\n"
              . "License      : GPLv2+\n"
              . "Description  : The {$pkgBase} package provides the {$pkgBase} utility."
            );
        }

        // dnf history
        if ($sub === 'history') {
            out("ID     | Command line                    | Date and time       | Action(s) | Altered\n"
              . "-------+--------------------------------+---------------------+-----------+--------\n"
              . "     9 | upgrade                        | " . date('Y-m-d H:i') . " | I, U      |      7\n"
              . "     8 | install fail2ban               | 2026-03-13 09:41    | Install   |      3\n"
              . "     7 | install php-fpm php-mysqlnd    | 2026-03-12 14:22    | Install   |      6\n"
              . "     6 | upgrade                        | 2026-03-11 03:00    | I, U      |     12\n"
              . "     5 | install mariadb-server         | 2026-03-10 11:18    | Install   |      8\n"
              . "     4 | install httpd mod_ssl          | 2026-03-10 10:55    | Install   |      4\n"
              . "     3 | install nginx                  | 2026-03-10 10:30    | Install   |      2\n"
              . "     2 | upgrade                        | 2026-03-09 03:00    | I, U      |     21\n"
              . "     1 | install (initial setup)        | 2026-03-08 08:00    | Install   |    412");
        }

        // dnf check-update
        if ($sub === 'check-update') {
            out("Last metadata expiration check: 0:12:14 ago on " . date('D d M Y H:i:s') . " UTC.\n\n"
              . str_pad("bash.x86_64", 30)          . " 5.1.8-9.el9       baseos\n"
              . str_pad("curl.x86_64", 30)          . " 7.76.1-26.el9     baseos\n"
              . str_pad("glibc.x86_64", 30)         . " 2.34-68.el9       baseos\n"
              . str_pad("openssl-libs.x86_64", 30)  . " 3.0.7-24.el9      baseos\n"
              . str_pad("php.x86_64", 30)           . " 8.0.30-1.el9      appstream\n"
              . str_pad("systemd.x86_64", 30)       . " 252-18.el9_3      baseos\n"
              . str_pad("tzdata.noarch", 30)        . " 2024a-1.el9       baseos");
        }

        // dnf clean
        if ($sub === 'clean') {
            if ($user !== 'root') {
                err('Error: This command has to be run with superuser privileges (under the root user on most systems).');
            }
            out("12 files removed");
        }

        // Unknown subcommand
        err("dnf: No such command: {$sub}. Please use /usr/bin/dnf --help");
}
