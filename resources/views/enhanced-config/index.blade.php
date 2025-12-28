@extends('layouts.librenmsv1')

@section('title', __('Enhanced Configuration'))

@section('content')
    <div class="container-fluid">
        <x-panel id="enhanced-config-panel">
            <x-slot name="title">
                <i class="fa fa-cog fa-fw fa-lg" aria-hidden="true"></i> {{ __('Enhanced Configuration') }}
            </x-slot>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#dns-servers" aria-controls="dns-servers" role="tab" data-toggle="tab">
                        <i class="fa fa-server"></i> {{ __('DNS Servers') }}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#dns-domains" aria-controls="dns-domains" role="tab" data-toggle="tab">
                        <i class="fa fa-globe"></i> {{ __('DNS Domains') }}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#ssl-verifications" aria-controls="ssl-verifications" role="tab" data-toggle="tab">
                        <i class="fa fa-lock"></i> {{ __('SSL Verifications') }}
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" style="padding-top: 15px;">
                <!-- DNS Servers Tab -->
                <div role="tabpanel" class="tab-pane active" id="dns-servers">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#dnsServerModal" data-action="create">
                                <i class="fa fa-plus"></i> {{ __('Add DNS Server') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="margin-top: 15px;">
                        <table id="dns-servers-table" class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th>{{ __('DNS Server') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Enabled') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($dns_servers as $dns_server)
                                <tr>
                                    <td>{{ $dns_server->dns_server }}</td>
                                    <td>{{ $dns_server->description ?? '-' }}</td>
                                    <td>{{ $dns_server->priority }}</td>
                                    <td>
                                        @if($dns_server->enabled)
                                            <span class="label label-success">{{ __('Yes') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#dnsServerModal" data-action="edit" data-id="{{ $dns_server->dns_server_id }}" data-server="{{ $dns_server->dns_server }}" data-description="{{ $dns_server->description }}" data-priority="{{ $dns_server->priority }}" data-enabled="{{ $dns_server->enabled }}">
                                            <i class="fa fa-pencil"></i> {{ __('Edit') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteDnsServer({{ $dns_server->dns_server_id }}, '{{ $dns_server->dns_server }}')">
                                            <i class="fa fa-trash"></i> {{ __('Delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DNS Domains Tab -->
                <div role="tabpanel" class="tab-pane" id="dns-domains">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#dnsDomainModal" data-action="create">
                                <i class="fa fa-plus"></i> {{ __('Add DNS Domain') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="margin-top: 15px;">
                        <table id="dns-domains-table" class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th>{{ __('Domain') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Device') }}</th>
                                <th>{{ __('Enabled') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($dns_domains as $dns_domain)
                                <tr>
                                    <td>{{ $dns_domain->domain }}</td>
                                    <td>{{ $dns_domain->description ?? '-' }}</td>
                                    <td>
                                        @if($dns_domain->device_id && $dns_domain->device)
                                            <a href="{{ url('device=' . $dns_domain->device_id) }}">{{ $dns_domain->device->hostname }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($dns_domain->enabled)
                                            <span class="label label-success">{{ __('Yes') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#dnsDomainModal" data-action="edit" data-id="{{ $dns_domain->dns_domain_id }}" data-domain="{{ $dns_domain->domain }}" data-description="{{ $dns_domain->description }}" data-device-id="{{ $dns_domain->device_id }}" data-enabled="{{ $dns_domain->enabled }}">
                                            <i class="fa fa-pencil"></i> {{ __('Edit') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteDnsDomain({{ $dns_domain->dns_domain_id }}, '{{ $dns_domain->domain }}')">
                                            <i class="fa fa-trash"></i> {{ __('Delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SSL Verifications Tab -->
                <div role="tabpanel" class="tab-pane" id="ssl-verifications">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sslVerificationModal" data-action="create">
                                <i class="fa fa-plus"></i> {{ __('Add SSL Verification') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="margin-top: 15px;">
                        <table id="ssl-verifications-table" class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th>{{ __('Domain') }}</th>
                                <th>{{ __('Port') }}</th>
                                <th>{{ __('Device') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Valid') }}</th>
                                <th>{{ __('Days Until Expires') }}</th>
                                <th>{{ __('Last Checked') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($ssl_verifications as $ssl_verification)
                                <tr>
                                    <td>{{ $ssl_verification->domain }}</td>
                                    <td>{{ $ssl_verification->port ?? 443 }}</td>
                                    <td>
                                        @if($ssl_verification->device_id && $ssl_verification->device)
                                            <a href="{{ url('device=' . $ssl_verification->device_id) }}">{{ $ssl_verification->device->hostname }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($ssl_verification->enabled)
                                            <span class="label label-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ssl_verification->valid)
                                            <span class="label label-success">{{ __('Valid') }}</span>
                                        @else
                                            <span class="label label-danger">{{ __('Invalid') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ssl_verification->days_until_expires !== null)
                                            @if($ssl_verification->days_until_expires <= 30)
                                                <span class="text-danger"><strong>{{ $ssl_verification->days_until_expires }}</strong></span>
                                            @elseif($ssl_verification->days_until_expires <= 60)
                                                <span class="text-warning"><strong>{{ $ssl_verification->days_until_expires }}</strong></span>
                                            @else
                                                <span class="text-success">{{ $ssl_verification->days_until_expires }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ssl_verification->last_checked)
                                            <small>{{ $ssl_verification->last_checked->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#sslVerificationModal" data-action="edit" data-id="{{ $ssl_verification->ssl_verification_id }}" data-domain="{{ $ssl_verification->domain }}" data-port="{{ $ssl_verification->port ?? 443 }}" data-device-id="{{ $ssl_verification->device_id }}" data-enabled="{{ $ssl_verification->enabled }}" data-alert-on-expiring="{{ $ssl_verification->alert_on_expiring }}" data-alert-days-before="{{ $ssl_verification->alert_days_before }}">
                                            <i class="fa fa-pencil"></i> {{ __('Edit') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteSslVerification({{ $ssl_verification->ssl_verification_id }}, '{{ $ssl_verification->domain }}')">
                                            <i class="fa fa-trash"></i> {{ __('Delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-panel>
    </div>

    <!-- DNS Server Modal -->
    <div class="modal fade" id="dnsServerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="dnsServerForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="dnsServerModalTitle">{{ __('Add DNS Server') }}</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="dns_server_id" id="dnsServerId">
                        <div class="form-group">
                            <label for="dnsServer">{{ __('DNS Server IP') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dnsServer" name="dns_server" placeholder="8.8.8.8" required>
                        </div>
                        <div class="form-group">
                            <label for="dnsServerDescription">{{ __('Description') }}</label>
                            <input type="text" class="form-control" id="dnsServerDescription" name="description" placeholder="{{ __('Optional description') }}">
                        </div>
                        <div class="form-group">
                            <label for="dnsServerPriority">{{ __('Priority') }}</label>
                            <input type="number" class="form-control" id="dnsServerPriority" name="priority" value="0" min="0">
                            <small class="help-block">{{ __('Lower number = higher priority') }}</small>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="dnsServerEnabled" name="enabled" value="1" checked>
                                {{ __('Enabled') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DNS Domain Modal -->
    <div class="modal fade" id="dnsDomainModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="dnsDomainForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="dnsDomainModalTitle">{{ __('Add DNS Domain') }}</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="dns_domain_id" id="dnsDomainId">
                        <div class="form-group">
                            <label for="dnsDomain">{{ __('Domain') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dnsDomain" name="domain" placeholder="example.com" required>
                            <small class="help-block">{{ __('Enter domain name (e.g., example.com or https://www.example.com)') }}</small>
                        </div>
                        <div class="form-group">
                            <label for="dnsDomainDescription">{{ __('Description') }}</label>
                            <input type="text" class="form-control" id="dnsDomainDescription" name="description" placeholder="{{ __('Optional description') }}">
                        </div>
                        <div class="form-group">
                            <label for="dnsDomainDeviceId">{{ __('Device') }}</label>
                            <select class="form-control" id="dnsDomainDeviceId" name="device_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach(\App\Models\Device::orderBy('hostname')->get() as $device)
                                    <option value="{{ $device->device_id }}">{{ $device->hostname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="dnsDomainEnabled" name="enabled" value="1" checked>
                                {{ __('Enabled') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SSL Verification Modal -->
    <div class="modal fade" id="sslVerificationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="sslVerificationForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="sslVerificationModalTitle">{{ __('Add SSL Verification') }}</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="ssl_verification_id" id="sslVerificationId">
                        <div class="form-group">
                            <label for="sslVerificationDomain">{{ __('Domain') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sslVerificationDomain" name="domain" placeholder="example.com" required>
                            <small class="help-block">{{ __('Enter domain name (e.g., example.com or https://www.example.com)') }}</small>
                        </div>
                        <div class="form-group">
                            <label for="sslVerificationPort">{{ __('Port') }}</label>
                            <input type="number" class="form-control" id="sslVerificationPort" name="port" value="443" min="1" max="65535">
                            <small class="help-block">{{ __('Default: 443 for HTTPS') }}</small>
                        </div>
                        <div class="form-group">
                            <label for="sslVerificationDeviceId">{{ __('Device') }}</label>
                            <select class="form-control" id="sslVerificationDeviceId" name="device_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach(\App\Models\Device::orderBy('hostname')->get() as $device)
                                    <option value="{{ $device->device_id }}">{{ $device->hostname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="sslVerificationEnabled" name="enabled" value="1" checked>
                                {{ __('Enabled') }}
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="sslVerificationAlertOnExpiring" name="alert_on_expiring" value="1" checked>
                                {{ __('Alert on Expiring') }}
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="sslVerificationAlertDaysBefore">{{ __('Alert Days Before Expiration') }}</label>
                            <input type="number" class="form-control" id="sslVerificationAlertDaysBefore" name="alert_days_before" value="30" min="1">
                            <small class="help-block">{{ __('Number of days before expiration to send alert') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // DNS Server Modal
    $('#dnsServerModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        var modal = $(this);
        
        if (action === 'edit') {
            modal.find('#dnsServerModalTitle').text('{{ __('Edit DNS Server') }}');
            modal.find('#dnsServerForm').attr('action', '{{ route("enhanced-config.dns-server.update", ["dnsServer" => ":id"]) }}'.replace(':id', button.data('id')));
            modal.find('#dnsServerForm').append('<input type="hidden" name="_method" value="PUT">');
            modal.find('#dnsServerId').val(button.data('id'));
            modal.find('#dnsServer').val(button.data('server'));
            modal.find('#dnsServerDescription').val(button.data('description') || '');
            modal.find('#dnsServerPriority').val(button.data('priority') || 0);
            modal.find('#dnsServerEnabled').prop('checked', button.data('enabled') == 1);
        } else {
            modal.find('#dnsServerModalTitle').text('{{ __('Add DNS Server') }}');
            modal.find('#dnsServerForm').attr('action', '{{ route("enhanced-config.dns-server.store") }}');
            modal.find('#dnsServerForm input[name="_method"]').remove();
            modal.find('#dnsServerForm')[0].reset();
            modal.find('#dnsServerId').val('');
            modal.find('#dnsServerEnabled').prop('checked', true);
        }
    });

    // DNS Domain Modal
    $('#dnsDomainModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        var modal = $(this);
        
        if (action === 'edit') {
            modal.find('#dnsDomainModalTitle').text('{{ __('Edit DNS Domain') }}');
            modal.find('#dnsDomainForm').attr('action', '{{ route("enhanced-config.dns-domain.update", ["dnsDomain" => ":id"]) }}'.replace(':id', button.data('id')));
            modal.find('#dnsDomainForm').append('<input type="hidden" name="_method" value="PUT">');
            modal.find('#dnsDomainId').val(button.data('id'));
            modal.find('#dnsDomain').val(button.data('domain'));
            modal.find('#dnsDomainDescription').val(button.data('description') || '');
            modal.find('#dnsDomainDeviceId').val(button.data('device-id') || '');
            modal.find('#dnsDomainEnabled').prop('checked', button.data('enabled') == 1);
        } else {
            modal.find('#dnsDomainModalTitle').text('{{ __('Add DNS Domain') }}');
            modal.find('#dnsDomainForm').attr('action', '{{ route("enhanced-config.dns-domain.store") }}');
            modal.find('#dnsDomainForm input[name="_method"]').remove();
            modal.find('#dnsDomainForm')[0].reset();
            modal.find('#dnsDomainId').val('');
            modal.find('#dnsDomainEnabled').prop('checked', true);
        }
    });

    function deleteDnsServer(id, server) {
        if (confirm('{{ __('Are you sure you want to delete DNS server') }}: ' + server + '?')) {
            $.ajax({
                url: '{{ route("enhanced-config.dns-server.destroy", ["dnsServer" => ":id"]) }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('{{ __('Error deleting DNS server') }}');
                }
            });
        }
    }

    function deleteDnsDomain(id, domain) {
        if (confirm('{{ __('Are you sure you want to delete DNS domain') }}: ' + domain + '?')) {
            $.ajax({
                url: '{{ route("enhanced-config.dns-domain.destroy", ["dnsDomain" => ":id"]) }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('{{ __('Error deleting DNS domain') }}');
                }
            });
        }
    }

    // SSL Verification Modal
    $('#sslVerificationModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        var modal = $(this);
        
        if (action === 'edit') {
            modal.find('#sslVerificationModalTitle').text('{{ __('Edit SSL Verification') }}');
            modal.find('#sslVerificationForm').attr('action', '{{ route("enhanced-config.ssl-verification.update", ["sslVerification" => ":id"]) }}'.replace(':id', button.data('id')));
            modal.find('#sslVerificationForm').append('<input type="hidden" name="_method" value="PUT">');
            modal.find('#sslVerificationId').val(button.data('id'));
            modal.find('#sslVerificationDomain').val(button.data('domain'));
            modal.find('#sslVerificationPort').val(button.data('port') || 443);
            modal.find('#sslVerificationDeviceId').val(button.data('device-id') || '');
            modal.find('#sslVerificationEnabled').prop('checked', button.data('enabled') == 1);
            modal.find('#sslVerificationAlertOnExpiring').prop('checked', button.data('alert-on-expiring') == 1);
            modal.find('#sslVerificationAlertDaysBefore').val(button.data('alert-days-before') || 30);
        } else {
            modal.find('#sslVerificationModalTitle').text('{{ __('Add SSL Verification') }}');
            modal.find('#sslVerificationForm').attr('action', '{{ route("enhanced-config.ssl-verification.store") }}');
            modal.find('#sslVerificationForm input[name="_method"]').remove();
            modal.find('#sslVerificationForm')[0].reset();
            modal.find('#sslVerificationId').val('');
            modal.find('#sslVerificationPort').val(443);
            modal.find('#sslVerificationEnabled').prop('checked', true);
            modal.find('#sslVerificationAlertOnExpiring').prop('checked', true);
            modal.find('#sslVerificationAlertDaysBefore').val(30);
        }
    });

    function deleteSslVerification(id, domain) {
        if (confirm('{{ __('Are you sure you want to delete SSL verification for') }}: ' + domain + '?')) {
            $.ajax({
                url: '{{ route("enhanced-config.ssl-verification.destroy", ["sslVerification" => ":id"]) }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('{{ __('Error deleting SSL verification') }}');
                }
            });
        }
    }
</script>
@endpush

