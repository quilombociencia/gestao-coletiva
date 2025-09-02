<?php
if (!defined('ABSPATH')) {
    exit;
}

$data_inicio = isset($_GET['data_inicio']) ? sanitize_text_field($_GET['data_inicio']) : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? sanitize_text_field($_GET['data_fim']) : date('Y-m-t');
$incluir_previsoes = isset($_GET['incluir_previsoes']) ? (bool)$_GET['incluir_previsoes'] : false;

if ($incluir_previsoes) {
    $relatorio = GC_Relatorio::gerar_relatorio_previsao($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $eh_relatorio_previsao = true;
} else {
    $relatorio = GC_Relatorio::gerar_relatorio_periodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $eh_relatorio_previsao = false;
}
?>

<div id="gc-livro-caixa" class="gc-container">
    <div class="gc-header">
        <h2>
            <?php if ($eh_relatorio_previsao): ?>
                📊 <?php _e('Livro Caixa com Previsões', 'gestao-coletiva'); ?>
            <?php else: ?>
                📋 <?php _e('Livro Caixa Público', 'gestao-coletiva'); ?>
            <?php endif; ?>
        </h2>
        <p>
            <?php if ($eh_relatorio_previsao): ?>
                <?php _e('Transparência das movimentações realizadas e projeção das recorrências futuras', 'gestao-coletiva'); ?>
            <?php else: ?>
                <?php _e('Transparência total das movimentações financeiras do projeto', 'gestao-coletiva'); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Seletor de período -->
    <div class="gc-periodo-selector">
        <form method="get" class="gc-form-periodo">
            <div class="gc-periodo-campos">
                <label for="data_inicio"><?php _e('Data Inicial:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo esc_attr($data_inicio); ?>">
                
                <label for="data_fim"><?php _e('Data Final:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo esc_attr($data_fim); ?>">
                
                <label>
                    <input type="checkbox" name="incluir_previsoes" value="1" <?php checked($incluir_previsoes); ?>>
                    <?php _e('Incluir Previsões', 'gestao-coletiva'); ?>
                </label>
                
                <button type="submit" class="gc-btn gc-btn-primary">
                    <?php _e('Atualizar Período', 'gestao-coletiva'); ?>
                </button>
            </div>
            
            <!-- Períodos pré-definidos -->
            <div class="gc-periodos-rapidos">
                <button type="button" class="gc-btn gc-btn-outline gc-periodo-rapido" data-periodo="mes-atual">
                    <?php _e('Mês Atual', 'gestao-coletiva'); ?>
                </button>
                <button type="button" class="gc-btn gc-btn-outline gc-periodo-rapido" data-periodo="mes-anterior">
                    <?php _e('Mês Anterior', 'gestao-coletiva'); ?>
                </button>
                <button type="button" class="gc-btn gc-btn-outline gc-periodo-rapido" data-periodo="ano-atual">
                    <?php _e('Ano Atual', 'gestao-coletiva'); ?>
                </button>
                <button type="button" class="gc-btn gc-btn-outline gc-periodo-rapido" data-periodo="tudo">
                    <?php _e('Histórico Completo', 'gestao-coletiva'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Resumo do balanço -->
    <div class="gc-balanco-periodo">
        <div class="gc-card">
            <h3><?php _e('Resumo do Período', 'gestao-coletiva'); ?></h3>
            <div class="gc-periodo-info">
                <span><?php echo date('d/m/Y', strtotime($relatorio['periodo']['inicio'])); ?> - 
                      <?php echo date('d/m/Y', strtotime($relatorio['periodo']['fim'])); ?></span>
                <?php if ($eh_relatorio_previsao && $relatorio['periodo']['is_futuro']): ?>
                    <span class="gc-badge gc-badge-info"><?php _e('Período Futuro', 'gestao-coletiva'); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($eh_relatorio_previsao): ?>
                <!-- Resumo com previsões -->
                <div class="gc-balanco-resumo-grid">
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Saldo Inicial:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor <?php echo ($relatorio['saldo_inicial'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['saldo_inicial']), 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Entradas Realizadas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-positivo">
                            +R$ <?php echo number_format($relatorio['movimentacao_realizada']['receitas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Saídas Realizadas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-negativo">
                            -R$ <?php echo number_format($relatorio['movimentacao_realizada']['despesas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Entradas Previstas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-positivo gc-previsto">
                            +R$ <?php echo number_format($relatorio['movimentacao_prevista']['receitas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Saídas Previstas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-negativo gc-previsto">
                            -R$ <?php echo number_format($relatorio['movimentacao_prevista']['despesas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item gc-saldo-final">
                        <span class="gc-label"><?php _e('Saldo Previsto:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-valor-destaque <?php echo ($relatorio['saldo_previsto'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['saldo_previsto']), 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Resumo histórico -->
                <div class="gc-balanco-resumo-grid">
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Saldo Inicial:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor <?php echo ($relatorio['saldo_inicial'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['saldo_inicial']), 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Entradas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-positivo">
                            +R$ <?php echo number_format($relatorio['movimentacao']['receitas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item">
                        <span class="gc-label"><?php _e('Saídas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-negativo">
                            -R$ <?php echo number_format($relatorio['movimentacao']['despesas'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <div class="gc-balanco-item gc-saldo-final">
                        <span class="gc-label"><?php _e('Saldo Final:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-valor-destaque <?php echo ($relatorio['saldo_final'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['saldo_final']), 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Gráfico de evolução -->
    <?php if (!empty($relatorio['evolucao_diaria'])): ?>
    <div class="gc-grafico-evolucao">
        <div class="gc-card">
            <h3><?php _e('Evolução do Saldo', 'gestao-coletiva'); ?></h3>
            <div id="gc-chart-evolucao" class="gc-chart-container">
                <canvas id="gc-canvas-evolucao"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Lista de lançamentos -->
    <div class="gc-lancamentos-periodo">
        <div class="gc-card">
            <h3><?php _e('Lançamentos do Período', 'gestao-coletiva'); ?></h3>
            
            <?php
            $todos_lancamentos = $eh_relatorio_previsao 
                ? array_merge($relatorio['lancamentos_realizados'], $relatorio['lancamentos_previstos'])
                : $relatorio['lancamentos'];
            
            // Ordenar por data
            usort($todos_lancamentos, function($a, $b) {
                return strtotime($a->data_criacao) - strtotime($b->data_criacao);
            });
            ?>
            
            <?php if (!empty($todos_lancamentos)): ?>
            <?php if ($eh_relatorio_previsao): ?>
                <!-- Tabs para relatório de previsão -->
                <div class="gc-tabs">
                    <button class="gc-tab-btn active" data-tab="todos">
                        📋 Todos (<?php echo count($todos_lancamentos); ?>)
                    </button>
                    <button class="gc-tab-btn" data-tab="realizados">
                        ✅ Realizados (<?php echo count($relatorio['lancamentos_realizados']); ?>)
                    </button>
                    <button class="gc-tab-btn" data-tab="previstos">
                        🔮 Previstos (<?php echo count($relatorio['lancamentos_previstos']); ?>)
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="gc-tabela-container">
                <table class="gc-tabela-lancamentos">
                    <thead>
                        <tr>
                            <th><?php _e('Data', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Número', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Descrição', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Autor', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Estado', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Entrada', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Saída', 'gestao-coletiva'); ?></th>
                            <?php if (!$eh_relatorio_previsao): ?>
                            <th><?php _e('Ações', 'gestao-coletiva'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todos_lancamentos as $lancamento): ?>
                        <?php 
                        $eh_previsto = isset($lancamento->lancamento_pai_id) || $lancamento->estado === 'previsto';
                        $class_extra = $eh_previsto ? ' gc-lancamento-previsto' : ' gc-lancamento-realizado';
                        $contador_contestacoes = GC_Lancamento::contar_contestacoes($lancamento->id);
                        $deve_mostrar_autoria = gc_deve_mostrar_autoria_publica($lancamento);
                        $autor = $deve_mostrar_autoria ? get_user_by('ID', $lancamento->autor_id) : null;
                        ?>
                        <tr class="gc-lancamento-row gc-tipo-<?php echo $lancamento->tipo; ?> gc-estado-<?php echo $lancamento->estado; ?><?php echo $class_extra; ?>">
                            <td><?php echo date('d/m/Y', strtotime($lancamento->data_criacao)); ?></td>
                            <td class="gc-numero-lancamento">
                                <strong>#<?php echo esc_html($lancamento->numero_unico); ?></strong>
                                <?php if ($eh_previsto): ?>
                                    <small class="gc-previsto-badge">Previsto</small>
                                <?php endif; ?>
                            </td>
                            <td class="gc-descricao-lancamento">
                                <?php echo esc_html($lancamento->descricao_curta); ?>
                                <?php if ($lancamento->estado !== 'efetivado' && !$eh_previsto): ?>
                                    <small class="gc-prazo-info">
                                        <?php echo esc_html(gc_estado_para_texto($lancamento->estado)); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($deve_mostrar_autoria && $autor): ?>
                                    <?php echo esc_html($autor->display_name); ?>
                                <?php elseif ($deve_mostrar_autoria): ?>
                                    <em><?php _e('Usuário removido', 'gestao-coletiva'); ?></em>
                                <?php else: ?>
                                    <em><?php _e('Anônimo', 'gestao-coletiva'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?> <?php echo $eh_previsto ? 'gc-previsto' : ''; ?>">
                                    <?php echo esc_html(gc_estado_para_texto($lancamento->estado)); ?>
                                </span>
                                <?php if ($contador_contestacoes > 0): ?>
                                    <br><small style="color: #d63638;">
                                        <?php printf(_n('%d contestação', '%d contestações', $contador_contestacoes, 'gestao-coletiva'), $contador_contestacoes); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="gc-valor-entrada">
                                <?php if ($lancamento->tipo === 'receita'): ?>
                                    <span class="gc-valor gc-positivo <?php echo $eh_previsto ? 'gc-previsto' : ''; ?>">
                                        R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="gc-valor-vazio">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="gc-valor-saida">
                                <?php if ($lancamento->tipo === 'despesa'): ?>
                                    <span class="gc-valor gc-negativo <?php echo $eh_previsto ? 'gc-previsto' : ''; ?>">
                                        R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="gc-valor-vazio">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$eh_relatorio_previsao): ?>
                            <td class="gc-acoes-lancamento">
                                <button type="button" class="gc-btn gc-btn-small gc-ver-lancamento" data-numero="<?php echo esc_attr($lancamento->numero_unico); ?>">
                                    <?php _e('Ver', 'gestao-coletiva'); ?>
                                </button>
                                <?php if ($contador_contestacoes > 0): ?>
                                    <button type="button" class="gc-btn gc-btn-small gc-ver-contestacoes-publico" data-id="<?php echo $lancamento->id; ?>" style="margin-left: 5px;">
                                        <?php _e('Contestações', 'gestao-coletiva'); ?> (<?php echo $contador_contestacoes; ?>)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="gc-empty-state">
                <p><?php _e('Nenhum lançamento encontrado para o período selecionado.', 'gestao-coletiva'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Períodos rápidos
    $('.gc-periodo-rapido').on('click', function() {
        var periodo = $(this).data('periodo');
        var hoje = new Date();
        var inicio, fim;
        
        switch(periodo) {
            case 'mes-atual':
                inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
                break;
            case 'mes-anterior':
                inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
                break;
            case 'ano-atual':
                inicio = new Date(hoje.getFullYear(), 0, 1);
                fim = new Date(hoje.getFullYear(), 11, 31);
                break;
            case 'tudo':
                inicio = new Date(2020, 0, 1);
                fim = hoje;
                break;
        }
        
        if (inicio && fim) {
            $('#data_inicio').val(inicio.toISOString().split('T')[0]);
            $('#data_fim').val(fim.toISOString().split('T')[0]);
            $('.gc-form-periodo').submit();
        }
    });
    
    // Ver lançamento individual
    $('.gc-ver-lancamento').on('click', function() {
        var numero = $(this).data('numero');
        if (numero) {
            // Buscar e exibir via AJAX em modal em vez de redirecionar
            gc_buscar_lancamento_ajax(numero);
        }
    });
    
    // Gráfico de evolução (se tiver dados)
    <?php if (!empty($relatorio['evolucao_diaria'])): ?>
    var chartData = {
        labels: [<?php echo "'" . implode("','", array_column($relatorio['evolucao_diaria'], 'data')) . "'"; ?>],
        datasets: [{
            label: '<?php _e("Saldo Acumulado", "gestao-coletiva"); ?>',
            data: [<?php echo implode(',', array_column($relatorio['evolucao_diaria'], 'saldo_acumulado')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    };
    
    // Implementar gráfico simples com Canvas ou biblioteca externa
    gc_desenhar_grafico_evolucao(chartData);
    <?php endif; ?>
    
    function gc_desenhar_grafico_evolucao(data) {
        // Implementação simplificada do gráfico
        // Em produção, usar Chart.js ou similar
        var canvas = document.getElementById('gc-canvas-evolucao');
        if (canvas) {
            var ctx = canvas.getContext('2d');
            canvas.width = canvas.offsetWidth;
            canvas.height = 300;
            
            // Desenho básico do gráfico
            ctx.fillStyle = 'rgba(75, 192, 192, 0.2)';
            ctx.fillRect(0, canvas.height - 50, canvas.width, 50);
            
            ctx.fillStyle = '#333';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('<?php _e("Gráfico de evolução (implementação simplificada)", "gestao-coletiva"); ?>', canvas.width/2, canvas.height/2);
        }
    }
    
    // Funcionalidade das abas de previsão
    $('.gc-tab-btn').on('click', function() {
        var $btn = $(this);
        var tab = $btn.data('tab');
        
        // Atualizar visual das abas
        $('.gc-tab-btn').removeClass('active');
        $btn.addClass('active');
        
        // Filtrar lançamentos baseado na aba
        var $rows = $('.gc-lancamento-row');
        
        switch(tab) {
            case 'todos':
                $rows.show();
                break;
            case 'realizados':
                $rows.hide();
                $('.gc-lancamento-realizado').show();
                break;
            case 'previstos':
                $rows.hide();
                $('.gc-lancamento-previsto').show();
                break;
        }
    });
    
    // Ver contestações de um lançamento (público)
    $(document).on('click', '.gc-ver-contestacoes-publico', function() {
        var lancamentoId = $(this).data('id');
        gc_abrir_modal_lista_contestacoes_publico(lancamentoId);
    });
    
    // Sistema de modal simples para views públicas (definir apenas uma vez)
    if (typeof gc_abrir_modal_publico === 'undefined') {
        window.gc_abrir_modal_publico = function(content) {
            var $modal = $('<div class="gc-modal-overlay-publico">').css({
                position: 'fixed',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                zIndex: 999999,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });
            
            var $modalContent = $('<div class="gc-modal-content-publico">').css({
                backgroundColor: 'white',
                padding: '20px',
                borderRadius: '8px',
                maxWidth: '90%',
                maxHeight: '90%',
                overflow: 'auto',
                position: 'relative'
            }).html(content);
            
            $modal.append($modalContent);
            $('body').append($modal);
            
            // Fechar ao clicar fora do modal
            $modal.on('click', function(e) {
                if (e.target === this) {
                    gc_fechar_modal_publico();
                }
            });
            
            // Fechar com ESC
            $(document).on('keydown.gc-modal', function(e) {
                if (e.keyCode === 27) {
                    gc_fechar_modal_publico();
                }
            });
        };
        
        window.gc_fechar_modal_publico = function() {
            $('.gc-modal-overlay-publico').remove();
            $(document).off('keydown.gc-modal');
        };
        
        window.gc_abrir_modal_lista_contestacoes_publico = function(lancamentoId) {
            $.ajax({
                url: gc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_listar_contestacoes_lancamento',
                    lancamento_id: lancamentoId,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var dados = response.data;
                        var html = '<div class="gc-modal-lista-contestacoes-publico">';
                        html += '<h3>Contestações do Lançamento #' + dados.lancamento.numero_unico + '</h3>';
                        
                        if (dados.contestacoes.length > 0) {
                            html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
                            html += '<thead><tr style="background: #f1f1f1;">';
                            html += '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Tipo</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Estado</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Data</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Descrição</th>';
                            html += '</tr></thead>';
                            html += '<tbody>';
                            
                            dados.contestacoes.forEach(function(contestacao) {
                                html += '<tr>';
                                html += '<td style="padding: 10px; border: 1px solid #ddd;">' + contestacao.tipo + '</td>';
                                html += '<td style="padding: 10px; border: 1px solid #ddd;"><span class="gc-badge gc-contestacao-' + contestacao.estado + '">' + contestacao.estado.replace(/_/g, ' ') + '</span></td>';
                                html += '<td style="padding: 10px; border: 1px solid #ddd;">' + new Date(contestacao.data_criacao).toLocaleDateString('pt-BR') + '</td>';
                                html += '<td style="padding: 10px; border: 1px solid #ddd;">' + (contestacao.descricao.length > 100 ? contestacao.descricao.substring(0, 100) + '...' : contestacao.descricao) + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                        } else {
                            html += '<p>Nenhuma contestação encontrada para este lançamento.</p>';
                        }
                        
                        html += '<div style="text-align: center; margin-top: 20px;">';
                        html += '<button type="button" onclick="gc_fechar_modal_publico()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">Fechar</button>';
                        html += '</div>';
                        html += '</div>';
                        
                        gc_abrir_modal_publico(html);
                    } else {
                        alert('Erro: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erro ao carregar contestações');
                }
            });
        };
    }
});
</script>