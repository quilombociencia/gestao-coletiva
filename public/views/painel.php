<?php
if (!defined('ABSPATH')) {
    exit;
}

$mes_atual = date('Y-m');
$data_inicio = $mes_atual . '-01 00:00:00';
$data_fim = date('Y-m-t 23:59:59');

$balanco_mes = GC_Lancamento::calcular_saldo_periodo($data_inicio, $data_fim, true);
$saldo_atual = GC_Lancamento::calcular_saldo_periodo('2000-01-01', date('Y-m-d 23:59:59'));
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
            
            <?php if (is_user_logged_in() && (current_user_can('author') || current_user_can('manage_options'))): ?>
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
                <a href="<?php echo admin_url('admin.php?page=gc-relatorios'); ?>" class="gc-btn gc-btn-outline">
                    <?php _e('Ver Relatórios', 'gestao-coletiva'); ?>
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
    // Abrir modal para criar lançamento
    $('.gc-btn-acao, .gc-btn-contribuir').on('click', function() {
        var tipo = $(this).data('tipo');
        gc_abrir_modal_lancamento(tipo);
    });
    
    // Abrir modal para ver lançamento
    $('.gc-btn-ver-lancamento').on('click', function() {
        gc_abrir_modal_buscar();
    });
    
    // Fechar modal
    $('.gc-modal-close, .gc-modal').on('click', function(e) {
        if (e.target === this) {
            $('#gc-modal').hide();
        }
    });
    
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
        html += '<input type="number" name="valor" min="0.01" step="0.01" required style="width: 100%;"></label></p>';
        
        if (tipo === 'receita') {
            html += '<div class="gc-instrucoes-pix">';
            html += '<h4><?php _e("Instruções para Doação via PIX", "gestao-coletiva"); ?></h4>';
            html += '<p><?php _e("Chave PIX:", "gestao-coletiva"); ?> <strong>contato@exemplo.com</strong></p>';
            html += '<p><?php _e("Após fazer o PIX, seu lançamento ficará 'Previsto' até ser confirmado pela administração.", "gestao-coletiva"); ?></p>';
            html += '</div>';
        }
        
        html += '<p><button type="submit" class="gc-btn gc-btn-primary">' + titulo + '</button></p>';
        html += '</form>';
        
        $('#gc-modal-body').html(html);
        $('#gc-modal').show();
    }
    
    function gc_abrir_modal_buscar() {
        var html = '<h2><?php _e("Consultar Lançamento", "gestao-coletiva"); ?></h2>';
        html += '<form id="gc-form-buscar-lancamento">';
        html += '<p><label><?php _e("Número do lançamento:", "gestao-coletiva"); ?><br>';
        html += '<input type="text" name="numero_lancamento" placeholder="GC2024000001" required style="width: 100%;"></label></p>';
        html += '<p><button type="submit" class="gc-btn gc-btn-primary"><?php _e("Consultar", "gestao-coletiva"); ?></button></p>';
        html += '</form>';
        html += '<div id="gc-resultado-busca"></div>';
        
        $('#gc-modal-body').html(html);
        $('#gc-modal').show();
    }
    
    // Handler para criar lançamento
    $(document).on('submit', '#gc-form-lancamento-publico', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize() + '&action=gc_criar_lancamento&nonce=' + (typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : '');
        
        $.ajax({
            url: typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message + ' Número: #' + response.data.numero);
                    $('#gc-modal').hide();
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e("Erro ao processar solicitação", "gestao-coletiva"); ?>');
            }
        });
    });
});
</script>