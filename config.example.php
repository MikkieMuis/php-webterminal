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
