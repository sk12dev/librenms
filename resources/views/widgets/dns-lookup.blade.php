@if($dns_records->isNotEmpty())
<div class="table-responsive">
    <table class="table table-hover table-condensed table-striped">
        <thead>
        <tr>
            <th class="text-left">{{ __('Domain') }}</th>
            <th class="text-left">{{ __('DNS Server') }}</th>
            <th class="text-left">{{ __('Resolved IP') }}</th>
            <th class="text-left">{{ __('Latency (ms)') }}</th>
            <th class="text-left">{{ __('Status') }}</th>
            <th class="text-left">{{ __('Last Checked') }}</th>
            @if($dns_records->whereNotNull('device_id')->isNotEmpty())
                <th class="text-left">{{ __('Device') }}</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($dns_records as $record)
            <tr>
                <td class="text-left">
                    <strong>{{ $record->domain }}</strong>
                </td>
                <td class="text-left">
                    <code>{{ $record->dns_server }}</code>
                </td>
                <td class="text-left">
                    @if($record->resolved_ip)
                        <code>{{ $record->resolved_ip }}</code>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->resolve_time_ms !== null)
                        @if($record->check_failed)
                            <span class="text-danger">-</span>
                        @elseif($record->resolve_time_ms > $slow_threshold)
                            <span class="text-warning"><strong>{{ number_format($record->resolve_time_ms, 2) }} ms</strong></span>
                        @else
                            <span class="text-success">{{ number_format($record->resolve_time_ms, 2) }} ms</span>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->check_failed)
                        <span class="label label-danger">{{ __('Failed') }}</span>
                        @if($record->error_message)
                            <br><small class="text-danger">{{ \Str::limit($record->error_message, 50) }}</small>
                        @endif
                    @else
                        <span class="label label-success">{{ __('OK') }}</span>
                    @endif
                </td>
                <td class="text-left">
                    @if($record->last_checked)
                        <small>{{ $record->last_checked->diffForHumans() }}</small>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                @if($dns_records->whereNotNull('device_id')->isNotEmpty())
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
    <h4>{{ __('No DNS lookup records found.') }}</h4>
    <p class="text-muted">{{ __('No DNS lookups match the current filter criteria.') }}</p>
@endif

