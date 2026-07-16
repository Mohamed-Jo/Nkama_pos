@extends('layouts.admin')

@section('page-title', 'Cartao Cliente')

@section('content')
    <style>
        .digital-card { max-width:420px; background:#0f172a; border:1px solid #334155; border-radius:8px; padding:20px; color:#fff; }
        .card-number { font-size:24px; font-weight:900; letter-spacing:2px; margin:12px 0; }
        .barcode { background:#fff; color:#020617; border-radius:6px; padding:14px; text-align:center; font-family:monospace; font-size:18px; letter-spacing:3px; }
        .barcode img { display:block; max-width:100%; height:54px; object-fit:contain; margin:0 auto; }
        .qr-image { background:#fff; border-radius:6px; padding:8px; width:132px; height:132px; display:flex; align-items:center; justify-content:center; }
        .qr-image svg { width:112px; height:112px; }
        .qr-fallback { width:132px; height:132px; background:repeating-linear-gradient(45deg,#020617 0 8px,#fff 8px 16px); border:8px solid #fff; border-radius:6px; }
        .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:10px; margin-top:14px; }
        .meta { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; }
        .meta span { color:#94a3b8; display:block; font-size:11px; text-transform:uppercase; }
        .meta strong { display:block; margin-top:4px; }
        .history { margin-top:22px; background:var(--card); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
        .history th, .history td { padding:12px; border-bottom:1px solid var(--border); text-align:left; }
    </style>

    @if($errors->any())
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if(session('success'))
        <div style="background:rgba(16,185,129,.15); border:1px solid #10b981; color:#86efac; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    <div style="display:grid; grid-template-columns:minmax(280px,420px) 1fr; gap:20px; align-items:start;">
        <section class="digital-card">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                <div>
                    <div style="color:#94a3b8; font-size:12px; font-weight:800; text-transform:uppercase;">Nkama POS Enterprise</div>
                    <div class="card-number">{{ $card->card_number }}</div>
                    <div style="font-size:18px; font-weight:800;">{{ $card->customer->name ?? 'Cliente removido' }}</div>
                </div>
                @php
                    $barcodeValue = (string) ($card->barcode ?? '');
                    $qrValue = (string) ($card->qr_code ?? '');
                    $barcodeIsImage = strlen($barcodeValue) > 80;
                    $qrIsSvg = str_contains($qrValue, '<svg');
                @endphp
                @if($qrIsSvg)
                    <div class="qr-image" title="{{ $card->card_number }}">{!! $qrValue !!}</div>
                @else
                    <div class="qr-fallback" title="{{ $card->card_number }}"></div>
                @endif
            </div>
            <div class="barcode" title="{{ $card->card_number }}">
                @if($barcodeIsImage)
                    <img src="data:image/png;base64,{{ $barcodeValue }}" alt="Barcode {{ $card->card_number }}">
                    <div style="font-size:12px; letter-spacing:1px; margin-top:8px;">{{ $card->card_number }}</div>
                @else
                    {{ $card->card_number }}
                @endif
            </div>
            <div class="meta-grid">
                <div class="meta"><span>Pontos</span><strong>{{ number_format($card->points, 0, ',', '.') }}</strong></div>
                <div class="meta"><span>Saldo</span><strong>{{ number_format($card->balance, 2, ',', '.') }} Kz</strong></div>
                <div class="meta"><span>Nivel</span><strong>{{ $card->level }}</strong></div>
                <div class="meta"><span>Estado</span><strong>{{ $card->status_label }}</strong></div>
                <div class="meta"><span>Validade</span><strong>{{ optional($card->expires_at)->format('d/m/Y') ?? '-' }}</strong></div>
                <div class="meta"><span>Cliente ID</span><strong>{{ $card->customer_id }}</strong></div>
            </div>
            <form method="POST" action="{{ route('admin.customer-cards.toggle-status', $card) }}" style="margin-top:14px;">
                @csrf
                @method('PATCH')
                <label style="display:block; color:#94a3b8; font-size:11px; font-weight:700; margin-top:10px; text-transform:uppercase;">PIN do supervisor
                    <input name="supervisor_pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required placeholder="8 digitos" style="margin-top:6px;">
                </label>
                <input name="supervisor_reason" type="hidden" value="Alteracao de estado do cartao">
                <button class="btn-primary" type="submit" style="width:100%; margin-top:10px;">{{ $card->status === 'active' ? 'Bloquear cartao' : 'Ativar cartao' }}</button>
                <div style="color:#94a3b8; font-size:12px; margin-top:8px; text-align:center;">Alterar estado do cartao</div>
            </form>
        </section>
        <section>
            <h2 style="font-size:18px; font-weight:800; margin:0 0 10px;">Dados do cartao</h2>
            <form method="POST" action="{{ route('admin.customer-cards.details', $card) }}" style="display:grid; grid-template-columns:1fr 180px; gap:10px; align-items:end; max-width:620px; margin-bottom:18px;">
                @csrf
                @method('PATCH')
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Cliente associado
                    <select name="customer_id" style="width:100%; margin-top:6px; padding:12px; border-radius:8px; border:1px solid var(--border); background:var(--input-bg); color:var(--input-text);">
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($card->customer_id === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Validade
                    <input name="expires_at" type="date" value="{{ optional($card->expires_at)->toDateString() }}">
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">PIN do supervisor
                    <input name="supervisor_pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required placeholder="8 digitos">
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Motivo
                    <input name="supervisor_reason" type="text" maxlength="180" placeholder="Justificacao da alteracao">
                </label>
                <button class="btn-primary" type="submit" style="grid-column:1 / -1;">Guardar dados do cartao</button>
            </form>
            <h2 style="font-size:18px; font-weight:800; margin:0 0 10px;">Recarga monetaria</h2>
            <form method="POST" action="{{ route('admin.customer-cards.recharge', $card) }}" style="display:grid; grid-template-columns:1fr 150px; gap:10px; align-items:end; max-width:520px; margin-bottom:18px;">
                @csrf
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Valor
                    <input name="amount" type="number" min="1" step="0.01" placeholder="0,00">
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Metodo
                    <select name="method" style="width:100%; margin-top:6px; padding:12px; border-radius:8px; border:1px solid var(--border); background:var(--input-bg); color:var(--input-text);">
                        <option value="cash">Dinheiro</option>
                        <option value="card">Multicaixa</option>
                        <option value="transf">Transferencia</option>
                    </select>
                </label>
                <label style="grid-column:1 / -1; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Descricao
                    <input name="description" type="text" placeholder="Opcional">
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">PIN do supervisor
                    <input name="supervisor_pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required placeholder="8 digitos">
                </label>
                <label style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Motivo
                    <input name="supervisor_reason" type="text" maxlength="180" placeholder="Autorizacao da recarga">
                </label>
                <button class="btn-primary" type="submit" style="grid-column:1 / -1;">Recarregar cartao</button>
            </form>

            <h2 style="font-size:18px; font-weight:800; margin:0 0 10px;">Resgate</h2>
            <form method="POST" action="{{ route('admin.customer-cards.redeem', $card) }}" style="display:flex; gap:10px; align-items:end; max-width:420px;">
                @csrf
                <label style="flex:1; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Pontos
                    <input name="points" type="number" min="100" step="100" value="100">
                </label>
                <label style="flex:1; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">PIN do supervisor
                    <input name="supervisor_pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required placeholder="8 digitos">
                </label>
                <label style="flex:1; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase;">Motivo
                    <input name="supervisor_reason" type="text" maxlength="180" placeholder="Autorizacao do resgate">
                </label>
                <button class="btn-primary" type="submit">Resgatar</button>
            </form>
            <div style="color:var(--muted); font-size:13px; margin-top:8px;">100 pontos = 500 Kz.</div>
        </section>
    </div>

    <section class="history">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Data</th><th>Tipo</th><th>Documento</th><th>Pontos</th><th>Saldo apos</th><th>Descricao</th></tr></thead>
            <tbody>
                @forelse($card->transactions()->with('sale')->latest()->get() as $transaction)
                    <tr>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $transaction->type_label }}</td>
                        <td>{{ $transaction->sale->invoice_number ?? '-' }}</td>
                        <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points, 0, ',', '.') }}</td>
                        <td>{{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                        <td>{{ $transaction->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:var(--muted);">Sem movimentos de pontos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <section class="history">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr><th colspan="6">Historico monetario</th></tr><tr><th>Data</th><th>Tipo</th><th>Metodo</th><th>Valor</th><th>Saldo apos</th><th>Descricao</th></tr></thead>
            <tbody>
                @forelse($card->balanceTransactions()->with('operator')->latest()->get() as $transaction)
                    <tr>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $transaction->type_label }}</td>
                        <td>{{ $transaction->method_label }}</td>
                        <td>{{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 2, ',', '.') }} Kz</td>
                        <td>{{ number_format($transaction->balance_after, 2, ',', '.') }} Kz</td>
                        <td>{{ $transaction->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:var(--muted);">Sem movimentos monetarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
