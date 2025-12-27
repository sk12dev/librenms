@extends('widgets.settings.base')

@section('form')
    <div class="form-group">
        <label for="count-{{ $id }}" class="control-label">{{ __('Number of records') }}:</label>
        <input class="form-control" type="number" min="1" step="1" name="count" id="count-{{ $id }}" value="{{ $count }}">
    </div>
    <div class="form-group">
        <label for="filter-{{ $id }}" class="control-label">{{ __('Filter') }}:</label>
        <select class="form-control" name="filter" id="filter-{{ $id }}">
            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
            <option value="failed" {{ $filter == 'failed' ? 'selected' : '' }}>{{ __('Failed Only') }}</option>
            <option value="slow" {{ $filter == 'slow' ? 'selected' : '' }}>{{ __('Slow Only') }}</option>
        </select>
    </div>
    <div class="form-group" id="slow_threshold_group-{{ $id }}" style="{{ $filter == 'slow' ? '' : 'display:none;' }}">
        <label for="slow_threshold-{{ $id }}" class="control-label">{{ __('Slow threshold (ms)') }}:</label>
        <input class="form-control" type="number" min="0" step="0.01" name="slow_threshold" id="slow_threshold-{{ $id }}" value="{{ $slow_threshold }}">
    </div>
    <div class="form-group">
        <label for="sort_by-{{ $id }}" class="control-label">{{ __('Sort by') }}:</label>
        <select class="form-control" name="sort_by" id="sort_by-{{ $id }}">
            <option value="domain" {{ $sort_by == 'domain' ? 'selected' : '' }}>{{ __('Domain') }}</option>
            <option value="dns_server" {{ $sort_by == 'dns_server' ? 'selected' : '' }}>{{ __('DNS Server') }}</option>
            <option value="resolve_time_ms" {{ $sort_by == 'resolve_time_ms' ? 'selected' : '' }}>{{ __('Latency') }}</option>
            <option value="last_checked" {{ $sort_by == 'last_checked' ? 'selected' : '' }}>{{ __('Last Checked') }}</option>
        </select>
    </div>
    <div class="form-group">
        <label for="sort_order-{{ $id }}" class="control-label">{{ __('Sort order') }}:</label>
        <select class="form-control" name="sort_order" id="sort_order-{{ $id }}">
            <option value="asc" {{ $sort_order == 'asc' ? 'selected' : '' }}>{{ __('Ascending') }}</option>
            <option value="desc" {{ $sort_order == 'desc' ? 'selected' : '' }}>{{ __('Descending') }}</option>
        </select>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        (function() {
            var filterSelect = $('#filter-{{ $id }}');
            var slowThresholdGroup = $('#slow_threshold_group-{{ $id }}');
            
            filterSelect.on('change', function() {
                if ($(this).val() === 'slow') {
                    slowThresholdGroup.show();
                } else {
                    slowThresholdGroup.hide();
                }
            });
        })();
    </script>
@endsection

