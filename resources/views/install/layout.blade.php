<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Install · Flaming Chariot Outreach</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas">
    <main class="container-site max-w-3xl py-12">
        <div class="mb-8 flex items-center gap-3 text-fg">
            <span class="flex size-10 items-center justify-center rounded-full bg-gold text-ink"><x-glyph name="flame" class="size-6" /></span>
            <span class="font-display text-lg font-extrabold">Flaming Chariot Outreach · Installer</span>
        </div>
        @yield('content')
    </main>
</body>
</html>
