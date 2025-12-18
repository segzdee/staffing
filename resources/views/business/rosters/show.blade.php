@extends('layouts.dashboard')

@section('title', $roster->name)
@section('page-title', $roster->name)
@section('page-subtitle', $roster->type_display)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center">
            <a href="{{ route('business.rosters.index') }}" class="mr-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $roster->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $roster->type_badge_color }}-100 text-{{ $roster->type_badge_color }}-800">
                        {{ $roster->type_display }}
                    </span>
                    @if($roster->is_default)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Default
                    </span>
                    @endif
                </div>
                @if($roster->description)
                <p class="mt-1 text-sm text-gray-500">{{ $roster->description }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('business.rosters.add-member', $roster) }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Add Worker
            </a>
            <a href="{{ route('business.rosters.edit', $roster) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Roster
            </a>
        </div>
    </div>

    <!-- Pending Invitations -->
    @if($pendingInvitations->count() > 0)
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <h3 class="text-sm font-medium text-yellow-800 mb-3">Pending Invitations ({{ $pendingInvitations->count() }})</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($pendingInvitations as $invitation)
            <div class="inline-flex items-center bg-white rounded-lg px-3 py-2 border border-yellow-200">
                <span class="text-sm text-gray-700">{{ $invitation->worker->name }}</span>
                <span class="ml-2 text-xs text-gray-500">Expires {{ $invitation->expires_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Members Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Worker
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Priority
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Custom Rate
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Shifts
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Worked
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($members as $member)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    @if($member->worker->avatar)
                                    <img src="{{ $member->worker->avatar }}" alt="{{ $member->worker->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                    <span class="text-sm font-medium text-gray-600">
                                        {{ substr($member->worker->name, 0, 1) }}
                                    </span>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $member->worker->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $member->worker->email }}
                                    </div>
                                    @if($member->worker->rating_as_worker)
                                    <div class="flex items-center mt-1">
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="ml-1 text-xs text-gray-500">{{ number_format($member->worker->rating_as_worker, 1) }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $member->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ $member->status_display }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $member->priority }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($member->custom_rate)
                            ${{ number_format($member->custom_rate, 2) }}/hr
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $member->total_shifts }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($member->last_worked_at)
                            {{ $member->last_worked_at->diffForHumans() }}
                            @else
                            Never
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <button type="button" onclick="openEditModal({{ $member->id }})" class="text-gray-600 hover:text-gray-900">
                                    Edit
                                </button>
                                <form action="{{ route('business.rosters.members.remove', [$roster, $member]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this worker from the roster?')">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No members in this roster</h3>
                            <p class="mt-1 text-sm text-gray-500">Start by adding workers to this roster.</p>
                            <div class="mt-6">
                                <a href="{{ route('business.rosters.add-member', $roster) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                    Add Worker
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editMemberModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg max-w-lg w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Member</h3>
            <form id="editMemberForm" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="edit_status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_priority" class="block text-sm font-medium text-gray-700">Priority (0-100)</label>
                        <input type="number" name="priority" id="edit_priority" min="0" max="100" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                    </div>
                    <div>
                        <label for="edit_custom_rate" class="block text-sm font-medium text-gray-700">Custom Rate ($/hr)</label>
                        <input type="number" name="custom_rate" id="edit_custom_rate" step="0.01" min="0" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                    </div>
                    <div>
                        <label for="edit_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" id="edit_notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const membersData = @json($members->items());

function openEditModal(memberId) {
    const member = membersData.find(m => m.id === memberId);
    if (!member) return;

    document.getElementById('edit_status').value = member.status;
    document.getElementById('edit_priority').value = member.priority;
    document.getElementById('edit_custom_rate').value = member.custom_rate || '';
    document.getElementById('edit_notes').value = member.notes || '';
    document.getElementById('editMemberForm').action = '{{ route("business.rosters.members.update", [$roster->id, ""]) }}/' + memberId;

    document.getElementById('editMemberModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editMemberModal').classList.add('hidden');
}

// Close modal on backdrop click
document.getElementById('editMemberModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endpush
@endsection
