@extends('layouts.admin')

@section('content')
    <style>
        /* Design System Nkama: Escala de Cinzas e Profundidade */
        :root {
            --panel: rgba(17, 24, 39, 0.4);
            --border: rgba(255, 255, 255, 0.06);
        }

        .panel {
            background: var(--panel);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        /* Animação suave de entrada para a tabela */
        tr {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-action {
            transition: transform 0.1s ease, background 0.2s ease;
        }

        .btn-action:active {
            transform: scale(0.95);
        }

        /* Scrollbar minimalista para a tabela */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }
    </style>

    <div class="max-w-7xl mx-auto py-12 px-8">

        <div class="flex justify-between items-center mb-12">
            <div class="space-y-1">
                <h1 class="text-4xl font-light text-white tracking-tight">Fornecedores</h1>
                <p class="text-gray-500 font-medium tracking-wide text-xs uppercase">Gestão Operacional de Parcerias</p>
            </div>
            <a href="{{ route('admin.suppliers.create') }}"
                class="group relative inline-flex items-center gap-2 px-6 py-2.5 bg-gray-50 text-black rounded-xl text-sm font-bold shadow-[0_0_20px_rgba(255,255,255,0.1)] hover:bg-white hover:scale-105 transition-all duration-300">
                <span>+ Novo Fornecedor</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach ([['Total', $totalSuppliers, 'text-white'], ['Ativos', $activeSuppliers, 'text-emerald-400'], ['Crescimento', number_format($growth, 1) . '%', 'text-orange-400']] as $stat)
                <div class="panel p-8">
                    <span class="text-[9px] font-black uppercase tracking-[0.25em] text-gray-500">{{ $stat[0] }}</span>
                    <p class="text-4xl font-thin {{ $stat[2] }} mt-3">{{ $stat[1] }}</p>
                </div>
            @endforeach
        </div>

        <div class="panel overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-white/5">
                    <tr class="text-gray-400 uppercase tracking-widest text-[10px] font-bold">
                        <th class="px-10 py-6">Empresa</th>
                        <th class="px-10 py-6">Contacto</th>
                        <th class="px-10 py-6">Telefone</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-white/[0.03] group">
                            <td class="px-10 py-7 text-white font-semibold">{{ $supplier->company_name }}</td>
                            <td class="px-10 py-7 text-gray-400">{{ $supplier->contact_person ?? '—' }}</td>
                            <td class="px-10 py-7 text-gray-500 font-mono text-xs">{{ $supplier->phone ?? '—' }}</td>
                            <td class="px-10 py-7">
                                <span
                                    class="flex items-center gap-2.5 text-[10px] uppercase font-bold tracking-wider {{ $supplier->status ? 'text-emerald-500' : 'text-rose-500' }}">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full {{ $supplier->status ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $supplier->status ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-10 py-7 text-right">
                                <div class="flex justify-end gap-6 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('admin.suppliers.edit', $supplier->id) }}"
                                        class="text-gray-500 hover:text-white transition-colors">Editar</a>
                                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier->id) }}">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-900 hover:text-rose-500 transition-colors">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-10 py-16 text-center text-gray-600 font-medium">Nenhum registo
                                disponível.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            {{ $suppliers->links() }}
        </div>
    </div>
@endsection
