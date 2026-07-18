<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DirectPrintService
{
    public function printView(string $view, array $data, string $filename): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('Impressao direta esta configurada apenas para Windows/Laragon.');
        }

        $sumatraPath = $this->sumatraPath();

        if (! $sumatraPath) {
            throw new RuntimeException('Instale o SumatraPDF e configure o caminho em Definicoes > Impressao / Ticket > Impressora direta.');
        }

        $directory = storage_path('app/direct-print');
        File::ensureDirectoryExists($directory);

        $path = $directory . DIRECTORY_SEPARATOR . Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '-' . now()->format('YmdHis') . '.pdf';

        $printSettings = BusinessSettings::print();
        $paperWidthMm = (float) ($printSettings['paper_width_mm'] ?? 80);
        $paperWidth = $this->mmToPoints($paperWidthMm);
        $paperHeight = $this->mmToPoints($this->estimatePaperHeightMm($view, $data, $printSettings));

        Pdf::loadView($view, array_merge($data, [
            'directPrint' => true,
            'printSettings' => $printSettings,
        ]))
            ->setOption('defaultMediaType', 'print')
            ->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait')
            ->save($path);

        $printerName = trim((string) (BusinessSettings::directPrint()['printer_name'] ?? ''));
        $arguments = [
            '-silent',
            '-exit-when-done',
            '-print-settings',
            'noscale',
        ];

        if ($printerName) {
            $arguments[] = '-print-to';
            $arguments[] = $printerName;
        } else {
            $arguments[] = '-print-to-default';
        }

        $arguments[] = $path;

        $process = new Process([
            'powershell',
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-Command',
            'Start-Process -FilePath ' . $this->powershellQuote($sumatraPath)
                . ' -ArgumentList @(' . implode(',', array_map($this->powershellQuote(...), $arguments)) . ')'
                . ' -WindowStyle Hidden',
        ]);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Falha ao iniciar a impressao direta.');
        }
    }

    private function sumatraPath(): ?string
    {
        $directPrint = BusinessSettings::directPrint();
        $configured = $directPrint['sumatra_path'] ?? config('printing.sumatra_path');

        if ($configured && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            env('LOCALAPPDATA') ? env('LOCALAPPDATA') . '\\SumatraPDF\\SumatraPDF.exe' : null,
            'C:\\Users\\Algardata\\AppData\\Local\\SumatraPDF\\SumatraPDF.exe',
            'C:\\Program Files\\SumatraPDF\\SumatraPDF.exe',
            'C:\\Program Files (x86)\\SumatraPDF\\SumatraPDF.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function powershellQuote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function mmToPoints(float $millimeters): float
    {
        return $millimeters * 72 / 25.4;
    }

    private function estimatePaperHeightMm(string $view, array $data, array $printSettings): float
    {
        $baseHeight = 82.0;
        $itemHeight = 5.8;
        $taxRowHeight = 4.4;
        $footerHeight = 28.0;
        $lineWrapHeight = 4.2;
        $items = [];
        $taxRows = 0;

        if ($view === 'admin.sales.ticket' && isset($data['sale'])) {
            $items = $data['sale']->items ?? [];
            $taxRows = $this->saleTaxRows($items);
            $footerHeight += 52;
        } elseif ($view === 'admin.credit-notes.ticket' && isset($data['creditNote'])) {
            $items = $data['creditNote']->items ?? [];
            $taxRows = $this->saleTaxRows($items);
            $footerHeight += 60;
        } elseif ($view === 'admin.restaurant.table-ticket' && isset($data['order'])) {
            $items = $data['order']->items ?? [];
            $taxRows = count($data['totals']['tax_breakdown'] ?? []);
        } elseif ($view === 'admin.shifts.ticket') {
            $payments = $data['payments'] ?? [];
            $cashMovements = $data['cashMovements'] ?? [];

            return min(900, max(180, 96 + (count($payments) + count($cashMovements)) * 5.4));
        }

        $height = $baseHeight + $footerHeight + max(1, count($items)) * $itemHeight + max(0, $taxRows) * $taxRowHeight;

        foreach ($items as $item) {
            $name = (string) ($item->product->name ?? 'Produto removido');
            $height += max(0, (int) ceil(mb_strlen($name) / 28) - 1) * $lineWrapHeight;
        }

        $height += (float) ($printSettings['ticket_padding_mm'] ?? 0) * 2;

        return min(900, max(160, $height));
    }

    private function saleTaxRows(iterable $items): int
    {
        $rates = [];

        foreach ($items as $item) {
            $rate = round((float) ($item->tax_rate ?? $item->product?->tax_rate ?? 0), 2);
            $rates[number_format($rate, 2, '.', '')] = true;
        }

        return count($rates);
    }
}
