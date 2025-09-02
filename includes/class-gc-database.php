<?php

if (!defined('ABSPATH')) {
    exit;
}

class GC_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array();
        
        // Tabela de lançamentos
        $tables[] = "CREATE TABLE {$wpdb->prefix}gc_lancamentos (
            id int(11) NOT NULL AUTO_INCREMENT,
            numero_unico varchar(20) NOT NULL UNIQUE,
            tipo enum('receita', 'despesa') NOT NULL,
            descricao_curta varchar(255) NOT NULL,
            descricao_detalhada text,
            valor decimal(10,2) NOT NULL,
            recorrencia enum('unica', 'mensal', 'trimestral', 'anual') DEFAULT 'unica',
            lancamento_pai_id int(11) NULL COMMENT 'ID do lançamento original para recorrências',
            data_proxima_recorrencia datetime NULL COMMENT 'Data da próxima ocorrência para lançamentos recorrentes',
            recorrencia_ativa boolean DEFAULT TRUE COMMENT 'Se a recorrência ainda está ativa',
            estado enum('previsto', 'efetivado', 'cancelado', 'expirado', 'em_contestacao', 'contestado', 'confirmado', 'aceito', 'em_disputa', 'retificado_comunidade', 'contestado_comunidade') DEFAULT 'previsto',
            autor_id int(11) NOT NULL,
            doacao_anonima boolean DEFAULT FALSE COMMENT 'Se a doação deve aparecer como anônima na view pública',
            data_criacao datetime NOT NULL,
            data_efetivacao datetime NULL,
            data_expiracao datetime NOT NULL,
            prazo_atual datetime NOT NULL,
            tipo_prazo enum('efetivacao', 'cancelamento', 'expiracao', 'resposta_contestacao', 'analise_resposta', 'publicacao_disputa', 'resolucao_disputa') NOT NULL,
            anexos text,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY data_criacao (data_criacao)
        ) $charset_collate;";
        
        // Tabela de contestações
        $tables[] = "CREATE TABLE {$wpdb->prefix}gc_contestacoes (
            id int(11) NOT NULL AUTO_INCREMENT,
            lancamento_id int(11) NOT NULL,
            autor_id int(11) NOT NULL,
            tipo enum('doacao_nao_contabilizada', 'valor_incorreto_receita', 'data_incorreta_receita', 'doacao_inexistente', 'doador_incorreto', 'despesa_nao_verificada', 'valor_incorreto_despesa', 'finalidade_questionavel', 'documentacao_insuficiente', 'despesa_desnecessaria') NOT NULL,
            descricao text NOT NULL,
            comprovante text,
            estado enum('pendente', 'respondida', 'aceita', 'rejeitada', 'em_disputa', 'votacao_aberta', 'disputa_finalizada', 'expirada') DEFAULT 'pendente',
            data_criacao datetime NOT NULL,
            data_resposta datetime NULL,
            data_analise datetime NULL,
            data_finalizacao_disputa datetime NULL,
            data_resolucao_final datetime NULL,
            resposta text,
            link_postagem_blog varchar(500) NULL,
            link_formulario_votacao varchar(500) NULL,
            resultado_votacao enum('contestacao_procedente', 'contestacao_improcedente') NULL,
            observacoes_finais text NULL,
            PRIMARY KEY (id),
            KEY lancamento_id (lancamento_id),
            KEY autor_id (autor_id),
            KEY estado (estado)
        ) $charset_collate;";
        
        // Tabela de histórico de edições
        $tables[] = "CREATE TABLE {$wpdb->prefix}gc_historico_edicoes (
            id int(11) NOT NULL AUTO_INCREMENT,
            lancamento_id int(11) NOT NULL,
            campo_alterado varchar(50) NOT NULL,
            valor_anterior text NULL,
            valor_novo text NULL,
            motivo enum('contestacao_admin', 'contestacao_aceita', 'edicao_direta') NOT NULL,
            contestacao_id int(11) NULL,
            usuario_id int(11) NOT NULL,
            data_alteracao datetime NOT NULL,
            PRIMARY KEY (id),
            KEY lancamento_id (lancamento_id),
            KEY contestacao_id (contestacao_id),
            KEY usuario_id (usuario_id),
            KEY data_alteracao (data_alteracao)
        ) $charset_collate;";
        
        // Tabela de relatórios
        $tables[] = "CREATE TABLE {$wpdb->prefix}gc_relatorios (
            id int(11) NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            tipo enum('mensal', 'trimestral', 'anual') NOT NULL,
            periodo varchar(20) NOT NULL,
            arquivo varchar(255) NOT NULL,
            data_upload datetime NOT NULL,
            autor_id int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY periodo (periodo),
            KEY data_upload (data_upload)
        ) $charset_collate;";
        
        // Tabela de configurações
        $tables[] = "CREATE TABLE {$wpdb->prefix}gc_configuracoes (
            id int(11) NOT NULL AUTO_INCREMENT,
            chave varchar(100) NOT NULL,
            valor text NOT NULL,
            descricao text,
            PRIMARY KEY (id),
            UNIQUE KEY chave (chave)
        ) $charset_collate;";
        
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        
        foreach ($tables as $table) {
            $result = dbDelta($table);
            
            // Se dbDelta falhar, tentar criar diretamente
            if (empty($result)) {
                // Extrair nome da tabela do SQL
                if (preg_match('/CREATE TABLE (\w+)/', $table, $matches)) {
                    $table_name = $matches[1];
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                    
                    if (!$table_exists) {
                        $wpdb->query($table);
                        error_log("Gestão Coletiva: Tabela $table_name criada diretamente via query()");
                    }
                }
            }
        }
        
        // Garantir que as atualizações estruturais sejam aplicadas automaticamente
        self::atualizar_estrutura_contestacoes();
    }
    
    public static function insert_default_settings() {
        global $wpdb;
        
        $default_settings = array(
            array(
                'chave' => 'prazo_efetivacao_horas',
                'valor' => '72',
                'descricao' => 'Prazo em horas para efetivação de lançamentos'
            ),
            array(
                'chave' => 'prazo_resposta_contestacao_horas',
                'valor' => '48',
                'descricao' => 'Prazo em horas para resposta a contestações'
            ),
            array(
                'chave' => 'prazo_analise_resposta_horas',
                'valor' => '24',
                'descricao' => 'Prazo em horas para análise das respostas'
            ),
            array(
                'chave' => 'prazo_publicacao_disputa_horas',
                'valor' => '24',
                'descricao' => 'Prazo em horas para publicação de disputas'
            ),
            array(
                'chave' => 'prazo_resolucao_disputa_dias',
                'valor' => '7',
                'descricao' => 'Prazo em dias para resolução de disputas após a publicação'
            ),
            array(
                'chave' => 'texto_agradecimento_certificado',
                'valor' => 'Agradecemos sua contribuição para o projeto!',
                'descricao' => 'Texto de agradecimento para certificados de doação'
            ),
            array(
                'chave' => 'chave_pix',
                'valor' => '',
                'descricao' => 'Chave PIX para recebimento de doações'
            ),
            array(
                'chave' => 'nome_beneficiario_pix',
                'valor' => '',
                'descricao' => 'Nome do beneficiário da chave PIX'
            )
        );
        
        $table_name = $wpdb->prefix . 'gc_configuracoes';
        
        foreach ($default_settings as $setting) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE chave = %s",
                $setting['chave']
            ));
            
            if (!$existing) {
                $wpdb->insert($table_name, $setting);
            }
        }
    }
    
    public static function get_setting($key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_configuracoes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT valor FROM $table_name WHERE chave = %s",
            $key
        ));
    }
    
    public static function update_setting($key, $value) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_configuracoes';
        
        // Verificar se a chave já existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE chave = %s",
            $key
        ));
        
        if ($exists) {
            // Atualizar registro existente
            return $wpdb->update(
                $table_name,
                array('valor' => $value),
                array('chave' => $key)
            );
        } else {
            // Inserir novo registro
            return $wpdb->insert(
                $table_name,
                array(
                    'chave' => $key,
                    'valor' => $value
                )
            );
        }
    }
    
    public static function generate_unique_number() {
        global $wpdb;
        
        do {
            $numero = 'GC' . date('Y') . sprintf('%06d', rand(1, 999999));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}gc_lancamentos WHERE numero_unico = %s",
                $numero
            ));
        } while ($exists);
        
        return $numero;
    }
    
    public static function limpar_lancamentos_periodo($data_inicial, $data_final) {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', 'Permissão negada');
        }
        
        $lancamentos_table = $wpdb->prefix . 'gc_lancamentos';
        $contestacoes_table = $wpdb->prefix . 'gc_contestacoes';
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $lancamentos_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM $lancamentos_table WHERE DATE(data_criacao) BETWEEN %s AND %s",
                $data_inicial,
                $data_final
            ));
            
            if (!empty($lancamentos_ids)) {
                $placeholders = implode(',', array_fill(0, count($lancamentos_ids), '%d'));
                
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $contestacoes_table WHERE lancamento_id IN ($placeholders)",
                    ...$lancamentos_ids
                ));
                
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $lancamentos_table WHERE id IN ($placeholders)",
                    ...$lancamentos_ids
                ));
            }
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'lancamentos_removidos' => count($lancamentos_ids)
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', 'Erro ao limpar dados: ' . $e->getMessage());
        }
    }
    
    public static function limpar_todos_dados() {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', 'Permissão negada');
        }
        
        $tables = array(
            $wpdb->prefix . 'gc_contestacoes',
            $wpdb->prefix . 'gc_lancamentos',
            $wpdb->prefix . 'gc_relatorios',
            $wpdb->prefix . 'gc_configuracoes'
        );
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $total_registros = 0;
            
            foreach ($tables as $table) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $total_registros += intval($count);
                
                $wpdb->query("TRUNCATE TABLE $table");
            }
            
            self::insert_default_settings();
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'registros_removidos' => $total_registros,
                'tabelas_limpas' => count($tables)
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', 'Erro ao limpar todos os dados: ' . $e->getMessage());
        }
    }
    
    public static function remover_tabelas_plugin() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gc_contestacoes',
            $wpdb->prefix . 'gc_lancamentos', 
            $wpdb->prefix . 'gc_relatorios',
            $wpdb->prefix . 'gc_configuracoes'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('gc_installed');
        delete_option('gc_db_version');
        delete_option('gc_activation_error');
        
        wp_clear_scheduled_hook('gc_processar_vencimentos');
    }
    
    public static function atualizar_estrutura_contestacoes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_contestacoes';
        
        // Verificar se os novos campos existem
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        $alteracoes_executadas = array();
        
        // Adicionar campo data_resolucao_final se não existir
        if (!in_array('data_resolucao_final', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN data_resolucao_final datetime NULL AFTER data_finalizacao_disputa");
            $alteracoes_executadas[] = 'data_resolucao_final';
        }
        
        // Adicionar campo resultado_votacao se não existir
        if (!in_array('resultado_votacao', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN resultado_votacao enum('contestacao_procedente', 'contestacao_improcedente') NULL AFTER link_formulario_votacao");
            $alteracoes_executadas[] = 'resultado_votacao';
        }
        
        // Adicionar campo observacoes_finais se não existir
        if (!in_array('observacoes_finais', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN observacoes_finais text NULL AFTER resultado_votacao");
            $alteracoes_executadas[] = 'observacoes_finais';
        }
        
        // Verificar se o enum de estado inclui 'disputa_resolvida'
        $estado_column = null;
        foreach ($columns as $column) {
            if ($column->Field === 'estado') {
                $estado_column = $column;
                break;
            }
        }
        
        if ($estado_column && (strpos($estado_column->Type, 'votacao_aberta') === false || strpos($estado_column->Type, 'expirada') === false)) {
            $wpdb->query("ALTER TABLE $table_name MODIFY estado enum('pendente', 'respondida', 'aceita', 'rejeitada', 'em_disputa', 'votacao_aberta', 'disputa_finalizada', 'expirada') DEFAULT 'pendente'");
            $alteracoes_executadas[] = 'estado_enum_atualizado_v2';
        }
        
        // Atualizar enum de tipos de contestação - v1.1.1
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $tipo_column = null;
        foreach ($columns as $column) {
            if ($column->Field === 'tipo') {
                $tipo_column = $column;
                break;
            }
        }
        
        if ($tipo_column && (strpos($tipo_column->Type, 'valor_incorreto_receita') === false || strpos($tipo_column->Type, 'despesa_desnecessaria') === false)) {
            $wpdb->query("ALTER TABLE $table_name MODIFY tipo enum('doacao_nao_contabilizada', 'valor_incorreto_receita', 'data_incorreta_receita', 'doacao_inexistente', 'doador_incorreto', 'despesa_nao_verificada', 'valor_incorreto_despesa', 'finalidade_questionavel', 'documentacao_insuficiente', 'despesa_desnecessaria') NOT NULL");
            $alteracoes_executadas[] = 'tipo_contestacao_enum_expandido';
        }
        
        return $alteracoes_executadas;
    }
    
    public static function atualizar_estrutura_lancamentos() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Verificar se os novos campos existem
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        $alteracoes_executadas = array();
        
        // Adicionar campo lancamento_pai_id se não existir
        if (!in_array('lancamento_pai_id', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN lancamento_pai_id int(11) NULL COMMENT 'ID do lançamento original para recorrências' AFTER recorrencia");
            $alteracoes_executadas[] = 'lancamento_pai_id';
        }
        
        // Adicionar campo data_proxima_recorrencia se não existir
        if (!in_array('data_proxima_recorrencia', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN data_proxima_recorrencia datetime NULL COMMENT 'Data da próxima ocorrência para lançamentos recorrentes' AFTER lancamento_pai_id");
            $alteracoes_executadas[] = 'data_proxima_recorrencia';
        }
        
        // Adicionar campo recorrencia_ativa se não existir
        if (!in_array('recorrencia_ativa', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN recorrencia_ativa boolean DEFAULT TRUE COMMENT 'Se a recorrência ainda está ativa' AFTER data_proxima_recorrencia");
            $alteracoes_executadas[] = 'recorrencia_ativa';
        }
        
        return $alteracoes_executadas;
    }
}