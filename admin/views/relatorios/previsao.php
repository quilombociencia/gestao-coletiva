<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="gc-relatorio-previsao">
    <!-- Header do Relatório -->
    <div class="gc-relatorio-header">
        <h3>
            <?php if ($relatorio['periodo']['is_futuro']): ?>
                📈 Relatório de Previsão
            <?php else: ?>
                📊 Relatório Histórico + Previsão  
            <?php endif; ?>
        </h3>
        <p class="gc-periodo-info">
            <?php echo date('d/m/Y', strtotime($relatorio['periodo']['inicio'])); ?> - 
            <?php echo date('d/m/Y', strtotime($relatorio['periodo']['fim'])); ?>
            <?php if ($relatorio['periodo']['is_futuro']): ?>
                <span class="gc-badge gc-badge-info">Período Futuro</span>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Resumo Financeiro -->
    <div class="gc-resumo-financeiro">
        <div class="gc-resumo-grid">
            <div class="gc-resumo-card">
                <div class="gc-resumo-label">💰 Saldo Inicial</div>
                <div class="gc-resumo-valor <?php echo ($relatorio['saldo_inicial'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                    R$ <?php echo number_format(abs($relatorio['saldo_inicial']), 2, ',', '.'); ?>
                </div>
            </div>
            
            <div class="gc-resumo-card">
                <div class="gc-resumo-label">✅ Saldo Realizado</div>
                <div class="gc-resumo-valor <?php echo ($relatorio['saldo_realizado'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                    R$ <?php echo number_format(abs($relatorio['saldo_realizado']), 2, ',', '.'); ?>
                </div>
            </div>
            
            <div class="gc-resumo-card gc-destaque">
                <div class="gc-resumo-label">🔮 Saldo Previsto</div>
                <div class="gc-resumo-valor gc-resumo-valor-grande <?php echo ($relatorio['saldo_previsto'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                    R$ <?php echo number_format(abs($relatorio['saldo_previsto']), 2, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Movimentação Detalhada -->
    <div class="gc-movimentacao-detalhada">
        <div class="gc-movimentacao-grid">
            <!-- Movimentação Realizada -->
            <div class="gc-movimentacao-card">
                <h4>✅ Movimentação Realizada</h4>
                <div class="gc-movimentacao-valores">
                    <div class="gc-mov-item">
                        <span class="gc-mov-label">Entradas:</span>
                        <span class="gc-mov-valor gc-positivo">+R$ <?php echo number_format($relatorio['movimentacao_realizada']['receitas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-mov-item">
                        <span class="gc-mov-label">Saídas:</span>
                        <span class="gc-mov-valor gc-negativo">-R$ <?php echo number_format($relatorio['movimentacao_realizada']['despesas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-mov-item gc-mov-total">
                        <span class="gc-mov-label">Saldo:</span>
                        <span class="gc-mov-valor <?php echo ($relatorio['movimentacao_realizada']['saldo'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['movimentacao_realizada']['saldo']), 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                <div class="gc-mov-count">
                    <?php echo count($relatorio['lancamentos_realizados']); ?> lançamentos
                </div>
            </div>
            
            <!-- Movimentação Prevista -->
            <div class="gc-movimentacao-card gc-previsao">
                <h4>🔮 Movimentação Prevista</h4>
                <div class="gc-movimentacao-valores">
                    <div class="gc-mov-item">
                        <span class="gc-mov-label">Entradas:</span>
                        <span class="gc-mov-valor gc-positivo">+R$ <?php echo number_format($relatorio['movimentacao_prevista']['receitas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-mov-item">
                        <span class="gc-mov-label">Saídas:</span>
                        <span class="gc-mov-valor gc-negativo">-R$ <?php echo number_format($relatorio['movimentacao_prevista']['despesas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-mov-item gc-mov-total">
                        <span class="gc-mov-label">Saldo:</span>
                        <span class="gc-mov-valor <?php echo ($relatorio['movimentacao_prevista']['saldo'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format(abs($relatorio['movimentacao_prevista']['saldo']), 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                <div class="gc-mov-count">
                    <?php echo count($relatorio['lancamentos_previstos']); ?> lançamentos
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico de Evolução -->
    <?php if (!empty($relatorio['evolucao_diaria'])): ?>
    <div class="gc-grafico-evolucao">
        <h4>📈 Evolução do Saldo</h4>
        <div class="gc-grafico-container">
            <canvas id="gc-canvas-previsao" width="800" height="300"></canvas>
            <div class="gc-grafico-legenda">
                <div class="gc-legenda-item">
                    <span class="gc-legenda-cor gc-cor-realizado"></span>
                    Saldo Realizado
                </div>
                <div class="gc-legenda-item">
                    <span class="gc-legenda-cor gc-cor-previsto"></span>
                    Saldo com Previsão
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Lançamentos -->
    <div class="gc-lancamentos-previsao">
        <h4>📋 Lançamentos do Período</h4>
        
        <!-- Tabs -->
        <div class="gc-tabs">
            <button class="gc-tab-btn active" data-tab="realizados" onclick="gcShowTab('realizados')">
                ✅ Realizados (<?php echo count($relatorio['lancamentos_realizados']); ?>)
            </button>
            <button class="gc-tab-btn" data-tab="previstos" onclick="gcShowTab('previstos')">
                🔮 Previstos (<?php echo count($relatorio['lancamentos_previstos']); ?>)
            </button>
        </div>
        
        <!-- Tab Realizados -->
        <div id="tab-realizados" class="gc-tab-content active">
            <?php if (!empty($relatorio['lancamentos_realizados'])): ?>
                <div class="gc-tabela-container">
                    <table class="gc-tabela-lancamentos">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Descrição</th>
                                <th>Estado</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorio['lancamentos_realizados'] as $lancamento): ?>
                            <tr class="gc-lancamento-row gc-realizado">
                                <td><?php echo date('d/m/Y', strtotime($lancamento->data_criacao)); ?></td>
                                <td><strong>#<?php echo esc_html($lancamento->numero_unico); ?></strong></td>
                                <td><?php echo esc_html($lancamento->descricao_curta); ?></td>
                                <td>
                                    <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                    </span>
                                </td>
                                <td class="gc-valor-entrada">
                                    <?php if ($lancamento->tipo === 'receita'): ?>
                                        <span class="gc-valor gc-positivo">R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-valor-vazio">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="gc-valor-saida">
                                    <?php if ($lancamento->tipo === 'despesa'): ?>
                                        <span class="gc-valor gc-negativo">R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-valor-vazio">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="gc-empty-state">
                    <p>Nenhum lançamento realizado no período.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab Previstos -->
        <div id="tab-previstos" class="gc-tab-content">
            <?php 
            // Debug: verificar se temos lançamentos previstos
            $count_previstos = count($relatorio['lancamentos_previstos']);
            error_log("Debug GC: Lançamentos previstos encontrados: " . $count_previstos);
            ?>
            <?php if (!empty($relatorio['lancamentos_previstos'])): ?>
                <div class="gc-tabela-container">
                    <table class="gc-tabela-lancamentos">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Descrição</th>
                                <th>Recorrência</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorio['lancamentos_previstos'] as $lancamento): ?>
                            <tr class="gc-lancamento-row gc-previsto">
                                <td><?php echo date('d/m/Y', strtotime($lancamento->data_criacao)); ?></td>
                                <td><strong>#<?php echo esc_html($lancamento->numero_unico); ?></strong></td>
                                <td><?php echo esc_html($lancamento->descricao_curta); ?></td>
                                <td>
                                    <?php if ($lancamento->recorrencia !== 'unica'): ?>
                                        <span class="gc-badge gc-badge-recorrencia"><?php echo ucfirst($lancamento->recorrencia); ?></span>
                                    <?php else: ?>
                                        <span class="gc-badge gc-badge-outline">Única</span>
                                    <?php endif; ?>
                                </td>
                                <td class="gc-valor-entrada">
                                    <?php if ($lancamento->tipo === 'receita'): ?>
                                        <span class="gc-valor gc-positivo">R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-valor-vazio">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="gc-valor-saida">
                                    <?php if ($lancamento->tipo === 'despesa'): ?>
                                        <span class="gc-valor gc-negativo">R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-valor-vazio">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="gc-empty-state">
                    <p><strong>🔍 Debug:</strong> Nenhum lançamento previsto encontrado para este período.</p>
                    <p><small>Total de lançamentos previstos: <?php echo $count_previstos; ?></small></p>
                    <p><small>Período: <?php echo $relatorio['periodo']['inicio'] . ' a ' . $relatorio['periodo']['fim']; ?></small></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="gc-relatorio-footer">
        <p><strong>Total:</strong> <?php echo $relatorio['total_lancamentos']; ?> lançamentos no período</p>
        <p>Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Debug das tabs
    console.log('GC Debug: Template de previsão carregado');
    console.log('GC Debug: Botões de tab encontrados (todos):', $('.gc-tab-btn').length);
    console.log('GC Debug: Botões de tab dos lançamentos:', $('.gc-lancamentos-previsao .gc-tab-btn').length);
    console.log('GC Debug: Conteúdos de tab dos lançamentos:', $('.gc-lancamentos-previsao .gc-tab-content').length);
    
    // Função para alternar tabs (fallback)
    window.gcShowTab = function(tabName) {
        console.log('GC Debug: gcShowTab chamada para:', tabName);
        
        // Remover active de todos os botões e conteúdos
        $('.gc-lancamentos-previsao .gc-tab-btn').removeClass('active');
        $('.gc-lancamentos-previsao .gc-tab-content').removeClass('active');
        
        // Ativar o botão clicado
        $('.gc-lancamentos-previsao .gc-tab-btn[data-tab="' + tabName + '"]').addClass('active');
        
        // Ativar o conteúdo correspondente
        $('.gc-lancamentos-previsao #tab-' + tabName).addClass('active');
        
        console.log('GC Debug: Tab ' + tabName + ' ativada');
    };
    
    // Desenhar gráfico de evolução
    <?php if (!empty($relatorio['evolucao_diaria'])): ?>
    var evolucaoData = <?php echo json_encode($relatorio['evolucao_diaria']); ?>;
    gc_desenhar_grafico_previsao(evolucaoData);
    <?php endif; ?>
    
    function gc_desenhar_grafico_previsao(data) {
        var canvas = document.getElementById('gc-canvas-previsao');
        if (!canvas) return;
        
        var ctx = canvas.getContext('2d');
        var width = canvas.width;
        var height = canvas.height;
        
        // Limpar canvas
        ctx.clearRect(0, 0, width, height);
        
        if (data.length === 0) return;
        
        // Calcular escalas
        var maxValue = Math.max(...data.map(d => Math.max(d.saldo_acumulado, d.saldo_com_previsao)));
        var minValue = Math.min(...data.map(d => Math.min(d.saldo_acumulado, d.saldo_com_previsao)));
        var range = maxValue - minValue;
        
        // Margens
        var margin = 50;
        var graphWidth = width - 2 * margin;
        var graphHeight = height - 2 * margin;
        
        // Função para converter valores para coordenadas
        function getX(index) {
            return margin + (index * graphWidth / (data.length - 1));
        }
        
        function getY(value) {
            return margin + graphHeight - ((value - minValue) / range * graphHeight);
        }
        
        // Desenhar eixos
        ctx.strokeStyle = '#ccc';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(margin, margin);
        ctx.lineTo(margin, height - margin);
        ctx.lineTo(width - margin, height - margin);
        ctx.stroke();
        
        // Desenhar linha do saldo realizado
        ctx.strokeStyle = '#28a745';
        ctx.lineWidth = 2;
        ctx.beginPath();
        data.forEach(function(point, index) {
            var x = getX(index);
            var y = getY(point.saldo_acumulado);
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
        
        // Desenhar linha do saldo com previsão
        ctx.strokeStyle = '#007cba';
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 5]);
        ctx.beginPath();
        data.forEach(function(point, index) {
            var x = getX(index);
            var y = getY(point.saldo_com_previsao);
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
        ctx.setLineDash([]);
        
        // Adicionar pontos
        data.forEach(function(point, index) {
            var x = getX(index);
            
            // Ponto saldo realizado
            ctx.fillStyle = '#28a745';
            ctx.beginPath();
            ctx.arc(x, getY(point.saldo_acumulado), 3, 0, 2 * Math.PI);
            ctx.fill();
            
            // Ponto saldo previsto
            ctx.fillStyle = '#007cba';
            ctx.beginPath();
            ctx.arc(x, getY(point.saldo_com_previsao), 3, 0, 2 * Math.PI);
            ctx.fill();
        });
        
        // Labels dos valores nos eixos (simplificado)
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        
        // Labels do eixo X (algumas datas)
        var step = Math.ceil(data.length / 8);
        for (var i = 0; i < data.length; i += step) {
            if (data[i]) {
                ctx.fillText(data[i].data, getX(i), height - margin + 20);
            }
        }
    }
});
</script>

<style>
/* Estilos específicos para o relatório de previsão */
.gc-relatorio-previsao {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.gc-relatorio-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f3f4;
}

.gc-relatorio-header h3 {
    color: #2c3e50;
    margin: 0 0 10px 0;
    font-size: 24px;
}

.gc-periodo-info {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.gc-resumo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.gc-resumo-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.gc-resumo-card.gc-destaque {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.gc-resumo-label {
    font-size: 14px;
    margin-bottom: 10px;
    opacity: 0.8;
}

.gc-resumo-valor {
    font-size: 24px;
    font-weight: bold;
}

.gc-resumo-valor-grande {
    font-size: 28px;
}

.gc-movimentacao-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.gc-movimentacao-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.gc-movimentacao-card.gc-previsao {
    border: 2px solid #007cba;
    background: #f0f8ff;
}

.gc-movimentacao-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.gc-mov-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
}

.gc-mov-item.gc-mov-total {
    border-top: 1px solid #dee2e6;
    font-weight: bold;
    font-size: 16px;
    margin-top: 10px;
    padding-top: 10px;
}

.gc-mov-count {
    text-align: center;
    font-size: 12px;
    color: #666;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.gc-grafico-container {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.gc-grafico-legenda {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 15px;
}

.gc-legenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.gc-legenda-cor {
    width: 20px;
    height: 3px;
    border-radius: 2px;
}

.gc-cor-realizado {
    background: #28a745;
}

.gc-cor-previsto {
    background: #007cba;
}

.gc-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.gc-tab-btn {
    background: none;
    border: none;
    padding: 15px 25px;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.gc-tab-btn.active {
    color: #007cba;
    border-bottom-color: #007cba;
    font-weight: 600;
}

.gc-tab-content {
    display: none;
}

.gc-tab-content.active {
    display: block;
}

.gc-lancamento-row.gc-previsto {
    background: #f0f8ff;
    border-left: 3px solid #007cba;
}

.gc-lancamento-row.gc-realizado {
    background: #f0fff0;
    border-left: 3px solid #28a745;
}

.gc-badge-recorrencia {
    background: #007cba;
    color: white;
}

.gc-relatorio-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    color: #666;
    font-size: 14px;
}

@media (max-width: 768px) {
    .gc-resumo-grid {
        grid-template-columns: 1fr;
    }
    
    .gc-movimentacao-grid {
        grid-template-columns: 1fr;
    }
    
    .gc-tabs {
        flex-direction: column;
    }
    
    .gc-tab-btn {
        border-bottom: 1px solid #e9ecef;
        border-right: none;
    }
    
    .gc-tab-btn.active {
        border-bottom-color: #e9ecef;
        border-left: 3px solid #007cba;
    }
}
</style>