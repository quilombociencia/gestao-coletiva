<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listar';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$contestacoes = GC_Contestacao::listar();
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
</div>