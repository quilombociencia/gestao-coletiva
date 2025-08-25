<?php
/**
 * Plugin Name: Gestão Coletiva
 * Plugin URI: https://github.com/quilombociencia/gestao-coletiva
 * Description: Plugin para gestão coletiva de recursos do projeto, permite a arrecadação, gestão e prestação de contas em tempo real.
 * Version: 1.0.1
 * Author: Quilombo Ciência
 * License: GPL/GNU 3.0
 * Text Domain: gestao-coletiva
 * Domain Path: /languages/
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('GC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GC_VERSION', '1.0.1');

// Main plugin class
class GestaoColetiva {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('gestao-coletiva', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Only load if no fatal errors
        if ($this->check_requirements()) {
            // Verificar se as tabelas existem, caso contrário criar
            $this->maybe_create_tables();
            
            $this->load_dependencies();
            $this->setup_hooks();
        }
    }
    
    private function check_requirements() {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        return true;
    }
    
    private function maybe_create_tables() {
        // Verificar se as tabelas já foram criadas
        $installed = get_option('gc_installed', false);
        if ($installed) {
            return; // Tabelas já criadas
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gc_configuracoes';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Carregar e executar criação das tabelas
            $database_file = GC_PLUGIN_PATH . 'includes/class-gc-database.php';
            if (file_exists($database_file)) {
                require_once $database_file;
                
                if (class_exists('GC_Database')) {
                    try {
                        GC_Database::create_tables();
                        GC_Database::insert_default_settings();
                        
                        // Marcar como instalado
                        add_option('gc_installed', true);
                        add_option('gc_db_version', GC_VERSION);
                        
                        error_log('Gestão Coletiva: Tabelas criadas automaticamente');
                    } catch (Exception $e) {
                        error_log('Gestão Coletiva: Erro ao criar tabelas automaticamente: ' . $e->getMessage());
                    }
                }
            }
        } else {
            // Tabelas existem, marcar como instalado
            add_option('gc_installed', true);
        }
    }
    
    private function load_dependencies() {
        // Load classes individually, don't fail if one is missing
        $class_files = array(
            'includes/class-gc-database.php',
            'includes/class-gc-lancamento.php',
            'includes/class-gc-contestacao.php',
            'includes/class-gc-relatorio.php'
        );
        
        $loaded_classes = array();
        foreach ($class_files as $file) {
            $file_path = GC_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                try {
                    require_once $file_path;
                    $loaded_classes[] = $file;
                } catch (Exception $e) {
                    error_log('Gestão Coletiva: Erro ao carregar ' . $file . ': ' . $e->getMessage());
                } catch (Error $e) {
                    error_log('Gestão Coletiva: Erro fatal ao carregar ' . $file . ': ' . $e->getMessage());
                }
            } else {
                error_log('Gestão Coletiva: Arquivo não encontrado: ' . $file_path);
            }
        }
        
        // Load admin class (always load for AJAX, but hooks only in admin)
        $admin_file = GC_PLUGIN_PATH . 'includes/class-gc-admin.php';
        if (file_exists($admin_file)) {
            try {
                require_once $admin_file;
                
                // Only instantiate if we have the database class
                if (class_exists('GC_Database')) {
                    new GC_Admin();
                    error_log('Gestão Coletiva: GC_Admin carregada com sucesso');
                } else {
                    error_log('Gestão Coletiva: GC_Database não carregada, não iniciando GC_Admin');
                }
            } catch (Exception $e) {
                error_log('Gestão Coletiva: Erro ao carregar GC_Admin: ' . $e->getMessage());
            } catch (Error $e) {
                error_log('Gestão Coletiva: Erro fatal ao carregar GC_Admin: ' . $e->getMessage());
            }
        }
    }
    
    private function setup_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    public function activate() {
        try {
            // Carregar apenas a classe Database para ativação
            $database_file = GC_PLUGIN_PATH . 'includes/class-gc-database.php';
            if (file_exists($database_file)) {
                require_once $database_file;
            }
            
            if (class_exists('GC_Database')) {
                GC_Database::create_tables();
                GC_Database::insert_default_settings();
                
                // Marcar que a instalação foi bem-sucedida
                add_option('gc_db_version', GC_VERSION);
                add_option('gc_installed', true);
                
                error_log('Gestão Coletiva: Tabelas criadas com sucesso');
            } else {
                throw new Exception('Classe GC_Database não encontrada');
            }
            
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log error instead of showing fatal error
            error_log('Gestão Coletiva Activation Error: ' . $e->getMessage());
            
            // Show admin notice instead of fatal error
            add_option('gc_activation_error', $e->getMessage());
            add_action('admin_notices', array($this, 'activation_error_notice'));
        } catch (Error $e) {
            // Log error instead of showing fatal error
            error_log('Gestão Coletiva Fatal Error: ' . $e->getMessage());
            
            // Show admin notice instead of fatal error
            add_option('gc_activation_error', 'Erro fatal: ' . $e->getMessage());
            add_action('admin_notices', array($this, 'activation_error_notice'));
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('gc_processar_vencimentos');
    }
    
    public function enqueue_public_scripts() {
        if (file_exists(GC_PLUGIN_PATH . 'assets/css/public.css')) {
            wp_enqueue_style('gc-public-css', GC_PLUGIN_URL . 'assets/css/public.css', array(), GC_VERSION);
        }
        
        if (file_exists(GC_PLUGIN_PATH . 'assets/js/public.js')) {
            wp_enqueue_script('gc-public-js', GC_PLUGIN_URL . 'assets/js/public.js', array('jquery'), GC_VERSION, true);
            
            wp_localize_script('gc-public-js', 'gc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gc_nonce'),
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'gestao-coletiva') === false) {
            return;
        }
        
        if (file_exists(GC_PLUGIN_PATH . 'assets/css/admin.css')) {
            wp_enqueue_style('gc-admin-css', GC_PLUGIN_URL . 'assets/css/admin.css', array(), GC_VERSION);
        }
        
        if (file_exists(GC_PLUGIN_PATH . 'assets/js/admin.js')) {
            wp_enqueue_script('gc-admin-js', GC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), GC_VERSION, true);
            
            wp_localize_script('gc-admin-js', 'gc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gc_nonce'),
            ));
        }
    }
    
    public function register_shortcodes() {
        add_shortcode('gc_painel', array($this, 'painel_shortcode'));
        add_shortcode('gc_lancamentos', array($this, 'lancamentos_shortcode'));
        add_shortcode('gc_livro_caixa', array($this, 'livro_caixa_shortcode'));
    }
    
    public function painel_shortcode($atts) {
        $file = GC_PLUGIN_PATH . 'public/views/painel.php';
        if (file_exists($file)) {
            ob_start();
            include $file;
            return ob_get_clean();
        }
        return '<p>' . __('Erro: arquivo de template não encontrado.', 'gestao-coletiva') . '</p>';
    }
    
    public function lancamentos_shortcode($atts) {
        $file = GC_PLUGIN_PATH . 'public/views/lancamentos.php';
        if (file_exists($file)) {
            ob_start();
            include $file;
            return ob_get_clean();
        }
        return '<p>' . __('Erro: arquivo de template não encontrado.', 'gestao-coletiva') . '</p>';
    }
    
    public function livro_caixa_shortcode($atts) {
        $file = GC_PLUGIN_PATH . 'public/views/livro-caixa.php';
        if (file_exists($file)) {
            ob_start();
            include $file;
            return ob_get_clean();
        }
        return '<p>' . __('Erro: arquivo de template não encontrado.', 'gestao-coletiva') . '</p>';
    }
    
    // Error notices
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Gestão Coletiva requer WordPress 5.0 ou superior.', 'gestao-coletiva');
        echo '</p></div>';
    }
    
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Gestão Coletiva requer PHP 7.4 ou superior.', 'gestao-coletiva');
        echo '</p></div>';
    }
    
    public function activation_error_notice() {
        $error = get_option('gc_activation_error');
        if ($error) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(__('Erro na ativação do Gestão Coletiva: %s', 'gestao-coletiva'), esc_html($error));
            echo '</p></div>';
            delete_option('gc_activation_error');
        }
    }
}

// Initialize the plugin
new GestaoColetiva();