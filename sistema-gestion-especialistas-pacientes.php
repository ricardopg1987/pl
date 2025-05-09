<?php
/**
 * Plugin Name: Sistema de Gestión de Especialistas y Pacientes
 * Plugin URI: https://tudominio.com/plugins/sgep
 * Description: Plugin que permite la gestión de especialistas, pacientes y visitantes, con sistema de matching y agendamiento.
 * Version: 1.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: sgep
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SGEP_VERSION', '1.0');
define('SGEP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SGEP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Buffer de salida para evitar errores de "headers already sent"
 */
function sgep_output_buffer() {
    ob_start();
}
add_action('init', 'sgep_output_buffer', 1); // Prioridad 1 para que se ejecute primero

// Incluir archivos del plugin
require_once SGEP_PLUGIN_DIR . 'includes/class-sgep-activator.php';
require_once SGEP_PLUGIN_DIR . 'includes/class-sgep-deactivator.php';
require_once SGEP_PLUGIN_DIR . 'includes/class-sgep-roles.php';
require_once SGEP_PLUGIN_DIR . 'includes/class-sgep-shortcodes.php';
require_once SGEP_PLUGIN_DIR . 'includes/class-sgep-ajax.php';
require_once SGEP_PLUGIN_DIR . 'admin/class-sgep-admin.php';
require_once SGEP_PLUGIN_DIR . 'public/class-sgep-public.php';

// Activación y desactivación del plugin
register_activation_hook(__FILE__, array('SGEP_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('SGEP_Deactivator', 'deactivate'));

/**
 * Aumentar límite máximo de carga de archivos (corregido)
 */
function sgep_upload_max_size() {
    return 2 * 1024 * 1024; // 2MB
}
add_filter('upload_size_limit', 'sgep_upload_max_size');

/**
 * Permite a los roles de especialistas y clientes subir archivos
 */
function sgep_allow_user_uploads() {
    // Permitir que especialistas y clientes suban archivos
    $roles = array('sgep_especialista', 'sgep_cliente');
    foreach ($roles as $role) {
        if ($role_obj = get_role($role)) {
            $role_obj->add_cap('upload_files');
        }
    }
}
add_action('admin_init', 'sgep_allow_user_uploads');

/**
 * Configura los datos de enctype para formularios de carga de archivos
 */
function sgep_form_enctype_setup() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Asegurarse de que los formularios tengan el enctype correcto
        $('.sgep-perfil-form').attr('enctype', 'multipart/form-data');
    });
    </script>
    <?php
}
add_action('wp_footer', 'sgep_form_enctype_setup');

/**
 * Registrar las meta keys adicionales para los usuarios
 */
function sgep_register_user_meta() {
    register_meta('user', 'sgep_imagen_perfil', array(
        'type' => 'string',
        'description' => 'URL de la imagen de perfil del usuario',
        'single' => true,
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest' => true,
    ));
    
    register_meta('user', 'sgep_imagen_perfil_id', array(
        'type' => 'integer',
        'description' => 'ID del attachment de la imagen de perfil',
        'single' => true,
        'sanitize_callback' => 'absint',
        'show_in_rest' => true,
    ));
}
add_action('init', 'sgep_register_user_meta');

/**
 * Estilizar el panel de especialista con estilos personalizados
 */
function sgep_enqueue_panel_styles() {
    // Comprobar si estamos en la página del panel de especialista
    $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $pages = get_option('sgep_pages', array());
    
    $is_panel_page = false;
    if (!empty($pages['sgep-panel-especialista'])) {
        $panel_page = get_post($pages['sgep-panel-especialista']);
        if ($panel_page && strpos($current_url, $panel_page->post_name) !== false) {
            $is_panel_page = true;
        }
    }
    
    if ($is_panel_page && is_user_logged_in()) {
        wp_enqueue_media(); // Habilitar el media uploader de WordPress
        
        // Añadir JavaScript para gestionar la carga y vista previa de imágenes
        wp_add_inline_script('sgep-public-js', "
            jQuery(document).ready(function($) {
                // Inicializar la vista previa de imagen
                $('#sgep_imagen_perfil').on('change', function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    var preview = $('#sgep_preview_imagen');
                    var container = $('#sgep_preview_container');
                    
                    reader.onload = function(e) {
                        preview.attr('src', e.target.result);
                        container.show();
                    }
                    
                    if (file) {
                        reader.readAsDataURL(file);
                    }
                });
            });
        ");
    }
}
add_action('wp_enqueue_scripts', 'sgep_enqueue_panel_styles', 20);

/**
 * Clase principal del plugin
 */
class SGEP_Plugin {
    
    /**
     * Instancia única del plugin
     */
    private static $instance = null;
    
    /**
     * Objeto para administrar los roles
     */
    public $roles;
    
    /**
     * Objeto para administrar shortcodes
     */
    public $shortcodes;
    
    /**
     * Objeto para gestionar la parte administrativa
     */
    public $admin;
    
    /**
     * Objeto para gestionar la parte pública
     */
    public $public;
    
    /**
     * Obtiene la instancia única del plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->setup_actions();
    }
    
    /**
     * Carga las dependencias del plugin
     */
    private function load_dependencies() {
        $this->roles = new SGEP_Roles();
        $this->shortcodes = new SGEP_Shortcodes();
        $this->admin = new SGEP_Admin();
        $this->public = new SGEP_Public();
        new SGEP_Ajax();
    }
    
    /**
     * Configura las acciones del plugin
     */
    private function setup_actions() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
    }
    
    /**
     * Carga el dominio de texto para traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain('sgep', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Iniciar el plugin
function sgep_init() {
    return SGEP_Plugin::get_instance();
}
sgep_init();

/**
 * Solución para el problema de redirección
 */
function sgep_fix_login_redirect() {
    // Agregar filtro para la redirección de login
    add_filter('login_redirect', 'sgep_custom_login_redirect', 999, 3);
    
    // También manejar el login mediante el formulario personalizado
    add_action('wp_login', 'sgep_handle_login_redirect', 10, 2);
}
add_action('init', 'sgep_fix_login_redirect');

/**
 * Filtro personalizado para manejar la redirección después del login
 */
function sgep_custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!is_wp_error($user) && isset($user->roles) && is_array($user->roles)) {
        $pages = get_option('sgep_pages', array());
        
        if (in_array('sgep_especialista', $user->roles) && isset($pages['sgep-panel-especialista'])) {
            return get_permalink($pages['sgep-panel-especialista']);
        } elseif (in_array('sgep_cliente', $user->roles) && isset($pages['sgep-panel-cliente'])) {
            return get_permalink($pages['sgep-panel-cliente']);
        }
    }
    
    return $redirect_to;
}

/**
 * Maneja la redirección después del login para formularios personalizados
 */
function sgep_handle_login_redirect($user_login, $user) {
    if (!isset($user->roles) || !is_array($user->roles)) return;
    
    $pages = get_option('sgep_pages', array());
    $redirect_to = '';
    
    if (in_array('sgep_especialista', $user->roles) && isset($pages['sgep-panel-especialista'])) {
        $redirect_to = get_permalink($pages['sgep-panel-especialista']);
    } elseif (in_array('sgep_cliente', $user->roles) && isset($pages['sgep-panel-cliente'])) {
        $redirect_to = get_permalink($pages['sgep-panel-cliente']);
    }
    
    if (!empty($redirect_to)) {
        // Usar JavaScript para la redirección en lugar de wp_redirect
        echo '<script>window.location.href = "' . esc_url($redirect_to) . '";</script>';
        exit;
    }
}

// Desactivar el módulo de optimización de imágenes de Elementor que está causando problemas
add_filter('elementor/image-loading/optimization_active', '__return_false');