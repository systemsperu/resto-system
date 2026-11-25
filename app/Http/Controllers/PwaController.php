<?php

namespace App\Http\Controllers;

use App\Branding\BrandSettings;
use App\Models\Installation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function manifest(BrandSettings $branding): JsonResponse
    {
        $brand = $branding->get();

        return response()->json([
            'id' => '/?app=recreosuite', 'name' => $brand->companyName, 'short_name' => mb_substr($brand->companyName, 0, 20),
            'description' => 'Gestión de restaurante y servicios', 'lang' => 'es-PE', 'dir' => 'ltr',
            'start_url' => '/login?source=pwa', 'scope' => '/', 'display' => 'standalone',
            'background_color' => '#FFFFFF', 'theme_color' => $brand->primaryColor,
            'icons' => [
                ['src' => route('pwa.icon', ['size' => 192]), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => route('pwa.icon', ['size' => 512]), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'shortcuts' => [
                ['name' => 'Ingresar', 'url' => '/login', 'icons' => [['src' => route('pwa.icon', ['size' => 192]), 'sizes' => '192x192']]],
                ['name' => 'Carta', 'url' => '/menu', 'icons' => [['src' => route('pwa.icon', ['size' => 192]), 'sizes' => '192x192']]],
            ],
        ])->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function icon(int $size, BrandSettings $branding): Response
    {
        abort_unless(in_array($size, [192, 512], true), 404);
        $brand = $branding->get();
        $image = imagecreatetruecolor($size, $size);
        [$red, $green, $blue] = sscanf($brand->primaryColor, '#%02x%02x%02x');
        imagefill($image, 0, 0, imagecolorallocate($image, $red, $green, $blue));
        $white = imagecolorallocate($image, 255, 255, 255);
        $font = max(5, (int) ($size / 12));
        $letter = mb_strtoupper(mb_substr($brand->companyName, 0, 1));
        imagestring($image, 5, (int) (($size - imagefontwidth(5) * strlen($letter)) / 2), (int) (($size - imagefontheight(5)) / 2), $letter, $white);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function offline(): View
    {
        return view('pwa.offline');
    }

    public function status(): JsonResponse
    {
        $installation = Installation::first();

        return response()->json(['lan' => true, 'cloud' => $installation?->sync_status ?? 'disabled', 'last_synced_at' => $installation?->last_synced_at?->toIso8601String()])->header('Cache-Control', 'no-store');
    }
}
