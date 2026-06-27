<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\CameraStyle;
use App\Models\VisualStyle;
use Inertia\Inertia;
use Inertia\Response;

class StyleController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Library/Styles/Index', [
            'visualStyles' => VisualStyle::where('is_active', true)->get(),
            'cameraStyles' => CameraStyle::where('is_active', true)->get(),
        ]);
    }
}
