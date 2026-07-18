@extends('layouts.admin')

@section('page-title', 'Empresa & IVA')

@section('content')
    <style>
        .settings-wrap {
            max-width: 980px;
        }

        .settings-alert {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.24);
            border-radius: 8px;
            color: #86efac;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .settings-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            border-radius: 8px;
            color: #fecaca;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .settings-panel {
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 18px;
        }

        .settings-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .settings-section {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 18px;
            padding-bottom: 18px;
        }

        .settings-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
        }

        .settings-title {
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .settings-field label {
            color: #cbd5e1;
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .settings-field input,
        .settings-field select {
            background: #070a12;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: white;
            margin: 0;
            padding: 12px;
            width: 100%;
        }

        .settings-field input[type="file"] {
            padding: 9px;
        }

        .settings-toggle {
            align-items: center;
            color: #cbd5e1;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            min-height: 44px;
        }

        .settings-toggle input {
            height: 18px;
            margin: 0;
            width: 18px;
        }

        .settings-logo {
            align-items: center;
            display: flex;
            gap: 14px;
        }

        .settings-logo-preview {
            align-items: center;
            background: #070a12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            display: flex;
            height: 74px;
            justify-content: center;
            overflow: hidden;
            width: 120px;
        }

        .settings-logo-preview img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .settings-logo-preview span {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .settings-background-preview {
            align-items: center;
            background:
                linear-gradient(rgba(3, 7, 18, 0.18), rgba(3, 7, 18, 0.62)),
                #070a12;
            background-position: center;
            background-size: cover;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            display: flex;
            height: 120px;
            justify-content: center;
            overflow: hidden;
            width: 220px;
        }

        .settings-background-preview span {
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 800;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.75);
        }

        .settings-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .settings-btn {
            background: #f97316;
            border: none;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            font-weight: 900;
            min-height: 42px;
            padding: 0 16px;
        }

        .settings-hint {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 6px;
        }

        .settings-tool-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }

        .settings-secondary-btn {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(249, 115, 22, 0.35);
            border-radius: 8px;
            color: #fed7aa;
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
            min-height: 38px;
            padding: 0 12px;
        }

        .settings-discovery-status {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 800;
        }

        .settings-discovery-status.ok {
            color: #86efac;
        }

        .settings-discovery-status.warn {
            color: #fcd34d;
        }

        @media (max-width: 820px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-logo {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="settings-wrap">
        @if(session('success'))
            <div class="settings-alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="settings-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="settings-panel">
            @csrf
            @method('PUT')

            <div class="settings-section">
                <div class="settings-title">Dados da empresa</div>

                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="company-name">Nome da empresa</label>
                        <input id="company-name" type="text" name="company[name]" value="{{ old('company.name', $company['name'] ?? '') }}" required>
                    </div>

                    <div class="settings-field">
                        <label for="company-location">Localizacao</label>
                        <input id="company-location" type="text" name="company[location]" value="{{ old('company.location', $company['location'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-nif">NIF</label>
                        <input id="company-nif" type="text" name="company[nif]" value="{{ old('company.nif', $company['nif'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-iban">IBAN</label>
                        <input id="company-iban" type="text" name="company[iban]" value="{{ old('company.iban', $company['iban'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-account-number">No. conta</label>
                        <input id="company-account-number" type="text" name="company[account_number]" value="{{ old('company.account_number', $company['account_number'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-bank-name">Banco</label>
                        <input id="company-bank-name" type="text" name="company[bank_name]" value="{{ old('company.bank_name', $company['bank_name'] ?? '') }}">
                    </div>
                    <div class="settings-field">
                        <label for="company-swift">SWIFT</label>
                        <input id="company-swift" type="text" name="company[swift]" value="{{ old('company.swift', $company['swift'] ?? '') }}">
                    </div>
                </div>

                <div class="settings-field" style="margin-top:16px;">
                    <label for="company-logo">Logotipo</label>
                    <div class="settings-logo">
                        <div class="settings-logo-preview">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logotipo da empresa">
                            @else
                                <span>Sem logotipo</span>
                            @endif
                        </div>
                        <input id="company-logo" type="file" name="company[logo]" accept="image/png,image/jpeg,image/webp">
                        <div class="settings-hint">Formatos: JPG, PNG ou WEBP. Maximo: 10 MB.</div>
                    </div>
                </div>

                <div class="settings-field" style="margin-top:16px;">
                    <label for="company-login-background">Fundo do login</label>
                    <div class="settings-logo">
                        <div class="settings-background-preview" @if($loginBackgroundUrl) style="background-image: linear-gradient(rgba(3, 7, 18, 0.18), rgba(3, 7, 18, 0.62)), url('{{ $loginBackgroundUrl }}');" @endif>
                            <span>{{ $loginBackgroundUrl ? 'Imagem atual' : 'Fundo padrao' }}</span>
                        </div>
                        <div style="flex:1;">
                            <input id="company-login-background" type="file" name="company[login_background]" accept="image/png,image/jpeg,image/webp">
                            <div class="settings-hint">Formatos: JPG, PNG ou WEBP. Maximo: 10 MB.</div>
                            @if($loginBackgroundUrl)
                                <label class="settings-toggle" style="margin-top:8px; min-height:auto;">
                                    <input type="checkbox" name="company[remove_login_background]" value="1">
                                    Remover imagem do login
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">IVA padrão</div>

                <div class="settings-grid">
                    <label class="settings-toggle">
                        <input type="checkbox" name="tax[active]" value="1" @checked(old('tax.active', $tax['active'] ?? false))>
                        Sugerir IVA nos novos produtos
                    </label>

                    <div class="settings-field">
                        <label for="tax-value">Valor padrão do IVA (%)</label>
                        <input id="tax-value" type="number" name="tax[value]" min="0" max="100" step="0.01" value="{{ old('tax.value', $tax['value'] ?? 14) }}">
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">Dados comerciais da factura</div>

                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="invoice-currency">Moeda</label>
                        <input id="invoice-currency" type="text" name="invoice[currency]" value="{{ old('invoice.currency', $invoice['currency'] ?? 'AOA') }}" maxlength="12" required>
                    </div>

                    <div class="settings-field">
                        <label for="invoice-exchange-rate">Cambio</label>
                        <input id="invoice-exchange-rate" type="number" name="invoice[exchange_rate]" min="0.000001" max="999999999" step="0.000001" value="{{ old('invoice.exchange_rate', $invoice['exchange_rate'] ?? 1) }}" required>
                    </div>

                    <div class="settings-field">
                        <label for="invoice-commercial-discount">Desconto comercial (%)</label>
                        <input id="invoice-commercial-discount" type="number" name="invoice[commercial_discount]" min="0" max="100" step="0.01" value="{{ old('invoice.commercial_discount', $invoice['commercial_discount'] ?? 0) }}" required>
                    </div>

                    <div class="settings-field">
                        <label for="invoice-payment-condition">Condicao de pagamento</label>
                        <input id="invoice-payment-condition" type="text" name="invoice[payment_condition]" value="{{ old('invoice.payment_condition', $invoice['payment_condition'] ?? 'Pronto pagamento') }}" maxlength="120">
                    </div>

                    <div class="settings-field">
                        <label for="invoice-due-days">Vencimento (dias)</label>
                        <input id="invoice-due-days" type="number" name="invoice[due_days]" min="0" max="3650" step="1" value="{{ old('invoice.due_days', $invoice['due_days'] ?? 0) }}" required>
                    </div>

                    <div class="settings-field">
                        <label for="invoice-exemption-reason">Motivo de isencao</label>
                        <input id="invoice-exemption-reason" type="text" name="invoice[exemption_reason]" value="{{ old('invoice.exemption_reason', $invoice['exemption_reason'] ?? '') }}" maxlength="255">
                    </div>
                    <label class="settings-toggle">
                        <input type="hidden" name="invoice[show_agt_qr]" value="0">
                        <input type="checkbox" name="invoice[show_agt_qr]" value="1" @checked(old('invoice.show_agt_qr', $invoice['show_agt_qr'] ?? true))>
                        Mostrar QR AGT na folha A4
                    </label>
                </div>
            </div>
            <div class="settings-section">
                <div class="settings-title">Impressao / Ticket</div>

                <div class="settings-title" style="margin-top:0;">Impressora direta</div>
                <div class="settings-tool-row">
                    <button type="button" id="btn-detect-printing" class="settings-secondary-btn">Detectar automaticamente</button>
                    <span id="printing-discovery-status" class="settings-discovery-status">Procura SumatraPDF e impressoras instaladas neste Windows.</span>
                </div>
                <div class="settings-grid" style="margin-bottom:18px;">
                    <div class="settings-field">
                        <label for="direct-print-sumatra-path">Caminho do SumatraPDF.exe</label>
                        <input id="direct-print-sumatra-path" type="text" name="direct_print[sumatra_path]" value="{{ old('direct_print.sumatra_path', $directPrint['sumatra_path'] ?? '') }}" placeholder="C:\Users\...\SumatraPDF.exe" list="sumatra-path-options">
                        <datalist id="sumatra-path-options"></datalist>
                        <div class="settings-hint">Deixe vazio para usar o caminho do .env ou a deteccao automatica.</div>
                    </div>

                    <div class="settings-field">
                        <label for="direct-print-printer-name">Nome da impressora</label>
                        <input id="direct-print-printer-name" type="text" name="direct_print[printer_name]" value="{{ old('direct_print.printer_name', $directPrint['printer_name'] ?? '') }}" placeholder="XP-80" list="printer-name-options">
                        <datalist id="printer-name-options"></datalist>
                        <div class="settings-hint">Deixe vazio para imprimir na impressora padrao do Windows.</div>
                    </div>
                </div>

                <div class="settings-title" style="margin-top:18px;">Visual do ticket</div>
                <div class="settings-tool-row">
                    <button type="button" id="btn-print-default-layout" class="settings-secondary-btn">Aplicar padrao recomendado</button>
                    <span class="settings-discovery-status">Restaura largura, margens, fontes e colunas do ticket.</span>
                </div>
                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="print-paper-width">Largura do papel (mm)</label>
                        <input id="print-paper-width" type="number" name="print[paper_width_mm]" min="58" max="100" step="0.1" value="{{ old('print.paper_width_mm', $print['paper_width_mm'] ?? 80) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-margin-left">Margem esquerda (mm)</label>
                        <input id="print-margin-left" type="number" name="print[page_margin_left_mm]" min="0" max="20" step="0.1" value="{{ old('print.page_margin_left_mm', $print['page_margin_left_mm'] ?? 0) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-margin-right">Margem direita (mm)</label>
                        <input id="print-margin-right" type="number" name="print[page_margin_right_mm]" min="0" max="20" step="0.1" value="{{ old('print.page_margin_right_mm', $print['page_margin_right_mm'] ?? 0) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-margin-top">Margem superior (mm)</label>
                        <input id="print-margin-top" type="number" name="print[page_margin_top_mm]" min="0" max="20" step="0.1" value="{{ old('print.page_margin_top_mm', $print['page_margin_top_mm'] ?? 0) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-margin-bottom">Margem inferior (mm)</label>
                        <input id="print-margin-bottom" type="number" name="print[page_margin_bottom_mm]" min="0" max="20" step="0.1" value="{{ old('print.page_margin_bottom_mm', $print['page_margin_bottom_mm'] ?? 0) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-ticket-width">Largura do ticket (mm)</label>
                        <input id="print-ticket-width" type="number" name="print[ticket_width_mm]" min="50" max="96" step="0.1" value="{{ old('print.ticket_width_mm', $print['ticket_width_mm'] ?? 76) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-ticket-padding">Margem interna do ticket (mm)</label>
                        <input id="print-ticket-padding" type="number" name="print[ticket_padding_mm]" min="0" max="10" step="0.1" value="{{ old('print.ticket_padding_mm', $print['ticket_padding_mm'] ?? 5) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-font-family">Tipo de fonte</label>
                        <select id="print-font-family" name="print[font_family]">
                            @php
                                $selectedFont = old('print.font_family', $print['font_family'] ?? 'Arial, Helvetica, sans-serif');
                                $fontOptions = [
                                    'Arial, Helvetica, sans-serif' => 'Arial',
                                    'Tahoma, Geneva, sans-serif' => 'Tahoma',
                                    'Verdana, Geneva, sans-serif' => 'Verdana',
                                    '"Times New Roman", Times, serif' => 'Times New Roman',
                                    '"Consolas", "Courier New", monospace' => 'Consolas',
                                ];
                            @endphp
                            @foreach($fontOptions as $fontValue => $fontLabel)
                                <option value="{{ $fontValue }}" @selected($selectedFont === $fontValue)>{{ $fontLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="settings-field">
                        <label for="print-base-font">Fonte base (px)</label>
                        <input id="print-base-font" type="number" name="print[base_font_size_px]" min="8" max="14" step="0.1" value="{{ old('print.base_font_size_px', $print['base_font_size_px'] ?? 10) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-company-font">Fonte empresa (px)</label>
                        <input id="print-company-font" type="number" name="print[company_font_size_px]" min="8" max="18" step="0.1" value="{{ old('print.company_font_size_px', $print['company_font_size_px'] ?? 12) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-content-font">Fonte informacoes (px)</label>
                        <input id="print-content-font" type="number" name="print[content_font_size_px]" min="8" max="16" step="0.1" value="{{ old('print.content_font_size_px', $print['content_font_size_px'] ?? 11) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-total-font">Fonte totais (px)</label>
                        <input id="print-total-font" type="number" name="print[total_font_size_px]" min="9" max="18" step="0.1" value="{{ old('print.total_font_size_px', $print['total_font_size_px'] ?? 12) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-tax-summary-font">Fonte resumo IVA (px)</label>
                        <input id="print-tax-summary-font" type="number" name="print[tax_summary_font_size_px]" min="8" max="14" step="0.1" value="{{ old('print.tax_summary_font_size_px', $print['tax_summary_font_size_px'] ?? 10) }}">
                    </div>
                </div>

                <div class="settings-title" style="margin-top:18px;">Colunas dos produtos</div>
                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="print-product-width">Descricao (mm)</label>
                        <input id="print-product-width" type="number" name="print[item_product_width_mm]" min="12" max="34" step="0.1" value="{{ old('print.item_product_width_mm', $print['item_product_width_mm'] ?? 20) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-tax-width">IVA item (mm)</label>
                        <input id="print-tax-width" type="number" name="print[item_tax_width_mm]" min="3" max="10" step="0.1" value="{{ old('print.item_tax_width_mm', $print['item_tax_width_mm'] ?? 5) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-qty-width">Qtd (mm)</label>
                        <input id="print-qty-width" type="number" name="print[item_qty_width_mm]" min="3" max="10" step="0.1" value="{{ old('print.item_qty_width_mm', $print['item_qty_width_mm'] ?? 5) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-price-width">Preco (mm)</label>
                        <input id="print-price-width" type="number" name="print[item_price_width_mm]" min="8" max="22" step="0.1" value="{{ old('print.item_price_width_mm', $print['item_price_width_mm'] ?? 12) }}">
                    </div>

                    <div class="settings-field">
                        <label for="print-subtotal-width">Subtotal item (mm)</label>
                        <input id="print-subtotal-width" type="number" name="print[item_subtotal_width_mm]" min="10" max="30" step="0.1" value="{{ old('print.item_subtotal_width_mm', $print['item_subtotal_width_mm'] ?? 20) }}">
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="settings-btn">Guardar configuracoes</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const detectButton = document.getElementById('btn-detect-printing');
            const status = document.getElementById('printing-discovery-status');
            const sumatraInput = document.getElementById('direct-print-sumatra-path');
            const printerInput = document.getElementById('direct-print-printer-name');
            const sumatraOptions = document.getElementById('sumatra-path-options');
            const printerOptions = document.getElementById('printer-name-options');
            const defaultLayoutButton = document.getElementById('btn-print-default-layout');

            if (!detectButton) {
                return;
            }

            const setStatus = (message, type = '') => {
                status.textContent = message;
                status.classList.remove('ok', 'warn');
                if (type) {
                    status.classList.add(type);
                }
            };

            const fillDatalist = (element, values) => {
                element.innerHTML = '';
                values.forEach((value) => {
                    const option = document.createElement('option');
                    option.value = value;
                    element.appendChild(option);
                });
            };

            detectButton.addEventListener('click', async () => {
                detectButton.disabled = true;
                detectButton.textContent = 'A procurar...';
                setStatus('A procurar SumatraPDF e impressoras...', '');

                try {
                    const response = await fetch('{{ route('admin.settings.printing-discovery') }}', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Nao foi possivel detectar.');
                    }

                    const paths = data.sumatra_paths || [];
                    const printers = data.printers || [];

                    fillDatalist(sumatraOptions, paths);
                    fillDatalist(printerOptions, printers.map((printer) => printer.default ? `${printer.name}` : printer.name));

                    if (!sumatraInput.value && paths.length > 0) {
                        sumatraInput.value = paths[0];
                    }

                    const defaultPrinter = printers.find((printer) => printer.default) || printers[0];
                    if (!printerInput.value && defaultPrinter) {
                        printerInput.value = defaultPrinter.name;
                    }

                    if (paths.length === 0 && printers.length === 0) {
                        setStatus('Nenhum SumatraPDF nem impressora detectados automaticamente.', 'warn');
                    } else {
                        setStatus(`Detectado: ${paths.length} caminho(s) do SumatraPDF e ${printers.length} impressora(s).`, 'ok');
                    }
                } catch (error) {
                    setStatus(error.message || 'Erro ao detectar impressora.', 'warn');
                } finally {
                    detectButton.disabled = false;
                    detectButton.textContent = 'Detectar automaticamente';
                }
            });

            if (defaultLayoutButton) {
                defaultLayoutButton.addEventListener('click', () => {
                    const defaults = {
                        'print-paper-width': '80',
                        'print-margin-left': '0',
                        'print-margin-right': '0',
                        'print-margin-top': '0',
                        'print-margin-bottom': '0',
                        'print-ticket-width': '76',
                        'print-ticket-padding': '5',
                        'print-font-family': 'Arial, Helvetica, sans-serif',
                        'print-base-font': '10',
                        'print-company-font': '12',
                        'print-content-font': '11',
                        'print-total-font': '12',
                        'print-tax-summary-font': '10',
                        'print-product-width': '20',
                        'print-tax-width': '5',
                        'print-qty-width': '5',
                        'print-price-width': '12',
                        'print-subtotal-width': '20'
                    };

                    Object.entries(defaults).forEach(([id, value]) => {
                        const field = document.getElementById(id);
                        if (field) {
                            field.value = value;
                        }
                    });
                });
            }
        });
    </script>
@endsection
