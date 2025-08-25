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
                    <input type="number" name="valor" id="valor" class="small-text" min="0.01" step="0.01" required>
                    <p class="description"><?php _e('Valor em reais (use ponto como separador decimal)', 'gestao-coletiva'); ?></p>
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
                    <p><strong><?php _e('Chave PIX:', 'gestao-coletiva'); ?></strong> contato@exemplo.com</p>
                    <p><strong><?php _e('Beneficiário:', 'gestao-coletiva'); ?></strong> Nome do Projeto</p>
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
            $('#instrucoes-doacao').hide();
        } else {
            $('#tr-anexos').hide();
            $('#instrucoes-doacao').show();
        }
    }).trigger('change');
    
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