<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listar';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$contestacoes = GC_Contestacao::listar();
$disputas_pendentes = GC_Contestacao::obter_disputas_pendentes();
$disputas_finalizadas = GC_Contestacao::obter_disputas_finalizadas();
?>

<div class="wrap">
    <h1>
        <?php _e('Contestações', 'gestao-coletiva'); ?>
        <a href="#" class="page-title-action" id="btn-nova-contestacao">
            <?php _e('Nova Contestação', 'gestao-coletiva'); ?>
        </a>
    </h1>
    
    <?php if (!empty($contestacoes)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Lançamento', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Tipo', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Descrição', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Estado', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Autor', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Data', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Ações', 'gestao-coletiva'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contestacoes as $contestacao):
                $autor = get_user_by('ID', $contestacao->autor_id);
            ?>
            <tr>
                <td>
                    <strong>#<?php echo esc_html($contestacao->numero_unico); ?></strong><br>
                    <small><?php echo esc_html($contestacao->lancamento_descricao); ?></small>
                </td>
                <td><?php echo ($contestacao->tipo == 'doacao_nao_contabilizada') ? __('Doação não contabilizada', 'gestao-coletiva') : __('Despesa não verificada', 'gestao-coletiva'); ?></td>
                <td><?php echo esc_html(wp_trim_words($contestacao->descricao, 10)); ?></td>
                <td>
                    <span class="gc-badge gc-contestacao-<?php echo $contestacao->estado; ?>">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $contestacao->estado))); ?>
                    </span>
                </td>
                <td><?php echo $autor ? esc_html($autor->display_name) : __('Usuário removido', 'gestao-coletiva'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($contestacao->data_criacao)); ?></td>
                <td>
                    <button type="button" class="button button-small gc-ver-contestacao" data-id="<?php echo $contestacao->id; ?>">
                        <?php _e('Ver', 'gestao-coletiva'); ?>
                    </button>
                    
                    <?php if (GC_Contestacao::pode_responder($contestacao->id) && $contestacao->estado == 'pendente'): ?>
                        <button type="button" class="button button-small gc-responder-contestacao" data-id="<?php echo $contestacao->id; ?>">
                            <?php _e('Responder', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (GC_Contestacao::pode_analisar($contestacao->id) && $contestacao->estado == 'respondida'): ?>
                        <button type="button" class="button button-small gc-analisar-contestacao" data-id="<?php echo $contestacao->id; ?>">
                            <?php _e('Analisar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (current_user_can('manage_options') && $contestacao->estado == 'em_disputa'): ?>
                        <button type="button" class="button button-small button-primary gc-finalizar-disputa" data-id="<?php echo $contestacao->id; ?>">
                            <?php _e('Finalizar Disputa', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="gc-empty-state">
        <h3><?php _e('Nenhuma contestação encontrada', 'gestao-coletiva'); ?></h3>
        <p><?php _e('Não há contestações registradas no sistema.', 'gestao-coletiva'); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($disputas_pendentes)): ?>
    <hr>
    <h2><?php _e('Disputas Aguardando Finalização', 'gestao-coletiva'); ?></h2>
    <p class="description"><?php _e('Disputas que precisam ter links para postagem no blog e formulário de votação.', 'gestao-coletiva'); ?></p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Lançamento', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Contestação', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Data da Disputa', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Ações', 'gestao-coletiva'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($disputas_pendentes as $disputa): ?>
            <tr>
                <td>
                    <strong>#<?php echo esc_html($disputa->numero_unico); ?></strong><br>
                    <small><?php echo esc_html($disputa->lancamento_descricao); ?> - R$ <?php echo number_format($disputa->lancamento_valor, 2, ',', '.'); ?></small>
                </td>
                <td><?php echo esc_html(wp_trim_words($disputa->descricao, 15)); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($disputa->data_analise)); ?></td>
                <td>
                    <button type="button" class="button button-primary gc-finalizar-disputa" data-id="<?php echo $disputa->id; ?>">
                        <?php _e('Finalizar Disputa', 'gestao-coletiva'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    
    <?php if (!empty($disputas_finalizadas)): ?>
    <hr>
    <h2><?php _e('Disputas Aguardando Resultado da Votação', 'gestao-coletiva'); ?></h2>
    <p class="description"><?php _e('Disputas que foram publicadas e estão aguardando o resultado da votação comunitária.', 'gestao-coletiva'); ?></p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Lançamento', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Contestação', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Data Finalização', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Links', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Ações', 'gestao-coletiva'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($disputas_finalizadas as $disputa): ?>
            <tr>
                <td>
                    <strong>#<?php echo esc_html($disputa->numero_unico); ?></strong><br>
                    <small><?php echo esc_html($disputa->lancamento_descricao); ?> - R$ <?php echo number_format($disputa->lancamento_valor, 2, ',', '.'); ?></small>
                </td>
                <td><?php echo esc_html(wp_trim_words($disputa->descricao, 15)); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($disputa->data_finalizacao_disputa)); ?></td>
                <td>
                    <?php if ($disputa->link_postagem_blog): ?>
                        <a href="<?php echo esc_url($disputa->link_postagem_blog); ?>" target="_blank" class="button button-small">📝 Blog</a>
                    <?php endif; ?>
                    <?php if ($disputa->link_formulario_votacao): ?>
                        <a href="<?php echo esc_url($disputa->link_formulario_votacao); ?>" target="_blank" class="button button-small">🗳️ Votação</a>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button" class="button button-primary gc-registrar-resultado" data-id="<?php echo $disputa->id; ?>">
                        <?php _e('Registrar Resultado', 'gestao-coletiva'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>