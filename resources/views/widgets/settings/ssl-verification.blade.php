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
            <option value="valid" {{ $filter == 'valid' ? 'selected' : '' }}>{{ __('Valid Only') }}</option>
            <option value="invalid" {{ $filter == 'invalid' ? 'selected' : '' }}>{{ __('Invalid Only') }}</option>
            <option value="expiring" {{ $filter == 'expiring' ? 'selected' : '' }}>{{ __('Expiring Soon') }}</option>
        </select>
    </div>
    <div class="form-group" id="expiring_days_group-{{ $id }}" style="{{ $filter == 'expiring' ? '' : 'display:none;' }}">
        <label for="expiring_days-{{ $id }}" class="control-label">{{ __('Expiring within (days)') }}:</label>
        <input class="form-control" type="number" min="1" step="1" name="expiring_days" id="expiring_days-{{ $id }}" value="{{ $expiring_days }}">
    </div>
    <div class="form-group">
        <label for="sort_by-{{ $id }}" class="control-label">{{ __('Sort by') }}:</label>
        <select class="form-control" name="sort_by" id="sort_by-{{ $id }}">
            <option value="domain" {{ $sort_by == 'domain' ? 'selected' : '' }}>{{ __('Domain') }}</option>
            <option value="days_until_expires" {{ $sort_by == 'days_until_expires' ? 'selected' : '' }}>{{ __('Days Until Expires') }}</option>
            <option value="last_checked" {{ $sort_by == 'last_checked' ? 'selected' : '' }}>{{ __('Last Checked') }}</option>
            <option value="valid_to" {{ $sort_by == 'valid_to' ? 'selected' : '' }}>{{ __('Expiration Date') }}</option>
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
            var expiringDaysGroup = $('#expiring_days_group-{{ $id }}');
            
            filterSelect.on('change', function() {
                if ($(this).val() === 'expiring') {
                    expiringDaysGroup.show();
                } else {
                    expiringDaysGroup.hide();
                }
            });
        })();
    </script>
@endsection

