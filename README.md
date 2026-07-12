# CS2D Settings

Adds a CS2D settings page to the Pelican server panel for editing common `sys/server.cfg` options without opening the file manager.

## Features

- Shows only on servers that look like CS2D servers.
- Lists available maps from the server `maps` directory.
- Loads existing values from `sys/server.cfg`.
- Saves server, gameplay, match, and bot settings back to `sys/server.cfg`.
- Replaces the existing managed settings block on save, with a one-time migration for older scattered settings.
- Provides a restart action so CS2D can reload saved settings.

## Managed Settings

The plugin currently manages these config keys:

- `sv_name`
- `sv_password`
- `sv_rcon`
- `map`
- `sv_gamemode`
- `sv_specmode`
- `sv_maxplayers`
- `mp_recoil`
- `mp_timelimit`
- `mp_winlimit`
- `mp_roundlimit`
- `mp_roundtime`
- `mp_freezetime`
- `mp_buytime`
- `mp_startmoney`
- `mp_teamkillpenalty`
- `mp_hostagepenalty`
- `mp_tkpunish`
- `mp_idlekick`
- `mp_vulnerablehostages`
- `mp_autoteambalance`
- `mp_spectatemouse`
- `bot_autofill`
- `bot_jointeam`
- `bot_prefix`
- `bot_count`

On save, the plugin replaces its managed block in `sys/server.cfg`. If no managed block exists yet, it removes old scattered lines for the managed keys once and appends a fresh managed block.

```cfg
// --- Pelican CS2D Settings START ---
// Managed by Pelican CS2D Settings Plugin
// --- Pelican CS2D Settings END ---
```

Unrelated custom config lines are preserved.

## Installation

Install this directory as a Pelican plugin with the plugin ID:

```text
cs2dsettings
```

The plugin targets the `server` panel and was built for:

```text
panel_version: 1.0.0-beta35
```

After installing or updating the plugin, clear/rebuild any panel/plugin cache required by your Pelican installation.

## Packaging

Build a Pelican-installable zip from the repository root:

```bash
scripts/package-plugin.sh
```

The archive is written to `dist/cs2dsettings.zip`.

## Usage

1. Open a CS2D server in the Pelican server panel.
2. Go to **CS2D > CS2D Settings**.
3. Edit the desired settings.
4. Click **Save Settings**.
5. Restart the server from the page so CS2D reloads `sys/server.cfg`.

The page is available only when the server name, startup command, image, or egg metadata contains a CS2D-related marker such as `cs2d`, `counter-strike 2d`, or `cs2d_dedicated`.

## Notes

- Settings are written through the daemon file API.
- Map names are sanitized before saving and `.map` is stripped from the selected map.
- Quoted config values are escaped when saved and unescaped when loaded.
- If the `maps` directory cannot be read, the page falls back to `de_dust2`.

## Troubleshooting

If the page does not appear:

- Confirm the server is in the `server` panel.
- Confirm the server or egg metadata includes a CS2D-related marker.
- Confirm the plugin is installed with the `cs2dsettings` ID.
- Clear/rebuild the panel plugin cache.

If settings do not apply:

- Confirm `sys/server.cfg` exists or can be created by the daemon file API.
- Confirm the save notification reports success.
- Restart the server after saving.
- Check `sys/server.cfg` and confirm the managed block appears near the end of the file.
