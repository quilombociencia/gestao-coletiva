<?php

if (!defined('ABSPATH')) {
    exit;
}

class GC_Contestacao {
    
    public static function criar($dados) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        $contestacao = array(
            'lancamento_id' => intval($dados['lancamento_id']),
            'autor_id' => get_current_user_id(),
            'tipo' => sanitize_text_field($dados['tipo']),
            'descricao' => sanitize_textarea_field($dados['descricao']),
            'comprovante' => isset($dados['comprovante']) ? sanitize_text_field($dados['comprovante']) : null,
            'estado' => 'pendente',
            'data_criacao' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $contestacao);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erro ao criar contestação');
        }
        
        GC_Lancamento::atualizar_estado($dados['lancamento_id'], 'em_contestacao');
        
        return $wpdb->insert_id;
    }
    
    public static function obter($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }
    
    public static function listar($filtros = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        $where = array('1=1');
        $params = array();
        
        if (isset($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = $filtros['estado'];
        }
        
        if (isset($filtros['lancamento_id'])) {
            $where[] = 'lancamento_id = %d';
            $params[] = $filtros['lancamento_id'];
        }
        
        if (isset($filtros['autor_id'])) {
            $where[] = 'autor_id = %d';
            $params[] = $filtros['autor_id'];
        }
        
        $order = isset($filtros['order']) ? $filtros['order'] : 'data_criacao DESC';
        $limit = isset($filtros['limit']) ? intval($filtros['limit']) : null;
        
        $sql = "SELECT c.*, l.numero_unico, l.descricao_curta as lancamento_descricao, l.valor as lancamento_valor 
                FROM $table_name c 
                JOIN {$wpdb->prefix}gc_lancamentos l ON c.lancamento_id = l.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY $order";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function responder($id, $resposta, $novo_estado) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        $update_data = array(
            'resposta' => sanitize_textarea_field($resposta),
            'estado' => $novo_estado,
            'data_resposta' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
        
        if ($result !== false) {
            $contestacao = self::obter($id);
            
            if ($novo_estado === 'respondida') {
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'confirmado');
            } elseif ($novo_estado === 'aceita') {
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'contestado');
            }
        }
        
        return $result;
    }
    
    public static function analisar($id, $aceitar) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        $contestacao = self::obter($id);
        
        if (!$contestacao || $contestacao->estado !== 'respondida') {
            return false;
        }
        
        $novo_estado = $aceitar ? 'aceita' : 'rejeitada';
        
        $update_data = array(
            'estado' => $novo_estado,
            'data_analise' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
        
        if ($result !== false) {
            if ($aceitar) {
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'aceito');
            } else {
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'em_disputa');
            }
        }
        
        return $result;
    }
    
    public static function pode_responder($contestacao_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (current_user_can('manage_options')) {
            return true;
        }
        
        $contestacao = self::obter($contestacao_id);
        if (!$contestacao) {
            return false;
        }
        
        $lancamento = GC_Lancamento::obter($contestacao->lancamento_id);
        if (!$lancamento) {
            return false;
        }
        
        if ($lancamento->tipo === 'receita') {
            return current_user_can('manage_options');
        } else {
            return $lancamento->autor_id == $user_id || current_user_can('manage_options');
        }
    }
    
    public static function pode_analisar($contestacao_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $contestacao = self::obter($contestacao_id);
        if (!$contestacao) {
            return false;
        }
        
        return $contestacao->autor_id == $user_id;
    }
    
    public static function obter_pendentes_por_usuario($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        
        $contestacoes_table = $wpdb->prefix . 'gc_contestacoes';
        $lancamentos_table = $wpdb->prefix . 'gc_lancamentos';
        
        if (current_user_can('manage_options')) {
            $sql = "SELECT c.*, l.numero_unico, l.descricao_curta as lancamento_descricao, l.valor as lancamento_valor
                    FROM $contestacoes_table c
                    JOIN $lancamentos_table l ON c.lancamento_id = l.id
                    WHERE c.estado = 'pendente'
                    ORDER BY c.data_criacao ASC";
            
            return $wpdb->get_results($sql);
        } else {
            $sql = $wpdb->prepare(
                "SELECT c.*, l.numero_unico, l.descricao_curta as lancamento_descricao, l.valor as lancamento_valor
                 FROM $contestacoes_table c
                 JOIN $lancamentos_table l ON c.lancamento_id = l.id
                 WHERE c.estado = 'pendente' 
                 AND (l.autor_id = %d OR (l.tipo = 'receita' AND %d IN (SELECT ID FROM {$wpdb->users} u 
                      JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                      WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                      AND um.meta_value LIKE '%%administrator%%')))
                 ORDER BY c.data_criacao ASC",
                $user_id,
                $user_id
            );
            
            return $wpdb->get_results($sql);
        }
    }
    
    public static function processar_vencimentos() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        $lancamentos_table = $wpdb->prefix . 'gc_lancamentos';
        
        $prazo_resposta = intval(GC_Database::get_setting('prazo_resposta_contestacao_horas'));
        $prazo_analise = intval(GC_Database::get_setting('prazo_analise_resposta_horas'));
        
        $agora = current_time('mysql');
        $prazo_resposta_limite = date('Y-m-d H:i:s', strtotime("-{$prazo_resposta} hours"));
        $prazo_analise_limite = date('Y-m-d H:i:s', strtotime("-{$prazo_analise} hours"));
        
        $pendentes_vencidas = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.lancamento_id FROM $table_name c
             WHERE c.estado = 'pendente' AND c.data_criacao < %s",
            $prazo_resposta_limite
        ));
        
        foreach ($pendentes_vencidas as $contestacao) {
            $wpdb->update(
                $table_name,
                array('estado' => 'aceita', 'data_analise' => $agora),
                array('id' => $contestacao->id)
            );
            
            GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'contestado');
        }
        
        $respondidas_vencidas = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.lancamento_id FROM $table_name c
             WHERE c.estado = 'respondida' AND c.data_resposta < %s",
            $prazo_analise_limite
        ));
        
        foreach ($respondidas_vencidas as $contestacao) {
            $wpdb->update(
                $table_name,
                array('estado' => 'aceita', 'data_analise' => $agora),
                array('id' => $contestacao->id)
            );
            
            GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'aceito');
        }
        
        return count($pendentes_vencidas) + count($respondidas_vencidas);
    }
}