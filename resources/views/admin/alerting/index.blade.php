@extends('layouts.app')

@section('title', 'Alerting Configuration')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Alerting Configuration</h1>
            <p class="mt-2 text-gray-600">Configure external alerting integrations (Slack, PagerDuty, Email)</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.alerting.history') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                View History
            </a>
            <a href="{{ route('admin.system-health.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                System Health
            </a>
        </div>
    </div>

    <!-- Global Status -->
    <div class="mb-6 p-4 rounded-lg {{ $alertingEnabled ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if($alertingEnabled)
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                @else
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                @endif
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium {{ $alertingEnabled ? 'text-green-800' : 'text-red-800' }}">
                    Alerting is {{ $alertingEnabled ? 'Enabled' : 'Disabled' }}
                </h3>
                <p class="mt-1 text-sm {{ $alertingEnabled ? 'text-green-700' : 'text-red-700' }}">
                    {{ $alertingEnabled ? 'Alerts will be sent according to your configuration.' : 'Set ALERTS_ENABLED=true in .env to enable alerting.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Total Alerts (30d)</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $statistics['total_alerts'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Sent Alerts</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ $statistics['sent_alerts'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Failed Alerts</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ $statistics['failed_alerts'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Avg Resolution</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">
                {{ $statistics['average_resolution_time'] ? round($statistics['average_resolution_time']) . 'm' : 'N/A' }}
            </p>
        </div>
    </div>

    <!-- Integrations -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Integrations</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Slack Integration -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-purple-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zm1.271 0a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zm0 1.271a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zm10.124 2.521a2.528 2.528 0 0 1 2.52-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.52V8.834zm-1.271 0a2.528 2.528 0 0 1-2.521 2.521 2.528 2.528 0 0 1-2.521-2.521V2.522A2.528 2.528 0 0 1 15.166 0a2.528 2.528 0 0 1 2.521 2.522v6.312zm-2.521 10.124a2.528 2.528 0 0 1 2.521 2.52A2.528 2.528 0 0 1 15.166 24a2.528 2.528 0 0 1-2.521-2.522v-2.52h2.521zm0-1.271a2.528 2.528 0 0 1-2.521-2.521 2.528 2.528 0 0 1 2.521-2.521h6.312A2.528 2.528 0 0 1 24 15.166a2.528 2.528 0 0 1-2.522 2.521h-6.312z"/>
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900">Slack</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $integrations['slack']->enabled ?? false ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $integrations['slack']->enabled ?? false ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Send alerts to Slack channels with rich formatting</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        @if(isset($integrations['slack']) && $integrations['slack']->last_used_at)
                        Last used: {{ $integrations['slack']->last_used_at->diffForHumans() }}
                        @else
                        Never used
                        @endif
                    </span>
                    <button type="button" onclick="openSlackModal()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Configure
                    </button>
                </div>
            </div>

            <!-- PagerDuty Integration -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16.965 1.18C15.085.164 13.769 0 10.683 0H3.73v14.55h6.926c2.743 0 4.8-.164 6.678-1.152 2.003-1.044 3.31-3.077 3.31-5.544 0-2.466-1.333-5.6-3.679-6.674zm-5.59 10.326H7.333V3.3l3.988-.027c2.99 0 5.33.88 5.33 4.12-.001 3.457-2.282 4.113-5.276 4.113zM3.73 17.61h3.604V24H3.73z"/>
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900">PagerDuty</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $integrations['pagerduty']->enabled ?? false ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $integrations['pagerduty']->enabled ?? false ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Trigger PagerDuty incidents for critical alerts</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        @if(isset($integrations['pagerduty']) && $integrations['pagerduty']->last_used_at)
                        Last used: {{ $integrations['pagerduty']->last_used_at->diffForHumans() }}
                        @else
                        Never used
                        @endif
                    </span>
                    <button type="button" onclick="openPagerDutyModal()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Configure
                    </button>
                </div>
            </div>

            <!-- Email Integration -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900">Email</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $integrations['email']->enabled ?? false ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $integrations['email']->enabled ?? false ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Send email notifications to administrators</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        @if(isset($integrations['email']) && $integrations['email']->last_used_at)
                        Last used: {{ $integrations['email']->last_used_at->diffForHumans() }}
                        @else
                        Never used
                        @endif
                    </span>
                    <button type="button" onclick="openEmailModal()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Configure
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Configurations -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Alert Rules</h2>
            <div class="flex items-center space-x-3">
                <button type="button" onclick="seedDefaults()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Seed Defaults
                </button>
                <button type="button" onclick="openAddConfigModal()" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Rule
                </button>
            </div>
        </div>

        @if($configurations->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thresholds</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channels</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cooldown</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($configurations as $config)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $config->display_name }}</div>
                            <div class="text-sm text-gray-500">{{ $config->metric_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-yellow-600">Warning: {{ $config->warning_threshold ?? 'N/A' }}</div>
                            <div class="text-sm text-red-600">Critical: {{ $config->critical_threshold ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $config->comparison }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-1">
                                @if($config->slack_enabled)
                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">Slack</span>
                                @endif
                                @if($config->pagerduty_enabled)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">PD</span>
                                @endif
                                @if($config->email_enabled)
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">Email</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $config->cooldown_minutes }}m
                            @if($config->quiet_hours_enabled)
                            <span class="text-xs text-gray-400">(quiet hours)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $config->enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $config->enabled ? 'Active' : 'Muted' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" onclick="toggleMute({{ $config->id }})" class="text-gray-600 hover:text-gray-900 mr-3">
                                {{ $config->enabled ? 'Mute' : 'Unmute' }}
                            </button>
                            <button type="button" onclick="editConfig({{ $config->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </button>
                            <button type="button" onclick="deleteConfig({{ $config->id }})" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <p class="text-gray-500 mb-4">No alert configurations found.</p>
            <button type="button" onclick="seedDefaults()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                Seed Default Configurations
            </button>
        </div>
        @endif
    </div>
</div>

<!-- Slack Modal -->
<div id="slackModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeSlackModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="slackForm" onsubmit="saveSlack(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Slack Integration</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="slack_enabled" name="enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ ($integrations['slack']->enabled ?? false) ? 'checked' : '' }}>
                            <label for="slack_enabled" class="ml-2 block text-sm text-gray-900">Enable Slack Integration</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Webhook URL</label>
                            <input type="url" name="webhook_url_default" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['slack']->getConfigValue('webhooks')['default'] ?? '' }}" placeholder="https://hooks.slack.com/services/...">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Critical Alerts Webhook URL</label>
                            <input type="url" name="webhook_url_critical" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['slack']->getConfigValue('webhooks')['critical'] ?? '' }}" placeholder="https://hooks.slack.com/services/...">
                            <p class="mt-1 text-xs text-gray-500">For #incidents channel</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Warnings Webhook URL</label>
                            <input type="url" name="webhook_url_warnings" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['slack']->getConfigValue('webhooks')['warnings'] ?? '' }}" placeholder="https://hooks.slack.com/services/...">
                            <p class="mt-1 text-xs text-gray-500">For #monitoring channel</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Channel</label>
                            <input type="text" name="default_channel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['slack']->getConfigValue('default_channel') ?? '#monitoring' }}" placeholder="#monitoring">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mention on Critical</label>
                            <input type="text" name="mention_on_critical" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['slack']->getConfigValue('mention_on_critical') ?? '@channel' }}" placeholder="@channel">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" onclick="testSlack()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Test Connection
                    </button>
                    <button type="button" onclick="closeSlackModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PagerDuty Modal -->
<div id="pagerdutyModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePagerDutyModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="pagerdutyForm" onsubmit="savePagerDuty(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">PagerDuty Integration</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="pagerduty_enabled" name="enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ ($integrations['pagerduty']->enabled ?? false) ? 'checked' : '' }}>
                            <label for="pagerduty_enabled" class="ml-2 block text-sm text-gray-900">Enable PagerDuty Integration</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Integration Key</label>
                            <input type="text" name="integration_key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['pagerduty']->getConfigValue('integration_key') ?? '' }}" placeholder="Your PagerDuty integration key">
                            <p class="mt-1 text-xs text-gray-500">Events API v2 integration key</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Routing Key</label>
                            <input type="text" name="routing_key_default" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['pagerduty']->getConfigValue('routing_keys')['default'] ?? '' }}" placeholder="Default routing key">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Critical Routing Key</label>
                            <input type="text" name="routing_key_critical" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="{{ $integrations['pagerduty']->getConfigValue('routing_keys')['critical'] ?? '' }}" placeholder="Critical alerts routing key">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" onclick="testPagerDuty()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Test Connection
                    </button>
                    <button type="button" onclick="closePagerDutyModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div id="emailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeEmailModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="emailForm" onsubmit="saveEmail(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Email Integration</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="email_enabled" name="enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ ($integrations['email']->enabled ?? false) ? 'checked' : '' }}>
                            <label for="email_enabled" class="ml-2 block text-sm text-gray-900">Enable Email Integration</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alert Recipients</label>
                            <textarea name="recipients" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" rows="3" placeholder="One email per line">{{ implode("\n", $integrations['email']->getConfigValue('recipients') ?? []) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">One email address per line</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Critical Alert Recipients</label>
                            <textarea name="critical_recipients" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" rows="3" placeholder="One email per line">{{ implode("\n", $integrations['email']->getConfigValue('critical_recipients') ?? []) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Additional recipients for critical alerts</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" onclick="closeEmailModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

// Slack Modal
function openSlackModal() {
    document.getElementById('slackModal').classList.remove('hidden');
}

function closeSlackModal() {
    document.getElementById('slackModal').classList.add('hidden');
}

function saveSlack(event) {
    event.preventDefault();
    const form = document.getElementById('slackForm');
    const formData = new FormData(form);

    fetch('{{ route("admin.alerting.slack") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            enabled: formData.get('enabled') === 'on',
            webhook_url_default: formData.get('webhook_url_default'),
            webhook_url_critical: formData.get('webhook_url_critical'),
            webhook_url_warnings: formData.get('webhook_url_warnings'),
            default_channel: formData.get('default_channel'),
            mention_on_critical: formData.get('mention_on_critical')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Slack settings saved successfully');
            closeSlackModal();
            location.reload();
        } else {
            alert('Error: ' + JSON.stringify(data.errors));
        }
    });
}

function testSlack() {
    fetch('{{ route("admin.alerting.test-slack") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    });
}

// PagerDuty Modal
function openPagerDutyModal() {
    document.getElementById('pagerdutyModal').classList.remove('hidden');
}

function closePagerDutyModal() {
    document.getElementById('pagerdutyModal').classList.add('hidden');
}

function savePagerDuty(event) {
    event.preventDefault();
    const form = document.getElementById('pagerdutyForm');
    const formData = new FormData(form);

    fetch('{{ route("admin.alerting.pagerduty") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            enabled: formData.get('enabled') === 'on',
            integration_key: formData.get('integration_key'),
            routing_key_default: formData.get('routing_key_default'),
            routing_key_critical: formData.get('routing_key_critical')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('PagerDuty settings saved successfully');
            closePagerDutyModal();
            location.reload();
        } else {
            alert('Error: ' + JSON.stringify(data.errors));
        }
    });
}

function testPagerDuty() {
    fetch('{{ route("admin.alerting.test-pagerduty") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    });
}

// Email Modal
function openEmailModal() {
    document.getElementById('emailModal').classList.remove('hidden');
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.add('hidden');
}

function saveEmail(event) {
    event.preventDefault();
    const form = document.getElementById('emailForm');
    const formData = new FormData(form);

    const recipients = formData.get('recipients').split('\n').map(e => e.trim()).filter(e => e);
    const criticalRecipients = formData.get('critical_recipients').split('\n').map(e => e.trim()).filter(e => e);

    fetch('{{ route("admin.alerting.email") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            enabled: formData.get('enabled') === 'on',
            recipients: recipients,
            critical_recipients: criticalRecipients
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email settings saved successfully');
            closeEmailModal();
            location.reload();
        } else {
            alert('Error: ' + JSON.stringify(data.errors));
        }
    });
}

// Configuration Actions
function toggleMute(id) {
    fetch(`/panel/admin/alerting/configurations/${id}/toggle-mute`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function editConfig(id) {
    // For simplicity, redirect to edit view or open modal
    alert('Edit functionality - implement based on your needs');
}

function deleteConfig(id) {
    if (!confirm('Are you sure you want to delete this alert configuration?')) {
        return;
    }

    fetch(`/panel/admin/alerting/configurations/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function seedDefaults() {
    fetch('{{ route("admin.alerting.seed-defaults") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
}

function openAddConfigModal() {
    alert('Add configuration modal - implement based on your needs');
}
</script>
@endsection
