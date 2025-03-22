<?php
/**
 * Clase para manejar la parte pública del plugin
 * 
 * Ruta: /public/class-sgep-public.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Public {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Agregar filtro de redirección después del login
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
    }
    
    /**
     * Registrar scripts y estilos para el frontend
     */
    public function enqueue_scripts() {
        // Estilos
        wp_enqueue_style('sgep-public-css', SGEP_PLUGIN_URL . 'public/css/sgep-public.css', array(), SGEP_VERSION);
        
        // Scripts
        wp_enqueue_script('sgep-public-js', SGEP_PLUGIN_URL . 'public/js/sgep-public.js', array('jquery'), SGEP_VERSION, true);
        wp_localize_script('sgep-public-js', 'sgep_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sgep_ajax_nonce')
        ));
    }
    
    /**
     * Redireccionar usuarios después del login según su rol
     */
    public function login_redirect($redirect_to, $request, $user) {
        $roles = new SGEP_Roles();
        return $roles->login_redirect($redirect_to, $request, $user);
    }
}