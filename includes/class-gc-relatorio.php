<?php

if (!defined('ABSPATH')) {
    exit;
}

class GC_Relatorio {
    
    public static function criar($dados, $arquivo) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_relatorios';
        
        $relatorio = array(
            'titulo' => sanitize_text_field($dados['titulo']),
            'tipo' => sanitize_text_field($dados['tipo']),
            'periodo' => sanitize_text_field($dados['periodo']),
            'arquivo' => sanitize_file_name($arquivo),
            'data_upload' => current_time('mysql'),
            'autor_id' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table_name, $relatorio);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erro ao salvar relatório');
        }
        
        return $wpdb->insert_id;
    }
    
    public static function obter($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_relatorios';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }
    
    public static function listar($tipo = null, $periodo = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_relatorios';
        $where = array('1=1');
        $params = array();
        
        if ($tipo) {
            $where[] = 'tipo = %s';
            $params[] = $tipo;
        }
        
        if ($periodo) {
            $where[] = 'periodo = %s';
            $params[] = $periodo;
        }
        
        $sql = "SELECT r.*, u.display_name as autor_nome 
                FROM $table_name r 
                LEFT JOIN {$wpdb->users} u ON r.autor_id = u.ID 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY r.data_upload DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function excluir($id) {
        global $wpdb;
        
        $relatorio = self::obter($id);
        if (!$relatorio) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $arquivo_path = $upload_dir['basedir'] . '/gestao-coletiva/relatorios/' . $relatorio->arquivo;
        
        if (file_exists($arquivo_path)) {
            unlink($arquivo_path);
        }
        
        $table_name = $wpdb->prefix . 'gc_relatorios';
        return $wpdb->delete($table_name, array('id' => $id));
    }
    
    public static function upload_arquivo($arquivo, $tipo, $periodo) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf' => 'application/pdf',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls' => 'application/vnd.ms-excel'
            )
        );
        
        $upload_dir = wp_upload_dir();
        $gc_upload_dir = $upload_dir['basedir'] . '/gestao-coletiva/relatorios/';
        
        if (!file_exists($gc_upload_dir)) {
            wp_mkdir_p($gc_upload_dir);
        }
        
        $nome_arquivo = $tipo . '_' . $periodo . '_' . date('Y-m-d_H-i-s') . '.' . pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $arquivo['name'] = $nome_arquivo;
        
        add_filter('upload_dir', function($dirs) {
            $dirs['path'] = $dirs['basedir'] . '/gestao-coletiva/relatorios';
            $dirs['url'] = $dirs['baseurl'] . '/gestao-coletiva/relatorios';
            return $dirs;
        });
        
        $movefile = wp_handle_upload($arquivo, $upload_overrides);
        
        remove_all_filters('upload_dir');
        
        if ($movefile && !isset($movefile['error'])) {
            return basename($movefile['file']);
        } else {
            return new WP_Error('upload_error', $movefile['error']);
        }
    }
    
    public static function get_arquivo_url($arquivo) {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/gestao-coletiva/relatorios/' . $arquivo;
    }
    
    public static function gerar_relatorio_periodo($data_inicio, $data_fim) {
        $saldo_inicial = self::calcular_saldo_inicial($data_inicio);
        $movimentacao = GC_Lancamento::calcular_saldo_periodo($data_inicio, $data_fim);
        $lancamentos = GC_Lancamento::listar(array(
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'order' => 'data_criacao ASC'
        ));
        
        $evolucao_diaria = self::calcular_evolucao_diaria($data_inicio, $data_fim);
        
        return array(
            'periodo' => array(
                'inicio' => $data_inicio,
                'fim' => $data_fim
            ),
            'saldo_inicial' => $saldo_inicial,
            'movimentacao' => $movimentacao,
            'saldo_final' => $saldo_inicial + $movimentacao['saldo'],
            'lancamentos' => $lancamentos,
            'evolucao_diaria' => $evolucao_diaria
        );
    }
    
    private static function calcular_saldo_inicial($data_limite) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $receitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'receita' 
             AND estado = 'efetivado'
             AND data_criacao < %s",
            $data_limite
        ));
        
        $despesas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'despesa' 
             AND estado = 'efetivado'
             AND data_criacao < %s",
            $data_limite
        ));
        
        return floatval($receitas) - floatval($despesas);
    }
    
    private static function calcular_evolucao_diaria($data_inicio, $data_fim) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        $sql = $wpdb->prepare(
            "SELECT DATE(data_criacao) as data,
                    SUM(CASE WHEN tipo = 'receita' AND estado = 'efetivado' THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = 'despesa' AND estado = 'efetivado' THEN valor ELSE 0 END) as despesas
             FROM $table_name 
             WHERE data_criacao BETWEEN %s AND %s
             GROUP BY DATE(data_criacao)
             ORDER BY data_criacao ASC",
            $data_inicio,
            $data_fim
        );
        
        $resultados = $wpdb->get_results($sql);
        $evolucao = array();
        $saldo_acumulado = self::calcular_saldo_inicial($data_inicio);
        
        foreach ($resultados as $dia) {
            $saldo_dia = floatval($dia->receitas) - floatval($dia->despesas);
            $saldo_acumulado += $saldo_dia;
            
            $evolucao[] = array(
                'data' => $dia->data,
                'receitas' => floatval($dia->receitas),
                'despesas' => floatval($dia->despesas),
                'saldo_dia' => $saldo_dia,
                'saldo_acumulado' => $saldo_acumulado
            );
        }
        
        return $evolucao;
    }
    
    public static function obter_periodos_disponiveis($tipo) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_relatorios';
        
        $periodos = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT periodo FROM $table_name WHERE tipo = %s ORDER BY periodo DESC",
            $tipo
        ));
        
        return $periodos;
    }
}