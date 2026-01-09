@extends('layouts.librenmsv1')

@section('title', $pagetitle)

@section('css')
    @if($cssFile)
        <link rel="stylesheet" href="{{ asset('react-dashboard/' . $cssFile) }}">
    @endif
@endsection

@section('content')
<div class="container-fluid" style="padding: 0;">
    {{-- React Dashboard Container --}}
    <div id="react-dashboard-root"></div>
</div>
@endsection

@section('scripts')
    {{-- Pass configuration to React app - MUST be loaded before the module script --}}
    <script>
        window.ReactDashboardConfig = {
            apiToken: @json($apiToken),
            apiUrl: @json($apiUrl),
            baseUrl: @json($baseUrl),
            csrfToken: @json(csrf_token()),
        };
        console.log('[Blade Template] ReactDashboardConfig set:', {
            apiUrl: window.ReactDashboardConfig.apiUrl,
            apiToken: window.ReactDashboardConfig.apiToken ? window.ReactDashboardConfig.apiToken.substring(0, 8) + '...' : 'NOT SET',
            baseUrl: window.ReactDashboardConfig.baseUrl,
        });
    </script>
    
    {{-- Load React dashboard assets --}}
    @if($jsFile)
        <script type="module" src="{{ asset('react-dashboard/' . $jsFile) }}"></script>
    @else
        <script>
            console.error('React Dashboard JS file not found. Please run the build script.');
        </script>
    @endif
@endsection

