<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['gc_action']) ? sanitize_text_field($_GET['gc_action']) : '';
$numero = isset($_GET['numero']) ? sanitize_text_field($_GET['numero']) : '';

if ($action === 'ver' && !empty($numero)) {
    $lancamento = GC_Lancamento::obter_por_numero($numero);
}
?>

<div id="gc-lancamentos-publico" class="gc-container">
    <?php if ($action === 'ver' && $lancamento): ?>
        <!-- Visualizar lançamento específico -->
        <div class="gc-lancamento-detalhes">
            <div class="gc-card">
                <div class="gc-lancamento-header">
                    <h2><?php _e('Lançamento', 'gestao-coletiva'); ?> #<?php echo esc_html($lancamento->numero_unico); ?></h2>
                    <span class="gc-badge gc-badge-large gc-estado-<?php echo $lancamento->estado; ?>">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                    </span>
                </div>
                
                <div class="gc-lancamento-info">
                    <div class="gc-info-grid">
                        <div class="gc-info-item">
                            <label><?php _e('Tipo:', 'gestao-coletiva'); ?></label>
                            <span class="gc-tipo-<?php echo $lancamento->tipo; ?>">
                                <?php echo ($lancamento->tipo == 'receita') ? __('Doação', 'gestao-coletiva') : __('Despesa', 'gestao-coletiva'); ?>
                            </span>
                        </div>
                        
                        <div class="gc-info-item">
                            <label><?php _e('Valor:', 'gestao-coletiva'); ?></label>
                            <span class="gc-valor gc-valor-grande <?php echo ($lancamento->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                            </span>
                        </div>
                        
                        <div class="gc-info-item">
                            <label><?php _e('Data de Criação:', 'gestao-coletiva'); ?></label>
                            <span><?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?></span>
                        </div>
                        
                        <?php if ($lancamento->data_efetivacao): ?>
                        <div class="gc-info-item">
                            <label><?php _e('Data de Efetivação:', 'gestao-coletiva'); ?></label>
                            <span><?php echo date('d/m/Y H:i', strtotime($lancamento->data_efetivacao)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="gc-info-item">
                            <label><?php _e('Recorrência:', 'gestao-coletiva'); ?></label>
                            <span><?php echo esc_html(ucfirst($lancamento->recorrencia)); ?></span>
                        </div>
                    </div>
                    
                    <div class="gc-descricoes">
                        <div class="gc-descricao">
                            <label><?php _e('Descrição:', 'gestao-coletiva'); ?></label>
                            <p><?php echo esc_html($lancamento->descricao_curta); ?></p>
                        </div>
                        
                        <?php if (!empty($lancamento->descricao_detalhada)): ?>
                        <div class="gc-descricao">
                            <label><?php _e('Detalhes:', 'gestao-coletiva'); ?></label>
                            <p><?php echo esc_html($lancamento->descricao_detalhada); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informações PIX para doações -->
                <?php if ($lancamento->tipo === 'receita'): ?>
                    <?php 
                    $chave_pix = GC_Database::get_setting('chave_pix');
                    $nome_beneficiario = GC_Database::get_setting('nome_beneficiario_pix');
                    if (!empty($chave_pix)): 
                    ?>
                    <div class="gc-pix-container">
                        <div class="gc-pix-info">
                            <h4><?php _e('🏦 Informações para Doação via PIX', 'gestao-coletiva'); ?></h4>
                            <div class="gc-pix-dados">
                                <div class="gc-pix-item">
                                    <label><?php _e('Chave PIX:', 'gestao-coletiva'); ?></label>
                                    <span class="gc-chave-pix" onclick="navigator.clipboard.writeText(this.textContent); alert('Chave PIX copiada!')" style="cursor: pointer; background: #f1f1f1; padding: 5px 8px; border-radius: 3px;"><?php echo esc_html($chave_pix); ?></span>
                                </div>
                                <?php if (!empty($nome_beneficiario)): ?>
                                <div class="gc-pix-item">
                                    <label><?php _e('Beneficiário:', 'gestao-coletiva'); ?></label>
                                    <span><?php echo esc_html($nome_beneficiario); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="gc-pix-item">
                                    <label><?php _e('Valor:', 'gestao-coletiva'); ?></label>
                                    <span class="gc-valor gc-positivo">R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></span>
                                </div>
                            </div>
                            <p class="gc-pix-instrucoes">
                                <?php _e('💡 Clique na chave PIX para copiar automaticamente. Após realizar a transferência, aguarde a confirmação do recebimento.', 'gestao-coletiva'); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Contador de prazo -->
                <?php if (!in_array($lancamento->estado, ['efetivado', 'cancelado', 'aceito'])): ?>
                <div class="gc-prazo-container">
                    <div class="gc-prazo-info">
                        <label><?php _e('Prazo atual:', 'gestao-coletiva'); ?></label>
                        <span class="gc-prazo-tipo"><?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->tipo_prazo))); ?></span>
                        <div class="gc-contador-prazo" data-prazo="<?php echo date('Y-m-d H:i:s', strtotime($lancamento->prazo_atual)); ?>">
                            <span class="gc-tempo-restante"></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Ações disponíveis -->
                <div class="gc-acoes-lancamento">
                    <?php 
                    // Estados que permitem gerar certificado
                    $estados_certificado = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
                    if (in_array($lancamento->estado, $estados_certificado) && $lancamento->tipo === 'receita'): 
                    ?>
                        <button type="button" class="gc-btn gc-btn-primary gc-gerar-certificado" data-id="<?php echo $lancamento->id; ?>">
                            <span class="gc-icon">🏆</span>
                            <?php _e('Gerar Certificado', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php 
                    // Estados que podem ser contestados (valores já efetivos mas questionáveis)
                    $estados_contestaveis = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
                    if (is_user_logged_in() && in_array($lancamento->estado, $estados_contestaveis)): 
                    ?>
                        <button type="button" class="gc-btn gc-btn-outline gc-abrir-contestacao" data-id="<?php echo $lancamento->id; ?>">
                            <span class="gc-icon">⚠️</span>
                            <?php _e('Contestar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (GC_Lancamento::pode_editar($lancamento->id)): ?>
                        <button type="button" class="gc-btn gc-btn-outline gc-editar-lancamento" data-id="<?php echo $lancamento->id; ?>">
                            <span class="gc-icon">✏️</span>
                            <?php _e('Editar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Interface para buscar/criar lançamentos -->
        <div class="gc-lancamentos-interface">
            <div class="gc-header">
                <h2><?php _e('Lançamentos', 'gestao-coletiva'); ?></h2>
                <p><?php _e('Registre doações, consulte lançamentos existentes ou gerencie suas contribuições.', 'gestao-coletiva'); ?></p>
            </div>
            
            <!-- Opções principais -->
            <div class="gc-opcoes-principais">
                <div class="gc-opcao-card gc-opcao-fundos">
                    <div class="gc-opcao-icon">💰</div>
                    <h3><?php _e('Incluir Fundos', 'gestao-coletiva'); ?></h3>
                    <p><?php _e('Registre uma doação pontual ou recorrente via PIX', 'gestao-coletiva'); ?></p>
                    <button type="button" class="gc-btn gc-btn-primary gc-btn-incluir-fundos">
                        <?php _e('Fazer Doação', 'gestao-coletiva'); ?>
                    </button>
                </div>
                
                <?php if (is_user_logged_in() && current_user_can('manage_options')): ?>
                <div class="gc-opcao-card gc-opcao-despesa">
                    <div class="gc-opcao-icon">📝</div>
                    <h3><?php _e('Incluir Despesa', 'gestao-coletiva'); ?></h3>
                    <p><?php _e('Registre um gasto do projeto com comprovantes', 'gestao-coletiva'); ?></p>
                    <button type="button" class="gc-btn gc-btn-primary gc-btn-incluir-despesa">
                        <?php _e('Registrar Gasto', 'gestao-coletiva'); ?>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="gc-opcao-card gc-opcao-consulta">
                    <div class="gc-opcao-icon">🔍</div>
                    <h3><?php _e('Ver Lançamento', 'gestao-coletiva'); ?></h3>
                    <p><?php _e('Consulte o status de um lançamento pelo número único', 'gestao-coletiva'); ?></p>
                    <div class="gc-busca-lancamento">
                        <form id="gc-form-buscar">
                            <input type="text" 
                                   id="numero-lancamento" 
                                   name="numero" 
                                   placeholder="<?php _e('Digite o número (ex: GC2024000001)', 'gestao-coletiva'); ?>" 
                                   required>
                            <button type="submit" class="gc-btn gc-btn-primary">
                                <?php _e('Buscar', 'gestao-coletiva'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Meus lançamentos (se logado) -->
            <?php if (is_user_logged_in()): 
                $meus_lancamentos = GC_Lancamento::listar(array(
                    'autor_id' => get_current_user_id(),
                    'limit' => 10,
                    'order' => 'data_criacao DESC'
                ));
                
                if (!empty($meus_lancamentos)):
            ?>
            <div class="gc-meus-lancamentos">
                <h3><?php _e('Meus Lançamentos', 'gestao-coletiva'); ?></h3>
                <div class="gc-lancamentos-lista">
                    <?php foreach ($meus_lancamentos as $lanc): ?>
                    <div class="gc-lancamento-item">
                        <div class="gc-item-info">
                            <strong>#<?php echo esc_html($lanc->numero_unico); ?></strong>
                            <span class="gc-item-descricao"><?php echo esc_html($lanc->descricao_curta); ?></span>
                            <span class="gc-badge gc-estado-<?php echo $lanc->estado; ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $lanc->estado))); ?>
                            </span>
                        </div>
                        <div class="gc-item-valor">
                            <span class="gc-valor <?php echo ($lanc->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                R$ <?php echo number_format($lanc->valor, 2, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="gc-item-acoes">
                            <a href="?gc_action=ver&numero=<?php echo esc_attr($lanc->numero_unico); ?>" class="gc-btn gc-btn-small">
                                <?php _e('Ver', 'gestao-coletiva'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Buscar lançamento
    $('#gc-form-buscar').on('submit', function(e) {
        e.preventDefault();
        var numero = $('#numero-lancamento').val();
        if (numero) {
            window.location.href = '?gc_action=ver&numero=' + encodeURIComponent(numero);
        }
    });
    
    // Incluir fundos
    $('.gc-btn-incluir-fundos').on('click', function() {
        // Redirecionar ou abrir modal para criar lançamento tipo receita
        gc_criar_lancamento('receita');
    });
    
    // Incluir despesa
    $('.gc-btn-incluir-despesa').on('click', function() {
        // Redirecionar ou abrir modal para criar lançamento tipo despesa  
        gc_criar_lancamento('despesa');
    });
    
    // Atualizar contador de prazo
    if ($('.gc-contador-prazo').length) {
        function atualizarContador() {
            $('.gc-contador-prazo').each(function() {
                var prazo = new Date($(this).data('prazo')).getTime();
                var agora = new Date().getTime();
                var diferenca = prazo - agora;
                
                if (diferenca > 0) {
                    var dias = Math.floor(diferenca / (1000 * 60 * 60 * 24));
                    var horas = Math.floor((diferenca % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutos = Math.floor((diferenca % (1000 * 60 * 60)) / (1000 * 60));
                    
                    var texto = '';
                    if (dias > 0) texto += dias + 'd ';
                    if (horas > 0) texto += horas + 'h ';
                    texto += minutos + 'min';
                    
                    $(this).find('.gc-tempo-restante').text(texto).removeClass('gc-vencido');
                } else {
                    $(this).find('.gc-tempo-restante').text('<?php _e("Vencido", "gestao-coletiva"); ?>').addClass('gc-vencido');
                }
            });
        }
        
        atualizarContador();
        setInterval(atualizarContador, 60000); // Atualizar a cada minuto
    }
    
    function gc_criar_lancamento(tipo) {
        // Implementar modal ou redirecionamento para criar lançamento
        alert('<?php _e("Funcionalidade de criar lançamento em desenvolvimento", "gestao-coletiva"); ?>');
    }
});
</script>