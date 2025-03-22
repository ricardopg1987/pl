<?php
/**
 * Clase para manejar los shortcodes del plugin
 * 
 * Ruta: /includes/class-sgep-shortcodes.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Registrar los shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('sgep_login', array($this, 'login_shortcode'));
        add_shortcode('sgep_registro', array($this, 'registro_shortcode'));
        add_shortcode('sgep_panel_especialista', array($this, 'panel_especialista_shortcode'));
        add_shortcode('sgep_panel_cliente', array($this, 'panel_cliente_shortcode'));
        add_shortcode('sgep_test_match', array($this, 'test_match_shortcode'));
        add_shortcode('sgep_resultados_match', array($this, 'resultados_match_shortcode'));
        add_shortcode('sgep_directorio_especialistas', array($this, 'directorio_especialistas_shortcode'));
    }
    
    /**
     * Shortcode para el formulario de login
     */
    public function login_shortcode($atts) {
        // Si el usuario ya está logueado, redireccionar
        if (is_user_logged_in()) {
            $roles = new SGEP_Roles();
            $pages = get_option('sgep_pages', array());
            
            if ($roles->is_especialista()) {
                // En lugar de usar wp_redirect, usar JavaScript
                echo '<script>window.location.href = "' . esc_url(get_permalink($pages['sgep-panel-especialista'])) . '";</script>';
                return '';
            } elseif ($roles->is_cliente()) {
                // En lugar de usar wp_redirect, usar JavaScript
                echo '<script>window.location.href = "' . esc_url(get_permalink($pages['sgep-panel-cliente'])) . '";</script>';
                return '';
            }
        }
        
        // Procesar formulario
        $error = '';
        
        if (isset($_POST['sgep_login_nonce']) && wp_verify_nonce($_POST['sgep_login_nonce'], 'sgep_login')) {
            $credentials = array(
                'user_login' => sanitize_user($_POST['sgep_username']),
                'user_password' => $_POST['sgep_password'],
                'remember' => isset($_POST['sgep_remember'])
            );
            
            $user = wp_signon($credentials, false);
            
            if (is_wp_error($user)) {
                $error = $user->get_error_message();
            } else {
                $redirect_to = admin_url();
                $pages = get_option('sgep_pages', array());
                
                if (in_array('sgep_especialista', $user->roles) && isset($pages['sgep-panel-especialista'])) {
                    $redirect_to = get_permalink($pages['sgep-panel-especialista']);
                } elseif (in_array('sgep_cliente', $user->roles) && isset($pages['sgep-panel-cliente'])) {
                    $redirect_to = get_permalink($pages['sgep-panel-cliente']);
                }
                
                // En lugar de usar wp_redirect, usar JavaScript
                echo '<script>window.location.href = "' . esc_url($redirect_to) . '";</script>';
                return '';
            }
        }
        
        // Obtener URL de registro
        $pages = get_option('sgep_pages', array());
        $registro_url = isset($pages['sgep-registro']) ? get_permalink($pages['sgep-registro']) : '#';
        
        // Renderizar formulario
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/login-form.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode para el formulario de registro
     */
    public function registro_shortcode($atts) {
        // Si el usuario ya está logueado, redireccionar
        if (is_user_logged_in()) {
            $roles = new SGEP_Roles();
            $pages = get_option('sgep_pages', array());
            
            if ($roles->is_especialista()) {
                // En lugar de usar wp_redirect, usar JavaScript
                echo '<script>window.location.href = "' . esc_url(get_permalink($pages['sgep-panel-especialista'])) . '";</script>';
                return '';
            } elseif ($roles->is_cliente()) {
                // En lugar de usar wp_redirect, usar JavaScript
                echo '<script>window.location.href = "' . esc_url(get_permalink($pages['sgep-panel-cliente'])) . '";</script>';
                return '';
            }
        }
        
        // Procesar formulario
        $error = '';
        $success = '';
        
        if (isset($_POST['sgep_registro_nonce']) && wp_verify_nonce($_POST['sgep_registro_nonce'], 'sgep_registro')) {
            $username = sanitize_user($_POST['sgep_username']);
            $email = sanitize_email($_POST['sgep_email']);
            $password = $_POST['sgep_password'];
            $password_confirm = $_POST['sgep_password_confirm'];
            $role = sanitize_text_field($_POST['sgep_role']);
            
            // Validaciones
            if (empty($username) || empty($email) || empty($password)) {
                $error = __('Todos los campos son obligatorios.', 'sgep');
            } elseif (!is_email($email)) {
                $error = __('El email no es válido.', 'sgep');
            } elseif ($password !== $password_confirm) {
                $error = __('Las contraseñas no coinciden.', 'sgep');
            } elseif (strlen($password) < 8) {
                $error = __('La contraseña debe tener al menos 8 caracteres.', 'sgep');
            } elseif (username_exists($username)) {
                $error = __('El nombre de usuario ya existe.', 'sgep');
            } elseif (email_exists($email)) {
                $error = __('El email ya está registrado.', 'sgep');
            } elseif ($role !== 'cliente' && $role !== 'especialista') {
                $error = __('Rol inválido.', 'sgep');
            }
            
            // Crear usuario si no hay errores
            if (empty($error)) {
                $user_id = wp_create_user($username, $password, $email);
                
                if (is_wp_error($user_id)) {
                    $error = $user_id->get_error_message();
                } else {
                    // Asignar rol
                    $user = new WP_User($user_id);
                    $user->set_role($role === 'especialista' ? 'sgep_especialista' : 'sgep_cliente');
                    
                    // Configurar meta datos según rol
                    $roles_obj = new SGEP_Roles();
                    
                    if ($role === 'especialista') {
                        $roles_obj->setup_especialista_meta($user_id, array());
                    } else {
                        $roles_obj->setup_cliente_meta($user_id, array());
                    }
                    
                    // Enviar email de bienvenida
                    wp_new_user_notification($user_id, null, 'both');
                    
                    $success = __('Tu cuenta ha sido creada correctamente. Ahora puedes iniciar sesión.', 'sgep');
                    
                    // Opcional: Iniciar sesión automáticamente
                    /*
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    
                    $pages = get_option('sgep_pages', array());
                    $redirect_page = $role === 'especialista' ? 'sgep-panel-especialista' : 'sgep-panel-cliente';
                    
                    if (isset($pages[$redirect_page])) {
                        // En lugar de usar wp_redirect, usar JavaScript
                        echo '<script>window.location.href = "' . esc_url(get_permalink($pages[$redirect_page])) . '";</script>';
                        return '';
                    }
                    */
                }
            }
        }
        
        // Obtener URL de login
        $pages = get_option('sgep_pages', array());
        $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : '#';
        
        // Renderizar formulario
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/registro-form.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode para el panel de especialista
     */
    public function panel_especialista_shortcode($atts) {
        // Verificar que el usuario esté logueado y sea especialista
        if (!is_user_logged_in()) {
            $pages = get_option('sgep_pages', array());
            $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : wp_login_url();
            
            return '<p>' . sprintf(__('Debes <a href="%s">iniciar sesión</a> como especialista para acceder a esta página.', 'sgep'), $login_url) . '</p>';
        }
        
        $roles = new SGEP_Roles();
        
        if (!$roles->is_especialista()) {
            return '<p>' . __('No tienes permisos para acceder a esta página.', 'sgep') . '</p>';
        }
        
        // Obtener datos del especialista
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Obtener pestañas y acción actual
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Renderizar panel
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode para el panel de cliente
     */
    public function panel_cliente_shortcode($atts) {
        // Verificar que el usuario esté logueado y sea cliente
        if (!is_user_logged_in()) {
            $pages = get_option('sgep_pages', array());
            $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : wp_login_url();
            
            return '<p>' . sprintf(__('Debes <a href="%s">iniciar sesión</a> como cliente para acceder a esta página.', 'sgep'), $login_url) . '</p>';
        }
        
        $roles = new SGEP_Roles();
        
        if (!$roles->is_cliente()) {
            return '<p>' . __('No tienes permisos para acceder a esta página.', 'sgep') . '</p>';
        }
        
        // Obtener datos del cliente
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Obtener pestañas y acción actual
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Redireccionar al test si no lo ha completado
        global $wpdb;
        $test_realizado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d LIMIT 1",
            $user_id
        ));
        
        if (!$test_realizado && $tab !== 'test') {
            $pages = get_option('sgep_pages', array());
            $test_url = isset($pages['sgep-test-match']) ? get_permalink($pages['sgep-test-match']) : '#';
            
            return '<p>' . sprintf(__('Antes de continuar, debes <a href="%s">completar el test de compatibilidad</a>.', 'sgep'), $test_url) . '</p>';
        }
        
        // Renderizar panel
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode para el test de compatibilidad
     */
    public function test_match_shortcode($atts) {
        // Verificar que el usuario esté logueado
        if (!is_user_logged_in()) {
            $pages = get_option('sgep_pages', array());
            $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : wp_login_url();
            $registro_url = isset($pages['sgep-registro']) ? get_permalink($pages['sgep-registro']) : '#';
            
            ob_start();
            ?>
            <div class="sgep-test-guest">
                <h2><?php _e('Test de Compatibilidad', 'sgep'); ?></h2>
                <p><?php _e('Para realizar el test de compatibilidad y encontrar el especialista ideal para ti, necesitas iniciar sesión o registrarte.', 'sgep'); ?></p>
                <p>
                    <a href="<?php echo esc_url($login_url); ?>" class="button"><?php _e('Iniciar Sesión', 'sgep'); ?></a>
                    <a href="<?php echo esc_url($registro_url); ?>" class="button"><?php _e('Registrarse', 'sgep'); ?></a>
                </p>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Verificar si es cliente
        $roles = new SGEP_Roles();
        
        if (!$roles->is_cliente()) {
            return '<p>' . __('Solo los clientes pueden realizar el test de compatibilidad.', 'sgep') . '</p>';
        }
        
        // Verificar si ya realizó el test
        global $wpdb;
        $user_id = get_current_user_id();
        $test_realizado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d LIMIT 1",
            $user_id
        ));
        
        if ($test_realizado) {
            $pages = get_option('sgep_pages', array());
            $resultados_url = isset($pages['sgep-resultados-match']) ? get_permalink($pages['sgep-resultados-match']) : '#';
            
            return '<p>' . sprintf(__('Ya has realizado el test de compatibilidad. <a href="%s">Ver resultados</a>.', 'sgep'), $resultados_url) . '</p>';
        }
        
        // Procesar envío del formulario
        if (isset($_POST['sgep_test_nonce']) && wp_verify_nonce($_POST['sgep_test_nonce'], 'sgep_test_submit')) {
            $respuestas = array();
            $preguntas = get_option('sgep_test_questions', array());
            
            foreach ($preguntas as $pregunta) {
                $id = $pregunta['id'];
                
                if ($pregunta['tipo'] === 'multiple') {
                    $respuesta = isset($_POST['sgep_question_' . $id]) ? (array) $_POST['sgep_question_' . $id] : array();
                    $respuestas[$id] = array_map('sanitize_text_field', $respuesta);
                } else {
                    $respuestas[$id] = isset($_POST['sgep_question_' . $id]) ? sanitize_text_field($_POST['sgep_question_' . $id]) : '';
                }
            }
            
            // Guardar respuestas
            $wpdb->insert(
                $wpdb->prefix . 'sgep_test_resultados',
                array(
                    'cliente_id' => $user_id,
                    'respuestas' => serialize($respuestas),
                    'resultado' => '', // Se calcula en un paso posterior
                    'created_at' => current_time('mysql')
                )
            );
            
            $test_id = $wpdb->insert_id;
            
            // Calcular matches
            $this->calcular_matches($user_id, $test_id, $respuestas);
            
            // Redireccionar a resultados
            $pages = get_option('sgep_pages', array());
            $resultados_url = isset($pages['sgep-resultados-match']) ? get_permalink($pages['sgep-resultados-match']) : '#';
            
            // En lugar de usar wp_redirect, usar JavaScript
            echo '<script>window.location.href = "' . esc_url($resultados_url) . '";</script>';
            return '';
        }
        
        // Obtener preguntas del test
        $preguntas = get_option('sgep_test_questions', array());
        
        // Renderizar formulario
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/test-match-form.php');
        return ob_get_clean();
    }
    
    /**
     * Calcula los matches entre el cliente y los especialistas
     */
    private function calcular_matches($cliente_id, $test_id, $respuestas) {
        global $wpdb;
        
        // Obtener todos los especialistas
        $roles = new SGEP_Roles();
        $especialistas = $roles->get_all_especialistas();
        
        $matches = array();
        
        foreach ($especialistas as $especialista) {
            $especialista_id = $especialista->ID;
            
            // Obtener metadatos del especialista
            $especialidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
            if (!is_array($especialidades)) {
                $especialidades = array();
            }
            
            $enfoque = get_user_meta($especialista_id, 'sgep_metodologias', true);
            $modalidad_online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
            $modalidad_presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
            $genero = get_user_meta($especialista_id, 'sgep_genero', true);
            $experiencia_years = (int) get_user_meta($especialista_id, 'sgep_experiencia', true);
            
            // Calcular puntaje base (50 puntos)
            $puntaje = 50;
            
            // Ponderar según áreas de interés (hasta 20 puntos)
            if (isset($respuestas[1]) && is_array($respuestas[1])) {
                $areas_match = array_intersect($respuestas[1], $especialidades);
                $puntaje += min(count($areas_match) * 5, 20);
            }
            
            // Ponderar según enfoque (hasta 10 puntos)
            if (isset($respuestas[2]) && !empty($enfoque)) {
                if ($respuestas[2] === $enfoque || $respuestas[2] === 'ambos') {
                    $puntaje += 10;
                }
            }
            
            // Ponderar según modalidad (hasta 10 puntos)
            if (isset($respuestas[3])) {
                if ($respuestas[3] === 'online' && $modalidad_online) {
                    $puntaje += 10;
                } elseif ($respuestas[3] === 'presencial' && $modalidad_presencial) {
                    $puntaje += 10;
                } elseif ($respuestas[3] === 'ambas' && $modalidad_online && $modalidad_presencial) {
                    $puntaje += 10;
                }
            }
            
            // Ponderar según género (hasta 5 puntos)
            if (isset($respuestas[4]) && !empty($genero)) {
                if ($respuestas[4] === $genero || $respuestas[4] === 'indiferente') {
                    $puntaje += 5;
                }
            }
            
            // Ponderar según experiencia (hasta 5 puntos)
            if (isset($respuestas[5])) {
                if ($respuestas[5] === 'junior' && $experiencia_years < 5) {
                    $puntaje += 5;
                } elseif ($respuestas[5] === 'mid' && $experiencia_years >= 5 && $experiencia_years <= 10) {
                    $puntaje += 5;
                } elseif ($respuestas[5] === 'senior' && $experiencia_years > 10) {
                    $puntaje += 5;
                } elseif ($respuestas[5] === 'indiferente') {
                    $puntaje += 5;
                }
            }
            
            // Guardar match
            $wpdb->insert(
                $wpdb->prefix . 'sgep_matches',
                array(
                    'cliente_id' => $cliente_id,
                    'especialista_id' => $especialista_id,
                    'test_resultado_id' => $test_id,
                    'puntaje' => $puntaje,
                    'created_at' => current_time('mysql')
                )
            );
            
            $matches[] = array(
                'especialista_id' => $especialista_id,
                'puntaje' => $puntaje
            );
        }
        
        // Actualizar resultado del test con la lista ordenada de matches
        usort($matches, function($a, $b) {
            return $b['puntaje'] - $a['puntaje']; // Ordenar de mayor a menor
        });
        
        $wpdb->update(
            $wpdb->prefix . 'sgep_test_resultados',
            array('resultado' => serialize($matches)),
            array('id' => $test_id)
        );
        
        return $matches;
    }
    
    /**
     * Shortcode para mostrar los resultados del test de compatibilidad
     */
    public function resultados_match_shortcode($atts) {
        // Verificar que el usuario esté logueado
        if (!is_user_logged_in()) {
            $pages = get_option('sgep_pages', array());
            $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : wp_login_url();
            
            return '<p>' . sprintf(__('Debes <a href="%s">iniciar sesión</a> para ver tus resultados.', 'sgep'), $login_url) . '</p>';
        }
        
        // Verificar si es cliente
        $roles = new SGEP_Roles();
        
        if (!$roles->is_cliente()) {
            return '<p>' . __('Solo los clientes pueden ver los resultados del test de compatibilidad.', 'sgep') . '</p>';
        }
        
        // Verificar si ya realizó el test
        global $wpdb;
        $user_id = get_current_user_id();
        $test_resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ));
        
        if (!$test_resultado) {
            $pages = get_option('sgep_pages', array());
            $test_url = isset($pages['sgep-test-match']) ? get_permalink($pages['sgep-test-match']) : '#';
            
            return '<p>' . sprintf(__('Aún no has realizado el test de compatibilidad. <a href="%s">Realizar test</a>.', 'sgep'), $test_url) . '</p>';
        }
        
        // Obtener matches
        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_matches WHERE cliente_id = %d AND test_resultado_id = %d ORDER BY puntaje DESC LIMIT 3",
            $user_id, $test_resultado->id
        ));
        
        // Renderizar resultados
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/resultados-match.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode para mostrar el directorio de especialistas
     */
    public function directorio_especialistas_shortcode($atts) {
        // Procesar atributos
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'orderby' => 'rating',
            'order' => 'DESC',
        ), $atts);
        
        // Obtener parámetros de filtro de la URL
        $current_page = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
        $especialidad = isset($_GET['especialidad']) ? sanitize_text_field($_GET['especialidad']) : '';
        $modalidad = isset($_GET['modalidad']) ? sanitize_text_field($_GET['modalidad']) : '';
        $genero = isset($_GET['genero']) ? sanitize_text_field($_GET['genero']) : '';
        
        // Obtener todos los especialistas
        $roles = new SGEP_Roles();
        $especialistas = $roles->get_all_especialistas();
        
        // Filtrar especialistas
        $especialistas_filtrados = array();
        
        foreach ($especialistas as $especialista) {
            $especialista_id = $especialista->ID;
            
            // Aplicar filtros
            if (!empty($especialidad)) {
                $especialidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
                if (!is_array($especialidades) || !in_array($especialidad, $especialidades)) {
                    continue;
                }
            }
            
            if (!empty($modalidad)) {
                $online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
                $presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
                
                if ($modalidad === 'online' && !$online) {
                    continue;
                } elseif ($modalidad === 'presencial' && !$presencial) {
                    continue;
                }
            }
            
            if (!empty($genero)) {
                $e_genero = get_user_meta($especialista_id, 'sgep_genero', true);
                if ($e_genero !== $genero) {
                    continue;
                }
            }
            
            // Agregar a la lista de filtrados
            $especialistas_filtrados[] = $especialista;
        }
        
        // Ordenar especialistas
        usort($especialistas_filtrados, function($a, $b) use ($atts) {
            if ($atts['orderby'] === 'rating') {
                $rating_a = floatval(get_user_meta($a->ID, 'sgep_rating', true));
                $rating_b = floatval(get_user_meta($b->ID, 'sgep_rating', true));
                
                if ($atts['order'] === 'DESC') {
                    return $rating_b - $rating_a;
                } else {
                    return $rating_a - $rating_b;
                }
            } else {
                return strcasecmp($a->display_name, $b->display_name);
            }
        });
        
        // Paginación
        $total_items = count($especialistas_filtrados);
        $total_pages = ceil($total_items / $atts['per_page']);
        
        $offset = ($current_page - 1) * $atts['per_page'];
        $especialistas_paginados = array_slice($especialistas_filtrados, $offset, $atts['per_page']);
        
        // Renderizar directorio
        ob_start();
        include(SGEP_PLUGIN_DIR . 'public/templates/directorio-especialistas.php');
        return ob_get_clean();
    }
}