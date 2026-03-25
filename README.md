# php-webterminal

![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![No dependencies](https://img.shields.io/badge/dependencies-none-brightgreen.svg)

A fake interactive Linux terminal emulator for your website — built in PHP and vanilla JavaScript, zero dependencies. Drop it into any page via a single `<iframe>` and give visitors a realistic shell experience complete with a fake filesystem, boot sequence, configurable system info, and a few surprises.

Whether you want to show off your server-side skills, add a geeky touch to your portfolio, or just give visitors something fun to poke at — this is a self-contained, fully sessionised terminal that feels like the real thing.

**Live demo:** [www.clsoftware.nl](https://www.clsoftware.nl)

---

Open the demo and have a poke around. Some highlights:

- **`sudo rm -rf /`** — just try it. You'll see why it's in here.
- **`sudo su`** or **`su root`** — become root. The prompt changes, permissions change, locked commands unlock.
- **`cat /etc/shadow`** — readable as root only. Try it as guest first.
- **`nano /etc/hosts`** — open and edit a real-looking system file. Save it, `cat` it back, the change sticks for the session.
- **`mysql -u root -p`** — a full fake database session. `SHOW DATABASES`, `USE production`, `SELECT * FROM users`. It's all there.
- **`htop`** — colour bars, fake processes, press `q` to exit.
- **`ssh someserver.com`** — it'll refuse the connection. Try `ssh localhost` instead.
- **`dig clsoftware.nl MX`** — fake but formatted like the real thing.
- **`bc <<< "2^32"`** — it actually calculates it.
- **`base64 <<< "hello"`** — encodes it. `base64 -d` decodes it back.
- **`neofetch`** — AlmaLinux logo, fake specs, the works.
- **`pushd /var/log`** then **`dirs`** then **`popd`** — directory stack, works as expected.
- **`dnf install vim`** — watch it pretend to install something. Root only.
- **`man ssh`** — there are man pages for everything.

The terminal resets when you close the tab. Nothing is real. Break whatever you want.

---

## Add a terminal to your website

Sometimes you just want something on your site that makes people go "oh, that's cool."

Drop a fully working Linux terminal into any page in one line:

```html
<iframe src="path/to/php-webterminal/index.php" width="900" height="520"></iframe>
```

Visitors get a real shell they can actually type in. They can `ls` around, read fake config files, break things, `sudo` to root, run `htop`, mess with `nano` — the whole deal. Nothing is real, nothing breaks, and most people will spend way longer poking at it than you'd expect.

It's a great addition to a portfolio, an agency site, or honestly anywhere you want to make a good impression on someone who knows their way around a terminal. A static screenshot of a server says "I know Linux." An interactive terminal says it louder.

Configure it to look like your own stack — set the hostname, OS, kernel version, disk layout, whatever — and it'll feel like you're giving someone a live shell on your actual machine. You're not, obviously, but it looks the part.

No database. No npm. No framework. One PHP file and you're done.

---

## Use it as a self-hosted Linux practice environment

> **No VM. No Docker. No signup. Just open a browser and start typing.**

This project doubles as a self-hosted, browser-based Linux sandbox. Use it to practice Linux commands without risking anything on a real server. It runs a simulated AlmaLinux 9.7 environment with a realistic filesystem, three user accounts (`guest`, `deploy`, and `root`), and over 80 commands covering everything beginners and intermediate users need to know.

**Who is this for?**

- Beginners learning Linux for the first time
- Students preparing for the LPIC-1, CompTIA Linux+, or RHCSA exams
- Sysadmins who want a quick command reference they can actually run
- Developers who want to add a Linux terminal to their portfolio site
- Anyone who just wants to poke around a Linux shell without setting up a VM

**What makes it different from paid Linux practice sites?**

- Open source (MIT licence)
- Zero setup — no account, no download, no VM
- Runs in any browser, including mobile
- **Fully self-hostable** — runs on any machine with PHP 7.4+, including your local laptop, a Raspberry Pi, a shared hosting account, or a VPS. No internet connection required after the initial clone. See [Setup](#setup) below.
- 80+ commands implemented: `ls`, `cd`, `grep`, `ps`, `top`, `htop`, `systemctl`, `dnf`, `nano`, `sudo`, `chmod`, `tar`, `zip`, `curl`, `wget`, `ping`, `netstat`, `ss`, `dig`, `journalctl`, `awk`, `sed`, and many more
- Two users: `guest` (default, logged in automatically), `deploy` (regular user), and `root` (full access via `sudo` or `su`) — practice privilege escalation safely
- Realistic fake filesystem with config files, logs, home directories, and service unit files
- Session-persistent — changes you make (`mkdir`, `touch`, `rm`) survive across commands in the same session
- Embeddable in any page via a single `<iframe>`

**Run it locally in 30 seconds:**

```bash
git clone https://github.com/MikkieMuis/php-webterminal.git
cd php-webterminal
cp config.example.php config.php
php -S localhost:8080
```

Then open `http://localhost:8080` in your browser. That's it — no database, no composer, no npm.

**Keywords:** linux terminal online, linux command line practice, linux sandbox browser, learn linux commands, linux practice environment, fake linux terminal, linux command simulator, bash practice online, linux shell online, linux for beginners, rhcsa practice, lpic practice, comptia linux practice, linux vm alternative, linux web terminal, php terminal emulator, localhost linux terminal, offline linux practice, self-hosted linux sandbox, linux practice without vm

---

## Features

- Realistic boot sequence with configurable kernel version, CPU and disk info (never exposes real server data)
- Auto-login as `guest` — no login prompt, start typing immediately
- Session-based fake filesystem — `cd`, `mkdir`, `touch`, `rm` persist across commands
- Full readline-style cursor editing — `←`/`→`, `Home`/`End`, `Backspace`, `Delete`, `Ctrl+A/E/U/K/W`
- Tab completion for commands and file paths
- Arrow key command history (`↑`/`↓`)
- Clipboard support — paste from browser with `Ctrl+V`, copy output with mouse selection or `Ctrl+Shift+C`
- Embeddable via `<iframe>` or standalone
- Zero dependencies — pure PHP + vanilla JS, no frameworks

### Commands implemented

| Category | Commands |
|---|---|
| Navigation | `ls`, `ll`, `cd`, `pwd` |
| Files | `cat`, `touch`, `mkdir`, `rmdir`, `rm`, `cp`, `mv`, `ln`, `wc`, `du`, `chmod`, `chown`, `zip`, `unzip`, `tar` |
| Search & text | `grep`, `head`, `tail`, `diff`, `find`, `sort`, `uniq`, `cut`, `tr`, `awk`, `sed` |
| Pagers | `more`, `less` |
| System info | `uname`, `uptime`, `hostname`, `date`, `df`, `free`, `ps`, `top`, `htop`, `id`, `env`, `printenv`, `which`, `whoami`, `fastfetch`, `neofetch`, `exa` |
| Services | `systemctl`, `firewall-cmd`, `journalctl` |
| Hardware & processes | `php`, `kill`, `pkill`, `lsblk`, `blkid`, `dmesg`, `vmstat`, `iostat`, `hostnamectl`, `timedatectl`, `chgrp`, `logger`, `lsof` |
| Network | `ping`, `ifconfig`, `ip`, `wget`, `curl`, `telnet`, `sendmail`, `netstat`, `ss`, `ssh`, `dig`, `host` |
| Shell | `echo`, `history`, `alias`, `clear`, `exit`, `logout`, `help`, `man`, `sudo`, `su`, `last`, `passwd`, `base64`, `bc`, `pushd`, `popd`, `dirs` |
| Editors | `nano`, `joe` |
| Packages | `dnf` |
| Database | `mysql`, `mariadb` |

`grep` supports `-i` (ignore case), `-n` (line numbers), `-v` (invert), `-c` (count), `-l` (filenames only), and `-r` (recursive).

`cp` supports `-r`/`-R` for recursive directory copy. `mv` renames files and moves them between directories.

`head` outputs the first N lines (or bytes with `-c`) of a file. Default is 10 lines. Supports `-n`, `-c`, `-q`, `-v`, and multiple files.

`tail` outputs the last N lines (or bytes with `-c`) of a file. Default is 10 lines. Supports `-n`, `-c`, `-f` (simulated follow), `-q`, `-v`, `+N` offset syntax, and multiple files.

`more` and `less` support paging through any file in the fake filesystem. `less` additionally supports backward navigation (`b`, `↑`, `g`/`G`). Press `q` or `Ctrl+C` to exit either pager.

`ll` is an alias for `ls -la`.

`sudo` allows non-root users to run commands as root after a password prompt.

`zip` creates ZIP archives from files and directories. `zip -r` recurses into directories. `unzip` extracts archives, lists contents with `-l`, and can extract to a target directory with `-d`. `tar` creates and extracts `.tar.gz` (with `-z`) and `.tar.bz2` (with `-j`) archives; `-c` create, `-x` extract, `-t` list, `-v` verbose.

`sort` sorts lines of a file. Supports `-r` (reverse), `-n` (numeric), `-u` (unique), `-f` (ignore case), `-k N` (sort by field N), and `-t SEP` (field separator).

`uniq` filters adjacent duplicate lines. Supports `-c` (prefix with count), `-d` (duplicates only), `-u` (unique only), and `-i` (ignore case).

`fastfetch` and `neofetch` display system information alongside the AlmaLinux ASCII logo.

`top` shows a live process table (auto-refreshes). `htop` shows an enhanced process viewer with colour bars for CPU and memory; press `q` to exit.

`dnf` supports `install`, `remove`, `update`/`upgrade` (animated), `list [installed|available]`, `search`, `info`, `history`, `check-update`, and `clean all`. `install`, `remove`, `update`, `upgrade`, and `clean` require root.

`systemctl` supports `status`, `start`, `stop`, `restart`, `enable`, `disable`, `is-active`, and `list-units` for eight fake services (`httpd`, `mariadb`, `php-fpm`, `mysqld`, `sshd`, `firewalld`, `crond`, `nginx`). The `.service` suffix is stripped automatically.

`php` supports `-v` / `--version` (version string), `-i` (phpinfo), `-m` (module list), and `-r 'code'` (evaluate arithmetic and echo expressions).

`exa` is a modern replacement for `ls`. Supports `--long` / `-l` (detailed table with Permissions, Size, Date columns), `--tree` / `-T` (recursive tree view), `--all` / `-a` (include dotfiles), `--git` (add a Git status column), and `--icons`.

`firewall-cmd` is a front-end for firewalld. Supports `--state`, `--reload`, `--list-all`, `--list-ports`, `--add-port=PORT/PROTO`, and `--remove-port=PORT/PROTO`.

`telnet` connects to a remote host and port (default 23). Returns connection metadata to the JS layer for a simulated connection animation.

`sendmail` queues a mail message. Plain invocation reports the message as queued. `-v` prints a full SMTP trace (EHLO, MAIL FROM, RCPT TO, DATA, QUIT). `-t` accepts the message from stdin (non-interactive stub).

`rmdir` removes empty directories (errors if the directory has contents). `du` reports disk usage; supports `-s` (summarise) and `-h` (human-readable). `chmod` and `chown` are cosmetic — they accept the standard arguments and succeed silently. `diff` compares two files line by line; supports `-u` (unified format) and `-i` (ignore case).

`passwd` simulates a password change prompt and always reports success. `base64` encodes or decodes a string (`-d` / `--decode`); pass input via `<<<`. `bc` evaluates arithmetic expressions (`+`, `-`, `*`, `/`, `^`, `%`, parentheses); pass the expression via `<<<`.

`nano` is a full-screen text editor overlay. Open a file with `nano <file>`, edit freely, and save with `Ctrl+O` (then Enter) or exit with `Ctrl+X`. `joe` is an alternative full-screen editor with `^K` prefix key bindings; save with `^KD`, exit with `^KX`, abort with `^KC`.

`ln -s TARGET LINK` creates symbolic links. Symlinks appear cyan in `ls` output and show `lrwxrwxrwx -> target` in `ls -l`. Hard links are not supported.

`cut` extracts fields or byte ranges from lines. Supports `-f` (fields), `-d` (delimiter), `-c` (character positions), and `--complement`.

`tr` translates or deletes characters. Supports `-d` (delete), `-s` (squeeze), and character classes such as `[:upper:]`, `[:lower:]`, `[:digit:]`.

`awk` processes text by field. Supports `print`, `printf`, `$0`–`$NF`, `NR`, `NF`, `BEGIN`/`END` blocks, and field separator with `-F`. Pass the program as `awk '{...}' file` or via stdin.

`sed` applies substitutions and other edits to text. Supports `s/pattern/replacement/[g]`, address ranges, `-n` (suppress default output), and `p` (print). Pass the script as `sed 's/.../' file` or via stdin.

`kill PID` sends SIGTERM to a process; `kill -9 PID` sends SIGKILL. `pkill NAME` kills processes by name. Both operate on the fake process table.

`lsblk` lists block devices in a tree layout (four disks: `sda` 500G, `sdb` 2T, `sdc` 4T, `sdd` 500G, each with partitions). `blkid` prints UUIDs and filesystem types for each partition.

`dmesg` prints the kernel ring buffer. Supports `-T` (human-readable timestamps) and `-n N` (last N lines).

`vmstat` shows virtual memory statistics (single static snapshot). `iostat` shows CPU and per-disk I/O statistics.

`hostnamectl` shows the system hostname, machine ID, OS, kernel, and chassis type. `timedatectl` shows local time, UTC time, timezone, and NTP sync status.

`chgrp` is a cosmetic stub — it accepts standard arguments and exits silently. `logger` writes a message to the system log (cosmetic; no persistent syslog across sessions).

`lsof` lists open files and network sockets. Filter by `-i :PORT` (connections on a port), `-p PID` (files for a process), or `-u USER` (files for a user).

`journalctl` queries the systemd journal. Supports `-u UNIT` (filter by service), `-n N` (last N lines), `-r` (reverse order), `-f` (simulated follow), and `--since DATE`.

`netstat` prints active connections and listening sockets. Supports `-a` (all), `-n` (numeric), `-p` (show PID/program), `-t` (TCP), `-u` (UDP), `-l` (listening only), and `-r` (routing table).

`ss` shows socket statistics (iproute2 replacement for `netstat`). Supports `-a`, `-l`, `-n`, `-p`, `-t`, `-u`.

`ssh [USER@]HOST` simulates a remote login. Connections to localhost succeed; all other hosts are refused. Supports `-v` (verbose handshake output).

`dig HOST [TYPE]` performs a DNS lookup and prints a full resolver-style response. Supports record types A, MX, NS, TXT, CNAME, SOA, ANY, and the `@server` flag to specify a resolver.

`host HOST [-t TYPE]` is a concise DNS lookup. Supports reverse PTR lookups for IP addresses and `-t TYPE` to request a specific record type.

`pushd <dir>` changes to the given directory and pushes the previous directory onto the stack. Called with no arguments it swaps the top two stack entries. `popd` returns to the previous directory by popping the stack. `dirs [-v]` displays the directory stack; `-v` shows numbered entries.

`mysql` and `mariadb` open an interactive database session stub. Supports `SHOW DATABASES`, `USE db`, `SHOW TABLES`, `DESCRIBE table`, `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE TABLE`, and `DROP TABLE` against a small in-memory fake schema.

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
| `Ctrl+L` | Clear the screen |
| `Ctrl+R` | Reverse history search (type to search, Enter to run, Esc to cancel) |
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
define('FS_VERSION', '18');  // increment this whenever fs_data.php changes
```

---

## Configuration reference

All constants live in `config.php`. Every option has an inline comment explaining it.

| Constant | Description |
|---|---|
| `CONF_HOSTNAME` | Hostname shown in the shell prompt and title bar |
| `CONF_DEFAULT_USER` | Override the startup username (default: `guest`) |
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
