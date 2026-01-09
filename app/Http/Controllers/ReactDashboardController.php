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
        
        // Read the built index.html to extract asset paths
        $indexHtmlPath = public_path('react-dashboard/index.html');
        $jsFile = null;
        $cssFile = null;
        
        if (file_exists($indexHtmlPath)) {
            $htmlContent = file_get_contents($indexHtmlPath);
            
            // Extract JS file path
            if (preg_match('/src=["\']([^"\']*\.js)["\']/', $htmlContent, $jsMatches)) {
                $jsFile = $jsMatches[1];
                // Convert absolute paths to relative paths
                // If it starts with /assets/, change to assets/ (relative to react-dashboard/)
                $jsFile = preg_replace('#^/assets/#', 'assets/', $jsFile);
                // If it starts with /react-dashboard/assets/, remove the leading /react-dashboard
                $jsFile = preg_replace('#^/react-dashboard/#', '', $jsFile);
            }
            
            // Extract CSS file path
            if (preg_match('/href=["\']([^"\']*\.css)["\']/', $htmlContent, $cssMatches)) {
                $cssFile = $cssMatches[1];
                // Convert absolute paths to relative paths
                $cssFile = preg_replace('#^/assets/#', 'assets/', $cssFile);
                $cssFile = preg_replace('#^/react-dashboard/#', '', $cssFile);
            }
        }
        
        // If no API token, provide helpful message
        if (!$apiToken) {
            \Log::warning('React Dashboard: User ' . $user->username . ' does not have an API token');
        }
        
        // Get base URL for API calls (without /api/v0, the React app will add that)
        $baseUrl = url('/');
        
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

