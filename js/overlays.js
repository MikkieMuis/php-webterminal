// overlays.js — interactive prompt overlays: rm -rf easter egg, sudo, su, passwd
// Depends on globals: mode, masked, loginUser, cwd, sysKernel,
//                     typed, cursorPos, handleEnter, termCols,
//                     print, hidePrompt, setPrompt, updateTitleAndPrompt,
//                     renderLine, handleResponse, curline, scr

// easter egg
function doRmRf() {
  hidePrompt();
  var files = [
    // core binaries
    '/bin/bash','/bin/sh','/bin/ls','/bin/cp','/bin/mv','/bin/rm',
    '/bin/cat','/bin/chmod','/bin/chown','/bin/kill','/bin/ps',
    '/usr/bin/python3','/usr/bin/perl','/usr/bin/php',
    '/usr/bin/wget','/usr/bin/curl','/usr/bin/ssh','/usr/bin/scp',
    '/usr/sbin/apache2','/usr/sbin/nginx','/usr/sbin/sshd',
    '/usr/sbin/cron','/usr/sbin/rsyslogd',

    // boot & kernel
    '/boot/vmlinuz-' + sysKernel,
    '/boot/initrd.img-' + sysKernel,
    '/boot/grub/grub.cfg',
    '/boot/grub/i386-pc/core.img',

    // system config
    '/etc/passwd','/etc/shadow','/etc/group','/etc/sudoers',
    '/etc/hosts','/etc/hostname','/etc/fstab','/etc/crontab',
    '/etc/ssh/sshd_config','/etc/ssh/ssh_host_rsa_key',
    '/etc/ssl/private/server.key','/etc/ssl/certs/server.crt',
    '/etc/apache2/apache2.conf','/etc/nginx/nginx.conf',
    '/etc/my.cnf','/etc/php.ini',

    // disk 1 — web & app data (/dev/sda)
    '/var/www/html/index.php','/var/www/html/wp-config.php',
    '/var/www/html/wp-content/uploads/2025/01/backup.tar.gz',
    '/var/www/html/app/config/database.yml',
    '/var/www/html/app/config/secrets.yml',
    '/var/log/apache2/access.log','/var/log/apache2/error.log',
    '/var/log/nginx/access.log','/var/log/auth.log',
    '/var/log/syslog','/var/log/kern.log',
    '/var/spool/cron/crontabs/root',

    // disk 2 — database (/dev/sdb)
    '/mnt/db/mysql/ibdata1','/mnt/db/mysql/ib_logfile0',
    '/mnt/db/mysql/ib_logfile1',
    '/mnt/db/mysql/production/users.ibd',
    '/mnt/db/mysql/production/orders.ibd',
    '/mnt/db/mysql/production/payments.ibd',
    '/mnt/db/mysql/production/sessions.ibd',
    '/mnt/db/mysql/binlog.000042','/mnt/db/mysql/binlog.000043',
    '/mnt/db/postgres/base/16384/PG_VERSION',
    '/mnt/db/postgres/global/pg_control',
    '/mnt/db/redis/dump.rdb',

    // disk 3 — backups (/dev/sdc)
    '/mnt/backup/daily/db-2026-03-09.sql.gz',
    '/mnt/backup/daily/db-2026-03-08.sql.gz',
    '/mnt/backup/daily/db-2026-03-07.sql.gz',
    '/mnt/backup/weekly/full-2026-03-01.tar.gz',
    '/mnt/backup/weekly/full-2026-02-22.tar.gz',
    '/mnt/backup/config/etc-2026-03-09.tar.gz',
    '/mnt/backup/offsite/.credentials',

    // disk 4 — user data & home (/dev/sdd)
    '/home/deploy/.ssh/authorized_keys',
    '/home/deploy/.ssh/id_rsa',
    '/home/deploy/.bash_history',
    '/root/.ssh/authorized_keys','/root/.ssh/id_rsa',
    '/root/.bash_history','/root/.aws/credentials',
    '/root/.docker/config.json',

    // systemd & init
    '/lib/systemd/systemd',
    '/lib/systemd/system/apache2.service',
    '/lib/systemd/system/mysql.service',
    '/lib/systemd/system/sshd.service',
    '/lib/systemd/system/cron.service',
  ];

  var i = 0;
  function next() {
    if (i >= files.length) {
      print('', 'n');
      print("rm: cannot remove '/proc/kcore': Operation not permitted", 'w');
      print("rm: cannot remove '/proc/sysrq-trigger': Operation not permitted", 'w');
      print("rm: cannot remove '/sys/kernel/security': Operation not permitted", 'w');
      print('', 'n');
      print('*** KERNEL PANIC - not syncing: Attempted to kill init! ***', 'e');
      print('*** CPU: 0 PID: 1 Comm: systemd Tainted: G D ' + sysKernel + ' ***', 'e');
      print('*** Call Trace: panic+0x15c/0x328 ***', 'e');
      print('*** System is going down NOW! ***', 'e');
      print('*** Sending SIGTERM to all processes ***', 'e');
      print('*** Sending SIGKILL to all processes ***', 'e');
      // terminal is dead — hide prompt, lock all input, never recover
      setTimeout(function() {
        hidePrompt();
        mode = 'dead';
      }, 800);
      return;
    }
    print("removed '" + files[i] + "'", 'e');
    i++;
    // vary speed slightly for realism
    setTimeout(next, 40 + Math.floor(Math.random() * 60));
  }
  next();
}

// sudo password prompt
function doSudoPrompt(sudoCmd) {
  var prevMode = mode;
  mode = 'sudo_password';
  setPrompt('[sudo] password for ' + loginUser + ':', true);

  // hijack handleEnter for one cycle
  var _origEnter = handleEnter;
  handleEnter = function(val) {
    handleEnter = _origEnter;
    print('[sudo] password for ' + loginUser + ': ', 'n');

    if (val.length >= 2) {
      // password accepted — re-run the inner command as root
      mode = 'command';
      masked = false;
      fetch('terminal.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cmd: sudoCmd, user: 'root', cols: termCols() })
      })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        handleResponse(data);
      })
      .catch(function() {
        print('sudo: connection error', 'e');
        print('', 'n');
        updateTitleAndPrompt();
        curline.style.display = 'flex';
      });
    } else {
      // wrong password
      print('sudo: 1 incorrect password attempt', 'e');
      print('', 'n');
      mode = 'command';
      masked = false;
      updateTitleAndPrompt();
      curline.style.display = 'flex';
    }
  };
}

// su — switch user
function doSu(target, needsPassword) {
  function switchTo(targetUser) {
    loginUser = targetUser;
    cwd = (targetUser === 'root') ? '/root' : '/home/' + targetUser;
    print('', 'n');
    updateTitleAndPrompt();
    renderLine();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
  }

  if (!needsPassword) {
    // root switching to another user — no password needed
    switchTo(target);
    return;
  }

  // prompt for target user's password
  mode = 'su_password';
  setPrompt('Password:', true);

  var _origEnter = handleEnter;
  handleEnter = function(val) {
    handleEnter = _origEnter;
    print('Password: ', 'n');
    if (val.length >= 2) {
      mode = 'command';
      masked = false;
      switchTo(target);
    } else {
      print('su: Authentication failure', 'e');
      print('', 'n');
      mode = 'command';
      masked = false;
      updateTitleAndPrompt();
      curline.style.display = 'flex';
    }
  };
}

// passwd — interactive password change
function doPasswd(target) {
  print('Changing password for ' + target + '.', 'n');

  // step 1: new password
  mode = 'passwd_new';
  setPrompt('New password:', true);

  var _origEnter = handleEnter;
  handleEnter = function(pw1) {
    print('New password: ', 'n');

    if (pw1.length < 2) {
      handleEnter = _origEnter;
      print('BAD PASSWORD: The password is shorter than 2 characters', 'e');
      print('passwd: Authentication token manipulation error', 'e');
      print('', 'n');
      mode = 'command';
      masked = false;
      updateTitleAndPrompt();
      curline.style.display = 'flex';
      return;
    }

    // step 2: confirm
    mode = 'passwd_confirm';
    setPrompt('Retype new password:', true);

    handleEnter = function(pw2) {
      handleEnter = _origEnter;
      print('Retype new password: ', 'n');
      mode = 'command';
      masked = false;
      if (pw1 === pw2) {
        print('passwd: all authentication tokens updated successfully.', 'n');
      } else {
        print('Sorry, passwords do not match.', 'e');
        print('passwd: Authentication token manipulation error', 'e');
      }
      print('', 'n');
      updateTitleAndPrompt();
      renderLine();
      curline.style.display = 'flex';
      scr.scrollTop = scr.scrollHeight;
    };
  };
}
