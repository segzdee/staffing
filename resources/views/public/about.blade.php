@extends('layouts.marketing')

@section('title', 'About Us | OvertimeStaff')
@section('meta_description', 'Learn about OvertimeStaff, our mission to revolutionize global staffing, and the team behind the platform.')

@section('content')
    <!-- Hero Section -->
    <section class="bg-muted/30 py-20 border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl mb-6">
                Revolutionizing Global Staffing
            </h1>
            <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
                We're on a mission to connect businesses with verified workers instantly, anywhere in the world.
            </p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg max-w-none text-muted-foreground">
                <h2 class="text-3xl font-bold text-foreground mb-6">Our Story</h2>
                <p class="mb-6">
                    Founded in 2024, OvertimeStaff emerged from a simple observation: the traditional staffing industry was
                    broken. Businesses struggled to fill shifts quickly, while skilled workers faced barriers to finding
                    flexible work.
                </p>
                <p class="mb-6">
                    We built OvertimeStaff to bridge this gap using technology. By creating a transparent, instant
                    marketplace, we empower businesses to operate efficiently and workers to take control of their
                    schedules.
                </p>
                <p class="mb-12">
                    Today, we serve hundreds of businesses across 70+ countries, facilitating thousands of successful shifts
                    every month.
                </p>

                <h2 class="text-3xl font-bold text-foreground mb-6">Our Values</h2>
                <div class="grid md:grid-cols-2 gap-8 not-prose">
                    <div class="bg-card border border-border rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-foreground mb-3">Trust First</h3>
                        <p class="text-sm text-muted-foreground">
                            We believe trust is the currency of our platform. We rigorously verify every worker and business
                            to ensure a safe community.
                        </p>
                    </div>
                    <div class="bg-card border border-border rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-foreground mb-3">Speed Matters</h3>
                        <p class="text-sm text-muted-foreground">
                            In staffing, every minute counts. We optimize for speed, from shift posting to payment
                            processing.
                        </p>
                    </div>
                    <div class="bg-card border border-border rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-foreground mb-3">Global Access</h3>
                        <p class="text-sm text-muted-foreground">
                            Talent is everywhere, but opportunity is not. We're leveling the playing field for workers
                            worldwide.
                        </p>
                    </div>
                    <div class="bg-card border border-border rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-foreground mb-3">Worker Empowerment</h3>
                        <p class="text-sm text-muted-foreground">
                            We champion independent work. Workers on our platform are their own bosses, with full control
                            over their earnings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-primary text-primary-foreground py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2">70+</div>
                    <div class="text-primary-foreground/80 font-medium">Countries</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">500+</div>
                    <div class="text-primary-foreground/80 font-medium">Businesses</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">10k+</div>
                    <div class="text-primary-foreground/80 font-medium">Verified Workers</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">98%</div>
                    <div class="text-primary-foreground/80 font-medium">Shift Fill Rate</div>
                </div>
            </div>
        </div>
    </section>
@endsection