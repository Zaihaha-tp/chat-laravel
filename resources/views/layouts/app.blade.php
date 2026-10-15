<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TempChat') — Temporary Chat Rooms</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-[#161614] text-[#E5E4DF] antialiased" style="font-family: 'Inter', system-ui, sans-serif;">
    <header class="border-b border-[#2a2a28]">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-medium tracking-tight" style="font-family: 'JetBrains Mono', monospace;">
                temp<span class="text-[#5FB5D6]">chat</span>
            </a>
            <span class="text-xs uppercase tracking-widest text-[#7B7A74]" style="font-family: 'JetBrains Mono', monospace;">24h rooms · no login</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-[#2a2a28] bg-[#1D1D1B] px-4 py-3 text-sm text-[#E5E4DF]">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <div id="toasts" class="fixed bottom-4 right-4 z-50 flex w-72 flex-col gap-2"></div>

    @stack('scripts')
</body>
</html>
