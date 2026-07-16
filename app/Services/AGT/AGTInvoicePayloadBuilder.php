<?php

namespace App\Services\AGT;

use App\Models\CreditNote;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class AGTInvoicePayloadBuilder
{
    public function __construct(private AGTSignatureService $signatureService)
    {
    }

    public function saleDocument(Sale $sale): array
    {
        $sale->loadMissing('customer', 'items.product', 'documentSeries');

        $document = $this->documentSkeleton(
            $sale,
            $sale->document_type_code ?: 'FR',
            $this->agtDocumentNo($sale, $sale->document_type_code ?: 'FR'),
            $sale->customer,
            $this->saleLines($sale)
        );

        $document['documentTotals'] = $this->totalsFromLines($document['lines']);
        $document['jwsDocumentSignature'] = $this->signatureService->signDocument($this->documentSignaturePayload($document));

        return $document;
    }

    public function creditNoteDocument(CreditNote $creditNote): array
    {
        $creditNote->loadMissing('customer', 'items.product', 'originalSale.agtDocument');

        $document = $this->documentSkeleton(
            $creditNote,
            'NC',
            $this->agtDocumentNo($creditNote, 'NC'),
            $creditNote->customer,
            $this->creditNoteLines($creditNote)
        );

        $document['documentTotals'] = $this->totalsFromLines($document['lines']);
        $document['jwsDocumentSignature'] = $this->signatureService->signDocument($this->documentSignaturePayload($document));

        return $document;
    }

    private function agtDocumentNo(Model $document, string $type): string
    {
        $document->loadMissing('documentSeries');

        $seriesCode = trim((string) $document->documentSeries?->code);
        $sequence = max((int) data_get($document, 'document_number'), 1);

        if ($seriesCode === '' || preg_match('/^\d{4}$/', $seriesCode) || ! (bool) $document->documentSeries?->active) {
            $year = (int) (optional($document->created_at)->format('Y') ?: now()->year);
            $documentTypeId = \App\Models\DocumentType::query()
                ->where('code', strtoupper(trim($type)))
                ->value('id');

            $activeSeriesCode = $documentTypeId
                ? \App\Models\DocumentSeries::query()
                    ->where('document_type_id', $documentTypeId)
                    ->where('year', $year)
                    ->where('active', true)
                    ->where('code', '<>', (string) $year)
                    ->orderByDesc('id')
                    ->value('code')
                : null;

            $seriesCode = (string) ($activeSeriesCode ?: $seriesCode ?: $year);
        }

        return strtoupper(trim($type)) . ' ' . $seriesCode . '/' . $sequence;
    }

    private function documentSignaturePayload(array $document): array
    {
        return [
            'documentNo' => $document['documentNo'],
            'taxRegistrationNumber' => (string) config('agt.nif'),
            'documentType' => $document['documentType'],
            'documentDate' => $document['documentDate'],
            'customerTaxID' => $document['customerTaxID'],
            'customerCountry' => $document['customerCountry'],
            'companyName' => $document['companyName'],
            'documentTotals' => $document['documentTotals'],
        ];
    }
    private function documentSkeleton(Model $document, string $type, string $number, ?Model $customer, array $lines): array
    {
        return [
            'documentNo' => $number,
            'documentType' => strtoupper($type),
            'documentStatus' => 'N',
            'documentDate' => optional($document->created_at)->format('Y-m-d') ?: now()->format('Y-m-d'),
            'companyName' => trim((string) ($customer?->name ?: 'CONSUMIDOR FINAL')),
            'customerTaxID' => $this->customerTaxId($customer),
            'customerCountry' => 'AO',
            'systemEntryDate' => optional($document->created_at)->format('Y-m-d\TH:i:sP') ?: now()->format('Y-m-d\TH:i:sP'),
            'lines' => $lines,
        ];
    }

    private function saleLines(Sale $sale): array
    {
        return $sale->items->values()->map(function ($item, int $index) {
            $net = round((float) ($item->net_subtotal ?? 0), 2);
            $tax = $this->taxContribution($net, (float) $item->tax_rate);
            $quantity = max((float) $item->quantity, 0.0001);
            $unitPrice = round($net / $quantity, 2);

            return [
                'lineNumber' => $index + 1,
                'productCode' => (string) ($item->product?->barcode ?: $item->product_id ?: '0001'),
                'productDescription' => trim((string) ($item->product?->name ?: 'Produto')),
                'quantity' => round($quantity, 4),
                'unitOfMeasure' => (string) ($item->product?->unit ?: 'UN'),
                'unitPrice' => $unitPrice,
                'unitPriceBase' => $unitPrice,
                'debitAmount' => 0,
                'creditAmount' => $net,
                'settlementAmount' => 0,
                'taxes' => [$this->taxLine((float) $item->tax_rate, $tax)],
            ];
        })->all();
    }

    private function creditNoteLines(CreditNote $creditNote): array
    {
        return $creditNote->items->values()->map(function ($item, int $index) use ($creditNote) {
            $net = round((float) ($item->net_subtotal ?? 0), 2);
            $tax = $this->taxContribution($net, (float) $item->tax_rate);
            $quantity = max((float) $item->quantity, 0.0001);
            $unitPrice = round($net / $quantity, 2);

            return [
                'lineNumber' => $index + 1,
                'productCode' => (string) ($item->product?->barcode ?: $item->product_id ?: '0001'),
                'productDescription' => trim((string) ($item->product?->name ?: 'Produto')),
                'quantity' => round($quantity, 4),
                'unitOfMeasure' => (string) ($item->product?->unit ?: 'UN'),
                'unitPrice' => $unitPrice,
                'unitPriceBase' => $unitPrice,
                'debitAmount' => $net,
                'creditAmount' => 0,
                'referenceInfo' => [
                    'reference' => $this->referencedDocumentNo($creditNote),
                    'reason' => (string) ($creditNote->reason ?: 'Anulacao/retificacao da fatura'),
                    'referenceItemLineNo' => $index + 1,
                ],
                'settlementAmount' => 0,
                'taxes' => [$this->taxLine((float) $item->tax_rate, $tax)],
            ];
        })->all();
    }

    private function referencedDocumentNo(CreditNote $creditNote): string
    {
        $agtDocument = $creditNote->originalSale?->agtDocument;

        return (string) (
            data_get($agtDocument?->last_response, 'status_query.documentStatusList.0.documentNo')
            ?: data_get($agtDocument?->payload, 'documents.0.documentNo')
            ?: $creditNote->originalSale?->invoice_number
            ?: ''
        );
    }
    private function totalsFromLines(array $lines): array
    {
        $net = 0.0;
        $tax = 0.0;

        foreach ($lines as $line) {
            $net += (float) ($line['creditAmount'] ?: $line['debitAmount'] ?: 0);

            foreach ($line['taxes'] ?? [] as $taxLine) {
                $tax += (float) ($taxLine['taxContribution'] ?? 0);
            }
        }

        return [
            'netTotal' => round($net, 2),
            'taxPayable' => round($tax, 2),
            'grossTotal' => round($net + $tax, 2),
        ];
    }

    private function taxLine(float $rate, float $amount): array
    {
        return [
            'taxType' => 'IVA',
            'taxCountryRegion' => 'AO',
            'taxCode' => $rate > 0 ? 'NOR' : 'ISE',
            'taxPercentage' => round($rate, 2),
            'taxContribution' => round($amount, 2),
        ];
    }

    private function taxContribution(float $net, float $rate): float
    {
        if ($rate <= 0 || $net <= 0) {
            return 0.0;
        }

        return ceil(($net * $rate / 100) * 100) / 100;
    }

    private function customerTaxId(?Model $customer): string
    {
        $taxId = trim((string) data_get($customer, 'nif'));

        if ($taxId === '' || $taxId === '0') {
            return '999999999';
        }

        return $taxId;
    }
}
