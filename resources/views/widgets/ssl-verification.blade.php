@if($ssl_records->isNotEmpty())
<div class="table-responsive">
    <table class="table table-hover table-condensed table-striped">
        <thead>
        <tr>
            <th class="text-left">{{ __('Domain') }}</th>
            <th class="text-left">{{ __('Status') }}</th>
            <th class="text-left">{{ __('Days Until Expires') }}</th>
            <th class="text-left">{{ __('Expiration Date') }}</th>
            <th class="text-left">{{ __('Issuer') }}</th>
            <th class="text-left">{{ __('Last Checked') }}</th>
            @if($ssl_records->whereNotNull('device_id')->isNotEmpty())
                <th class="text-left">{{ __('Device') }}</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($ssl_records as $record)
            <tr>
                <td class="text-left">
                    <strong>{{ $record->domain }}</strong>
                    @if($record->port != 443)
                        <span class="label label-default">:{{ $record->port }}</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->valid)
                        <span class="label label-success">{{ __('Valid') }}</span>
                    @else
                        <span class="label label-danger">{{ __('Invalid') }}</span>
                        @if($record->check_failed && $record->error_message)
                            <br><small class="text-danger">{{ \Str::limit($record->error_message, 50) }}</small>
                        @endif
                    @endif
                </td>
                <td class="text-left">
                    @if($record->days_until_expires !== null)
                        @if($record->days_until_expires < 0)
                            <span class="text-danger"><strong>{{ __('Expired') }} ({{ abs($record->days_until_expires) }} {{ __('days ago') }})</strong></span>
                        @elseif($record->days_until_expires <= 30)
                            <span class="text-warning"><strong>{{ $record->days_until_expires }} {{ __('days') }}</strong></span>
                        @else
                            <span class="text-success">{{ $record->days_until_expires }} {{ __('days') }}</span>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->valid_to)
                        {{ $record->valid_to->format('Y-m-d') }}
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->issuer)
                        <small>{{ \Str::limit($record->issuer, 40) }}</small>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->last_checked)
                        <small>{{ $record->last_checked->diffForHumans() }}</small>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                @if($ssl_records->whereNotNull('device_id')->isNotEmpty())
                    <td class="text-left">
                        @if($record->device_id && $record->device)
                            <x-device-link :device="$record->device">{{ $record->device->shortDisplayName() }}</x-device-link>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@else
    <h4>{{ __('No SSL verification records found.') }}</h4>
    <p class="text-muted">{{ __('No SSL certificates match the current filter criteria.') }}</p>
@endif

