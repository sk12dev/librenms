# Custom branding assets

Place fork branding files here so upstream LibreNMS updates do not overwrite them.

| File | Purpose |
|------|---------|
| `logo.svg` | Header, login, and 2FA pages (via `title_image` config) |
| `favicon.ico` | Optional browser tab icon (set `favicon` config) |

Default config points to `images/custom/logo.svg`. Override in the UI under **Settings → Web UI → Style**, or run:

```bash
lnms config:set title_image images/custom/logo.svg
lnms config:set project_name "Your Brand Name"
```
