<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('admin.reports.partials.pdf-style')
</head>
<body>
    @include('admin.reports.partials.header')

    <table class="summary">
        <tr>
            <td><span>Vendas</span><strong>{{ number_format($totals['sales'], 2, ',', '.') }}</strong></td>
            <td><span>NC</span><strong>{{ number_format($totals['credit_notes'], 2, ',', '.') }}</strong></td>
            <td><span>Recebimentos</span><strong>{{ number_format($totals['payments'], 2, ',', '.') }}</strong></td>
            <td><span>Compras</span><strong>{{ number_format($totals['purchases'], 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td><span>Mov. caixa</span><strong>{{ number_format($totals['cash_movements'], 2, ',', '.') }}</strong></td>
            <td><span>CC debito</span><strong>{{ number_format($totals['account_debit'], 2, ',', '.') }}</strong></td>
            <td><span>CC credito</span><strong>{{ number_format($totals['account_credit'], 2, ',', '.') }}</strong></td>
            <td><span>Saldo CC mov.</span><strong>{{ number_format($totals['account_debit'] - $totals['account_credit'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <h3>Vendas e Notas de Credito</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Documento</th>
                <th>Entidade</th>
                <th>Operador</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ optional($sale->created_at)->format('H:i') }}</td>
                    <td>{{ $sale->document_type_code ?? 'FR' }} {{ $sale->invoice_number }}</td>
                    <td>{{ $sale->customer->name ?? 'Consumidor Final' }}</td>
                    <td>{{ $sale->operator->name ?? 'Sistema' }}</td>
                    <td class="right">{{ number_format((float) $sale->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sem vendas neste dia.</td></tr>
            @endforelse

            @foreach($creditNotes as $note)
                <tr>
                    <td>{{ optional($note->created_at)->format('H:i') }}</td>
                    <td>NC {{ $note->invoice_number }}</td>
                    <td>{{ $note->customer->name ?? 'Consumidor Final' }}</td>
                    <td>{{ $note->operator->name ?? 'Sistema' }}</td>
                    <td class="right negative">-{{ number_format((float) $note->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Recebimentos e Caixa</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Tipo</th>
                <th>Metodo</th>
                <th>Descricao</th>
                <th class="right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ optional($payment->created_at)->format('H:i') }}</td>
                    <td>Pagamento</td>
                    <td>{{ strtoupper($payment->method ?? '-') }}</td>
                    <td>{{ $payment->sale?->invoice_number ? 'Venda ' . $payment->sale->invoice_number : ($payment->notes ?: '-') }}</td>
                    <td class="right">{{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sem pagamentos neste dia.</td></tr>
            @endforelse

            @foreach($cashMovements as $movement)
                <tr>
                    <td>{{ optional($movement->created_at)->format('H:i') }}</td>
                    <td>{{ $movement->type }}</td>
                    <td>{{ strtoupper($movement->method ?? '-') }}</td>
                    <td>{{ $movement->description ?: '-' }}</td>
                    <td class="right {{ (float) $movement->amount < 0 ? 'negative' : '' }}">{{ number_format((float) $movement->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Conta Corrente</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Entidade</th>
                <th>Origem</th>
                <th>Descricao</th>
                <th class="right">Debito</th>
                <th class="right">Credito</th>
            </tr>
        </thead>
        <tbody>
            @forelse($currentAccountEntries as $entry)
                <tr>
                    <td>{{ $entry->entity_name }}<br><span class="muted">{{ $entry->entity_type === 'customer' ? 'Cliente' : 'Fornecedor' }}</span></td>
                    <td>
                        @php
                            $originLabel = match($entry->document_type) {
                                'sale' => 'FT',
                                'credit_note' => 'NC',
                                'purchase' => 'Compra',
                                'current_account_settlement' => 'Liquidacao',
                                default => 'Ajuste',
                            };
                        @endphp
                        {{ $originLabel }}@if($entry->document_id) #{{ $entry->document_id }} @endif
                    </td>
                    <td>{{ $entry->description ?: '-' }}</td>
                    <td class="right">{{ number_format((float) $entry->debit, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $entry->credit, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sem lancamentos de conta corrente neste dia.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Compras</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Documento</th>
                <th>Fornecedor</th>
                <th>Estado</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ optional($purchase->created_at)->format('H:i') }}</td>
                    <td>#{{ $purchase->id }} {{ $purchase->document_number ?: '' }}</td>
                    <td>{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</td>
                    <td>{{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }} / {{ $purchase->approvalLabel() }} / {{ $purchase->statusLabel() }} / {{ $purchase->paymentStatusLabel() }}</td>
                    <td class="right">{{ number_format((float) $purchase->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sem compras neste dia.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Turnos de Caixa</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Operador</th>
                <th>Abertura</th>
                <th>Fecho</th>
                <th>Estado</th>
                <th class="right">Vendas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $shift)
                <tr>
                    <td>{{ $shift->operator->name ?? 'Sistema' }}</td>
                    <td>{{ optional($shift->opened_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($shift->closed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $shift->status }}</td>
                    <td class="right">{{ number_format((float) ($shift->total_sales ?? 0), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sem turnos neste dia.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado automaticamente ao avancar a data operacional.</div>
</body>
</html>
