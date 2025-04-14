<?php
/**
 * Clase para gestionar los roles personalizados
 * 
 * Ruta: /includes/class-sgep-roles.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Roles {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'check_roles'));
    }
    
    /**
     * Verifica que los roles existan
     */
    public function check_roles() {
        // Verificar que los roles existan en caso de que hayan sido eliminados manualmente
        if (!get_role('sgep_especialista')) {
            $this->add_role_especialista();
        }
        
        if (!get_role('sgep_cliente')) {
            $this->add_role_cliente();
        }
    }
    
    /**
     * Agrega los roles personalizados
     */
    public function add_roles() {
        $this->add_role_especialista();
        $this->add_role_cliente();
    }
    
    /**
     * Elimina los roles personalizados
     */
    public function remove_roles() {
        remove_role('sgep_especialista');
        remove_role('sgep_cliente');
    }
    
    /**
     * Agrega el rol de especialista
     */
    private function add_role_especialista() {
        add_role(
            'sgep_especialista',
            __('Especialista', 'sgep'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
                'sgep_manage_schedule' => true,
                'sgep_view_clients' => true,
                'sgep_send_messages' => true,
            )
        );
    }
    
    /**
     * Agrega el rol de cliente
     */
    private function add_role_cliente() {
        add_role(
            'sgep_cliente',
            __('Cliente', 'sgep'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => false,
                'sgep_book_appointments' => true,
                'sgep_take_tests' => true,
                'sgep_send_messages' => true,
            )
        );
    }
    
    /**
     * Verifica si un usuario es especialista
     */
    public function is_especialista($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('sgep_especialista', (array) $user->roles);
    }
    
    /**
     * Verifica si un usuario es cliente
     */
    public function is_cliente($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('sgep_cliente', (array) $user->roles);
    }
    
    /**
     * Obtiene todos los especialistas
     */
    public function get_all_especialistas() {
        $args = array(
            'role' => 'sgep_especialista',
            'orderby' => 'display_name',
            'order' => 'ASC',
        );
        
        return get_users($args);
    }
    
    /**
     * Obtiene todos los clientes
     */
    public function get_all_clientes() {
        $args = array(
            'role' => 'sgep_cliente',
            'orderby' => 'display_name',
            'order' => 'ASC',
        );
        
        return get_users($args);
    }
    
    /**
     * Asigna metadatos específicos para especialistas
     */
    public function setup_especialista_meta($user_id, $meta_data) {
        $default_meta = array(
            'sgep_especialidad' => '',
            'sgep_descripcion' => '',
            'sgep_experiencia' => '',
            'sgep_titulo' => '',
            'sgep_precio_consulta' => '',
            'sgep_duracion_consulta' => 60,
            'sgep_acepta_online' => 1,
            'sgep_acepta_presencial' => 0,
            'sgep_habilidades' => array(),
            'sgep_metodologias' => array(),
        );
        
        $meta_data = wp_parse_args($meta_data, $default_meta);
        
        foreach ($meta_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
    }
    
    /**
     * Asigna metadatos específicos para clientes
     */
    public function setup_cliente_meta($user_id, $meta_data) {
        $default_meta = array(
            'sgep_telefono' => '',
            'sgep_fecha_nacimiento' => '',
            'sgep_intereses' => array(),
        );
        
        $meta_data = wp_parse_args($meta_data, $default_meta);
        
        foreach ($meta_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
    }
    
    /**
     * Redirecciona a los usuarios después del login según su rol
     */
    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            $pages = get_option('sgep_pages', array());
            
            if (in_array('sgep_especialista', $user->roles) && isset($pages['sgep-panel-especialista'])) {
                return get_permalink($pages['sgep-panel-especialista']);
            } elseif (in_array('sgep_cliente', $user->roles) && isset($pages['sgep-panel-cliente'])) {
                return get_permalink($pages['sgep-panel-cliente']);
            }
        }
        
        return $redirect_to;
    }
}