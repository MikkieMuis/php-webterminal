<?php
//  network commands: ifconfig, ip, ping, wget, curl
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
}
