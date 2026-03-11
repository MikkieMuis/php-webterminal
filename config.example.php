<?php
// ============================================================
//  php-webterminal — configuration
//  Copy this file to config.php and edit to your needs.
// ============================================================

// The hostname shown in the shell prompt and title bar.
// Do NOT use gethostname() here — your real server hostname
// may leak hosting provider details.
define('CONF_HOSTNAME', 'your-hostname-here');

// Optionally override the displayed username after login.
// Leave empty to use whatever the visitor typed at the login prompt.
define('CONF_DEFAULT_USER', '');

// Fake kernel version, architecture, and OS string shown by uname, top, etc.
// Never use shell_exec('uname -r') or file_get_contents('/etc/os-release') —
// those expose real server details.
define('CONF_KERNEL', '5.14.0-1-generic');
define('CONF_ARCH',   'x86_64');
define('CONF_OS',     'Ubuntu 22.04.3 LTS');

// Fake disk usage shown by df.  Values in bytes.
// Never use disk_free_space() / disk_total_space() — those expose real data.
define('CONF_DISK_TOTAL', 107374182400);   // 100 GB
define('CONF_DISK_USED',   32212254720);   //  30 GB
define('CONF_DISK_FREE',   75161927680);   //  70 GB

// Fake load averages shown by uptime and top.
// Never use sys_getloadavg() — that exposes real server load.
define('CONF_LOAD_1',  0.42);
define('CONF_LOAD_5',  0.38);
define('CONF_LOAD_15', 0.31);
