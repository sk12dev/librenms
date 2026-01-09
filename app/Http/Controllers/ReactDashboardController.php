<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/**
 * Controller for React Dashboard integration
 * 
 * This controller serves a page that loads your React dashboard application.
 * The React app should be built and placed in the html/react-dashboard/ directory.
 */
class ReactDashboardController extends Controller
{
    /**
     * Display the React dashboard page
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        // Get the first enabled API token for the user
        $apiToken = $user->apiTokens()
            ->where('disabled', 0)
            ->first()
            ?->token_hash ?? null;
        
        // If no API token exists, create one automatically
        if (!$apiToken) {
            try {
                $newToken = \App\Models\ApiToken::generateToken($user, 'React Dashboard Auto-generated');
                $apiToken = $newToken->token_hash;
                \Log::info('React Dashboard: Auto-generated API token for user ' . $user->username);
            } catch (\Exception $e) {
                \Log::error('React Dashboard: Failed to generate API token: ' . $e->getMessage());
            }
        }
        
        // Get asset paths - check for Vite manifest first, then fallback to known paths
        $manifestPath = public_path('react-dashboard/.vite/manifest.json');
        $jsFile = null;
        $cssFile = null;
        
        if (file_exists($manifestPath)) {
            // Read Vite manifest to get actual asset filenames (with hashes)
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest && isset($manifest['index.html'])) {
                $indexEntry = $manifest['index.html'];
                if (isset($indexEntry['file'])) {
                    $jsFile = $indexEntry['file'];
                }
                if (isset($indexEntry['css']) && is_array($indexEntry['css']) && !empty($indexEntry['css'])) {
                    $cssFile = $indexEntry['css'][0];
                }
            }
        }
        
        // Fallback to known paths if manifest doesn't exist
        if (!$jsFile) {
            // Look for any JS file in assets directory
            $assetsDir = public_path('react-dashboard/assets');
            if (is_dir($assetsDir)) {
                $jsFiles = glob($assetsDir . '/index*.js');
                if (!empty($jsFiles)) {
                    $jsFile = 'assets/' . basename($jsFiles[0]);
                }
            }
        }
        
        if (!$cssFile) {
            // Look for any CSS file in assets directory
            $assetsDir = public_path('react-dashboard/assets');
            if (is_dir($assetsDir)) {
                $cssFiles = glob($assetsDir . '/index*.css');
                if (!empty($cssFiles)) {
                    $cssFile = 'assets/' . basename($cssFiles[0]);
                }
            }
        }
        
        // If no API token, provide helpful message
        if (!$apiToken) {
            \Log::warning('React Dashboard: User ' . $user->username . ' does not have an API token');
        }
        
        // Get base URL for API calls (without /api/v0, the React app will add that)
        $baseUrl = url('/');
        
        // Debug logging
        \Log::info('React Dashboard Config:', [
            'apiToken' => $apiToken ? substr($apiToken, 0, 8) . '...' : 'NULL',
            'apiUrl' => $baseUrl,
            'baseUrl' => $baseUrl,
            'jsFile' => $jsFile,
            'cssFile' => $cssFile,
        ]);
        
        return view('react-dashboard.index', [
            'pagetitle' => 'React Dashboard',
            'show_menu' => true,
            // Pass any initial data your React app needs
            'apiToken' => $apiToken,
            'apiUrl' => $baseUrl, // Base URL, React will append /api/v0
            'baseUrl' => $baseUrl,
            'jsFile' => $jsFile,
            'cssFile' => $cssFile,
        ]);
    }
}

