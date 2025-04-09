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
        if (!get_role('sgep_administrador')) {
            $this->add_role_administrador();
        }
        
        if (!get_role('sgep_especialista')) {
            $this->add_role_especialista();
        }
        
        if (!get_role('sgep_cliente')) {
            $this->add_role_cliente();
        }
        
        if (!get_role('sgep_vendedor')) {
            $this->add_role_vendedor();
        }
    }
    
    /**
     * Agrega los roles personalizados
     */
    public function add_roles() {
        $this->add_role_administrador();
        $this->add_role_especialista();
        $this->add_role_cliente();
        $this->add_role_vendedor();
    }
    
    /**
     * Elimina los roles personalizados
     */
    public function remove_roles() {
        remove_role('sgep_administrador');
        remove_role('sgep_especialista');
        remove_role('sgep_cliente');
        remove_role('sgep_vendedor');
    }
    
    /**
     * Agrega el rol de administrador
     */
    private function add_role_administrador() {
        add_role(
            'sgep_administrador',
            __('Administrador SGEP', 'sgep'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'manage_options' => true,
                'sgep_manage_all' => true,
                'sgep_manage_specialists' => true,
                'sgep_manage_clients' => true,
                'sgep_manage_appointments' => true,
                'sgep_manage_tests' => true,
                'sgep_manage_products' => true,
                'sgep_manage_store' => true,
            )
        );
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
                'sgep_manage_own_products' => true, // Nuevo permiso para gestionar productos propios
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
                'sgep_purchase_products' => true, // Nuevo permiso para comprar productos
            )
        );
    }
    
    /**
     * Agrega el rol de vendedor
     */
    private function add_role_vendedor() {
        add_role(
            'sgep_vendedor',
            __('Vendedor', 'sgep'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
                'sgep_manage_products' => true,
                'sgep_manage_orders' => true,
                'sgep_view_sales' => true,
                'sgep_send_messages' => true,
            )
        );
    }
    
    /**
     * Verifica si un usuario es administrador
     */
    public function is_administrador($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('sgep_administrador', (array) $user->roles);
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
     * Verifica si un usuario es vendedor
     */
    public function is_vendedor($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('sgep_vendedor', (array) $user->roles);
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
     * Obtiene todos los vendedores
     */
    public function get_all_vendedores() {
        $args = array(
            'role' => 'sgep_vendedor',
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
            'sgep_conocimientos' => '', // Cambiado de título a conocimientos
            'sgep_precio_consulta' => '',
            'sgep_duracion_consulta' => 60,
            'sgep_acepta_online' => 1,
            'sgep_acepta_presencial' => 0,
            'sgep_habilidades' => array(),
            'sgep_metodologias' => array(),
            'sgep_actividades' => '', // Nuevo campo
            'sgep_intereses' => '', // Nuevo campo
            'sgep_filosofia' => '', // Nuevo campo para filosofía personal
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
            
            if (in_array('sgep_administrador', $user->roles) && current_user_can('manage_options')) {
                return admin_url('admin.php?page=sgep-dashboard');
            } else if (in_array('sgep_especialista', $user->roles) && isset($pages['sgep-panel-especialista'])) {
                return get_permalink($pages['sgep-panel-especialista']);
            } elseif (in_array('sgep_cliente', $user->roles) && isset($pages['sgep-panel-cliente'])) {
                return get_permalink($pages['sgep-panel-cliente']);
            } elseif (in_array('sgep_vendedor', $user->roles) && isset($pages['sgep-panel-vendedor'])) {
                return get_permalink($pages['sgep-panel-vendedor']);
            }
        }
        
        return $redirect_to;
    }
}