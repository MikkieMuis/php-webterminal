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
    //  /etc
    // ──────────────────────────────────────────────────────────
    '/etc/ssh'              => ['type'=>'dir'],
    '/etc/cron.d'           => ['type'=>'dir'],
    '/etc/cron.daily'       => ['type'=>'dir'],
    '/etc/cron.weekly'      => ['type'=>'dir'],
    '/etc/php.d'            => ['type'=>'dir'],
    '/etc/httpd'            => ['type'=>'dir'],
    '/etc/httpd/conf'       => ['type'=>'dir'],
    '/etc/httpd/conf.d'     => ['type'=>'dir'],
    '/etc/mysql'            => ['type'=>'dir'],
    '/etc/logrotate.d'      => ['type'=>'dir'],
    '/etc/sysconfig'        => ['type'=>'dir'],
    '/etc/profile.d'        => ['type'=>'dir'],
    '/etc/sudoers.d'        => ['type'=>'dir'],
    '/etc/pki'              => ['type'=>'dir'],
    '/etc/pki/tls'          => ['type'=>'dir'],
    '/etc/pki/tls/certs'    => ['type'=>'dir'],
    '/etc/pki/tls/private'  => ['type'=>'dir'],

    '/etc/hostname'     => ['type'=>'file','content'=>$H],
    '/etc/os-release'   => ['type'=>'file','content'=>
        "NAME=\"AlmaLinux\"\nVERSION=\"9.7 (Seafoam Ocelot)\"\nID=almalinux\nID_LIKE=\"rhel fedora\"\nVERSION_ID=\"9.7\"\nPLATFORM_ID=\"platform:el9\"\nPRETTY_NAME=\"AlmaLinux 9.7 (Seafoam Ocelot)\"\nANSI_COLOR=\"0;34\"\nLOGO=\"fedora-logo-icon\"\nCPE_NAME=\"cpe:/o:almalinux:almalinux:9::baseos\"\nHOME_URL=\"https://almalinux.org/\"\nBUG_REPORT_URL=\"https://bugs.almalinux.org/\""],
    '/etc/passwd'       => ['type'=>'file','content'=>
"root:x:0:0:root:/root:/bin/bash\ndaemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin\nbin:x:2:2:bin:/bin:/usr/sbin/nologin\nsys:x:3:3:sys:/dev:/usr/sbin/nologin\nsync:x:4:65534:sync:/bin:/bin/sync\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin\nmysql:x:102:105:MySQL Server,,,:/var/lib/mysql:/bin/false\ndeploy:x:1001:1001:Deploy User:/home/deploy:/bin/bash\nnobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin"],
    '/etc/shadow'       => ['type'=>'file','content'=>
"root:\$6\$rounds=5000\$salt\$hashedpassword:19500:0:99999:7:::\ndeploy:\$6\$rounds=5000\$salt\$hashedpassword:19500:0:99999:7:::"],
    '/etc/group'        => ['type'=>'file','content'=>
"root:x:0:\ndaemon:x:1:\nbin:x:2:\nsys:x:3:\nwww-data:x:33:\nmysql:x:105:\ndeploy:x:1001:\ndocker:x:999:deploy"],
    '/etc/hosts'        => ['type'=>'file','content'=>
"127.0.0.1   localhost\n127.0.1.1   $H\n::1         localhost ip6-localhost ip6-loopback\n192.168.1.10  $H\n192.168.1.20  db-server\n192.168.1.30  backup-server"],
    '/etc/fstab'        => ['type'=>'file','content'=>
"# <file system>  <mount point>  <type>  <options>        <dump>  <pass>\n/dev/sda1        /              ext4    errors=remount-ro 0       1\n/dev/sda2        /boot          ext4    defaults          0       2\n/dev/sdb1        /mnt/db        ext4    defaults,noatime  0       2\n/dev/sdc1        /mnt/backup    ext4    defaults,noatime  0       2\n/dev/sdd1        /home          ext4    defaults          0       2\ntmpfs            /tmp           tmpfs   defaults,nosuid   0       0\ntmpfs            /dev/shm       tmpfs   defaults          0       0"],
    '/etc/crontab'      => ['type'=>'file','content'=>
"SHELL=/bin/bash\nPATH=/sbin:/bin:/usr/sbin:/usr/bin\n\n# .---------------- minute (0-59)\n# |  .------------- hour (0-23)\n# |  |  .---------- day of month (1-31)\n# |  |  |  .------- month (1-12)\n# |  |  |  |  .---- day of week (0-6)\n# m  h dom mon dow  user  command\n  0  2  *   *   *   root  /usr/local/bin/backup.sh\n  */5 * *   *   *   root  /usr/local/bin/health-check.sh\n 30  3  *   *   0   root  /usr/local/bin/weekly-report.sh\n  0  0  1   *   *   root  /usr/local/bin/monthly-cleanup.sh"],
    '/etc/sudoers'      => ['type'=>'file','content'=>
"# /etc/sudoers\nDefaults    env_reset\nDefaults    mail_badpass\nDefaults    secure_path=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"\n\nroot    ALL=(ALL:ALL) ALL\ndeploy  ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart apache2, /usr/bin/systemctl restart php-fpm"],
    '/etc/environment'  => ['type'=>'file','content'=>
"PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"\nLANG=en_US.UTF-8\nLC_ALL=en_US.UTF-8"],
    '/etc/timezone'     => ['type'=>'file','content'=>'Europe/Amsterdam'],
    '/etc/resolv.conf'  => ['type'=>'file','content'=>
"# Generated by NetworkManager\nnameserver 8.8.8.8\nnameserver 8.8.4.4\nsearch $H.local"],
    '/etc/motd'         => ['type'=>'file','content'=>
"\nWelcome to $H\n\nAlmaLinux 9.7 — Kernel $K\nAll access is logged and monitored.\nUnauthorised use is strictly prohibited.\n"],
    '/etc/issue'        => ['type'=>'file','content'=>"AlmaLinux 9.7 (Seafoam Ocelot)\nKernel \\r on an \\m\n"],
    '/etc/shells'       => ['type'=>'file','content'=>"/bin/sh\n/bin/bash\n/bin/dash\n/usr/bin/bash\n/usr/bin/sh"],
    '/etc/locale.conf'  => ['type'=>'file','content'=>"LANG=en_US.UTF-8\nLC_TIME=en_GB.UTF-8"],

    '/etc/ssh/sshd_config' => ['type'=>'file','content'=>
"Port 22\nAddressFamily any\nListenAddress 0.0.0.0\nPermitRootLogin prohibit-password\nPubkeyAuthentication yes\nPasswordAuthentication no\nPermitEmptyPasswords no\nChallengeResponseAuthentication no\nUsePAM yes\nX11Forwarding no\nPrintMotd yes\nAcceptEnv LANG LC_*\nSubsystem sftp /usr/lib/openssh/sftp-server\nAllowUsers root deploy\nMaxAuthTries 3\nClientAliveInterval 300\nClientAliveCountMax 2"],
    '/etc/ssh/ssh_host_rsa_key' => ['type'=>'file','content'=>'-----BEGIN OPENSSH PRIVATE KEY-----\n[private key — not readable]\n-----END OPENSSH PRIVATE KEY-----'],

    '/etc/httpd/conf/httpd.conf' => ['type'=>'file','content'=>
"ServerRoot \"/etc/httpd\"\nListen 80\nListen 443\nServerName $H\nServerAdmin webmaster@$H\nDocumentRoot \"/var/www/html\"\nDirectoryIndex index.php index.html\nErrorLog \"/var/log/httpd/error.log\"\nCustomLog \"/var/log/httpd/access.log\" combined\nKeepAlive On\nMaxKeepAliveRequests 100\nKeepAliveTimeout 5"],
    '/etc/httpd/conf.d/ssl.conf' => ['type'=>'file','content'=>
"<VirtualHost *:443>\n    ServerName $H\n    SSLEngine on\n    SSLCertificateFile /etc/pki/tls/certs/server.crt\n    SSLCertificateKeyFile /etc/pki/tls/private/server.key\n    DocumentRoot /var/www/html\n</VirtualHost>"],

    '/etc/mysql/my.cnf' => ['type'=>'file','content'=>
"[mysqld]\nuser            = mysql\npid-file        = /var/run/mysqld/mysqld.pid\nsocket          = /var/run/mysqld/mysqld.sock\nport            = 3306\ndatadir         = /mnt/db/mysql\nbind-address    = 127.0.0.1\nmax_connections = 200\ninnodb_buffer_pool_size = 4G\ninnodb_log_file_size    = 512M\nslow_query_log  = 1\nslow_query_log_file = /var/log/mysql/slow.log\nlong_query_time = 2\n\n[mysqldump]\nquick\nmax_allowed_packet = 64M"],

    '/etc/logrotate.d/httpd' => ['type'=>'file','content'=>
"/var/log/httpd/*.log {\n    daily\n    missingok\n    rotate 14\n    compress\n    delaycompress\n    notifempty\n    sharedscripts\n    postrotate\n        /bin/systemctl reload httpd > /dev/null 2>/dev/null || true\n    endscript\n}"],
    '/etc/logrotate.d/mysql'  => ['type'=>'file','content'=>
"/var/log/mysql/*.log {\n    daily\n    rotate 7\n    compress\n    missingok\n    notifempty\n    create 640 mysql adm\n}"],

    '/etc/cron.d/backup'      => ['type'=>'file','content'=>"0 2 * * * root /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1"],
    '/etc/cron.daily/logcheck' => ['type'=>'file','content'=>"#!/bin/bash\n/usr/sbin/logcheck"],
    '/etc/cron.weekly/fstrim'  => ['type'=>'file','content'=>"#!/bin/bash\nfstrim -av"],

    '/etc/profile.d/aliases.sh' => ['type'=>'file','content'=>
"alias ll='ls -la'\nalias la='ls -A'\nalias l='ls -CF'\nalias grep='grep --color=auto'\nalias df='df -h'\nalias free='free -h'"],

    '/etc/pki/tls/certs/server.crt' => ['type'=>'file','content'=>
"-----BEGIN CERTIFICATE-----\nMIIDXTCCAkWgAwIBAgIJALmCFxSqatp5MA0GCSqGSIb3DQEBCwUAMEUxCzAJBgNV\n[certificate data]\n-----END CERTIFICATE-----"],
    '/etc/pki/tls/private/server.key' => ['type'=>'file','content'=>
"-----BEGIN RSA PRIVATE KEY-----\n[private key — not readable]\n-----END RSA PRIVATE KEY-----"],

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
"git pull origin main\nsudo systemctl restart apache2\ntail -f /var/log/httpd/error.log\nmysql -u root -p\ndf -h\nfree -h\nps aux | grep apache\nssh backup-server\nrsync -avz /var/www/ backup-server:/mnt/backup/www/"],
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
"apt-get update\napt-get upgrade -y\ndf -h\nfree -h\nps aux\ntail -f /var/log/httpd/error.log\nmysql -u root -p\nsystemctl status httpd\nsystemctl restart httpd\ncertbot renew\ncrontab -l\nls -la /var/www/html\ncat /var/log/auth.log | grep Failed\nnetstat -tulanp\niptables -L -v\nuname -a\nuptime"],
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
"Server maintenance notes\n========================\n\nLast updated: March 2026\n\n- MySQL slow query log enabled, check weekly\n- SSL cert expires 2026-09-14, renew with: certbot renew\n- Backup job runs nightly at 02:00, check /var/log/backup.log\n- Deploy user has passwordless sudo for apache2 + php-fpm restarts only\n- Firewall: only 22, 80, 443 open externally\n- /mnt/db is on separate SSD, do NOT fill above 80%\n- See /usr/local/bin/ for all maintenance scripts"],
    '/root/server-setup.sh'      => ['type'=>'file','content'=>
"#!/bin/bash\n# Initial server setup script\n# Run once as root\n\nset -e\n\napt-get update && apt-get upgrade -y\napt-get install -y apache2 mysql-server php8.2 php8.2-mysql \\\n    php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml \\\n    certbot python3-certbot-apache fail2ban ufw git composer\n\n# Firewall\nufw allow 22/tcp\nufw allow 80/tcp\nufw allow 443/tcp\nufw --force enable\n\n# MySQL hardening\nmysql_secure_installation\n\n# Create deploy user\nuseradd -m -s /bin/bash deploy\nmkdir -p /home/deploy/.ssh\nchmod 700 /home/deploy/.ssh\n\necho \"Setup complete.\""],

    // ──────────────────────────────────────────────────────────
    //  /var
    // ──────────────────────────────────────────────────────────
    '/var/log'              => ['type'=>'dir'],
    '/var/log/httpd'        => ['type'=>'dir'],
    '/var/log/mysql'        => ['type'=>'dir'],
    '/var/log/php-fpm'      => ['type'=>'dir'],
    '/var/www'              => ['type'=>'dir'],
    '/var/www/html'         => ['type'=>'dir'],
    '/var/spool'            => ['type'=>'dir'],
    '/var/spool/cron'       => ['type'=>'dir'],
    '/var/spool/cron/crontabs' => ['type'=>'dir'],
    '/var/run'              => ['type'=>'dir'],
    '/var/cache'            => ['type'=>'dir'],
    '/var/lib'              => ['type'=>'dir'],
    '/var/lib/mysql'        => ['type'=>'dir'],

    '/var/log/httpd/access.log' => ['type'=>'file','content'=>
"192.168.1.42 - - [10/Mar/2026:08:14:22 +0100] \"GET / HTTP/1.1\" 200 4823 \"-\" \"Mozilla/5.0\"\n192.168.1.42 - - [10/Mar/2026:08:14:23 +0100] \"GET /css/style.css HTTP/1.1\" 200 1842\n185.220.101.45 - - [10/Mar/2026:09:02:11 +0100] \"GET /wp-admin/ HTTP/1.1\" 404 512\n185.220.101.45 - - [10/Mar/2026:09:02:12 +0100] \"POST /xmlrpc.php HTTP/1.1\" 404 512\n10.0.0.5 - deploy [10/Mar/2026:10:31:07 +0100] \"GET /api/status HTTP/1.1\" 200 128\n93.184.216.34 - - [10/Mar/2026:11:15:44 +0100] \"GET /index.php HTTP/1.1\" 200 9241\n192.168.1.42 - - [10/Mar/2026:14:22:09 +0100] \"POST /api/login HTTP/1.1\" 200 312\n192.168.1.42 - - [10/Mar/2026:14:22:11 +0100] \"GET /dashboard HTTP/1.1\" 200 18432\n45.33.32.156 - - [10/Mar/2026:15:01:33 +0100] \"GET /etc/passwd HTTP/1.1\" 404 512\n45.33.32.156 - - [10/Mar/2026:15:01:34 +0100] \"GET /.env HTTP/1.1\" 404 512"],
    '/var/log/httpd/error.log'  => ['type'=>'file','content'=>
"[Mon Mar 09 02:14:11.842310 2026] [mpm_prefork:notice] [pid 1105] AH00163: Apache/2.4.62 configured\n[Mon Mar 09 02:14:11.843201 2026] [core:notice] [pid 1105] AH00094: Command line: '/usr/sbin/apache2'\n[Tue Mar 10 03:12:44.112233 2026] [php:error] [pid 2241] PHP Warning: include(/var/www/html/config.php): failed to open stream in /var/www/html/index.php on line 14\n[Tue Mar 10 08:02:11.334455 2026] [authz_core:error] [pid 2244] AH01630: client denied by server configuration: /var/www/html/.env\n[Tue Mar 10 09:02:12.556677 2026] [authz_core:error] [pid 2244] AH01630: client denied by server configuration: /var/www/html/xmlrpc.php"],
    '/var/log/mysql/error.log'  => ['type'=>'file','content'=>
"2026-03-09T02:00:01.123456Z 0 [System] [MY-010116] [Server] /usr/sbin/mysqld starting\n2026-03-09T02:00:01.234567Z 1 [System] [MY-013576] [InnoDB] InnoDB initialization has started.\n2026-03-09T02:00:02.345678Z 1 [System] [MY-013577] [InnoDB] InnoDB initialization has ended.\n2026-03-09T02:00:02.456789Z 0 [System] [MY-010229] [Server] Starting XA crash recovery.\n2026-03-09T02:00:02.567890Z 0 [System] [MY-010031] [Server] /usr/sbin/mysqld: ready for connections.\n2026-03-10T14:22:09.678901Z 42 [Warning] [MY-010055] [Server] IP address '45.33.32.156' could not be resolved"],
    '/var/log/mysql/slow.log'   => ['type'=>'file','content'=>
"# Time: 2026-03-10T03:15:22.123456Z\n# User@Host: webapp[webapp] @ localhost [127.0.0.1]\n# Query_time: 3.441  Lock_time: 0.000  Rows_sent: 1  Rows_examined: 184291\nSELECT * FROM orders WHERE status='pending' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);\n\n# Time: 2026-03-10T11:42:07.654321Z\n# Query_time: 5.112  Lock_time: 0.001  Rows_sent: 842  Rows_examined: 1204891\nSELECT u.*, COUNT(o.id) as order_count FROM users u LEFT JOIN orders o ON u.id = o.user_id GROUP BY u.id;"],
    '/var/log/auth.log'         => ['type'=>'file','content'=>
"Mar 10 00:00:01 $H sshd[914]: Server listening on 0.0.0.0 port 22.\nMar 10 08:14:05 $H sshd[2201]: Accepted publickey for deploy from 192.168.1.42 port 54821 ssh2\nMar 10 09:02:10 $H sshd[2211]: Invalid user admin from 185.220.101.45 port 39812\nMar 10 09:02:11 $H sshd[2211]: Failed password for invalid user admin from 185.220.101.45 port 39812\nMar 10 09:02:12 $H sshd[2212]: Invalid user root from 185.220.101.45 port 39813\nMar 10 09:02:13 $H sshd[2212]: Failed password for invalid user root from 185.220.101.45 port 39813\nMar 10 09:02:14 $H sshd[2213]: Disconnecting invalid user root 185.220.101.45 port 39813: Too many authentication failures\nMar 10 10:31:05 $H sshd[2301]: Accepted publickey for deploy from 10.0.0.5 port 51234 ssh2\nMar 10 14:22:01 $H sudo[2401]: root : TTY=pts/0 ; PWD=/root ; USER=root ; COMMAND=/usr/bin/systemctl restart httpd"],
    '/var/log/syslog'           => ['type'=>'file','content'=>
"Mar 10 00:00:01 $H systemd[1]: Starting Daily apt download activities...\nMar 10 00:00:02 $H cron[2048]: (root) CMD (/usr/local/bin/health-check.sh)\nMar 10 02:00:01 $H cron[2048]: (root) CMD (/usr/local/bin/backup.sh)\nMar 10 02:00:01 $H backup[3101]: Starting backup...\nMar 10 02:04:33 $H backup[3101]: Backup complete. 4.2GB written to /mnt/backup/daily/\nMar 10 03:12:44 $H kernel: [123456.789] EXT4-fs error (device sda1): ext4_find_entry:1455\nMar 10 06:17:02 $H systemd[1]: logrotate.service: Succeeded.\nMar 10 08:14:05 $H sshd[2201]: Accepted publickey for deploy\nMar 10 09:02:10 $H sshd[2211]: Invalid user admin from 185.220.101.45\nMar 10 10:31:05 $H sshd[2301]: Accepted publickey for deploy"],
    '/var/log/backup.log'       => ['type'=>'file','content'=>
"[2026-03-08 02:00:01] Starting nightly backup\n[2026-03-08 02:00:02] Dumping MySQL databases...\n[2026-03-08 02:02:44] MySQL dump complete: 1.8GB\n[2026-03-08 02:02:45] Syncing /var/www to backup...\n[2026-03-08 02:03:12] Sync complete: 842MB\n[2026-03-08 02:03:13] Compressing...\n[2026-03-08 02:04:33] Done. Total: 4.2GB written to /mnt/backup/daily/db-2026-03-08.sql.gz\n[2026-03-09 02:00:01] Starting nightly backup\n[2026-03-09 02:04:41] Done. Total: 4.2GB written to /mnt/backup/daily/db-2026-03-09.sql.gz\n[2026-03-10 02:00:01] Starting nightly backup\n[2026-03-10 02:04:38] Done. Total: 4.3GB written to /mnt/backup/daily/db-2026-03-10.sql.gz"],

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
