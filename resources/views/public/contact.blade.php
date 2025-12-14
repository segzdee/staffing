<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | OvertimeStaff</title>
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

    <!-- Hero -->
    <div class="bg-gradient-to-br from-brand-500 to-brand-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-6">Contact Us</h1>
            <p class="text-xl text-brand-100 max-w-3xl mx-auto">
                Have questions? We're here to help. Reach out to our team anytime.
            </p>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid md:grid-cols-3 gap-8 mb-16">
            <!-- Email -->
            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Email</h3>
                <p class="text-gray-600 mb-4">Our team typically responds within 24 hours</p>
                <a href="mailto:support@overtimestaff.com" class="text-brand-600 hover:text-brand-700 font-medium">
                    support@overtimestaff.com
                </a>
            </div>

            <!-- Phone -->
            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Phone</h3>
                <p class="text-gray-600 mb-4">Mon-Fri from 8am to 6pm EST</p>
                <a href="tel:+18555555555" class="text-brand-600 hover:text-brand-700 font-medium">
                    1-855-555-5555
                </a>
            </div>

            <!-- Live Chat -->
            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Live Chat</h3>
                <p class="text-gray-600 mb-4">Available 24/7 for urgent issues</p>
                <button class="text-brand-600 hover:text-brand-700 font-medium">
                    Start Chat
                </button>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Send Us a Message</h2>
                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone (Optional)</label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="user_type" class="block text-sm font-medium text-gray-700 mb-2">I am a...</label>
                        <select id="user_type" name="user_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">Select an option</option>
                            <option value="worker">Worker</option>
                            <option value="business">Business</option>
                            <option value="agency">Agency</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="6" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"></textarea>
                    </div>

                    <div>
                        <button type="submit" class="w-full px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-semibold">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-20 max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Common Questions</h2>
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">What are your support hours?</h3>
                    <p class="text-gray-600">Our phone and email support is available Monday-Friday, 8am-6pm EST. Live chat is available 24/7 for urgent issues.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">How quickly will I get a response?</h3>
                    <p class="text-gray-600">Email inquiries typically receive a response within 24 hours. Phone calls are answered immediately during business hours. Live chat responses are instant.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you have a help center?</h3>
                    <p class="text-gray-600">Yes! Visit our Help Center at help.overtimestaff.com for guides, tutorials, and answers to frequently asked questions.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I schedule a demo?</h3>
                    <p class="text-gray-600">Absolutely! Business customers can schedule a personalized demo by calling us or using the contact form above with "Demo Request" as the subject.</p>
                </div>
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
