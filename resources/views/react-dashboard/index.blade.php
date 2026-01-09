@extends('layouts.librenmsv1')

@section('title', $pagetitle)

@section('css')
    @if($cssFile)
        <link rel="stylesheet" href="{{ asset('react-dashboard/' . $cssFile) }}">
    @endif
@endsection

@section('javascript')
    {{-- Set config in head to ensure it's available before module loads --}}
    <script>
        (function() {
            window.ReactDashboardConfig = {
                apiToken: {!! json_encode($apiToken) !!},
                apiUrl: {!! json_encode($apiUrl) !!},
                baseUrl: {!! json_encode($baseUrl) !!},
                csrfToken: {!! json_encode(csrf_token()) !!},
            };
            console.log('[Blade Template] ReactDashboardConfig set in head:', {
                apiUrl: window.ReactDashboardConfig.apiUrl,
                apiToken: window.ReactDashboardConfig.apiToken ? window.ReactDashboardConfig.apiToken.substring(0, 8) + '...' : 'NULL/UNDEFINED',
                baseUrl: window.ReactDashboardConfig.baseUrl,
                hasApiToken: !!window.ReactDashboardConfig.apiToken,
                hasApiUrl: !!window.ReactDashboardConfig.apiUrl,
                rawApiToken: window.ReactDashboardConfig.apiToken,
            });
        })();
    </script>
@endsection

@section('content')
<div class="container-fluid" style="padding: 0;">
    {{-- React Dashboard Container --}}
    <div id="react-dashboard-root"></div>
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

