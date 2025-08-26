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
            
            // Atualizar estado do lançamento baseado na decisão
            if ($novo_estado === 'procedente') {
                // Contestação PROCEDENTE: admin concorda que há erro no lançamento
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'contestado');
                // Atualizar estado da contestação para 'respondida' para seguir fluxo
                $wpdb->update(
                    $table_name,
                    array('estado' => 'respondida'),
                    array('id' => $id)
                );
            } elseif ($novo_estado === 'improcedente') {
                // Contestação IMPROCEDENTE: admin discorda, lançamento está correto
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'confirmado');
                // Atualizar estado da contestação para 'respondida' para seguir fluxo
                $wpdb->update(
                    $table_name,
                    array('estado' => 'respondida'),
                    array('id' => $id)
                );
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
        
        $novo_estado = $aceitar ? 'aceita' : 'em_disputa';
        
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
    
    public static function finalizar_disputa($id, $link_postagem, $link_votacao) {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', 'Permissão negada');
        }
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        $contestacao = self::obter($id);
        
        if (!$contestacao || $contestacao->estado !== 'em_disputa') {
            return new WP_Error('invalid_state', 'Contestação deve estar em disputa para ser finalizada');
        }
        
        $update_data = array(
            'estado' => 'disputa_finalizada',
            'link_postagem_blog' => esc_url_raw($link_postagem),
            'link_formulario_votacao' => esc_url_raw($link_votacao),
            'data_finalizacao_disputa' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
        
        if ($result !== false) {
            // Não alteramos o estado do lançamento aqui - será determinado pela votação
            // O lançamento permanece em 'em_disputa' até resultado da votação
            return array(
                'success' => true,
                'message' => 'Disputa finalizada com sucesso. Links registrados para votação comunitária.'
            );
        }
        
        return new WP_Error('db_error', 'Erro ao finalizar disputa');
    }
    
    public static function obter_disputas_pendentes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        $sql = "SELECT c.*, l.numero_unico, l.descricao_curta as lancamento_descricao, l.valor as lancamento_valor
                FROM $table_name c
                JOIN {$wpdb->prefix}gc_lancamentos l ON c.lancamento_id = l.id
                WHERE c.estado = 'em_disputa'
                ORDER BY c.data_analise ASC";
        
        return $wpdb->get_results($sql);
    }
    
    public static function obter_disputas_finalizadas() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        $sql = "SELECT c.*, l.numero_unico, l.descricao_curta as lancamento_descricao, l.valor as lancamento_valor
                FROM $table_name c
                JOIN {$wpdb->prefix}gc_lancamentos l ON c.lancamento_id = l.id
                WHERE c.estado = 'disputa_finalizada'
                ORDER BY c.data_finalizacao_disputa ASC";
        
        return $wpdb->get_results($sql);
    }
    
    public static function registrar_resultado_votacao($id, $resultado_votacao, $observacoes = '') {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', 'Permissão negada');
        }
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        $contestacao = self::obter($id);
        
        if (!$contestacao || $contestacao->estado !== 'disputa_finalizada') {
            return new WP_Error('invalid_state', 'Contestação deve estar com disputa finalizada para registrar resultado');
        }
        
        if (!in_array($resultado_votacao, ['contestacao_procedente', 'contestacao_improcedente'])) {
            return new WP_Error('invalid_result', 'Resultado de votação inválido');
        }
        
        // Verificar se os campos existem na tabela antes de tentar atualizar
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        // Debug: log das colunas existentes
        error_log('Gestão Coletiva - Colunas da tabela contestações: ' . implode(', ', $column_names));
        
        $update_data = array(
            'estado' => 'disputa_resolvida'
        );
        
        // Só adicionar campos que existem na tabela
        if (in_array('resultado_votacao', $column_names)) {
            $update_data['resultado_votacao'] = $resultado_votacao;
        }
        
        if (in_array('observacoes_finais', $column_names)) {
            $update_data['observacoes_finais'] = sanitize_textarea_field($observacoes);
        }
        
        if (in_array('data_resolucao_final', $column_names)) {
            $update_data['data_resolucao_final'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
        
        if ($result !== false) {
            // Debug: log do resultado do update
            error_log('Gestão Coletiva - Update resultado: ' . $result . ' - Dados: ' . print_r($update_data, true));
            
            // Atualizar estado do lançamento baseado no resultado da votação
            if ($resultado_votacao === 'contestacao_procedente') {
                // Comunidade decidiu que a contestação é procedente
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'retificado_comunidade');
            } else {
                // Comunidade decidiu que a contestação é improcedente
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'contestado_comunidade');
            }
            
            return array(
                'success' => true,
                'message' => 'Resultado da votação registrado com sucesso. Disputa foi resolvida definitivamente.'
            );
        }
        
        // Debug: log do erro
        error_log('Gestão Coletiva - Erro no update: ' . $wpdb->last_error);
        
        return new WP_Error('db_error', 'Erro ao registrar resultado da votação: ' . $wpdb->last_error);
    }
    
    public static function corrigir_estados_rejeitada() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        // Buscar contestações com estado 'rejeitada' que deveriam estar 'em_disputa'
        $contestacoes_rejeitadas = $wpdb->get_results(
            "SELECT id, lancamento_id FROM $table_name WHERE estado = 'rejeitada'"
        );
        
        $corrigidas = 0;
        foreach ($contestacoes_rejeitadas as $contestacao) {
            // Atualizar contestação para 'em_disputa'
            $result1 = $wpdb->update(
                $table_name,
                array('estado' => 'em_disputa'),
                array('id' => $contestacao->id)
            );
            
            // Atualizar lançamento para 'em_disputa' se necessário
            if ($result1 !== false) {
                GC_Lancamento::atualizar_estado($contestacao->lancamento_id, 'em_disputa');
                $corrigidas++;
            }
        }
        
        return $corrigidas;
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