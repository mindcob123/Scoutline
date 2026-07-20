<x-layout>
    <x-slot:title>Scoutline — Local Business & Lead Radar</x-slot:title>

    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    </x-slot:styles>
    <!-- TOP NAVIGATION BAR -->
    <nav class="navbar">
        <a href="/" class="brand-logo">
             Scout<span>line</span>
        </a>
        <div class="nav-actions">
            <a href="/login" class="btn-outline">Sign in</a>
            <a href="/signup" class="btn-outline" style="margin-left: 10px; border-color: var(--primary-gold); color: var(--primary-gold);">Sign Up</a>
        </div>
    </nav>
    <!-- HERO SECTION -->
    <div class="hero-wrapper">
        
        <!-- Animated Intelligence Radar -->
        <div class="radar-viewport">
            <div class="radar-container">
                <div class="radar-axis-h"></div>
                <div class="radar-axis-v"></div>
                <div class="radar-pulse-wave pulse-1"></div>
                <div class="radar-pulse-wave pulse-2"></div>
                <div class="radar-pulse-wave pulse-3"></div>
                <div class="radar-sweep"></div>
                <div class="radar-target target-1"></div>
                <div class="radar-target target-2"></div>
                <div class="radar-target target-3"></div>
            </div>
        </div>

        <!-- Tagline -->
        <p class="radar-tagline">Local Business & Lead Radar</p>
        
        <!-- Main Headline -->
        <h1 class="hero-headline">
            Scan a neighborhood. <span>Log every decision-maker.</span>
        </h1>
        
    </div>
</x-layout>