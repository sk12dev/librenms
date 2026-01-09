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
        
        return view('react-dashboard.index', [
            'pagetitle' => 'React Dashboard',
            'show_menu' => true,
            // Pass any initial data your React app needs
            'apiToken' => $apiToken,
            'apiUrl' => url('/api/v0'),
            'baseUrl' => url('/'),
            'jsFile' => $jsFile,
            'cssFile' => $cssFile,
        ]);
    }
}

