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
</script>
@endpush

