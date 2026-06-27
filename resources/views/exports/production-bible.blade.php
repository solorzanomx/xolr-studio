<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Production Bible — {{ $project->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Georgia', serif; color: #1a1a1a; background: #fff; font-size: 13px; line-height: 1.6; }
        .page { max-width: 900px; margin: 0 auto; padding: 40px; }

        /* Cover */
        .cover { text-align: center; padding: 80px 40px; border-bottom: 3px solid #1a1a1a; margin-bottom: 60px; }
        .cover h1 { font-size: 42px; font-weight: 700; letter-spacing: -1px; margin-bottom: 8px; }
        .cover .subtitle { font-size: 16px; color: #666; font-style: italic; }
        .cover .meta { margin-top: 24px; font-size: 11px; color: #999; font-family: monospace; }
        .cover .badge { display: inline-block; background: #1a1a1a; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-family: monospace; margin-top: 16px; letter-spacing: 2px; text-transform: uppercase; }

        /* Sections */
        h2 { font-size: 24px; font-weight: 700; margin: 48px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1a1a; }
        h3 { font-size: 16px; font-weight: 700; margin: 24px 0 8px; }
        h4 { font-size: 13px; font-weight: 700; margin: 16px 0 4px; color: #555; }
        p { margin-bottom: 8px; color: #333; }

        /* Episode block */
        .episode { margin-bottom: 32px; padding: 20px; border: 1px solid #e0e0e0; border-radius: 4px; }
        .episode-header { display: flex; gap: 12px; align-items: baseline; margin-bottom: 12px; }
        .episode-number { font-family: monospace; font-size: 11px; background: #f0f0f0; padding: 2px 8px; border-radius: 3px; }
        .episode-title { font-size: 15px; font-weight: 700; }

        /* Scene */
        .scene { margin: 12px 0; padding: 12px; background: #fafafa; border-left: 3px solid #ccc; }
        .scene-label { font-family: monospace; font-size: 10px; color: #999; text-transform: uppercase; margin-bottom: 4px; }

        /* Shots grid */
        .shots { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .shot-card { width: 100px; background: #f5f5f5; border: 1px solid #e0e0e0; border-radius: 3px; overflow: hidden; text-align: center; }
        .shot-card img { width: 100%; height: 60px; object-fit: cover; display: block; }
        .shot-card .no-render { width: 100%; height: 60px; background: #e8e8e8; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; }
        .shot-card .shot-info { padding: 4px; }
        .shot-card .shot-num { font-family: monospace; font-size: 9px; color: #666; }
        .shot-card .shot-type { font-size: 9px; color: #999; }

        /* Universe notes */
        .note { margin: 8px 0; padding: 10px 14px; border: 1px solid #e8e8e8; border-radius: 3px; }
        .note strong { display: block; font-size: 12px; margin-bottom: 4px; }

        /* Print */
        @media print {
            body { font-size: 11px; }
            .no-print { display: none !important; }
            .page { padding: 0; }
            h2 { page-break-before: always; }
            h2:first-of-type { page-break-before: avoid; }
        }

        /* Print button */
        .no-print { position: fixed; top: 16px; right: 16px; background: #1a1a1a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 13px; font-family: sans-serif; }
        .no-print:hover { background: #333; }
    </style>
</head>
<body>
<div class="page">

    <button class="no-print" onclick="window.print()">Imprimir / Guardar PDF</button>

    <!-- Cover -->
    <div class="cover">
        <p class="subtitle">Production Bible</p>
        <h1>{{ $project->name }}</h1>
        @if($project->description)
            <p class="subtitle" style="margin-top: 12px;">{{ $project->description }}</p>
        @endif
        <span class="badge">{{ strtoupper(str_replace('_', ' ', $project->type ?? '')) }}</span>
        <p class="meta">Generado el {{ $generated }} · Xolr Studio</p>
    </div>

    @if($project->synopsis)
    <h2>Sinopsis</h2>
    <p>{{ $project->synopsis }}</p>
    @endif

    @if($project->universeNotes->count())
    <h2>Universe Bible</h2>
    @foreach($project->universeNotes as $note)
    <div class="note">
        <strong>{{ $note->title }}</strong>
        <p>{{ $note->content }}</p>
    </div>
    @endforeach
    @endif

    @foreach($project->seasons as $season)
    <h2>Temporada {{ $season->number }}@if($season->title): {{ $season->title }}@endif</h2>

    @foreach($season->episodes as $episode)
    <div class="episode">
        <div class="episode-header">
            <span class="episode-number">EP{{ str_pad($episode->number, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="episode-title">{{ $episode->title }}</span>
        </div>
        @if($episode->logline)
            <p><em>{{ $episode->logline }}</em></p>
        @endif
        @if($episode->synopsis)
            <p>{{ $episode->synopsis }}</p>
        @endif

        @foreach($episode->scenes as $scene)
        <div class="scene">
            <p class="scene-label">Escena {{ $scene->number }}@if($scene->title) — {{ $scene->title }}@endif</p>
            @if($scene->description)<p>{{ $scene->description }}</p>@endif

            @if($scene->shots->count())
            <div class="shots">
                @foreach($scene->shots as $shot)
                <div class="shot-card">
                    @if($shot->approvedRender && $shot->approvedRender->file_path && str_starts_with($shot->approvedRender->file_path, 'http'))
                        <img src="{{ $shot->approvedRender->file_path }}" alt="Shot {{ $shot->number }}">
                    @else
                        <div class="no-render">Sin render</div>
                    @endif
                    <div class="shot-info">
                        <p class="shot-num">S{{ $shot->number }}</p>
                        <p class="shot-type">{{ $shot->shot_type }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach
    @endforeach

</div>
</body>
</html>
