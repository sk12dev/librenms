# React Dashboard Integration Guide

This guide explains how to integrate your separate React dashboard project into this LibreNMS PHP/Laravel project.

## Overview

Your React dashboard will be accessible at: `/react-dashboard` (requires authentication)

## Integration Steps

### Step 1: Prepare Your React Project

1. **Build your React app for production:**
   ```bash
   cd /path/to/your/react-dashboard
   npm run build
   ```

2. **Configure your React app's build output:**
   - Make sure your React app builds to a `build/` or `dist/` directory
   - Update `package.json` if needed to set the correct output directory

### Step 2: Copy Built Files to LibreNMS

1. **Create the directory structure:**
   ```bash
   mkdir -p html/react-dashboard
   ```

2. **Copy your built React files:**
   ```bash
   # From your React project root
   cp -r build/* /path/to/librenms/html/react-dashboard/
   ```
   
   Or on Windows:
   ```powershell
   Copy-Item -Path "build\*" -Destination "C:\Users\Andy\Documents\Development\sk12devLibrenmsFork\librenms\html\react-dashboard\" -Recurse
   ```

3. **Verify the structure:**
   ```
   html/react-dashboard/
   ├── index.html (optional, not used)
   ├── static/
   │   ├── js/
   │   │   └── main.js (or main.[hash].js)
   │   └── css/
   │       └── main.css (or main.[hash].css)
   └── ...
   ```

### Step 3: Update the Blade Template

Edit `resources/views/react-dashboard/index.blade.php` and update the script paths to match your actual build output:

```php
{{-- If your React app uses hashed filenames, you may need to use a different approach --}}
<script src="{{ asset('react-dashboard/static/js/main.js') }}"></script>
<link href="{{ asset('react-dashboard/static/css/main.css') }}" rel="stylesheet">
```

**Note:** If your React build uses hashed filenames (e.g., `main.abc123.js`), you have two options:

1. **Option A:** Use a manifest file to map filenames
2. **Option B:** Configure your React build to use fixed filenames

### Step 4: Configure React App for LibreNMS API

Your React app should use the LibreNMS API. The Blade template passes configuration via `window.ReactDashboardConfig`:

```javascript
// In your React app
const config = window.ReactDashboardConfig || {};

// Use the API URL
const apiUrl = config.apiUrl || '/api/v0';

// Include authentication token if needed
const headers = {
  'X-Auth-Token': config.apiToken,
  'X-CSRF-TOKEN': config.csrfToken,
};
```

### Step 5: Handle API Authentication

LibreNMS API authentication options:

1. **API Token (recommended):**
   - Users can generate API tokens in LibreNMS
   - Pass the token in headers: `X-Auth-Token: <token>`

2. **Session-based (if same domain):**
   - Cookies are automatically sent with requests
   - May require CORS configuration

3. **Update the controller** to pass the correct token:
   ```php
   'apiToken' => $request->user()->api_token ?? null,
   ```

### Step 6: Test the Integration

1. **Start your Laravel development server:**
   ```bash
   php artisan serve
   ```

2. **Navigate to:** `http://localhost:8000/react-dashboard`

3. **Verify:**
   - Page loads without errors
   - React app initializes
   - API calls work correctly
   - Authentication is working

## Alternative: Build React with Vite in This Project

If you want to integrate React directly into the Vite build process:

1. **Install React dependencies:**
   ```bash
   npm install react react-dom
   npm install --save-dev @vitejs/plugin-react
   ```

2. **Update `vite.config.mjs`:**
   ```javascript
   import react from '@vitejs/plugin-react';
   
   export default defineConfig({
       plugins: [
           laravel({...}),
           vue({...}),
           react(), // Add React plugin
           tailwindcss(),
       ],
       // Add React entry point
       input: [
           'resources/js/app.js',
           'resources/js/react-dashboard.js', // New entry
       ],
   });
   ```

3. **Create `resources/js/react-dashboard.js`:**
   ```javascript
   import React from 'react';
   import ReactDOM from 'react-dom/client';
   import App from './react-dashboard/App';
   
   const root = ReactDOM.createRoot(document.getElementById('react-dashboard-root'));
   root.render(<App />);
   ```

4. **Update the Blade template:**
   ```php
   @vite(['resources/js/react-dashboard.js'])
   ```

## Troubleshooting

### React app not loading
- Check browser console for errors
- Verify file paths in Blade template match actual build output
- Check that files are in `html/react-dashboard/` directory

### API calls failing
- Check CORS settings if React app is on different domain
- Verify API token is being passed correctly
- Check LibreNMS API documentation for correct endpoints

### Build path issues
- Update `publicPath` in React build config if assets aren't loading
- For Create React App: Set `"homepage": "/react-dashboard"` in `package.json`

## Next Steps

1. Share your React project codebase (see below)
2. We can help optimize the integration
3. We can help with API integration specifics
4. We can help with styling/theme integration

