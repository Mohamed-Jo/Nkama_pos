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
        $paperWidth = ((float) ($printSettings['paper_width_mm'] ?? 80)) * 72 / 25.4;

        Pdf::loadView($view, array_merge($data, [
            'directPrint' => true,
            'printSettings' => $printSettings,
        ]))
            ->setPaper([0, 0, $paperWidth, 1200], 'portrait')
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
}
