<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Suivi nouvelles APPs') — Veille Apps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 800: '#1e1e2e', 900: '#11111b', 700: '#313244' }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .score-cell { font-weight: 700; font-size: 1.1rem; }
        .score-high { color: #22c55e; }
        .score-mid { color: #eab308; }
        .score-low { color: #ef4444; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-dark-900 text-gray-200 min-h-screen">
    <nav class="bg-dark-800 border-b border-dark-700 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-white">
                Suivi nouvelles APPs
            </a>
            <div class="flex items-center gap-4 text-sm text-gray-400">
                @yield('nav-stats')
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
