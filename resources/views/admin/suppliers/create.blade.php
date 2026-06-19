@extends('layouts.admin')

@section('content')
    <style>
        :root {
            --glass: color-mix(in srgb, #0b0f19 80%, transparent);
            --input-bg: #05070a;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Input com efeito de foco "Luminous" */
        input:focus,
        textarea:focus {
            border-color: rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
        }
    </style>
    <div class="max-w-5xl mx-auto py-12 px-6">

        <div class="mb-10 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-medium text-white tracking-tight">Configurar Fornecedor</h1>
                <p class="text-gray-500 mt-2">Defina os detalhes de contacto e as informações logísticas.</p>
            </div>
            <div class="flex gap-3">
                <span
                    class="flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-[10px] font-bold uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Modo Edição
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.suppliers.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            @csrf

            <div class="lg:col-span-2 space-y-8">
                <div class="glass-card rounded-3xl p-8">
                    <h2 class="text-xs font-bold text-gray-500 mb-8 uppercase tracking-widest">Dados do Fornecedor</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Nome da
                                Empresa</label>
                            <input type="text" name="company_name" required placeholder="Ex: Nkama Logistics LDA"
                                class="w-full bg-[var(--input-bg)] border border-white/5 rounded-2xl px-5 py-4 text-white placeholder-gray-700 outline-none transition-all duration-300">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Pessoa
                                    de Contacto</label>
                                <input type="text" name="contact_person" placeholder="Nome completo"
                                    class="w-full bg-[var(--input-bg)] border border-white/5 rounded-2xl px-5 py-4 text-white placeholder-gray-700 outline-none transition-all duration-300">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Telefone</label>
                                <input type="text" name="phone" placeholder="+244 900 000 000"
                                    class="w-full bg-[var(--input-bg)] border border-white/5 rounded-2xl px-5 py-4 text-white placeholder-gray-700 outline-none transition-all duration-300 font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Email
                                Corporativo</label>
                            <input type="email" name="email" placeholder="contacto@empresa.com"
                                class="w-full bg-[var(--input-bg)] border border-white/5 rounded-2xl px-5 py-4 text-white placeholder-gray-700 outline-none transition-all duration-300">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Endereço
                                Fiscal</label>
                            <textarea name="address" rows="3" placeholder="Insira o endereço completo aqui..."
                                class="w-full bg-[var(--input-bg)] border border-white/5 rounded-2xl px-5 py-4 text-white placeholder-gray-700 outline-none transition-all duration-300"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="glass-card rounded-3xl p-8 sticky top-8">
                    <h3 class="text-xs font-bold text-gray-500 mb-6 uppercase tracking-widest">Ações</h3>

                    <button type="submit"
                        class="w-full bg-white text-black py-4 rounded-2xl font-bold hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl">
                        Guardar Alterações
                    </button>

                    <a href="{{ route('admin.suppliers.index') }}"
                        class="block text-center mt-6 text-sm text-gray-600 hover:text-white transition">
                        Cancelar operação
                    </a>

                    <div class="mt-10 pt-8 border-t border-white/5">
                        <p class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">Nota de sistema</p>
                        <p class="text-xs text-gray-500 mt-3 leading-relaxed">
                            Ao registar este fornecedor, ele ficará disponível automaticamente no módulo de Compras e Gestão
                            de Stocks.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
