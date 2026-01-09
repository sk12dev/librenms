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
    {{-- Pass configuration to React app --}}
    <script>
        window.ReactDashboardConfig = {
            apiToken: @json($apiToken),
            apiUrl: @json($apiUrl),
            baseUrl: @json($baseUrl),
            csrfToken: @json(csrf_token()),
        };
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

