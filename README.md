# php-webterminal

A fake interactive Linux terminal built in PHP and vanilla JavaScript. Drop it into any website and give visitors a realistic shell experience — complete with a fake filesystem, real system stats, animated commands, and a few surprises.

**Live demo:** [www.clsoftware.nl](https://www.clsoftware.nl)

---

## Features

- Realistic boot sequence with real kernel version, CPU and disk info pulled from the server
- Login prompt — password must be longer than 8 characters
- Session-based fake filesystem — `cd`, `mkdir`, `touch`, `rm` persist across commands
- Arrow key command history
- Embeddable via `<iframe>` or standalone
- Zero dependencies — pure PHP + vanilla JS, no frameworks

### Commands implemented

| Category | Commands |
|---|---|
| Navigation | `ls`, `cd`, `pwd` |
| Files | `cat`, `touch`, `mkdir`, `rm`, `cp`, `mv`, `grep` |
| System | `uname`, `uptime`, `hostname`, `date`, `df`, `free`, `ps`, `top`, `id`, `env`, `which` |
| Network | `ping`, `ifconfig`, `ip`, `wget`, `curl` |
| Shell | `echo`, `history`, `alias`, `clear`, `exit`, `logout`, `help`, `man` |
| Users | `whoami`, `sudo`, `last` |
| Easter egg | `sudo rm -rf /` |

---

## Requirements

- PHP 7.4 or higher
- Any web server (Apache, Nginx, etc.)

---

## Setup

1. Clone the repo into your web root (or a subdirectory):

```bash
git clone https://github.com/MikkieMuis/php-webterminal.git
```

2. Copy the example config and edit it:

```bash
cp config.example.php config.php
```

3. Open `config.php` and set your hostname:

```php
define('CONF_HOSTNAME', 'myserver');
```

4. Visit `index.php` in your browser. Done.

---

## Embedding via iframe

To embed the terminal inside an existing page, use an `<iframe>` and forward keyboard events via `postMessage` so the terminal receives input even when the iframe does not have focus:

```html
<div id="terminal-wrap">
  <iframe id="term-frame" src="path/to/php-webterminal/index.php"></iframe>
</div>

<script>
var frame = document.getElementById('term-frame');
document.addEventListener('keydown', function(e) {
  if (['Space','ArrowUp','ArrowDown','Backspace','Enter'].includes(e.code)) {
    e.preventDefault();
  }
  frame.contentWindow.postMessage({
    type: 'keydown', key: e.key,
    ctrlKey: e.ctrlKey, altKey: e.altKey, metaKey: e.metaKey
  }, '*');
});
</script>
```

---

## Customising the filesystem

Edit `fs_data.php` to change what files and directories exist. Each entry is an array with a `type` (`file` or `dir`) and optional `content` and `mtime`:

```php
'/etc/motd' => [
    'type'    => 'file',
    'content' => "Welcome to my server.\n",
    'mtime'   => mktime(9, 0, 0, 1, 15, 2026),
],
```

After changing `fs_data.php`, bump the `FS_VERSION` constant at the top of `terminal.php` to force all active browser sessions to reload the new filesystem:

```php
define('FS_VERSION', '4');  // increment this whenever fs_data.php changes
```

---

## Configuration reference

| Constant | Default | Description |
|---|---|---|
| `CONF_HOSTNAME` | `your-hostname-here` | Hostname shown in prompt and title bar |
| `CONF_DEFAULT_USER` | *(empty)* | If set, skips the username prompt and uses this value |

---

## License

MIT — do whatever you want with it.
