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
        window.ReactDashboardConfig = {
            apiToken: {!! json_encode($apiToken) !!},
            apiUrl: {!! json_encode($apiUrl) !!},
            baseUrl: {!! json_encode($baseUrl) !!},
            csrfToken: {!! json_encode(csrf_token()) !!},
        };
        console.log('[Blade Template] ReactDashboardConfig set:', {
            apiUrl: window.ReactDashboardConfig.apiUrl,
            apiToken: window.ReactDashboardConfig.apiToken ? window.ReactDashboardConfig.apiToken.substring(0, 8) + '...' : 'NULL',
            baseUrl: window.ReactDashboardConfig.baseUrl,
        });
    </script>
@endsection

@section('content')
<div class="container-fluid" style="padding: 0;">
    {{-- React Dashboard Container with config in data attributes as backup --}}
    <div 
        id="react-dashboard-root"
        data-api-token="{{ $apiToken }}"
        data-api-url="{{ $apiUrl }}"
        data-base-url="{{ $baseUrl }}"
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

