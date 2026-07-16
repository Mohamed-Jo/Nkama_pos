<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgtDocument;
use App\Models\AgtSeries;
use App\Models\CreditNote;
use App\Models\DocumentSeries;
use App\Models\Sale;
use App\Services\AGT\AGTApiService;
use App\Services\AGTSeriesRequestService;
use App\Services\AGTElectronicInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgtDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'ready');

        $documents = AgtDocument::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pendingSales = Sale::query()
            ->whereDoesntHave('agtDocument')
            ->with('customer')
            ->latest()
            ->take(25)
            ->get();

        $pendingCreditNotes = CreditNote::query()
            ->whereDoesntHave('agtDocument')
            ->with('customer')
            ->latest()
            ->take(25)
            ->get();

        $counts = AgtDocument::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $series = DocumentSeries::query()
            ->with(['type', 'agtSeries'])
            ->orderByDesc('year')
            ->orderBy('code')
            ->get();

        $agtSeries = AgtSeries::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return view('admin.agt.index', [
            'documents' => $documents,
            'pendingSales' => $pendingSales,
            'pendingCreditNotes' => $pendingCreditNotes,
            'counts' => $counts,
            'series' => $series,
            'agtSeries' => $agtSeries,
            'status' => $status,
            'agtEnabled' => (bool) config('agt.enabled'),
            'agtEndpoint' => config('agt.endpoints.' . config('agt.environment') . '.registar_factura'),
            'agtEnvironment' => config('agt.environment'),
            'agtNif' => config('agt.nif'),
        ]);
    }

    public function requestSeries(Request $request, AGTApiService $agt, AGTSeriesRequestService $seriesService): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:FR,FT,NC'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $response = $agt->solicitarSerie($validated['document_type'], (int) $validated['year']);
        $success = $this->agtSuccess($response);

        $this->updateLocalSeriesStatus($validated['document_type'], (int) $validated['year'], $success, $response, $seriesService);

        return back()->with(
            $success ? 'success' : 'error',
            $success
                ? 'Solicitacao de serie enviada/aceite pela AGT.'
                : (($response['localError'] ?? false)
                    ? 'Solicitacao de serie nao enviada: ' . $this->agtMessage($response)
                    : 'AGT rejeitou a solicitacao de serie: ' . $this->agtMessage($response))
        );
    }

    public function listSeries(Request $request, AGTApiService $agt, AGTSeriesRequestService $seriesService): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:FR,FT,NC'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $year = isset($validated['year']) ? (int) $validated['year'] : null;
        $response = $agt->listarSeries($validated['document_type'], $year);
        $success = $this->agtSuccess($response);
        $synced = $seriesService->syncListedSeries($validated['document_type'], $year, $response);

        return back()->with(
            $success ? 'success' : 'error',
            $success
                ? 'Consulta de series AGT concluida. Series sincronizadas: ' . $synced . '.'
                : (($response['localError'] ?? false)
                    ? 'Consulta de series nao enviada: ' . $this->agtMessage($response)
                    : 'Nao foi possivel listar series AGT: ' . $this->agtMessage($response))
        )->with('agt_series_response', $response);
    }

    public function prepareSale(Sale $sale, AGTElectronicInvoiceService $service): RedirectResponse
    {
        $document = $service->prepareSale($sale);

        return back()->with('success', 'Documento AGT preparado: ' . $document->invoice_number);
    }

    public function prepareCreditNote(CreditNote $creditNote, AGTElectronicInvoiceService $service): RedirectResponse
    {
        $document = $service->prepareCreditNote($creditNote);

        return back()->with('success', 'Nota de credito AGT preparada: ' . $document->invoice_number);
    }

    public function send(AgtDocument $agtDocument, AGTElectronicInvoiceService $service): RedirectResponse
    {
        $document = $service->send($agtDocument);

        return back()->with(
            $document->status === 'failed' ? 'error' : 'success',
            $document->invoice_number . ' - ' . $document->validation_message
        );
    }

    private function updateLocalSeriesStatus(string $documentType, int $year, bool $success, array $response, AGTSeriesRequestService $seriesService): void
    {
        DocumentSeries::query()
            ->with('type')
            ->where('year', $year)
            ->whereHas('type', fn ($query) => $query->where('code', $documentType))
            ->get()
            ->each(fn (DocumentSeries $series) => $seriesService->recordSeriesResponse($series, $response, $success));
    }

    private function agtSuccess(array $response): bool
    {
        if ((int) ($response['resultCode'] ?? 0) === 1) {
            return true;
        }

        if (isset($response['errorList']) && ! empty($response['errorList'])) {
            return false;
        }

        return isset($response['seriesInfo']) || isset($response['requestID']) || isset($response['documents']);
    }

    private function agtMessage(array $response): string
    {
        $errors = $response['errorList'] ?? $response['errors'] ?? null;

        if (is_array($errors)) {
            return collect($errors)
                ->map(fn ($error) => is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $error)
                ->filter()
                ->take(3)
                ->implode(' | ') ?: 'Erro AGT nao especificado.';
        }

        return (string) ($errors ?: ($response['message'] ?? 'Erro AGT nao especificado.'));
    }
}