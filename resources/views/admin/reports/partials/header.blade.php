<div class="header">
    @if(!empty($logoUrl))
        <div class="header-logo">
            <img src="{{ $logoUrl }}" alt="Logotipo">
        </div>
    @endif

    <div class="header-company">
        <div class="company">{{ $company['name'] ?: config('app.name', 'MARIA ERP') }}</div>
        @if(!empty($company['nif']))
            <div class="muted">NIF: {{ $company['nif'] }}</div>
        @endif
        @if(!empty($company['location']))
            <div class="muted">{{ $company['location'] }}</div>
        @endif
    </div>

    <div class="title">{{ $title }}</div>
    <div class="period">
        Periodo: {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}
        | Gerado em {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</div>
