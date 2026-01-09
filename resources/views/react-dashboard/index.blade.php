@extends('layouts.librenmsv1')

@section('title', $pagetitle)

@section('css')
    @if($cssFile)
        <link rel="stylesheet" href="{{ asset('react-dashboard/' . $cssFile) }}">
    @endif
@endsection

@section('javascript')
    {{-- Set config in head AND window to ensure it's available --}}
    <script>
        (function() {
            var apiToken = {!! json_encode($apiToken) !!};
            var apiUrl = {!! json_encode($apiUrl) !!};
            var baseUrl = {!! json_encode($baseUrl) !!};
            
            console.log('[Blade Template] Raw values from PHP:', {
                apiToken: apiToken ? apiToken.substring(0, 8) + '...' : 'NULL/EMPTY',
                apiUrl: apiUrl || 'NULL/EMPTY',
                baseUrl: baseUrl || 'NULL/EMPTY',
            });
            
            window.ReactDashboardConfig = {
                apiToken: apiToken,
                apiUrl: apiUrl,
                baseUrl: baseUrl,
                csrfToken: {!! json_encode(csrf_token()) !!},
            };
            
            console.log('[Blade Template] ReactDashboardConfig set:', {
                apiUrl: window.ReactDashboardConfig.apiUrl,
                apiToken: window.ReactDashboardConfig.apiToken ? window.ReactDashboardConfig.apiToken.substring(0, 8) + '...' : 'NULL',
                baseUrl: window.ReactDashboardConfig.baseUrl,
                hasApiToken: !!window.ReactDashboardConfig.apiToken,
                hasApiUrl: !!window.ReactDashboardConfig.apiUrl,
            });
        })();
    </script>
@endsection

@section('content')
<div class="container-fluid" style="padding: 0;">
    {{-- React Dashboard Container with config in data attributes as backup --}}
    <div 
        id="react-dashboard-root"
        data-api-token="{{ $apiToken ?: '' }}"
        data-api-url="{{ $apiUrl ?: '' }}"
        data-base-url="{{ $baseUrl ?: '' }}"
    ></div>
</div>
@endsection

@section('scripts')
    {{-- Load React dashboard assets --}}
    @if($jsFile)
        <script type="module" src="{{ asset('react-dashboard/' . $jsFile) }}"></script>
    @else
        <script>
            console.error('React Dashboard JS file not found. Please run the build script.');
        </script>
    @endif
@endsection

