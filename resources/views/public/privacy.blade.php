<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | OvertimeStaff</title>
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

    <!-- Privacy Policy Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
        <p class="text-gray-600 mb-8">Last updated: January 1, 2025</p>

        <div class="prose prose-lg max-w-none">
            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Introduction</h2>
                <p class="text-gray-600 mb-4">
                    OvertimeStaff ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.
                </p>
                <p class="text-gray-600">
                    Please read this Privacy Policy carefully. By accessing or using our platform, you acknowledge that you have read, understood, and agree to be bound by this Privacy Policy.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Information We Collect</h2>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.1 Personal Information</h3>
                <p class="text-gray-600 mb-4">
                    We collect information you provide directly to us, including:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Name, email address, phone number</li>
                    <li>Physical address and location data</li>
                    <li>Payment information (processed securely via Stripe)</li>
                    <li>Government-issued ID for verification purposes</li>
                    <li>Professional credentials and certifications</li>
                    <li>Employment history and work experience</li>
                    <li>Profile photos and other uploaded media</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.2 Automatically Collected Information</h3>
                <p class="text-gray-600 mb-4">
                    When you use our platform, we automatically collect:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Device information (type, operating system, browser)</li>
                    <li>IP address and approximate location</li>
                    <li>Usage data (pages visited, features used, time spent)</li>
                    <li>GPS location data during shift clock-in/out (with your permission)</li>
                    <li>Cookies and similar tracking technologies</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.3 Information from Third Parties</h3>
                <p class="text-gray-600 mb-4">
                    We may receive information about you from third parties, including:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Background check providers</li>
                    <li>Identity verification services</li>
                    <li>Payment processors (Stripe, PayPal, etc.)</li>
                    <li>Social media platforms (if you connect your accounts)</li>
                </ul>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. How We Use Your Information</h2>
                <p class="text-gray-600 mb-4">We use your information to:</p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Create and manage your account</li>
                    <li>Match workers with suitable shifts</li>
                    <li>Process payments and manage financial transactions</li>
                    <li>Verify identity and conduct background checks</li>
                    <li>Communicate with you about shifts, updates, and support</li>
                    <li>Improve our platform and develop new features</li>
                    <li>Ensure platform security and prevent fraud</li>
                    <li>Comply with legal obligations</li>
                    <li>Send marketing communications (with your consent)</li>
                </ul>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. How We Share Your Information</h2>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.1 With Other Users</h3>
                <p class="text-gray-600 mb-4">
                    When workers and businesses connect through our platform, we share relevant profile information to facilitate the shift matching process. Workers can see business profiles, and businesses can see worker profiles including skills, ratings, and availability.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.2 With Service Providers</h3>
                <p class="text-gray-600 mb-4">
                    We share information with third-party service providers who help us operate our platform:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Payment processors (Stripe, PayPal, Paystack, Razorpay, Mollie, Flutterwave, MercadoPago)</li>
                    <li>Cloud hosting providers (AWS, Cloudinary)</li>
                    <li>Analytics providers</li>
                    <li>Communication services (email, SMS, push notifications)</li>
                    <li>Background check providers</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.3 For Legal Purposes</h3>
                <p class="text-gray-600 mb-4">
                    We may disclose your information if required by law, court order, or government request, or if we believe disclosure is necessary to protect rights, safety, or property.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.4 Business Transfers</h3>
                <p class="text-gray-600 mb-4">
                    If OvertimeStaff is involved in a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Data Security</h2>
                <p class="text-gray-600 mb-4">
                    We implement appropriate technical and organizational measures to protect your personal information, including:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Encryption of data in transit and at rest</li>
                    <li>Secure authentication mechanisms</li>
                    <li>Regular security audits and assessments</li>
                    <li>Access controls and employee training</li>
                    <li>PCI DSS compliance for payment processing</li>
                </ul>
                <p class="text-gray-600">
                    However, no method of transmission over the Internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Data Retention</h2>
                <p class="text-gray-600 mb-4">
                    We retain your personal information for as long as necessary to:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Provide our services to you</li>
                    <li>Comply with legal obligations</li>
                    <li>Resolve disputes and enforce agreements</li>
                    <li>Support business operations</li>
                </ul>
                <p class="text-gray-600">
                    When you close your account, we may retain certain information as required by law or for legitimate business purposes.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Your Rights and Choices</h2>
                <p class="text-gray-600 mb-4">Depending on your location, you may have the right to:</p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li><strong>Access:</strong> Request a copy of your personal information</li>
                    <li><strong>Correction:</strong> Request correction of inaccurate information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                    <li><strong>Portability:</strong> Request your data in a portable format</li>
                    <li><strong>Opt-out:</strong> Opt out of marketing communications</li>
                    <li><strong>Withdraw consent:</strong> Withdraw previously given consent</li>
                </ul>
                <p class="text-gray-600">
                    To exercise these rights, please contact us at privacy@overtimestaff.com.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Cookies and Tracking</h2>
                <p class="text-gray-600 mb-4">
                    We use cookies and similar technologies to:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Keep you logged in</li>
                    <li>Remember your preferences</li>
                    <li>Analyze platform usage</li>
                    <li>Deliver targeted advertising (with consent)</li>
                </ul>
                <p class="text-gray-600">
                    You can control cookies through your browser settings. Note that disabling cookies may affect platform functionality.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. International Data Transfers</h2>
                <p class="text-gray-600 mb-4">
                    OvertimeStaff operates globally. Your information may be transferred to and processed in countries other than your country of residence. We ensure appropriate safeguards are in place for international transfers, including standard contractual clauses and adequacy decisions.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Children's Privacy</h2>
                <p class="text-gray-600 mb-4">
                    Our platform is not intended for users under 18 years of age. We do not knowingly collect personal information from children. If we learn we have collected information from a child under 18, we will delete it promptly.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">11. California Privacy Rights (CCPA)</h2>
                <p class="text-gray-600 mb-4">
                    California residents have additional rights under the California Consumer Privacy Act (CCPA):
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Right to know what personal information we collect</li>
                    <li>Right to delete personal information</li>
                    <li>Right to opt-out of the sale of personal information</li>
                    <li>Right to non-discrimination for exercising privacy rights</li>
                </ul>
                <p class="text-gray-600">
                    We do not sell personal information. To exercise your CCPA rights, contact us at privacy@overtimestaff.com.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">12. European Privacy Rights (GDPR)</h2>
                <p class="text-gray-600 mb-4">
                    If you are in the European Economic Area (EEA), you have additional rights under the General Data Protection Regulation (GDPR), including the rights described in Section 7. Our legal basis for processing includes:
                </p>
                <ul class="list-disc pl-6 text-gray-600 mb-4 space-y-2">
                    <li>Contract performance (providing our services)</li>
                    <li>Legitimate interests (platform improvement, security)</li>
                    <li>Legal compliance</li>
                    <li>Consent (where required)</li>
                </ul>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Changes to This Policy</h2>
                <p class="text-gray-600 mb-4">
                    We may update this Privacy Policy from time to time. We will notify you of material changes by posting the new policy on this page and updating the "Last updated" date. We encourage you to review this policy periodically.
                </p>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">14. Contact Us</h2>
                <p class="text-gray-600 mb-4">
                    If you have questions or concerns about this Privacy Policy or our data practices, please contact us at:
                </p>
                <p class="text-gray-600">
                    <strong>OvertimeStaff Privacy Team</strong><br>
                    Email: privacy@overtimestaff.com<br>
                    Phone: 1-855-555-5555<br>
                    Address: 123 Main Street, Suite 400, Boston, MA 02110
                </p>
            </section>
        </div>

        <!-- Quick Links -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Documents</h3>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="/terms" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Terms of Service</h4>
                    <p class="text-sm text-gray-600">Our terms and conditions</p>
                </a>
                <a href="/cookie-policy" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Cookie Policy</h4>
                    <p class="text-sm text-gray-600">How we use cookies and tracking</p>
                </a>
                <a href="/contact" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <h4 class="font-medium text-gray-900 mb-1">Contact Us</h4>
                    <p class="text-sm text-gray-600">Get in touch with our team</p>
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
