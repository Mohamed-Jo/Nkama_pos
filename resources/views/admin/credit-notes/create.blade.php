@extends('layouts.admin')

@section('page-title', 'Emitir NC')

@section('content')
    @php
        $viewTicket = \App\Services\ModuleSettings::enabled('view_ticket');
        $saleTicketUrl = route('admin.sales.ticket', $sale);
        $salePrintUrl = route('admin.print.sales', $sale);
    @endphp
    <style>
        .nc-wrap { display:grid; gap:18px; max-width:1100px; }
        .nc-panel {
            background:rgba(15,23,42,.76); border:1px solid rgba(255,255,255,.07);
            border-radius:8px; padding:18px;
        }
        .nc-title { color:#fff; font-size:23px; font-weight:900; margin:0; }
        .nc-muted { color:#94a3b8; font-size:13px; margin-top:5px; }
        .nc-grid { display:grid; gap:14px; grid-template-columns:repeat(4,minmax(0,1fr)); }
        .nc-stat { background:rgba(255,255,255,.035); border:1px solid rgba(255,255,255,.06); border-radius:8px; padding:14px; }
        .nc-stat span { color:#94a3b8; display:block; font-size:11px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }
        .nc-stat strong { color:#fff; display:block; font-size:19px; margin-top:7px; }
        .nc-table-wrap { overflow-x:auto; }
        .nc-table { border-collapse:collapse; min-width:850px; width:100%; }
        .nc-table th {
            background:rgba(255,255,255,.04); color:#94a3b8; font-size:11px; letter-spacing:.06em;
            padding:12px; text-align:left; text-transform:uppercase;
        }
        .nc-table td { border-top:1px solid rgba(255,255,255,.06); color:#e5e7eb; padding:12px; }
        .nc-input {
            background:#070a12; border:1px solid rgba(255,255,255,.09); border-radius:8px;
            color:#e5e7eb; min-height:40px; padding:9px 11px; width:100%;
        }
        .nc-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:16px; }
        .nc-btn {
            border:0; border-radius:8px; cursor:pointer; font-weight:900; min-height:42px;
            padding:0 15px; text-decoration:none; display:inline-flex; align-items:center;
        }
        .nc-btn-primary { background:#f97316; color:#111827; }
        .nc-btn-ghost { background:rgba(255,255,255,.06); color:#e5e7eb; border:1px solid rgba(255,255,255,.08); }
        .nc-warning { color:#fecaca; background:rgba(248,113,113,.12); border:1px solid rgba(248,113,113,.24); border-radius:8px; padding:10px 12px; }
        @media (max-width:900px) { .nc-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:580px) { .nc-grid { grid-template-columns:1fr; } .nc-actions { justify-content:flex-start; flex-wrap:wrap; } }
    </style>

    <div class="nc-wrap">
        <div>
            <h1 class="nc-title">Emitir Nota de Credito</h1>
            <div class="nc-muted">Documento original: {{ $sale->invoice_number }}</div>
        </div>

        @if($errors->any())
            <div class="nc-warning">{{ $errors->first() }}</div>
        @endif

        <div class="nc-grid">
            <div class="nc-stat">
                <span>Cliente</span>
                <strong>{{ $sale->customer->name ?? 'Consumidor Final' }}</strong>
            </div>
            <div class="nc-stat">
                <span>Tipo original</span>
                <strong>{{ $sale->document_type_code ?: 'FR' }}</strong>
            </div>
            <div class="nc-stat">
                <span>Total original</span>
                <strong>{{ number_format($sale->total, 2, ',', '.') }} Kz</strong>
            </div>
            <div class="nc-stat">
                <span>Data</span>
                <strong>{{ optional($sale->created_at)->format('d/m/Y') }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.sales.credit-notes.store', $sale) }}" class="nc-panel">
            @csrf
            <label style="display:block; color:#cbd5e1; font-size:12px; font-weight:900; margin-bottom:7px;">Motivo</label>
            <input class="nc-input" name="reason" maxlength="255" value="{{ old('reason') }}" placeholder="Ex.: Devolucao, erro de faturacao, anulacao parcial">

            <div style="margin-top:14px; background:rgba(56,189,248,.08); border:1px solid rgba(56,189,248,.22); border-radius:8px; padding:12px;">
                <label style="display:block; color:#bae6fd; font-size:12px; font-weight:900; margin-bottom:7px;">Metodo de reembolso quando houver valor a devolver</label>
                <select class="nc-input" name="refund_method">
                    <option value="">Sem reembolso em caixa</option>
                    <option value="cash" @selected(old('refund_method') === 'cash')>Dinheiro</option>
                    <option value="card" @selected(old('refund_method') === 'card')>Multicaixa</option>
                    <option value="transf" @selected(old('refund_method') === 'transf')>Transferencia</option>
                </select>
                <div class="nc-muted" style="margin-top:7px;">
                    Em FR o valor da NC sai como reembolso. Em FT a NC abate primeiro a conta corrente; se exceder a divida, o excedente usa este metodo.
                </div>
            </div>

            <div class="nc-table-wrap" style="margin-top:16px;">
                <table class="nc-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd vendida</th>
                            <th>Ja creditado</th>
                            <th>Saldo</th>
                            <th>Qtd NC</th>
                            <th>Preco</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            @php
                                $credited = (float) ($creditedByItem[$item->id] ?? 0);
                                $available = max((float) $item->quantity - $credited, 0);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item->product->name ?? 'Produto removido' }}</strong>
                                    <div class="nc-muted">IVA {{ number_format($item->tax_rate ?? 0, 2, ',', '.') }}%</div>
                                </td>
                                <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format($credited, 2, ',', '.') }}</td>
                                <td>{{ number_format($available, 2, ',', '.') }}</td>
                                <td>
                                    <input class="nc-input" type="number" name="items[{{ $item->id }}]" min="0" max="{{ $available }}" step="0.01" value="{{ old('items.' . $item->id, 0) }}" {{ $available <= 0 ? 'disabled' : '' }}>
                                </td>
                                <td>{{ number_format($item->unit_price, 2, ',', '.') }} Kz</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nc-actions">
                <a class="nc-btn nc-btn-ghost"
                    href="{{ $viewTicket ? $saleTicketUrl : $salePrintUrl }}"
                    @if($viewTicket) target="_blank" @else data-direct-print-url="{{ $salePrintUrl }}" @endif>
                    {{ $viewTicket ? 'Voltar ao ticket' : 'Imprimir documento' }}
                </a>
                <button class="nc-btn nc-btn-primary" type="submit">Emitir NC</button>
            </div>
        </form>
    </div>

    <script>
        @if($errors->any())
            nkamaAlert(@json($errors->first()), 'error');
        @endif
    </script>
@endsection
