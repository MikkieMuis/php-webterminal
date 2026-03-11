# Changelog

All notable changes to php-webterminal will be documented here.

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
- Boot sequence with real kernel, CPU and disk info from server
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
