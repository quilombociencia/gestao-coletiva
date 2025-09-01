<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listar';
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '';

$relatorios_mensais = GC_Relatorio::listar('mensal');
$relatorios_trimestrais = GC_Relatorio::listar('trimestral');
$relatorios_anuais = GC_Relatorio::listar('anual');
?>

<div class="wrap">
    <div class="gc-header-admin">
        <?php $logo_url = GC_Database::get_setting('logo_url'); ?>
        <?php if (!empty($logo_url)): ?>
            <div class="gc-logo-admin">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php _e('Logo da Organização', 'gestao-coletiva'); ?>" class="gc-admin-logo">
            </div>
        <?php endif; ?>
        <h1>
            <?php _e('Relatórios', 'gestao-coletiva'); ?>
            <?php if (current_user_can('manage_options')): ?>
            <a href="#" class="page-title-action" id="btn-upload-relatorio">
            <?php _e('Incluir Relatório', 'gestao-coletiva'); ?>
        </a>
        <?php endif; ?>
        </h1>
    </div>
    
    <!-- Relatórios Dinâmicos -->
    <div class="gc-relatorios-dinamicos">
        <!-- Tabs -->
        <div class="gc-tabs">
            <button class="gc-tab-btn active" data-tab="historico">
                📊 Livro Caixa Histórico
            </button>
            <button class="gc-tab-btn" data-tab="previsao">
                🔮 Relatório de Previsão
            </button>
        </div>
        
        <!-- Tab Histórico -->
        <div id="tab-historico" class="gc-tab-content active">
            <h3><?php _e('Livro Caixa Histórico', 'gestao-coletiva'); ?></h3>
            <p><?php _e('Relatório baseado em lançamentos já realizados e confirmados.', 'gestao-coletiva'); ?></p>
            
            <form id="gc-form-periodo" class="gc-form-inline">
                <label for="data_inicio"><?php _e('Data Inicial:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo date('Y-m-01'); ?>">
                
                <label for="data_fim"><?php _e('Data Final:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo date('Y-m-t'); ?>">
                
                <button type="button" id="btn-gerar-relatorio" class="button">
                    <?php _e('Gerar Relatório', 'gestao-coletiva'); ?>
                </button>
            </form>
            
            <div id="relatorio-periodo" class="gc-relatorio-periodo" style="display: none;">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
        </div>
        
        <!-- Tab Previsão -->
        <div id="tab-previsao" class="gc-tab-content">
            <h3><?php _e('Relatório de Previsão', 'gestao-coletiva'); ?></h3>
            <p><?php _e('Relatório incluindo lançamentos previstos e recorrências futuras. Ideal para planejamento financeiro.', 'gestao-coletiva'); ?></p>
            
            <form id="gc-form-previsao" class="gc-form-inline">
                <label for="data_inicio_prev"><?php _e('Data Inicial:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_inicio_prev" name="data_inicio" value="<?php echo date('Y-m-01'); ?>">
                
                <label for="data_fim_prev"><?php _e('Data Final:', 'gestao-coletiva'); ?></label>
                <input type="date" id="data_fim_prev" name="data_fim" value="<?php echo date('Y-m-t', strtotime('+3 months')); ?>">
                
                <div class="gc-presets">
                    <label><?php _e('Períodos rápidos:', 'gestao-coletiva'); ?></label>
                    <button type="button" class="button button-small gc-preset-periodo" data-meses="3">
                        <?php _e('Próximos 3 meses', 'gestao-coletiva'); ?>
                    </button>
                    <button type="button" class="button button-small gc-preset-periodo" data-meses="6">
                        <?php _e('Próximos 6 meses', 'gestao-coletiva'); ?>
                    </button>
                    <button type="button" class="button button-small gc-preset-periodo" data-meses="12">
                        <?php _e('Próximo ano', 'gestao-coletiva'); ?>
                    </button>
                </div>
                
                <button type="button" id="btn-gerar-previsao" class="button button-primary">
                    <?php _e('Gerar Previsão', 'gestao-coletiva'); ?>
                </button>
            </form>
            
            <div id="relatorio-previsao" class="gc-relatorio-previsao-container" style="display: none;">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Relatórios Mensais -->
    <div class="gc-relatorios-secao">
        <h2><?php _e('Relatórios Mensais', 'gestao-coletiva'); ?></h2>
        <?php if (!empty($relatorios_mensais)): ?>
        <div class="gc-relatorios-grid">
            <?php foreach ($relatorios_mensais as $relatorio): ?>
            <div class="gc-relatorio-item">
                <div class="gc-relatorio-info">
                    <h4><?php echo esc_html($relatorio->titulo); ?></h4>
                    <p><?php _e('Período:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->periodo); ?></p>
                    <p><?php _e('Enviado em:', 'gestao-coletiva'); ?> <?php echo date('d/m/Y H:i', strtotime($relatorio->data_upload)); ?></p>
                    <p><?php _e('Por:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->autor_nome); ?></p>
                </div>
                <div class="gc-relatorio-acoes">
                    <a href="<?php echo GC_Relatorio::get_arquivo_url($relatorio->arquivo); ?>" 
                       target="_blank" class="button button-small">
                        <?php _e('Download', 'gestao-coletiva'); ?>
                    </a>
                    <?php if (current_user_can('manage_options')): ?>
                    <button type="button" class="button button-small gc-excluir-relatorio" 
                            data-id="<?php echo $relatorio->id; ?>">
                        <?php _e('Excluir', 'gestao-coletiva'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?php _e('Nenhum relatório mensal encontrado.', 'gestao-coletiva'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Relatórios Trimestrais -->
    <div class="gc-relatorios-secao">
        <h2><?php _e('Relatórios Trimestrais', 'gestao-coletiva'); ?></h2>
        <?php if (!empty($relatorios_trimestrais)): ?>
        <div class="gc-relatorios-grid">
            <?php foreach ($relatorios_trimestrais as $relatorio): ?>
            <div class="gc-relatorio-item">
                <div class="gc-relatorio-info">
                    <h4><?php echo esc_html($relatorio->titulo); ?></h4>
                    <p><?php _e('Período:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->periodo); ?></p>
                    <p><?php _e('Enviado em:', 'gestao-coletiva'); ?> <?php echo date('d/m/Y H:i', strtotime($relatorio->data_upload)); ?></p>
                    <p><?php _e('Por:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->autor_nome); ?></p>
                </div>
                <div class="gc-relatorio-acoes">
                    <a href="<?php echo GC_Relatorio::get_arquivo_url($relatorio->arquivo); ?>" 
                       target="_blank" class="button button-small">
                        <?php _e('Download', 'gestao-coletiva'); ?>
                    </a>
                    <?php if (current_user_can('manage_options')): ?>
                    <button type="button" class="button button-small gc-excluir-relatorio" 
                            data-id="<?php echo $relatorio->id; ?>">
                        <?php _e('Excluir', 'gestao-coletiva'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?php _e('Nenhum relatório trimestral encontrado.', 'gestao-coletiva'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Relatórios Anuais -->
    <div class="gc-relatorios-secao">
        <h2><?php _e('Relatórios Anuais', 'gestao-coletiva'); ?></h2>
        <?php if (!empty($relatorios_anuais)): ?>
        <div class="gc-relatorios-grid">
            <?php foreach ($relatorios_anuais as $relatorio): ?>
            <div class="gc-relatorio-item">
                <div class="gc-relatorio-info">
                    <h4><?php echo esc_html($relatorio->titulo); ?></h4>
                    <p><?php _e('Período:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->periodo); ?></p>
                    <p><?php _e('Enviado em:', 'gestao-coletiva'); ?> <?php echo date('d/m/Y H:i', strtotime($relatorio->data_upload)); ?></p>
                    <p><?php _e('Por:', 'gestao-coletiva'); ?> <?php echo esc_html($relatorio->autor_nome); ?></p>
                </div>
                <div class="gc-relatorio-acoes">
                    <a href="<?php echo GC_Relatorio::get_arquivo_url($relatorio->arquivo); ?>" 
                       target="_blank" class="button button-small">
                        <?php _e('Download', 'gestao-coletiva'); ?>
                    </a>
                    <?php if (current_user_can('manage_options')): ?>
                    <button type="button" class="button button-small gc-excluir-relatorio" 
                            data-id="<?php echo $relatorio->id; ?>">
                        <?php _e('Excluir', 'gestao-coletiva'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p><?php _e('Nenhum relatório anual encontrado.', 'gestao-coletiva'); ?></p>
        <?php endif; ?>
    </div>
</div>