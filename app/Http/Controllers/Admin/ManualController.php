<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    public function userManual(): BinaryFileResponse
    {
        $path = base_path('output/pdf/manual-utilizador-maria-erp.pdf');

        abort_unless(is_file($path), 404, 'Manual de utilizador não encontrado.');

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="manual-utilizador-maria-erp.pdf"',
        ]);
    }
}