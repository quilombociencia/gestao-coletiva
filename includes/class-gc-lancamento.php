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
        
        $lancamento = array(
            'numero_unico' => $numero_unico,
            'tipo' => sanitize_text_field($dados['tipo']),
            'descricao_curta' => sanitize_text_field($dados['descricao_curta']),
            'descricao_detalhada' => sanitize_textarea_field($dados['descricao_detalhada']),
            'valor' => floatval($dados['valor']),
            'recorrencia' => isset($dados['recorrencia']) ? sanitize_text_field($dados['recorrencia']) : 'unica',
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
            $lancamento->anexos = json_decode($lancamento->anexos, true);
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
            $lancamento->anexos = json_decode($lancamento->anexos, true);
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
                $lancamento->anexos = json_decode($lancamento->anexos, true);
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
        
        $certificado = array(
            'numero_unico' => $lancamento->numero_unico,
            'tipo' => $lancamento->tipo,
            'autor' => $autor->display_name,
            'descricao_curta' => $lancamento->descricao_curta,
            'descricao_detalhada' => $lancamento->descricao_detalhada,
            'valor' => $lancamento->valor,
            'data_efetivacao' => $lancamento->data_efetivacao,
            'texto_agradecimento' => $texto_agradecimento
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
}