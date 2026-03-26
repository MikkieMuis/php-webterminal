<?php
//  network commands: ifconfig, ip, ping, wget, curl, telnet, sendmail,
//                    netstat, ss, ssh, dig, host
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // ifconfig
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

    // ip
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

    // ping
    case 'ping':
        if ($args === '') err('ping: usage error: Destination address required');
        $pingHost  = '';
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
        $knownHosts = [
            'localhost'      => '127.0.0.1',
            '127.0.0.1'      => '127.0.0.1',
            'google.com'     => '142.250.185.46',
            'www.google.com' => '142.250.185.46',
            'github.com'     => '140.82.121.4',
            'cloudflare.com' => '104.16.132.229',
            '1.1.1.1'        => '1.1.1.1',
            '8.8.8.8'        => '8.8.8.8',
            'amazon.com'     => '205.251.242.103',
            'microsoft.com'  => '20.76.201.171',
        ];
        $ip  = isset($knownHosts[$pingHost]) ? $knownHosts[$pingHost]
             : implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)]);
        $ttl = ($ip === '127.0.0.1') ? 64 : 55;
        $packets = [];
        for ($pi = 1; $pi <= $pingCount; $pi++) {
            $ms = ($ip === '127.0.0.1') ? round(0.04 + lcg_value()*0.05, 3)
                                        : round(8 + lcg_value()*20, 3);
            $packets[] = sprintf('64 bytes from %s (%s): icmp_seq=%d ttl=%d time=%.3f ms', $pingHost, $ip, $pi, $ttl, $ms);
        }
        $times   = array_map(function($l){ preg_match('/time=([\d.]+)/',$l,$m); return (float)$m[1]; }, $packets);
        $summary = sprintf(
            "--- %s ping statistics ---\n%d packets transmitted, %d received, 0%% packet loss, time %dms\nrtt min/avg/max/mdev = %.3f/%.3f/%.3f/%.3f ms",
            $pingHost, $pingCount, $pingCount,
            (int)(array_sum($times)*1.1),
            min($times), array_sum($times)/count($times), max($times),
            (max($times)-min($times))/2
        );
        echo json_encode([
            'output'  => '',
            'ping'    => true,
            'header'  => 'PING ' . $pingHost . ' (' . $ip . ') 56(84) bytes of data.',
            'packets' => $packets,
            'summary' => $summary,
        ]);
        exit;

    // wget
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
            'output' => '',
            'wget'   => true,
            'url'    => $wUrl,
            'host'   => $wHost,
            'file'   => $wFile,
            'size'   => $wSize,
        ]);
        exit;

    // curl
    case 'curl':
        if ($args === '') err('curl: try \'curl --help\' for more information');
        $cUrl    = '';
        $cOut    = '';
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
            'output' => '',
            'curl'   => true,
            'url'    => $cUrl,
            'host'   => $cHost,
            'file'   => $cOut,
            'size'   => $cSize,
            'silent' => $cSilent,
        ]);
        exit;

    // telnet
    case 'telnet': {
        if ($args === '') {
            out("telnet> \ntelnet: no host specified. Usage: telnet HOST [PORT]");
        }
        $telHost = '';
        $telPort = 23;
        foreach ($argv as $av) {
            if ($av[0] !== '-' && $telHost === '') { $telHost = $av; }
            elseif (is_numeric($av))               { $telPort = (int)$av; }
        }
        if ($telHost === '') err('telnet: no host specified. Usage: telnet HOST [PORT]');

        // Resolve display IP
        $knownHosts = [
            'localhost'  => '127.0.0.1',
            '127.0.0.1'  => '127.0.0.1',
        ];
        $ip = isset($knownHosts[$telHost]) ? $knownHosts[$telHost]
            : implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)]);

        echo json_encode([
            'output'  => '',
            'telnet'  => true,
            'host'    => $telHost,
            'ip'      => $ip,
            'port'    => $telPort,
        ]);
        exit;
    }

    // netstat
    case 'netstat': {
        $showAll  = (strpos($args, 'a') !== false);
        $showNum  = (strpos($args, 'n') !== false);
        $showPid  = (strpos($args, 'p') !== false);
        $showTcp  = (strpos($args, 't') !== false) || $args === '' || $args === '-a';
        $showUdp  = (strpos($args, 'u') !== false) || $args === '' || $args === '-a';
        $showListen = (strpos($args, 'l') !== false) || $showAll;

        if (strpos($args, 'r') !== false) {
            // routing table
            out("Kernel IP routing table\n"
              . "Destination     Gateway         Genmask         Flags   MSS Window  irtt Iface\n"
              . "0.0.0.0         192.168.1.1     0.0.0.0         UG        0 0          0 eth0\n"
              . "192.168.1.0     0.0.0.0         255.255.255.0   U         0 0          0 eth0\n"
              . "127.0.0.0       0.0.0.0         255.0.0.0       U         0 0          0 lo");
        }

        $lines = ["Active Internet connections" . ($showAll ? " (servers and established)" : " (w/o servers)")];
        $lines[] = "Proto Recv-Q Send-Q Local Address           Foreign Address         State" . ($showPid ? "       PID/Program" : "");

        // TCP established
        $established = [
            ['tcp', '0', '0', '0.0.0.0:22',    '192.168.1.42:54321', 'ESTABLISHED', '1234/sshd'],
            ['tcp', '0', '0', '127.0.0.1:3306', '127.0.0.1:51820',   'ESTABLISHED', '2001/mysqld'],
            ['tcp', '0', '0', '0.0.0.0:80',     '93.184.216.34:51200','ESTABLISHED', '1100/httpd'],
        ];
        // TCP listeners
        $listeners = [
            ['tcp', '0', '0', '0.0.0.0:22',    '0.0.0.0:*', 'LISTEN', '1234/sshd'],
            ['tcp', '0', '0', '0.0.0.0:80',    '0.0.0.0:*', 'LISTEN', '1100/httpd'],
            ['tcp', '0', '0', '0.0.0.0:443',   '0.0.0.0:*', 'LISTEN', '1100/httpd'],
            ['tcp', '0', '0', '127.0.0.1:3306','0.0.0.0:*', 'LISTEN', '2001/mysqld'],
            ['tcp', '0', '0', '127.0.0.1:9000','0.0.0.0:*', 'LISTEN', '2222/php-fpm'],
        ];
        // UDP
        $udp = [
            ['udp', '0', '0', '0.0.0.0:68',   '0.0.0.0:*', '', '888/dhclient'],
            ['udp', '0', '0', '127.0.0.1:323', '0.0.0.0:*', '', '777/chronyd'],
        ];

        $rows = [];
        if ($showListen) {
            foreach ($listeners as $r) $rows[] = $r;
        }
        if (!$showListen || $showAll) {
            foreach ($established as $r) $rows[] = $r;
        }
        if ($showUdp) {
            foreach ($udp as $r) $rows[] = $r;
        }

        foreach ($rows as $r) {
            $line = sprintf("%-5s %-6s %-6s %-23s %-23s %-11s", $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]);
            if ($showPid) $line .= ' ' . $r[6];
            $lines[] = $line;
        }
        out(implode("\n", $lines));
    }

    // ss
    case 'ss': {
        $showAll    = (strpos($args, 'a') !== false);
        $showListen = (strpos($args, 'l') !== false) || $showAll;
        $showNum    = (strpos($args, 'n') !== false);
        $showProc   = (strpos($args, 'p') !== false);
        $showTcp    = (strpos($args, 't') !== false) || $args === '' || $args === '-a'
                   || (strpos($args, 'l') !== false && strpos($args, 'u') === false);
        $showUdp    = (strpos($args, 'u') !== false);

        if (strpos($args, 'r') !== false) {
            // route summary like ss -r
            out("Routing table output not supported. Use: ip route");
        }

        $lines = ["Netid  State      Recv-Q Send-Q Local Address:Port               Peer Address:Port" . ($showProc ? "  Process" : "")];

        $sockets = [
            // state,      recv,send, local,                          peer
            ['tcp', 'LISTEN',     '0', '128', '0.0.0.0:22',         '0.0.0.0:*',         '1234/sshd'],
            ['tcp', 'LISTEN',     '0', '128', '0.0.0.0:80',         '0.0.0.0:*',         '1100/httpd'],
            ['tcp', 'LISTEN',     '0', '128', '0.0.0.0:443',        '0.0.0.0:*',         '1100/httpd'],
            ['tcp', 'LISTEN',     '0', '80',  '127.0.0.1:3306',     '0.0.0.0:*',         '2001/mysqld'],
            ['tcp', 'LISTEN',     '0', '511', '127.0.0.1:9000',     '0.0.0.0:*',         '2222/php-fpm'],
            ['tcp', 'ESTABLISHED','0', '0',   '192.168.1.10:22',    '192.168.1.42:54321', '1234/sshd'],
            ['udp', 'UNCONN',     '0', '0',   '0.0.0.0:68',         '0.0.0.0:*',         '888/dhclient'],
            ['udp', 'UNCONN',     '0', '0',   '127.0.0.1:323',      '0.0.0.0:*',         '777/chronyd'],
        ];

        $listenOnly = (strpos($args, 'l') !== false) && !$showAll;
        foreach ($sockets as $s) {
            list($proto, $state, $rq, $sq, $local, $peer, $proc) = $s;
            if (!$showTcp && $proto === 'tcp') continue;
            if (!$showUdp && $proto === 'udp' && $showTcp) continue;
            if (!$showListen && $state === 'LISTEN') continue;
            if ($listenOnly && $state !== 'LISTEN') continue;
            $line = sprintf("%-6s %-11s %-6s %-6s %-31s %-31s", $proto, $state, $rq, $sq, $local, $peer);
            if ($showProc) $line .= ' users:(("' . explode('/', $proc)[1] . '",pid=' . explode('/', $proc)[0] . ',fd=3))';
            $lines[] = $line;
        }
        out(implode("\n", $lines));
    }

    // ssh
    case 'ssh': {
        if ($args === '') {
            out("usage: ssh [-46AaCfGgKkMNnqsTtVvXxYy] [-b bind_interface]\n"
              . "           [-c cipher_spec] [-D [bind_address:]port]\n"
              . "           [-E log_file] [-e escape_char]\n"
              . "           [-F configfile] [-I pkcs11] [-i identity_file]\n"
              . "           [-J [user@]host[:port]] [-L address]\n"
              . "           [-l login_name] [-m mac_spec] [-O ctl_cmd]\n"
              . "           [-o option] [-p port] [-Q query_option]\n"
              . "           [-R address] [-S ctl_path] [-W host:port]\n"
              . "           [-w local_tun[:remote_tun]] destination [command]");
        }

        // parse flags
        $sshUser = $user;
        $sshPort = 22;
        $sshHost = '';
        $sshVerbose = false;
        for ($si = 0; $si < count($argv); $si++) {
            $a = $argv[$si];
            if ($a === '-l' && isset($argv[$si+1])) { $sshUser = $argv[++$si]; }
            elseif ($a === '-p' && isset($argv[$si+1])) { $sshPort = (int)$argv[++$si]; }
            elseif ($a === '-v' || $a === '-vv' || $a === '-vvv') { $sshVerbose = true; }
            elseif ($a[0] !== '-' && $sshHost === '') {
                // user@host syntax
                if (strpos($a, '@') !== false) {
                    list($sshUser, $sshHost) = explode('@', $a, 2);
                } else {
                    $sshHost = $a;
                }
            }
        }

        if ($sshHost === '') {
            err('ssh: no host specified');
        }

        // Resolve IP
        $knownHosts = [
            'localhost'   => '127.0.0.1',
            '127.0.0.1'   => '127.0.0.1',
            'github.com'  => '140.82.121.4',
            'google.com'  => '142.250.185.46',
        ];
        $ip = isset($knownHosts[$sshHost]) ? $knownHosts[$sshHost]
            : implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)]);

        $portStr = ($sshPort !== 22) ? ' port ' . $sshPort : '';

        $verbose = '';
        if ($sshVerbose) {
            $verbose = "OpenSSH_8.7p1, OpenSSL 3.0.7 1 Nov 2022\n"
                     . "debug1: Reading configuration data /etc/ssh/ssh_config\n"
                     . "debug1: Connecting to $sshHost [$ip]$portStr\n"
                     . "debug1: Connection established.\n"
                     . "debug1: Host '$sshHost' is known and matches the ECDSA host key.\n"
                     . "debug1: Authenticating to $sshHost:$sshPort as '$sshUser'\n"
                     . "debug1: Authentications that can continue: publickey,password\n"
                     . "debug1: Trying private key: /root/.ssh/id_rsa\n"
                     . "debug1: Authentication succeeded (publickey).\n";
        }

        // Simulate connection refused for non-localhost hosts (realistic)
        if ($ip !== '127.0.0.1') {
            $banner = $verbose . "ssh: connect to host $sshHost port $sshPort: Connection refused";
            err($banner);
        }

        out($verbose . "Last login: " . date('D M j H:i:s Y') . " from 192.168.1.42\n"
          . "[" . $sshUser . "@" . CONF_HOSTNAME . " ~]$ ");
    }

    // dig
    case 'dig': {
        if ($args === '') {
            out("; <<>> DiG 9.16.23-RH <<>>\n"
              . ";; global options: +cmd\n"
              . ";; Got answer:\n"
              . ";; QUESTION SECTION:\n"
              . ";.                              IN      NS\n\n"
              . "Usage: dig [@server] name [type]");
        }

        // Parse: dig [@server] hostname [type]
        $digHost  = '';
        $digType  = 'A';
        $digServer= '';
        foreach ($argv as $a) {
            if ($a[0] === '@') { $digServer = substr($a, 1); }
            elseif (in_array(strtoupper($a), ['A','AAAA','MX','NS','TXT','CNAME','SOA','PTR','ANY'])) {
                $digType = strtoupper($a);
            } elseif ($a[0] !== '-' && $digHost === '') {
                $digHost = $a;
            }
        }

        if ($digHost === '') err('dig: no host specified');

        // Static DNS records
        $dns = [
            'google.com'     => ['A'=>['142.250.185.46'], 'MX'=>['10 smtp.google.com.'], 'NS'=>['ns1.google.com.','ns2.google.com.']],
            'github.com'     => ['A'=>['140.82.121.4'],   'NS'=>['ns-1707.awsdns-21.co.uk.']],
            'cloudflare.com' => ['A'=>['104.16.132.229'], 'MX'=>['10 mail.cloudflare.com.']],
            'localhost'      => ['A'=>['127.0.0.1']],
        ];

        $records = isset($dns[$digHost][$digType]) ? $dns[$digHost][$digType]
                 : [implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)])];

        $qtime = rand(10, 120);
        $server = $digServer ?: '8.8.8.8';
        $ts = date('D M j H:i:s Y T');

        $answer = '';
        foreach ($records as $rec) {
            $answer .= sprintf("%-23s 300     IN      %-6s %s\n", $digHost.'.', $digType, $rec);
        }

        out("; <<>> DiG 9.16.23-RH <<>> $digHost" . ($digType !== 'A' ? " $digType" : "") . "\n"
          . ";; global options: +cmd\n"
          . ";; Got answer:\n"
          . ";; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: " . rand(1000,9999) . "\n"
          . ";; flags: qr rd ra; QUERY: 1, ANSWER: " . count($records) . ", AUTHORITY: 0, ADDITIONAL: 0\n\n"
          . ";; QUESTION SECTION:\n"
          . sprintf(";%-22s IN      %s\n\n", $digHost.'.', $digType)
          . ";; ANSWER SECTION:\n"
          . $answer . "\n"
          . ";; Query time: $qtime msec\n"
          . ";; SERVER: $server#53($server)\n"
          . ";; WHEN: $ts\n"
          . ";; MSG SIZE  rcvd: " . (rand(4,10) * 12));
    }

    // host
    case 'host': {
        if ($args === '') {
            out("Usage: host [-aCdilrTvVw] [-c class] [-N ndots] [-t type] [-W time]\n"
              . "            [-R number] [-m flag] [-4] [-6] hostname [server]");
        }

        $hostTarget = '';
        $hostType   = 'A';
        foreach ($argv as $a) {
            if ($a === '-t' ) continue;
            if (in_array(strtoupper($a), ['A','AAAA','MX','NS','TXT','CNAME','SOA'])) {
                $hostType = strtoupper($a);
            } elseif ($a[0] !== '-' && $hostTarget === '') {
                $hostTarget = $a;
            }
        }

        if ($hostTarget === '') err('host: no host specified');

        $dns = [
            'google.com'     => ['A'=>['142.250.185.46'], 'MX'=>['google.com mail is handled by 10 smtp.google.com.']],
            'github.com'     => ['A'=>['140.82.121.4']],
            'cloudflare.com' => ['A'=>['104.16.132.229']],
            'localhost'      => ['A'=>['127.0.0.1']],
        ];

        // Reverse PTR lookup
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $hostTarget)) {
            $rev = implode('.', array_reverse(explode('.', $hostTarget))) . '.in-addr.arpa';
            $ptr = 'server-' . str_replace('.', '-', $hostTarget) . '.example.com';
            out("$rev domain name pointer $ptr.");
        }

        if ($hostType === 'MX' && isset($dns[$hostTarget]['MX'])) {
            foreach ($dns[$hostTarget]['MX'] as $rec) out($hostTarget . ' ' . $rec);
        }

        $ip = isset($dns[$hostTarget]['A'][0]) ? $dns[$hostTarget]['A'][0]
            : implode('.', [rand(1,254),rand(1,254),rand(1,254),rand(1,254)]);
        out("$hostTarget has address $ip");
    }

    // sendmail
    case 'sendmail': {
        // Usage: sendmail [-v] recipient
        //        sendmail -t  (read from stdin — we just accept and discard)
        if ($args === '' || $args === '-t') {
            out("sendmail: ready to accept input (use Ctrl+D to send)\n(non-interactive mode: message discarded)");
        }
        $recipient = '';
        $verbose   = false;
        foreach ($argv as $av) {
            if ($av === '-v')          { $verbose = true; }
            elseif ($av[0] !== '-')    { $recipient = $av; }
        }
        if ($recipient === '') {
            err('sendmail: no recipient specified');
        }
        $ts = date('D, d M Y H:i:s O');
        if ($verbose) {
            out("sendmail: Connecting to localhost (127.0.0.1) port 25...\n"
              . "220 " . CONF_HOSTNAME . " ESMTP Postfix\n"
              . "EHLO " . CONF_HOSTNAME . "\n"
              . "250-" . CONF_HOSTNAME . " Hello\n"
              . "250 OK\n"
              . "MAIL FROM:<root@" . CONF_HOSTNAME . ">\n"
              . "250 OK\n"
              . "RCPT TO:<" . $recipient . ">\n"
              . "250 Accepted\n"
              . "DATA\n"
              . "354 Enter message, ending with \".\" on a line by itself\n"
              . ".\n"
              . "250 OK: message queued as " . strtoupper(bin2hex(random_bytes(5))) . "\n"
              . "QUIT\n"
              . "221 Bye");
        }
        out("Message queued for delivery to " . $recipient . "\nDate: " . $ts);
        break;
    }

    // scp
    case 'scp': {
        // Usage: scp [opts] [[user@]host:]src [[user@]host:]dest
        if ($args === '') err("usage: scp [-346BCpqrTv] [-c cipher] [-F ssh_config] [-i identity_file]\n           [-J destination] [-l limit] [-o ssh_option] [-P port]\n           [-S program] source ... target");

        // Parse source and destination (last two non-flag args)
        $scpFiles = [];
        $scpPort  = 22;
        for ($si = 0; $si < count($argv); $si++) {
            if (($argv[$si] === '-P') && isset($argv[$si+1])) {
                $scpPort = (int)$argv[++$si];
            } elseif ($argv[$si][0] !== '-') {
                $scpFiles[] = $argv[$si];
            }
        }
        if (count($scpFiles) < 2) err('scp: specify source and destination');

        $scpSrc  = $scpFiles[0];
        $scpDest = $scpFiles[count($scpFiles) - 1];

        // Determine filename for progress display
        $scpFile = basename(str_replace(':', '/', $scpSrc));
        if ($scpFile === '') $scpFile = 'file';

        $scpSize = rand(512, 8192);

        // Return as a download-style animation (like wget)
        echo json_encode([
            'output' => '',
            'scp'    => true,
            'src'    => $scpSrc,
            'dest'   => $scpDest,
            'file'   => $scpFile,
            'size'   => $scpSize,
            'port'   => $scpPort,
        ]);
        exit;
    }

    // nmcli
    case 'nmcli': {
        // Usage: nmcli [device status | connection show | general status | ...]
        $sub  = isset($argv[0]) ? strtolower($argv[0]) : '';
        $sub2 = isset($argv[1]) ? strtolower($argv[1]) : '';

        if ($sub === '' || ($sub === 'general' && ($sub2 === '' || $sub2 === 'status'))) {
            out("STATE      CONNECTIVITY  WIFI-HW  WIFI     WWAN-HW  WWAN\n"
              . "connected  full          enabled  enabled  enabled  enabled");
        }

        if ($sub === 'device' && ($sub2 === '' || $sub2 === 'status')) {
            out("DEVICE  TYPE      STATE      CONNECTION\n"
              . "eth0    ethernet  connected  Wired connection 1\n"
              . "lo      loopback  unmanaged  --");
        }

        if ($sub === 'device' && $sub2 === 'show') {
            $dev = isset($argv[2]) ? $argv[2] : 'eth0';
            out("GENERAL.DEVICE:                         " . $dev . "\n"
              . "GENERAL.TYPE:                           ethernet\n"
              . "GENERAL.HWADDR:                         00:15:5D:00:00:01\n"
              . "GENERAL.MTU:                            1500\n"
              . "GENERAL.STATE:                          100 (connected)\n"
              . "GENERAL.CONNECTION:                     Wired connection 1\n"
              . "GENERAL.CON-PATH:                       /org/freedesktop/NetworkManager/ActiveConnection/1\n"
              . "WIRED-PROPERTIES.CARRIER:               on\n"
              . "IP4.ADDRESS[1]:                         192.168.1.10/24\n"
              . "IP4.GATEWAY:                            192.168.1.1\n"
              . "IP4.ROUTE[1]:                           dst = 0.0.0.0/0, nh = 192.168.1.1, mt = 100\n"
              . "IP4.DNS[1]:                             8.8.8.8\n"
              . "IP4.DNS[2]:                             8.8.4.4\n"
              . "IP6.ADDRESS[1]:                         fe80::215:5dff:fe00:1/64\n"
              . "IP6.GATEWAY:                            --");
        }

        if ($sub === 'connection' && ($sub2 === '' || $sub2 === 'show')) {
            out("NAME                UUID                                  TYPE      DEVICE\n"
              . "Wired connection 1  12345678-aaaa-bbbb-cccc-ddddeeeeffff  ethernet  eth0");
        }

        if ($sub === 'connection' && $sub2 === 'show' && isset($argv[2])) {
            $conn = $argv[2];
            out("connection.id:                          " . $conn . "\n"
              . "connection.uuid:                        12345678-aaaa-bbbb-cccc-ddddeeeeffff\n"
              . "connection.type:                        802-3-ethernet\n"
              . "connection.interface-name:              eth0\n"
              . "connection.autoconnect:                 yes\n"
              . "ipv4.method:                            auto\n"
              . "ipv4.addresses:                         --\n"
              . "ipv4.gateway:                           --\n"
              . "ipv4.dns:                               --\n"
              . "GENERAL.STATE:                          activated\n"
              . "GENERAL.DEFAULT:                        yes\n"
              . "IP4.ADDRESS[1]:                         192.168.1.10/24\n"
              . "IP4.GATEWAY:                            192.168.1.1");
        }

        if ($sub === 'radio') {
            out("WIFI-HW  WIFI     WWAN-HW  WWAN\n"
              . "enabled  enabled  enabled  enabled");
        }

        if ($sub === '--version' || $sub === '-v') {
            out("nmcli tool, version 1.42.2-1");
        }

        // Default / help
        if ($sub === '--help' || $sub === '-h' || $sub === 'help') {
            out("Usage: nmcli [OPTIONS] OBJECT { COMMAND | help }\n\n"
              . "OPTIONS:\n"
              . "  -v[ersion]                         show program version\n"
              . "  -h[elp]                            print this help\n\n"
              . "OBJECT:\n"
              . "  g[eneral]       NetworkManager's general status and operations\n"
              . "  n[etworking]    overall networking control\n"
              . "  r[adio]         NetworkManager radio switches\n"
              . "  c[onnection]    NetworkManager's connections\n"
              . "  d[evice]        devices managed by NetworkManager\n"
              . "  a[gent]         NetworkManager secret agent or polkit agent\n"
              . "  m[onitor]       monitor NetworkManager changes");
        }

        // If sub command matched above, out() already exited via out(). If we get here
        // the subcommand was not recognised — show a short error.
        err("Error: Object '" . $sub . "' is unknown, try 'nmcli help'.");
    }
}