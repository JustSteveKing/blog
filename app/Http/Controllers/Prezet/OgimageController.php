<?php

declare(strict_types=1);

namespace App\Http\Controllers\Prezet;

use Illuminate\View\View;
use Prezet\Prezet\Models\Document;

final class OgimageController
{
    public function __invoke(string $slug): View
    {
        $doc = app(Document::class)::query()
            ->where('slug', $slug)
            ->when('local' !== config('app.env'), fn($query) => $query->where('draft', false))
            ->firstOrFail();

        return view('prezet.ogimage', [
            'fm' => $doc->frontmatter,
        ]);
    }
}
