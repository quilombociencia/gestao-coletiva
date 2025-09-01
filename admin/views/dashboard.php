<?php
if (!defined('ABSPATH')) {
    exit;
}

$mes_atual = date('Y-m');
$data_inicio = $mes_atual . '-01 00:00:00';
$data_fim = date('Y-m-t 23:59:59');

$balanco_mes = GC_Lancamento::calcular_saldo_periodo($data_inicio, $data_fim, true);
$lancamentos_recentes = GC_Lancamento::listar(array('limit' => 5));
$contestacoes_pendentes = GC_Contestacao::obter_pendentes_por_usuario();
?>

<div class="wrap">
    <div class="gc-header-admin">
        <?php $logo_url = GC_Database::get_setting('logo_url'); ?>
        <?php if (!empty($logo_url)): ?>
            <div class="gc-logo-admin">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php _e('Logo da Organização', 'gestao-coletiva'); ?>" class="gc-admin-logo">
            </div>
        <?php endif; ?>
        <h1><?php _e('Gestão Coletiva - Painel', 'gestao-coletiva'); ?></h1>
    </div>
    
    <div class="gc-dashboard">
        <div class="gc-row">
            <!-- Banner para contribuir -->
            <div class="gc-card gc-banner-contribuir">
                <h2><?php _e('Contribua com o Projeto', 'gestao-coletiva'); ?></h2>
                <p><?php _e('Sua contribuição ajuda a manter este projeto ativo e em constante desenvolvimento.', 'gestao-coletiva'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=criar&tipo=receita'); ?>" class="button button-primary button-large">
                    <?php _e('Fazer Doação', 'gestao-coletiva'); ?>
                </a>
            </div>
            
            <!-- Balanço financeiro do mês -->
            <div class="gc-card gc-balanco-mes">
                <h3><?php _e('Balanço do Mês', 'gestao-coletiva'); ?> - <?php echo date('m/Y'); ?></h3>
                <div class="gc-balanco-valores">
                    <div class="gc-receitas">
                        <span class="gc-label"><?php _e('Receitas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-positivo">R$ <?php echo number_format($balanco_mes['receitas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-despesas">
                        <span class="gc-label"><?php _e('Despesas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-negativo">R$ <?php echo number_format($balanco_mes['despesas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-saldo">
                        <span class="gc-label"><?php _e('Saldo:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor <?php echo ($balanco_mes['saldo'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                            R$ <?php echo number_format($balanco_mes['saldo'], 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="gc-row">
            <!-- Ações rápidas -->
            <div class="gc-card gc-acoes-rapidas">
                <h3><?php _e('Ações Rápidas', 'gestao-coletiva'); ?></h3>
                <div class="gc-acoes-grid">
                    <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=criar&tipo=receita'); ?>" class="gc-acao">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Incluir Fundos', 'gestao-coletiva'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=criar&tipo=despesa'); ?>" class="gc-acao">
                        <span class="dashicons dashicons-minus"></span>
                        <?php _e('Incluir Despesa', 'gestao-coletiva'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=buscar'); ?>" class="gc-acao">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Ver Lançamento', 'gestao-coletiva'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=gc-relatorios'); ?>" class="gc-acao">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Livro Caixa', 'gestao-coletiva'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Contestações pendentes -->
            <?php if (!empty($contestacoes_pendentes)): ?>
            <div class="gc-card gc-contestacoes-pendentes">
                <h3><?php _e('Contestações Pendentes', 'gestao-coletiva'); ?></h3>
                <div class="gc-contestacoes-lista">
                    <?php foreach ($contestacoes_pendentes as $contestacao): ?>
                    <div class="gc-contestacao-item">
                        <strong>#<?php echo esc_html($contestacao->numero_unico); ?></strong>
                        <span><?php echo esc_html($contestacao->lancamento_descricao); ?></span>
                        <span class="gc-valor">R$ <?php echo number_format($contestacao->lancamento_valor, 2, ',', '.'); ?></span>
                        <a href="<?php echo admin_url('admin.php?page=gc-contestacoes&action=ver&id=' . $contestacao->id); ?>" class="button button-small">
                            <?php _e('Ver', 'gestao-coletiva'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo admin_url('admin.php?page=gc-contestacoes'); ?>" class="button">
                    <?php _e('Ver Todas', 'gestao-coletiva'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="gc-row">
            <!-- Lançamentos recentes -->
            <div class="gc-card gc-lancamentos-recentes">
                <h3><?php _e('Lançamentos Recentes', 'gestao-coletiva'); ?></h3>
                <?php if (!empty($lancamentos_recentes)): ?>
                <table class="gc-table">
                    <thead>
                        <tr>
                            <th><?php _e('Número', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Tipo', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Descrição', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Valor', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Estado', 'gestao-coletiva'); ?></th>
                            <th><?php _e('Data', 'gestao-coletiva'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lancamentos_recentes as $lancamento): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $lancamento->id); ?>">
                                    #<?php echo esc_html($lancamento->numero_unico); ?>
                                </a>
                            </td>
                            <td>
                                <span class="gc-tipo-<?php echo $lancamento->tipo; ?>">
                                    <?php echo ($lancamento->tipo == 'receita') ? __('Receita', 'gestao-coletiva') : __('Despesa', 'gestao-coletiva'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($lancamento->descricao_curta); ?></td>
                            <td class="gc-valor <?php echo ($lancamento->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                            </td>
                            <td>
                                <span class="gc-estado gc-estado-<?php echo $lancamento->estado; ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?php echo admin_url('admin.php?page=gc-lancamentos'); ?>" class="button">
                    <?php _e('Ver Todos', 'gestao-coletiva'); ?>
                </a>
                <?php else: ?>
                <p><?php _e('Nenhum lançamento encontrado.', 'gestao-coletiva'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Status do Sistema -->
        <div class="gc-row">
            <div class="gc-card gc-status-sistema">
                <h3><?php _e('Status do Sistema', 'gestao-coletiva'); ?></h3>
                <div class="gc-status-grid">
                    <div class="gc-status-item">
                        <span class="gc-status-label"><?php _e('Próximo processamento:', 'gestao-coletiva'); ?></span>
                        <span class="gc-status-valor"><?php echo date('d/m/Y H:i', wp_next_scheduled('gc_processar_vencimentos')); ?></span>
                    </div>
                    <div class="gc-status-item">
                        <span class="gc-status-label"><?php _e('Plugin versão:', 'gestao-coletiva'); ?></span>
                        <span class="gc-status-valor"><?php echo GC_VERSION; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>