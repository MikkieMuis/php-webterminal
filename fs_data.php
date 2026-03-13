<?php
// ============================================================
//  php-webterminal — fake filesystem data
//  Returns an array of path => node entries.
//  node: ['type'=>'dir'] or ['type'=>'file','content'=>'...']
//  Optionally include 'mtime' (unix timestamp).
//
//  This file is included by terminal.php and merged into
//  $_SESSION['fs'] on first boot.
//
//  Keep entries grouped by directory for readability.
//  Split into multiple files if this grows too large.
// ============================================================

function fs_get_data() {
    $K = SYS_KERNEL;
    $H = CONF_HOSTNAME;

    return [

    // ──────────────────────────────────────────────────────────
    //  TOP-LEVEL DIRECTORIES
    // ──────────────────────────────────────────────────────────
    '/'          => ['type'=>'dir'],
    '/bin'       => ['type'=>'dir'],
    '/boot'      => ['type'=>'dir'],
    '/dev'       => ['type'=>'dir'],
    '/etc'       => ['type'=>'dir'],
    '/home'      => ['type'=>'dir'],
    '/lib'       => ['type'=>'dir'],
    '/lib64'     => ['type'=>'dir'],
    '/media'     => ['type'=>'dir'],
    '/mnt'       => ['type'=>'dir'],
    '/opt'       => ['type'=>'dir'],
    '/proc'      => ['type'=>'dir'],
    '/root'      => ['type'=>'dir'],
    '/run'       => ['type'=>'dir'],
    '/sbin'      => ['type'=>'dir'],
    '/srv'       => ['type'=>'dir'],
    '/sys'       => ['type'=>'dir'],
    '/tmp'       => ['type'=>'dir'],
    '/usr'       => ['type'=>'dir'],
    '/var'       => ['type'=>'dir'],

    // ──────────────────────────────────────────────────────────
    //  /boot
    // ──────────────────────────────────────────────────────────
    '/boot/grub'                        => ['type'=>'dir'],
    '/boot/grub/grub.cfg'               => ['type'=>'file','content'=>
"set default=0\nset timeout=5\n\nmenuentry 'AlmaLinux 9.7' {\n    linux /boot/vmlinuz-$K root=/dev/sda1 ro quiet\n    initrd /boot/initramfs-$K.img\n}"],
    '/boot/vmlinuz-' . $K               => ['type'=>'file','content'=>'[binary kernel image]'],
    '/boot/initramfs-' . $K . '.img'    => ['type'=>'file','content'=>'[binary initramfs image]'],
    '/boot/System.map-' . $K            => ['type'=>'file','content'=>'[kernel symbol table]'],
    '/boot/config-' . $K                => ['type'=>'file','content'=>"# Linux kernel config\nCONFIG_SMP=y\nCONFIG_X86_64=y\nCONFIG_MODULES=y\nCONFIG_NETFILTER=y"],

    // ──────────────────────────────────────────────────────────
    //  /etc  — full AlmaLinux 9 layout (matches real server)
    // ──────────────────────────────────────────────────────────

    // ── subdirectories ────────────────────────────────────────
    '/etc/alsa'             => ['type'=>'dir','mtime'=>mktime(0,0,0,2,14,2024)],
    '/etc/alternatives'     => ['type'=>'dir','mtime'=>mktime(18,38,0,1,28,2026)],
    '/etc/audit'            => ['type'=>'dir','mtime'=>mktime(13,48,0,9,21,2023)],
    '/etc/authselect'       => ['type'=>'dir','mtime'=>mktime(0,0,0,3,12,2025)],
    '/etc/bash_completion.d'=> ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/binfmt.d'         => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/bluetooth'        => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/cifs-utils'       => ['type'=>'dir','mtime'=>mktime(16,36,0,11,27,2024)],
    '/etc/cockpit'          => ['type'=>'dir','mtime'=>mktime(16,39,0,9,21,2023)],
    '/etc/cron.d'           => ['type'=>'dir','mtime'=>mktime(13,43,0,9,25,2023)],
    '/etc/cron.daily'       => ['type'=>'dir','mtime'=>mktime(0,0,0,4,11,2022)],
    '/etc/cron.hourly'      => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/cron.monthly'     => ['type'=>'dir','mtime'=>mktime(0,0,0,4,11,2022)],
    '/etc/cron.weekly'      => ['type'=>'dir','mtime'=>mktime(0,0,0,4,11,2022)],
    '/etc/crypto-policies'  => ['type'=>'dir','mtime'=>mktime(12,20,0,10,20,2024)],
    '/etc/dbus-1'           => ['type'=>'dir','mtime'=>mktime(0,0,0,2,14,2024)],
    '/etc/dconf'            => ['type'=>'dir','mtime'=>mktime(0,0,0,2,14,2024)],
    '/etc/debuginfod'       => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/default'          => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/depmod.d'         => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/dhcp'             => ['type'=>'dir','mtime'=>mktime(0,0,0,10,27,2023)],
    '/etc/dnf'              => ['type'=>'dir','mtime'=>mktime(16,43,0,11,29,2024)],
    '/etc/dracut.conf.d'    => ['type'=>'dir','mtime'=>mktime(18,48,0,12,17,2024)],
    '/etc/firewalld'        => ['type'=>'dir','mtime'=>mktime(12,48,0,11,4,2024)],
    '/etc/fonts'            => ['type'=>'dir','mtime'=>mktime(0,0,0,2,14,2024)],
    '/etc/gcrypt'           => ['type'=>'dir','mtime'=>mktime(0,0,0,10,1,2024)],
    '/etc/gnupg'            => ['type'=>'dir','mtime'=>mktime(22,34,0,1,15,2026)],
    '/etc/goaccess'         => ['type'=>'dir','mtime'=>mktime(0,0,0,5,28,2025)],
    '/etc/grub.d'           => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/httpd'            => ['type'=>'dir','mtime'=>mktime(4,33,0,2,13,2026)],
    '/etc/httpd/conf'       => ['type'=>'dir','mtime'=>mktime(4,33,0,2,13,2026)],
    '/etc/httpd/conf.d'     => ['type'=>'dir','mtime'=>mktime(4,33,0,2,13,2026)],
    '/etc/httpd/conf.modules.d' => ['type'=>'dir','mtime'=>mktime(4,33,0,2,13,2026)],
    '/etc/iproute2'         => ['type'=>'dir','mtime'=>mktime(16,43,0,11,29,2024)],
    '/etc/issue.d'          => ['type'=>'dir','mtime'=>mktime(11,15,0,11,11,2024)],
    '/etc/kdump'            => ['type'=>'dir','mtime'=>mktime(8,15,0,9,24,2024)],
    '/etc/kernel'           => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/krb5.conf.d'      => ['type'=>'dir','mtime'=>mktime(0,0,0,6,24,2025)],
    '/etc/ld.so.conf.d'     => ['type'=>'dir','mtime'=>mktime(12,3,0,2,17,2026)],
    '/etc/letsencrypt'      => ['type'=>'dir','mtime'=>mktime(8,32,0,3,13,2026)],
    '/etc/logrotate.d'      => ['type'=>'dir','mtime'=>mktime(12,40,0,2,17,2026)],
    '/etc/lvm'              => ['type'=>'dir','mtime'=>mktime(15,56,0,12,18,2024)],
    '/etc/lynis'            => ['type'=>'dir','mtime'=>mktime(16,41,0,10,23,2024)],
    '/etc/mc'               => ['type'=>'dir','mtime'=>mktime(0,0,0,10,27,2023)],
    '/etc/modprobe.d'       => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/modules-load.d'   => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/motd.d'           => ['type'=>'dir','mtime'=>mktime(0,0,0,9,3,2025)],
    '/etc/my.cnf.d'         => ['type'=>'dir','mtime'=>mktime(0,0,0,2,14,2024)],
    '/etc/NetworkManager'   => ['type'=>'dir','mtime'=>mktime(19,39,0,11,11,2024)],
    '/etc/nginx'            => ['type'=>'dir','mtime'=>mktime(11,24,0,10,20,2024)],
    '/etc/nginx/conf.d'     => ['type'=>'dir','mtime'=>mktime(11,24,0,10,20,2024)],
    '/etc/pam.d'            => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/php.d'            => ['type'=>'dir','mtime'=>mktime(6,30,0,2,11,2026)],
    '/etc/php-fpm.d'        => ['type'=>'dir','mtime'=>mktime(15,26,0,3,4,2026)],
    '/etc/pki'              => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/pki/tls'          => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/pki/tls/certs'    => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/pki/tls/private'  => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/profile.d'        => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/rc.d'             => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/rpm'              => ['type'=>'dir','mtime'=>mktime(9,58,0,10,1,2024)],
    '/etc/rsyslog.d'        => ['type'=>'dir','mtime'=>mktime(17,12,0,9,21,2023)],
    '/etc/samba'            => ['type'=>'dir','mtime'=>mktime(11,25,0,2,22,2026)],
    '/etc/security'         => ['type'=>'dir','mtime'=>mktime(0,0,0,9,6,2025)],
    '/etc/selinux'          => ['type'=>'dir','mtime'=>mktime(10,38,0,3,2,2026)],
    '/etc/skel'             => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/ssh'              => ['type'=>'dir','mtime'=>mktime(2,52,0,12,18,2024)],
    '/etc/ssl'              => ['type'=>'dir','mtime'=>mktime(16,43,0,11,29,2024)],
    '/etc/sudoers.d'        => ['type'=>'dir','mtime'=>mktime(14,30,0,9,21,2023)],
    '/etc/sysconfig'        => ['type'=>'dir','mtime'=>mktime(14,25,0,2,28,2026)],
    '/etc/sysctl.d'         => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/systemd'          => ['type'=>'dir','mtime'=>mktime(9,27,0,12,4,2024)],
    '/etc/tmpfiles.d'       => ['type'=>'dir','mtime'=>mktime(18,37,0,1,28,2026)],
    '/etc/tuned'            => ['type'=>'dir','mtime'=>mktime(16,44,0,11,29,2024)],
    '/etc/udev'             => ['type'=>'dir','mtime'=>mktime(18,39,0,1,28,2026)],
    '/etc/X11'              => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/xdg'              => ['type'=>'dir','mtime'=>mktime(0,0,0,10,2,2024)],
    '/etc/yum'              => ['type'=>'dir','mtime'=>mktime(18,38,0,1,28,2026)],
    '/etc/yum.repos.d'      => ['type'=>'dir','mtime'=>mktime(18,38,0,1,28,2026)],

    // ── files ─────────────────────────────────────────────────
    '/etc/adjtime'          => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"0.000000 0 0.000000\n0\nUTC"],
    '/etc/aliases'          => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>"# /etc/aliases\nmailer-daemon: postmaster\npostmaster: root\nnobody: root\nhostmaster: root\nusenet: root\nnews: root\nwebmaster: root\nwww: root\nftp: root\nabuse: root\nsecurity: root\nroot: admin@$H"],
    '/etc/almalinux-release'=> ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>"AlmaLinux release 9.7 (Seafoam Ocelot)"],
    '/etc/anacrontab'       => ['type'=>'file','mtime'=>mktime(13,43,0,9,25,2023),'content'=>"# /etc/anacrontab: configuration file for anacron\nSHELL=/bin/sh\nPATH=/sbin:/bin:/usr/sbin:/usr/bin\nMAILTO=root\n1\t5\tcron.daily\trun-parts /etc/cron.daily\n7\t25\tcron.weekly\trun-parts /etc/cron.weekly\n@monthly 45\tcron.monthly\trun-parts /etc/cron.monthly"],
    '/etc/at.deny'          => ['type'=>'file','mtime'=>mktime(1,38,0,11,12,2024),'content'=>''],
    '/etc/bashrc'           => ['type'=>'file','mtime'=>mktime(0,0,0,4,3,2024),'content'=>"# /etc/bashrc\nif [ \$EUID -eq 0 ]; then\n    PS1='\\u@\\h:\\w# '\nelse\n    PS1='\\u@\\h:\\w\\$ '\nfi\nalias ll='ls -la'\nalias la='ls -A'\nalias l='ls -CF'\nalias grep='grep --color=auto'"],
    '/etc/chrony.conf'      => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"pool 2.almalinux.pool.ntp.org iburst\ndriftfile /var/lib/chrony/drift\nmakestep 1.0 3\nrtcsync\nlogdir /var/log/chrony"],
    '/etc/colordiffrc'      => ['type'=>'file','mtime'=>mktime(18,46,0,1,17,2026),'content'=>"plain=off\nnewtext=darkgreen\noldtext=darkred\ndiffstuff=darkcyan\ncvsstuff=darkmagenta"],
    '/etc/crontab'          => ['type'=>'file','mtime'=>mktime(13,43,0,9,25,2023),'content'=>
"SHELL=/bin/bash\nPATH=/sbin:/bin:/usr/sbin:/usr/bin\n\n# .---------------- minute (0-59)\n# |  .------------- hour (0-23)\n# |  |  .---------- day of month (1-31)\n# |  |  |  .------- month (1-12)\n# |  |  |  |  .---- day of week (0-6)\n# m  h dom mon dow  user  command\n  0  2  *   *   *   root  /usr/local/bin/backup.sh\n  */5 * *   *   *   root  /usr/local/bin/health-check.sh\n 30  3  *   *   0   root  /usr/local/bin/weekly-report.sh\n  0  0  1   *   *   root  /usr/local/bin/monthly-cleanup.sh"],
    '/etc/crypttab'         => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>''],
    '/etc/dracut.conf'      => ['type'=>'file','mtime'=>mktime(18,48,0,12,17,2024),'content'=>"# dracut config\nadd_drivers+=\" nvme \""],
    '/etc/environment'      => ['type'=>'file','mtime'=>mktime(0,0,0,4,3,2024),'content'=>''],
    '/etc/exports'          => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>''],
    '/etc/filesystems'      => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>"ext4\next3\next2\nnodev proc\nnodev devpts\niso9660\nvfat\nhfs\nhfsplus\n*"],
    '/etc/fstab'            => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>
"# /etc/fstab\n# <device>          <mount>    <type>  <options>           <dump> <pass>\n/dev/sda1           /          xfs     defaults            0      1\n/dev/sda2           /boot      xfs     defaults            0      2\n/dev/sdb1           /mnt/db    xfs     defaults,noatime    0      2\n/dev/sdc1           /mnt/backup xfs    defaults,noatime    0      2\ntmpfs               /tmp       tmpfs   defaults,nosuid,nodev 0    0\ntmpfs               /dev/shm   tmpfs   defaults            0      0"],
    '/etc/fuse.conf'        => ['type'=>'file','mtime'=>mktime(0,0,0,10,2,2024),'content'=>"# /etc/fuse.conf\n# Set the maximum number of FUSE mounts allowed to non-root users.\n# The default is 1000.\n#mount_max = 1000\nuser_allow_other"],
    '/etc/group'            => ['type'=>'file','mtime'=>mktime(0,0,0,5,28,2025),'content'=>
"root:x:0:\nbin:x:1:\ndaemon:x:2:\nsys:x:3:\nadm:x:4:\ntty:x:5:\ndisk:x:6:\nlp:x:7:\nmem:x:8:\nkmem:x:9:\nwheel:x:10:deploy\nmail:x:12:\nman:x:15:\ndialout:x:18:\nfloppy:x:19:\ngames:x:20:\ntape:x:33:\nvideo:x:39:\nftp:x:50:\nlock:x:54:\naudio:x:63:\nnobody:x:65534:\nusers:x:100:\nhttpd:x:48:\nmysql:x:27:\nnginx:x:998:\nphp-fpm:x:997:\nsshd:x:74:\nchrony:x:994:\ndeploy:x:1001:\ndocker:x:999:deploy"],
    '/etc/host.conf'        => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>"multi on"],
    '/etc/hostname'         => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>$H],
    '/etc/hosts'            => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>
"127.0.0.1   localhost localhost.localdomain localhost4 localhost4.localdomain4\n::1         localhost localhost.localdomain localhost6 localhost6.localdomain6\n127.0.1.1   $H\n192.168.1.10  $H\n192.168.1.20  db-server\n192.168.1.30  backup-server"],
    '/etc/inittab'          => ['type'=>'file','mtime'=>mktime(9,27,0,12,4,2024),'content'=>"# inittab is no longer used.\n# The default runlevel is defined in /etc/systemd/system/default.target\nid:3:initdefault:"],
    '/etc/inputrc'          => ['type'=>'file','mtime'=>mktime(13,24,0,10,13,2024),'content'=>"# /etc/inputrc\nset meta-flag on\nset input-meta on\nset output-meta on\nset convert-meta off\n\"\\e[1~\": beginning-of-line\n\"\\e[4~\": end-of-line\n\"\\e[5~\": beginning-of-history\n\"\\e[6~\": end-of-history\n\"\\e[3~\": delete-char\n\"\\e[2~\": quoted-insert"],
    '/etc/issue'            => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>"AlmaLinux 9.7 (Seafoam Ocelot)\nKernel \\r on an \\m\n"],
    '/etc/issue.net'        => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>"AlmaLinux 9.7 (Seafoam Ocelot)"],
    '/etc/kdump.conf'       => ['type'=>'file','mtime'=>mktime(16,44,0,11,29,2024),'content'=>"path /var/crash\ncore_collector makedumpfile -l --message-level 7 -d 31"],
    '/etc/krb5.conf'        => ['type'=>'file','mtime'=>mktime(0,0,0,6,24,2025),'content'=>"[logging]\ndefault = FILE:/var/log/krb5libs.log\n\n[libdefaults]\ndns_lookup_realm = false\nticket_lifetime = 24h\nrenew_lifetime = 7d\nforwardable = true\ndefault_realm = EXAMPLE.COM"],
    '/etc/ld.so.cache'      => ['type'=>'file','mtime'=>mktime(15,26,0,3,4,2026),'content'=>'[binary — shared library cache]'],
    '/etc/ld.so.conf'       => ['type'=>'file','mtime'=>mktime(11,54,0,2,17,2026),'content'=>"include /etc/ld.so.conf.d/*.conf"],
    '/etc/locale.conf'      => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>"LANG=en_US.UTF-8\nLC_TIME=en_GB.UTF-8"],
    '/etc/logrotate.conf'   => ['type'=>'file','mtime'=>mktime(12,28,0,2,12,2026),'content'=>"# global options\nweekly\nrotate 4\ncreate\ndateext\ncompress\ninclude /etc/logrotate.d\n/var/log/wtmp {\n    monthly\n    create 0664 root utmp\n    minsize 1M\n    rotate 1\n}\n/var/log/btmp {\n    missingok\n    monthly\n    create 0600 root utmp\n    rotate 1\n}"],
    '/etc/machine-id'       => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"a3f2c1d4e5b6789012345678abcdef01"],
    '/etc/magic'            => ['type'=>'file','mtime'=>mktime(0,0,0,4,3,2024),'content'=>'# magic(5) - file magic patterns'],
    '/etc/man_db.conf'      => ['type'=>'file','mtime'=>mktime(14,56,0,9,21,2023),'content'=>"MANDB_MAP\t/usr/man\t/var/cache/man/fsstnd\nMANDB_MAP\t/usr/share/man\t/var/cache/man\nMANDB_MAP\t/usr/local/man\t/var/cache/man/oldlocal\nMANDB_MAP\t/usr/local/share/man\t/var/cache/man/local"],
    '/etc/motd'             => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>''],
    '/etc/my.cnf'           => ['type'=>'file','mtime'=>mktime(0,0,0,2,24,2024),'content'=>
"[mysqld]\ndatadir=/mnt/db/mysql\nsocket=/var/lib/mysql/mysql.sock\nlog-error=/var/log/mariadb/mariadb.log\npid-file=/run/mariadb/mariadb.pid\n\n[client]\nport=3306\nsocket=/var/lib/mysql/mysql.sock\n\n!includedir /etc/my.cnf.d"],
    '/etc/nanorc'           => ['type'=>'file','mtime'=>mktime(0,0,0,3,4,2026),'content'=>"# GNU nano config\nset autoindent\nset linenumbers\nset mouse\nset smooth\nset tabsize 4\nset tabstospaces\ninclude /usr/share/nano/*.nanorc"],
    '/etc/netconfig'        => ['type'=>'file','mtime'=>mktime(0,0,0,10,1,2024),'content'=>"udp6       tpi_clts      v     inet6    udp     -       -\ntcp6       tpi_cots_ord  v     inet6    tcp     -       -\nudp        tpi_clts      v     inet     udp     -       -\ntcp        tpi_cots_ord  v     inet     tcp     -       -\nrawip6     tpi_raw       -     inet6     -      -       -\nrawip      tpi_raw       -     inet      -      -       -\nlocal      tpi_cots_ord  -      -        -      -       -"],
    '/etc/networks'         => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>"default 0.0.0.0\nloopback 127.0.0.0\nlink-local 169.254.0.0"],
    '/etc/npmrc'            => ['type'=>'file','mtime'=>mktime(16,47,0,2,17,2026),'content'=>"prefix=/usr/local"],
    '/etc/nsswitch.conf'    => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"passwd:     sss files systemd\nshadow:     files sss\ngroup:      sss files systemd\nhosts:      files dns myhostname\nnetworks:   files dns\nprotocols:  files\nservices:   files sss\nethers:     files\nrpc:        files\nnetgroup:   sss files"],
    '/etc/os-release'       => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>
"NAME=\"AlmaLinux\"\nVERSION=\"9.7 (Seafoam Ocelot)\"\nID=almalinux\nID_LIKE=\"rhel centos fedora\"\nVERSION_ID=\"9.7\"\nPLATFORM_ID=\"platform:el9\"\nPRETTY_NAME=\"AlmaLinux 9.7 (Seafoam Ocelot)\"\nANSI_COLOR=\"0;34\"\nLOGO=\"fedora-logo-icon\"\nCPE_NAME=\"cpe:/o:almalinux:almalinux:9::baseos\"\nHOME_URL=\"https://almalinux.org/\"\nDOCUMENTATION_URL=\"https://wiki.almalinux.org/\"\nBUG_REPORT_URL=\"https://bugs.almalinux.org/\"\nALMA_SUPPORT_END_DATE=\"2032-06-01\""],
    '/etc/passwd'           => ['type'=>'file','mtime'=>mktime(0,0,0,5,28,2025),'content'=>
"root:x:0:0:root:/root:/bin/bash\nbin:x:1:1:bin:/bin:/sbin/nologin\ndaemon:x:2:2:daemon:/sbin:/sbin/nologin\nadm:x:3:4:adm:/var/adm:/sbin/nologin\nlp:x:4:7:lp:/var/spool/lpd:/sbin/nologin\nsync:x:5:0:sync:/sbin:/bin/sync\nshutdown:x:6:0:shutdown:/sbin:/sbin/shutdown\nhalt:x:7:0:halt:/sbin:/sbin/halt\nmail:x:8:12:mail:/var/spool/mail:/sbin/nologin\nnobody:x:65534:65534:Kernel Overflow User:/:/sbin/nologin\nhttpd:x:48:48:Apache:/usr/share/httpd:/sbin/nologin\nmysql:x:27:27:MySQL Server:/var/lib/mysql:/sbin/nologin\nnginx:x:998:998:Nginx web server:/var/lib/nginx:/sbin/nologin\nphp-fpm:x:997:997:php-fpm:/run/php-fpm:/sbin/nologin\nsshd:x:74:74:Privilege-separated SSH:/usr/share/empty.sshd:/sbin/nologin\nchrony:x:994:994::/var/lib/chrony:/sbin/nologin\ndeploy:x:1001:1001:Deploy User:/home/deploy:/bin/bash"],
    '/etc/php-fpm.conf'     => ['type'=>'file','mtime'=>mktime(6,30,0,2,11,2026),'content'=>"[global]\npid = /run/php-fpm/php-fpm.pid\nerror_log = /var/log/php-fpm/error.log\nlog_level = warning\ndaemonize = yes\ninclude=/etc/php-fpm.d/*.conf"],
    '/etc/php.ini'          => ['type'=>'file','mtime'=>mktime(11,35,0,2,6,2026),'content'=>
"[PHP]\nengine = On\nshort_open_tag = Off\nprecision = 14\noutput_buffering = 4096\nzlib.output_compression = Off\nimplicit_flush = Off\nmax_execution_time = 30\nmemory_limit = 256M\nerror_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT\ndisplay_errors = Off\nlog_errors = On\nerror_log = /var/log/php-fpm/php_errors.log\npost_max_size = 64M\nupload_max_filesize = 64M\nmax_file_uploads = 20\ndefault_charset = \"UTF-8\"\nextension_dir = /usr/lib64/php/modules\ndate.timezone = Europe/Amsterdam\n\n[Session]\nsession.save_handler = files\nsession.save_path = /var/lib/php/session\nsession.use_strict_mode = 1\nsession.cookie_httponly = 1\nsession.cookie_secure = 1"],
    '/etc/printcap'         => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>'# /etc/printcap'],
    '/etc/profile'          => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>
"# /etc/profile\nif [ \"\$EUID\" = \"0\" ]; then\n    pathmunge /usr/sbin\n    pathmunge /usr/local/sbin\nelse\n    pathmunge /usr/local/sbin after\n    pathmunge /usr/sbin after\nfi\nexport PATH USER LOGNAME MAIL HOSTNAME HISTSIZE HISTCONTROL\nfor i in /etc/profile.d/*.sh; do\n    [ -r \"\$i\" ] && . \"\$i\"\ndone\nunset i pathmunge"],
    '/etc/protocols'        => ['type'=>'file','mtime'=>mktime(0,0,0,6,23,2020),'content'=>"ip\t0\tIP\nicmp\t1\tICMP\ntcp\t6\tTCP\nudp\t17\tUDP\nipv6\t41\tIPv6\nospf\t89\tOSPF\nsctp\t132\tSCTP"],
    '/etc/resolv.conf'      => ['type'=>'file','mtime'=>mktime(14,36,0,2,28,2026),'content'=>
"# Generated by NetworkManager\nnameserver 8.8.8.8\nnameserver 8.8.4.4\nsearch $H.local"],
    '/etc/rsyncd.conf'      => ['type'=>'file','mtime'=>mktime(0,0,0,3,13,2025),'content'=>"# /etc/rsyncd.conf\nlog file = /var/log/rsyncd.log\ntransfer logging = yes\n\n[backup]\npath = /mnt/backup\ncomment = Backup volume\nread only = no\nauth users = backup\nsecrets file = /etc/rsyncd.secrets"],
    '/etc/rsyslog.conf'     => ['type'=>'file','mtime'=>mktime(15,8,0,9,21,2023),'content'=>
"# /etc/rsyslog.conf\nmodule(load=\"imuxsock\")\nmodule(load=\"imjournal\")\n\n*.info;mail.none;authpriv.none;cron.none  /var/log/messages\nauthpriv.*                                /var/log/secure\nmail.*                                    -/var/log/maillog\ncron.*                                    /var/log/cron\n*.emerg                                   :omusrmsg:*\nuucp,news.crit                            /var/log/spooler\nlocal7.*                                  /var/log/boot.log"],
    '/etc/shadow'           => ['type'=>'file','mtime'=>mktime(0,0,0,5,28,2025),'content'=>
"root:\$6\$rounds=5000\$rAnDoMsAlT123\$hashedpassword1234567890:19500:0:99999:7:::\ndeploy:\$6\$rounds=5000\$rAnDoMsAlT456\$hashedpassword0987654321:19500:0:99999:7:::"],
    '/etc/shells'           => ['type'=>'file','mtime'=>mktime(0,0,0,3,18,2024),'content'=>"/bin/sh\n/bin/bash\n/usr/bin/bash\n/usr/bin/sh\n/usr/bin/tmux"],
    '/etc/strato-release'   => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"Strato Managed Server"],
    '/etc/sudoers'          => ['type'=>'file','mtime'=>mktime(14,30,0,9,21,2023),'content'=>
"# /etc/sudoers\nDefaults    env_reset\nDefaults    mail_badpass\nDefaults    secure_path=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"\nDefaults    logfile=/var/log/sudo.log\n\nroot    ALL=(ALL:ALL) ALL\n%wheel  ALL=(ALL) ALL\ndeploy  ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart httpd, /usr/bin/systemctl restart php-fpm, /usr/bin/systemctl reload nginx"],
    '/etc/sysctl.conf'      => ['type'=>'file','mtime'=>mktime(9,27,0,12,4,2024),'content'=>"# /etc/sysctl.conf\nnet.ipv4.ip_forward = 0\nnet.ipv4.conf.default.rp_filter = 1\nnet.ipv4.conf.default.accept_source_route = 0\nkernel.sysrq = 0\nnet.ipv4.tcp_syncookies = 1\nvm.swappiness = 10"],
    '/etc/tmux.conf'        => ['type'=>'file','mtime'=>mktime(12,28,0,2,12,2026),'content'=>"set -g default-terminal \"tmux-256color\"\nset -ga terminal-overrides \",*256col*:Tc\"\nset -g history-limit 50000\nset -g mouse on\nset -g base-index 1\nbind r source-file ~/.tmux.conf"],
    '/etc/vconsole.conf'    => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"KEYMAP=us\nFONT=eurlatgr"],
    '/etc/virc'             => ['type'=>'file','mtime'=>mktime(0,24,0,11,12,2024),'content'=>"set nocompatible\nset backspace=indent,eol,start"],
    '/etc/wgetrc'           => ['type'=>'file','mtime'=>mktime(0,0,0,9,3,2024),'content'=>"# /etc/wgetrc\nverbose = off\ntimestamping = on\nquiet = off"],

    // ── symlinks (shown as files with -> in content) ───────────
    '/etc/grub2.cfg'        => ['type'=>'file','mtime'=>mktime(11,41,0,11,11,2024),'content'=>'-> ../boot/grub2/grub.cfg'],
    '/etc/grub2-efi.cfg'    => ['type'=>'file','mtime'=>mktime(11,41,0,11,11,2024),'content'=>'-> ../boot/grub2/grub.cfg'],
    '/etc/localtime'        => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>'-> ../usr/share/zoneinfo/Europe/Amsterdam'],
    '/etc/mtab'             => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>'-> ../proc/self/mounts'],
    '/etc/os-release~link'  => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>'-> ../usr/lib/os-release'],
    '/etc/rc.local'         => ['type'=>'file','mtime'=>mktime(9,27,0,12,4,2024),'content'=>'-> rc.d/rc.local'],
    '/etc/redhat-release'   => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>'-> almalinux-release'],
    '/etc/system-release'   => ['type'=>'file','mtime'=>mktime(11,15,0,11,11,2024),'content'=>'-> almalinux-release'],
    '/etc/yum.conf'         => ['type'=>'file','mtime'=>mktime(13,27,0,9,22,2023),'content'=>'-> dnf/dnf.conf'],

    // ── /etc/ssh ──────────────────────────────────────────────
    '/etc/ssh/sshd_config'      => ['type'=>'file','mtime'=>mktime(2,52,0,12,18,2024),'content'=>
"Port 22\nAddressFamily any\nListenAddress 0.0.0.0\nPermitRootLogin prohibit-password\nPubkeyAuthentication yes\nPasswordAuthentication no\nPermitEmptyPasswords no\nChallengeResponseAuthentication no\nUsePAM yes\nX11Forwarding no\nPrintMotd yes\nAcceptEnv LANG LC_*\nSubsystem sftp /usr/lib/openssh/sftp-server\nAllowUsers root deploy\nMaxAuthTries 3\nClientAliveInterval 300\nClientAliveCountMax 2\nBanner /etc/issue.net"],
    '/etc/ssh/ssh_config'       => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>"Host *\n    GSSAPIAuthentication yes\n    SendEnv LANG LC_*\n    HashKnownHosts yes"],
    '/etc/ssh/ssh_host_rsa_key' => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"-----BEGIN OPENSSH PRIVATE KEY-----\n[private key — not readable]\n-----END OPENSSH PRIVATE KEY-----"],
    '/etc/ssh/ssh_host_rsa_key.pub' => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC... root@$H"],
    '/etc/ssh/ssh_host_ed25519_key' => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"-----BEGIN OPENSSH PRIVATE KEY-----\n[private key — not readable]\n-----END OPENSSH PRIVATE KEY-----"],
    '/etc/ssh/ssh_host_ed25519_key.pub' => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... root@$H"],

    // ── /etc/httpd ────────────────────────────────────────────
    '/etc/httpd/conf/httpd.conf'    => ['type'=>'file','mtime'=>mktime(4,33,0,2,13,2026),'content'=>
"ServerRoot \"/etc/httpd\"\nListen 80\nListen 443\nServerName $H\nServerAdmin webmaster@$H\nDocumentRoot \"/var/www/html\"\nDirectoryIndex index.php index.html\nErrorLog \"/var/log/httpd/error_log\"\nCustomLog \"/var/log/httpd/access_log\" combined\nKeepAlive On\nMaxKeepAliveRequests 100\nKeepAliveTimeout 5\nIncludeOptional conf.d/*.conf"],
    '/etc/httpd/conf.d/ssl.conf'    => ['type'=>'file','mtime'=>mktime(4,33,0,2,13,2026),'content'=>
"<VirtualHost *:443>\n    ServerName $H\n    SSLEngine on\n    SSLCertificateFile /etc/letsencrypt/live/$H/fullchain.pem\n    SSLCertificateKeyFile /etc/letsencrypt/live/$H/privkey.pem\n    DocumentRoot /var/www/html\n    ErrorLog /var/log/httpd/ssl_error_log\n    CustomLog /var/log/httpd/ssl_access_log combined\n    CustomLog /var/log/httpd/ssl_request_log \"%t %h %{SSL_PROTOCOL}x %{SSL_CIPHER}x \\\"%r\\\" %b\"\n</VirtualHost>"],
    '/etc/httpd/conf.d/welcome.conf'=> ['type'=>'file','mtime'=>mktime(4,33,0,2,13,2026),'content'=>"# This file is intentionally commented out."],
    '/etc/httpd/conf.modules.d/00-base.conf' => ['type'=>'file','mtime'=>mktime(4,33,0,2,13,2026),'content'=>"LoadModule auth_basic_module modules/mod_auth_basic.so\nLoadModule authz_core_module modules/mod_authz_core.so\nLoadModule dir_module modules/mod_dir.so\nLoadModule env_module modules/mod_env.so\nLoadModule mime_module modules/mod_mime.so\nLoadModule rewrite_module modules/mod_rewrite.so"],

    // ── /etc/nginx ────────────────────────────────────────────
    '/etc/nginx/nginx.conf'         => ['type'=>'file','mtime'=>mktime(11,24,0,10,20,2024),'content'=>
"user nginx;\nworker_processes auto;\nerror_log /var/log/nginx/error.log warn;\npid /run/nginx.pid;\n\nevents {\n    worker_connections 1024;\n}\n\nhttp {\n    include /etc/nginx/mime.types;\n    default_type application/octet-stream;\n    sendfile on;\n    keepalive_timeout 65;\n    include /etc/nginx/conf.d/*.conf;\n}"],

    // ── /etc/my.cnf.d ─────────────────────────────────────────
    '/etc/my.cnf.d/mariadb-server.cnf' => ['type'=>'file','mtime'=>mktime(0,0,0,2,14,2024),'content'=>
"[mysqld]\nbind-address    = 127.0.0.1\nmax_connections = 200\ninnodb_buffer_pool_size = 4G\ninnodb_log_file_size    = 512M\nslow_query_log  = 1\nslow_query_log_file = /var/log/mariadb/slow.log\nlong_query_time = 2\n\n[mysqldump]\nquick\nmax_allowed_packet = 64M"],

    // ── /etc/logrotate.d ──────────────────────────────────────
    '/etc/logrotate.d/httpd'        => ['type'=>'file','mtime'=>mktime(12,40,0,2,17,2026),'content'=>
"/var/log/httpd/access_log /var/log/httpd/error_log /var/log/httpd/ssl_access_log /var/log/httpd/ssl_error_log /var/log/httpd/ssl_request_log {\n    daily\n    missingok\n    rotate 8\n    compress\n    delaycompress\n    notifempty\n    sharedscripts\n    postrotate\n        /bin/systemctl reload httpd > /dev/null 2>/dev/null || true\n    endscript\n}"],
    '/etc/logrotate.d/mariadb'      => ['type'=>'file','mtime'=>mktime(12,40,0,2,17,2026),'content'=>
"/var/log/mariadb/mariadb.log {\n    daily\n    rotate 7\n    compress\n    missingok\n    notifempty\n    create 640 mysql adm\n    postrotate\n        /bin/systemctl reload mariadb > /dev/null 2>/dev/null || true\n    endscript\n}"],
    '/etc/logrotate.d/nginx'        => ['type'=>'file','mtime'=>mktime(12,40,0,2,17,2026),'content'=>
"/var/log/nginx/*.log {\n    daily\n    missingok\n    rotate 52\n    compress\n    delaycompress\n    notifempty\n    sharedscripts\n    postrotate\n        nginx -s reopen\n    endscript\n}"],
    '/etc/logrotate.d/syslog'       => ['type'=>'file','mtime'=>mktime(17,12,0,9,21,2023),'content'=>
"/var/log/cron\n/var/log/maillog\n/var/log/messages\n/var/log/secure\n/var/log/spooler\n{\n    missingok\n    sharedscripts\n    postrotate\n        /bin/kill -HUP `cat /var/run/syslogd.pid 2>/dev/null` 2>/dev/null || true\n    endscript\n}"],

    // ── /etc/cron.* ───────────────────────────────────────────
    '/etc/cron.d/backup'            => ['type'=>'file','mtime'=>mktime(13,43,0,9,25,2023),'content'=>"0 2 * * * root /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1"],
    '/etc/cron.d/0hourly'           => ['type'=>'file','mtime'=>mktime(13,43,0,9,25,2023),'content'=>"SHELL=/bin/bash\nPATH=/sbin:/bin:/usr/sbin:/usr/bin\nMAILTO=root\n01 * * * * root run-parts /etc/cron.hourly"],
    '/etc/cron.daily/logcheck'      => ['type'=>'file','mtime'=>mktime(0,0,0,4,11,2022),'content'=>"#!/bin/bash\n/usr/sbin/logcheck"],
    '/etc/cron.daily/logrotate'     => ['type'=>'file','mtime'=>mktime(0,0,0,4,11,2022),'content'=>"#!/bin/sh\n/usr/sbin/logrotate /etc/logrotate.conf\nEXITVALUE=\$?\nif [ \$EXITVALUE != 0 ]; then\n    /usr/bin/logger -t logrotate \"ALERT exited abnormally with [\$EXITVALUE]\"\nfi\nexit 0"],
    '/etc/cron.daily/man-db'        => ['type'=>'file','mtime'=>mktime(0,0,0,4,11,2022),'content'=>"#!/bin/bash\nmandb -q"],
    '/etc/cron.weekly/fstrim'       => ['type'=>'file','mtime'=>mktime(0,0,0,4,11,2022),'content'=>"#!/bin/bash\nfstrim -av"],

    // ── /etc/profile.d ────────────────────────────────────────
    '/etc/profile.d/aliases.sh'     => ['type'=>'file','mtime'=>mktime(16,44,0,11,29,2024),'content'=>
"alias ll='ls -la'\nalias la='ls -A'\nalias l='ls -CF'\nalias grep='grep --color=auto'\nalias df='df -h'\nalias free='free -h'"],
    '/etc/profile.d/colorls.sh'     => ['type'=>'file','mtime'=>mktime(16,44,0,11,29,2024),'content'=>"# Color support for ls\nexport LS_COLORS='di=34:ln=35:so=32:pi=33:ex=31:bd=34;46:cd=34;43:su=30;41:sg=30;46:tw=30;42:ow=30;43'"],
    '/etc/profile.d/lang.sh'        => ['type'=>'file','mtime'=>mktime(16,44,0,11,29,2024),'content'=>"export LANG=en_US.UTF-8\nexport LC_TIME=en_GB.UTF-8"],

    // ── /etc/pki ──────────────────────────────────────────────
    '/etc/pki/tls/certs/server.crt' => ['type'=>'file','mtime'=>mktime(8,32,0,3,13,2026),'content'=>
"-----BEGIN CERTIFICATE-----\nMIIDXTCCAkWgAwIBAgIJALmCFxSqatp5MA0GCSqGSIb3DQEBCwUAMEUxCzAJBgNV\n[certificate data — issued by Let's Encrypt]\n-----END CERTIFICATE-----"],
    '/etc/pki/tls/private/server.key' => ['type'=>'file','mtime'=>mktime(8,32,0,3,13,2026),'content'=>
"-----BEGIN RSA PRIVATE KEY-----\n[private key — not readable]\n-----END RSA PRIVATE KEY-----"],

    // ── /etc/sysconfig ────────────────────────────────────────
    '/etc/sysconfig/network'        => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"NETWORKING=yes\nHOSTNAME=$H"],
    '/etc/sysconfig/clock'          => ['type'=>'file','mtime'=>mktime(0,0,0,10,27,2023),'content'=>"ZONE=Europe/Amsterdam\nUTC=true"],

    // ──────────────────────────────────────────────────────────
    //  /home
    // ──────────────────────────────────────────────────────────
    '/home/deploy'              => ['type'=>'dir'],
    '/home/deploy/.ssh'         => ['type'=>'dir'],
    '/home/deploy/.ssh/authorized_keys' => ['type'=>'file','content'=>
"ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3v8... deploy@workstation\nssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDx9z... deploy@laptop"],
    '/home/deploy/.ssh/id_rsa'          => ['type'=>'file','content'=>'-----BEGIN OPENSSH PRIVATE KEY-----\n[private key]\n-----END OPENSSH PRIVATE KEY-----'],
    '/home/deploy/.ssh/id_rsa.pub'      => ['type'=>'file','content'=>'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3v8... deploy@server'],
    '/home/deploy/.bashrc'      => ['type'=>'file','content'=>
"# .bashrc\nexport PS1='\\u@\\h:\\w\\$ '\nexport PATH=\$PATH:/home/deploy/.local/bin\nexport EDITOR=vim\nalias ll='ls -la'\nalias deploy='cd /var/www && git pull && sudo systemctl restart apache2'"],
    '/home/deploy/.bash_history' => ['type'=>'file','content'=>
"git pull origin main\nsudo systemctl restart httpd\ntail -f /var/log/httpd/error_log\nmysql -u root -p\ndf -h\nfree -h\nps aux | grep httpd\nssh backup-server\nrsync -avz /var/www/ backup-server:/mnt/backup/www/"],
    '/home/deploy/.profile'     => ['type'=>'file','content'=>
"# .profile\nif [ -n \"\$BASH_VERSION\" ]; then\n    if [ -f \"\$HOME/.bashrc\" ]; then\n        . \"\$HOME/.bashrc\"\n    fi\nfi"],
    '/home/deploy/deploy.sh'    => ['type'=>'file','content'=>
"#!/bin/bash\n# Deployment script\nset -e\ncd /var/www/html\necho \"[$(date)] Starting deployment...\"\ngit fetch origin\ngit reset --hard origin/main\ncomposer install --no-dev --optimize-autoloader\nphp artisan migrate --force\nphp artisan cache:clear\nphp artisan config:cache\nsudo systemctl reload apache2\necho \"[$(date)] Deployment complete.\""],

    // ──────────────────────────────────────────────────────────
    //  /root
    // ──────────────────────────────────────────────────────────
    '/root/.ssh'                => ['type'=>'dir'],
    '/root/.aws'                => ['type'=>'dir'],
    '/root/.config'             => ['type'=>'dir'],

    '/root/.bashrc'             => ['type'=>'file','content'=>
"# .bashrc\nexport PS1='\\u@\\h:\\w# '\nexport EDITOR=vim\nexport HISTSIZE=10000\nexport HISTFILESIZE=20000\nexport HISTTIMEFORMAT=\"%F %T \"\nalias ll='ls -la'\nalias la='ls -A'\nalias grep='grep --color=auto'\nalias df='df -h'\nalias free='free -h'\nalias ports='netstat -tulanp'\nalias myip='curl -s ifconfig.me'"],
    '/root/.bash_history'       => ['type'=>'file','content'=>
"apt-get update\napt-get upgrade -y\ndf -h\nfree -h\nps aux\ntail -f /var/log/httpd/error_log\nmysql -u root -p\nsystemctl status httpd\nsystemctl restart httpd\ncertbot renew\ncrontab -l\nls -la /var/www/html\ncat /var/log/secure | grep Failed\nnetstat -tulanp\niptables -L -v\nuname -a\nuptime"],
    '/root/.bash_profile'       => ['type'=>'file','content'=>
"# .bash_profile\nif [ -f ~/.bashrc ]; then\n    . ~/.bashrc\nfi"],
    '/root/.vimrc'              => ['type'=>'file','content'=>
"set nocompatible\nset number\nset tabstop=4\nset shiftwidth=4\nset expandtab\nset autoindent\nset hlsearch\nset incsearch\nsyntax on\ncolorscheme desert"],
    '/root/.ssh/authorized_keys' => ['type'=>'file','content'=>
"ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3v8... admin@workstation"],
    '/root/.ssh/id_rsa'          => ['type'=>'file','content'=>'-----BEGIN OPENSSH PRIVATE KEY-----\n[private key]\n-----END OPENSSH PRIVATE KEY-----'],
    '/root/.ssh/id_rsa.pub'      => ['type'=>'file','content'=>'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3v8... root@' . $H],
    '/root/.ssh/known_hosts'     => ['type'=>'file','content'=>
"backup-server ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAB...\ndb-server ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAB...\ngithub.com ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCj..."],
    '/root/.aws/credentials'     => ['type'=>'file','content'=>
"[default]\naws_access_key_id = AKIAIOSFODNN7EXAMPLE\naws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY\nregion = eu-west-1"],
    '/root/notes.txt'            => ['type'=>'file','content'=>
"Server maintenance notes\n========================\n\nLast updated: March 2026\n\n- MariaDB slow query log enabled, check weekly (/var/log/mariadb/slow.log)\n- SSL cert expires 2026-09-14, renew with: certbot renew\n- Backup job runs nightly at 02:00, check /var/log/backup.log\n- Deploy user has passwordless sudo for httpd + php-fpm restarts only\n- Firewall: only 22, 80, 443 open externally\n- /mnt/db is on separate SSD, do NOT fill above 80%\n- See /usr/local/bin/ for all maintenance scripts"],
    '/root/server-setup.sh'      => ['type'=>'file','content'=>
"#!/bin/bash\n# Initial server setup script\n# Run once as root\n\nset -e\n\napt-get update && apt-get upgrade -y\napt-get install -y apache2 mysql-server php8.2 php8.2-mysql \\\n    php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml \\\n    certbot python3-certbot-apache fail2ban ufw git composer\n\n# Firewall\nufw allow 22/tcp\nufw allow 80/tcp\nufw allow 443/tcp\nufw --force enable\n\n# MySQL hardening\nmysql_secure_installation\n\n# Create deploy user\nuseradd -m -s /bin/bash deploy\nmkdir -p /home/deploy/.ssh\nchmod 700 /home/deploy/.ssh\n\necho \"Setup complete.\""],

    // ──────────────────────────────────────────────────────────
    //  /var
    // ──────────────────────────────────────────────────────────
    // ── /var subdirs ──
    '/var/log'                  => ['type'=>'dir'],
    '/var/log/anaconda'         => ['type'=>'dir'],
    '/var/log/audit'            => ['type'=>'dir'],
    '/var/log/chrony'           => ['type'=>'dir'],
    '/var/log/httpd'            => ['type'=>'dir'],
    '/var/log/letsencrypt'      => ['type'=>'dir'],
    '/var/log/mail'             => ['type'=>'dir'],
    '/var/log/mariadb'          => ['type'=>'dir'],
    '/var/log/php-fpm'          => ['type'=>'dir'],
    '/var/log/private'          => ['type'=>'dir'],
    '/var/log/samba'            => ['type'=>'dir'],
    '/var/log/sssd'             => ['type'=>'dir'],
    '/var/log/tuned'            => ['type'=>'dir'],
    '/var/www'                  => ['type'=>'dir'],
    '/var/www/html'             => ['type'=>'dir'],
    '/var/spool'                => ['type'=>'dir'],
    '/var/spool/cron'           => ['type'=>'dir'],
    '/var/spool/cron/crontabs'  => ['type'=>'dir'],
    '/var/run'                  => ['type'=>'dir'],
    '/var/cache'                => ['type'=>'dir'],
    '/var/lib'                  => ['type'=>'dir'],
    '/var/lib/mysql'            => ['type'=>'dir'],

    // ── /var/log/httpd — matches real server layout ──
    '/var/log/httpd/access_log'               => ['type'=>'file','mtime'=>mktime(13,52,0,3,12,2026),'content'=>
"192.168.1.42 - - [12/Mar/2026:08:14:22 +0100] \"GET / HTTP/1.1\" 200 4823 \"-\" \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36\"\n192.168.1.42 - - [12/Mar/2026:08:14:23 +0100] \"GET /css/style.css HTTP/1.1\" 200 1842 \"https://$H/\" \"Mozilla/5.0\"\n185.220.101.45 - - [12/Mar/2026:09:02:11 +0100] \"GET /wp-admin/ HTTP/1.1\" 404 512 \"-\" \"python-requests/2.28\"\n185.220.101.45 - - [12/Mar/2026:09:02:12 +0100] \"POST /xmlrpc.php HTTP/1.1\" 404 512\n10.0.0.5 - deploy [12/Mar/2026:10:31:07 +0100] \"GET /api/status HTTP/1.1\" 200 128\n93.184.216.34 - - [12/Mar/2026:11:15:44 +0100] \"GET /index.php HTTP/1.1\" 200 9241\n192.168.1.42 - - [12/Mar/2026:13:22:09 +0100] \"POST /api/login HTTP/1.1\" 200 312\n45.33.32.156 - - [12/Mar/2026:13:01:33 +0100] \"GET /etc/passwd HTTP/1.1\" 404 512\n45.33.32.156 - - [12/Mar/2026:13:01:34 +0100] \"GET /.env HTTP/1.1\" 404 512"],
    '/var/log/httpd/access_log-20260215'      => ['type'=>'file','mtime'=>mktime(23,58,0,2,14,2026),'content'=>'[rotated access log — Feb 8-14 2026]'],
    '/var/log/httpd/access_log-20260222'      => ['type'=>'file','mtime'=>mktime(23,58,0,2,21,2026),'content'=>'[rotated access log — Feb 15-21 2026]'],
    '/var/log/httpd/access_log-20260301'      => ['type'=>'file','mtime'=>mktime(23,59,0,2,28,2026),'content'=>'[rotated access log — Feb 22-28 2026]'],
    '/var/log/httpd/access_log-20260308'      => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated access log — Mar 1-7 2026]'],

    '/var/log/httpd/error_log'                => ['type'=>'file','mtime'=>mktime(13,24,0,3,12,2026),'content'=>
"[Wed Mar 12 02:14:11.842310 2026] [mpm_prefork:notice] [pid 1105] AH00163: Apache/2.4.62 (AlmaLinux) configured -- resuming normal operations\n[Wed Mar 12 02:14:11.843201 2026] [core:notice] [pid 1105] AH00094: Command line: '/usr/sbin/httpd -D FOREGROUND'\n[Wed Mar 12 09:02:11.334455 2026] [authz_core:error] [pid 2244] AH01630: client denied by server configuration: /var/www/html/.env\n[Wed Mar 12 09:02:12.556677 2026] [authz_core:error] [pid 2244] AH01630: client denied by server configuration: /var/www/html/xmlrpc.php\n[Wed Mar 12 13:01:33.112233 2026] [authz_core:error] [pid 2251] AH01630: client denied by server configuration: /var/www/html/etc/passwd"],
    '/var/log/httpd/error_log-20260215'       => ['type'=>'file','mtime'=>mktime(0,0,0,2,15,2026),'content'=>'[rotated error log — Feb 8-14 2026]'],
    '/var/log/httpd/error_log-20260222'       => ['type'=>'file','mtime'=>mktime(0,0,0,2,22,2026),'content'=>'[rotated error log — Feb 15-21 2026]'],
    '/var/log/httpd/error_log-20260301'       => ['type'=>'file','mtime'=>mktime(0,0,0,3,1,2026), 'content'=>'[rotated error log — Feb 22-28 2026]'],
    '/var/log/httpd/error_log-20260308'       => ['type'=>'file','mtime'=>mktime(0,0,0,3,8,2026), 'content'=>'[rotated error log — Mar 1-7 2026]'],

    '/var/log/httpd/ssl_access_log'           => ['type'=>'file','mtime'=>mktime(13,51,0,3,12,2026),'content'=>
"192.168.1.42 - - [12/Mar/2026:08:14:25 +0100] \"GET / HTTP/1.1\" 200 4823 \"-\" \"Mozilla/5.0\"\n10.0.0.5 - - [12/Mar/2026:10:31:09 +0100] \"GET /api/status HTTP/1.1\" 200 128\n93.184.216.34 - - [12/Mar/2026:11:15:46 +0100] \"GET /index.php HTTP/1.1\" 200 9241"],
    '/var/log/httpd/ssl_access_log-20260215'  => ['type'=>'file','mtime'=>mktime(23,43,0,2,14,2026),'content'=>'[rotated SSL access log — Feb 8-14 2026]'],
    '/var/log/httpd/ssl_access_log-20260222'  => ['type'=>'file','mtime'=>mktime(23,16,0,2,21,2026),'content'=>'[rotated SSL access log — Feb 15-21 2026]'],
    '/var/log/httpd/ssl_access_log-20260301'  => ['type'=>'file','mtime'=>mktime(23,22,0,2,28,2026),'content'=>'[rotated SSL access log — Feb 22-28 2026]'],
    '/var/log/httpd/ssl_access_log-20260308'  => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated SSL access log — Mar 1-7 2026]'],

    '/var/log/httpd/ssl_error_log'            => ['type'=>'file','mtime'=>mktime(13,39,0,3,12,2026),'content'=>
"[Wed Mar 12 02:14:11.001122 2026] [ssl:notice] [pid 1105] AH01876: mod_ssl/2.4.62 compiled against OpenSSL 3.2.1\n[Wed Mar 12 02:14:11.002233 2026] [ssl:info] [pid 1108] AH01887: Init: Initializing (virtual) servers for SSL"],
    '/var/log/httpd/ssl_error_log-20260215'   => ['type'=>'file','mtime'=>mktime(23,43,0,2,14,2026),'content'=>'[rotated SSL error log]'],
    '/var/log/httpd/ssl_error_log-20260222'   => ['type'=>'file','mtime'=>mktime(23,15,0,2,21,2026),'content'=>'[rotated SSL error log]'],
    '/var/log/httpd/ssl_error_log-20260301'   => ['type'=>'file','mtime'=>mktime(23,22,0,2,28,2026),'content'=>'[rotated SSL error log]'],
    '/var/log/httpd/ssl_error_log-20260308'   => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated SSL error log]'],

    '/var/log/httpd/ssl_request_log'          => ['type'=>'file','mtime'=>mktime(13,51,0,3,12,2026),'content'=>
"[12/Mar/2026:08:14:25 +0100] 192.168.1.42 TLSv1.3 TLS_AES_256_GCM_SHA384 \"GET / HTTP/1.1\" 4823\n[12/Mar/2026:10:31:09 +0100] 10.0.0.5 TLSv1.3 TLS_AES_256_GCM_SHA384 \"GET /api/status HTTP/1.1\" 128"],
    '/var/log/httpd/ssl_request_log-20260215' => ['type'=>'file','mtime'=>mktime(23,43,0,2,14,2026),'content'=>'[rotated SSL request log]'],
    '/var/log/httpd/ssl_request_log-20260222' => ['type'=>'file','mtime'=>mktime(23,16,0,2,21,2026),'content'=>'[rotated SSL request log]'],
    '/var/log/httpd/ssl_request_log-20260301' => ['type'=>'file','mtime'=>mktime(23,22,0,2,28,2026),'content'=>'[rotated SSL request log]'],
    '/var/log/httpd/ssl_request_log-20260308' => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated SSL request log]'],

    '/var/log/httpd/wget_log'                 => ['type'=>'file','mtime'=>mktime(13,52,0,3,12,2026),'content'=>
"[12/Mar/2026:00:05:01] wget cron job started\n[12/Mar/2026:00:05:02] fetching https://$H/api/health\n[12/Mar/2026:00:05:02] 200 OK\n[12/Mar/2026:00:10:01] wget cron job started\n[12/Mar/2026:00:10:02] 200 OK"],
    '/var/log/httpd/wget_log-20260215'        => ['type'=>'file','mtime'=>mktime(22,31,0,2,14,2026),'content'=>'[rotated wget log]'],
    '/var/log/httpd/wget_log-20260222'        => ['type'=>'file','mtime'=>mktime(14,37,0,2,21,2026),'content'=>'[rotated wget log]'],
    '/var/log/httpd/wget_log-20260301'        => ['type'=>'file','mtime'=>mktime(18,17,0,2,28,2026),'content'=>'[rotated wget log]'],
    '/var/log/httpd/wget_log-20260308'        => ['type'=>'file','mtime'=>mktime(19,29,0,3,7,2026), 'content'=>'[rotated wget log]'],

    // ── /var/log — top-level log files matching real server ──
    '/var/log/btmp'             => ['type'=>'file','mtime'=>mktime(13,53,0,3,12,2026),'content'=>'[binary — failed login attempts]'],
    '/var/log/btmp-20260301'    => ['type'=>'file','mtime'=>mktime(23,51,0,2,28,2026),'content'=>'[binary — rotated failed logins]'],
    '/var/log/cron'             => ['type'=>'file','mtime'=>mktime(13,53,0,3,12,2026),'content'=>
"Mar 12 00:00:01 $H crond[1512]: (root) CMD (/usr/local/bin/backup.sh >> /var/log/backup.log 2>&1)\nMar 12 00:05:01 $H crond[1512]: (root) CMD (/usr/local/bin/health-check.sh)\nMar 12 00:10:01 $H crond[1512]: (root) CMD (/usr/local/bin/health-check.sh)\nMar 12 02:00:01 $H crond[1512]: (root) CMD (/usr/local/bin/backup.sh)\nMar 12 06:25:01 $H crond[1512]: (root) CMD (run-parts /etc/cron.daily)\nMar 12 13:52:01 $H crond[1512]: (root) CMD (/usr/local/bin/health-check.sh)"],
    '/var/log/cron-20260215'    => ['type'=>'file','mtime'=>mktime(0,0,0,2,15,2026),'content'=>'[rotated cron log]'],
    '/var/log/cron-20260222'    => ['type'=>'file','mtime'=>mktime(23,59,0,2,21,2026),'content'=>'[rotated cron log]'],
    '/var/log/cron-20260301'    => ['type'=>'file','mtime'=>mktime(23,59,0,2,28,2026),'content'=>'[rotated cron log]'],
    '/var/log/cron-20260308'    => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated cron log]'],
    '/var/log/dnf.librepo.log'  => ['type'=>'file','mtime'=>mktime(13,26,0,3,12,2026),'content'=>"2026-03-12T13:26:01Z DEBUG librepo: checking metadata freshness\n2026-03-12T13:26:02Z DEBUG librepo: metadata up to date"],
    '/var/log/dnf.librepo.log.1'=> ['type'=>'file','mtime'=>mktime(14,23,0,3,1,2026), 'content'=>'[rotated dnf librepo log]'],
    '/var/log/dnf.librepo.log.2'=> ['type'=>'file','mtime'=>mktime(13,17,0,12,26,2025),'content'=>'[rotated dnf librepo log]'],
    '/var/log/dnf.librepo.log.3'=> ['type'=>'file','mtime'=>mktime(12,57,0,10,25,2025),'content'=>'[rotated dnf librepo log]'],
    '/var/log/dnf.librepo.log.4'=> ['type'=>'file','mtime'=>mktime(0,0,0,8,25,2025),  'content'=>'[rotated dnf librepo log]'],
    '/var/log/dnf.log'          => ['type'=>'file','mtime'=>mktime(13,26,0,3,12,2026),'content'=>
"2026-03-12T13:26:01Z DEBUG dnf: Running transaction check\n2026-03-12T13:26:02Z INFO dnf: Transaction complete\n2026-03-12T13:26:02Z DEBUG dnf: Cleaning up"],
    '/var/log/dnf.log.1'        => ['type'=>'file','mtime'=>mktime(15,29,0,3,3,2026), 'content'=>'[rotated dnf log]'],
    '/var/log/dnf.log.2'        => ['type'=>'file','mtime'=>mktime(17,59,0,2,1,2026), 'content'=>'[rotated dnf log]'],
    '/var/log/dnf.log.3'        => ['type'=>'file','mtime'=>mktime(18,14,0,1,2,2026), 'content'=>'[rotated dnf log]'],
    '/var/log/dnf.log.4'        => ['type'=>'file','mtime'=>mktime(11,50,0,12,1,2025),'content'=>'[rotated dnf log]'],
    '/var/log/dnf.rpm.log'      => ['type'=>'file','mtime'=>mktime(13,26,0,3,12,2026),'content'=>"2026-03-12T13:26:01Z INFO rpm: Upgrade: httpd-2.4.62-1.el9.x86_64\n2026-03-12T13:26:02Z INFO rpm: Upgrade: php-8.2.28-1.el9.x86_64"],
    '/var/log/dnf.rpm.log.1'    => ['type'=>'file','mtime'=>mktime(14,59,0,9,21,2025),'content'=>'[rotated dnf rpm log]'],
    '/var/log/firewalld'        => ['type'=>'file','mtime'=>mktime(0,0,0,12,14,2024),'content'=>
"2024-12-14 00:00:01 INFO  Running firewalld\n2024-12-14 00:00:01 INFO  Permanent and runtime config differ on zone public.\n2024-12-14 00:00:02 INFO  Firewall started"],
    '/var/log/hawkey.log'       => ['type'=>'file','mtime'=>mktime(13,26,0,3,12,2026),'content'=>"2026-03-12T13:26:01Z DEBUG hawkey: Downloading filelists for repo: baseos\n2026-03-12T13:26:02Z DEBUG hawkey: Sack: 12842 packages"],
    '/var/log/hawkey.log-20260215' => ['type'=>'file','mtime'=>mktime(23,15,0,2,14,2026),'content'=>'[rotated hawkey log]'],
    '/var/log/hawkey.log-20260222' => ['type'=>'file','mtime'=>mktime(23,33,0,2,21,2026),'content'=>'[rotated hawkey log]'],
    '/var/log/hawkey.log-20260301' => ['type'=>'file','mtime'=>mktime(23,17,0,2,28,2026),'content'=>'[rotated hawkey log]'],
    '/var/log/hawkey.log-20260308' => ['type'=>'file','mtime'=>mktime(20,13,0,3,7,2026), 'content'=>'[rotated hawkey log]'],
    '/var/log/kdump.log'        => ['type'=>'file','mtime'=>mktime(0,0,0,11,9,2024),'content'=>"kdump: No memory area to be reserved at system initialization.\nkdump: Disabled."],
    '/var/log/lastlog'          => ['type'=>'file','mtime'=>mktime(13,52,0,3,12,2026),'content'=>'[binary — last login records]'],
    '/var/log/lynis.log'        => ['type'=>'file','mtime'=>mktime(0,0,0,12,25,2024),'content'=>
"[2024-12-25 00:00:01] ====\n[2024-12-25 00:00:01] Lynis 3.0.9\n[2024-12-25 00:00:02] OS: AlmaLinux 9.7\n[2024-12-25 00:00:05] Hardening index: 74\n[2024-12-25 00:00:05] Tests performed: 263\n[2024-12-25 00:00:05] Warnings: 3\n[2024-12-25 00:00:05] Suggestions: 22"],
    '/var/log/lynis-report.dat' => ['type'=>'file','mtime'=>mktime(0,0,0,12,25,2024),'content'=>'[lynis report data]'],
    '/var/log/maillog'          => ['type'=>'file','mtime'=>mktime(12,46,0,3,12,2026),'content'=>
"Mar 12 00:00:01 $H postfix/pickup[1601]: message queued\nMar 12 06:25:02 $H postfix/smtp[1701]: connect to mail.example.com\nMar 12 12:46:01 $H postfix/qmgr[1602]: removed from queue"],
    '/var/log/maillog-20260215' => ['type'=>'file','mtime'=>mktime(20,35,0,2,14,2026),'content'=>'[rotated maillog]'],
    '/var/log/maillog-20260222' => ['type'=>'file','mtime'=>mktime(20,10,0,2,21,2026),'content'=>'[rotated maillog]'],
    '/var/log/maillog-20260301' => ['type'=>'file','mtime'=>mktime(20,10,0,2,28,2026),'content'=>'[rotated maillog]'],
    '/var/log/maillog-20260308' => ['type'=>'file','mtime'=>mktime(20,10,0,3,7,2026), 'content'=>'[rotated maillog]'],
    '/var/log/messages'         => ['type'=>'file','mtime'=>mktime(13,53,0,3,12,2026),'content'=>
"Mar 12 00:00:01 $H systemd[1]: Starting Daily Cleanup of Temporary Directories...\nMar 12 02:00:02 $H kernel: XFS (sda1): Unmounting Filesystem\nMar 12 06:25:01 $H systemd[1]: logrotate.service: Succeeded.\nMar 12 08:14:05 $H sshd[2201]: Accepted publickey for deploy from 192.168.1.42 port 54821\nMar 12 09:02:10 $H sshd[2211]: Invalid user admin from 185.220.101.45\nMar 12 13:15:01 $H systemd[1]: Starting dnf makecache..."],
    '/var/log/messages-20260215'=> ['type'=>'file','mtime'=>mktime(0,0,0,2,15,2026),'content'=>'[rotated messages log]'],
    '/var/log/messages-20260222'=> ['type'=>'file','mtime'=>mktime(23,59,0,2,21,2026),'content'=>'[rotated messages log]'],
    '/var/log/messages-20260301'=> ['type'=>'file','mtime'=>mktime(23,59,0,2,28,2026),'content'=>'[rotated messages log]'],
    '/var/log/messages-20260308'=> ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated messages log]'],
    '/var/log/README'           => ['type'=>'file','mtime'=>mktime(0,0,0,7,17,2023),'content'=>'See /usr/share/doc/systemd/README.logs for log file documentation.'],
    '/var/log/secure'           => ['type'=>'file','mtime'=>mktime(13,53,0,3,12,2026),'content'=>
"Mar 12 00:00:01 $H sshd[914]: Server listening on 0.0.0.0 port 22.\nMar 12 08:14:05 $H sshd[2201]: Accepted publickey for deploy from 192.168.1.42 port 54821 ssh2\nMar 12 09:02:10 $H sshd[2211]: Invalid user admin from 185.220.101.45 port 39812\nMar 12 09:02:11 $H sshd[2211]: Failed password for invalid user admin from 185.220.101.45 port 39812\nMar 12 09:02:12 $H sshd[2212]: Invalid user root from 185.220.101.45 port 39813\nMar 12 09:02:13 $H sshd[2212]: Failed password for invalid user root from 185.220.101.45 port 39813\nMar 12 09:02:14 $H sshd[2213]: Disconnecting invalid user root 185.220.101.45: Too many authentication failures\nMar 12 10:31:05 $H sshd[2301]: Accepted publickey for deploy from 10.0.0.5 port 51234 ssh2\nMar 12 13:22:01 $H sudo[2401]: root : TTY=pts/0 ; PWD=/root ; USER=root ; COMMAND=/usr/bin/systemctl restart httpd"],
    '/var/log/secure-20260215'  => ['type'=>'file','mtime'=>mktime(23,58,0,2,14,2026),'content'=>'[rotated secure log]'],
    '/var/log/secure-20260222'  => ['type'=>'file','mtime'=>mktime(23,59,0,2,21,2026),'content'=>'[rotated secure log]'],
    '/var/log/secure-20260301'  => ['type'=>'file','mtime'=>mktime(23,59,0,2,28,2026),'content'=>'[rotated secure log]'],
    '/var/log/secure-20260308'  => ['type'=>'file','mtime'=>mktime(23,59,0,3,7,2026), 'content'=>'[rotated secure log]'],
    '/var/log/spooler'          => ['type'=>'file','mtime'=>mktime(0,0,0,3,8,2026),'content'=>''],
    '/var/log/spooler-20260215' => ['type'=>'file','mtime'=>mktime(0,0,0,2,8,2026),'content'=>''],
    '/var/log/spooler-20260222' => ['type'=>'file','mtime'=>mktime(0,0,0,2,15,2026),'content'=>''],
    '/var/log/spooler-20260301' => ['type'=>'file','mtime'=>mktime(0,0,0,2,22,2026),'content'=>''],
    '/var/log/spooler-20260308' => ['type'=>'file','mtime'=>mktime(0,0,0,3,1,2026),'content'=>''],
    '/var/log/tallylog'         => ['type'=>'file','mtime'=>mktime(0,0,0,7,17,2023),'content'=>'[binary — login failure counts]'],
    '/var/log/wtmp'             => ['type'=>'file','mtime'=>mktime(13,52,0,3,12,2026),'content'=>'[binary — login/logout records]'],
    '/var/log/wtmp-20260211'    => ['type'=>'file','mtime'=>mktime(22,37,0,2,10,2026),'content'=>'[binary — rotated wtmp]'],
    '/var/log/xferlog'          => ['type'=>'file','mtime'=>mktime(0,0,0,3,6,2024),'content'=>''],

    // ── /var/log/mariadb ──
    '/var/log/mariadb/mariadb.log' => ['type'=>'file','mtime'=>mktime(0,0,0,3,12,2026),'content'=>
"2026-03-12  0:00:01 0 [Note] /usr/sbin/mariadbd: ready for connections.\n2026-03-12  0:00:01 0 [Note] mysqld: Startup complete\n2026-03-12 14:22:09 42 [Warning] Access denied for user 'root'@'45.33.32.156'"],

    '/var/log/backup.log'       => ['type'=>'file','mtime'=>mktime(2,4,0,3,12,2026),'content'=>
"[2026-03-10 02:00:01] Starting nightly backup\n[2026-03-10 02:00:02] Dumping MariaDB databases...\n[2026-03-10 02:02:44] MariaDB dump complete: 1.8GB\n[2026-03-10 02:02:45] Syncing /var/www to backup...\n[2026-03-10 02:03:12] Sync complete: 842MB\n[2026-03-10 02:04:33] Done. Total: 4.2GB written to /mnt/backup/daily/db-2026-03-10.sql.gz\n[2026-03-11 02:00:01] Starting nightly backup\n[2026-03-11 02:04:41] Done. Total: 4.2GB written to /mnt/backup/daily/db-2026-03-11.sql.gz\n[2026-03-12 02:00:01] Starting nightly backup\n[2026-03-12 02:04:38] Done. Total: 4.3GB written to /mnt/backup/daily/db-2026-03-12.sql.gz"],

    '/var/www/html/index.php'       => ['type'=>'file','content'=>
"<?php\n// Main entry point\nrequire_once 'config.php';\nrequire_once 'vendor/autoload.php';\n\n\$app = new App\\Application();\n\$app->run();"],
    '/var/www/html/config.php'      => ['type'=>'file','content'=>
"<?php\ndefine('DB_HOST', '127.0.0.1');\ndefine('DB_NAME', 'production');\ndefine('DB_USER', 'webapp');\ndefine('DB_PASS', 'S3cur3P@ssw0rd!');\ndefine('APP_ENV', 'production');\ndefine('APP_DEBUG', false);\ndefine('APP_KEY', 'base64:k3yV4lu3H3r3==');"],
    '/var/www/html/.htaccess'       => ['type'=>'file','content'=>
"RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php [QSA,L]\n\n# Security\n<FilesMatch \"^\\.(env|git|htaccess)\">\n    Require all denied\n</FilesMatch>"],
    '/var/www/html/composer.json'   => ['type'=>'file','content'=>
"{\n    \"name\": \"app/webapp\",\n    \"require\": {\n        \"php\": \"^8.2\",\n        \"ext-pdo\": \"*\",\n        \"monolog/monolog\": \"^3.0\",\n        \"symfony/routing\": \"^6.0\",\n        \"twig/twig\": \"^3.0\"\n    },\n    \"autoload\": {\n        \"psr-4\": { \"App\\\\\\\\\": \"src/\" }\n    }\n}"],
    '/var/www/html/robots.txt'      => ['type'=>'file','content'=>"User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /config.php\nSitemap: https://$H/sitemap.xml"],

    '/var/spool/cron/crontabs/root' => ['type'=>'file','content'=>
"# Root crontab\n0 2 * * * /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1\n*/5 * * * * /usr/local/bin/health-check.sh\n0 3 * * 0 /usr/local/bin/weekly-report.sh\n@reboot /usr/local/bin/on-boot.sh"],

    // ──────────────────────────────────────────────────────────
    //  /usr
    // ──────────────────────────────────────────────────────────
    '/usr/local'            => ['type'=>'dir'],
    '/usr/local/bin'        => ['type'=>'dir'],
    '/usr/local/sbin'       => ['type'=>'dir'],
    '/usr/bin'              => ['type'=>'dir'],
    '/usr/sbin'             => ['type'=>'dir'],
    '/usr/share'            => ['type'=>'dir'],
    '/usr/share/doc'        => ['type'=>'dir'],
    '/usr/lib'              => ['type'=>'dir'],
    '/usr/lib/systemd'      => ['type'=>'dir'],
    '/usr/lib/systemd/system' => ['type'=>'dir'],

    '/usr/local/bin/backup.sh'         => ['type'=>'file','content'=>
"#!/bin/bash\n# Nightly backup script\nset -e\nDATE=\$(date +%Y-%m-%d)\nBACKUP_DIR=/mnt/backup/daily\nMYSQL_USER=root\nMYSQL_PASS=\$(cat /root/.mysql_secret)\n\necho \"[\$(date)] Starting nightly backup\"\n\n# Dump MySQL\nmysqldump -u\$MYSQL_USER -p\$MYSQL_PASS --all-databases | gzip > \$BACKUP_DIR/db-\$DATE.sql.gz\necho \"[\$(date)] MySQL dump complete\"\n\n# Sync web files\nrsync -avz --delete /var/www/ backup-server:/mnt/backup/www/\necho \"[\$(date)] Web files synced\"\n\n# Remove backups older than 14 days\nfind \$BACKUP_DIR -name '*.sql.gz' -mtime +14 -delete\necho \"[\$(date)] Old backups pruned\"\necho \"[\$(date)] Backup complete\""],
    '/usr/local/bin/health-check.sh'   => ['type'=>'file','content'=>
"#!/bin/bash\n# Health check — runs every 5 minutes via cron\nLOG=/var/log/health.log\n\ncheck_service() {\n    if ! systemctl is-active --quiet \$1; then\n        echo \"[\$(date)] WARNING: \$1 is down, restarting...\" >> \$LOG\n        systemctl restart \$1\n    fi\n}\n\ncheck_service httpd\ncheck_service mysqld\ncheck_service php-fpm\n\n# Check disk usage\nDISK=\$(df / | tail -1 | awk '{print \$5}' | tr -d '%')\nif [ \$DISK -gt 85 ]; then\n    echo \"[\$(date)] WARNING: Disk usage at \${DISK}%\" >> \$LOG\nfi"],
    '/usr/local/bin/weekly-report.sh'  => ['type'=>'file','content'=>
"#!/bin/bash\n# Weekly report — emails server stats to admin\nREPORT=\$(mktemp)\necho \"Weekly Server Report - \$(date)\" >> \$REPORT\necho \"\" >> \$REPORT\necho \"Uptime: \$(uptime)\" >> \$REPORT\necho \"Disk usage:\" >> \$REPORT\ndf -h >> \$REPORT\necho \"\" >> \$REPORT\necho \"Memory:\" >> \$REPORT\nfree -h >> \$REPORT\nmail -s \"Weekly Report \$(date +%Y-%m-%d)\" admin@$H < \$REPORT\nrm \$REPORT"],

    '/usr/lib/systemd/system/httpd.service' => ['type'=>'file','content'=>
"[Unit]\nDescription=The Apache HTTP Server\nAfter=network.target mysqld.service\n\n[Service]\nType=forking\nExecStart=/usr/sbin/apachectl start\nExecReload=/usr/sbin/apachectl graceful\nExecStop=/usr/sbin/apachectl stop\nUser=root\nPrivateTmp=true\n\n[Install]\nWantedBy=multi-user.target"],
    '/usr/lib/systemd/system/mysqld.service' => ['type'=>'file','content'=>
"[Unit]\nDescription=MySQL Community Server\nAfter=network.target\n\n[Service]\nType=forking\nUser=mysql\nExecStart=/usr/sbin/mysqld --daemonize\nExecStop=/usr/bin/mysqladmin shutdown\nTimeoutSec=600\n\n[Install]\nWantedBy=multi-user.target"],
    '/usr/lib/systemd/system/sshd.service'   => ['type'=>'file','content'=>
"[Unit]\nDescription=OpenSSH server daemon\nAfter=network.target\n\n[Service]\nType=forking\nExecStart=/usr/sbin/sshd\nExecReload=/bin/kill -HUP \$MAINPID\nKillMode=process\n\n[Install]\nWantedBy=multi-user.target"],
    '/usr/lib/systemd/system/php-fpm.service' => ['type'=>'file','content'=>
"[Unit]\nDescription=PHP FastCGI Process Manager\nAfter=network.target\n\n[Service]\nType=notify\nExecStart=/usr/sbin/php-fpm --nodaemonize\nExecReload=/bin/kill -USR2 \$MAINPID\n\n[Install]\nWantedBy=multi-user.target"],

    // ──────────────────────────────────────────────────────────
    //  /tmp
    // ──────────────────────────────────────────────────────────
    '/tmp/php-upload-xK3m9p'   => ['type'=>'file','content'=>'[temporary PHP upload file]'],
    '/tmp/sess_a8f3c2d1e4b7'   => ['type'=>'file','content'=>'[PHP session data]'],
    '/tmp/.ICE-unix'            => ['type'=>'dir'],

    // ──────────────────────────────────────────────────────────
    //  /proc (read-only, a few key entries)
    // ──────────────────────────────────────────────────────────
    '/proc/version'     => ['type'=>'file','content'=>"Linux version $K (gcc version 11.4.1) #1 SMP"],
    '/proc/cpuinfo'     => ['type'=>'file','content'=>
"processor	: 0\nvendor_id	: GenuineIntel\ncpu family	: 6\nmodel name	: Intel(R) Xeon(R) E5-2670 @ 2.60GHz\ncpu MHz		: 2600.000\ncache size	: 20480 KB\ncpu cores	: 8\n\nprocessor	: 1\nmodel name	: Intel(R) Xeon(R) E5-2670 @ 2.60GHz\ncpu cores	: 8"],
    '/proc/meminfo'     => ['type'=>'file','content'=>
"MemTotal:       16252928 kB\nMemFree:         8847360 kB\nMemAvailable:   11534336 kB\nBuffers:          524288 kB\nCached:          3604480 kB\nSwapTotal:       2097152 kB\nSwapFree:        2097152 kB"],
    '/proc/uptime'      => ['type'=>'file','content'=>'86401.12 341204.88'],
    '/proc/loadavg'     => ['type'=>'file','content'=>'0.36 0.52 0.40 2/412 2091'],

    // ──────────────────────────────────────────────────────────
    //  /mnt — storage volumes
    // ──────────────────────────────────────────────────────────
    '/mnt/db'               => ['type'=>'dir'],
    '/mnt/db/mysql'         => ['type'=>'dir'],
    '/mnt/db/mysql/production' => ['type'=>'dir'],
    '/mnt/db/redis'         => ['type'=>'dir'],
    '/mnt/backup'           => ['type'=>'dir'],
    '/mnt/backup/daily'     => ['type'=>'dir'],
    '/mnt/backup/weekly'    => ['type'=>'dir'],
    '/mnt/backup/config'    => ['type'=>'dir'],

    '/mnt/db/mysql/production/users.ibd'    => ['type'=>'file','content'=>'[InnoDB tablespace data]'],
    '/mnt/db/mysql/production/orders.ibd'   => ['type'=>'file','content'=>'[InnoDB tablespace data]'],
    '/mnt/db/mysql/production/payments.ibd' => ['type'=>'file','content'=>'[InnoDB tablespace data]'],
    '/mnt/db/mysql/ibdata1'                 => ['type'=>'file','content'=>'[InnoDB shared tablespace]'],
    '/mnt/db/redis/dump.rdb'                => ['type'=>'file','content'=>'[Redis RDB snapshot]'],

    '/mnt/backup/daily/db-2026-03-08.sql.gz'  => ['type'=>'file','content'=>'[compressed MySQL dump]'],
    '/mnt/backup/daily/db-2026-03-09.sql.gz'  => ['type'=>'file','content'=>'[compressed MySQL dump]'],
    '/mnt/backup/daily/db-2026-03-10.sql.gz'  => ['type'=>'file','content'=>'[compressed MySQL dump]'],
    '/mnt/backup/weekly/full-2026-03-01.tar.gz' => ['type'=>'file','content'=>'[compressed full backup]'],
    '/mnt/backup/weekly/full-2026-02-22.tar.gz' => ['type'=>'file','content'=>'[compressed full backup]'],
    '/mnt/backup/config/etc-2026-03-09.tar.gz'  => ['type'=>'file','content'=>'[compressed /etc backup]'],

    ]; // end return
}
