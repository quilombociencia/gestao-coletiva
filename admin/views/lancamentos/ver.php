<?php
if (!$id) {
    wp_die(__('ID do lançamento não informado', 'gestao-coletiva'));
}

$lancamento = GC_Lancamento::obter($id);
if (!$lancamento) {
    wp_die(__('Lançamento não encontrado', 'gestao-coletiva'));
}

$autor = get_user_by('ID', $lancamento->autor_id);
$pode_editar = GC_Lancamento::pode_editar($id);
?>

<div class="wrap">
    <h1>
        <?php _e('Lançamento', 'gestao-coletiva'); ?> #<?php echo esc_html($lancamento->numero_unico); ?>
        <?php if ($pode_editar): ?>
            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=editar&id=' . $id); ?>" class="page-title-action">
                <?php _e('Editar', 'gestao-coletiva'); ?>
            </a>
        <?php endif; ?>
    </h1>
    
    <div class="gc-lancamento-detalhes">
        <div class="gc-card">
            <div class="gc-row">
                <div class="gc-info-basica">
                    <h3><?php _e('Informações Básicas', 'gestao-coletiva'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Número Único:', 'gestao-coletiva'); ?></th>
                            <td><strong>#<?php echo esc_html($lancamento->numero_unico); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Tipo:', 'gestao-coletiva'); ?></th>
                            <td>
                                <span class="gc-badge gc-tipo-<?php echo $lancamento->tipo; ?>">
                                    <?php echo ($lancamento->tipo == 'receita') ? __('Receita', 'gestao-coletiva') : __('Despesa', 'gestao-coletiva'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Estado:', 'gestao-coletiva'); ?></th>
                            <td>
                                <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Valor:', 'gestao-coletiva'); ?></th>
                            <td class="gc-valor <?php echo ($lancamento->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                <strong>R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Autor:', 'gestao-coletiva'); ?></th>
                            <td><?php echo $autor ? esc_html($autor->display_name) : __('Usuário removido', 'gestao-coletiva'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Recorrência:', 'gestao-coletiva'); ?></th>
                            <td>
                                <?php echo esc_html(ucfirst($lancamento->recorrencia)); ?>
                                <?php if ($lancamento->recorrencia !== 'unica'): ?>
                                    <?php if ($lancamento->recorrencia_ativa): ?>
                                        <span class="gc-badge gc-badge-success"><?php _e('Ativa', 'gestao-coletiva'); ?></span>
                                    <?php else: ?>
                                        <span class="gc-badge gc-badge-error"><?php _e('Cancelada', 'gestao-coletiva'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <?php if ($lancamento->lancamento_pai_id): ?>
                        <tr>
                            <th scope="row"><?php _e('Lançamento Pai:', 'gestao-coletiva'); ?></th>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $lancamento->lancamento_pai_id); ?>">
                                    #<?php echo esc_html(GC_Lancamento::obter($lancamento->lancamento_pai_id)->numero_unico ?? 'N/A'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($lancamento->data_proxima_recorrencia && $lancamento->recorrencia_ativa): ?>
                        <tr>
                            <th scope="row"><?php _e('Próxima Recorrência:', 'gestao-coletiva'); ?></th>
                            <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_proxima_recorrencia)); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <div class="gc-info-datas">
                    <h3><?php _e('Datas e Prazos', 'gestao-coletiva'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Data de Criação:', 'gestao-coletiva'); ?></th>
                            <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?></td>
                        </tr>
                        
                        <?php if ($lancamento->data_efetivacao): ?>
                        <tr>
                            <th scope="row"><?php _e('Data de Efetivação:', 'gestao-coletiva'); ?></th>
                            <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_efetivacao)); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <th scope="row"><?php _e('Data de Expiração:', 'gestao-coletiva'); ?></th>
                            <td><?php echo date('d/m/Y H:i', strtotime($lancamento->data_expiracao)); ?></td>
                        </tr>
                        
                        <?php if (!in_array($lancamento->estado, ['efetivado', 'cancelado', 'aceito'])): ?>
                        <tr>
                            <th scope="row"><?php _e('Prazo Atual:', 'gestao-coletiva'); ?></th>
                            <td>
                                <strong><?php echo date('d/m/Y H:i', strtotime($lancamento->prazo_atual)); ?></strong>
                                <br>
                                <small><?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->tipo_prazo))); ?></small>
                                <?php if (strtotime($lancamento->prazo_atual) < time()): ?>
                                    <span class="gc-prazo-vencido">(Vencido)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <div class="gc-row">
                <div class="gc-descricoes">
                    <h3><?php _e('Descrições', 'gestao-coletiva'); ?></h3>
                    
                    <div class="gc-descricao">
                        <h4><?php _e('Descrição Curta:', 'gestao-coletiva'); ?></h4>
                        <p><?php echo esc_html($lancamento->descricao_curta); ?></p>
                    </div>
                    
                    <?php if (!empty($lancamento->descricao_detalhada)): ?>
                    <div class="gc-descricao">
                        <h4><?php _e('Descrição Detalhada:', 'gestao-coletiva'); ?></h4>
                        <p><?php echo nl2br(esc_html($lancamento->descricao_detalhada)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($lancamento->anexos) && is_array($lancamento->anexos) && count($lancamento->anexos) > 0): ?>
                <div class="gc-anexos">
                    <h3><?php _e('Anexos', 'gestao-coletiva'); ?></h3>
                    <div class="gc-lista-anexos">
                        <?php foreach ($lancamento->anexos as $anexo): ?>
                        <div class="gc-anexo-item">
                            <a href="<?php echo esc_url($anexo); ?>" target="_blank" class="button">
                                <?php _e('Ver Anexo', 'gestao-coletiva'); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Ações -->
            <div class="gc-acoes-container">
                <h3><?php _e('Ações', 'gestao-coletiva'); ?></h3>
                
                <div class="gc-acoes">
                    <?php if ($pode_editar): ?>
                        <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=editar&id=' . $id); ?>" class="button button-primary">
                            <?php _e('Editar Lançamento', 'gestao-coletiva'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (current_user_can('manage_options') && $lancamento->estado == 'previsto'): ?>
                        <button type="button" class="button gc-atualizar-estado" data-id="<?php echo $id; ?>" data-estado="efetivado">
                            <?php _e('Efetivar', 'gestao-coletiva'); ?>
                        </button>
                        
                        <button type="button" class="button gc-atualizar-estado" data-id="<?php echo $id; ?>" data-estado="cancelado">
                            <?php _e('Cancelar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    
                    <?php 
                    // Estados que permitem gerar certificado
                    $estados_certificado = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
                    if (in_array($lancamento->estado, $estados_certificado) && $lancamento->tipo === 'receita'): 
                    ?>
                        <button type="button" class="button gc-gerar-certificado" data-id="<?php echo $id; ?>">
                            <?php _e('Gerar Certificado', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <!-- Ações de Recorrência -->
                    <?php if ($lancamento->recorrencia !== 'unica'): ?>
                        <?php if ($lancamento->recorrencia_ativa): ?>
                            <button type="button" class="button button-secondary gc-cancelar-recorrencia" data-id="<?php echo $id; ?>">
                                <?php _e('Cancelar Recorrência', 'gestao-coletiva'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="button gc-ver-serie-recorrencia" data-id="<?php echo $id; ?>">
                            <?php _e('Ver Série Completa', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php 
                    // Estados que podem ser contestados (valores já efetivos mas questionáveis)
                    $estados_contestaveis = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
                    if (is_user_logged_in() && in_array($lancamento->estado, $estados_contestaveis)): 
                    ?>
                        <button type="button" class="button gc-abrir-contestacao" data-id="<?php echo $id; ?>">
                            <?php _e('Contestar', 'gestao-coletiva'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contestações relacionadas -->
    <?php
    $contestacoes = GC_Contestacao::listar(array('lancamento_id' => $id));
    if (!empty($contestacoes)):
    ?>
    <div class="gc-contestacoes-relacionadas">
        <div class="gc-card">
            <h3><?php _e('Contestações', 'gestao-coletiva'); ?></h3>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Tipo', 'gestao-coletiva'); ?></th>
                        <th><?php _e('Descrição', 'gestao-coletiva'); ?></th>
                        <th><?php _e('Estado', 'gestao-coletiva'); ?></th>
                        <th><?php _e('Data', 'gestao-coletiva'); ?></th>
                        <th><?php _e('Ações', 'gestao-coletiva'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contestacoes as $contestacao): ?>
                    <tr>
                        <td><?php echo ($contestacao->tipo == 'doacao_nao_contabilizada') ? __('Doação não contabilizada', 'gestao-coletiva') : __('Despesa não verificada', 'gestao-coletiva'); ?></td>
                        <td><?php echo esc_html(wp_trim_words($contestacao->descricao, 15)); ?></td>
                        <td>
                            <span class="gc-badge gc-contestacao-<?php echo $contestacao->estado; ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $contestacao->estado))); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($contestacao->data_criacao)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=gc-contestacoes&action=ver&id=' . $contestacao->id); ?>" class="button button-small">
                                <?php _e('Ver', 'gestao-coletiva'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>