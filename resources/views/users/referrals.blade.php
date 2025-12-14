@extends('layouts.authenticated')

@section('title', 'Referral Program')
@section('page-title', 'Referral Program')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
    </svg>
    <span>Referrals</span>
</a>
<a href="{{ route('settings.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>Settings</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Referral Program</h2>
            <p class="text-sm text-gray-500 mt-1">Invite friends and earn rewards for every successful referral</p>
        </div>
    </div>

    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-brand-500 to-brand-600 rounded-xl p-8 text-white">
        <div class="max-w-2xl">
            <h3 class="text-2xl font-bold mb-2">Earn ${{ $referralBonus ?? 25 }} for Every Referral!</h3>
            <p class="text-brand-100 mb-6">
                Share your unique referral link with friends. When they sign up and complete their first shift, you both earn rewards!
            </p>
            <div class="flex items-center space-x-4">
                <div class="flex-1 bg-white/10 rounded-lg p-3">
                    <p class="text-xs text-brand-200 mb-1">Your Referral Link</p>
                    <div class="flex items-center">
                        <input type="text" readonly value="{{ $referralLink ?? url('/register?ref='.auth()->user()->referral_code ?? 'ABC123') }}"
                               id="referralLink" class="bg-transparent text-white text-sm w-full focus:outline-none">
                        <button onclick="copyReferralLink()" class="ml-2 text-brand-200 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="shareOnTwitter()" class="p-3 bg-white/10 rounded-lg hover:bg-white/20">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                        </svg>
                    </button>
                    <button onclick="shareOnFacebook()" class="p-3 bg-white/10 rounded-lg hover:bg-white/20">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </button>
                    <button onclick="shareViaEmail()" class="p-3 bg-white/10 rounded-lg hover:bg-white/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <p class="text-sm text-gray-500">Total Referrals</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalReferrals ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <p class="text-sm text-gray-500">Successful Referrals</p>
            <p class="text-3xl font-bold text-green-600">{{ $successfulReferrals ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-3xl font-bold text-yellow-600">{{ $pendingReferrals ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <p class="text-sm text-gray-500">Total Earned</p>
            <p class="text-3xl font-bold text-brand-600">${{ number_format($totalEarned ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- How It Works -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">How It Works</h3>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">1. Share Your Link</h4>
                <p class="text-sm text-gray-600">Share your unique referral link with friends, family, or on social media.</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">2. Friend Signs Up</h4>
                <p class="text-sm text-gray-600">Your friend creates an account using your referral link.</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">3. You Both Earn</h4>
                <p class="text-sm text-gray-600">After their first completed shift, you both receive ${{ $referralBonus ?? 25 }}!</p>
            </div>
        </div>
    </div>

    <!-- Referral History -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Referral History</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($referrals ?? [] as $referral)
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="{{ $referral->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($referral->user->name ?? 'User') }}"
                         alt="User" class="w-10 h-10 rounded-full">
                    <div>
                        <p class="font-medium text-gray-900">{{ $referral->user->name ?? 'User Name' }}</p>
                        <p class="text-sm text-gray-500">Joined {{ $referral->created_at ?? 'Recently' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    @php
                        $status = $referral->status ?? 'pending';
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'expired' => 'bg-gray-100 text-gray-800',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($status) }}
                    </span>
                    @if($status === 'completed')
                    <p class="text-sm text-green-600 mt-1">+${{ number_format($referralBonus ?? 25, 2) }}</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No referrals yet</h3>
                <p class="mt-2 text-sm text-gray-500">Share your link to start earning rewards!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    document.execCommand('copy');
    alert('Referral link copied to clipboard!');
}

function shareOnTwitter() {
    const url = encodeURIComponent(document.getElementById('referralLink').value);
    const text = encodeURIComponent('Join me on OvertimeStaff and find flexible shifts! Use my referral link:');
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
}

function shareOnFacebook() {
    const url = encodeURIComponent(document.getElementById('referralLink').value);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
}

function shareViaEmail() {
    const url = document.getElementById('referralLink').value;
    const subject = encodeURIComponent('Join OvertimeStaff - Find Flexible Work');
    const body = encodeURIComponent(`Hey!\n\nI've been using OvertimeStaff to find flexible shift work and thought you might be interested.\n\nSign up using my referral link and we'll both earn a bonus:\n${url}\n\nSee you there!`);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}
</script>
@endpush
@endsection
