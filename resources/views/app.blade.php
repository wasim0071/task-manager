<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="IQT-FSD-2026 task board. A Laravel and Vue application for tracking work.">
    <meta name="theme-color" content="#1c1915">
    <title>IQT-FSD-2026 Task Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <header class="topbar">
            <div class="wrap topbar-inner">
                <div class="brand">
                    <h1>IQT-FSD-2026</h1>
                    <p class="lede">Loading the task board…</p>
                </div>
            </div>
        </header>
    </div>
    <noscript>
        <p class="noscript">IQT-FSD-2026 needs JavaScript enabled to run the task board.</p>
    </noscript>
</body>
</html>
