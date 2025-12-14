<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service | OvertimeStaff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#FFF7ED',
                            100: '#FFEDD5',
                            200: '#FED7AA',
                            300: '#FDBA74',
                            400: '#FB923C',
                            500: '#F97316',
                            600: '#EA580C',
                            700: '#C2410C',
                            800: '#9A3412',
                            900: '#7C2D12',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-gray-900">OvertimeStaff</a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-gray-600 hover:text-gray-900">Home</a>
                    <a href="/features" class="text-gray-600 hover:text-gray-900">Features</a>
                    <a href="/pricing" class="text-gray-600 hover:text-gray-900">Pricing</a>
                    <a href="/about" class="text-gray-600 hover:text-gray-900">About</a>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Sign In</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Legal Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Terms of Service</h1>
        <p class="text-gray-600 mb-8">Last updated: January 1, 2025</p>

        <div class="prose prose-lg max-w-none">
            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                <p class="text-gray-600 mb-4">
                    By accessing and using OvertimeStaff ("the Platform"), you accept and agree to be bound by the terms and provisions of this agreement. If you do not agree to these Terms of Service, please do not use the Platform.
                </p>
                <p class="text-gray-600">
                    These terms apply to all users of the Platform, including workers, businesses, agencies, and administrative users.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Use of the Platform</h2>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.1 Eligibility</h3>
                <p class="text-gray-600 mb-4">
                    You must be at least 18 years old and legally able to enter into contracts to use the Platform. By using the Platform, you represent and warrant that you meet these requirements.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.2 Account Registration</h3>
                <p class="text-gray-600 mb-4">
                    You must register for an account to access certain features of the Platform. You agree to:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Provide accurate, current, and complete information</li>
                    <li>Maintain and update your information to keep it accurate</li>
                    <li>Maintain the security of your password</li>
                    <li>Accept responsibility for all activities under your account</li>
                    <li>Notify us immediately of any unauthorized use</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.3 Prohibited Conduct</h3>
                <p class="text-gray-600 mb-4">You agree not to:</p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Violate any applicable laws or regulations</li>
                    <li>Infringe upon the rights of others</li>
                    <li>Transmit any harmful code or viruses</li>
                    <li>Attempt to gain unauthorized access to the Platform</li>
                    <li>Interfere with the proper functioning of the Platform</li>
                    <li>Use the Platform for fraudulent purposes</li>
                    <li>Harass, abuse, or harm other users</li>
                </ul>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Worker Terms</h2>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.1 Worker Status</h3>
                <p class="text-gray-600 mb-4">
                    Workers are independent contractors, not employees of OvertimeStaff or the businesses they work for through the Platform. You are responsible for all taxes, insurance, and other obligations related to your work.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.2 Background Checks</h3>
                <p class="text-gray-600 mb-4">
                    By registering as a worker, you consent to background checks, identity verification, and skill assessments as required by the Platform.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.3 Shift Commitments</h3>
                <p class="text-gray-600 mb-4">
                    Once you accept a shift assignment, you commit to completing it. Cancellations less than 24 hours before the shift start time may result in penalties, including account suspension.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.4 Payment</h3>
                <p class="text-gray-600 mb-4">
                    Workers receive payment via Stripe Connect within 15 minutes of shift completion and approval. Payment amounts are based on actual hours worked and the agreed hourly rate.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Business Terms</h2>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.1 Shift Postings</h3>
                <p class="text-gray-600 mb-4">
                    Businesses agree to provide accurate information about shifts, including job descriptions, requirements, location, and compensation.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.2 Service Fees</h3>
                <p class="text-gray-600 mb-4">
                    Businesses pay an 8% service fee on completed shift hours. This fee covers platform operations, payment processing, insurance, and support services.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.3 Worker Treatment</h3>
                <p class="text-gray-600 mb-4">
                    Businesses agree to treat workers professionally and in compliance with all applicable labor laws and regulations. Any reports of harassment, discrimination, or unsafe working conditions will be investigated and may result in account termination.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.4 Shift Cancellations</h3>
                <p class="text-gray-600 mb-4">
                    Shifts must be cancelled at least 24 hours before the start time. Late cancellations may incur fees or penalties.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Payment Terms</h2>
                <p class="text-gray-600 mb-4">
                    All payments are processed through Stripe. By using the Platform, you agree to Stripe's terms of service. OvertimeStaff is not responsible for payment processing errors or disputes with Stripe.
                </p>
                <p class="text-gray-600 mb-4">
                    Refunds are handled on a case-by-case basis according to our refund policy.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Intellectual Property</h2>
                <p class="text-gray-600 mb-4">
                    All content on the Platform, including text, graphics, logos, and software, is the property of OvertimeStaff and protected by copyright and trademark laws. You may not use, reproduce, or distribute any content without our permission.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Privacy</h2>
                <p class="text-gray-600 mb-4">
                    Your use of the Platform is subject to our Privacy Policy. By using the Platform, you consent to our collection and use of your information as described in the Privacy Policy.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Disputes and Resolution</h2>
                <p class="text-gray-600 mb-4">
                    Any disputes between users should first be reported to OvertimeStaff support. We will mediate disputes according to our dispute resolution policy. Unresolved disputes may be subject to binding arbitration.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Limitation of Liability</h2>
                <p class="text-gray-600 mb-4">
                    OvertimeStaff provides the Platform "as is" without warranties of any kind. We are not liable for any indirect, incidental, or consequential damages arising from your use of the Platform.
                </p>
                <p class="text-gray-600 mb-4">
                    Our total liability for any claims related to the Platform is limited to the amount you paid us in the 12 months before the claim.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Termination</h2>
                <p class="text-gray-600 mb-4">
                    We may suspend or terminate your account at any time for violations of these terms or for any other reason. You may terminate your account at any time by contacting support.
                </p>
                <p class="text-gray-600 mb-4">
                    Upon termination, you remain liable for all outstanding obligations.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Changes to Terms</h2>
                <p class="text-gray-600 mb-4">
                    We reserve the right to modify these terms at any time. We will notify users of material changes via email or platform notifications. Continued use of the Platform after changes constitutes acceptance of the new terms.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Governing Law</h2>
                <p class="text-gray-600 mb-4">
                    These terms are governed by the laws of the State of Delaware, without regard to conflict of law principles.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Contact Information</h2>
                <p class="text-gray-600 mb-4">
                    For questions about these Terms of Service, please contact us at:
                </p>
                <p class="text-gray-600">
                    Email: legal@overtimestaff.com<br>
                    Phone: 1-855-555-5555<br>
                    Address: 123 Main Street, Suite 400, Boston, MA 02110
                </p>
            </section>
        </div>

        <!-- Quick Links -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Documents</h3>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="/privacy" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Privacy Policy</h4>
                    <p class="text-sm text-gray-600">How we collect and use your data</p>
                </a>
                <a href="/refund-policy" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Refund Policy</h4>
                    <p class="text-sm text-gray-600">Information about refunds and cancellations</p>
                </a>
                <a href="/cookie-policy" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Cookie Policy</h4>
                    <p class="text-sm text-gray-600">How we use cookies and tracking</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-600">
            <p>&copy; 2025 OvertimeStaff. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
