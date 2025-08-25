<?php
$numero = isset($_GET['numero']) ? sanitize_text_field($_GET['numero']) : '';
$lancamento = null;

if (!empty($numero)) {
    $lancamento = GC_Lancamento::obter_por_numero($numero);
}
?>

<div class="wrap">
    <h1><?php _e('Buscar Lançamento', 'gestao-coletiva'); ?></h1>
    
    <div class="gc-busca-form">
        <form method="get" class="gc-form-busca">
            <input type="hidden" name="page" value="gc-lancamentos">
            <input type="hidden" name="action" value="buscar">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="numero"><?php _e('Número do Lançamento', 'gestao-coletiva'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="numero" 
                               id="numero" 
                               value="<?php echo esc_attr($numero); ?>" 
                               placeholder="GC2024000001" 
                               class="regular-text"
                               required>
                        <p class="description">
                            <?php _e('Digite o número único do lançamento (formato: GC2024000001)', 'gestao-coletiva'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Buscar Lançamento', 'gestao-coletiva'), 'primary', 'submit', false); ?>
        </form>
    </div>
    
    <?php if (!empty($numero)): ?>
        <?php if ($lancamento): ?>
            <!-- Resultado da busca -->
            <div class="gc-resultado-busca">
                <div class="gc-card">
                    <h2><?php _e('Lançamento Encontrado', 'gestao-coletiva'); ?></h2>
                    
                    <div class="gc-lancamento-resumo">
                        <div class="gc-info-grid">
                            <div class="gc-info-item">
                                <strong><?php _e('Número:', 'gestao-coletiva'); ?></strong>
                                #<?php echo esc_html($lancamento->numero_unico); ?>
                            </div>
                            
                            <div class="gc-info-item">
                                <strong><?php _e('Tipo:', 'gestao-coletiva'); ?></strong>
                                <span class="gc-badge gc-tipo-<?php echo $lancamento->tipo; ?>">
                                    <?php echo ($lancamento->tipo == 'receita') ? __('Receita', 'gestao-coletiva') : __('Despesa', 'gestao-coletiva'); ?>
                                </span>
                            </div>
                            
                            <div class="gc-info-item">
                                <strong><?php _e('Estado:', 'gestao-coletiva'); ?></strong>
                                <span class="gc-badge gc-estado-<?php echo $lancamento->estado; ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))); ?>
                                </span>
                            </div>
                            
                            <div class="gc-info-item">
                                <strong><?php _e('Valor:', 'gestao-coletiva'); ?></strong>
                                <span class="gc-valor <?php echo ($lancamento->tipo == 'receita') ? 'gc-positivo' : 'gc-negativo'; ?>">
                                    R$ <?php echo number_format($lancamento->valor, 2, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <div class="gc-info-item">
                                <strong><?php _e('Data:', 'gestao-coletiva'); ?></strong>
                                <?php echo date('d/m/Y H:i', strtotime($lancamento->data_criacao)); ?>
                            </div>
                            
                            <div class="gc-info-item">
                                <strong><?php _e('Descrição:', 'gestao-coletiva'); ?></strong>
                                <?php echo esc_html($lancamento->descricao_curta); ?>
                            </div>
                        </div>
                        
                        <div class="gc-acoes-resultado">
                            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=ver&id=' . $lancamento->id); ?>" 
                               class="button button-primary">
                                <?php _e('Ver Detalhes Completos', 'gestao-coletiva'); ?>
                            </a>
                            
                            <?php if (GC_Lancamento::pode_editar($lancamento->id)): ?>
                                <a href="<?php echo admin_url('admin.php?page=gc-lancamentos&action=editar&id=' . $lancamento->id); ?>" 
                                   class="button">
                                    <?php _e('Editar', 'gestao-coletiva'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($lancamento->estado === 'efetivado' && $lancamento->tipo === 'receita'): ?>
                                <button type="button" class="button gc-gerar-certificado" data-id="<?php echo $lancamento->id; ?>">
                                    <?php _e('Gerar Certificado', 'gestao-coletiva'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lançamento não encontrado -->
            <div class="gc-resultado-busca">
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('Lançamento não encontrado!', 'gestao-coletiva'); ?></strong>
                        <?php _e('Verifique se o número foi digitado corretamente e tente novamente.', 'gestao-coletiva'); ?>
                    </p>
                </div>
                
                <div class="gc-sugestoes">
                    <h3><?php _e('Dicas para buscar lançamentos:', 'gestao-coletiva'); ?></h3>
                    <ul>
                        <li><?php _e('Certifique-se de que o número está no formato correto (ex: GC2024000001)', 'gestao-coletiva'); ?></li>
                        <li><?php _e('Verifique se não há espaços em branco no início ou fim do número', 'gestao-coletiva'); ?></li>
                        <li><?php _e('O número é case-sensitive, use letras maiúsculas', 'gestao-coletiva'); ?></li>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=gc-lancamentos'); ?>">
                                <?php _e('Ver todos os lançamentos', 'gestao-coletiva'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Busca rápida por usuário (para admins) -->
    <?php if (current_user_can('manage_options')): ?>
    <div class="gc-busca-avancada">
        <div class="gc-card">
            <h3><?php _e('Busca Avançada', 'gestao-coletiva'); ?></h3>
            
            <form method="get" class="gc-form-avancada">
                <input type="hidden" name="page" value="gc-lancamentos">
                
                <div class="gc-filtros-avancados">
                    <div class="gc-filtro">
                        <label for="autor_id"><?php _e('Usuário:', 'gestao-coletiva'); ?></label>
                        <?php 
                        wp_dropdown_users(array(
                            'name' => 'autor_id',
                            'id' => 'autor_id',
                            'show_option_none' => __('Todos os usuários', 'gestao-coletiva'),
                            'selected' => isset($_GET['autor_id']) ? $_GET['autor_id'] : ''
                        )); 
                        ?>
                    </div>
                    
                    <div class="gc-filtro">
                        <label for="tipo_busca"><?php _e('Tipo:', 'gestao-coletiva'); ?></label>
                        <select name="tipo" id="tipo_busca">
                            <option value=""><?php _e('Todos os tipos', 'gestao-coletiva'); ?></option>
                            <option value="receita" <?php selected(isset($_GET['tipo']) && $_GET['tipo'] == 'receita'); ?>>
                                <?php _e('Receitas', 'gestao-coletiva'); ?>
                            </option>
                            <option value="despesa" <?php selected(isset($_GET['tipo']) && $_GET['tipo'] == 'despesa'); ?>>
                                <?php _e('Despesas', 'gestao-coletiva'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="gc-filtro">
                        <label for="estado_busca"><?php _e('Estado:', 'gestao-coletiva'); ?></label>
                        <select name="estado" id="estado_busca">
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
                        </select>
                    </div>
                </div>
                
                <?php submit_button(__('Buscar com Filtros', 'gestao-coletiva'), 'secondary', 'submit_filtros', false); ?>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.gc-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.gc-info-item {
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.gc-acoes-resultado {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gc-filtros-avancados {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.gc-filtro label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.gc-sugestoes {
    margin-top: 20px;
}

.gc-sugestoes ul {
    margin-left: 20px;
}

.gc-sugestoes li {
    margin-bottom: 8px;
}
</style>