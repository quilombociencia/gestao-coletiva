<?php

if (!defined('ABSPATH')) {
    exit;
}

class GC_Lancamento {
    
    public static function criar($dados) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $numero_unico = GC_Database::generate_unique_number();
        
        $prazo_efetivacao = intval(GC_Database::get_setting('prazo_efetivacao_horas'));
        $data_expiracao = date('Y-m-d H:i:s', strtotime("+{$prazo_efetivacao} hours"));
        
        $recorrencia = isset($dados['recorrencia']) ? sanitize_text_field($dados['recorrencia']) : 'unica';
        $data_proxima_recorrencia = null;
        
        // Calcular próxima recorrência se não for única
        if ($recorrencia !== 'unica') {
            $data_proxima_recorrencia = self::calcular_proxima_recorrencia(current_time('mysql'), $recorrencia);
        }
        
        $lancamento = array(
            'numero_unico' => $numero_unico,
            'tipo' => sanitize_text_field($dados['tipo']),
            'descricao_curta' => sanitize_text_field($dados['descricao_curta']),
            'descricao_detalhada' => sanitize_textarea_field($dados['descricao_detalhada']),
            'valor' => floatval($dados['valor']),
            'recorrencia' => $recorrencia,
            'lancamento_pai_id' => isset($dados['lancamento_pai_id']) ? intval($dados['lancamento_pai_id']) : null,
            'data_proxima_recorrencia' => $data_proxima_recorrencia,
            'recorrencia_ativa' => ($recorrencia !== 'unica') ? 1 : 0,
            'estado' => 'previsto',
            'autor_id' => get_current_user_id(),
            'data_criacao' => current_time('mysql'),
            'data_expiracao' => $data_expiracao,
            'prazo_atual' => $data_expiracao,
            'tipo_prazo' => 'efetivacao',
            'anexos' => isset($dados['anexos']) ? json_encode($dados['anexos']) : null
        );
        
        $result = $wpdb->insert($table_name, $lancamento);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erro ao criar lançamento');
        }
        
        return $wpdb->insert_id;
    }
    
    public static function obter($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $lancamento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
        
        if ($lancamento && $lancamento->anexos) {
            error_log('GC Debug: Anexos antes decodificação: ' . $lancamento->anexos);
            $lancamento->anexos = json_decode($lancamento->anexos, true);
            error_log('GC Debug: Anexos após decodificação: ' . print_r($lancamento->anexos, true));
        } else {
            error_log('GC Debug: Sem anexos ou lançamento não encontrado');
        }
        
        return $lancamento;
    }
    
    public static function obter_por_numero($numero_unico) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $lancamento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE numero_unico = %s",
            $numero_unico
        ));
        
        if ($lancamento && $lancamento->anexos) {
            error_log('GC Debug: Anexos antes decodificação: ' . $lancamento->anexos);
            $lancamento->anexos = json_decode($lancamento->anexos, true);
            error_log('GC Debug: Anexos após decodificação: ' . print_r($lancamento->anexos, true));
        } else {
            error_log('GC Debug: Sem anexos ou lançamento não encontrado');
        }
        
        return $lancamento;
    }
    
    public static function listar($filtros = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        $where = array('1=1');
        $params = array();
        
        if (isset($filtros['tipo'])) {
            $where[] = 'tipo = %s';
            $params[] = $filtros['tipo'];
        }
        
        if (isset($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = $filtros['estado'];
        }
        
        if (isset($filtros['data_inicio']) && isset($filtros['data_fim'])) {
            $where[] = 'data_criacao BETWEEN %s AND %s';
            $params[] = $filtros['data_inicio'];
            $params[] = $filtros['data_fim'];
        }
        
        if (isset($filtros['autor_id'])) {
            $where[] = 'autor_id = %d';
            $params[] = $filtros['autor_id'];
        }
        
        if (isset($filtros['estados_realizados']) && $filtros['estados_realizados']) {
            $where[] = "estado IN ('efetivado', 'confirmado', 'aceito', 'retificado_comunidade')";
        }
        
        $order = isset($filtros['order']) ? $filtros['order'] : 'data_criacao DESC';
        $limit = isset($filtros['limit']) ? intval($filtros['limit']) : null;
        
        $sql = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . " ORDER BY $order";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $lancamentos = $wpdb->get_results($sql);
        
        foreach ($lancamentos as $lancamento) {
            if ($lancamento->anexos) {
                error_log('GC Debug listar(): ID ' . $lancamento->id . ' - Anexos antes: ' . $lancamento->anexos);
                $lancamento->anexos = json_decode($lancamento->anexos, true);
                error_log('GC Debug listar(): ID ' . $lancamento->id . ' - Anexos depois: ' . print_r($lancamento->anexos, true));
            }
        }
        
        return $lancamentos;
    }
    
    public static function atualizar_estado($id, $novo_estado, $dados_extras = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $update_data = array('estado' => $novo_estado);
        
        switch ($novo_estado) {
            case 'efetivado':
                $update_data['data_efetivacao'] = current_time('mysql');
                break;
                
            case 'em_contestacao':
                $prazo_resposta = intval(GC_Database::get_setting('prazo_resposta_contestacao_horas'));
                $update_data['prazo_atual'] = date('Y-m-d H:i:s', strtotime("+{$prazo_resposta} hours"));
                $update_data['tipo_prazo'] = 'resposta_contestacao';
                break;
                
            case 'confirmado':
                $prazo_analise = intval(GC_Database::get_setting('prazo_analise_resposta_horas'));
                $update_data['prazo_atual'] = date('Y-m-d H:i:s', strtotime("+{$prazo_analise} hours"));
                $update_data['tipo_prazo'] = 'analise_resposta';
                break;
                
            case 'em_disputa':
                $prazo_publicacao = intval(GC_Database::get_setting('prazo_publicacao_disputa_horas'));
                $update_data['prazo_atual'] = date('Y-m-d H:i:s', strtotime("+{$prazo_publicacao} hours"));
                $update_data['tipo_prazo'] = 'publicacao_disputa';
                break;
        }
        
        if (!empty($dados_extras)) {
            $update_data = array_merge($update_data, $dados_extras);
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
    }
    
    public static function pode_editar($id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $lancamento = self::obter($id);
        
        if (!$lancamento) {
            return false;
        }
        
        if (current_user_can('manage_options')) {
            return true;
        }
        
        if ($lancamento->autor_id != $user_id) {
            return false;
        }
        
        return in_array($lancamento->estado, array('previsto'));
    }
    
    public static function atualizar($id, $dados) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Verificar se o lançamento existe
        $lancamento = self::obter($id);
        if (!$lancamento) {
            return new WP_Error('not_found', 'Lançamento não encontrado');
        }
        
        // Verificar permissões
        if (!self::pode_editar($id)) {
            return new WP_Error('permission_denied', 'Você não tem permissão para editar este lançamento');
        }
        
        // Preparar dados para atualização
        $update_data = array();
        
        if (isset($dados['tipo'])) {
            $update_data['tipo'] = sanitize_text_field($dados['tipo']);
        }
        
        if (isset($dados['descricao_curta'])) {
            $update_data['descricao_curta'] = sanitize_text_field($dados['descricao_curta']);
        }
        
        if (isset($dados['descricao_detalhada'])) {
            $update_data['descricao_detalhada'] = sanitize_textarea_field($dados['descricao_detalhada']);
        }
        
        if (isset($dados['valor'])) {
            $update_data['valor'] = floatval($dados['valor']);
        }
        
        if (isset($dados['recorrencia'])) {
            $update_data['recorrencia'] = sanitize_text_field($dados['recorrencia']);
        }
        
        // Processar anexos se fornecidos
        if (isset($dados['anexos'])) {
            $anexos_atuais = is_array($lancamento->anexos) ? $lancamento->anexos : array();
            
            // Remover anexos marcados para remoção
            if (isset($dados['remover_anexos']) && is_array($dados['remover_anexos'])) {
                foreach ($dados['remover_anexos'] as $index) {
                    $index = intval($index);
                    if (isset($anexos_atuais[$index])) {
                        // Tentar remover o arquivo do servidor
                        $file_path = str_replace(site_url(), ABSPATH, $anexos_atuais[$index]);
                        if (file_exists($file_path)) {
                            wp_delete_file($file_path);
                        }
                        unset($anexos_atuais[$index]);
                    }
                }
                // Reindexar o array
                $anexos_atuais = array_values($anexos_atuais);
            }
            
            // Adicionar novos anexos
            if (is_array($dados['anexos']) && !empty($dados['anexos'])) {
                $anexos_atuais = array_merge($anexos_atuais, $dados['anexos']);
            }
            
            $update_data['anexos'] = json_encode($anexos_atuais);
        }
        
        // Se não há dados para atualizar, retornar erro
        if (empty($update_data)) {
            return new WP_Error('no_data', 'Nenhum dado fornecido para atualização');
        }
        
        // Atualizar no banco de dados
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id),
            array('%s', '%s', '%s', '%f', '%s', '%s'), // Formats para os dados
            array('%d') // Format para o ID
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erro ao atualizar lançamento no banco de dados');
        }
        
        error_log('GC Debug: Lançamento ' . $id . ' atualizado com sucesso. Dados: ' . print_r($update_data, true));
        
        return true;
    }
    
    public static function calcular_saldo_periodo($data_inicio, $data_fim, $incluir_previstos = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Estados que representam valores efetivamente confirmados/realizados
        $estados = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
        
        if ($incluir_previstos) {
            $estados[] = 'previsto';
        }
        
        $estados_str = "'" . implode("','", $estados) . "'";
        
        $receitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'receita' 
             AND estado IN ($estados_str)
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio,
            $data_fim
        ));
        
        $despesas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'despesa' 
             AND estado IN ($estados_str)
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio,
            $data_fim
        ));
        
        return array(
            'receitas' => floatval($receitas),
            'despesas' => floatval($despesas),
            'saldo' => floatval($receitas) - floatval($despesas)
        );
    }
    
    public static function gerar_certificado($id) {
        $lancamento = self::obter($id);
        
        // Estados que permitem gerar certificado (valores confirmados/realizados)
        $estados_validos = array('efetivado', 'confirmado', 'aceito', 'retificado_comunidade');
        
        if (!$lancamento || !in_array($lancamento->estado, $estados_validos)) {
            return false;
        }
        
        $autor = get_user_by('ID', $lancamento->autor_id);
        $texto_agradecimento = GC_Database::get_setting('texto_agradecimento_certificado');
        $logo_url = GC_Database::get_setting('logo_url');
        
        // Gerar URL para verificação do certificado
        $verificacao_url = add_query_arg(array(
            'verificar_certificado' => $lancamento->numero_unico
        ), home_url());
        
        // Gerar QR Code usando API pública
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verificacao_url);
        
        $certificado = array(
            'numero_unico' => $lancamento->numero_unico,
            'tipo' => $lancamento->tipo,
            'autor' => $autor->display_name,
            'autor_email' => $autor->user_email,
            'descricao_curta' => $lancamento->descricao_curta,
            'descricao_detalhada' => $lancamento->descricao_detalhada,
            'valor' => $lancamento->valor,
            'recorrencia' => $lancamento->recorrencia,
            'recorrencia_ativa' => $lancamento->recorrencia_ativa,
            'data_efetivacao' => $lancamento->data_efetivacao,
            'data_criacao' => $lancamento->data_criacao,
            'texto_agradecimento' => $texto_agradecimento,
            'logo_url' => $logo_url,
            'qr_code_url' => $qr_code_url,
            'verificacao_url' => $verificacao_url,
            'organizacao' => get_bloginfo('name'),
            'site_url' => home_url()
        );
        
        return $certificado;
    }
    
    public static function processar_vencimentos() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        $agora = current_time('mysql');
        
        $vencidos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, estado, tipo_prazo FROM $table_name 
             WHERE prazo_atual < %s 
             AND estado IN ('previsto', 'em_contestacao', 'confirmado', 'em_disputa')",
            $agora
        ));
        
        foreach ($vencidos as $lancamento) {
            switch ($lancamento->estado) {
                case 'previsto':
                    self::atualizar_estado($lancamento->id, 'expirado');
                    break;
                    
                case 'em_contestacao':
                    if ($lancamento->tipo_prazo === 'resposta_contestacao') {
                        self::atualizar_estado($lancamento->id, 'contestado');
                    }
                    break;
                    
                case 'confirmado':
                    if ($lancamento->tipo_prazo === 'analise_resposta') {
                        self::atualizar_estado($lancamento->id, 'aceito');
                    }
                    break;
                    
                case 'em_disputa':
                    break;
            }
        }
        
        return count($vencidos);
    }
    
    /**
     * Calcula a próxima data de recorrência
     */
    public static function calcular_proxima_recorrencia($data_base, $tipo_recorrencia) {
        $data = new DateTime($data_base);
        
        switch ($tipo_recorrencia) {
            case 'mensal':
                $data->add(new DateInterval('P1M'));
                break;
            case 'trimestral':
                $data->add(new DateInterval('P3M'));
                break;
            case 'anual':
                $data->add(new DateInterval('P1Y'));
                break;
            default:
                return null;
        }
        
        return $data->format('Y-m-d H:i:s');
    }
    
    /**
     * Processa lançamentos recorrentes que estão na data
     */
    public static function processar_recorrencias() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        $agora = current_time('mysql');
        
        // Buscar lançamentos recorrentes que precisam gerar nova ocorrência
        $recorrentes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE recorrencia != 'unica' 
             AND recorrencia_ativa = 1 
             AND data_proxima_recorrencia <= %s 
             AND data_proxima_recorrencia IS NOT NULL
             ORDER BY data_proxima_recorrencia ASC",
            $agora
        ));
        
        $lancamentos_criados = 0;
        
        foreach ($recorrentes as $lancamento_original) {
            // Determinar se este é o lançamento pai ou um filho
            $lancamento_pai_id = $lancamento_original->lancamento_pai_id ?: $lancamento_original->id;
            
            // Criar novo lançamento recorrente
            $novo_lancamento_id = self::criar_recorrencia($lancamento_original, $lancamento_pai_id);
            
            if ($novo_lancamento_id) {
                $lancamentos_criados++;
                
                // Atualizar data da próxima recorrência no lançamento atual
                $proxima_recorrencia = self::calcular_proxima_recorrencia(
                    $lancamento_original->data_proxima_recorrencia, 
                    $lancamento_original->recorrencia
                );
                
                $wpdb->update(
                    $table_name,
                    array('data_proxima_recorrencia' => $proxima_recorrencia),
                    array('id' => $lancamento_original->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        return $lancamentos_criados;
    }
    
    /**
     * Processa recorrências até uma data específica (para relatórios futuros)
     */
    public static function processar_recorrencias_ate_data($data_limite) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Buscar lançamentos recorrentes que precisam gerar ocorrências até a data limite
        $recorrentes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE recorrencia != 'unica' 
             AND recorrencia_ativa = 1 
             AND data_proxima_recorrencia <= %s 
             AND data_proxima_recorrencia IS NOT NULL
             ORDER BY data_proxima_recorrencia ASC",
            $data_limite
        ));
        
        $lancamentos_criados = 0;
        
        foreach ($recorrentes as $lancamento_original) {
            $contador_seguranca = 0;
            $max_iteracoes = 100; // Limite de segurança
            
            // Continuar criando até a data limite ou atingir o máximo
            while ($lancamento_original->data_proxima_recorrencia <= $data_limite && 
                   $contador_seguranca < $max_iteracoes) {
                
                // Verificar se já existe um lançamento para esta data
                $ja_existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name 
                     WHERE lancamento_pai_id = %d 
                     AND DATE(data_criacao) = %s",
                    $lancamento_original->lancamento_pai_id ?: $lancamento_original->id,
                    date('Y-m-d', strtotime($lancamento_original->data_proxima_recorrencia))
                ));
                
                if (!$ja_existe) {
                    // Determinar se este é o lançamento pai ou um filho
                    $lancamento_pai_id = $lancamento_original->lancamento_pai_id ?: $lancamento_original->id;
                    
                    // Criar novo lançamento recorrente
                    $novo_lancamento_id = self::criar_recorrencia($lancamento_original, $lancamento_pai_id);
                    
                    if ($novo_lancamento_id) {
                        $lancamentos_criados++;
                    }
                }
                
                // Calcular próxima recorrência
                $proxima_recorrencia = self::calcular_proxima_recorrencia(
                    $lancamento_original->data_proxima_recorrencia, 
                    $lancamento_original->recorrencia
                );
                
                // Atualizar data para continuar o loop
                $lancamento_original->data_proxima_recorrencia = $proxima_recorrencia;
                $contador_seguranca++;
            }
            
            // Atualizar a data no banco apenas se criamos algum lançamento
            if ($contador_seguranca > 0) {
                $wpdb->update(
                    $table_name,
                    array('data_proxima_recorrencia' => $lancamento_original->data_proxima_recorrencia),
                    array('id' => $lancamento_original->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        return $lancamentos_criados;
    }
    
    /**
     * Cria um novo lançamento baseado em uma recorrência
     */
    private static function criar_recorrencia($lancamento_original, $lancamento_pai_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $numero_unico = GC_Database::generate_unique_number();
        
        $prazo_efetivacao = intval(GC_Database::get_setting('prazo_efetivacao_horas'));
        $data_recorrencia = $lancamento_original->data_proxima_recorrencia;
        $data_expiracao = date('Y-m-d H:i:s', strtotime($data_recorrencia . " +{$prazo_efetivacao} hours"));
        
        // Calcular próxima recorrência para este novo lançamento
        $proxima_recorrencia = self::calcular_proxima_recorrencia($data_recorrencia, $lancamento_original->recorrencia);
        
        $lancamento = array(
            'numero_unico' => $numero_unico,
            'tipo' => $lancamento_original->tipo,
            'descricao_curta' => $lancamento_original->descricao_curta,
            'descricao_detalhada' => $lancamento_original->descricao_detalhada,
            'valor' => floatval($lancamento_original->valor),
            'recorrencia' => $lancamento_original->recorrencia,
            'lancamento_pai_id' => $lancamento_pai_id,
            'data_proxima_recorrencia' => $proxima_recorrencia,
            'recorrencia_ativa' => 1,
            'estado' => 'previsto',
            'autor_id' => $lancamento_original->autor_id,
            'data_criacao' => $data_recorrencia, // Data da recorrência, não hoje!
            'data_expiracao' => $data_expiracao,
            'prazo_atual' => $data_expiracao,
            'tipo_prazo' => 'efetivacao',
            'anexos' => $lancamento_original->anexos
        );
        
        $result = $wpdb->insert($table_name, $lancamento);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Cancela a recorrência de um lançamento
     */
    public static function cancelar_recorrencia($lancamento_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Obter o lançamento
        $lancamento = self::obter($lancamento_id);
        if (!$lancamento) {
            return false;
        }
        
        // Determinar ID do lançamento pai
        $lancamento_pai_id = $lancamento->lancamento_pai_id ?: $lancamento->id;
        
        // Cancelar recorrência em toda a série
        $wpdb->update(
            $table_name,
            array(
                'recorrencia_ativa' => 0,
                'data_proxima_recorrencia' => null
            ),
            array('id' => $lancamento_pai_id),
            array('%d', '%s'),
            array('%d')
        );
        
        // Cancelar também nos lançamentos filhos
        $wpdb->update(
            $table_name,
            array(
                'recorrencia_ativa' => 0,
                'data_proxima_recorrencia' => null
            ),
            array('lancamento_pai_id' => $lancamento_pai_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return true;
    }
    
    /**
     * Lista lançamentos de uma série de recorrência
     */
    public static function listar_serie_recorrencia($lancamento_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $lancamento = self::obter($lancamento_id);
        if (!$lancamento) {
            return array();
        }
        
        $lancamento_pai_id = $lancamento->lancamento_pai_id ?: $lancamento->id;
        
        // Buscar todos os lançamentos da série
        $serie = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE (id = %d OR lancamento_pai_id = %d)
             ORDER BY data_criacao ASC",
            $lancamento_pai_id,
            $lancamento_pai_id
        ));
        
        return $serie;
    }
}