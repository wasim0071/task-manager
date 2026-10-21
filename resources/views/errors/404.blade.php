<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Page not found — IQT-FSD-2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=Outfit:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --ink: #1c1915;
            --muted: #5c564e;
            --paper: #f3efe4;
            --line: #c9bba4;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            color: var(--ink);
            background-color: var(--paper);
            background-image:
                radial-gradient(900px 420px at -10% -10%, rgba(140, 59, 28, 0.09), transparent 55%),
                radial-gradient(720px 380px at 110% 0%, rgba(28, 104, 67, 0.08), transparent 50%);
            font-family: Outfit, ui-sans-serif, system-ui, sans-serif;
        }

        main {
            width: min(32rem, 100%);
            text-align: left;
        }

        p {
            margin: 0;
        }

        .code {
            margin-bottom: 0.75rem;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(3.2rem, 10vw, 5.5rem);
            font-weight: 560;
            letter-spacing: -0.045em;
            line-height: 0.9;
        }

        .lede {
            max-width: 28rem;
            margin-top: 1rem;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.5;
        }

        a {
            display: inline-block;
            margin-top: 1.75rem;
            padding: 0.7rem 1.1rem;
            border-radius: 999px;
            background: var(--ink);
            color: var(--paper);
            font-weight: 600;
            text-decoration: none;
        }

        a:hover { background: #312b24; }

        a:focus-visible {
            outline: 2px solid #8c3b1c;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
    <main>
        <p class="code">IQT-FSD-2026</p>
        <h1>Page not found</h1>
        <p class="lede">That address is not part of the task board. The home page is still where you left it.</p>
        <a href="{{ url('/') }}">Go to home</a>
    </main>
</body>
</html>
