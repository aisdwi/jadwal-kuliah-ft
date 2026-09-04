<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Website Penjadwalan Perkuliahan - Universitas Riau</title>
        <meta name="description" content="Sistem Penjadwalan Perkuliahan Universitas Riau">
        <link rel="icon" type="image/png" href="/unri-logo.png?v=2">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

        @php
            // Resolve the latest built assets so the deployed SPA stays in sync after npm run build.
            $assetPath = public_path('build/assets');
            $latestAsset = static function (string $pattern) use ($assetPath): ?string {
                $files = glob($assetPath . '/' . $pattern) ?: [];
                usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

                return $files[0] ?? null;
            };

            $cssFile = $latestAsset('main-*.css');
            $jsFile = $latestAsset('main-*.js');
        @endphp

        @if ($cssFile)
            <link rel="stylesheet" href="{{ asset('build/assets/' . basename($cssFile)) }}">
        @endif
    </head>
    <body>
        <div id="root"></div>

        @if ($jsFile)
            <script type="module" src="{{ asset('build/assets/' . basename($jsFile)) }}"></script>
        @else
            <p style="font-family: sans-serif; padding: 2rem;">Frontend asset belum tersedia. Jalankan npm run build.</p>
        @endif
    </body>
</html>
