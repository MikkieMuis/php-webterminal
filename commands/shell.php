<?php
//  shell commands: echo, clear, exit, logout, history, help,
//                  alias, last, sudo, man, passwd, base64, bc,
//                  pushd, popd, dirs
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // echo
    case 'echo':
        out($args);

    // clear
    case 'clear':
        echo json_encode(['output'=>'', 'clear'=>true]);
        exit;

    // exit / logout
    case 'exit':
    case 'logout':
        echo json_encode(['output'=>"logout\n\nConnection to " . CONF_HOSTNAME . " closed.", 'logout'=>true]);
        exit;

    // history
    case 'history':
        $log = array_reverse($_SESSION['cmdlog']);
        $lines = [];
        foreach ($log as $i => $c) {
            $lines[] = sprintf('%5d  %s', $i + 1, $c);
        }
        out(implode("\n", $lines));

    // help
    case 'help':
        out("Available commands — type 'man <command>' for details.\n"
          . "\n"
          . "FILESYSTEM\n"
          . "  ls [-la]          list directory contents\n"
          . "  cd <dir>          change working directory\n"
          . "  pwd               print working directory\n"
          . "  mkdir <dir>       create directory\n"
          . "  rmdir <dir>       remove empty directory\n"
          . "  touch <file>      create/update file timestamp\n"
          . "  rm [-rf] <path>   remove files or directories\n"
          . "  cp [-r] SRC DEST  copy files or directories\n"
          . "  mv SRC DEST       move/rename files\n"
          . "  cat <file>        print file contents\n"
          . "  head [-n N] <f>   print first N lines\n"
          . "  tail [-n N] <f>   print last N lines\n"
          . "  more/less <file>  page through file\n"
          . "  wc [-lwc] <file>  count lines/words/bytes\n"
          . "  grep [OPTS] PAT F search file for pattern\n"
          . "  sort [-rnuf] [-k N] F sort lines of a file\n"
          . "  uniq [-cdu] [-i] F filter adjacent duplicate lines\n"
          . "  cut -f/-c RANGE F  cut columns or characters from lines\n"
          . "  tr SET1 SET2       translate or delete characters\n"
          . "  find [PATH] [OPTS] search for files in directory tree\n"
          . "  awk 'PROG' [FILE]  pattern-action text processor\n"
          . "  sed 's/PAT/REP/' F stream editor for text\n"
          . "  diff [-u] F1 F2   compare two files\n"
          . "  du [-shd N] [PATH]  disk usage of directory\n"
          . "  ln -s TARGET LINK create symbolic link\n"
          . "  chmod MODE FILE   change file permissions (cosmetic)\n"
          . "  chown USER FILE   change file owner (cosmetic)\n"
          . "  chgrp GROUP FILE  change file group (cosmetic)\n"
          . "  zip [-r] ARC SRC  create ZIP archive\n"
          . "  unzip [-l] ARC    extract ZIP archive\n"
          . "  tar -czf ARC SRC  create/extract tar archive\n"
          . "\n"
          . "SYSTEM\n"
          . "  whoami            print current user\n"
          . "  id                print user and group IDs\n"
          . "  hostname          print system hostname\n"
          . "  uname [-a]        print kernel/system info\n"
          . "  uptime            show system uptime and load\n"
          . "  date              print current date and time\n"
          . "  df [-h]           report disk space usage\n"
          . "  free [-h]         display memory usage\n"
          . "  ps [aux]          list running processes\n"
          . "  top               dynamic process viewer\n"
          . "  htop              interactive process viewer\n"
          . "  env / printenv    print environment variables\n"
          . "  which <cmd>       locate a command\n"
          . "  fastfetch         system info with ASCII logo\n"
          . "  neofetch          system info with ASCII logo (alias)\n"
          . "  systemctl         manage system services\n"
          . "  exa               modern ls replacement (--long/--tree/--git)\n"
          . "  firewall-cmd      manage firewalld rules\n"
          . "  lsblk             list block devices\n"
          . "  blkid             print block device UUIDs and types\n"
          . "  dmesg [-T] [-n N] print kernel ring buffer\n"
          . "  vmstat            report virtual memory statistics\n"
          . "  iostat            report CPU and disk I/O statistics\n"
          . "  hostnamectl       show/set system hostname info\n"
          . "  timedatectl       show/set system time and NTP status\n"
          . "  journalctl [-u SVC] [-n N]  query the journal log\n"
          . "  lsof [-i PORT] [-p PID]     list open files and sockets\n"
          . "  php [-v|-i|-m|-r] PHP CLI\n"
          . "\n"
          . "NETWORK\n"
          . "  ifconfig          show network interfaces\n"
          . "  ip addr           show IP addresses\n"
          . "  ping <host>       send ICMP echo request\n"
          . "  wget <url>        download a file\n"
          . "  curl <url>        transfer data from/to URL\n"
          . "  telnet HOST [PORT] open TCP connection (simulated)\n"
          . "  sendmail [-v] TO  send mail message\n"
          . "  netstat [-anp]    show network connections\n"
          . "  ss [-tlnp]        socket statistics\n"
          . "  ssh [USER@]HOST   connect to remote host (simulated)\n"
          . "  scp SRC DEST      secure copy between hosts (simulated)\n"
          . "  dig HOST [TYPE]   DNS lookup\n"
          . "  host HOST         DNS lookup (short form)\n"
          . "  nmcli             NetworkManager command-line client\n"
          . "\n"
          . "SHELL & MISC\n"
          . "  echo <text>       print text to screen\n"
          . "  clear             clear the terminal\n"
          . "  history           show command history\n"
          . "  alias             list command aliases\n"
          . "  last              show recent login history\n"
          . "  sudo <cmd>        run command as superuser\n"
          . "  su [USER]         switch user (default: root)\n"
          . "  passwd            change user password\n"
          . "  base64 [-d]       encode/decode base64\n"
          . "  bc                basic calculator\n"
          . "  logger MSG        write message to system log\n"
          . "  pushd <dir>       push directory onto stack and cd\n"
          . "  popd              pop directory from stack and cd\n"
          . "  dirs [-v]         display directory stack\n"
          . "  xargs [CMD]       build and execute commands from stdin\n"
          . "  strace [-p PID]   trace system calls\n"
          . "  nano <file>       text editor\n"
           . "  joe <file>        Joe's Own Editor (^K commands)\n"
           . "  mysql [-u USER]   MySQL/MariaDB interactive client\n"
           . "  mariadb [-u USER] alias for mysql\n"
           . "  man <cmd>         show manual page\n"
          . "  dnf               package manager\n"
          . "  exit / logout     close the session\n"
          . "\n"
          . "Type 'man <command>' for detailed usage information.");

    // alias
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
        out('');

    // last
    case 'last':
        $prev  = date('D M j H:i', time() - 86400);
        $prev2 = date('D M j H:i', time() - 172800);
        out("root     pts/0        192.168.1.42     " . date('D M j H:i') . "   still logged in\n"
          . "root     pts/0        192.168.1.42     " . $prev  . "  - " . date('H:i', time()-82800)  . "  (23:00)\n"
          . "root     pts/1        10.0.0.5         " . $prev2 . "  - " . date('H:i', time()-169200) . "  (01:12)\n"
          . "deploy   pts/2        10.0.0.12        " . $prev2 . "  - " . date('H:i', time()-168000) . "  (00:48)\n"
          . "\nwtmp begins " . date('D M j', strtotime('-30 days')) . " 00:00");

    // sudo
    case 'sudo':
        if ($user === 'root') {
            if (preg_match('/rm\s.*-rf\s.*\/|rm\s.*\/\s.*-rf/', $args) || $args === 'rm -rf /') {
                echo json_encode(['output'=>'', 'rmrf'=>true]);
                exit;
            }
            out('sudo: you are already root.');
        }
        echo json_encode(['output'=>'', 'sudo_prompt'=>true, 'sudo_cmd'=>$args]);
        exit;

    // su
    case 'su':
        // determine target user: `su`, `su -`, `su root` → root; `su - username` / `su username` → that user
        $target = 'root';
        if ($args !== '' && $args !== '-') {
            $parts = preg_split('/\s+/', ltrim($args, '-'));
            $target = trim($parts[count($parts)-1]);
        }
        if ($user === $target) {
            out('');  // already that user — no-op like real su
            exit;
        }
        if ($user === 'root') {
            // root can su to anyone without a password
            echo json_encode(['output'=>'', 'su_prompt'=>false, 'su_target'=>$target]);
            exit;
        }
        // non-root: need password prompt
        echo json_encode(['output'=>'', 'su_prompt'=>true, 'su_target'=>$target]);
        exit;

    // man
    case 'man':
        if ($args === '') {
            out("What manual page do you want?\nFor example, try 'man ls'.");
        }
        $topic = strtolower(preg_split('/\s+/', $args)[0]);
        require __DIR__ . '/man_pages.php';
        if (isset($pages[$topic])) {
            out($pages[$topic]);
        }
        err('No manual entry for ' . $topic);

    // passwd
    case 'passwd':
        $target = ($args !== '') ? $args : $user;
        // Non-root cannot change another user's password
        if ($user !== 'root' && $target !== $user) {
            err('passwd: You may not view or modify password information for ' . $target . '.');
        }
        echo json_encode(['output'=>'', 'passwd_prompt'=>true, 'passwd_target'=>$target]);
        exit;

    // base64
    case 'base64': {
        // Usage: base64 [-d] [STRING]
        // base64 <<< string  → but we receive it as args
        $decode = (strpos($args, '-d') !== false || strpos($args, '--decode') !== false);
        // strip flags to get the payload
        $payload = trim(preg_replace('/\s*-{1,2}d(ecode)?\s*/', ' ', $args));
        // strip heredoc-style <<< prefix if present
        $payload = trim(ltrim($payload, '<'));
        if ($payload === '') {
            err('base64: missing input — try: base64 <<< "string"  or  base64 -d <<< "encoded"');
        }
        if ($decode) {
            $decoded = base64_decode($payload, true);
            if ($decoded === false) err('base64: invalid input');
            out($decoded);
        } else {
            out(base64_encode($payload));
        }
    }

    // bc
    case 'bc': {
        // Usage: bc [-l] [EXPRESSION]  — basic arithmetic only
        if ($args === '' || $args === '-l') {
            out("bc " . ($args === '-l' ? '(mathlib) ' : '') . "1.07.1\nCopyright 1991-1994, 1997, 1998, 2000, 2004, 2006, 2008, 2012-2017 Free Software Foundation, Inc.\nThis is free software with ABSOLUTELY NO WARRANTY.\nFor details type `warranty'.\n(interactive mode not supported — pass expression as argument: bc <<< \"1+1\")");
        }
        // strip flags and heredoc marker
        $expr = trim(preg_replace('/\s*-l\s*/', ' ', $args));
        $expr = trim(ltrim($expr, '<'));
        // allow only safe arithmetic chars
        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.\^%]+$/', $expr)) {
            err('bc: invalid expression — only arithmetic is supported');
        }
        // evaluate
        $result = null;
        try {
            // use eval carefully — only digits and operators allowed (validated above)
            // replace ^ with ** for PHP
            $phpExpr = str_replace('^', '**', $expr);
            $result  = @eval('return (' . $phpExpr . ');');
        } catch (\Throwable $e) {
            err('bc: runtime error');
        }
        if ($result === null || $result === false) err('bc: arithmetic error');
        // format: integer if no fractional part
        $formatted = (is_float($result) && floor($result) !== $result)
            ? rtrim(rtrim(number_format($result, 10, '.', ''), '0'), '.')
            : (string)(int)$result;
        out($formatted);
    }

    // pushd
    case 'pushd': {
        if (!isset($_SESSION['dirstack'])) $_SESSION['dirstack'] = [];
        if ($args === '') {
            // With no args, swap top of stack with cwd (like bash)
            if (empty($_SESSION['dirstack'])) err('pushd: no other directory');
            $top = array_pop($_SESSION['dirstack']);
            array_push($_SESSION['dirstack'], $_SESSION['cwd']);
            $_SESSION['cwd'] = $top;
            $stack = array_merge([$_SESSION['cwd']], array_reverse($_SESSION['dirstack']));
            out(implode(' ', $stack));
        }
        $fs   = $_SESSION['fs'];
        $dest = res_path($args);
        if (!isset($fs[$dest]) || $fs[$dest]['type'] !== 'dir') {
            err('pushd: ' . $args . ': No such file or directory');
        }
        array_push($_SESSION['dirstack'], $_SESSION['cwd']);
        $_SESSION['cwd'] = $dest;
        $stack = array_merge([$dest], array_reverse($_SESSION['dirstack']));
        out(implode(' ', $stack));
    }

    // popd
    case 'popd': {
        if (!isset($_SESSION['dirstack'])) $_SESSION['dirstack'] = [];
        if (empty($_SESSION['dirstack'])) err('popd: directory stack empty');
        $_SESSION['cwd'] = array_pop($_SESSION['dirstack']);
        $stack = array_merge([$_SESSION['cwd']], array_reverse($_SESSION['dirstack']));
        out(implode(' ', $stack));
    }

    // dirs
    case 'dirs': {
        if (!isset($_SESSION['dirstack'])) $_SESSION['dirstack'] = [];
        $stack = array_merge([$_SESSION['cwd']], array_reverse($_SESSION['dirstack']));
        if (strpos($args, '-v') !== false) {
            $lines = [];
            foreach ($stack as $i => $d) $lines[] = " $i  $d";
            out(implode("\n", $lines));
        } else {
            out(implode(' ', $stack));
        }
    }

    // xargs
    case 'xargs': {
        // Usage: xargs [CMD [ARGS...]]
        // In a pipe context $body contains the stdin fed via pipe.
        // Without pipe, xargs with no stdin acts as if reading an empty list.
        // xargs CMD  — appends each whitespace-separated token from stdin as args to CMD.
        // xargs      — default command is echo.

        $xcmd  = isset($argv[0]) ? $argv[0] : 'echo';
        // Extra static args before the appended tokens (everything after xcmd)
        $xstatic = count($argv) > 1 ? array_slice($argv, 1) : [];

        // stdin comes from $body (pipe) or is empty
        $stdin = isset($body) ? trim($body) : '';
        if ($stdin === '') {
            // no stdin → nothing to do, run cmd with no extra args
            $tokens = [];
        } else {
            $tokens = preg_split('/\s+/', $stdin, -1, PREG_SPLIT_NO_EMPTY);
        }

        // Build the effective command and re-dispatch it
        $allArgs = array_merge($xstatic, $tokens);
        $full    = $xcmd . (count($allArgs) ? ' ' . implode(' ', $allArgs) : '');

        // Re-invoke terminal with the built command
        $cmd  = strtolower($xcmd);
        $argv = $allArgs;
        $args = implode(' ', $allArgs);

        // Dispatch to the appropriate command file
        $dispatchMap = [
            'ls'=>'filesystem','cd'=>'filesystem','mkdir'=>'filesystem','rmdir'=>'filesystem',
            'touch'=>'filesystem','rm'=>'filesystem','cat'=>'filesystem','wc'=>'filesystem',
            'more'=>'filesystem','less'=>'filesystem','cp'=>'filesystem','mv'=>'filesystem',
            'du'=>'filesystem','chmod'=>'filesystem','chown'=>'filesystem','ln'=>'filesystem',
            'grep'=>'search','head'=>'search','tail'=>'search','diff'=>'search','find'=>'search',
            'sort'=>'text','uniq'=>'text','cut'=>'text','tr'=>'text',
            'awk'=>'awk','sed'=>'sed',
            'zip'=>'archive','unzip'=>'archive','tar'=>'archive',
            'whoami'=>'sysinfo','pwd'=>'sysinfo','hostname'=>'sysinfo','uname'=>'sysinfo',
            'uptime'=>'sysinfo','date'=>'sysinfo','df'=>'sysinfo','free'=>'sysinfo',
            'ps'=>'sysinfo','top'=>'sysinfo','htop'=>'sysinfo','id'=>'sysinfo',
            'env'=>'sysinfo','printenv'=>'sysinfo','which'=>'sysinfo','exa'=>'sysinfo',
            'fastfetch'=>'sysinfo','neofetch'=>'sysinfo',
            'systemctl'=>'services','firewall-cmd'=>'services','journalctl'=>'services',
            'php'=>'hardware','kill'=>'hardware','pkill'=>'hardware','lsblk'=>'hardware',
            'blkid'=>'hardware','dmesg'=>'hardware','vmstat'=>'hardware','iostat'=>'hardware',
            'hostnamectl'=>'hardware','timedatectl'=>'hardware','chgrp'=>'hardware',
            'logger'=>'hardware','lsof'=>'hardware','strace'=>'hardware',
            'ifconfig'=>'network','ip'=>'network','ping'=>'network','wget'=>'network',
            'curl'=>'network','telnet'=>'network','sendmail'=>'network',
            'netstat'=>'network','ss'=>'network','ssh'=>'network','dig'=>'network',
            'host'=>'network','scp'=>'network','nmcli'=>'network',
            'echo'=>'shell','history'=>'shell','alias'=>'shell','last'=>'shell',
            'sudo'=>'shell','su'=>'shell','man'=>'shell','passwd'=>'shell',
            'base64'=>'shell','bc'=>'shell','pushd'=>'shell','popd'=>'shell','dirs'=>'shell',
        ];
        if (isset($dispatchMap[$cmd])) {
            require __DIR__ . '/' . $dispatchMap[$cmd] . '.php';
        } else {
            // fallback: just echo the constructed command line
            out($full);
        }
        exit;
    }
}
