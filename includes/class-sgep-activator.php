<?php
/**
 * Clase para manejar la activación del plugin
 * 
 * Ruta: /includes/class-sgep-activator.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Activator {
    
    /**
     * Método que se ejecuta cuando se activa el plugin
     */
    public static function activate() {
        // Crear roles personalizados
        $roles = new SGEP_Roles();
        $roles->add_roles();
        
        // Crear páginas necesarias
        self::create_pages();
        
        // Crear tablas personalizadas en la base de datos
        self::create_tables();
        
        // Establecer configuración predeterminada
        self::set_default_options();
        
        // Limpiar rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Crear las páginas necesarias para el plugin
     */
    private static function create_pages() {
        $pages = array(
            'sgep-login' => array(
                'title' => __('Iniciar Sesión', 'sgep'),
                'content' => '[sgep_login]'
            ),
            'sgep-registro' => array(
                'title' => __('Registro', 'sgep'),
                'content' => '[sgep_registro]'
            ),
            'sgep-panel-especialista' => array(
                'title' => __('Panel del Especialista', 'sgep'),
                'content' => '[sgep_panel_especialista]'
            ),
            'sgep-panel-cliente' => array(
                'title' => __('Panel del Cliente', 'sgep'),
                'content' => '[sgep_panel_cliente]'
            ),
            'sgep-test-match' => array(
                'title' => __('Test de Compatibilidad', 'sgep'),
                'content' => '[sgep_test_match]'
            ),
            'sgep-resultados-match' => array(
                'title' => __('Resultados de Compatibilidad', 'sgep'),
                'content' => '[sgep_resultados_match]'
            ),
            'sgep-directorio-especialistas' => array(
                'title' => __('Directorio de Especialistas', 'sgep'),
                'content' => '[sgep_directorio_especialistas]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
        
        // Guardar IDs de páginas creadas en opciones
        $pages_ids = array();
        foreach (array_keys($pages) as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                $pages_ids[$slug] = $page->ID;
            }
        }
        
        update_option('sgep_pages', $pages_ids);
    }
    
    /**
     * Crear tablas personalizadas en la base de datos
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para almacenar las citas/consultas
        $table_citas = $wpdb->prefix . 'sgep_citas';
        
        $sql = "CREATE TABLE $table_citas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            especialista_id bigint(20) NOT NULL,
            cliente_id bigint(20) NOT NULL,
            fecha datetime NOT NULL,
            duracion int(11) NOT NULL DEFAULT 60,
            estado varchar(20) NOT NULL DEFAULT 'pendiente',
            zoom_link text,
            zoom_id varchar(255),
            zoom_password varchar(255),
            notas text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabla para almacenar disponibilidad de especialistas
        $table_disponibilidad = $wpdb->prefix . 'sgep_disponibilidad';
        
        $sql .= "CREATE TABLE $table_disponibilidad (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            especialista_id bigint(20) NOT NULL,
            dia_semana int(1) NOT NULL,
            hora_inicio time NOT NULL,
            hora_fin time NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabla para almacenar mensajes entre especialistas y clientes
        $table_mensajes = $wpdb->prefix . 'sgep_mensajes';
        
        $sql .= "CREATE TABLE $table_mensajes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            remitente_id bigint(20) NOT NULL,
            destinatario_id bigint(20) NOT NULL,
            asunto varchar(255) NOT NULL,
            mensaje text NOT NULL,
            leido tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabla para almacenar resultados de test
        $table_test_resultados = $wpdb->prefix . 'sgep_test_resultados';
        
        $sql .= "CREATE TABLE $table_test_resultados (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cliente_id bigint(20) NOT NULL,
            respuestas text NOT NULL,
            resultado text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabla para almacenar matches entre especialistas y clientes
        $table_matches = $wpdb->prefix . 'sgep_matches';
        
        $sql .= "CREATE TABLE $table_matches (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cliente_id bigint(20) NOT NULL,
            especialista_id bigint(20) NOT NULL,
            test_resultado_id bigint(20) NOT NULL,
            puntaje decimal(5,2) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Establecer opciones predeterminadas
     */
    private static function set_default_options() {
        // Opciones generales
        $options = array(
            'sgep_zoom_api_key' => '',
            'sgep_zoom_api_secret' => '',
            'sgep_email_notifications' => 1,
            'sgep_test_questions' => self::get_default_test_questions(),
        );
        
        foreach ($options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Obtener preguntas predeterminadas para el test
     */
    private static function get_default_test_questions() {
        return array(
            array(
                'id' => 1,
                'pregunta' => '¿Qué áreas te gustaría trabajar principalmente?',
                'tipo' => 'multiple',
                'opciones' => array(
                    'ansiedad' => 'Ansiedad',
                    'depresion' => 'Depresión',
                    'estres' => 'Estrés',
                    'autoestima' => 'Autoestima',
                    'relaciones' => 'Relaciones',
                    'duelo' => 'Duelo',
                    'trauma' => 'Trauma',
                )
            ),
            array(
                'id' => 2,
                'pregunta' => '¿Prefieres un enfoque más práctico o reflexivo?',
                'tipo' => 'radio',
                'opciones' => array(
                    'practico' => 'Práctico (ejercicios, tareas)',
                    'reflexivo' => 'Reflexivo (análisis, comprensión)',
                    'ambos' => 'Equilibrio entre ambos'
                )
            ),
            array(
                'id' => 3,
                'pregunta' => '¿Qué modalidad de consulta prefieres?',
                'tipo' => 'radio',
                'opciones' => array(
                    'online' => 'Online (videollamada)',
                    'presencial' => 'Presencial',
                    'ambas' => 'Ambas opciones'
                )
            ),
            array(
                'id' => 4,
                'pregunta' => '¿Tienes preferencia por el género del especialista?',
                'tipo' => 'radio',
                'opciones' => array(
                    'hombre' => 'Hombre',
                    'mujer' => 'Mujer',
                    'indiferente' => 'Indiferente'
                )
            ),
            array(
                'id' => 5,
                'pregunta' => '¿Qué rango de experiencia prefieres en tu especialista?',
                'tipo' => 'radio',
                'opciones' => array(
                    'junior' => 'Menos de 5 años',
                    'mid' => 'Entre 5 y 10 años',
                    'senior' => 'Más de 10 años',
                    'indiferente' => 'Indiferente'
                )
            )
        );
    }
}