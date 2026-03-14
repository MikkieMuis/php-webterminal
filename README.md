# php-webterminal

![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![No dependencies](https://img.shields.io/badge/dependencies-none-brightgreen.svg)

A fake interactive Linux terminal emulator for your website — built in PHP and vanilla JavaScript, zero dependencies. Drop it into any page via a single `<iframe>` and give visitors a realistic shell experience complete with a fake filesystem, boot sequence, configurable system info, and a few surprises.

Whether you want to show off your server-side skills, add a geeky touch to your portfolio, or just give visitors something fun to poke at — this is a self-contained, fully sessionised terminal that feels like the real thing.

**Live demo:** [www.clsoftware.nl](https://www.clsoftware.nl)

---

## Features

- Realistic boot sequence with configurable kernel version, CPU and disk info (never exposes real server data)
- Login prompt — password must be longer than 8 characters
- Session-based fake filesystem — `cd`, `mkdir`, `touch`, `rm` persist across commands
- Full readline-style cursor editing — `←`/`→`, `Home`/`End`, `Backspace`, `Delete`, `Ctrl+A/E/U/K/W`
- Arrow key command history (`↑`/`↓`)
- Clipboard support — paste from browser with `Ctrl+V`, copy output with mouse selection or `Ctrl+Shift+C`
- Embeddable via `<iframe>` or standalone
- Zero dependencies — pure PHP + vanilla JS, no frameworks

### Commands implemented

| Category | Commands |
|---|---|
| Navigation | `ls`, `cd`, `pwd` |
| Files | `cat`, `touch`, `mkdir`, `rm`, `cp`, `mv`, `wc`, `grep` |
| Pagers | `more`, `less` |
| System | `uname`, `uptime`, `hostname`, `date`, `df`, `free`, `ps`, `top`, `id`, `env`, `printenv`, `which` |
| Network | `ping`, `ifconfig`, `ip`, `wget`, `curl` |
| Shell | `echo`, `history`, `alias`, `clear`, `exit`, `logout`, `help`, `man` |
| Editors | `nano` |
| Packages | `dnf` |
| Users | `whoami`, `sudo`, `last` |
| Easter egg | `sudo rm -rf /` |

`grep` supports `-i` (ignore case), `-n` (line numbers), `-v` (invert), `-c` (count), `-l` (filenames only), and `-r` (recursive).

`cp` supports `-r`/`-R` for recursive directory copy. `mv` renames files and moves them between directories.

`more` and `less` support paging through any file in the fake filesystem. `less` additionally supports backward navigation (`b`, `↑`, `g`/`G`). Press `q` or `Ctrl+C` to exit either pager.

`dnf` supports `install`, `remove`, `update`/`upgrade` (animated), `list [installed|available]`, `search`, `info`, `history`, `check-update`, and `clean all`. `install`, `remove`, `update`, `upgrade`, and `clean` require root.

---

## Keyboard shortcuts

| Shortcut | Action |
|---|---|
| `↑` / `↓` | Scroll through command history |
| `←` / `→` | Move cursor within typed text |
| `Home` / `End` | Jump to start / end of line |
| `Backspace` / `Delete` | Delete char before / at cursor |
| `Ctrl+A` / `Ctrl+E` | Jump to start / end of line |
| `Ctrl+U` | Delete from cursor to start of line |
| `Ctrl+K` | Delete from cursor to end of line |
| `Ctrl+W` | Delete word before cursor |
| `Ctrl+C` | Cancel current input line (shows `^C`) |
| `Ctrl+V` | Paste clipboard text at cursor |
| `Ctrl+Shift+C` | Copy selected output text, or typed line if nothing selected |

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

3. Edit `config.php` — every option is documented inline with comments.

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
  }, window.location.origin);  // same-origin only
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
define('FS_VERSION', '7');  // increment this whenever fs_data.php changes
```

---

## Configuration reference

All constants live in `config.php`. Every option has an inline comment explaining it.

| Constant | Description |
|---|---|
| `CONF_HOSTNAME` | Hostname shown in the shell prompt and title bar |
| `CONF_DEFAULT_USER` | If set, skips the username prompt and uses this value |
| `CONF_KERNEL` | Kernel version string shown by `uname -a`, `top`, boot sequence |
| `CONF_ARCH` | CPU architecture shown by `uname -a` |
| `CONF_OS` | OS name shown by `uname -a` and boot sequence |
| `CONF_DISK_TOTAL` | Fake total disk size in bytes, shown by `df` |
| `CONF_DISK_USED` | Fake used disk space in bytes, shown by `df` |
| `CONF_DISK_FREE` | Fake free disk space in bytes, shown by `df` |
| `CONF_LOAD_1` | Fake 1-minute load average, shown by `uptime` and `top` |
| `CONF_LOAD_5` | Fake 5-minute load average, shown by `uptime` and `top` |
| `CONF_LOAD_15` | Fake 15-minute load average, shown by `uptime` and `top` |

---

## Known incompatibilities

### Vimium / Surfingkeys and other keyboard extensions

Browser extensions that intercept keyboard input — such as [Vimium](https://github.com/philc/vimium) and [Surfingkeys](https://github.com/brookhong/Surfingkeys) — will capture keystrokes before they reach the terminal, breaking typing completely.

If the terminal is not responding to keyboard input, disable these extensions for the page or add the site to their exclusion list:

- **Vimium** — open Vimium options → *Excluded URLs and keys* → add the site URL
- **Surfingkeys** — press `Alt+s` on the page to toggle it off, or add an exclusion in settings

---

## Similar projects

These all take a different approach (pure JavaScript, browser-only, no PHP backend) but are worth knowing about:

- [m4tt72/terminal](https://github.com/m4tt72/terminal) ★1.5k — The gold standard for terminal portfolio sites. Built with Svelte 4 + TypeScript. Highlights: multiple switchable themes, real weather via `wttr.in`, `todo` task manager, tab autocomplete, responsive on mobile. 53 releases, actively maintained.
- [MarketingPipeline/Termino.js](https://github.com/MarketingPipeline/Termino.js) ★642 — A JavaScript library for embedding a terminal widget into any website. Supports HTML output (clickable links), multiple instances per page, custom key/mouse event hooks, and curses-style apps. Great if you want a drop-in component rather than a standalone page.
- [DosX-dev/braux](https://github.com/DosX-dev/braux) ★97 — Unix-like web console system in pure JS/HTML/CSS with multiple color themes and scheme switching. Minimal and clean.
- [jcubic/fake-linux-terminal](https://github.com/jcubic/fake-linux-terminal) — browser-only fake GNU/Linux environment using LightningFS and jQuery Terminal. More ambitious in scope, still a work in progress.
- [edgorman/edgorman.github.io](https://github.com/edgorman/edgorman.github.io) — personal portfolio site that uses real GitHub repos as the filesystem content via git submodules. Clever idea.


---

## License

MIT — do whatever you want with it.
