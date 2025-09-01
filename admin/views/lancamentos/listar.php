<?php
$filtros = array();
if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
    $filtros['tipo'] = sanitize_text_field($_GET['tipo']);
}
if (isset($_GET['estado']) && !empty($_GET['estado'])) {
    $filtros['estado'] = sanitize_text_field($_GET['estado']);
}
if (isset($_GET['autor_id']) && !empty($_GET['autor_id'])) {
    $filtros['autor_id'] = intval($_GET['autor_id']);
}

$lancamentos = GC_Lancamento::listar($filtros);
?>

<div class="wrap">
    <h1>
        <?php _e('Lançamentos', 'gestao-coletiva'); ?>
        <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=criar'); ?>" class="page-title-action">
            <?php _e('Adicionar Novo', 'gestao-coletiva'); ?>
        </a>
    </h1>
    
    <!-- Filtros -->
    <div class="gc-filtros">
        <form method="get" class="gc-filtros-form">
            <input type="hidden" name="page" value="gc-lancamentos">
            
            <select name="tipo">
                <option value=""><?php _e('Todos os tipos', 'gestao-coletiva'); ?></option>
                <option value="receita" <?php selected(isset($_GET['tipo']) && $_GET['tipo'] == 'receita'); ?>>
                    <?php _e('Receitas', 'gestao-coletiva'); ?>
                </option>
                <option value="despesa" <?php selected(isset($_GET['tipo']) && $_GET['tipo'] == 'despesa'); ?>>
                    <?php _e('Despesas', 'gestao-coletiva'); ?>
                </option>
            </select>
            
            <select name="estado">
                <option value=""><?php _e('Todos os estados', 'gestao-coletiva'); ?></option>
                <option value="previsto" <?php selected(isset($_GET['estado']) && $_GET['estado'] == 'previsto'); ?>>
                    <?php _e('Previsto', 'gestao-coletiva'); ?>
                </option>
                <option value="efetivado" <?php selected(isset($_GET['estado']) && $_GET['estado'] == 'efetivado'); ?>>
                    <?php _e('Efetivado', 'gestao-coletiva'); ?>
                </option>
                <option value="cancelado" <?php selected(isset($_GET['estado']) && $_GET['estado'] == 'cancelado'); ?>>
                    <?php _e('Cancelado', 'gestao-coletiva'); ?>
                </option>
                <option value="expirado" <?php selected(isset($_GET['estado']) && $_GET['estado'] == 'expirado'); ?>>
                    <?php _e('Expirado', 'gestao-coletiva'); ?>
                </option>
                <option value="em_contestacao" <?php selected(isset($_GET['estado']) && $_GET['estado'] == 'em_contestacao'); ?>>
                    <?php _e('Em Contestação', 'gestao-coletiva'); ?>
                </option>
            </select>
            
            <?php submit_button(__('Filtrar', 'gestao-coletiva'), 'secondary', 'submit', false); ?>
            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos'); ?>" class="button">
                <?php _e('Limpar', 'gestao-coletiva'); ?>
            </a>
        </form>
    </div>
    
    <!-- Lista de Lançamentos -->
    <?php if (!empty($lancamentos)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Número', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Tipo', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Descrição', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Valor', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Estado', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Autor', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Data', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Prazo', 'gestao-coletiva'); ?></th>
                <th scope="col"><?php _e('Ações', 'gestao-coletiva'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lancamentos as $lancamento): 
                $autor = get_user_by('ID', $lancamento->autor_id);
                $prazo_vencido = strtotime($lancamento->prazo_atual) < time();
            ?>
            <tr>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $lancamento->id); ?>">
                            #<?php echo esc_html($lancamento->numero_unico); ?>
                        </a>
                    </strong>
                </td>
                <td>
                    <span class="gc-badge gc-tipo-<?php echo $lancamento->tipo; ?>">
                        <?php echo ($lancamento->tipo == 'receita') ? __('Receita', 'gestao-coletiva') : __('Despesa', 'gestao-coletiva'); ?>
                    </span>
                </td>
                <td><?php echo esc_html($lancamento->descricao_curta); ?></td>
                <td class="gc-valor <?php echo ($lancamento->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                    R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                </td>
                <td>
                    <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?>">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                    </span>
                </td>
                <td><?php echo $autor ? esc_html($autor->display_name) : __('Usuário removido', 'gestao-coletiva'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?></td>
                <td>
                    <?php if ($lancamento->estado != 'efetivado' && $lancamento->estado != 'cancelado' && $lancamento->estado != 'aceito'): ?>
                        <span class="gc-prazo <?php echo $prazo_vencido ? 'gc-prazo-vencido' : ''; ?>">
                            <?php echo date('d/m/Y H:i', strtotime($lancamento->prazo_atual)); ?>
                        </span>
                        <br>
                        <small><?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->tipo_prazo))); ?></small>
                    <?php else: ?>
                        <span class="gc-prazo-finalizado">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $lancamento->id); ?>" class="button button-small">
                        <?php _e('Ver', 'gestao-coletiva'); ?>
                    </a>
                    
                    <?php if (!empty($lancamento->anexos) && is_array($lancamento->anexos) && count($lancamento->anexos) > 0): ?>
                        <a href="<?php echo esc_url($lancamento->anexos[0]); ?>" target="_blank" class="button button-small" title="<?php echo count($lancamento->anexos) > 1 ? sprintf(__('%d anexos', 'gestao-coletiva'), count($lancamento->anexos)) : __('1 anexo', 'gestao-coletiva'); ?>">
                            <?php _e('Anexos', 'gestao-coletiva'); ?> (<?php echo count($lancamento->anexos); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if (GC_Lancamento::pode_editar($lancamento->id)): ?>
                        <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=editar&id=' . $lancamento->id); ?>" class="button button-small">
                            <?php _e('Editar', 'gestao-coletiva'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (current_user_can('manage_options') && $lancamento->estado == 'previsto'): ?>
                        <button type="button" class="button button-small gc-atualizar-estado" 
                                data-id="<?php echo $lancamento->id; ?>" 
                                data-estado="efetivado">
                            <?php _e('Efetivar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="gc-empty-state">
        <h3><?php _e('Nenhum lançamento encontrado', 'gestao-coletiva'); ?></h3>
        <p><?php _e('Não há lançamentos que correspondam aos critérios selecionados.', 'gestao-coletiva'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=criar'); ?>" class="button button-primary">
            <?php _e('Criar Primeiro Lançamento', 'gestao-coletiva'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.gc-atualizar-estado').on('click', function() {
        var button = $(this);
        var id = button.data('id');
        var estado = button.data('estado');
        
        if (confirm('<?php _e('Tem certeza que deseja alterar o estado deste lançamento?', 'gestao-coletiva'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gc_atualizar_estado',
                    id: id,
                    estado: estado,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('Erro ao processar solicitação', 'gestao-coletiva'); ?>');
                }
            });
        }
    });
});
</script>