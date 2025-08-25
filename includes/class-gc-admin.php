<?php

if (!defined('ABSPATH')) {
    exit;
}

class GC_Admin {
    
    public function __construct() {
        // Only register admin menu hooks if in admin area
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Always register AJAX hooks (they work in both admin and frontend)
        add_action('wp_ajax_gc_criar_lancamento', array($this, 'ajax_criar_lancamento'));
        add_action('wp_ajax_gc_atualizar_estado', array($this, 'ajax_atualizar_estado'));
        add_action('wp_ajax_gc_criar_contestacao', array($this, 'ajax_criar_contestacao'));
        add_action('wp_ajax_gc_responder_contestacao', array($this, 'ajax_responder_contestacao'));
        add_action('wp_ajax_gc_analisar_contestacao', array($this, 'ajax_analisar_contestacao'));
        add_action('wp_ajax_gc_upload_relatorio', array($this, 'ajax_upload_relatorio'));
        add_action('wp_ajax_gc_salvar_configuracoes', array($this, 'ajax_salvar_configuracoes'));
        add_action('wp_ajax_gc_gerar_certificado', array($this, 'ajax_gerar_certificado'));
        
        add_action('wp_ajax_nopriv_gc_criar_lancamento', array($this, 'ajax_criar_lancamento'));
        add_action('wp_ajax_gc_buscar_lancamento', array($this, 'ajax_buscar_lancamento'));
        add_action('wp_ajax_nopriv_gc_buscar_lancamento', array($this, 'ajax_buscar_lancamento'));
        add_action('wp_ajax_gc_gerar_relatorio_periodo', array($this, 'ajax_gerar_relatorio_periodo'));
        add_action('wp_ajax_gc_processar_vencimentos_manual', array($this, 'ajax_processar_vencimentos_manual'));
        
        if (!wp_next_scheduled('gc_processar_vencimentos')) {
            wp_schedule_event(time(), 'hourly', 'gc_processar_vencimentos');
        }
        
        add_action('gc_processar_vencimentos', array($this, 'processar_vencimentos_cron'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Gestão Coletiva', 'gestao-coletiva'),
            __('Gestão Coletiva', 'gestao-coletiva'),
            'read',
            'gestao-coletiva',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'gestao-coletiva',
            __('Dashboard', 'gestao-coletiva'),
            __('Dashboard', 'gestao-coletiva'),
            'read',
            'gestao-coletiva',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'gestao-coletiva',
            __('Lançamentos', 'gestao-coletiva'),
            __('Lançamentos', 'gestao-coletiva'),
            'read',
            'gc-lancamentos',
            array($this, 'lancamentos_page')
        );
        
        add_submenu_page(
            'gestao-coletiva',
            __('Contestações', 'gestao-coletiva'),
            __('Contestações', 'gestao-coletiva'),
            'read',
            'gc-contestacoes',
            array($this, 'contestacoes_page')
        );
        
        add_submenu_page(
            'gestao-coletiva',
            __('Relatórios', 'gestao-coletiva'),
            __('Relatórios', 'gestao-coletiva'),
            'read',
            'gc-relatorios',
            array($this, 'relatorios_page')
        );
        
        add_submenu_page(
            'gestao-coletiva',
            __('Configurações', 'gestao-coletiva'),
            __('Configurações', 'gestao-coletiva'),
            'manage_options',
            'gc-configuracoes',
            array($this, 'configuracoes_page')
        );
    }
    
    public function dashboard_page() {
        include GC_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    public function lancamentos_page() {
        include GC_PLUGIN_PATH . 'admin/views/lancamentos.php';
    }
    
    public function contestacoes_page() {
        include GC_PLUGIN_PATH . 'admin/views/contestacoes.php';
    }
    
    public function relatorios_page() {
        include GC_PLUGIN_PATH . 'admin/views/relatorios.php';
    }
    
    public function configuracoes_page() {
        include GC_PLUGIN_PATH . 'admin/views/configuracoes.php';
    }
    
    public function ajax_criar_lancamento() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gc_nonce')) {
            wp_send_json_error(__('Nonce inválido', 'gestao-coletiva'));
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
            return;
        }
        
        if (!class_exists('GC_Lancamento')) {
            wp_send_json_error(__('Sistema indisponível. Contate o administrador.', 'gestao-coletiva'));
            return;
        }
        
        $tipo = sanitize_text_field($_POST['tipo']);
        
        // Verificar permissões: apenas administradores podem registrar despesas
        if ($tipo === 'despesa' && !current_user_can('manage_options')) {
            wp_send_json_error(__('Apenas administradores podem registrar despesas.', 'gestao-coletiva'));
            return;
        }
        
        // Para receitas, verificar se o usuário pode criar lançamentos (authors+ ou filter personalizado)
        if ($tipo === 'receita' && !current_user_can('edit_posts')) {
            $user_id = get_current_user_id();
            $pode_criar = apply_filters('gc_pode_criar_lancamento', false, $user_id, $tipo);
            if (!$pode_criar) {
                wp_send_json_error(__('Você não tem permissão para criar lançamentos.', 'gestao-coletiva'));
                return;
            }
        }
        
        $dados = array(
            'tipo' => $tipo,
            'descricao_curta' => sanitize_text_field($_POST['descricao_curta']),
            'descricao_detalhada' => sanitize_textarea_field($_POST['descricao_detalhada']),
            'valor' => floatval($_POST['valor']),
            'recorrencia' => isset($_POST['recorrencia']) ? sanitize_text_field($_POST['recorrencia']) : 'unica'
        );
        
        try {
            $resultado = GC_Lancamento::criar($dados);
            
            if (is_wp_error($resultado)) {
                wp_send_json_error($resultado->get_error_message());
            } else {
                $lancamento = GC_Lancamento::obter($resultado);
                wp_send_json_success(array(
                    'id' => $resultado,
                    'numero_unico' => $lancamento ? $lancamento->numero_unico : '',
                    'message' => __('Lançamento criado com sucesso!', 'gestao-coletiva')
                ));
            }
        } catch (Error $e) {
            error_log('Gestão Coletiva - Erro ao criar lançamento: ' . $e->getMessage());
            wp_send_json_error(__('Erro ao criar lançamento', 'gestao-coletiva') . ': ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Gestão Coletiva - Exceção ao criar lançamento: ' . $e->getMessage());
            wp_send_json_error(__('Erro ao criar lançamento', 'gestao-coletiva') . ': ' . $e->getMessage());
        }
    }
    
    public function ajax_atualizar_estado() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $id = intval($_POST['id']);
        $estado = sanitize_text_field($_POST['estado']);
        
        $resultado = GC_Lancamento::atualizar_estado($id, $estado);
        
        if ($resultado === false) {
            wp_send_json_error(__('Erro ao atualizar estado', 'gestao-coletiva'));
        } else {
            wp_send_json_success(__('Estado atualizado com sucesso!', 'gestao-coletiva'));
        }
    }
    
    public function ajax_criar_contestacao() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado para criar uma contestação.', 'gestao-coletiva'));
            return;
        }
        
        if (!class_exists('GC_Contestacao')) {
            wp_send_json_error(__('Sistema indisponível. Contate o administrador.', 'gestao-coletiva'));
            return;
        }
        
        $dados = array(
            'lancamento_id' => intval($_POST['lancamento_id']),
            'tipo' => sanitize_text_field($_POST['tipo']),
            'descricao' => sanitize_textarea_field($_POST['descricao'])
        );
        
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === 0) {
            $upload = wp_handle_upload($_FILES['comprovante'], array('test_form' => false));
            if ($upload && !isset($upload['error'])) {
                $dados['comprovante'] = $upload['file'];
            }
        }
        
        try {
            $resultado = GC_Contestacao::criar($dados);
            
            if (is_wp_error($resultado)) {
                wp_send_json_error($resultado->get_error_message());
            } else {
                wp_send_json_success(array(
                    'id' => $resultado,
                    'message' => __('Contestação criada com sucesso!', 'gestao-coletiva')
                ));
            }
        } catch (Error $e) {
            wp_send_json_error(__('Erro ao processar a solicitação. Contate o administrador.', 'gestao-coletiva'));
        }
    }
    
    public function ajax_responder_contestacao() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        $contestacao_id = intval($_POST['contestacao_id']);
        
        if (!GC_Contestacao::pode_responder($contestacao_id)) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $resposta = sanitize_textarea_field($_POST['resposta']);
        $novo_estado = sanitize_text_field($_POST['novo_estado']);
        
        $resultado = GC_Contestacao::responder($contestacao_id, $resposta, $novo_estado);
        
        if ($resultado === false) {
            wp_send_json_error(__('Erro ao responder contestação', 'gestao-coletiva'));
        } else {
            wp_send_json_success(__('Contestação respondida com sucesso!', 'gestao-coletiva'));
        }
    }
    
    public function ajax_analisar_contestacao() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        $contestacao_id = intval($_POST['contestacao_id']);
        
        if (!GC_Contestacao::pode_analisar($contestacao_id)) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $aceitar = filter_var($_POST['aceitar'], FILTER_VALIDATE_BOOLEAN);
        
        $resultado = GC_Contestacao::analisar($contestacao_id, $aceitar);
        
        if ($resultado === false) {
            wp_send_json_error(__('Erro ao analisar contestação', 'gestao-coletiva'));
        } else {
            wp_send_json_success(__('Contestação analisada com sucesso!', 'gestao-coletiva'));
        }
    }
    
    public function ajax_upload_relatorio() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== 0) {
            wp_send_json_error(__('Erro no upload do arquivo', 'gestao-coletiva'));
        }
        
        $dados = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'tipo' => sanitize_text_field($_POST['tipo']),
            'periodo' => sanitize_text_field($_POST['periodo'])
        );
        
        $arquivo_nome = GC_Relatorio::upload_arquivo($_FILES['arquivo'], $dados['tipo'], $dados['periodo']);
        
        if (is_wp_error($arquivo_nome)) {
            wp_send_json_error($arquivo_nome->get_error_message());
        }
        
        $resultado = GC_Relatorio::criar($dados, $arquivo_nome);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $resultado,
                'message' => __('Relatório enviado com sucesso!', 'gestao-coletiva')
            ));
        }
    }
    
    public function ajax_salvar_configuracoes() {
        // Verificar nonce manualmente (check_ajax_referer às vezes falha)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gc_nonce')) {
            wp_send_json_error(__('Nonce inválido', 'gestao-coletiva'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
            return;
        }
        
        if (!class_exists('GC_Database')) {
            wp_send_json_error(__('Sistema indisponível. Contate o administrador.', 'gestao-coletiva'));
            return;
        }
        
        $configuracoes = array(
            'prazo_efetivacao_horas',
            'prazo_resposta_contestacao_horas',
            'prazo_analise_resposta_horas',
            'prazo_publicacao_disputa_horas',
            'prazo_resolucao_disputa_dias',
            'texto_agradecimento_certificado'
        );
        
        try {
            foreach ($configuracoes as $config) {
                if (isset($_POST[$config])) {
                    $valor = sanitize_text_field($_POST[$config]);
                    GC_Database::update_setting($config, $valor);
                }
            }
            
            wp_send_json_success(__('Configurações salvas com sucesso!', 'gestao-coletiva'));
        } catch (Error $e) {
            wp_send_json_error(__('Erro ao salvar configurações. Contate o administrador.', 'gestao-coletiva'));
        }
    }
    
    public function ajax_gerar_certificado() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $id = intval($_POST['id']);
        $certificado = GC_Lancamento::gerar_certificado($id);
        
        if (!$certificado) {
            wp_send_json_error(__('Não foi possível gerar o certificado', 'gestao-coletiva'));
        }
        
        wp_send_json_success($certificado);
    }
    
    public function ajax_buscar_lancamento() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gc_nonce')) {
            wp_send_json_error(__('Nonce inválido', 'gestao-coletiva'));
            return;
        }
        
        $numero = sanitize_text_field($_POST['numero']);
        
        if (empty($numero)) {
            wp_send_json_error(__('Número do lançamento não informado', 'gestao-coletiva'));
            return;
        }
        
        if (!class_exists('GC_Lancamento')) {
            wp_send_json_error(__('Sistema indisponível. Contate o administrador.', 'gestao-coletiva'));
            return;
        }
        
        try {
            $lancamento = GC_Lancamento::obter_por_numero($numero);
            
            if (!$lancamento) {
                wp_send_json_error(__('Lançamento não encontrado', 'gestao-coletiva'));
                return;
            }
            
            $autor = get_user_by('ID', $lancamento->autor_id);
            
            $html = '<div class="gc-lancamento-encontrado">';
            $html .= '<h3>Lançamento #' . esc_html($lancamento->numero_unico) . '</h3>';
            $html .= '<p><strong>Tipo:</strong> ' . (($lancamento->tipo == 'receita') ? 'Receita' : 'Despesa') . '</p>';
            $html .= '<p><strong>Estado:</strong> ' . esc_html(ucfirst(str_replace('_', ' ', $lancamento->estado))) . '</p>';
            $html .= '<p><strong>Valor:</strong> R$ ' . number_format($lancamento->valor, 2, ',', '.') . '</p>';
            $html .= '<p><strong>Descrição:</strong> ' . esc_html($lancamento->descricao_curta) . '</p>';
            $html .= '<p><strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($lancamento->data_criacao)) . '</p>';
            if ($autor) {
                $html .= '<p><strong>Autor:</strong> ' . esc_html($autor->display_name) . '</p>';
            }
            $html .= '</div>';
            
            wp_send_json_success($html);
        } catch (Error $e) {
            wp_send_json_error(__('Erro ao buscar lançamento. Contate o administrador.', 'gestao-coletiva'));
        }
    }
    
    public function ajax_gerar_relatorio_periodo() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        $data_inicio = sanitize_text_field($_POST['data_inicio']);
        $data_fim = sanitize_text_field($_POST['data_fim']);
        
        if (empty($data_inicio) || empty($data_fim)) {
            wp_send_json_error(__('Datas não informadas', 'gestao-coletiva'));
        }
        
        $relatorio = GC_Relatorio::gerar_relatorio_periodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        
        $html = '<div class="gc-relatorio-gerado">';
        $html .= '<h3>Relatório do Período</h3>';
        $html .= '<p><strong>Período:</strong> ' . date('d/m/Y', strtotime($relatorio['periodo']['inicio'])) . ' - ' . date('d/m/Y', strtotime($relatorio['periodo']['fim'])) . '</p>';
        $html .= '<p><strong>Saldo Inicial:</strong> R$ ' . number_format(abs($relatorio['saldo_inicial']), 2, ',', '.') . '</p>';
        $html .= '<p><strong>Receitas:</strong> R$ ' . number_format($relatorio['movimentacao']['receitas'], 2, ',', '.') . '</p>';
        $html .= '<p><strong>Despesas:</strong> R$ ' . number_format($relatorio['movimentacao']['despesas'], 2, ',', '.') . '</p>';
        $html .= '<p><strong>Saldo Final:</strong> R$ ' . number_format(abs($relatorio['saldo_final']), 2, ',', '.') . '</p>';
        $html .= '<p><strong>Total de Lançamentos:</strong> ' . count($relatorio['lancamentos']) . '</p>';
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    public function ajax_processar_vencimentos_manual() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $lancamentos_processados = GC_Lancamento::processar_vencimentos();
        $contestacoes_processadas = GC_Contestacao::processar_vencimentos();
        
        $message = sprintf(
            __('Processamento concluído! %d lançamentos e %d contestações foram processados.', 'gestao-coletiva'),
            $lancamentos_processados,
            $contestacoes_processadas
        );
        
        wp_send_json_success($message);
    }
    
    public function processar_vencimentos_cron() {
        $lancamentos_processados = GC_Lancamento::processar_vencimentos();
        $contestacoes_processadas = GC_Contestacao::processar_vencimentos();
        
        if ($lancamentos_processados > 0 || $contestacoes_processadas > 0) {
            error_log("Gestão Coletiva: Processados {$lancamentos_processados} lançamentos e {$contestacoes_processadas} contestações vencidas.");
        }
    }
}