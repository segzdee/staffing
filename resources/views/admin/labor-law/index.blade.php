@extends('layouts.admin')

@section('title', 'Labor Law Rules')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Labor Law Rules</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.labor-law.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Dashboard
            </a>
            <a href="{{ route('admin.labor-law.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Add New Rule
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form action="{{ route('admin.labor-law.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jurisdiction</label>
                <select name="jurisdiction" class="border-gray-300 rounded-md shadow-sm">
                    <option value="">All Jurisdictions</option>
                    @foreach($jurisdictions as $jurisdiction)
                    <option value="{{ $jurisdiction }}" {{ request('jurisdiction') == $jurisdiction ? 'selected' : '' }}>
                        {{ $jurisdiction }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rule Type</label>
                <select name="rule_type" class="border-gray-300 rounded-md shadow-sm">
                    <option value="">All Types</option>
                    @foreach($ruleTypes as $type => $label)
                    <option value="{{ $type }}" {{ request('rule_type') == $type ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="border-gray-300 rounded-md shadow-sm">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-900">
                Filter
            </button>
            <a href="{{ route('admin.labor-law.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                Reset
            </a>
        </form>
    </div>

    <!-- Rules Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rule</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurisdiction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enforcement</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($rules as $rule)
                <tr class="{{ !$rule->is_active ? 'bg-gray-50' : '' }}">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $rule->name }}</div>
                        <div class="text-xs text-gray-500">{{ $rule->rule_code }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ $rule->jurisdiction }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                            {{ $ruleTypes[$rule->rule_type] ?? $rule->rule_type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{
                            $rule->enforcement === 'hard_block' ? 'bg-red-100 text-red-800' :
                            ($rule->enforcement === 'soft_warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $rule->enforcement)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form action="{{ route('admin.labor-law.toggle-active', $rule) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2 py-1 text-xs rounded-full {{
                                $rule->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                            }}">
                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <a href="{{ route('admin.labor-law.show', $rule) }}" class="text-blue-600 hover:underline mr-3">View</a>
                        <a href="{{ route('admin.labor-law.edit', $rule) }}" class="text-gray-600 hover:underline">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No labor law rules found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $rules->links() }}
    </div>
</div>
@endsection
