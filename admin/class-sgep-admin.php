<?php
/**
 * Clase para manejar la parte administrativa del plugin
 * 
 * Ruta: /admin/class-sgep-admin.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Agregar menú de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar configuraciones
        add_action('admin_init', array($this, 'register_settings'));
        
        // Agregar metaboxes a usuarios
        add_action('show_user_profile', array($this, 'add_user_meta_fields'));
        add_action('edit_user_profile', array($this, 'add_user_meta_fields'));
        
        // Guardar metaboxes de usuarios
        add_action('personal_options_update', array($this, 'save_user_meta_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_meta_fields'));
    }
    
    /**
     * Registrar scripts y estilos para el admin
     */
    public function enqueue_scripts() {
        // Solo cargar en las páginas del plugin
        $screen = get_current_screen();
        if (strpos($screen->id, 'sgep') === false) {
            return;
        }
        
        // Estilos
        wp_enqueue_style('sgep-admin-css', SGEP_PLUGIN_URL . 'admin/css/sgep-admin.css', array(), SGEP_VERSION);
        
        // Scripts
        wp_enqueue_script('sgep-admin-js', SGEP_PLUGIN_URL . 'admin/js/sgep-admin.js', array('jquery'), SGEP_VERSION, true);
        wp_localize_script('sgep-admin-js', 'sgep_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sgep_admin_nonce')
        ));
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Sistema de Gestión de Especialistas y Pacientes', 'sgep'),
            __('SGEP', 'sgep'),
            'manage_options',
            'sgep-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        // Submenús
        add_submenu_page(
            'sgep-dashboard',
            __('Panel', 'sgep'),
            __('Panel', 'sgep'),
            'manage_options',
            'sgep-dashboard',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'sgep-dashboard',
            __('Especialistas', 'sgep'),
            __('Especialistas', 'sgep'),
            'manage_options',
            'sgep-especialistas',
            array($this, 'render_especialistas')
        );
        
        add_submenu_page(
            'sgep-dashboard',
            __('Clientes', 'sgep'),
            __('Clientes', 'sgep'),
            'manage_options',
            'sgep-clientes',
            array($this, 'render_clientes')
        );
        
        add_submenu_page(
            'sgep-dashboard',
            __('Citas', 'sgep'),
            __('Citas', 'sgep'),
            'manage_options',
            'sgep-citas',
            array($this, 'render_citas')
        );
        
        add_submenu_page(
            'sgep-dashboard',
            __('Test de Compatibilidad', 'sgep'),
            __('Test', 'sgep'),
            'manage_options',
            'sgep-test',
            array($this, 'render_test')
        );
        
        // Añadir nueva sección para productos/tienda
        add_submenu_page(
            'sgep-dashboard',
            __('Productos', 'sgep'),
            __('Productos', 'sgep'),
            'manage_options',
            'sgep-productos',
            array($this, 'render_productos')
        );
        
        add_submenu_page(
            'sgep-dashboard',
            __('Configuración', 'sgep'),
            __('Configuración', 'sgep'),
            'manage_options',
            'sgep-settings',
            array($this, 'render_settings')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('sgep_settings', 'sgep_zoom_api_key');
        register_setting('sgep_settings', 'sgep_zoom_api_secret');
        register_setting('sgep_settings', 'sgep_email_notifications', array(
            'type' => 'boolean',
            'default' => 1
        ));
        register_setting('sgep_settings', 'sgep_test_questions');
        
        // Nuevas configuraciones para la tienda
        register_setting('sgep_settings', 'sgep_store_enabled', array(
            'type' => 'boolean',
            'default' => 0
        ));
        register_setting('sgep_settings', 'sgep_discount_codes_enabled', array(
            'type' => 'boolean',
            'default' => 0
        ));
    }
    
    /**
     * Renderizar página de dashboard
     */
    public function render_dashboard() {
        // Obtener estadísticas
        global $wpdb;
        
        $total_especialistas = count_users()['avail_roles']['sgep_especialista'] ?? 0;
        $total_clientes = count_users()['avail_roles']['sgep_cliente'] ?? 0;
        
        $total_citas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas");
        $citas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'pendiente'");
        $citas_confirmadas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'confirmada'");
        $citas_canceladas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'cancelada'");
        
        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_test_resultados");
        
        include(SGEP_PLUGIN_DIR . 'admin/templates/dashboard.php');
    }
    
    /**
     * Renderizar página de especialistas
     */
    public function render_especialistas() {
        $roles = new SGEP_Roles();
        $especialistas = $roles->get_all_especialistas();
        
        include(SGEP_PLUGIN_DIR . 'admin/templates/especialistas.php');
    }
    
    /**
     * Renderizar página de clientes
     */
    public function render_clientes() {
        $roles = new SGEP_Roles();
        $clientes = $roles->get_all_clientes();
        
        include(SGEP_PLUGIN_DIR . 'admin/templates/clientes.php');
    }
    
    /**
     * Renderizar página de citas
     */
    public function render_citas() {
        global $wpdb;
        
        // Parámetros de paginación
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        
        // Filtros
        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $especialista_id = isset($_GET['especialista_id']) ? intval($_GET['especialista_id']) : 0;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
        
        // Consulta base
        $query = "SELECT c.*, e.display_name as especialista_nombre, cl.display_name as cliente_nombre 
                  FROM {$wpdb->prefix}sgep_citas c
                  LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
                  LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
                  WHERE 1=1";
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE 1=1";
        $query_args = array();
        
        // Agregar filtros
        if (!empty($estado)) {
            $query .= " AND c.estado = %s";
            $count_query .= " AND estado = %s";
            $query_args[] = $estado;
        }
        
        if ($especialista_id > 0) {
            $query .= " AND c.especialista_id = %d";
            $count_query .= " AND especialista_id = %d";
            $query_args[] = $especialista_id;
        }
        
        if ($cliente_id > 0) {
            $query .= " AND c.cliente_id = %d";
            $count_query .= " AND cliente_id = %d";
            $query_args[] = $cliente_id;
        }
        
        // Ordenar
        $query .= " ORDER BY c.fecha DESC";
        
        // Paginación
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = $offset;
        
        // Obtener resultados
        $citas = $wpdb->get_results($wpdb->prepare($query, $query_args));
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, array_slice($query_args, 0, -2)));
        $total_pages = ceil($total_items / $per_page);
        
        // Obtener especialistas y clientes para los filtros
        $roles = new SGEP_Roles();
        $especialistas = $roles->get_all_especialistas();
        $clientes = $roles->get_all_clientes();
        
        include(SGEP_PLUGIN_DIR . 'admin/templates/citas.php');
    }
    
    /**
     * Renderizar página de test
     */
    public function render_test() {
        // Verificar si se envió el formulario
        if (isset($_POST['sgep_test_questions_nonce']) && wp_verify_nonce($_POST['sgep_test_questions_nonce'], 'sgep_save_test_questions')) {
            $preguntas = isset($_POST['sgep_test_questions']) ? $_POST['sgep_test_questions'] : array();
            $preguntas_sanitizadas = array();
            
            foreach ($preguntas as $pregunta) {
                $id = isset($pregunta['id']) ? intval($pregunta['id']) : 0;
                $texto = isset($pregunta['pregunta']) ? sanitize_text_field($pregunta['pregunta']) : '';
                $tipo = isset($pregunta['tipo']) ? sanitize_text_field($pregunta['tipo']) : 'radio';
                $opciones = isset($pregunta['opciones']) ? $pregunta['opciones'] : array();
                
                if (!empty($texto) && !empty($tipo)) {
                    $opciones_sanitizadas = array();
                    
                    foreach ($opciones as $key => $valor) {
                        $key_sanitizada = sanitize_key($key);
                        $valor_sanitizado = sanitize_text_field($valor);
                        
                        if (!empty($key_sanitizada) && !empty($valor_sanitizado)) {
                            $opciones_sanitizadas[$key_sanitizada] = $valor_sanitizado;
                        }
                    }
                    
                    if (!empty($opciones_sanitizadas)) {
                        $preguntas_sanitizadas[] = array(
                            'id' => $id,
                            'pregunta' => $texto,
                            'tipo' => $tipo,
                            'opciones' => $opciones_sanitizadas
                        );
                    }
                }
            }
            
            update_option('sgep_test_questions', $preguntas_sanitizadas);
            $mensaje = __('Preguntas del test guardadas correctamente.', 'sgep');
        }
        
        // Obtener preguntas actuales
        $preguntas = get_option('sgep_test_questions', array());
        
        include(SGEP_PLUGIN_DIR . 'admin/templates/test.php');
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings() {
        include(SGEP_PLUGIN_DIR . 'admin/templates/settings.php');
    }
    
    /**
     * Nueva función para renderizar página de productos
     */
    public function render_productos() {
        global $wpdb;
        
        // Parámetros de paginación
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        
        // Verificar si existe la tabla de productos y crearla si no existe
        $table_productos = $wpdb->prefix . 'sgep_productos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_productos'") != $table_productos) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_productos (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                nombre varchar(255) NOT NULL,
                descripcion text,
                precio decimal(10,2) NOT NULL,
                sku varchar(50) UNIQUE,
                especialista_id bigint(20),
                stock int(11) DEFAULT 0,
                categoria varchar(100),
                imagen_url varchar(255),
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Crear tabla para códigos de descuento
            $table_descuentos = $wpdb->prefix . 'sgep_descuentos';
            $sql = "CREATE TABLE $table_descuentos (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                codigo varchar(50) NOT NULL UNIQUE,
                porcentaje decimal(5,2) NOT NULL,
                fecha_inicio datetime,
                fecha_fin datetime,
                usos_maximos int(11) DEFAULT 0,
                usos_actuales int(11) DEFAULT 0,
                estado varchar(20) DEFAULT 'activo',
                created_at datetime NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            dbDelta($sql);
        }
        
        // Acción de producto (crear, editar, eliminar)
        $accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
        $producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($accion === 'eliminar' && $producto_id > 0 && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'eliminar_producto_' . $producto_id)) {
            $wpdb->delete($table_productos, array('id' => $producto_id));
            $mensaje = __('Producto eliminado correctamente.', 'sgep');
            $accion = ''; // Volver al listado
        }
        
        // Procesar formulario
        if (isset($_POST['sgep_producto_nonce']) && wp_verify_nonce($_POST['sgep_producto_nonce'], 'guardar_producto')) {
            $nombre = sanitize_text_field($_POST['nombre']);
            $descripcion = sanitize_textarea_field($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $sku = sanitize_text_field($_POST['sku']);
            $especialista_id = intval($_POST['especialista_id']);
            $stock = intval($_POST['stock']);
            $categoria = sanitize_text_field($_POST['categoria']);
            $imagen_url = esc_url_raw($_POST['imagen_url']);
            
            $datos = array(
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'sku' => $sku,
                'especialista_id' => $especialista_id > 0 ? $especialista_id : null,
                'stock' => $stock,
                'categoria' => $categoria,
                'imagen_url' => $imagen_url,
                'updated_at' => current_time('mysql')
            );
            
            if ($accion === 'editar' && $producto_id > 0) {
                $wpdb->update(
                    $table_productos,
                    $datos,
                    array('id' => $producto_id)
                );
                $mensaje = __('Producto actualizado correctamente.', 'sgep');
            } else {
                $datos['created_at'] = current_time('mysql');
                $wpdb->insert($table_productos, $datos);
                $producto_id = $wpdb->insert_id;
                $mensaje = __('Producto creado correctamente.', 'sgep');
            }
            
            $accion = ''; // Volver al listado
        }
        
        // Si estamos editando o creando un producto
        if ($accion === 'editar' || $accion === 'crear') {
            $producto = null;
            if ($accion === 'editar' && $producto_id > 0) {
                $producto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_productos WHERE id = %d", $producto_id));
                if (!$producto) {
                    $mensaje = __('El producto no existe.', 'sgep');
                    $accion = '';
                }
            }
            
            // Obtener lista de especialistas para el selector
            $roles = new SGEP_Roles();
            $especialistas = $roles->get_all_especialistas();
            
            include(SGEP_PLUGIN_DIR . 'admin/templates/producto-form.php');
        } else {
            // Listado de productos
            $query = "SELECT p.*, u.display_name as especialista_nombre
                      FROM $table_productos p
                      LEFT JOIN {$wpdb->users} u ON p.especialista_id = u.ID
                      ORDER BY p.nombre ASC
                      LIMIT %d OFFSET %d";
            
            $productos = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_productos");
            $total_pages = ceil($total_items / $per_page);
            
            include(SGEP_PLUGIN_DIR . 'admin/templates/productos.php');
        }
    }
    
    /**
     * Agregar campos de metadatos a los perfiles de usuario
     */
    public function add_user_meta_fields($user) {
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($user->ID)) {
            // Campos para especialistas
            $especialidad = get_user_meta($user->ID, 'sgep_especialidad', true);
            $descripcion = get_user_meta($user->ID, 'sgep_descripcion', true);
            $experiencia = get_user_meta($user->ID, 'sgep_experiencia', true);
            // Cambio: Reemplazar "título profesional" por "conocimientos"
            $conocimientos = get_user_meta($user->ID, 'sgep_conocimientos', true);
            $precio_consulta = get_user_meta($user->ID, 'sgep_precio_consulta', true);
            $duracion_consulta = get_user_meta($user->ID, 'sgep_duracion_consulta', true);
            $acepta_online = get_user_meta($user->ID, 'sgep_acepta_online', true);
            $acepta_presencial = get_user_meta($user->ID, 'sgep_acepta_presencial', true);
            $habilidades = get_user_meta($user->ID, 'sgep_habilidades', true);
            $metodologias = get_user_meta($user->ID, 'sgep_metodologias', true);
            $genero = get_user_meta($user->ID, 'sgep_genero', true);
            // Nuevos campos
            $actividades = get_user_meta($user->ID, 'sgep_actividades', true);
            $intereses = get_user_meta($user->ID, 'sgep_intereses', true);
            $filosofia = get_user_meta($user->ID, 'sgep_filosofia', true);
            
            include(SGEP_PLUGIN_DIR . 'admin/templates/user-meta-especialista.php');
        } elseif ($roles->is_cliente($user->ID)) {
            // Campos para clientes
            $telefono = get_user_meta($user->ID, 'sgep_telefono', true);
            $fecha_nacimiento = get_user_meta($user->ID, 'sgep_fecha_nacimiento', true);
            $intereses = get_user_meta($user->ID, 'sgep_intereses', true);
            
            include(SGEP_PLUGIN_DIR . 'admin/templates/user-meta-cliente.php');
        }
    }
    
    /**
     * Guardar campos de metadatos de usuarios
     */
    public function save_user_meta_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($user_id)) {
            // Guardar metadatos de especialista
            update_user_meta($user_id, 'sgep_especialidad', sanitize_text_field($_POST['sgep_especialidad']));
            update_user_meta($user_id, 'sgep_descripcion', sanitize_textarea_field($_POST['sgep_descripcion']));
            update_user_meta($user_id, 'sgep_experiencia', sanitize_text_field($_POST['sgep_experiencia']));
            // Actualizar conocimientos en lugar de título
            update_user_meta($user_id, 'sgep_conocimientos', sanitize_text_field($_POST['sgep_conocimientos']));
            update_user_meta($user_id, 'sgep_precio_consulta', sanitize_text_field($_POST['sgep_precio_consulta']));
            update_user_meta($user_id, 'sgep_duracion_consulta', intval($_POST['sgep_duracion_consulta']));
            update_user_meta($user_id, 'sgep_acepta_online', isset($_POST['sgep_acepta_online']) ? 1 : 0);
            update_user_meta($user_id, 'sgep_acepta_presencial', isset($_POST['sgep_acepta_presencial']) ? 1 : 0);
            update_user_meta($user_id, 'sgep_habilidades', isset($_POST['sgep_habilidades']) ? (array) $_POST['sgep_habilidades'] : array());
            update_user_meta($user_id, 'sgep_metodologias', sanitize_text_field($_POST['sgep_metodologias']));
            update_user_meta($user_id, 'sgep_genero', sanitize_text_field($_POST['sgep_genero']));
            // Guardar nuevos campos
            update_user_meta($user_id, 'sgep_actividades', sanitize_textarea_field($_POST['sgep_actividades']));
            update_user_meta($user_id, 'sgep_intereses', sanitize_textarea_field($_POST['sgep_intereses']));
            update_user_meta($user_id, 'sgep_filosofia', sanitize_textarea_field($_POST['sgep_filosofia']));
        } elseif ($roles->is_cliente($user_id)) {
            // Guardar metadatos de cliente
            update_user_meta($user_id, 'sgep_telefono', sanitize_text_field($_POST['sgep_telefono']));
            update_user_meta($user_id, 'sgep_fecha_nacimiento', sanitize_text_field($_POST['sgep_fecha_nacimiento']));
            update_user_meta($user_id, 'sgep_intereses', isset($_POST['sgep_intereses']) ? (array) $_POST['sgep_intereses'] : array());
        }
        
        return true;
    }
}