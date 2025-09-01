<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'gestao-coletiva'));
}

$configuracoes = array(
    'logo_url' => GC_Database::get_setting('logo_url'),
    'prazo_efetivacao_horas' => GC_Database::get_setting('prazo_efetivacao_horas'),
    'prazo_resposta_contestacao_horas' => GC_Database::get_setting('prazo_resposta_contestacao_horas'),
    'prazo_analise_resposta_horas' => GC_Database::get_setting('prazo_analise_resposta_horas'),
    'prazo_publicacao_disputa_horas' => GC_Database::get_setting('prazo_publicacao_disputa_horas'),
    'prazo_resolucao_disputa_dias' => GC_Database::get_setting('prazo_resolucao_disputa_dias'),
    'texto_agradecimento_certificado' => GC_Database::get_setting('texto_agradecimento_certificado'),
    'chave_pix' => GC_Database::get_setting('chave_pix'),
    'nome_beneficiario_pix' => GC_Database::get_setting('nome_beneficiario_pix')
);
?>

<div class="wrap">
    <h1><?php _e('Configurações - Gestão Coletiva', 'gestao-coletiva'); ?></h1>
    
    <form id="gc-form-configuracoes" method="post">
        <?php wp_nonce_field('gc_nonce', 'gc_nonce'); ?>
        
        <h2><?php _e('Identidade Visual', 'gestao-coletiva'); ?></h2>
        <p class="description">
            <?php _e('Configure a aparência e identidade visual do sistema.', 'gestao-coletiva'); ?>
        </p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="logo_url">
                        <?php _e('Logo da Organização', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <div class="gc-logo-upload">
                        <div class="gc-logo-preview">
                            <?php if (!empty($configuracoes['logo_url'])): ?>
                                <img src="<?php echo esc_url($configuracoes['logo_url']); ?>" alt="Logo atual" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 10px;">
                            <?php else: ?>
                                <div class="gc-no-logo" style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;">
                                    <?php _e('Nenhum logo selecionado', 'gestao-coletiva'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="gc-logo-actions" style="margin-top: 10px;">
                            <input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr($configuracoes['logo_url']); ?>">
                            <button type="button" id="btn-selecionar-logo" class="button">
                                <?php _e('Selecionar Logo', 'gestao-coletiva'); ?>
                            </button>
                            <?php if (!empty($configuracoes['logo_url'])): ?>
                                <button type="button" id="btn-remover-logo" class="button">
                                    <?php _e('Remover Logo', 'gestao-coletiva'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <p class="description">
                            <?php _e('Recomendado: PNG ou JPG, máximo 300x150px. O logo será usado nos certificados e cabeçalhos administrativos.', 'gestao-coletiva'); ?>
                        </p>
                    </div>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Prazos do Sistema', 'gestao-coletiva'); ?></h2>
        <p class="description">
            <?php _e('Configure os prazos em horas ou dias para cada etapa do processo de gestão coletiva.', 'gestao-coletiva'); ?>
        </p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="prazo_efetivacao_horas">
                        <?php _e('Prazo para Efetivação (horas)', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           name="prazo_efetivacao_horas" 
                           id="prazo_efetivacao_horas" 
                           value="<?php echo esc_attr($configuracoes['prazo_efetivacao_horas']); ?>" 
                           min="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Tempo para um administrador conferir o crédito em conta ou os recibos de despesa.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="prazo_resposta_contestacao_horas">
                        <?php _e('Prazo para Resposta a Contestações (horas)', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           name="prazo_resposta_contestacao_horas" 
                           id="prazo_resposta_contestacao_horas" 
                           value="<?php echo esc_attr($configuracoes['prazo_resposta_contestacao_horas']); ?>" 
                           min="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Tempo para que o autor do lançamento ou administrador responda às contestações.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="prazo_analise_resposta_horas">
                        <?php _e('Prazo para Análise de Resposta (horas)', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           name="prazo_analise_resposta_horas" 
                           id="prazo_analise_resposta_horas" 
                           value="<?php echo esc_attr($configuracoes['prazo_analise_resposta_horas']); ?>" 
                           min="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Tempo para que o autor da contestação analise a resposta recebida.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="prazo_publicacao_disputa_horas">
                        <?php _e('Prazo para Publicação de Disputas (horas)', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           name="prazo_publicacao_disputa_horas" 
                           id="prazo_publicacao_disputa_horas" 
                           value="<?php echo esc_attr($configuracoes['prazo_publicacao_disputa_horas']); ?>" 
                           min="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Tempo para publicar um texto no blog com detalhamento da disputa.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="prazo_resolucao_disputa_dias">
                        <?php _e('Prazo para Resolução de Disputas (dias)', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           name="prazo_resolucao_disputa_dias" 
                           id="prazo_resolucao_disputa_dias" 
                           value="<?php echo esc_attr($configuracoes['prazo_resolucao_disputa_dias']); ?>" 
                           min="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Tempo em dias para votação da comunidade sobre disputas publicadas.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Textos e Mensagens', 'gestao-coletiva'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="texto_agradecimento_certificado">
                        <?php _e('Texto de Agradecimento para Certificados', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <textarea name="texto_agradecimento_certificado" 
                              id="texto_agradecimento_certificado" 
                              rows="3" 
                              cols="50" 
                              class="large-text"><?php echo esc_textarea($configuracoes['texto_agradecimento_certificado']); ?></textarea>
                    <p class="description">
                        <?php _e('Texto que aparecerá nos certificados de doação para agradecer os contribuidores.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Configurações PIX', 'gestao-coletiva'); ?></h2>
        <p class="description">
            <?php _e('Configure a chave PIX para facilitar as doações ao projeto.', 'gestao-coletiva'); ?>
        </p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="chave_pix">
                        <?php _e('Chave PIX', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" 
                           name="chave_pix" 
                           id="chave_pix" 
                           value="<?php echo esc_attr($configuracoes['chave_pix']); ?>" 
                           class="regular-text"
                           placeholder="<?php _e('exemplo@email.com, CPF, telefone, ou chave aleatória', 'gestao-coletiva'); ?>">
                    <p class="description">
                        <?php _e('Informe a chave PIX para recebimento das doações (email, CPF, celular ou chave aleatória).', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="nome_beneficiario_pix">
                        <?php _e('Nome do Beneficiário', 'gestao-coletiva'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" 
                           name="nome_beneficiario_pix" 
                           id="nome_beneficiario_pix" 
                           value="<?php echo esc_attr($configuracoes['nome_beneficiario_pix']); ?>" 
                           class="regular-text"
                           placeholder="<?php _e('Nome completo do titular da conta', 'gestao-coletiva'); ?>">
                    <p class="description">
                        <?php _e('Nome completo do titular da conta PIX que aparecerá para os doadores.', 'gestao-coletiva'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Salvar Configurações', 'gestao-coletiva'), 'primary', 'submit', true, array('id' => 'submit-configuracoes')); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('Informações do Sistema', 'gestao-coletiva'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Versão do Plugin:', 'gestao-coletiva'); ?></th>
            <td><?php echo GC_VERSION; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Próximo Processamento:', 'gestao-coletiva'); ?></th>
            <td><?php echo wp_next_scheduled('gc_processar_vencimentos') ? date('d/m/Y H:i:s', wp_next_scheduled('gc_processar_vencimentos')) : __('Não agendado', 'gestao-coletiva'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Status do Cron:', 'gestao-coletiva'); ?></th>
            <td>
                <?php if (wp_next_scheduled('gc_processar_vencimentos')): ?>
                    <span style="color: green;"><?php _e('Ativo', 'gestao-coletiva'); ?></span>
                <?php else: ?>
                    <span style="color: red;"><?php _e('Inativo', 'gestao-coletiva'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Ações de Sistema', 'gestao-coletiva'); ?></h3>
    <p>
        <button type="button" id="btn-processar-vencimentos" class="button">
            <?php _e('Processar Vencimentos Agora', 'gestao-coletiva'); ?>
        </button>
        <span class="description">
            <?php _e('Executa manualmente o processamento de lançamentos e contestações vencidas.', 'gestao-coletiva'); ?>
        </span>
    </p>
    
    
    <h3 style="color: #d63638;"><?php _e('⚠️ Zona de Perigo - Limpeza de Dados', 'gestao-coletiva'); ?></h3>
    <div class="notice notice-warning">
        <p><strong><?php _e('ATENÇÃO:', 'gestao-coletiva'); ?></strong> <?php _e('As ações abaixo são IRREVERSÍVEIS. Faça backup antes de prosseguir.', 'gestao-coletiva'); ?></p>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Limpar Lançamentos por Período', 'gestao-coletiva'); ?></th>
            <td>
                <fieldset>
                    <label>
                        <?php _e('Data inicial:', 'gestao-coletiva'); ?> 
                        <input type="date" id="data-inicial-limpeza" class="regular-text">
                    </label>
                    <br><br>
                    <label>
                        <?php _e('Data final:', 'gestao-coletiva'); ?> 
                        <input type="date" id="data-final-limpeza" class="regular-text">
                    </label>
                    <br><br>
                    <button type="button" id="btn-limpar-periodo" class="button button-secondary">
                        <?php _e('Limpar Lançamentos do Período', 'gestao-coletiva'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Remove todos os lançamentos e contestações relacionadas dentro do período especificado.', 'gestao-coletiva'); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Limpar Todos os Dados', 'gestao-coletiva'); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" id="confirmar-limpeza-total"> 
                        <?php _e('Confirmo que quero apagar TODOS os dados (lançamentos, contestações, relatórios)', 'gestao-coletiva'); ?>
                    </label>
                    <br><br>
                    <button type="button" id="btn-limpar-tudo" class="button button-secondary" disabled>
                        <?php _e('🗑️ Apagar Todos os Dados', 'gestao-coletiva'); ?>
                    </button>
                    <p class="description" style="color: #d63638;">
                        <?php _e('REMOVE COMPLETAMENTE todas as tabelas e dados do plugin. Esta ação NÃO PODE ser desfeita!', 'gestao-coletiva'); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Seletor de mídia para logo
    var gc_media_uploader;
    
    $('#btn-selecionar-logo').on('click', function(e) {
        e.preventDefault();
        
        // Se o uploader já existe, reabre
        if (gc_media_uploader) {
            gc_media_uploader.open();
            return;
        }
        
        // Cria o seletor de mídia
        gc_media_uploader = wp.media({
            title: '<?php _e("Selecionar Logo", "gestao-coletiva"); ?>',
            button: {
                text: '<?php _e("Usar esta imagem", "gestao-coletiva"); ?>'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });
        
        // Quando uma imagem for selecionada
        gc_media_uploader.on('select', function() {
            var attachment = gc_media_uploader.state().get('selection').first().toJSON();
            
            // Atualizar campo hidden
            $('#logo_url').val(attachment.url);
            
            // Atualizar preview
            $('.gc-logo-preview').html('<img src="' + attachment.url + '" alt="Logo selecionado" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 10px;">');
            
            // Mostrar botão remover se não existe
            if (!$('#btn-remover-logo').length) {
                $('.gc-logo-actions').append('<button type="button" id="btn-remover-logo" class="button"><?php _e("Remover Logo", "gestao-coletiva"); ?></button>');
            }
        });
        
        // Abre o seletor
        gc_media_uploader.open();
    });
    
    // Remover logo
    $(document).on('click', '#btn-remover-logo', function(e) {
        e.preventDefault();
        
        if (confirm('<?php _e("Tem certeza que deseja remover o logo?", "gestao-coletiva"); ?>')) {
            $('#logo_url').val('');
            $('.gc-logo-preview').html('<div class="gc-no-logo" style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;"><?php _e("Nenhum logo selecionado", "gestao-coletiva"); ?></div>');
            $(this).remove();
        }
    });
    
    // Salvar configurações
    $('#gc-form-configuracoes').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=gc_salvar_configuracoes&nonce=' + gc_ajax.nonce;
        
        $('#submit-configuracoes').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('<?php _e("Erro:", "gestao-coletiva"); ?> ' + response.data);
                }
                $('#submit-configuracoes').prop('disabled', false);
            },
            error: function() {
                alert('<?php _e("Erro ao processar solicitação", "gestao-coletiva"); ?>');
                $('#submit-configuracoes').prop('disabled', false);
            }
        });
    });
    
    // Processar vencimentos manualmente
    $('#btn-processar-vencimentos').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e("Processando...", "gestao-coletiva"); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_processar_vencimentos_manual',
                nonce: '<?php echo wp_create_nonce("gc_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('<?php _e("Erro:", "gestao-coletiva"); ?> ' + response.data);
                }
                button.prop('disabled', false).text('<?php _e("Processar Vencimentos Agora", "gestao-coletiva"); ?>');
            },
            error: function() {
                alert('<?php _e("Erro ao processar solicitação", "gestao-coletiva"); ?>');
                button.prop('disabled', false).text('<?php _e("Processar Vencimentos Agora", "gestao-coletiva"); ?>');
            }
        });
    });
});
</script>