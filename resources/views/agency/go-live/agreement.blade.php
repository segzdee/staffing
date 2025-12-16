@extends('layouts.authenticated')

@section('title', 'Commercial Agreement')
@section('page-title', 'Commercial Agreement')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.go-live.checklist') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Go-Live Checklist</span>
</a>
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <span>Agreement</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('agency.go-live.checklist') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Checklist
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Agency Partnership Agreement</h1>
            <p class="mt-1 text-gray-600">Version {{ $agreementVersion }}</p>
        </div>

        @if($isSigned)
        <!-- Already Signed Notice -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
            <div class="flex">
                <svg class="h-6 w-6 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-green-800">Agreement Signed</h3>
                    <p class="mt-1 text-green-700">
                        This agreement was signed on {{ \Carbon\Carbon::parse($signedAt)->format('F j, Y \a\t g:i A') }}.
                    </p>
                    <a href="{{ route('agency.go-live.checklist') }}" class="mt-4 inline-flex items-center text-green-700 hover:text-green-800 font-medium">
                        Return to Checklist
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Agreement Content -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Agreement Text -->
            <div class="p-6 max-h-[60vh] overflow-y-auto prose prose-sm max-w-none">
                <h2>OvertimeStaff Agency Partnership Agreement</h2>

                <p><strong>Effective Date:</strong> Upon electronic signature</p>
                <p><strong>Parties:</strong> OvertimeStaff Inc. ("Platform") and {{ $agency->agency_name }} ("Agency")</p>

                <h3>1. Service Overview</h3>
                <p>This Agreement governs Agency's use of the OvertimeStaff platform to manage worker placements and receive commission payments for shift fulfillment services.</p>

                <h3>2. Agency Responsibilities</h3>
                <ul>
                    <li>Maintain accurate and up-to-date worker records</li>
                    <li>Ensure all workers complete required verifications and background checks</li>
                    <li>Provide workers who meet the skill requirements for posted shifts</li>
                    <li>Respond to shift requests within specified timeframes</li>
                    <li>Maintain appropriate insurance coverage as specified in platform requirements</li>
                    <li>Comply with all applicable labor laws and regulations</li>
                    <li>Handle worker payroll and tax obligations for workers directly employed by Agency</li>
                </ul>

                <h3>3. Commission Structure</h3>
                <p>Agency will receive commissions based on successfully filled shifts according to the following structure:</p>
                <ul>
                    <li><strong>Standard Commission:</strong> {{ $agency->commission_rate ?? 10 }}% of shift value for standard placements</li>
                    <li><strong>Urgent Fill Bonus:</strong> Additional 5% for shifts filled within 4 hours of posting</li>
                    <li><strong>Performance Bonuses:</strong> Additional incentives based on fill rate and worker ratings</li>
                </ul>
                <p>Commissions are calculated after deduction of platform fees and are paid out according to the Agency's selected payout schedule via Stripe Connect.</p>

                <h3>4. Payment Terms</h3>
                <ul>
                    <li>Commission payments processed via Stripe Connect</li>
                    <li>Standard payout schedule: Weekly on Fridays</li>
                    <li>Minimum payout threshold: $50.00</li>
                    <li>Platform reserves right to withhold payments pending dispute resolution</li>
                </ul>

                <h3>5. Worker Management</h3>
                <ul>
                    <li>Agency is responsible for verifying worker eligibility and qualifications</li>
                    <li>Workers must complete platform onboarding and verification processes</li>
                    <li>Agency must maintain minimum worker quality standards (4.0+ average rating)</li>
                    <li>Worker no-shows may result in commission adjustments or penalties</li>
                </ul>

                <h3>6. Performance Standards</h3>
                <p>Agency agrees to maintain the following performance metrics:</p>
                <ul>
                    <li><strong>Fill Rate:</strong> Minimum 75% of accepted shifts successfully filled</li>
                    <li><strong>Worker Rating:</strong> Average worker rating of 4.0 or higher</li>
                    <li><strong>Response Time:</strong> Respond to shift requests within 2 hours during business hours</li>
                    <li><strong>No-Show Rate:</strong> Less than 5% worker no-shows</li>
                </ul>
                <p>Failure to meet these standards may result in account restrictions or termination.</p>

                <h3>7. Insurance Requirements</h3>
                <p>Agency must maintain the following minimum insurance coverage:</p>
                <ul>
                    <li>General Liability: $1,000,000 per occurrence</li>
                    <li>Workers' Compensation: As required by applicable law</li>
                    <li>Professional Liability: $500,000 per occurrence (if applicable)</li>
                </ul>

                <h3>8. Data Protection & Privacy</h3>
                <p>Agency agrees to:</p>
                <ul>
                    <li>Handle personal data in compliance with applicable privacy laws (GDPR, CCPA, etc.)</li>
                    <li>Not share platform data with third parties without written consent</li>
                    <li>Implement reasonable security measures to protect data</li>
                    <li>Report any data breaches within 24 hours</li>
                </ul>

                <h3>9. Intellectual Property</h3>
                <p>All platform intellectual property, including trademarks, logos, and software, remains the property of OvertimeStaff Inc. Agency may not use platform branding without prior written approval.</p>

                <h3>10. Termination</h3>
                <p>Either party may terminate this Agreement with 30 days written notice. Immediate termination may occur in cases of:</p>
                <ul>
                    <li>Material breach of agreement terms</li>
                    <li>Failure to maintain required licenses or insurance</li>
                    <li>Fraudulent activity</li>
                    <li>Repeated performance standard violations</li>
                </ul>
                <p>Upon termination, Agency will receive payment for all completed and verified shifts.</p>

                <h3>11. Limitation of Liability</h3>
                <p>Platform liability is limited to direct damages not exceeding the total commissions paid to Agency in the 12 months preceding the claim. Neither party shall be liable for indirect, consequential, or punitive damages.</p>

                <h3>12. Dispute Resolution</h3>
                <p>Disputes shall first be addressed through good-faith negotiation. Unresolved disputes will be subject to binding arbitration in accordance with the rules of the American Arbitration Association.</p>

                <h3>13. Governing Law</h3>
                <p>This Agreement shall be governed by the laws of the State of Delaware, without regard to conflict of law principles.</p>

                <h3>14. Amendments</h3>
                <p>Platform may amend this Agreement with 30 days notice. Continued use of the platform after the notice period constitutes acceptance of amendments.</p>

                <h3>15. Entire Agreement</h3>
                <p>This Agreement constitutes the entire agreement between the parties and supersedes all prior agreements and understandings.</p>
            </div>

            @if(!$isSigned)
            <!-- Signature Form -->
            <div class="border-t border-gray-200 p-6 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Electronic Signature</h3>

                <form action="{{ route('agency.go-live.sign') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="signature_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Legal Name *
                            </label>
                            <input type="text"
                                   id="signature_name"
                                   name="signature_name"
                                   value="{{ old('signature_name') }}"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent @error('signature_name') border-red-500 @enderror"
                                   placeholder="Enter your full legal name">
                            @error('signature_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="signature_title" class="block text-sm font-medium text-gray-700 mb-2">
                                Title / Position *
                            </label>
                            <input type="text"
                                   id="signature_title"
                                   name="signature_title"
                                   value="{{ old('signature_title') }}"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent @error('signature_title') border-red-500 @enderror"
                                   placeholder="e.g., Owner, CEO, Manager">
                            @error('signature_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox"
                                   name="accept_terms"
                                   value="1"
                                   required
                                   class="mt-1 h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-600">
                                I have read and agree to the terms of this Agency Partnership Agreement. I confirm that I am authorized to sign this agreement on behalf of <strong>{{ $agency->agency_name }}</strong>.
                            </span>
                        </label>
                        @error('accept_terms')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            By clicking "Sign Agreement", you are agreeing to the terms above and creating a legally binding contract.
                        </p>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors duration-150">
                            <svg class="mr-2 -ml-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Sign Agreement
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Questions about the agreement? <a href="{{ route('contact') }}" class="text-brand-600 hover:text-brand-700 font-medium">Contact our support team</a></p>
        </div>
    </div>
</div>
@endsection
