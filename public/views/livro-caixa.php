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
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?> <?php echo $eh_previsto ? 'gc-previsto' : ''; ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                </span>
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
});
</script>