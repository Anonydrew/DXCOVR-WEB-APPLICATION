<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} | DXCOVR</title>
    <style>
        :root { color-scheme: dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #080a08; color: #f5f7f2; font: 16px/1.6 system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { max-width: 36rem; padding: 2rem 1rem; }
        p.code { font: 500 .75rem/1 ui-monospace, monospace; letter-spacing: .12em; text-transform: uppercase; color: #a6ec46; }
        h1 { font-size: clamp(2rem, 6vw, 3rem); line-height: 1.05; letter-spacing: -.02em; margin: 1rem 0; }
        p { color: #b8bdb4; }
        a { display: inline-block; margin-top: 1.5rem; padding: .8rem 1.4rem; border-radius: 999px; background: #a6ec46; color: #080a08; font-weight: 600; text-decoration: none; }
        a:focus-visible { outline: 2px solid #f5f7f2; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <p class="code">Error {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="/">Back to the homepage</a>
    </main>
</body>
</html>
