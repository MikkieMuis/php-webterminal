<?php
//  shell commands: echo, clear, exit, logout, history, help,
//                  alias, last, sudo, man, passwd, base64, bc
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
        $base = [
            '    1  apt-get update',
            '    2  apt-get upgrade -y',
            '    3  df -h',
            '    4  free -h',
            '    5  ps aux',
        ];
        $log    = $_SESSION['cmdlog'];
        $offset = count($base) + 1;
        foreach ($log as $i => $c) {
            $base[] = sprintf('%5d  %s', $offset + $i, $c);
        }
        out(implode("\n", $base));

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
          . "  diff [-u] F1 F2   compare two files\n"
          . "  du [-sh] [PATH]   disk usage of directory\n"
          . "  chmod MODE FILE   change file permissions (cosmetic)\n"
          . "  chown USER FILE   change file owner (cosmetic)\n"
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
          . "  systemctl         manage system services\n"
          . "  php [-v|-i|-m|-r] PHP CLI\n"
          . "\n"
          . "NETWORK\n"
          . "  ifconfig          show network interfaces\n"
          . "  ip addr           show IP addresses\n"
          . "  ping <host>       send ICMP echo request\n"
          . "  wget <url>        download a file\n"
          . "  curl <url>        transfer data from/to URL\n"
          . "\n"
          . "SHELL & MISC\n"
          . "  echo <text>       print text to screen\n"
          . "  clear             clear the terminal\n"
          . "  history           show command history\n"
          . "  alias             list command aliases\n"
          . "  last              show recent login history\n"
          . "  sudo <cmd>        run command as superuser\n"
          . "  passwd            change user password\n"
          . "  base64 [-d]       encode/decode base64\n"
          . "  bc                basic calculator\n"
          . "  nano <file>       text editor\n"
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
        // fake password change — accept silently, do nothing
        $target = ($args !== '') ? $args : $user;
        out("Changing password for " . $target . ".\nNew password: \nRetype new password: \npasswd: all authentication tokens updated successfully.");

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
}
