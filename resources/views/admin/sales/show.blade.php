@extends('layouts.admin')

@section('content')
    <style>
        .invoice-box {
            background: #0f172a;
            padding: 24px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, .06);
            color: #e2e8f0;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background: #f97316;
            color: black;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .btn-print:hover {
            transform: translateY(-2px);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            color: #94a3b8;
            font-size: 13px;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .05);
        }

        .total-box {
            text-align: right;
            margin-top: 20px;
            font-size: 20px;
            font-weight: 700;
            color: #f97316;
        }
    </style>

    <div class="invoice-box">

        <!-- HEADER -->
        <div class="invoice-header">

            <h1 class="text-2xl font-bold">
                🧾 Fatura #{{ $sale->invoice_number }}
            </h1>

            <button onclick="window.print()" class="btn-print">
                🖨 Imprimir
            </button>

        </div>

        <!-- INFO -->
        <div style="margin-bottom:20px; color:#94a3b8;">

            <p>
                Cliente:
                <strong style="color:white;">
                    {{ $sale->customer->name ?? 'Consumidor Final' }}
                </strong>
            </p>

            <p>
                Data:
                <strong style="color:white;">
                    {{ optional($sale->created_at)->format('d/m/Y H:i') }}
                </strong>
            </p>

        </div>

        <!-- TABLE -->
        <table class="table">

            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Preço</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($sale->items as $item)
                    <tr>

                        <td>
                            {{ $item->product->name ?? 'Produto removido' }}
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td>
                            {{ number_format($item->unit_price, 2) }}
                        </td>

                        <td>
                            {{ number_format($item->subtotal, 2) }}
                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

        <!-- TOTAL -->
        <div class="total-box">
            Total: AOA {{ number_format($sale->total, 2) }}
        </div>

    </div>
@endsection
