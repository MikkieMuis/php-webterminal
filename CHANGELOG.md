# Changelog

All notable changes to php-webterminal will be documented here.

## [1.6.0] - 2026-03-14

### Added
- `dnf` — AlmaLinux 9 package manager with 10 subcommands:
  - `dnf install <pkg...>` — animated per-package download + install progress; requires root
  - `dnf remove <pkg...>` — animated removal sequence; requires root
  - `dnf update` / `dnf upgrade` — animated upgrade run across 7 packages; requires root
  - `dnf list [installed|available]` — realistic installed/available package list
  - `dnf search <term>` — searches ~37 packages by name and description
  - `dnf info <pkg>` — detailed package metadata (version, size, repo, summary)
  - `dnf history` — fake transaction history with 9 entries
  - `dnf check-update` — lists 7 packages with updates available
  - `dnf clean all` — fake cache cleanup; requires root
  - `dnf` (no args) — usage hint
- `which dnf`, `which yum`, `which rpm` — added to binary map

## [1.5.0] - 2026-03-14

### Added
- `grep` — search file contents with `-i`, `-n`, `-v`, `-c`, `-l`, `-r`/`-R` flag support
- `cp` — copy files and directories; supports `-r`/`-R` for recursive directory copy
- `mv` — move and rename files and directories
- Man pages for `grep`, `cp`, `mv`

## [1.4.0] - 2026-03-14

### Added
- `wc` — word/line/byte count with `-l`, `-w`, `-c`/`-m` flags
- `more` / `less` — pager overlay with scroll, `q` to quit; `less` adds backward navigation
- Man pages for `wc`, `more`, `less`, `cron`, `crontab`, `httpd`, `apachectl`

### Fixed
- `nano` keyboard input was silently ignored — `nanoActive` flag was never set to `true`
- `nano` line-count off-by-one for files ending with a newline

### Changed
- Titlebar (traffic-light dots + filename) removed — cleaner, less chrome, more realistic
- Font size bumped from 14px to 15px for improved readability

## [1.3.0] - 2026-03-11

### Added
- Expanded fake filesystem to ~600 entries: `/etc` subdirs, `/var/log`, `/var/www/html`, `/mnt`, `/usr/local/bin`, `/root` dotfiles and scripts

### Changed
- Bumped `FS_VERSION` to force session reload after filesystem expansion

## [1.2.0] - 2026-03-11

### Security
- Removed all real server data exposure: `shell_exec('uname -r/m')`, `disk_free_space()`, `disk_total_space()`, `sys_getloadavg()`, and `/etc/os-release` reads replaced with constants in `config.php`
- Added `postMessage` origin validation — cross-origin messages are now rejected
- Capped `php://input` at 4 KB; `cmd` field at 1024 chars, `user` at 64 chars, per-entry command log at 1024 chars
- Session cookie hardened: `Secure`, `HttpOnly`, `SameSite=Strict`

### Changed
- All fake system info (kernel, OS, disk, load) is now fully configurable via `config.php`
- `config.example.php` updated with all new constants

## [1.1.0] - 2026-03-11

### Added
- `man` — fake manual pages for 20 commands
- `ping` — animated ICMP reply sequence with statistics summary
- `top` — full-screen live process monitor, press `q` to quit
- `wget` — animated download progress bar
- `curl` — animated transfer stats, supports `-o`/`-O`/`-s` flags
- `id` — prints uid/gid/groups
- `which` — resolves paths for ~50 common binaries
- `env` — prints session environment variables
- `alias` — lists standard bash aliases
- `last` — prints recent login history
- `sudo` for non-root users — prompts for password before executing inner command
- `sudo rm -rf /` easter egg — now correctly requires password for non-root users

### Fixed
- Arrow key history now works when terminal is embedded in an iframe and has focus
- `sudo rm -rf /` no longer bypasses password prompt for non-root users
- Terminal goes permanently dead after kernel panic — no "just kidding", no auto-reset

## [1.0.0] - 2026-03-09

### Added
- Initial release
- Boot sequence with configurable kernel, CPU and disk info (via `config.php`)
- Login prompt with password validation
- Session-based fake filesystem (~165 entries)
- `FS_VERSION` system — bumping forces all sessions to reload filesystem
- Core commands: `ls`, `cd`, `pwd`, `cat`, `mkdir`, `touch`, `rm`
- System commands: `df`, `free`, `ps`, `uptime`, `uname`, `hostname`, `date`
- Network commands: `ifconfig`, `ip`
- Shell commands: `echo`, `history`, `clear`, `exit`, `logout`, `help`, `whoami`, `sudo`
- Arrow key command history
- `sudo rm -rf /` easter egg with kernel panic sequence
- Embeddable via iframe with postMessage keyboard forwarding


### Security
- Removed all real server data exposure: `shell_exec('uname -r/m')`, `disk_free_space()`, `disk_total_space()`, `sys_getloadavg()`, and `/etc/os-release` reads replaced with constants in `config.php`
- Added `postMessage` origin validation — cross-origin messages are now rejected
- Capped `php://input` at 4 KB; `cmd` field at 1024 chars, `user` at 64 chars, per-entry command log at 1024 chars
- Session cookie hardened: `Secure`, `HttpOnly`, `SameSite=Strict`

### Changed
- All fake system info (kernel, OS, disk, load) is now fully configurable via `config.php`
- `config.example.php` updated with all new constants

## [1.1.0] - 2026-03-11

### Added
- `man` — fake manual pages for 20 commands
- `ping` — animated ICMP reply sequence with statistics summary
- `top` — full-screen live process monitor, press `q` to quit
- `wget` — animated download progress bar
- `curl` — animated transfer stats, supports `-o`/`-O`/`-s` flags
- `id` — prints uid/gid/groups
- `which` — resolves paths for ~50 common binaries
- `env` — prints session environment variables
- `alias` — lists standard bash aliases
- `last` — prints recent login history
- `sudo` for non-root users — prompts for password before executing inner command
- `sudo rm -rf /` easter egg — now correctly requires password for non-root users

### Fixed
- Arrow key history now works when terminal is embedded in an iframe and has focus
- `sudo rm -rf /` no longer bypasses password prompt for non-root users
- Terminal goes permanently dead after kernel panic — no "just kidding", no auto-reset

## [1.0.0] - 2026-03-09

### Added
- Initial release
- Boot sequence with configurable kernel, CPU and disk info (via `config.php`)
- Login prompt with password validation
- Session-based fake filesystem (~165 entries)
- `FS_VERSION` system — bumping forces all sessions to reload filesystem
- Core commands: `ls`, `cd`, `pwd`, `cat`, `mkdir`, `touch`, `rm`
- System commands: `df`, `free`, `ps`, `uptime`, `uname`, `hostname`, `date`
- Network commands: `ifconfig`, `ip`
- Shell commands: `echo`, `history`, `clear`, `exit`, `logout`, `help`, `whoami`, `sudo`
- Arrow key command history
- `sudo rm -rf /` easter egg with kernel panic sequence
- Embeddable via iframe with postMessage keyboard forwarding
