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
            estado enum('previsto', 'efetivado', 'cancelado', 'expirado', 'em_contestacao', 'contestado', 'confirmado', 'aceito', 'em_disputa', 'retificado_comunidade', 'contestado_comunidade') DEFAULT 'previsto',
            autor_id int(11) NOT NULL,
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
            tipo enum('doacao_nao_contabilizada', 'despesa_nao_verificada') NOT NULL,
            descricao text NOT NULL,
            comprovante text,
            estado enum('pendente', 'respondida', 'aceita', 'rejeitada', 'em_disputa') DEFAULT 'pendente',
            data_criacao datetime NOT NULL,
            data_resposta datetime NULL,
            data_analise datetime NULL,
            resposta text,
            PRIMARY KEY (id),
            KEY lancamento_id (lancamento_id),
            KEY autor_id (autor_id),
            KEY estado (estado)
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
}