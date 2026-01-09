# React Dashboard Integration - Setup Instructions

## Quick Start

1. **Build and copy the React dashboard:**
   ```powershell
   cd react-dashboard-integration
   .\build-and-copy.ps1
   ```

   Or on Linux/Mac:
   ```bash
   cd react-dashboard-integration
   chmod +x build-and-copy.sh
   ./build-and-copy.sh
   ```

2. **Access the dashboard:**
   - Navigate to: `http://your-librenms-url/react-dashboard`
   - You must be logged in to LibreNMS

## What Was Changed

### React Project (`react-dashboard-integration/`)

1. **`vite.config.ts`** - Updated to:
   - Set base path to `/react-dashboard/`
   - Configure build output

2. **`src/api/client.ts`** - Updated to:
   - Read API configuration from `window.ReactDashboardConfig` (set by Blade template)
   - Fallback to environment variables for standalone development

3. **`src/main.tsx`** - Updated to:
   - Support both `#root` (standalone) and `#react-dashboard-root` (integrated) element IDs

### LibreNMS Project

1. **`app/Http/Controllers/ReactDashboardController.php`** - New controller that:
   - Gets the user's API token
   - Reads built asset paths from `index.html`
   - Passes configuration to the React app

2. **`resources/views/react-dashboard/index.blade.php`** - Blade template that:
   - Loads React dashboard assets dynamically
   - Passes API configuration via `window.ReactDashboardConfig`

3. **`routes/web.php`** - Added route:
   - `GET /react-dashboard` (requires authentication)

## API Token Setup

The React dashboard needs an API token to make API calls. The controller automatically uses the first enabled API token for the logged-in user.

**To create an API token:**
1. Log in to LibreNMS
2. Go to your user settings
3. Navigate to API Access section
4. Create a new API token
5. The dashboard will automatically use it

## Development Workflow

### Standalone Development (React app only)

1. Create a `.env` file in `react-dashboard-integration/`:
   ```
   VITE_LIBRENMS_API_URL=http://your-librenms-url
   VITE_LIBRENMS_API_TOKEN=your-api-token
   ```

2. Run the dev server:
   ```bash
   cd react-dashboard-integration
   npm run dev
   ```

### Integrated Development (within LibreNMS)

1. Make changes to React code in `react-dashboard-integration/src/`

2. Build and copy:
   ```powershell
   cd react-dashboard-integration
   .\build-and-copy.ps1
   ```

3. Refresh the page at `/react-dashboard`

## Troubleshooting

### Dashboard not loading

- **Check browser console** for errors
- **Verify files exist:** Check that `html/react-dashboard/` contains the built files
- **Rebuild:** Run the build script again

### API calls failing

- **Check API token:** Ensure you have an enabled API token
- **Check console:** Look for API errors in browser console
- **Verify API URL:** Check that `window.ReactDashboardConfig.apiUrl` is correct

### Assets not loading (404 errors)

- **Rebuild:** Run `build-and-copy.ps1` again
- **Check paths:** Verify files are in `html/react-dashboard/assets/`
- **Clear cache:** Hard refresh browser (Ctrl+F5)

### Styling issues

- The React dashboard uses Ant Design which may conflict with LibreNMS styles
- The dashboard is wrapped in a container that should isolate styles
- Check browser DevTools for CSS conflicts

## File Structure

```
librenms/
├── react-dashboard-integration/    # React source code
│   ├── src/                         # React source files
│   ├── dist/                        # Build output (temporary)
│   └── build-and-copy.ps1          # Build script
├── html/
│   └── react-dashboard/            # Copied build files (served by web server)
│       ├── index.html
│       └── assets/
│           ├── index-[hash].js
│           └── index-[hash].css
├── app/Http/Controllers/
│   └── ReactDashboardController.php
└── resources/views/react-dashboard/
    └── index.blade.php
```

## Next Steps

- Customize the dashboard layout and widgets
- Add more API endpoints as needed
- Integrate with LibreNMS theme/styling
- Add user preferences for dashboard configuration

