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
        
        // Estados que representam valores efetivamente confirmados/realizados
        $estados_confirmados = "('efetivado', 'confirmado', 'aceito', 'retificado_comunidade')";
        
        $receitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'receita' 
             AND estado IN $estados_confirmados
             AND data_criacao < %s",
            $data_limite
        ));
        
        $despesas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'despesa' 
             AND estado IN $estados_confirmados
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
                    SUM(CASE WHEN tipo = 'receita' AND estado IN ('efetivado', 'confirmado', 'aceito', 'retificado_comunidade') THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = 'despesa' AND estado IN ('efetivado', 'confirmado', 'aceito', 'retificado_comunidade') THEN valor ELSE 0 END) as despesas
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
    
    /**
     * Gera relatório de previsão incluindo lançamentos futuros
     */
    public static function gerar_relatorio_previsao($data_inicio, $data_fim) {
        $saldo_inicial = self::calcular_saldo_inicial($data_inicio);
        
        // Garantir que lançamentos recorrentes futuros sejam criados até a data final
        GC_Lancamento::processar_recorrencias_ate_data($data_fim);
        
        // Separar movimentação realizada vs prevista
        $movimentacao_realizada = self::calcular_movimentacao_realizada($data_inicio, $data_fim);
        $movimentacao_prevista = self::calcular_movimentacao_prevista($data_inicio, $data_fim);
        
        // Obter lançamentos realizados e previstos (apenas os que existem no banco)
        $lancamentos_realizados = self::obter_lancamentos_realizados($data_inicio, $data_fim);
        $lancamentos_previstos = self::obter_lancamentos_previstos($data_inicio, $data_fim);
        
        // Calcular evolução com previsões (apenas lançamentos reais)
        $evolucao_diaria = self::calcular_evolucao_com_previsao($data_inicio, $data_fim, $saldo_inicial);
        
        $saldo_realizado = $saldo_inicial + $movimentacao_realizada['saldo'];
        $saldo_previsto = $saldo_realizado + $movimentacao_prevista['saldo'];
        
        return array(
            'periodo' => array(
                'inicio' => $data_inicio,
                'fim' => $data_fim,
                'is_futuro' => strtotime($data_inicio) > time()
            ),
            'saldo_inicial' => $saldo_inicial,
            'movimentacao_realizada' => $movimentacao_realizada,
            'movimentacao_prevista' => $movimentacao_prevista,
            'saldo_realizado' => $saldo_realizado,
            'saldo_previsto' => $saldo_previsto,
            'lancamentos_realizados' => $lancamentos_realizados,
            'lancamentos_previstos' => $lancamentos_previstos,
            'evolucao_diaria' => $evolucao_diaria,
            'total_lancamentos' => count($lancamentos_realizados) + count($lancamentos_previstos)
        );
    }
    
    /**
     * Calcula movimentação de lançamentos já realizados
     */
    private static function calcular_movimentacao_realizada($data_inicio, $data_fim) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Estados realizados/confirmados
        $estados_realizados = "('efetivado', 'confirmado', 'aceito', 'retificado_comunidade')";
        
        $receitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'receita' 
             AND estado IN $estados_realizados
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        $despesas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'despesa' 
             AND estado IN $estados_realizados
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        return array(
            'receitas' => floatval($receitas),
            'despesas' => floatval($despesas),
            'saldo' => floatval($receitas) - floatval($despesas)
        );
    }
    
    /**
     * Calcula movimentação de lançamentos previstos
     */
    private static function calcular_movimentacao_prevista($data_inicio, $data_fim) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        
        // Estados previstos
        $estados_previstos = "('previsto')";
        
        $receitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'receita' 
             AND estado IN $estados_previstos
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        $despesas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM $table_name 
             WHERE tipo = 'despesa' 
             AND estado IN $estados_previstos
             AND data_criacao BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        return array(
            'receitas' => floatval($receitas),
            'despesas' => floatval($despesas),
            'saldo' => floatval($receitas) - floatval($despesas)
        );
    }
    
    /**
     * Obtém lançamentos realizados no período
     */
    private static function obter_lancamentos_realizados($data_inicio, $data_fim) {
        return GC_Lancamento::listar(array(
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'estados_realizados' => true,
            'order' => 'data_criacao ASC'
        ));
    }
    
    /**
     * Obtém lançamentos previstos no período
     */
    private static function obter_lancamentos_previstos($data_inicio, $data_fim) {
        return GC_Lancamento::listar(array(
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'estado' => 'previsto',
            'order' => 'data_criacao ASC'
        ));
    }
    
    /**
     * Calcula evolução diária incluindo previsões
     */
    private static function calcular_evolucao_com_previsao($data_inicio, $data_fim, $saldo_inicial) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gc_lancamentos';
        $agora = current_time('mysql');
        
        // Obter movimentações diárias
        $movimentacoes = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(data_criacao) as data,
                    tipo,
                    estado,
                    SUM(valor) as valor_total,
                    COUNT(*) as quantidade
             FROM $table_name 
             WHERE data_criacao BETWEEN %s AND %s
             GROUP BY DATE(data_criacao), tipo, estado
             ORDER BY data_criacao ASC",
            $data_inicio, $data_fim
        ));
        
        $evolucao = array();
        $saldo_acumulado = $saldo_inicial;
        $data_atual = new DateTime($data_inicio);
        $data_final = new DateTime($data_fim);
        
        while ($data_atual <= $data_final) {
            $data_str = $data_atual->format('Y-m-d');
            $is_futuro = $data_atual->format('Y-m-d H:i:s') > $agora;
            
            $movimento_dia = 0;
            $receitas_realizadas = 0;
            $despesas_realizadas = 0;
            $receitas_previstas = 0;
            $despesas_previstas = 0;
            
            foreach ($movimentacoes as $mov) {
                if ($mov->data === $data_str) {
                    $valor = floatval($mov->valor_total);
                    
                    if (in_array($mov->estado, ['efetivado', 'confirmado', 'aceito', 'retificado_comunidade'])) {
                        // Lançamento realizado
                        if ($mov->tipo === 'receita') {
                            $receitas_realizadas += $valor;
                            $movimento_dia += $valor;
                        } else {
                            $despesas_realizadas += $valor;
                            $movimento_dia -= $valor;
                        }
                    } elseif ($mov->estado === 'previsto') {
                        // Lançamento previsto (incluindo recorrências criadas pelo sistema)
                        if ($mov->tipo === 'receita') {
                            $receitas_previstas += $valor;
                        } else {
                            $despesas_previstas += $valor;
                        }
                    }
                }
            }
            
            $saldo_acumulado += $movimento_dia;
            $saldo_com_previsao = $saldo_acumulado + $receitas_previstas - $despesas_previstas;
            
            $evolucao[] = array(
                'data' => $data_atual->format('d/m'),
                'data_completa' => $data_str,
                'saldo_acumulado' => $saldo_acumulado,
                'saldo_com_previsao' => $saldo_com_previsao,
                'receitas_realizadas' => $receitas_realizadas,
                'despesas_realizadas' => $despesas_realizadas,
                'receitas_previstas' => $receitas_previstas,
                'despesas_previstas' => $despesas_previstas,
                'is_futuro' => $is_futuro
            );
            
            $data_atual->add(new DateInterval('P1D'));
        }
        
        return $evolucao;
    }
}