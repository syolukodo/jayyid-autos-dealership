<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt: keeps crawlers on public pages and away from the portals,
 * JSON endpoints and endless filter/sort combinations. Non-production
 * environments block everything so staging copies never get indexed.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = app()->environment('production')
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /account',
                'Disallow: /api/',
                'Disallow: /login',
                'Disallow: /register',
                'Disallow: /verify-email',
                'Disallow: /staff/',
                'Disallow: /*?*sort=',
                'Disallow: /*?*budget=',
                'Allow: /',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
