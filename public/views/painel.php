<?php
if (!defined('ABSPATH')) {
    exit;
}

$mes_atual = date('Y-m');
$data_inicio = $mes_atual . '-01 00:00:00';
$data_fim = date('Y-m-t 23:59:59');

$balanco_mes = GC_Lancamento::calcular_saldo_periodo($data_inicio, $data_fim, true);
$saldo_atual = GC_Lancamento::calcular_saldo_periodo('2000-01-01', date('Y-m-d 23:59:59'));

// Dados PIX para o JavaScript
$chave_pix = GC_Database::get_setting('chave_pix');
$nome_beneficiario_pix = GC_Database::get_setting('nome_beneficiario_pix');
?>

<div id="gc-painel-publico" class="gc-container">
    <!-- Banner para contribuir -->
    <div class="gc-banner-contribuir">
        <div class="gc-banner-content">
            <h2><?php _e('Contribua com o Projeto', 'gestao-coletiva'); ?></h2>
            <p><?php _e('Sua contribuição ajuda a manter este projeto ativo e em constante desenvolvimento. Seja parte desta iniciativa coletiva!', 'gestao-coletiva'); ?></p>
            <div class="gc-banner-actions">
                <button type="button" class="gc-btn gc-btn-primary gc-btn-contribuir" data-tipo="receita">
                    <span class="gc-icon">💝</span>
                    <?php _e('Fazer Doação', 'gestao-coletiva'); ?>
                </button>
                <button type="button" class="gc-btn gc-btn-secondary gc-btn-ver-lancamento">
                    <span class="gc-icon">🔍</span>
                    <?php _e('Consultar Doação', 'gestao-coletiva'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Balanço financeiro resumido -->
    <div class="gc-balanco-resumo">
        <div class="gc-card gc-balanco-card">
            <h3><?php _e('Situação Financeira', 'gestao-coletiva'); ?></h3>
            
            <div class="gc-saldo-principal">
                <span class="gc-label"><?php _e('Saldo Atual:', 'gestao-coletiva'); ?></span>
                <span class="gc-valor gc-saldo-valor <?php echo ($saldo_atual['saldo'] >= 0) ? 'gc-positivo' : 'gc-negativo'; ?>">
                    R$ <?php echo number_format(abs($saldo_atual['saldo']), 2, ',', '.'); ?>
                </span>
            </div>
            
            <div class="gc-balanco-mes-atual">
                <h4><?php _e('Movimento do Mês', 'gestao-coletiva'); ?> (<?php echo date('m/Y'); ?>)</h4>
                <div class="gc-balanco-detalhes">
                    <div class="gc-receitas">
                        <span class="gc-label"><?php _e('Entradas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-positivo">+R$ <?php echo number_format($balanco_mes['receitas'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="gc-despesas">
                        <span class="gc-label"><?php _e('Saídas:', 'gestao-coletiva'); ?></span>
                        <span class="gc-valor gc-negativo">-R$ <?php echo number_format($balanco_mes['despesas'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ações rápidas -->
    <div class="gc-acoes-container">
        <h3><?php _e('O que você pode fazer?', 'gestao-coletiva'); ?></h3>
        
        <div class="gc-acoes-grid">
            <div class="gc-acao-card" data-acao="incluir-fundos">
                <div class="gc-acao-icon">💰</div>
                <h4><?php _e('Incluir Fundos', 'gestao-coletiva'); ?></h4>
                <p><?php _e('Registre sua doação pontual ou recorrente via PIX', 'gestao-coletiva'); ?></p>
                <button type="button" class="gc-btn gc-btn-outline gc-btn-acao" data-tipo="receita">
                    <?php _e('Doar Agora', 'gestao-coletiva'); ?>
                </button>
            </div>
            
            <?php if (is_user_logged_in() && current_user_can('manage_options')): ?>
            <div class="gc-acao-card" data-acao="incluir-despesa">
                <div class="gc-acao-icon">📝</div>
                <h4><?php _e('Incluir Despesa', 'gestao-coletiva'); ?></h4>
                <p><?php _e('Registre gastos do projeto com comprovantes', 'gestao-coletiva'); ?></p>
                <button type="button" class="gc-btn gc-btn-outline gc-btn-acao" data-tipo="despesa">
                    <?php _e('Registrar Gasto', 'gestao-coletiva'); ?>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="gc-acao-card" data-acao="ver-lancamento">
                <div class="gc-acao-icon">🔍</div>
                <h4><?php _e('Ver Lançamento', 'gestao-coletiva'); ?></h4>
                <p><?php _e('Consulte o status de uma doação ou gasto', 'gestao-coletiva'); ?></p>
                <button type="button" class="gc-btn gc-btn-outline gc-btn-ver-lancamento">
                    <?php _e('Consultar', 'gestao-coletiva'); ?>
                </button>
            </div>
            
            <div class="gc-acao-card" data-acao="livro-caixa">
                <div class="gc-acao-icon">📊</div>
                <h4><?php _e('Livro Caixa', 'gestao-coletiva'); ?></h4>
                <p><?php _e('Veja a transparência total das finanças', 'gestao-coletiva'); ?></p>
                <a href="#" class="gc-btn gc-btn-outline gc-ver-livro-caixa">
                    <?php _e('Ver Livro-Caixa', 'gestao-coletiva'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Transparência e informações -->
    <div class="gc-transparencia">
        <div class="gc-card">
            <h3><?php _e('Transparência Total', 'gestao-coletiva'); ?></h3>
            <p><?php _e('Todos os lançamentos são públicos e podem ser verificados a qualquer momento. A prestação de contas acontece em tempo real através do livro-caixa público e periodicamente através de relatórios detalhados.', 'gestao-coletiva'); ?></p>
            
            <div class="gc-stats-grid">
                <div class="gc-stat">
                    <span class="gc-stat-numero"><?php echo count(GC_Lancamento::listar(array('estado' => 'efetivado'))); ?></span>
                    <span class="gc-stat-label"><?php _e('Lançamentos Confirmados', 'gestao-coletiva'); ?></span>
                </div>
                <div class="gc-stat">
                    <span class="gc-stat-numero"><?php echo count(GC_Lancamento::listar(array('recorrencia_ativa' => 1))); ?></span>
                    <span class="gc-stat-label"><?php _e('Doações Recorrentes', 'gestao-coletiva'); ?></span>
                </div>
                <div class="gc-stat">
                    <span class="gc-stat-numero"><?php echo count(GC_Contestacao::listar()); ?></span>
                    <span class="gc-stat-label"><?php _e('Contestações Resolvidas', 'gestao-coletiva'); ?></span>
                </div>
                <div class="gc-stat">
                    <span class="gc-stat-numero">100%</span>
                    <span class="gc-stat-label"><?php _e('Transparência', 'gestao-coletiva'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ações -->
<div id="gc-modal" class="gc-modal" style="display: none;">
    <div class="gc-modal-content">
        <span class="gc-modal-close">&times;</span>
        <div id="gc-modal-body">
            <!-- Conteúdo será carregado dinamicamente -->
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Painel: Inicializando handlers locais...');
    
    // Ver livro-caixa
    $('.gc-ver-livro-caixa').on('click', function(e) {
        e.preventDefault();
        console.log('Link livro-caixa clicado');
        // Redirecionar para página que usa o shortcode [gc_livro_caixa]
        window.location.href = '<?php echo home_url("livro-caixa"); ?>';
    });
    
    // Verificar se o sistema de modal global está disponível
    if (typeof gc_abrir_modal !== 'function') {
        console.log('Sistema de modal global não encontrado. Definindo handlers locais...');
        
        // Handlers locais apenas se o global não existir
        $('.gc-btn-acao, .gc-btn-contribuir').on('click', function() {
            var tipo = $(this).data('tipo');
            console.log('Botão ação clicado:', tipo);
            gc_abrir_modal_lancamento(tipo);
        });
        
        $('.gc-btn-ver-lancamento').on('click', function() {
            console.log('Botão consultar clicado');
            gc_abrir_modal_buscar();
        });
        
        // Fechar modal
        $('.gc-modal-close, .gc-modal').on('click', function(e) {
            if (e.target === this) {
                $('#gc-modal').hide();
            }
        });
    } else {
        console.log('Sistema de modal global encontrado. Usando handlers globais.');
    }
    
    function gc_abrir_modal_lancamento(tipo) {
        var titulo = tipo === 'receita' ? '<?php _e("Fazer Doação", "gestao-coletiva"); ?>' : '<?php _e("Registrar Despesa", "gestao-coletiva"); ?>';
        
        var html = '<h2>' + titulo + '</h2>';
        html += '<form id="gc-form-lancamento-publico">';
        html += '<input type="hidden" name="tipo" value="' + tipo + '">';
        html += '<p><label><?php _e("Descrição breve:", "gestao-coletiva"); ?><br>';
        html += '<input type="text" name="descricao_curta" required maxlength="255" style="width: 100%;"></label></p>';
        html += '<p><label><?php _e("Descrição detalhada:", "gestao-coletiva"); ?><br>';
        html += '<textarea name="descricao_detalhada" rows="4" style="width: 100%;"></textarea></label></p>';
        html += '<p><label><?php _e("Valor (R$):", "gestao-coletiva"); ?><br>';
        html += '<input type="number" name="valor" min="0.01" step="0.01" required class="gc-input-valor" style="width: 100%; font-size: 16px; padding: 10px;"></label></p>';
        
        if (tipo === 'receita') {
            html += '<div class="gc-instrucoes-pix">';
            html += '<h4><?php _e("Instruções para Doação via PIX", "gestao-coletiva"); ?></h4>';
            <?php if (!empty($chave_pix)): ?>
                html += '<p><?php _e("Para confirmar sua doação, realize a transferência via PIX usando as informações abaixo:", "gestao-coletiva"); ?></p>';
                html += '<div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px; padding: 15px; margin: 10px 0;">';
                html += '<p><strong><?php _e("Chave PIX:", "gestao-coletiva"); ?></strong> <span style="font-family: monospace; background: #f1f1f1; padding: 2px 6px; border-radius: 3px; cursor: pointer;" onclick="navigator.clipboard.writeText(this.textContent); alert(\'Chave PIX copiada!\')"><?php echo esc_js($chave_pix); ?></span></p>';
                <?php if (!empty($nome_beneficiario_pix)): ?>
                    html += '<p><strong><?php _e("Beneficiário:", "gestao-coletiva"); ?></strong> <?php echo esc_js($nome_beneficiario_pix); ?></p>';
                <?php endif; ?>
                html += '</div>';
            <?php else: ?>
                html += '<p style="color: #d63638; font-style: italic;"><?php _e("⚠️ Chave PIX não configurada. Entre em contato com a administração.", "gestao-coletiva"); ?></p>';
            <?php endif; ?>
            html += '<p><?php _e("Após fazer o PIX, seu lançamento ficará \'Previsto\' até ser confirmado pela administração.", "gestao-coletiva"); ?></p>';
            html += '</div>';
        }
        
        html += '<p><button type="submit" class="gc-btn gc-btn-primary">' + titulo + '</button></p>';
        html += '</form>';
        
        // Usar sistema de modal global se disponível
        if (typeof gc_abrir_modal === 'function') {
            gc_abrir_modal(html);
        } else {
            // Fallback para modal local
            $('#gc-modal-body').html(html);
            $('#gc-modal').show();
        }
    }
    
    function gc_abrir_modal_buscar() {
        var html = '<h2><?php _e("Consultar Lançamento", "gestao-coletiva"); ?></h2>';
        html += '<form id="gc-form-buscar-lancamento">';
        html += '<p><label><?php _e("Número do lançamento:", "gestao-coletiva"); ?><br>';
        html += '<input type="text" name="numero_lancamento" placeholder="GC2024000001" required style="width: 100%;"></label></p>';
        html += '<p><button type="submit" class="gc-btn gc-btn-primary"><?php _e("Consultar", "gestao-coletiva"); ?></button></p>';
        html += '</form>';
        html += '<div id="gc-resultado-busca"></div>';
        
        // Usar sistema de modal global se disponível
        if (typeof gc_abrir_modal === 'function') {
            gc_abrir_modal(html);
        } else {
            // Fallback para modal local
            $('#gc-modal-body').html(html);
            $('#gc-modal').show();
        }
    }
    
    // Handler para criar lançamento já existe no public.js global
    // Removido para evitar duplicação
});
</script>