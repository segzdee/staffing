@extends('layouts.dashboard')

@section('title', 'Add Worker to Roster')
@section('page-title', 'Add Worker')
@section('page-subtitle', 'Add a worker to {{ $roster->name }}')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('business.rosters.show', $roster) }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to {{ $roster->name }}
        </a>
    </div>

    <!-- Search Workers -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Search Workers</h3>

            <div class="relative">
                <input type="text" id="workerSearch" placeholder="Search by name or email..."
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm pl-10">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Search Results -->
            <div id="searchResults" class="mt-4 hidden">
                <div id="searchLoading" class="py-8 text-center text-gray-500 hidden">
                    <svg class="animate-spin h-6 w-6 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2">Searching...</p>
                </div>
                <div id="resultsContainer" class="space-y-2"></div>
                <div id="noResults" class="py-8 text-center text-gray-500 hidden">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2">No workers found matching your search.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Worker Form (appears when worker is selected) -->
    <div id="addWorkerForm" class="bg-white rounded-xl border border-gray-200 overflow-hidden hidden">
        <form action="{{ route('business.rosters.members.add', $roster) }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="worker_id" id="selectedWorkerId">

            <div class="flex items-center mb-6 pb-6 border-b border-gray-200">
                <div id="selectedWorkerAvatar" class="flex-shrink-0 h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center">
                    <span class="text-lg font-medium text-gray-600">?</span>
                </div>
                <div class="ml-4">
                    <h3 id="selectedWorkerName" class="text-lg font-medium text-gray-900">Worker Name</h3>
                    <p id="selectedWorkerEmail" class="text-sm text-gray-500">worker@example.com</p>
                </div>
                <button type="button" onclick="clearSelection()" class="ml-auto text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority (0-100)</label>
                    <input type="number" name="priority" id="priority" value="0" min="0" max="100"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500">Higher priority workers appear first when filling shifts.</p>
                </div>

                <div>
                    <label for="custom_rate" class="block text-sm font-medium text-gray-700">Custom Rate ($/hr) - Optional</label>
                    <input type="number" name="custom_rate" id="custom_rate" step="0.01" min="0"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                        placeholder="Leave blank to use default rate">
                    <p class="mt-1 text-sm text-gray-500">Override the default rate for this worker.</p>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes - Optional</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                        placeholder="Private notes about this worker..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-3">
                <button type="button" onclick="clearSelection()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                    Add to Roster
                </button>
            </div>
        </form>
    </div>

    <!-- Or Invite Worker -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Want to invite instead?</h3>
                <p class="mt-1 text-sm text-blue-700">
                    You can also send roster invitations to workers. Search for a worker and click "Invite" to send them an invitation to join your roster.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let searchTimeout;

document.getElementById('workerSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        document.getElementById('searchResults').classList.add('hidden');
        return;
    }

    document.getElementById('searchResults').classList.remove('hidden');
    document.getElementById('searchLoading').classList.remove('hidden');
    document.getElementById('resultsContainer').innerHTML = '';
    document.getElementById('noResults').classList.add('hidden');

    searchTimeout = setTimeout(() => {
        fetch('{{ route("business.rosters.search-workers", $roster) }}?search=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                document.getElementById('searchLoading').classList.add('hidden');

                if (data.workers.length === 0) {
                    document.getElementById('noResults').classList.remove('hidden');
                    return;
                }

                const container = document.getElementById('resultsContainer');
                container.innerHTML = data.workers.map(worker => `
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-600">${worker.name.charAt(0)}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">${worker.name}</p>
                                <p class="text-xs text-gray-500">${worker.email}</p>
                                <div class="flex items-center mt-1">
                                    ${worker.rating ? `
                                    <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="ml-1 text-xs text-gray-500">${Number(worker.rating).toFixed(1)}</span>
                                    ` : ''}
                                    ${worker.total_shifts ? `<span class="ml-2 text-xs text-gray-500">${worker.total_shifts} shifts</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="selectWorker(${JSON.stringify(worker).replace(/"/g, '&quot;')})" class="px-3 py-1 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                                Add
                            </button>
                            <button type="button" onclick="inviteWorker(${worker.id})" class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Invite
                            </button>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                document.getElementById('searchLoading').classList.add('hidden');
                console.error('Search error:', error);
            });
    }, 300);
});

function selectWorker(worker) {
    document.getElementById('selectedWorkerId').value = worker.id;
    document.getElementById('selectedWorkerName').textContent = worker.name;
    document.getElementById('selectedWorkerEmail').textContent = worker.email;
    document.getElementById('selectedWorkerAvatar').innerHTML = `<span class="text-lg font-medium text-gray-600">${worker.name.charAt(0)}</span>`;

    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('addWorkerForm').classList.remove('hidden');
}

function clearSelection() {
    document.getElementById('selectedWorkerId').value = '';
    document.getElementById('addWorkerForm').classList.add('hidden');
}

function inviteWorker(workerId) {
    // Submit invite form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("business.rosters.invite", $roster) }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    const workerInput = document.createElement('input');
    workerInput.type = 'hidden';
    workerInput.name = 'worker_id';
    workerInput.value = workerId;
    form.appendChild(workerInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
@endsection
