<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $season->project->name }} — T{{ $season->number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Georgia', serif; color: #1a1a1a; background: #fff; line-height: 1.8; }
        .page { max-width: 680px; margin: 0 auto; padding: 60px 40px; }

        /* Cover */
        .cover { text-align: center; padding: 100px 0; margin-bottom: 80px; }
        .cover .series { font-size: 13px; letter-spacing: 4px; text-transform: uppercase; color: #999; margin-bottom: 16px; font-family: monospace; }
        .cover h1 { font-size: 38px; font-weight: 400; letter-spacing: -1px; margin-bottom: 8px; }
        .cover .season { font-size: 16px; color: #666; margin-bottom: 32px; }
        .cover .divider { width: 40px; height: 2px; background: #1a1a1a; margin: 0 auto 24px; }
        .cover .meta { font-size: 11px; color: #bbb; font-family: monospace; }

        /* Chapter */
        .chapter { margin-bottom: 80px; }
        .chapter-header { margin-bottom: 32px; padding-bottom: 16px; border-bottom: 1px solid #e0e0e0; }
        .chapter-eyebrow { font-family: monospace; font-size: 10px; color: #999; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 6px; }
        .chapter-title { font-size: 28px; font-weight: 400; }
        .chapter-logline { font-size: 14px; color: #666; font-style: italic; margin-top: 8px; }

        /* Scene */
        .scene-break { text-align: center; margin: 32px 0; color: #bbb; letter-spacing: 8px; font-size: 16px; }
        .scene-heading { font-size: 11px; font-family: monospace; color: #999; letter-spacing: 2px; text-transform: uppercase; margin: 24px 0 12px; }

        /* Render illustration */
        .illustration { margin: 24px 0; text-align: center; }
        .illustration img { max-width: 100%; border-radius: 4px; }
        .illustration .caption { font-size: 11px; color: #999; font-style: italic; margin-top: 8px; font-family: monospace; }
        .illustration .placeholder { height: 200px; background: #f5f5f5; border: 1px solid #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #bbb; font-family: monospace; font-size: 11px; }

        /* Text content */
        p { margin-bottom: 16px; font-size: 15px; }
        .dialogue { margin: 16px 40px; font-style: italic; color: #444; border-left: 2px solid #ddd; padding-left: 16px; }
        .dialogue .speaker { font-family: monospace; font-size: 11px; color: #999; text-transform: uppercase; font-style: normal; display: block; margin-bottom: 4px; }

        /* Print */
        @media print {
            .no-print { display: none !important; }
            .page { padding: 0; }
            .chapter { page-break-before: always; }
            .chapter:first-of-type { page-break-before: avoid; }
        }

        .no-print { position: fixed; top: 16px; right: 16px; background: #1a1a1a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 13px; font-family: sans-serif; }
        .no-print:hover { background: #333; }
    </style>
</head>
<body>
<div class="page">

    <button class="no-print" onclick="window.print()">Imprimir / Guardar PDF</button>

    <!-- Cover -->
    <div class="cover">
        <p class="series">{{ $season->project->name }}</p>
        <h1>{{ $season->title ?? "Temporada {$season->number}" }}</h1>
        <p class="season">Volumen {{ $season->number }}</p>
        <div class="divider"></div>
        <p class="meta">Generado el {{ $generated }} · Xolr Studio</p>
    </div>

    @foreach($season->episodes as $episode)
    <div class="chapter">
        <div class="chapter-header">
            <p class="chapter-eyebrow">Capítulo {{ $episode->number }}</p>
            <h2 class="chapter-title">{{ $episode->title }}</h2>
            @if($episode->logline)
                <p class="chapter-logline">{{ $episode->logline }}</p>
            @endif
        </div>

        @if($episode->synopsis)
            <p>{{ $episode->synopsis }}</p>
        @endif

        @foreach($episode->scenes as $scene)
        <p class="scene-heading">
            @if($scene->location) {{ $scene->location->name }} · @endif
            {{ $scene->title ?? "Escena {$scene->number}" }}
        </p>

        @if($scene->description)
            <p>{{ $scene->description }}</p>
        @endif

        @foreach($scene->shots->filter(fn($s) => $s->approvedRender) as $shot)
        <div class="illustration">
            @if(str_starts_with($shot->approvedRender->file_path ?? '', 'http'))
                <img src="{{ $shot->approvedRender->file_path }}" alt="{{ $shot->description }}" />
            @else
                <div class="placeholder">[ Shot {{ $shot->number }} — sin render ]</div>
            @endif
            @if($shot->description)
                <p class="caption">{{ $shot->description }}</p>
            @endif
        </div>

        @if($shot->dialogue_text)
        <div class="dialogue">
            @if($shot->characters->count())
                <span class="speaker">{{ $shot->characters->first()->name }}</span>
            @endif
            {{ $shot->dialogue_text }}
        </div>
        @endif
        @endforeach

        @if(! $loop->last)
        <p class="scene-break">· · ·</p>
        @endif
        @endforeach
    </div>
    @endforeach

</div>
</body>
</html>
