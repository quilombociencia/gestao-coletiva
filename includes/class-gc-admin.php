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
        add_action('wp_ajax_gc_editar_lancamento', array($this, 'ajax_editar_lancamento'));
        add_action('wp_ajax_gc_atualizar_estado', array($this, 'ajax_atualizar_estado'));
        add_action('wp_ajax_gc_criar_contestacao', array($this, 'ajax_criar_contestacao'));
        add_action('wp_ajax_gc_responder_contestacao', array($this, 'ajax_responder_contestacao'));
        add_action('wp_ajax_gc_analisar_contestacao', array($this, 'ajax_analisar_contestacao'));
        add_action('wp_ajax_gc_upload_relatorio', array($this, 'ajax_upload_relatorio'));
        add_action('wp_ajax_gc_salvar_configuracoes', array($this, 'ajax_salvar_configuracoes'));
        add_action('wp_ajax_gc_gerar_certificado', array($this, 'ajax_gerar_certificado'));
        add_action('wp_ajax_nopriv_gc_gerar_certificado', array($this, 'ajax_gerar_certificado'));
        add_action('wp_ajax_gc_verificar_certificado', array($this, 'ajax_verificar_certificado'));
        add_action('wp_ajax_nopriv_gc_verificar_certificado', array($this, 'ajax_verificar_certificado'));
        
        add_action('wp_ajax_nopriv_gc_criar_lancamento', array($this, 'ajax_criar_lancamento'));
        add_action('wp_ajax_gc_buscar_lancamento', array($this, 'ajax_buscar_lancamento'));
        add_action('wp_ajax_nopriv_gc_buscar_lancamento', array($this, 'ajax_buscar_lancamento'));
        add_action('wp_ajax_gc_get_pix_info', array($this, 'ajax_get_pix_info'));
        add_action('wp_ajax_nopriv_gc_get_pix_info', array($this, 'ajax_get_pix_info'));
        add_action('wp_ajax_gc_gerar_relatorio_periodo', array($this, 'ajax_gerar_relatorio_periodo'));
        add_action('wp_ajax_gc_gerar_relatorio_previsao', array($this, 'ajax_gerar_relatorio_previsao'));
        add_action('wp_ajax_gc_processar_vencimentos_manual', array($this, 'ajax_processar_vencimentos_manual'));
        add_action('wp_ajax_gc_limpar_periodo', array($this, 'ajax_limpar_periodo'));
        add_action('wp_ajax_gc_limpar_tudo', array($this, 'ajax_limpar_tudo'));
        add_action('wp_ajax_gc_finalizar_disputa', array($this, 'ajax_finalizar_disputa'));
        add_action('wp_ajax_gc_registrar_resultado', array($this, 'ajax_registrar_resultado'));
        add_action('wp_ajax_gc_ver_contestacao', array($this, 'ajax_ver_contestacao'));
        add_action('wp_ajax_gc_cancelar_recorrencia', array($this, 'ajax_cancelar_recorrencia'));
        add_action('wp_ajax_gc_corrigir_inconsistencias', array($this, 'ajax_corrigir_inconsistencias'));
        
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
            __('Painel', 'gestao-coletiva'),
            __('Painel', 'gestao-coletiva'),
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
        
        // Processar anexos se houver
        if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
            error_log('GC Debug: Processando anexos - ' . count($_FILES['anexos']['name']) . ' arquivos encontrados');
            
            // Incluir biblioteca de upload do WordPress
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            $anexos_urls = array();
            
            for ($i = 0; $i < count($_FILES['anexos']['name']); $i++) {
                if ($_FILES['anexos']['error'][$i] === 0) {
                    // Preparar array de arquivo individual para wp_handle_upload
                    $arquivo = array(
                        'name' => $_FILES['anexos']['name'][$i],
                        'type' => $_FILES['anexos']['type'][$i],
                        'tmp_name' => $_FILES['anexos']['tmp_name'][$i],
                        'error' => $_FILES['anexos']['error'][$i],
                        'size' => $_FILES['anexos']['size'][$i]
                    );
                    
                    $upload = wp_handle_upload($arquivo, array(
                        'test_form' => false,
                        'mimes' => array(
                            'pdf' => 'application/pdf',
                            'jpg|jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif'
                        ),
                        'unique_filename_callback' => function($dir, $name, $ext) {
                            return 'gc_' . time() . '_' . wp_generate_uuid4() . $ext;
                        }
                    ));
                    
                    if ($upload && !isset($upload['error'])) {
                        $anexos_urls[] = $upload['url'];
                        error_log('GC Debug: Anexo carregado com sucesso: ' . $upload['url']);
                    } else {
                        error_log('GC Debug: Erro no upload: ' . print_r($upload, true));
                    }
                }
            }
            
            if (!empty($anexos_urls)) {
                $dados['anexos'] = $anexos_urls;
                error_log('GC Debug: ' . count($anexos_urls) . ' anexos serão salvos no lançamento');
            }
        } else {
            error_log('GC Debug: Nenhum arquivo $_FILES[anexos] encontrado ou não é array');
        }
        
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
    
    public function ajax_editar_lancamento() {
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
        
        $lancamento_id = intval($_POST['lancamento_id']);
        
        // Verificar se o lançamento existe
        $lancamento = GC_Lancamento::obter($lancamento_id);
        if (!$lancamento) {
            wp_send_json_error(__('Lançamento não encontrado.', 'gestao-coletiva'));
            return;
        }
        
        // Verificar permissões de edição
        if (!GC_Lancamento::pode_editar($lancamento_id)) {
            wp_send_json_error(__('Você não tem permissão para editar este lançamento.', 'gestao-coletiva'));
            return;
        }
        
        $dados_atualizacao = array(
            'tipo' => sanitize_text_field($_POST['tipo']),
            'descricao_curta' => sanitize_text_field($_POST['descricao_curta']),
            'descricao_detalhada' => sanitize_textarea_field($_POST['descricao_detalhada']),
            'valor' => floatval($_POST['valor']),
            'recorrencia' => isset($_POST['recorrencia']) ? sanitize_text_field($_POST['recorrencia']) : $lancamento->recorrencia
        );
        
        // Processar anexos existentes (remoções)
        $anexos_atuais = $lancamento->anexos ? (is_array($lancamento->anexos) ? $lancamento->anexos : json_decode($lancamento->anexos, true)) : array();
        $remover_anexos = isset($_POST['remover_anexos']) ? array_map('intval', $_POST['remover_anexos']) : array();
        
        // Remover anexos selecionados
        foreach ($remover_anexos as $indice) {
            if (isset($anexos_atuais[$indice])) {
                unset($anexos_atuais[$indice]);
            }
        }
        
        // Reindexar array
        $anexos_atuais = array_values($anexos_atuais);
        
        // Processar novos anexos
        if (isset($_FILES['novos_anexos']) && is_array($_FILES['novos_anexos']['name'])) {
            error_log('GC Debug editar: Processando ' . count($_FILES['novos_anexos']['name']) . ' novos anexos');
            
            // Incluir biblioteca de upload do WordPress
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            for ($i = 0; $i < count($_FILES['novos_anexos']['name']); $i++) {
                if ($_FILES['novos_anexos']['error'][$i] === 0) {
                    // Preparar array de arquivo individual para wp_handle_upload
                    $arquivo = array(
                        'name' => $_FILES['novos_anexos']['name'][$i],
                        'type' => $_FILES['novos_anexos']['type'][$i],
                        'tmp_name' => $_FILES['novos_anexos']['tmp_name'][$i],
                        'error' => $_FILES['novos_anexos']['error'][$i],
                        'size' => $_FILES['novos_anexos']['size'][$i]
                    );
                    
                    $upload = wp_handle_upload($arquivo, array(
                        'test_form' => false,
                        'mimes' => array(
                            'pdf' => 'application/pdf',
                            'jpg|jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif'
                        ),
                        'unique_filename_callback' => function($dir, $name, $ext) {
                            return 'gc_' . time() . '_' . wp_generate_uuid4() . $ext;
                        }
                    ));
                    
                    if ($upload && !isset($upload['error'])) {
                        $anexos_atuais[] = $upload['url'];
                        error_log('GC Debug editar: Novo anexo adicionado: ' . $upload['url']);
                    } else {
                        error_log('GC Debug editar: Erro no upload: ' . print_r($upload, true));
                    }
                }
            }
        }
        
        // Atualizar anexos se houver
        if (!empty($anexos_atuais)) {
            $dados_atualizacao['anexos'] = $anexos_atuais;
        } else {
            $dados_atualizacao['anexos'] = null;
        }
        
        try {
            $resultado = GC_Lancamento::atualizar($lancamento_id, $dados_atualizacao);
            
            if (is_wp_error($resultado)) {
                wp_send_json_error($resultado->get_error_message());
            } else {
                wp_send_json_success(array(
                    'id' => $lancamento_id,
                    'message' => __('Lançamento atualizado com sucesso!', 'gestao-coletiva')
                ));
            }
        } catch (Error $e) {
            error_log('Gestão Coletiva - Erro ao editar lançamento: ' . $e->getMessage());
            wp_send_json_error(__('Erro ao editar lançamento', 'gestao-coletiva') . ': ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Gestão Coletiva - Exceção ao editar lançamento: ' . $e->getMessage());
            wp_send_json_error(__('Erro ao editar lançamento', 'gestao-coletiva') . ': ' . $e->getMessage());
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
            'logo_url',
            'prazo_efetivacao_horas',
            'prazo_resposta_contestacao_horas',
            'prazo_analise_resposta_horas',
            'prazo_publicacao_disputa_horas',
            'prazo_resolucao_disputa_dias',
            'texto_agradecimento_certificado',
            'chave_pix',
            'nome_beneficiario_pix'
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
        $lancamento = GC_Lancamento::obter($id);
        
        if (!$lancamento) {
            wp_send_json_error(__('Lançamento não encontrado', 'gestao-coletiva'));
        }
        
        // Verificar permissões: apenas autor da doação ou administradores
        $user_id = get_current_user_id();
        $pode_gerar = ($lancamento->autor_id == $user_id) || current_user_can('manage_options');
        
        if (!$pode_gerar) {
            wp_send_json_error(__('Você não tem permissão para gerar este certificado', 'gestao-coletiva'));
        }
        
        $certificado = GC_Lancamento::gerar_certificado($id);
        
        if (!$certificado) {
            wp_send_json_error(__('Não foi possível gerar o certificado', 'gestao-coletiva'));
        }
        
        wp_send_json_success($certificado);
    }
    
    public function ajax_verificar_certificado() {
        if (!isset($_POST['numero_unico'])) {
            wp_send_json_error(__('Número do certificado não informado', 'gestao-coletiva'));
        }
        
        $numero_unico = sanitize_text_field($_POST['numero_unico']);
        
        if (empty($numero_unico)) {
            wp_send_json_error(__('Número do certificado não informado', 'gestao-coletiva'));
        }
        
        // Buscar lançamento pelo número único
        $lancamento = GC_Lancamento::obter_por_numero($numero_unico);
        
        if (!$lancamento) {
            wp_send_json_error(__('Certificado não encontrado', 'gestao-coletiva'));
        }
        
        // Verificar se o lançamento permite gerar certificado
        $certificado = GC_Lancamento::gerar_certificado($lancamento->id);
        
        if (!$certificado) {
            wp_send_json_error(__('Certificado não disponível para este lançamento', 'gestao-coletiva'));
        }
        
        // Retornar dados do certificado para verificação
        $verificacao = array(
            'valido' => true,
            'numero_unico' => $certificado['numero_unico'],
            'autor' => $certificado['autor'],
            'valor' => $certificado['valor'],
            'data_efetivacao' => $certificado['data_efetivacao'],
            'tipo' => $certificado['tipo'],
            'descricao_curta' => $certificado['descricao_curta'],
            'recorrencia' => $certificado['recorrencia'],
            'recorrencia_ativa' => $certificado['recorrencia_ativa'],
            'organizacao' => $certificado['organizacao']
        );
        
        wp_send_json_success($verificacao);
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
            
            // Decodificar anexos se existir
            if ($lancamento->anexos && is_string($lancamento->anexos)) {
                $lancamento->anexos = json_decode($lancamento->anexos, true);
            }
            if (!is_array($lancamento->anexos)) {
                $lancamento->anexos = array();
            }
            
            // Verificar permissões para ações
            $estados_certificado = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
            $user_id = get_current_user_id();
            $eh_autor = ($lancamento->autor_id == $user_id);
            $eh_admin = current_user_can('manage_options');
            $pode_gerar_certificado = in_array($lancamento->estado, $estados_certificado) && 
                                    $lancamento->tipo === 'receita' && 
                                    ($eh_autor || $eh_admin);
            
            $estados_contestaveis = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
            $pode_contestar = is_user_logged_in() && in_array($lancamento->estado, $estados_contestaveis);
            
            // Dados estruturados para o JavaScript
            $dados = array(
                'id' => $lancamento->id,
                'numero_unico' => $lancamento->numero_unico,
                'tipo' => $lancamento->tipo,
                'estado' => $lancamento->estado,
                'valor' => $lancamento->valor,
                'descricao_curta' => $lancamento->descricao_curta,
                'descricao_detalhada' => $lancamento->descricao_detalhada,
                'data_criacao' => $lancamento->data_criacao,
                'data_efetivacao' => $lancamento->data_efetivacao,
                'anexos' => $lancamento->anexos,
                'pode_gerar_certificado' => $pode_gerar_certificado,
                'pode_contestar' => $pode_contestar
            );
            
            wp_send_json_success($dados);
        } catch (Error $e) {
            wp_send_json_error(__('Erro ao buscar lançamento. Contate o administrador.', 'gestao-coletiva'));
        }
    }
    
    public function ajax_get_pix_info() {
        // Não precisa verificar nonce para informações PIX públicas
        
        try {
            $chave_pix = GC_Database::get_setting('chave_pix');
            $nome_beneficiario = GC_Database::get_setting('nome_beneficiario_pix');
            
            $data = array(
                'chave_pix' => $chave_pix,
                'nome_beneficiario' => $nome_beneficiario
            );
            
            wp_send_json_success($data);
        } catch (Error $e) {
            wp_send_json_error(__('Erro ao buscar informações PIX.', 'gestao-coletiva'));
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
    
    public function ajax_gerar_relatorio_previsao() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        $data_inicio = sanitize_text_field($_POST['data_inicio']);
        $data_fim = sanitize_text_field($_POST['data_fim']);
        
        if (empty($data_inicio) || empty($data_fim)) {
            wp_send_json_error(__('Datas não informadas', 'gestao-coletiva'));
        }
        
        $relatorio = GC_Relatorio::gerar_relatorio_previsao($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        
        // Renderizar HTML do relatório
        ob_start();
        include GC_PLUGIN_PATH . 'admin/views/relatorios/previsao.php';
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    public function ajax_processar_vencimentos_manual() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $lancamentos_processados = GC_Lancamento::processar_vencimentos();
        $recorrencias_criadas = GC_Lancamento::processar_recorrencias();
        $contestacoes_processadas = GC_Contestacao::processar_vencimentos();
        
        $message = sprintf(
            __('Processamento concluído! %d lançamentos processados, %d recorrências criadas e %d contestações processadas.', 'gestao-coletiva'),
            $lancamentos_processados,
            $recorrencias_criadas,
            $contestacoes_processadas
        );
        
        wp_send_json_success($message);
    }
    
    public function processar_vencimentos_cron() {
        $lancamentos_processados = GC_Lancamento::processar_vencimentos();
        $recorrencias_criadas = GC_Lancamento::processar_recorrencias();
        $contestacoes_processadas = GC_Contestacao::processar_vencimentos();
        
        if ($lancamentos_processados > 0 || $recorrencias_criadas > 0 || $contestacoes_processadas > 0) {
            error_log("Gestão Coletiva: Processados {$lancamentos_processados} lançamentos, {$recorrencias_criadas} recorrências criadas e {$contestacoes_processadas} contestações vencidas.");
        }
    }
    
    public function ajax_limpar_periodo() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $data_inicial = sanitize_text_field($_POST['data_inicial']);
        $data_final = sanitize_text_field($_POST['data_final']);
        
        if (empty($data_inicial) || empty($data_final)) {
            wp_send_json_error(__('Datas inicial e final são obrigatórias', 'gestao-coletiva'));
        }
        
        if (strtotime($data_inicial) > strtotime($data_final)) {
            wp_send_json_error(__('Data inicial não pode ser maior que data final', 'gestao-coletiva'));
        }
        
        $resultado = GC_Database::limpar_lancamentos_periodo($data_inicial, $data_final);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        }
        
        $message = sprintf(
            __('Limpeza concluída! %d lançamentos foram removidos do período selecionado.', 'gestao-coletiva'),
            $resultado['lancamentos_removidos']
        );
        
        wp_send_json_success($message);
    }
    
    public function ajax_limpar_tudo() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $confirmacao = sanitize_text_field($_POST['confirmacao']);
        
        if ($confirmacao !== 'confirmar') {
            wp_send_json_error(__('Confirmação necessária para prosseguir com a limpeza completa', 'gestao-coletiva'));
        }
        
        $resultado = GC_Database::limpar_todos_dados();
        
        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        }
        
        $message = sprintf(
            __('Limpeza completa realizada! %d registros foram removidos de %d tabelas. As configurações padrão foram restauradas.', 'gestao-coletiva'),
            $resultado['registros_removidos'],
            $resultado['tabelas_limpas']
        );
        
        wp_send_json_success($message);
    }
    
    public function ajax_finalizar_disputa() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $contestacao_id = intval($_POST['contestacao_id']);
        $link_postagem = sanitize_url($_POST['link_postagem']);
        $link_votacao = sanitize_url($_POST['link_votacao']);
        
        if (empty($contestacao_id)) {
            wp_send_json_error(__('ID da contestação é obrigatório', 'gestao-coletiva'));
        }
        
        if (empty($link_postagem)) {
            wp_send_json_error(__('Link da postagem no blog é obrigatório', 'gestao-coletiva'));
        }
        
        if (empty($link_votacao)) {
            wp_send_json_error(__('Link do formulário de votação é obrigatório', 'gestao-coletiva'));
        }
        
        // Validar URLs
        if (!filter_var($link_postagem, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Link da postagem deve ser uma URL válida', 'gestao-coletiva'));
        }
        
        if (!filter_var($link_votacao, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Link do formulário deve ser uma URL válida', 'gestao-coletiva'));
        }
        
        $resultado = GC_Contestacao::finalizar_disputa($contestacao_id, $link_postagem, $link_votacao);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        }
        
        wp_send_json_success($resultado['message']);
    }
    
    public function ajax_registrar_resultado() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $contestacao_id = intval($_POST['contestacao_id']);
        $resultado_votacao = sanitize_text_field($_POST['resultado_votacao']);
        $observacoes = sanitize_textarea_field($_POST['observacoes']);
        
        if (empty($contestacao_id)) {
            wp_send_json_error(__('ID da contestação é obrigatório', 'gestao-coletiva'));
        }
        
        if (empty($resultado_votacao)) {
            wp_send_json_error(__('Resultado da votação é obrigatório', 'gestao-coletiva'));
        }
        
        if (!in_array($resultado_votacao, ['contestacao_procedente', 'contestacao_improcedente'])) {
            wp_send_json_error(__('Resultado da votação é inválido', 'gestao-coletiva'));
        }
        
        $resultado = GC_Contestacao::registrar_resultado_votacao($contestacao_id, $resultado_votacao, $observacoes);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        }
        
        wp_send_json_success($resultado['message']);
    }
    
    public function ajax_ver_contestacao() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $contestacao_id = intval($_POST['contestacao_id']);
        
        if (empty($contestacao_id)) {
            wp_send_json_error(__('ID da contestação é obrigatório', 'gestao-coletiva'));
        }
        
        $contestacao = GC_Contestacao::obter($contestacao_id);
        
        if (!$contestacao) {
            wp_send_json_error(__('Contestação não encontrada', 'gestao-coletiva'));
        }
        
        // Buscar dados do lançamento
        $lancamento = GC_Lancamento::obter($contestacao->lancamento_id);
        if (!$lancamento) {
            wp_send_json_error(__('Lançamento não encontrado', 'gestao-coletiva'));
        }
        
        // Buscar dados do autor da contestação
        $autor_contestacao = get_user_by('ID', $contestacao->autor_id);
        $autor_lancamento = get_user_by('ID', $lancamento->autor_id);
        
        // Montar dados para resposta
        $dados = array(
            'contestacao' => array(
                'id' => $contestacao->id,
                'tipo' => $contestacao->tipo,
                'descricao' => $contestacao->descricao,
                'comprovante' => $contestacao->comprovante,
                'estado' => $contestacao->estado,
                'data_criacao' => $contestacao->data_criacao,
                'data_resposta' => $contestacao->data_resposta,
                'data_analise' => $contestacao->data_analise,
                'data_finalizacao_disputa' => $contestacao->data_finalizacao_disputa,
                'data_resolucao_final' => $contestacao->data_resolucao_final,
                'resposta' => $contestacao->resposta,
                'link_postagem_blog' => $contestacao->link_postagem_blog,
                'link_formulario_votacao' => $contestacao->link_formulario_votacao,
                'resultado_votacao' => $contestacao->resultado_votacao,
                'observacoes_finais' => $contestacao->observacoes_finais,
                'autor_nome' => $autor_contestacao ? $autor_contestacao->display_name : 'Usuário removido'
            ),
            'lancamento' => array(
                'numero_unico' => $lancamento->numero_unico,
                'tipo' => $lancamento->tipo,
                'descricao_curta' => $lancamento->descricao_curta,
                'valor' => $lancamento->valor,
                'estado' => $lancamento->estado,
                'data_criacao' => $lancamento->data_criacao,
                'autor_nome' => $autor_lancamento ? $autor_lancamento->display_name : 'Usuário removido'
            ),
            'pode_responder' => GC_Contestacao::pode_responder($contestacao_id),
            'pode_analisar' => GC_Contestacao::pode_analisar($contestacao_id),
            'pode_finalizar' => current_user_can('manage_options') && $contestacao->estado === 'em_disputa',
            'pode_registrar_resultado' => current_user_can('manage_options') && $contestacao->estado === 'votacao_aberta'
        );
        
        wp_send_json_success($dados);
    }
    
    public function ajax_cancelar_recorrencia() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        $lancamento_id = intval($_POST['id']);
        
        if (!$lancamento_id) {
            wp_send_json_error(__('ID do lançamento não fornecido', 'gestao-coletiva'));
            return;
        }
        
        $lancamento = GC_Lancamento::obter($lancamento_id);
        if (!$lancamento) {
            wp_send_json_error(__('Lançamento não encontrado', 'gestao-coletiva'));
            return;
        }
        
        // Verificar permissões: autor do lançamento ou administrador
        if (!current_user_can('manage_options') && $lancamento->autor_id != get_current_user_id()) {
            wp_send_json_error(__('Você não tem permissão para cancelar esta recorrência', 'gestao-coletiva'));
            return;
        }
        
        if ($lancamento->recorrencia === 'unica') {
            wp_send_json_error(__('Este lançamento não é recorrente', 'gestao-coletiva'));
            return;
        }
        
        $resultado = GC_Lancamento::cancelar_recorrencia($lancamento_id);
        
        if ($resultado) {
            wp_send_json_success(__('Recorrência cancelada com sucesso', 'gestao-coletiva'));
        } else {
            wp_send_json_error(__('Erro ao cancelar recorrência', 'gestao-coletiva'));
        }
    }
    
    public function ajax_corrigir_inconsistencias() {
        check_ajax_referer('gc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'gestao-coletiva'));
        }
        
        $resultado = GC_Contestacao::corrigir_estados_inconsistentes();
        
        $message = sprintf(
            __('%d contestações foram corrigidas de %d encontradas com inconsistências.', 'gestao-coletiva'),
            $resultado['corrigidas'],
            $resultado['encontradas']
        );
        
        wp_send_json_success($message);
    }
}