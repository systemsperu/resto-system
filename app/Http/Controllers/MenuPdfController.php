<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;

class MenuPdfController extends Controller
{
    public function pdf(): Response
    {
        $categories = Category::with(['products' => fn ($query) => $query->where('is_active', true)->where('is_saleable', true)])->where('is_active', true)->get();
        $pdf = Pdf::loadView('menu.pdf', ['categories' => $categories, 'companyName' => Setting::where('key', 'company_name')->value('value') ?? 'Carta', 'currency' => Setting::where('key', 'currency_symbol')->value('value') ?? 'S/'])->setPaper('a4');

        return $pdf->download('carta.pdf');
    }

    public function qr(): Response
    {
        $result = (new PngWriter)->write(new QrCode(route('menu.index'), size: 480, margin: 20));

        return response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
    }
}
