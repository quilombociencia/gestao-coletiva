<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'gestao-coletiva'));
}

$configuracoes = array(
    'prazo_efetivacao_horas' => GC_Database::get_setting('prazo_efetivacao_horas'),
    'prazo_resposta_contestacao_horas' => GC_Database::get_setting('prazo_resposta_contestacao_horas'),
    'prazo_analise_resposta_horas' => GC_Database::get_setting('prazo_analise_resposta_horas'),
    'prazo_publicacao_disputa_horas' => GC_Database::get_setting('prazo_publicacao_disputa_horas'),
    'prazo_resolucao_disputa_dias' => GC_Database::get_setting('prazo_resolucao_disputa_dias'),
    'texto_agradecimento_certificado' => GC_Database::get_setting('texto_agradecimento_certificado')
);
?>

<div class="wrap">
    <h1><?php _e('Configurações - Gestão Coletiva', 'gestao-coletiva'); ?></h1>
    
    <form id="gc-form-configuracoes" method="post">
        <?php wp_nonce_field('gc_nonce', 'gc_nonce'); ?>
        
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
    
    <p>
        <button type="button" id="btn-corrigir-estados" class="button">
            <?php _e('Corrigir Estados de Contestação', 'gestao-coletiva'); ?>
        </button>
        <span class="description">
            <?php _e('Corrige contestações com estado "rejeitada" que deveriam estar "em_disputa".', 'gestao-coletiva'); ?>
        </span>
    </p>
    
    <p>
        <button type="button" id="btn-atualizar-estrutura" class="button">
            <?php _e('Atualizar Estrutura do Banco', 'gestao-coletiva'); ?>
        </button>
        <span class="description">
            <?php _e('Adiciona campos novos necessários para funcionalidade de votação (resultado_votacao, observacoes_finais, etc.).', 'gestao-coletiva'); ?>
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