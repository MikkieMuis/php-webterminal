<?php
//  shell commands: echo, clear, exit, logout, history, help,
//                  alias, last, sudo, man
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
        $pages = [
            'cp'      => "CP(1)                     User Commands                    CP(1)\n\nNAME\n       cp - copy files and directories\n\nSYNOPSIS\n       cp [OPTION]... SOURCE DEST\n       cp [OPTION]... SOURCE... DIRECTORY\n\nDESCRIPTION\n       Copy SOURCE to DEST, or multiple SOURCE(s) to DIRECTORY.\n\n       -r, -R, --recursive\n              copy directories recursively\n\n       -f, --force\n              if an existing destination file cannot be opened, remove it\n              and try again\n\n       -v, --verbose\n              explain what is being done\n\n       -p, --preserve\n              preserve the specified attributes (default: mode, ownership,\n              timestamps)\n\n       -u, --update\n              copy only when the SOURCE file is newer than the destination\n              file or when the destination file is missing\n\nAUTHOR\n       Written by Torbjorn Granlund, David MacKenzie, and Jim Meyering.\n\nGNU coreutils                    March 2026                          CP(1)",

            'mv'      => "MV(1)                     User Commands                    MV(1)\n\nNAME\n       mv - move (rename) files\n\nSYNOPSIS\n       mv [OPTION]... SOURCE DEST\n       mv [OPTION]... SOURCE... DIRECTORY\n\nDESCRIPTION\n       Rename SOURCE to DEST, or move SOURCE(s) to DIRECTORY.\n\n       -f, --force\n              do not prompt before overwriting\n\n       -i, --interactive\n              prompt before overwrite\n\n       -n, --no-clobber\n              do not overwrite an existing file\n\n       -v, --verbose\n              explain what is being done\n\n       -u, --update\n              move only when the SOURCE file is newer than the destination\n              file or when the destination file is missing\n\nAUTHOR\n       Written by Mike Parker, David MacKenzie, and Jim Meyering.\n\nGNU coreutils                    March 2026                          MV(1)",

            'ls'      => "LS(1)                     User Commands                    LS(1)\n\nNAME\n       ls - list directory contents\n\nSYNOPSIS\n       ls [OPTION]... [FILE]...\n\nDESCRIPTION\n       List  information  about  the FILEs (the current directory by default).\n       Sort entries alphabetically if none of -cftuvSUX nor --sort  is  speci-\n       fied.\n\n       -a, --all\n              do not ignore entries starting with .\n\n       -l     use a long listing format\n\n       -h, --human-readable\n              with -l and -s, print sizes like 1K 234M 2G etc.\n\n       -r, --reverse\n              reverse order while sorting\n\n       -t     sort by time, newest first\n\nAUTHOR\n       Written by Richard M. Stallman and David MacKenzie.\n\nGNU coreutils                    March 2026                          LS(1)",

            'cat'     => "CAT(1)                    User Commands                   CAT(1)\n\nNAME\n       cat - concatenate files and print on the standard output\n\nSYNOPSIS\n       cat [OPTION]... [FILE]...\n\nDESCRIPTION\n       Concatenate FILE(s) to standard output.\n\n       -n, --number\n              number all output lines\n\n       -v, --show-nonprinting\n              use ^ and M- notation, except for LFD and TAB\n\nAUTHOR\n       Written by Torbjorn Granlund and Richard M. Stallman.\n\nGNU coreutils                    March 2026                         CAT(1)",

            'cd'      => "CD(1)                     bash built-in                    CD(1)\n\nNAME\n       cd - change the shell working directory\n\nSYNOPSIS\n       cd [-L|[-P [-e]] [-@]] [dir]\n\nDESCRIPTION\n       Change  the  current  directory  to dir.  The default DIR is the value\n       of the HOME shell variable.\n\n       -L     force symbolic links to be followed\n       -P     use the physical directory structure\n\nGNU bash                         March 2026                          CD(1)",

            'pwd'     => "PWD(1)                    User Commands                   PWD(1)\n\nNAME\n       pwd - print name of current/working directory\n\nSYNOPSIS\n       pwd [OPTION]...\n\nDESCRIPTION\n       Print the full filename of the current working directory.\n\n       -L, --logical\n              use PWD from environment, even if it contains symlinks\n\n       -P, --physical\n              avoid all symlinks\n\nAUTHOR\n       Written by Jim Meyering.\n\nGNU coreutils                    March 2026                         PWD(1)",

            'mkdir'   => "MKDIR(1)                  User Commands                 MKDIR(1)\n\nNAME\n       mkdir - make directories\n\nSYNOPSIS\n       mkdir [OPTION]... DIRECTORY...\n\nDESCRIPTION\n       Create the DIRECTORY(ies), if they do not already exist.\n\n       -m, --mode=MODE\n              set file mode (as in chmod), not a=rwx - umask\n\n       -p, --parents\n              no error if existing, make parent directories as needed\n\n       -v, --verbose\n              print a message for each created directory\n\nAUTHOR\n       Written by David MacKenzie.\n\nGNU coreutils                    March 2026                       MKDIR(1)",

            'rm'      => "RM(1)                     User Commands                    RM(1)\n\nNAME\n       rm - remove files or directories\n\nSYNOPSIS\n       rm [OPTION]... [FILE]...\n\nDESCRIPTION\n       This manual page documents the GNU version of rm.  rm removes each\n       specified file.  By default, it does not remove directories.\n\n       -f, --force\n              ignore nonexistent files and arguments, never prompt\n\n       -r, -R, --recursive\n              remove directories and their contents recursively\n\n       -v, --verbose\n              explain what is being done\n\nWARNING\n       If you use rm to remove a file, it might be possible to  recover  some\n       of its contents.\n\nAUTHOR\n       Written by Paul Rubin, David MacKenzie, Richard Stallman, Jim Meyering.\n\nGNU coreutils                    March 2026                          RM(1)",

            'touch'   => "TOUCH(1)                  User Commands                 TOUCH(1)\n\nNAME\n       touch - change file timestamps\n\nSYNOPSIS\n       touch [OPTION]... FILE...\n\nDESCRIPTION\n       Update the access and modification times of each FILE to the current\n       time.  A FILE argument that does not exist is created empty.\n\n       -a     change only the access time\n\n       -m     change only the modification time\n\n       -t STAMP\n              use [[CC]YY]MMDDhhmm[.ss] instead of current time\n\nAUTHOR\n       Written by Paul Rubin, Arnold Robbins, Jim Kingdon, David MacKenzie,\n       and Randy Smith.\n\nGNU coreutils                    March 2026                       TOUCH(1)",

            'grep'    => "GREP(1)                   User Commands                  GREP(1)\n\nNAME\n       grep, egrep, fgrep - print lines that match patterns\n\nSYNOPSIS\n       grep [OPTION...] PATTERNS [FILE...]\n\nDESCRIPTION\n       grep  searches  for  PATTERNS  in  each FILE.\n\n       -i, --ignore-case\n              ignore case distinctions in patterns and data\n\n       -r, --recursive\n              read all files under each directory, recursively\n\n       -n, --line-number\n              print line number with output lines\n\n       -v, --invert-match\n              select non-matching lines\n\n       -c, --count\n              print only a count of selected lines per FILE\n\nAUTHOR\n       Written by Mike Haertel and others.\n\nGNU grep                         March 2026                        GREP(1)",

            'ssh'     => "SSH(1)                    User Commands                   SSH(1)\n\nNAME\n       ssh - OpenSSH remote login client\n\nSYNOPSIS\n       ssh [-46AaCfGgKkMNnqsTtVvXxYy] [-p port] [-i identity_file]\n           [-l login_name] destination [command]\n\nDESCRIPTION\n       ssh  (SSH client) is a program for logging into a remote machine and\n       for executing commands on a remote machine.\n\n       -p port\n              Port to connect to on the remote host.\n\n       -i identity_file\n              Selects a file from which the identity (private key) for\n              public key authentication is read.\n\n       -v     Verbose mode.\n\nOpenSSH                          March 2026                         SSH(1)",

            'ping'    => "PING(8)               System Manager's Manual              PING(8)\n\nNAME\n       ping - send ICMP ECHO_REQUEST to network hosts\n\nSYNOPSIS\n       ping [-c count] [-i interval] [-t ttl] {destination}\n\nDESCRIPTION\n       ping uses the ICMP protocol's mandatory ECHO_REQUEST datagram\n       to elicit an ICMP ECHO_RESPONSE from a host or gateway.\n\n       -c count\n              Stop after sending count ECHO_REQUEST packets.\n\n       -i interval\n              Wait interval seconds between sending each packet.\n\n       -t ttl\n              Set the IP Time to Live.\n\nIPutils                          March 2026                        PING(8)",

            'df'      => "DF(1)                     User Commands                    DF(1)\n\nNAME\n       df - report file system space usage\n\nSYNOPSIS\n       df [OPTION]... [FILE]...\n\nDESCRIPTION\n       df displays the amount of space available on the file system.\n\n       -h, --human-readable\n              print sizes in powers of 1024 (e.g., 1023M)\n\n       -T, --print-type\n              print file system type\n\n       -i, --inodes\n              list inode information instead of block usage\n\nAUTHOR\n       Written by Torbjorn Granlund, David MacKenzie, and Paul Eggert.\n\nGNU coreutils                    March 2026                          DF(1)",

            'free'    => "FREE(1)                   User Commands                  FREE(1)\n\nNAME\n       free - Display amount of free and used memory in the system\n\nSYNOPSIS\n       free [options]\n\nDESCRIPTION\n       free displays the total amount of free and used physical and swap\n       memory in the system.\n\n       -b, --bytes\n              Display the amount of memory in bytes.\n\n       -h, --human\n              Show all output fields automatically scaled to shortest\n              three digit unit.\n\nAUTHOR\n       Written by Brian Edmonds.\n\nprocps-ng                        March 2026                        FREE(1)",

            'ps'      => "PS(1)                     User Commands                    PS(1)\n\nNAME\n       ps - report a snapshot of the current processes\n\nSYNOPSIS\n       ps [options]\n\nDESCRIPTION\n       ps displays information about a selection of the active processes.\n\n       a      Lift the BSD-style \"only yourself\" restriction.\n       u      Display user-oriented format.\n       x      Lift the BSD-style \"must have a tty\" restriction.\n\n       -e     Select all processes.\n       -f     Full-format listing.\n\nAUTHOR\n       ps was originally written by Branko Lankester.\n\nprocps-ng                        March 2026                          PS(1)",

            'top'     => "TOP(1)                    User Commands                   TOP(1)\n\nNAME\n       top - display Linux processes\n\nSYNOPSIS\n       top -hv|-bcEeHiOSs1 -d secs -n max -u|U user -p pids -o field\n\nDESCRIPTION\n       The top program provides a dynamic real-time view of a running system.\n\nINTERACTIVE COMMANDS\n       q      Quit\n       k      Kill a task\n       r      Renice a task\n       SPACE  Refresh display\n\nAUTHOR\n       Written by Roger Binns.\n\nprocps-ng                        March 2026                         TOP(1)",

            'uname'   => "UNAME(1)                  User Commands                 UNAME(1)\n\nNAME\n       uname - print system information\n\nSYNOPSIS\n       uname [OPTION]...\n\nDESCRIPTION\n       Print certain system information.\n\n       -a, --all\n              print all information\n\n       -s, --kernel-name\n              print the kernel name\n\n       -n, --nodename\n              print the network node hostname\n\n       -r, --kernel-release\n              print the kernel release\n\n       -m, --machine\n              print the machine hardware name\n\nAUTHOR\n       Written by David MacKenzie.\n\nGNU coreutils                    March 2026                       UNAME(1)",

            'chmod'   => "CHMOD(1)                  User Commands                 CHMOD(1)\n\nNAME\n       chmod - change file mode bits\n\nSYNOPSIS\n       chmod [OPTION]... MODE[,MODE]... FILE...\n\nDESCRIPTION\n       chmod changes the file mode bits of each given file according to mode.\n\n       -R, --recursive\n              change files and directories recursively\n\n       -v, --verbose\n              output a diagnostic for every file processed\n\nAUTHOR\n       Written by David MacKenzie and Jim Meyering.\n\nGNU coreutils                    March 2026                       CHMOD(1)",

            'curl'    => "CURL(1)                   User Commands                  CURL(1)\n\nNAME\n       curl - transfer a URL\n\nSYNOPSIS\n       curl [options / URLs]\n\nDESCRIPTION\n       curl  is  a tool for transferring data from or to a server using URLs.\n\n       -o, --output <file>\n              Write output to <file> instead of stdout.\n\n       -O, --remote-name\n              Write output to a local file named like the remote file.\n\n       -s, --silent\n              Silent mode.\n\n       -L, --location\n              Follow redirects.\n\n       -v, --verbose\n              Makes curl verbose during the operation.\n\nAUTHOR\n       Written by Daniel Stenberg.\n\ncurl                             March 2026                        CURL(1)",

            'wget'    => "WGET(1)                   User Commands                  WGET(1)\n\nNAME\n       wget - The non-interactive network downloader.\n\nSYNOPSIS\n       wget [option]... [URL]...\n\nDESCRIPTION\n       GNU  Wget  is  a  free  utility  for  non-interactive download of\n       files from the Web.\n\n       -q, --quiet\n              Turn off Wget's output.\n\n       -O file, --output-document=file\n              Write documents to file.\n\n       -r, --recursive\n              Turn on recursive retrieving.\n\n       -c, --continue\n              Continue getting a partially-downloaded file.\n\nAUTHOR\n       Written by Hrvoje Niksic.\n\nGNU Wget                         March 2026                        WGET(1)",

            'sudo'    => "SUDO(8)               System Manager's Manual              SUDO(8)\n\nNAME\n       sudo, sudoedit - execute a command as another user\n\nSYNOPSIS\n       sudo [-u user] command [argument ...]\n\nDESCRIPTION\n       sudo allows a permitted user to execute a command as the superuser\n       or another user.\n\n       -u user, --user=user\n              Run the command as a user other than the default target\n              user (usually root).\n\n       -l, --list\n              List the allowed (and forbidden) commands for the invoking user.\n\nTODD C. MILLER                   March 2026                        SUDO(8)",

            'history' => "HISTORY(3)             bash built-in                 HISTORY(3)\n\nNAME\n       history - GNU History Library\n\nSYNOPSIS\n       history [-c] [-d offset] [n]\n       history -anrw [filename]\n\nDESCRIPTION\n       Without options, display the command history list with line numbers.\n\n       -c     Clear the history list by deleting all the entries.\n\n       -d offset\n              Delete the history entry at position offset.\n\n       -a     Append the 'new' history lines to the history file.\n\nGNU bash                         March 2026                     HISTORY(3)",

            'echo'    => "ECHO(1)                   User Commands                  ECHO(1)\n\nNAME\n       echo - display a line of text\n\nSYNOPSIS\n       echo [SHORT-OPTION]... [STRING]...\n\nDESCRIPTION\n       Echo the STRING(s) to standard output.\n\n       -n     do not output the trailing newline\n\n       -e     enable interpretation of backslash escapes\n\n       -E     disable interpretation of backslash escapes (default)\n\nAUTHOR\n       Written by Brian Fox and Chet Ramey.\n\nGNU coreutils                    March 2026                        ECHO(1)",

            'nano'    => "NANO(1)                   User Commands                  NANO(1)\n\nNAME\n       nano - Nano's ANOther editor, an enhanced free Pico clone\n\nSYNOPSIS\n       nano [OPTIONS] [[+LINE[,COL]] FILE]...\n\nDESCRIPTION\n       nano is a small and friendly editor. It copies the look and feel\n       of Pico, but is free software.\n\nKEYBINDINGS\n       ^G (F1)   Display this help text\n       ^X (F2)   Close the current file buffer / Exit from nano\n       ^O (F3)   Write the current file to disk\n       ^W (F6)   Search forward for a string\n       ^K (F9)   Cut the current line and store it in the cutbuffer\n       ^U (F10)  Paste the contents of the cutbuffer\n       ^C (F11)  Display the position of the cursor\n\n       Arrow keys move the cursor. Home/End go to start/end of line.\n       PageUp/PageDown scroll by one screenful.\n\nAUTHOR\n       Written by Chris Allegretta. Current maintainer: Benno Schulenberg.\n\nGNU nano                         March 2026                        NANO(1)",

            'wc'      => "WC(1)                     User Commands                    WC(1)\n\nNAME\n       wc - print newline, word, and byte counts for each file\n\nSYNOPSIS\n       wc [OPTION]... [FILE]...\n\nDESCRIPTION\n       Print newline, word, and byte counts for each FILE, and a total\n       line if more than one FILE is specified.  With no FILE, read\n       standard input.\n\n       -c, --bytes\n              print the byte counts\n\n       -m, --chars\n              print the character counts\n\n       -l, --lines\n              print the newline counts\n\n       -w, --words\n              print the word counts\n\n       -L, --max-line-length\n              print the maximum display width\n\nAUTHOR\n       Written by Paul Rubin and David MacKenzie.\n\nGNU coreutils                    March 2026                          WC(1)",

            'more'    => "MORE(1)                   User Commands                  MORE(1)\n\nNAME\n       more - file perusal filter for crt viewing\n\nSYNOPSIS\n       more [options] file...\n\nDESCRIPTION\n       more is a filter for paging through text one screenful at a time.\n\n       -d     Prompt with '[Press space to continue, q to quit.]'\n\n       -p     Do not scroll. Instead, clear the whole screen and then\n              display the text.\n\n       +num   Start at line number num.\n\nINTERACTIVE COMMANDS\n       SPACE        display next k lines of text (default current screen size)\n       ENTER        display next k lines of text (default 1)\n       q or Q       exit more\n       /pattern     search for pattern in remaining text\n       =            display current line number\n       h or ?       display this help text\n\nAUTHOR\n       Written by Eric Shienbrood, Geoff Peck, John Foderaro.\n\nutil-linux                       March 2026                        MORE(1)",

            'less'    => "LESS(1)                   User Commands                  LESS(1)\n\nNAME\n       less - opposite of more\n\nSYNOPSIS\n       less [-[+]aABcCdeEfFgGiIJKLmMnNqQrRsSuUVwWX~]\n            [-b space] [-h lines] [-j line] [-k keyfile]\n            [-o logfile] [-p pattern] [-P prompt] [-t tag]\n            [-T tagsfile] [-x tab,...] [-y lines] [-[z] lines]\n            [+[+]cmd] [--] [filename]...\n\nDESCRIPTION\n       less is a program similar to more, but which allows backward\n       movement in the file as well as forward movement.\n\nINTERACTIVE COMMANDS\n       SPACE or f    Scroll forward one window\n       b             Scroll backward one window\n       ENTER or e    Scroll forward one line\n       y             Scroll backward one line\n       g             Go to first line\n       G             Go to last line\n       /pattern      Search forward for pattern\n       ?pattern      Search backward for pattern\n       n             Repeat previous search\n       q or Q        Exit less\n       h             Display this help\n\n       less is more feature-rich than more: it supports both forward\n       and backward navigation, search highlighting, and does not need\n       to read the entire file before displaying.\n\nAUTHOR\n       Written by Mark Nudelman.\n\nless                             March 2026                        LESS(1)",

            'cron'    => "CRON(8)               System Manager's Manual              CRON(8)\n\nNAME\n       cron - daemon to execute scheduled commands\n\nSYNOPSIS\n       cron [-n] [-p] [-s] [-m <mailcommand>]\n\nDESCRIPTION\n       cron is started automatically from /etc/init.d on entering\n       multi-user runlevels.  It searches /var/spool/cron for crontab\n       files which are named after accounts in /etc/passwd; the found\n       crontabs are loaded into the memory.\n\n       cron also searches for /etc/anacrontab and any files in the\n       /etc/cron.d directory.  These differ from user crontabs in that\n       they contain a username field in the entry.\n\nCRONTAB FORMAT\n       A crontab entry has the form:\n\n       minute  hour  day  month  weekday  command\n\n       Ranges, lists, and steps are allowed:\n         */5 * * * *    every 5 minutes\n         0 2 * * *      daily at 02:00\n         0 9 * * 1      every Monday at 09:00\n\nFILES\n       /etc/crontab          system crontab\n       /etc/cron.d/          additional crontab fragments\n       /etc/cron.hourly/     scripts run every hour\n       /etc/cron.daily/      scripts run every day\n       /etc/cron.weekly/     scripts run every week\n       /etc/cron.monthly/    scripts run every month\n       /var/spool/cron/      per-user crontabs\n\nSEE ALSO\n       crontab(1), anacron(8)\n\nPaul Vixie                       March 2026                        CRON(8)",

            'crontab' => "CRONTAB(1)                User Commands               CRONTAB(1)\n\nNAME\n       crontab - maintain crontab files for individual users\n\nSYNOPSIS\n       crontab [-u user] file\n       crontab [-u user] [-l | -r | -e] [-i]\n\nDESCRIPTION\n       crontab is the program used to install, remove or list the tables\n       used to drive the cron daemon.\n\n       -l     List the current crontab.\n\n       -e     Edit the current crontab using the editor specified by the\n              VISUAL or EDITOR environment variable.\n\n       -r     Remove the current crontab.\n\n       -u user\n              Specify the name of the user whose crontab is to be\n              modified.  If this option is not given, it defaults to\n              the current user.\n\nFILES\n       /var/spool/cron/crontabs/    user crontab storage\n\nSEE ALSO\n       cron(8)\n\nPaul Vixie                       March 2026                     CRONTAB(1)",

            'httpd'   => "HTTPD(8)              System Manager's Manual             HTTPD(8)\n\nNAME\n       httpd - Apache Hypertext Transfer Protocol Server\n\nSYNOPSIS\n       httpd [-d serverroot] [-f config] [-C directive] [-c directive]\n             [-D parameter] [-e level] [-E file] [-k signal]\n             [-T] [-t] [-S] [-X] [-v] [-V] [-h] [-l] [-L] [-M] [-n name]\n\nDESCRIPTION\n       httpd is the Apache HyperText Transfer Protocol (HTTP) server\n       program.  It is designed to be run as a standalone daemon process.\n\n       -d serverroot\n              Set the initial value for the ServerRoot directive.\n\n       -f config\n              Use the directives in the file config on startup.\n              Default: /etc/httpd/conf/httpd.conf\n\n       -k signal\n              Send signal to the running httpd.  Signals: start, restart,\n              graceful, graceful-stop, stop.\n\n       -t     Run syntax tests for configuration files only.\n\n       -v     Print the version of httpd and exit.\n\n       -M     List all loaded modules and exit.\n\nFILES\n       /etc/httpd/conf/httpd.conf      main configuration file\n       /etc/httpd/conf.d/              additional configuration\n       /etc/httpd/conf.modules.d/      module load configuration\n       /var/log/httpd/                 log files\n       /var/www/html/                  default document root\n\nSEE ALSO\n       apachectl(8)\n\nApache HTTP Server               March 2026                       HTTPD(8)",

            'apachectl' => "APACHECTL(8)          System Manager's Manual          APACHECTL(8)\n\nNAME\n       apachectl - Apache HTTP Server Control Interface\n\nSYNOPSIS\n       apachectl command\n\nDESCRIPTION\n       apachectl is a front end to the Apache HyperText Transfer\n       Protocol (HTTP) server.  It is designed to help the administrator\n       control the functioning of the Apache httpd daemon.\n\nCOMMANDS\n       start       Start the Apache daemon.\n       stop        Stop the Apache daemon.\n       restart     Restart the Apache daemon gracefully (SIGHUP).\n       graceful    Graceful restart — open connections are not aborted.\n       fullstatus  Display a full status report from mod_status.\n       status      Display a brief status report.\n       configtest  Run a configuration file syntax test (equivalent to\n                   httpd -t).\n\nFILES\n       /etc/httpd/conf/httpd.conf\n\nSEE ALSO\n       httpd(8)\n\nApache HTTP Server               March 2026                   APACHECTL(8)",
        ];
        if (isset($pages[$topic])) {
            out($pages[$topic]);
        }
        err('No manual entry for ' . $topic);
}
