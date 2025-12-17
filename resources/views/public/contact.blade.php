@extends('layouts.marketing')

@section('title', 'Contact Us | OvertimeStaff')
@section('meta_description', 'Get in touch with the OvertimeStaff team. We are here to help with your staffing needs.')

@section('content')
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h1 class="text-4xl font-bold tracking-tight text-foreground mb-4">Contact Us</h1>
        <p class="text-muted-foreground mb-12 text-lg">
            Have questions or need assistance? Our team is here to help. Reach out to us through any of the channels below.
        </p>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Contact Information -->
            <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-foreground mb-6">Get in Touch</h2>

                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="shrink-0">
                            <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base font-medium text-foreground">Email Support</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                For general inquiries and support:<br>
                                <a href="mailto:support@overtimestaff.com"
                                    class="text-primary hover:underline">support@overtimestaff.com</a>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="shrink-0">
                            <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base font-medium text-foreground">Phone Support</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Mon-Fri from 9am to 6pm EST:<br>
                                <a href="tel:+18555555555" class="text-primary hover:underline">1-855-555-5555</a>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="shrink-0">
                            <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base font-medium text-foreground">Office Location</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                123 Main Street, Suite 400<br>
                                Boston, MA 02110<br>
                                United States
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inquiry Form Placeholder -->
            <div
                class="bg-muted/30 border border-border rounded-lg p-6 flex flex-col justify-center items-center text-center">
                <svg class="h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <h3 class="text-lg font-medium text-foreground mb-2">Send us a message</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    We'll get back to you within 24 hours.
                </p>
                <!-- This would be a form in a real implementation -->
                <button type="button"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Open Contact Form
                </button>
            </div>
        </div>
    </section>
@endsection