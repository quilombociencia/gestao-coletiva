<?php
if (!$id) {
    wp_die(__('ID do lançamento não informado', 'gestao-coletiva'));
}

$lancamento = GC_Lancamento::obter($id);
if (!$lancamento) {
    wp_die(__('Lançamento não encontrado', 'gestao-coletiva'));
}

// Verificar permissões
if (!GC_Lancamento::pode_editar($id)) {
    wp_die(__('Você não tem permissão para editar este lançamento', 'gestao-coletiva'));
}

$autor = get_user_by('ID', $lancamento->autor_id);
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
            <?php _e('Editar Lançamento', 'gestao-coletiva'); ?> #<?php echo esc_html($lancamento->numero_unico); ?>
            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $id); ?>" class="page-title-action">
                <?php _e('Ver Detalhes', 'gestao-coletiva'); ?>
            </a>
        </h1>
    </div>
    
    <form id="gc-form-editar-lancamento" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('gc_editar_lancamento', 'gc_nonce'); ?>
        <input type="hidden" name="lancamento_id" value="<?php echo $id; ?>">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="numero_unico"><?php _e('Número Único', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <input type="text" id="numero_unico" value="<?php echo esc_attr($lancamento->numero_unico); ?>" disabled class="regular-text">
                    <p class="description"><?php _e('O número único não pode ser alterado.', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tipo"><?php _e('Tipo', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <select name="tipo" id="tipo" required>
                        <option value="receita" <?php selected($lancamento->tipo, 'receita'); ?>><?php _e('Receita', 'gestao-coletiva'); ?></option>
                        <option value="despesa" <?php selected($lancamento->tipo, 'despesa'); ?>><?php _e('Despesa', 'gestao-coletiva'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="descricao_curta"><?php _e('Descrição Curta', 'gestao-coletiva'); ?> *</label>
                </th>
                <td>
                    <input type="text" name="descricao_curta" id="descricao_curta" value="<?php echo esc_attr($lancamento->descricao_curta); ?>" required class="regular-text">
                    <p class="description"><?php _e('Descrição breve do lançamento (máximo 255 caracteres)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="descricao_detalhada"><?php _e('Descrição Detalhada', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <textarea name="descricao_detalhada" id="descricao_detalhada" rows="5" class="large-text"><?php echo esc_textarea($lancamento->descricao_detalhada); ?></textarea>
                    <p class="description"><?php _e('Descrição completa do lançamento (opcional)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="valor"><?php _e('Valor', 'gestao-coletiva'); ?> *</label>
                </th>
                <td>
                    <input type="number" name="valor" id="valor" value="<?php echo esc_attr($lancamento->valor); ?>" step="0.01" min="0" required class="gc-input-valor">
                    <p class="description"><?php _e('Valor em reais (R$)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="recorrencia"><?php _e('Recorrência', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <select name="recorrencia" id="recorrencia">
                        <option value="unica" <?php selected($lancamento->recorrencia, 'unica'); ?>><?php _e('Única', 'gestao-coletiva'); ?></option>
                        <option value="mensal" <?php selected($lancamento->recorrencia, 'mensal'); ?>><?php _e('Mensal', 'gestao-coletiva'); ?></option>
                        <option value="trimestral" <?php selected($lancamento->recorrencia, 'trimestral'); ?>><?php _e('Trimestral', 'gestao-coletiva'); ?></option>
                        <option value="anual" <?php selected($lancamento->recorrencia, 'anual'); ?>><?php _e('Anual', 'gestao-coletiva'); ?></option>
                    </select>
                    <?php if ($lancamento->recorrencia !== 'unica'): ?>
                        <p class="description">
                            <strong><?php _e('Atenção:', 'gestao-coletiva'); ?></strong>
                            <?php _e('Alterar a recorrência afetará apenas este lançamento específico, não a série completa.', 'gestao-coletiva'); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Anexos existentes -->
            <?php if (!empty($lancamento->anexos) && is_array($lancamento->anexos) && count($lancamento->anexos) > 0): ?>
            <tr>
                <th scope="row">
                    <label><?php _e('Anexos Existentes', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <div class="gc-anexos-existentes">
                        <?php foreach ($lancamento->anexos as $index => $anexo): ?>
                            <div class="gc-anexo-existente">
                                <a href="<?php echo esc_url($anexo); ?>" target="_blank">
                                    <?php printf(__('Anexo %d', 'gestao-coletiva'), $index + 1); ?>
                                </a>
                                <label>
                                    <input type="checkbox" name="remover_anexos[]" value="<?php echo $index; ?>">
                                    <?php _e('Remover', 'gestao-coletiva'); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php _e('Marque os anexos que deseja remover.', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
            
            <!-- Novos anexos -->
            <tr>
                <th scope="row">
                    <label for="novos_anexos"><?php _e('Adicionar Novos Anexos', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <input type="file" name="novos_anexos[]" id="novos_anexos" multiple accept=".pdf,.jpg,.jpeg,.png,.gif">
                    <p class="description"><?php _e('Selecione novos arquivos para anexar (PDF, JPG, PNG - múltiplos arquivos permitidos)', 'gestao-coletiva'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label><?php _e('Informações do Lançamento', 'gestao-coletiva'); ?></label>
                </th>
                <td>
                    <p><strong><?php _e('Estado atual:', 'gestao-coletiva'); ?></strong> 
                        <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                        </span>
                    </p>
                    <p><strong><?php _e('Autor:', 'gestao-coletiva'); ?></strong> <?php echo $autor ? esc_html($autor->display_name) : __('Usuário removido', 'gestao-coletiva'); ?></p>
                    <p><strong><?php _e('Data de criação:', 'gestao-coletiva'); ?></strong> <?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?></p>
                    <?php if ($lancamento->prazo_atual): ?>
                    <p><strong><?php _e('Prazo atual:', 'gestao-coletiva'); ?></strong> <?php echo date('d/m/Y H:i', strtotime($lancamento->prazo_atual)); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <div class="gc-form-actions">
            <?php submit_button(__('Atualizar Lançamento', 'gestao-coletiva'), 'primary', 'submit-edicao', false, ['id' => 'submit-edicao']); ?>
            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $id); ?>" class="button">
                <?php _e('Cancelar', 'gestao-coletiva'); ?>
            </a>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Submissão do formulário
    $('#gc-form-editar-lancamento').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'gc_editar_lancamento');
        formData.append('nonce', '<?php echo wp_create_nonce('gc_nonce'); ?>');
        
        $('#submit-edicao').prop('disabled', true).val('<?php _e("Salvando...", "gestao-coletiva"); ?>');
        
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
                    $('#submit-edicao').prop('disabled', false).val('<?php _e("Atualizar Lançamento", "gestao-coletiva"); ?>');
                }
            },
            error: function() {
                alert('<?php _e("Erro ao processar solicitação", "gestao-coletiva"); ?>');
                $('#submit-edicao').prop('disabled', false).val('<?php _e("Atualizar Lançamento", "gestao-coletiva"); ?>');
            }
        });
    });
    
    // Confirmar remoção de anexos
    $('input[name="remover_anexos[]"]').on('change', function() {
        if ($(this).is(':checked')) {
            if (!confirm('<?php _e("Tem certeza que deseja remover este anexo?", "gestao-coletiva"); ?>')) {
                $(this).prop('checked', false);
            }
        }
    });
});
</script>

<style>
.gc-anexos-existentes {
    margin-bottom: 15px;
}

.gc-anexo-existente {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #f9f9f9;
}

.gc-anexo-existente a {
    font-weight: 500;
    text-decoration: none;
}

.gc-anexo-existente label {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #d63384;
    cursor: pointer;
}

.gc-form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e5e5;
}

.gc-form-actions .button {
    margin-right: 10px;
}
</style>