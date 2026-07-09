<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ $title ?? '' }}</title>

    <link rel="stylesheet" href="{{ asset('css/global.css') }}">

    {{ $styles ?? '' }}
</head>
<body>

    <header class="app-header">
        <div class="app-brand">
            <span class="app-brand-mark">Scoutline</span>
            <span class="app-brand-tag">Lead Intelligence</span>
        </div>

        @if(isset($navActions))
            {{ $navActions }}
        @endif
    </header>

    {{ $slot }}

    @if(isset($modalsAndDrawers))
        {{ $modalsAndDrawers }}
    @endif

</body>
</html>