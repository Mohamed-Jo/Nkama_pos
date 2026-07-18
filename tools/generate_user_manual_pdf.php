<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$outputDir = __DIR__ . '/../output/pdf';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$generatedAt = '18/07/2026';
$title = 'Manual de Utilizador';
$systemName = 'MARIA ERP';

$sections = [
    [
        'title' => '1. Visao Geral',
        'body' => [
            'O MARIA ERP e um sistema de ponto de venda e gestao operacional para vendas de supermercado, atendimento de restaurante, faturacao, caixa, stock, compras, clientes, conta corrente, relatorios, auditoria e configuracoes fiscais.',
            'O acesso ao sistema e feito por operador. Cada operador ve apenas os menus permitidos para a sua funcao, por isso alguns botoes ou paginas podem nao aparecer em todos os perfis.',
        ],
        'bullets' => [
            'Dashboard: resumo rapido de vendas, caixa, catalogo, clientes e stock critico.',
            'POS / Caixa: venda direta, restaurante, formas de pagamento e emissao de ticket.',
            'Vendas: consulta de faturas, impressao, fatura A4 e notas de credito.',
            'Catalogo e stock: produtos, categorias, fornecedores, armazens e inventario fisico.',
            'Gestao: relatorios PDF, auditoria, compras, conta corrente, cartoes de cliente e AGT.',
            'Configuracoes: empresa, IVA, impressao, documentos, series, modulos e operadores.',
        ],
    ],
    [
        'title' => '2. Entrada no Sistema',
        'body' => [
            'Na tela inicial, o operador introduz o PIN ou credenciais definidos pelo administrador. Depois da autenticacao, o sistema abre a area administrativa.',
            'Ao terminar o trabalho, use a opcao Sair no canto superior para encerrar a sessao do operador.',
        ],
        'bullets' => [
            'Se o operador esquecer a senha, use a recuperacao de senha do operador, quando disponivel.',
            'Nao partilhe PINs entre operadores; as vendas, fechos e auditorias ficam associados ao operador autenticado.',
            'Se uma opcao necessaria nao aparecer, confirme com o responsavel se o operador tem permissao para esse modulo.',
        ],
    ],
    [
        'title' => '3. Trabalho Diario no Caixa',
        'body' => [
            'Antes de vender, confirme se existe um turno de caixa aberto. O sistema bloqueia o processamento de vendas quando o fecho/turno exigido nao esta ativo.',
            'Durante o dia, as vendas e recebimentos ficam associados ao turno e ao operador, permitindo conferencia posterior no historico de fechos e nos relatorios.',
        ],
        'bullets' => [
            'Abrir turno: informe o valor inicial em caixa.',
            'Vender: use POS / Caixa para adicionar produtos, escolher pagamento e finalizar.',
            'Consultar: use Vendas para rever faturas, imprimir tickets ou abrir a fatura A4.',
            'Fechar turno: informe o valor contado; confira esperado, contado e diferenca.',
            'Auditoria diaria: o botao Auditoria gera o relatorio de lancamento diario antes de avancar a data do sistema.',
        ],
    ],
    [
        'title' => '4. POS / Caixa - Supermercado',
        'body' => [
            'No modo supermercado, os produtos ativos ficam disponiveis para venda direta. O operador pode procurar por nome, selecionar artigos, ajustar quantidades e finalizar a venda.',
            'Os precos de venda sao tratados como valores finais exibidos ao cliente. Quando o IVA estiver ativo, o sistema calcula a incidencia e o imposto a partir do preco registado.',
        ],
        'bullets' => [
            'Adicione produtos clicando no artigo ou usando codigo de barras, quando configurado.',
            'Revise o carrinho antes de finalizar: produto, quantidade, preco unitario, subtotal e total.',
            'Escolha a forma de pagamento: dinheiro, cartao, multicaixa, transferencia, misto, conta corrente ou cartao cliente, conforme modulos ativos.',
            'Em dinheiro, confirme o valor recebido e o troco antes de concluir.',
            'Depois de concluir, imprima o ticket ou deixe o sistema imprimir diretamente, conforme a configuracao Ver Ticket.',
        ],
    ],
    [
        'title' => '5. POS / Caixa - Restaurante',
        'body' => [
            'Quando o modulo Restaurante esta ativo, o POS permite trabalhar por mesas. As mesas podem estar livres, ocupadas, reservadas ou aguardando pagamento.',
            'No restaurante, os artigos aparecem por categorias de restaurante, facilitando atendimento em telas pequenas.',
        ],
        'bullets' => [
            'Selecione uma mesa livre para abrir atendimento ou uma mesa ocupada para continuar o pedido.',
            'Use os filtros de mesas para alternar entre livres e ocupadas.',
            'Adicione itens pela categoria, ajuste quantidades e envie o pedido.',
            'Para transferir, escolha a mesa de origem, a mesa de destino e selecione os produtos/quantidades a mover.',
            'Use Imprimir Conta ou fechar pagamento para emitir o documento da mesa.',
            'Se a mesa estiver reservada, liberte a reserva ou prossiga conforme a permissao do operador.',
        ],
    ],
    [
        'title' => '6. Vendas, Faturas e Tickets',
        'body' => [
            'A pagina Vendas apresenta a lista de documentos emitidos, totais, estado de pagamento, cliente, operador e estado AGT quando aplicavel.',
            'A partir da lista ou do detalhe da venda, o operador autorizado pode ver a fatura, imprimir ticket, abrir fatura A4 ou emitir nota de credito.',
        ],
        'bullets' => [
            'Use filtros por data ou pesquisa para localizar uma fatura.',
            'O botao Ticket abre a pre-visualizacao ou envia direto para impressora, conforme configuracao.',
            'A fatura A4 e indicada para arquivo, envio ao cliente ou impressao formal.',
            'O resumo de IVA no ticket agrupa incidencia e imposto por taxa aplicada.',
            'Notas de credito ja emitidas aparecem junto da fatura original para consulta e reimpressao.',
        ],
    ],
    [
        'title' => '7. Notas de Credito',
        'body' => [
            'A nota de credito deve ser usada para anular total ou parcialmente uma venda ja emitida. Apenas perfis autorizados conseguem ver e executar esta acao.',
            'Ao criar a nota de credito, confira os artigos, quantidades e motivo antes de confirmar. O documento fica ligado a fatura original.',
        ],
        'bullets' => [
            'Abra Vendas e escolha a fatura original.',
            'Clique em Anular/NC quando o botao estiver disponivel.',
            'Defina os itens e quantidades a creditar.',
            'Informe o metodo de reembolso, quando aplicavel.',
            'Depois de emitir, use o detalhe da venda para reimprimir ou consultar a NC.',
        ],
    ],
    [
        'title' => '8. Produtos e Categorias',
        'body' => [
            'O catalogo organiza os artigos vendidos no sistema. Cada produto pode ter categoria, codigo de barras, preco, custo, unidade, stock, IVA e disponibilidade por canal.',
            'As categorias ajudam na organizacao do supermercado e do restaurante. No restaurante, marcar o produto como disponivel para restaurante permite que ele apareca nas categorias do POS de mesas.',
        ],
        'bullets' => [
            'Crie categorias antes de cadastrar muitos produtos.',
            'No produto, informe nome, categoria, preco de venda, stock minimo e disponibilidade.',
            'Use o campo de stock apenas para produtos controlados em inventario.',
            'Mantenha codigos de barras sem duplicacao para leitura correta no POS.',
            'Produtos inativos deixam de aparecer para venda, mas permanecem no historico.',
        ],
    ],
    [
        'title' => '9. Stock, Inventario e Armazens',
        'body' => [
            'O modulo de stock permite acompanhar quantidades, movimentos, produtos com stock baixo e acertos de inventario.',
            'Quando armazens estiverem ativos, o sistema permite controlar stock por local e transferir artigos entre armazens.',
        ],
        'bullets' => [
            'Stock: consulte quantidade atual, minimo e valor estimado.',
            'Movimentos: veja entradas, saidas, ajustes, vendas, compras e transferencias.',
            'Inventario fisico: registe a contagem real e aplique correcao quando necessario.',
            'Armazens: defina locais, padroes e transfira produtos de um local para outro.',
            'Antes de ajustar stock, escreva um motivo claro para auditoria.',
        ],
    ],
    [
        'title' => '10. Compras e Fornecedores',
        'body' => [
            'O modulo Compras regista aquisicoes de mercadorias e acompanha aprovacao, recepcao e pagamento. Fornecedores alimentam o cadastro usado nas compras e na conta corrente.',
            'Compras podem ser diretas ou associadas a conta corrente do fornecedor, quando o modulo estiver ativo.',
        ],
        'bullets' => [
            'Cadastre o fornecedor antes de lancar a compra.',
            'Crie a compra com produtos, quantidades, custos e tipo de pagamento.',
            'Use aprovacao/rejeicao quando o fluxo da empresa exigir conferencia.',
            'Ao receber mercadoria, confirme quantidades para atualizar stock.',
            'Consulte o detalhe da compra para acompanhar estado, pagamento e historico.',
        ],
    ],
    [
        'title' => '11. Clientes, Conta Corrente e Cartao Cliente',
        'body' => [
            'O cadastro de clientes permite associar vendas, conta corrente e cartoes de fidelizacao. A conta corrente regista valores pendentes, liquidacoes e extratos por cliente ou fornecedor.',
            'O cartao cliente permite saldo, recargas, resgates e pedidos de autorizacao, conforme configuracao do modulo.',
        ],
        'bullets' => [
            'Clientes: registe nome, contacto e dados fiscais quando necessarios.',
            'Conta corrente: acompanhe documentos em aberto e liquide valores com metodo de pagamento permitido.',
            'Cartao cliente: consulte saldo, movimentos, recargas e resgates.',
            'Autorizacoes pendentes aparecem para perfis com permissao de gestao, auditoria ou catalogo.',
            'Nao finalize vendas com saldo pendente sem confirmar a regra operacional da empresa.',
        ],
    ],
    [
        'title' => '12. Relatorios PDF',
        'body' => [
            'A area Relatorios gera documentos PDF para conferencia e arquivo. Os relatorios usam filtros como data, cliente, fornecedor, produto, estado ou metodo de pagamento conforme o tipo.',
            'Relatorios devem ser usados para fecho diario, conferencia de caixa, analise de stock e acompanhamento fiscal.',
        ],
        'bullets' => [
            'Vendas PDF: documentos emitidos, pagos, pendentes e notas de credito.',
            'Caixa PDF: recebimentos por metodo, movimentos e resumo de turnos.',
            'Lancamento Diario PDF: consolidado do dia com vendas, caixa, conta corrente, compras e turnos.',
            'Compras PDF: compras por periodo, estado, fornecedor e tipo de pagamento.',
            'Conta Corrente PDF: extrato de clientes ou fornecedores.',
            'Stock e Movimentos PDF: posicao de inventario e historico de movimentacao.',
            'Fechos de Caixa PDF: turnos abertos/fechados, esperado, contado e diferencas.',
            'Auditoria PDF: eventos relevantes do sistema por utilizador, entidade e periodo.',
            'Cartao Cliente PDF: saldos, movimentos e autorizacoes.',
        ],
    ],
    [
        'title' => '13. Auditoria e Data do Sistema',
        'body' => [
            'A auditoria regista acoes relevantes executadas no sistema. O botao Auditoria no topo e reservado a perfis autorizados e deve ser usado no processo de encerramento diario.',
            'Ao avancar a data operacional, o sistema gera primeiro o relatorio de lancamento diario da data encerrada.',
        ],
        'bullets' => [
            'Use Auditoria no fim do expediente ou quando o responsavel financeiro determinar.',
            'Guarde o PDF de lancamento diario junto dos documentos do dia.',
            'Antes de encerrar, confirme vendas, notas de credito, caixa, compras e conta corrente.',
            'Nao avance a data se ainda houver vendas do dia por regularizar.',
        ],
    ],
    [
        'title' => '14. Fiscalizacao AGT',
        'body' => [
            'O modulo AGT acompanha documentos fiscais, series, envio e estado de comunicacao. A disponibilidade depende da configuracao fiscal, credenciais e permissoes.',
            'Quando a integracao estiver ativa, verifique regularmente documentos nao enviados, pendentes ou com erro.',
        ],
        'bullets' => [
            'Fiscalizacao AGT: consulte documentos e estados.',
            'Series AGT: solicite ou liste series conforme necessidade fiscal.',
            'Preparar documento: gera dados para envio da venda ou nota de credito.',
            'Enviar: submete o documento para a AGT quando estiver pronto.',
            'Configuracoes AGT: mantenha NIF, ambiente, endpoint, certificado/chave e parametros tecnicos atualizados.',
        ],
    ],
    [
        'title' => '15. Configuracoes do Sistema',
        'body' => [
            'As configuracoes definem comportamento global. Devem ser alteradas apenas por administradores ou responsaveis autorizados.',
            'Depois de alterar configuracoes fiscais, de impressao ou modulos, teste uma venda simples e uma impressao para confirmar o resultado.',
        ],
        'bullets' => [
            'Empresa & IVA: nome, localizacao, NIF, IBAN, numero de conta, SWIFT, logotipo, ativacao e valor do IVA.',
            'Impressao / Ticket: largura do papel, largura do ticket, margens, fonte, tamanhos, colunas e impressora direta.',
            'Documentos & Series: tipos fiscais, series anuais, numeracao e impacto na conta corrente.',
            'Modulos: ativar/desativar restaurante, supermercado, stock, compras, conta corrente, auditoria, cartao cliente e Ver Ticket.',
            'Operadores: criar utilizadores operacionais, perfis, PIN, estado ativo e codigo de recuperacao.',
        ],
    ],
    [
        'title' => '16. Permissoes e Perfis',
        'body' => [
            'O sistema controla acesso por permissoes. Isto protege vendas, caixa, auditoria, catalogo e configuracoes sensiveis.',
            'Quando um operador nao ve uma acao, o motivo mais comum e falta de permissao ou modulo desativado.',
        ],
        'bullets' => [
            'POS: permite operar vendas no caixa.',
            'Vendas: permite consultar documentos emitidos.',
            'Criar venda: permite emitir novas vendas fora do POS, quando disponivel.',
            'Nota de credito: permite anular/creditar documentos.',
            'Caixa: permite abrir/fechar turno e operar movimentos.',
            'Auditoria/Relatorios: permite consultar informacao sensivel de gestao.',
            'Catalogo: permite gerir produtos, categorias, clientes, fornecedores e stock.',
            'Seguranca: permite gerir operadores, modulos, configuracoes e data operacional.',
        ],
    ],
    [
        'title' => '17. Boas Praticas Operacionais',
        'body' => [
            'Uma rotina consistente reduz erros de caixa, stock e faturacao. Use este checklist como referencia diaria.',
        ],
        'bullets' => [
            'Antes de abrir: confirme impressora, internet, turno de caixa e stock critico.',
            'Durante a venda: confira cliente, artigos, quantidades, descontos e metodo de pagamento.',
            'Ao imprimir: verifique se total, IVA e numero do documento estao legiveis.',
            'Ao anular: emita nota de credito, nunca apague documentos fiscais do historico.',
            'No restaurante: mantenha mesas atualizadas e transfira apenas os itens corretos.',
            'No fim do dia: feche caixa, gere relatorios e execute Auditoria para lancamento diario.',
        ],
    ],
    [
        'title' => '18. Resolucao Rapida de Problemas',
        'body' => [
            'Quando algo nao funcionar como esperado, use as verificacoes abaixo antes de chamar o suporte tecnico.',
        ],
        'bullets' => [
            'Nao consigo vender: confirme se o turno esta aberto e se o operador tem permissao de POS.',
            'Produto nao aparece: confirme se esta ativo, tem categoria e disponibilidade correta.',
            'Produto do restaurante nao aparece: confirme se esta marcado como Restaurante.',
            'Ticket nao abre: Ver Ticket pode estar desativado; nesse caso o sistema tenta imprimir direto.',
            'Nao consigo emitir NC: o operador precisa da permissao de nota de credito e a fatura precisa ter valor disponivel para creditar.',
            'Relatorio vazio: reveja filtros de data, cliente, produto ou estado.',
            'AGT com erro: confira configuracoes AGT, series, estado do documento e ligacao a internet.',
            'Diferenca no caixa: compare vendas em dinheiro, movimentos manuais, reembolsos e valor contado.',
        ],
    ],
];

$toc = '';
foreach ($sections as $section) {
    $toc .= '<li>' . htmlspecialchars($section['title']) . '</li>';
}

$sectionHtml = '';
foreach ($sections as $section) {
    $sectionHtml .= '<section class="manual-section">';
    $sectionHtml .= '<h2>' . htmlspecialchars($section['title']) . '</h2>';
    foreach ($section['body'] as $paragraph) {
        $sectionHtml .= '<p>' . htmlspecialchars($paragraph) . '</p>';
    }
    if (! empty($section['bullets'])) {
        $sectionHtml .= '<ul>';
        foreach ($section['bullets'] as $bullet) {
            $sectionHtml .= '<li>' . htmlspecialchars($bullet) . '</li>';
        }
        $sectionHtml .= '</ul>';
    }
    $sectionHtml .= '</section>';
}

$html = <<<HTML
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 24mm 17mm 19mm 17mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2933;
            font-size: 10.6pt;
            line-height: 1.45;
        }

        h1, h2, h3 {
            margin: 0;
            color: #102a43;
            line-height: 1.2;
        }

        .cover {
            height: 235mm;
            padding: 22mm 15mm;
            background: #0f172a;
            color: #ffffff;
            position: relative;
            page-break-after: always;
        }

        .cover-accent {
            width: 62mm;
            height: 5mm;
            background: #22c55e;
            margin-bottom: 18mm;
        }

        .cover .eyebrow {
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #a7f3d0;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 8mm;
        }

        .cover h1 {
            color: #ffffff;
            font-size: 34pt;
            margin-bottom: 5mm;
        }

        .cover h2 {
            color: #e2e8f0;
            font-size: 18pt;
            font-weight: normal;
            margin-bottom: 18mm;
        }

        .cover .meta {
            border-left: 3px solid #22c55e;
            padding-left: 7mm;
            color: #cbd5e1;
            font-size: 11pt;
            margin-top: 30mm;
        }

        .cover .footer {
            position: absolute;
            bottom: 20mm;
            left: 15mm;
            right: 15mm;
            color: #94a3b8;
            font-size: 9.5pt;
        }

        .intro-box {
            border: 1px solid #d9e2ec;
            background: #f8fafc;
            padding: 10mm;
            margin-bottom: 10mm;
        }

        .intro-box h2 {
            font-size: 17pt;
            margin-bottom: 4mm;
        }

        .toc {
            columns: 2;
            column-gap: 12mm;
            padding-left: 0;
            margin: 0;
        }

        .toc li {
            list-style: none;
            break-inside: avoid;
            border-bottom: 1px solid #e5e7eb;
            padding: 2.4mm 0;
            font-size: 9.8pt;
        }

        .manual-section {
            page-break-inside: avoid;
            margin-bottom: 8mm;
            padding-bottom: 4mm;
            border-bottom: 1px solid #e6edf3;
        }

        .manual-section h2 {
            font-size: 15.5pt;
            margin: 0 0 3.5mm;
            padding-top: 1mm;
        }

        p {
            margin: 0 0 3.2mm;
        }

        ul {
            margin: 2mm 0 0 5mm;
            padding: 0;
        }

        li {
            margin-bottom: 1.7mm;
        }

        .note {
            background: #ecfdf5;
            border-left: 4px solid #22c55e;
            padding: 5mm 6mm;
            margin: 8mm 0;
            page-break-inside: avoid;
        }

        .workflow {
            width: 100%;
            border-collapse: collapse;
            margin: 7mm 0 9mm;
            page-break-inside: avoid;
        }

        .workflow th {
            background: #102a43;
            color: white;
            text-align: left;
            padding: 3mm;
            font-size: 9.4pt;
        }

        .workflow td {
            border: 1px solid #d9e2ec;
            padding: 3mm;
            vertical-align: top;
            font-size: 9.2pt;
        }

        .workflow tr:nth-child(even) td {
            background: #f8fafc;
        }

        .page-break {
            page-break-before: always;
        }

        .small {
            color: #52606d;
            font-size: 9.3pt;
        }
    </style>
</head>
<body>
    <div class="cover">
        <div class="cover-accent"></div>
        <div class="eyebrow">Guia operacional</div>
        <h1>{$title}</h1>
        <h2>{$systemName}</h2>
        <div class="meta">
            <div><strong>Ambito:</strong> Operadores, caixa, vendas, stock, restaurante, gestao e administracao.</div>
            <div><strong>Versao do manual:</strong> {$generatedAt}</div>
            <div><strong>Formato:</strong> PDF para consulta e formacao interna.</div>
        </div>
        <div class="footer">Este manual descreve o uso normal do sistema. Permissoes e modulos ativos podem alterar as opcoes visiveis para cada operador.</div>
    </div>

    <div class="intro-box">
        <h2>Como usar este manual</h2>
        <p>Leia primeiro a rotina diaria de caixa e depois consulte o modulo especifico quando precisar executar uma tarefa. O manual foi organizado pela mesma logica do menu administrativo.</p>
        <p class="small">Nota: textos como POS / Caixa, Vendas, Relatorios e Empresa & IVA correspondem aos nomes usados no sistema.</p>
    </div>

    <h2 style="margin-bottom: 5mm;">Indice</h2>
    <ol class="toc">{$toc}</ol>

    <div class="page-break"></div>

    <div class="note">
        <strong>Regra de seguranca:</strong> cada operador deve trabalhar com a sua propria conta. Isto garante rastreabilidade em vendas, notas de credito, stock, caixa e auditoria.
    </div>

    <table class="workflow">
        <thead>
            <tr>
                <th>Momento</th>
                <th>Acao recomendada</th>
                <th>Onde fazer</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Inicio do dia</td>
                <td>Entrar no sistema, confirmar modulos, verificar impressora e abrir turno.</td>
                <td>Login, Dashboard, POS / Caixa</td>
            </tr>
            <tr>
                <td>Durante o atendimento</td>
                <td>Registar vendas, manter mesas atualizadas, controlar pagamentos e imprimir documentos.</td>
                <td>POS / Caixa, Restaurante, Vendas</td>
            </tr>
            <tr>
                <td>Conferencia</td>
                <td>Consultar faturas, stock, conta corrente, compras e movimentos de caixa.</td>
                <td>Vendas, Stock, Compras, Conta Corrente</td>
            </tr>
            <tr>
                <td>Fim do dia</td>
                <td>Fechar caixa, gerar relatorios e executar Auditoria para lancamento diario.</td>
                <td>Historico de Fechos, Relatorios, Auditoria</td>
            </tr>
        </tbody>
    </table>

    {$sectionHtml}
</body>
</html>
HTML;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
$canvas->page_text(500, 810, 'Pagina {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.32, 0.38, 0.45]);

$output = $outputDir . '/manual-utilizador-maria-erp.pdf';
file_put_contents($output, $dompdf->output());

echo $output . PHP_EOL;
