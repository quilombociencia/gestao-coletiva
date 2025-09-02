<?php
$tipo_default = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'receita';
?>

<div class="wrap">
    <h1><?php _e('Criar Lançamento', 'gestao-coletiva'); ?></h1>
    
    <form id="gc-form-lancamento" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('gc_nonce', 'gc_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tipo"><?php _e('Tipo', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <select name="tipo" id="tipo" required>
                        <option value="receita" <?php selected($tipo_default, 'receita'); ?>>
                            <?php _e('Inclusão de Fundos (Receita)', 'gestao-coletiva'); ?>
                        </option>
                        <option value="despesa" <?php selected($tipo_default, 'despesa'); ?>>
                            <?php _e('Inclusão de Despesa', 'gestao-coletiva'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="descricao_curta"><?php _e('Descrição Curta', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <input type="text" name="descricao_curta" id="descricao_curta" class="regular-text" required maxlength="255">
                    <p class="description"><?php _e('Breve descrição do lançamento (máximo 255 caracteres)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="descricao_detalhada"><?php _e('Descrição Detalhada', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <textarea name="descricao_detalhada" id="descricao_detalhada" rows="5" cols="50" class="large-text"></textarea>
                    <p class="description"><?php _e('Descrição completa e detalhada do lançamento', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="valor"><?php _e('Valor (R$)', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <input type="number" name="valor" id="valor" class="regular-text" min="0.01" step="0.01" required>
                    <p class="description"><?php _e('Valor em reais (use ponto como separador decimal)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr id="tr-doacao-anonima" style="display: none;">
                <th scope="row">
                    <label for="doacao_anonima"><?php _e('Doação Anônima', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="doacao_anonima" id="doacao_anonima" value="1">
                        <?php _e('Não mostrar meu nome publicamente nesta doação', 'gestao-coletiva'); ?>
                    </label>
                    <div id="aviso-limite-anonimo" class="notice notice-info" style="display: none; margin-top: 10px; padding: 10px;">
                        <p id="texto-aviso-limite"></p>
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="recorrencia"><?php _e('Recorrência', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <select name="recorrencia" id="recorrencia">
                        <option value="unica"><?php _e('Única', 'gestao-coletiva'); ?></option>
                        <option value="mensal"><?php _e('Mensal', 'gestao-coletiva'); ?></option>
                        <option value="trimestral"><?php _e('Trimestral', 'gestao-coletiva'); ?></option>
                        <option value="anual"><?php _e('Anual', 'gestao-coletiva'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr id="tr-anexos" style="display: none;">
                <th scope="row">
                    <label for="anexos"><?php _e('Comprovantes', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <input type="file" name="anexos[]" id="anexos" multiple accept=".pdf,.jpg,.jpeg,.png,.gif">
                    <p class="description"><?php _e('Anexe comprovantes da despesa (PDF, JPG, PNG - múltiplos arquivos permitidos)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- Instruções para doações -->
        <div id="instrucoes-doacao" style="display: none;">
            <h3><?php _e('Instruções para Doação', 'gestao-coletiva'); ?></h3>
            <div class="gc-instrucoes-pix">
                <h4><?php _e('Doação via PIX', 'gestao-coletiva'); ?></h4>
                <p><?php _e('Para confirmar sua doação, realize a transferência via PIX usando as informações abaixo:', 'gestao-coletiva'); ?></p>
                
                <div class="gc-pix-info">
                    <?php 
                    $chave_pix = GC_Database::get_setting('chave_pix');
                    $nome_beneficiario = GC_Database::get_setting('nome_beneficiario_pix');
                    ?>
                    <?php if (!empty($chave_pix)): ?>
                        <p><strong><?php _e('Chave PIX:', 'gestao-coletiva'); ?></strong> <span style="font-family: monospace; background: #f1f1f1; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($chave_pix); ?></span></p>
                        <?php if (!empty($nome_beneficiario)): ?>
                            <p><strong><?php _e('Beneficiário:', 'gestao-coletiva'); ?></strong> <?php echo esc_html($nome_beneficiario); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #d63638; font-style: italic;">
                            <?php _e('⚠️ Chave PIX não configurada. Configure em Gestão Coletiva → Configurações.', 'gestao-coletiva'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <p class="gc-aviso">
                    <?php _e('Após realizar o PIX, seu lançamento ficará com status "Previsto" até que seja verificado pela administração e alterado para "Efetivado".', 'gestao-coletiva'); ?>
                </p>
            </div>
        </div>
        
        <?php submit_button(__('Criar Lançamento', 'gestao-coletiva'), 'primary', 'submit', true, array('id' => 'submit-lancamento')); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Mostrar/ocultar campos baseado no tipo
    $('#tipo').on('change', function() {
        var tipo = $(this).val();
        
        if (tipo === 'despesa') {
            $('#tr-anexos').show();
            $('#tr-doacao-anonima').hide();
            $('#instrucoes-doacao').hide();
        } else {
            $('#tr-anexos').hide();
            $('#tr-doacao-anonima').show();
            $('#instrucoes-doacao').show();
            verificarLimiteAnonimo();
        }
    }).trigger('change');
    
    // Verificar limite de doação anônima quando valor mudar
    $('#valor').on('input change', function() {
        if ($('#tipo').val() === 'receita') {
            verificarLimiteAnonimo();
        }
    });
    
    function verificarLimiteAnonimo() {
        var valor = parseFloat($('#valor').val()) || 0;
        
        if (valor <= 0) {
            $('#aviso-limite-anonimo').hide();
            $('#doacao_anonima').prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_verificar_limite_anonimo',
                valor: valor,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var dados = response.data;
                    var $checkbox = $('#doacao_anonima');
                    var $aviso = $('#aviso-limite-anonimo');
                    var $textoAviso = $('#texto-aviso-limite');
                    
                    if (!dados.pode_anonimo) {
                        $checkbox.prop('disabled', true).prop('checked', false);
                        $aviso.show().removeClass('notice-info').addClass('notice-warning');
                        
                        if (dados.excede_limite_valor) {
                            $textoAviso.html('<strong>⚠️ Doação será identificada publicamente</strong><br>' +
                                'Doações acima de R$ ' + parseFloat(dados.valor_maximo).toFixed(2).replace('.', ',') + ' têm identificação obrigatória.');
                        } else if (dados.excede_limite_mensal) {
                            $textoAviso.html('<strong>⚠️ Limite mensal de doações anônimas excedido</strong><br>' +
                                'Você já doou R$ ' + parseFloat(dados.total_mes).toFixed(2).replace('.', ',') + ' anonimamente este mês. ' +
                                'Limite mensal: R$ ' + parseFloat(dados.valor_maximo).toFixed(2).replace('.', ',') + '.');
                        }
                    } else {
                        $checkbox.prop('disabled', false);
                        $aviso.show().removeClass('notice-warning').addClass('notice-info');
                        $textoAviso.html('<strong>ℹ️ Doação pode ser anônima</strong><br>' +
                            'Você pode doar R$ ' + parseFloat(dados.limite_restante).toFixed(2).replace('.', ',') + ' anonimamente ainda este mês.');
                    }
                }
            }
        });
    }
    
    // Submissão do formulário
    $('#gc-form-lancamento').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'gc_criar_lancamento');
        formData.append('nonce', gc_ajax.nonce);
        
        $('#submit-lancamento').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '<?php echo admin_url("admin.php?page=gc-lancamentos&action=ver&id="); ?>' + response.data.id;
                } else {
                    alert('<?php _e("Erro:", "gestao-coletiva"); ?> ' + response.data);
                    $('#submit-lancamento').prop('disabled', false);
                }
            },
            error: function() {
                alert('<?php _e("Erro ao processar solicitação", "gestao-coletiva"); ?>');
                $('#submit-lancamento').prop('disabled', false);
            }
        });
    });
});
</script>