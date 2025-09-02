<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['gc_action']) ? sanitize_text_field($_GET['gc_action']) : '';
$numero = isset($_GET['numero']) ? sanitize_text_field($_GET['numero']) : '';

if ($action === 'ver' && !empty($numero)) {
    $lancamento = GC_Lancamento::obter_por_numero($numero);
    if ($lancamento) {
        $contador_contestacoes = GC_Lancamento::contar_contestacoes($lancamento->id);
        $deve_mostrar_autoria = gc_deve_mostrar_autoria_publica($lancamento);
        $autor = $deve_mostrar_autoria ? get_user_by('ID', $lancamento->autor_id) : null;
    }
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
                        <?php echo esc_html(gc_estado_para_texto($lancamento->estado)); ?>
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
                        
                        <div class="gc-info-item">
                            <label><?php _e('Autor:', 'gestao-coletiva'); ?></label>
                            <span>
                                <?php if ($deve_mostrar_autoria && $autor): ?>
                                    <?php echo esc_html($autor->display_name); ?>
                                <?php elseif ($deve_mostrar_autoria): ?>
                                    <em><?php _e('Usuário removido', 'gestao-coletiva'); ?></em>
                                <?php else: ?>
                                    <em><?php _e('Anônimo', 'gestao-coletiva'); ?></em>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($lancamento->data_efetivacao): ?>
                        <div class="gc-info-item">
                            <label><?php _e('Data de Efetivação:', 'gestao-coletiva'); ?></label>
                            <span><?php echo date('d/m/Y H:i', strtotime($lancamento->data_efetivacao)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="gc-info-item">
                            <label><?php _e('Recorrência:', 'gestao-coletiva'); ?></label>
                            <span>
                                <?php echo esc_html(ucfirst($lancamento->recorrencia)); ?>
                                <?php if ($lancamento->recorrencia !== 'unica'): ?>
                                    <?php if ($lancamento->recorrencia_ativa): ?>
                                        <span class="gc-badge gc-badge-success"><?php _e('Ativa', 'gestao-coletiva'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-badge gc-badge-error"><?php _e('Cancelada', 'gestao-coletiva'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($lancamento->data_proxima_recorrencia && $lancamento->recorrencia_ativa): ?>
                        <div class="gc-info-item">
                            <label><?php _e('Próxima Recorrência:', 'gestao-coletiva'); ?></label>
                            <span><?php echo date('d/m/Y H:i', strtotime($lancamento->data_proxima_recorrencia)); ?></span>
                        </div>
                        <?php endif; ?>
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
                    $user_id = get_current_user_id();
                    $eh_autor = ($lancamento->autor_id == $user_id);
                    $eh_admin = current_user_can('manage_options');
                    $pode_gerar = in_array($lancamento->estado, $estados_certificado) && 
                                $lancamento->tipo === 'receita' && 
                                ($eh_autor || $eh_admin);
                    if ($pode_gerar): 
                    ?>
                        <button type="button" class="gc-btn gc-btn-primary gc-gerar-certificado" data-id="<?php echo $lancamento->id; ?>">
                            <span class="gc-icon">🏆</span>
                            <?php _e('Gerar Certificado', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <!-- Ações de Recorrência -->
                    <?php if ($lancamento->recorrencia !== 'unica' && ($eh_autor || $eh_admin)): ?>
                        <?php if ($lancamento->recorrencia_ativa): ?>
                            <button type="button" class="gc-btn gc-btn-secondary gc-cancelar-recorrencia" data-id="<?php echo $lancamento->id; ?>">
                                <?php _e('Cancelar Recorrência', 'gestao-coletiva'); ?>
                            </button>
                        <?php endif; ?>
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
                    
                    <?php if ($contador_contestacoes > 0): ?>
                        <button type="button" class="gc-btn gc-btn-outline gc-ver-contestacoes-publico" data-id="<?php echo $lancamento->id; ?>">
                            <span class="gc-icon">📋</span>
                            <?php _e('Ver Contestações', 'gestao-coletiva'); ?> (<?php echo $contador_contestacoes; ?>)
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($lancamento->anexos) && is_array($lancamento->anexos) && count($lancamento->anexos) > 0): ?>
                        <button type="button" class="gc-btn gc-btn-outline gc-ver-anexos" data-anexos='<?php echo json_encode($lancamento->anexos); ?>'>
                            <span class="gc-icon">📎</span>
                            <?php _e('Ver Anexos', 'gestao-coletiva'); ?>
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
                                <?php echo esc_html(gc_estado_para_texto($lanc->estado)); ?>
                            </span>
                        </div>
                        <div class="gc-item-valor">
                            <span class="gc-valor <?php echo ($lanc->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                R$ <?php echo number_format($lanc->valor, 2, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="gc-item-acoes">
                            <button type="button" class="gc-btn gc-btn-small gc-ver-lancamento" data-numero="<?php echo esc_attr($lanc->numero_unico); ?>">
                                <?php _e('Ver', 'gestao-coletiva'); ?>
                            </button>
                            <?php 
                            $contador_lanc = GC_Lancamento::contar_contestacoes($lanc->id);
                            if ($contador_lanc > 0): 
                            ?>
                                <button type="button" class="gc-btn gc-btn-small gc-ver-contestacoes-publico" data-id="<?php echo $lanc->id; ?>" style="margin-left: 5px;">
                                    <?php _e('Contestações', 'gestao-coletiva'); ?> (<?php echo $contador_lanc; ?>)
                                </button>
                            <?php endif; ?>
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
            var currentUrl = window.location.href.split('?')[0];
            window.location.href = currentUrl + '?gc_action=ver&numero=' + encodeURIComponent(numero);
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
    
    // Ver contestações de um lançamento (público)
    $(document).on('click', '.gc-ver-contestacoes-publico', function() {
        var lancamentoId = $(this).data('id');
        gc_abrir_modal_lista_contestacoes_publico(lancamentoId);
    });
    
    function gc_criar_lancamento(tipo) {
        // Abrir modal para criar lançamento em vez de redirecionar
        gc_abrir_modal_lancamento_simples(tipo);
    }
    
    // Sistema de modal simples para views públicas
    function gc_abrir_modal_publico(content) {
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
    }
    
    function gc_fechar_modal_publico() {
        $('.gc-modal-overlay-publico').remove();
        $(document).off('keydown.gc-modal');
    }
    
    function gc_abrir_modal_lista_contestacoes_publico(lancamentoId) {
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
    }
});
</script>