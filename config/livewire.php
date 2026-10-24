<?php

return [

    // The app is deployed under a subdirectory (symlinked at /evermore-api
    // instead of the domain root), so Livewire's hand-built script tag URL
    // needs an explicit override — Laravel's own URL generator already
    // handles the subdirectory correctly everywhere else, but Livewire's
    // asset script tag bypasses it and just prefixes the raw route URI with
    // "/", which resolves to the wrong (non-existent) path at the domain
    // root. See: https://livewire.laravel.com/docs/installation#configuring-livewires-asset-url
    'asset_url' => env('LIVEWIRE_ASSET_URL'),

];
