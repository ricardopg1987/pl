<?php
/**
 * Clase para manejar las peticiones AJAX
 * 
 * Ruta: /includes/class-sgep-ajax.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Acciones para usuarios logueados
        add_action('wp_ajax_sgep_guardar_disponibilidad', array($this, 'guardar_disponibilidad'));
        add_action('wp_ajax_sgep_eliminar_disponibilidad', array($this, 'eliminar_disponibilidad'));
        add_action('wp_ajax_sgep_agendar_cita', array($this, 'agendar_cita'));
        add_action('wp_ajax_sgep_cancelar_cita', array($this, 'cancelar_cita'));
        add_action('wp_ajax_sgep_confirmar_cita', array($this, 'confirmar_cita'));
        add_action('wp_ajax_sgep_enviar_mensaje', array($this, 'enviar_mensaje'));
        add_action('wp_ajax_sgep_marcar_mensaje_leido', array($this, 'marcar_mensaje_leido'));
        
        // Nueva acción para obtener horas disponibles
        add_action('wp_ajax_sgep_obtener_horas_disponibles', array($this, 'obtener_horas_disponibles'));
        add_action('wp_ajax_nopriv_sgep_obtener_horas_disponibles', array($this, 'obtener_horas_disponibles'));
        
        // Acciones para usuarios no logueados
        add_action('wp_ajax_nopriv_sgep_obtener_especialistas', array($this, 'obtener_especialistas'));
        add_action('wp_ajax_sgep_obtener_especialistas', array($this, 'obtener_especialistas'));
    }
    
    /**
     * Guardar disponibilidad de especialista
     */
    public function guardar_disponibilidad() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_manage_schedule')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
        }
        
        // Obtener datos
        $dia_semana = isset($_POST['dia_semana']) ? intval($_POST['dia_semana']) : 0;
        $hora_inicio = isset($_POST['hora_inicio']) ? sanitize_text_field($_POST['hora_inicio']) : '';
        $hora_fin = isset($_POST['hora_fin']) ? sanitize_text_field($_POST['hora_fin']) : '';
        
        // Validaciones
        if ($dia_semana < 0 || $dia_semana > 6) {
            wp_send_json_error(__('Día de la semana inválido.', 'sgep'));
        }
        
        if (empty($hora_inicio) || empty($hora_fin)) {
            wp_send_json_error(__('Debes especificar la hora de inicio y fin.', 'sgep'));
        }
        
        // Insertar disponibilidad
        global $wpdb;
        $resultado = $wpdb->insert(
            $wpdb->prefix . 'sgep_disponibilidad',
            array(
                'especialista_id' => get_current_user_id(),
                'dia_semana' => $dia_semana,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin
            )
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al guardar la disponibilidad.', 'sgep'));
        }
        
        $id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'id' => $id,
            'message' => __('Disponibilidad guardada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Eliminar disponibilidad de especialista
     */
    public function eliminar_disponibilidad() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_manage_schedule')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
        }
        
        // Obtener datos
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(__('ID de disponibilidad inválido.', 'sgep'));
        }
        
        // Verificar que la disponibilidad pertenezca al especialista actual
        global $wpdb;
        $disponibilidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_disponibilidad WHERE id = %d",
            $id
        ));
        
        if (!$disponibilidad || $disponibilidad->especialista_id != get_current_user_id()) {
            wp_send_json_error(__('No tienes permisos para eliminar esta disponibilidad.', 'sgep'));
        }
        
        // Eliminar disponibilidad
        $resultado = $wpdb->delete(
            $wpdb->prefix . 'sgep_disponibilidad',
            array('id' => $id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al eliminar la disponibilidad.', 'sgep'));
        }
        
        wp_send_json_success(array(
            'message' => __('Disponibilidad eliminada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Agendar cita con especialista
     */
    public function agendar_cita() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_book_appointments')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
        }
        
        // Obtener datos
        $especialista_id = isset($_POST['especialista_id']) ? intval($_POST['especialista_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $hora = isset($_POST['hora']) ? sanitize_text_field($_POST['hora']) : '';
        $notas = isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '';
        
        // Validar datos
        if ($especialista_id <= 0) {
            wp_send_json_error(__('Especialista inválido.', 'sgep'));
        }
        
        if (empty($fecha) || empty($hora)) {
            wp_send_json_error(__('Debes especificar la fecha y hora de la cita.', 'sgep'));
        }
        
        // Verificar que el especialista exista
        $roles = new SGEP_Roles();
        if (!$roles->is_especialista($especialista_id)) {
            wp_send_json_error(__('El especialista seleccionado no existe.', 'sgep'));
        }
        
        // Verificar disponibilidad
        $fecha_hora = $fecha . ' ' . $hora;
        $timestamp = strtotime($fecha_hora);
        $dia_semana = date('w', $timestamp);
        
        global $wpdb;
        $disponibilidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_disponibilidad 
            WHERE especialista_id = %d 
            AND dia_semana = %d 
            AND %s BETWEEN hora_inicio AND hora_fin",
            $especialista_id, $dia_semana, $hora
        ));
        
        if (!$disponibilidad) {
            wp_send_json_error(__('El especialista no tiene disponibilidad en el horario seleccionado.', 'sgep'));
        }
        
        // Verificar que no haya otra cita en ese horario
        $cita_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas 
            WHERE especialista_id = %d 
            AND fecha = %s 
            AND estado != 'cancelada'",
            $especialista_id, $fecha_hora
        ));
        
        if ($cita_existente) {
            wp_send_json_error(__('Ya existe una cita agendada en el horario seleccionado.', 'sgep'));
        }
        
        // Obtener duración de la consulta
        $duracion = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
        $duracion = $duracion ? intval($duracion) : 60;
        
        // Insertar cita
        $resultado = $wpdb->insert(
            $wpdb->prefix . 'sgep_citas',
            array(
                'especialista_id' => $especialista_id,
                'cliente_id' => get_current_user_id(),
                'fecha' => $fecha_hora,
                'duracion' => $duracion,
                'estado' => 'pendiente',
                'notas' => $notas,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al agendar la cita.', 'sgep'));
        }
        
        $cita_id = $wpdb->insert_id;
        
        // Enviar notificación al especialista
        $this->enviar_notificacion_cita($cita_id, 'nueva');
        
        wp_send_json_success(array(
            'id' => $cita_id,
            'message' => __('Cita agendada correctamente. Esperando confirmación del especialista.', 'sgep')
        ));
    }
    
    /**
     * Cancelar cita
     */
    public function cancelar_cita() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        
        if ($cita_id <= 0) {
            wp_send_json_error(__('ID de cita inválido.', 'sgep'));
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
        }
        
        // Verificar permisos
        $usuario_actual = get_current_user_id();
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($usuario_actual) && $cita->especialista_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para cancelar esta cita.', 'sgep'));
        }
        
        if ($roles->is_cliente($usuario_actual) && $cita->cliente_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para cancelar esta cita.', 'sgep'));
        }
        
        // Verificar que la cita no esté ya cancelada
        if ($cita->estado === 'cancelada') {
            wp_send_json_error(__('La cita ya ha sido cancelada.', 'sgep'));
        }
        
        // Actualizar estado de la cita
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_citas',
            array(
                'estado' => 'cancelada',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $cita_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al cancelar la cita.', 'sgep'));
        }
        
        // Enviar notificación
        $this->enviar_notificacion_cita($cita_id, 'cancelada');
        
        wp_send_json_success(array(
            'message' => __('Cita cancelada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Confirmar cita
     */
    public function confirmar_cita() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_manage_schedule')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
        }
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        $zoom_link = isset($_POST['zoom_link']) ? esc_url_raw($_POST['zoom_link']) : '';
        $zoom_id = isset($_POST['zoom_id']) ? sanitize_text_field($_POST['zoom_id']) : '';
        $zoom_password = isset($_POST['zoom_password']) ? sanitize_text_field($_POST['zoom_password']) : '';
        
        if ($cita_id <= 0) {
            wp_send_json_error(__('ID de cita inválido.', 'sgep'));
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
        }
        
        // Verificar que el especialista sea el dueño de la cita
        if ($cita->especialista_id != get_current_user_id()) {
            wp_send_json_error(__('No tienes permisos para confirmar esta cita.', 'sgep'));
        }
        
        // Verificar que la cita esté pendiente
        if ($cita->estado !== 'pendiente') {
            wp_send_json_error(__('Solo se pueden confirmar citas pendientes.', 'sgep'));
        }
        
        // Actualizar cita
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_citas',
            array(
                'estado' => 'confirmada',
                'zoom_link' => $zoom_link,
                'zoom_id' => $zoom_id,
                'zoom_password' => $zoom_password,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $cita_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al confirmar la cita.', 'sgep'));
        }
        
        // Enviar notificación
        $this->enviar_notificacion_cita($cita_id, 'confirmada');
        
        wp_send_json_success(array(
            'message' => __('Cita confirmada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Enviar mensaje entre especialista y cliente
     */
    public function enviar_mensaje() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_send_messages')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
        }
        
        // Obtener datos
        $destinatario_id = isset($_POST['destinatario_id']) ? intval($_POST['destinatario_id']) : 0;
        $asunto = isset($_POST['asunto']) ? sanitize_text_field($_POST['asunto']) : '';
        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
        
        // Validar datos
        if ($destinatario_id <= 0) {
            wp_send_json_error(__('Destinatario inválido.', 'sgep'));
        }
        
        if (empty($asunto) || empty($mensaje)) {
            wp_send_json_error(__('El asunto y el mensaje son obligatorios.', 'sgep'));
        }
        
        // Verificar que el destinatario exista
        $destinatario = get_userdata($destinatario_id);
        if (!$destinatario) {
            wp_send_json_error(__('El destinatario no existe.', 'sgep'));
        }
        
        // Verificar relación entre remitente y destinatario
        $roles = new SGEP_Roles();
        $remitente_id = get_current_user_id();
        
        if ($roles->is_especialista($remitente_id) && !$roles->is_cliente($destinatario_id)) {
            wp_send_json_error(__('Solo puedes enviar mensajes a clientes.', 'sgep'));
        }
        
        if ($roles->is_cliente($remitente_id) && !$roles->is_especialista($destinatario_id)) {
            wp_send_json_error(__('Solo puedes enviar mensajes a especialistas.', 'sgep'));
        }
        
        // Insertar mensaje
        global $wpdb;
        $resultado = $wpdb->insert(
            $wpdb->prefix . 'sgep_mensajes',
            array(
                'remitente_id' => $remitente_id,
                'destinatario_id' => $destinatario_id,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'leido' => 0,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al enviar el mensaje.', 'sgep'));
        }
        
        $mensaje_id = $wpdb->insert_id;
        
        // Enviar notificación por email
        $this->enviar_notificacion_mensaje($mensaje_id);
        
        wp_send_json_success(array(
            'id' => $mensaje_id,
            'message' => __('Mensaje enviado correctamente.', 'sgep')
        ));
    }
    
    /**
     * Marcar mensaje como leído
     */
    public function marcar_mensaje_leido() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $mensaje_id = isset($_POST['mensaje_id']) ? intval($_POST['mensaje_id']) : 0;
        
        if ($mensaje_id <= 0) {
            wp_send_json_error(__('ID de mensaje inválido.', 'sgep'));
        }
        
        // Obtener mensaje
        global $wpdb;
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_mensajes WHERE id = %d",
            $mensaje_id
        ));
        
        if (!$mensaje) {
            wp_send_json_error(__('El mensaje no existe.', 'sgep'));
        }
        
        // Verificar que el usuario actual sea el destinatario
        if ($mensaje->destinatario_id != get_current_user_id()) {
            wp_send_json_error(__('No tienes permisos para marcar este mensaje como leído.', 'sgep'));
        }
        
        // Actualizar mensaje
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_mensajes',
            array('leido' => 1),
            array('id' => $mensaje_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al marcar el mensaje como leído.', 'sgep'));
        }
        
        wp_send_json_success(array(
            'message' => __('Mensaje marcado como leído.', 'sgep')
        ));
    }
    
    /**
     * Obtener especialistas (filtrados)
     */
    public function obtener_especialistas() {
        // Obtener parámetros de filtro
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
            
            // Agregar a la lista de filtrados con datos necesarios
            $especialistas_filtrados[] = array(
                'id' => $especialista_id,
                'nombre' => $especialista->display_name,
                'avatar' => get_avatar_url($especialista_id),
                'especialidad' => get_user_meta($especialista_id, 'sgep_especialidad', true),
                'rating' => get_user_meta($especialista_id, 'sgep_rating', true),
                'precio' => get_user_meta($especialista_id, 'sgep_precio_consulta', true),
                'online' => (bool) get_user_meta($especialista_id, 'sgep_acepta_online', true),
                'presencial' => (bool) get_user_meta($especialista_id, 'sgep_acepta_presencial', true),
            );
        }
        
        wp_send_json_success(array(
            'especialistas' => $especialistas_filtrados
        ));
    }
    
    /**
     * Obtener horas disponibles según fecha y especialista
     */
    public function obtener_horas_disponibles() {
        // Verificar nonce para seguridad
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $especialista_id = isset($_GET['especialista_id']) ? intval($_GET['especialista_id']) : 0;
        $fecha = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
        
        // Validaciones
        if ($especialista_id <= 0 || empty($fecha)) {
            wp_send_json_error(__('Datos incompletos', 'sgep'));
            return;
        }
        
        // Obtener día de la semana de la fecha seleccionada
        $timestamp = strtotime($fecha);
        $dia_semana = date('w', $timestamp);
        
        // Obtener disponibilidad del especialista para ese día
        global $wpdb;
        $disponibilidad = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_disponibilidad 
            WHERE especialista_id = %d AND dia_semana = %d
            ORDER BY hora_inicio",
            $especialista_id, $dia_semana
        ));
        
        // Obtener citas ya agendadas para ese día y especialista
        $citas_agendadas = $wpdb->get_results($wpdb->prepare(
            "SELECT TIME_FORMAT(DATE_FORMAT(fecha, '%%H:%%i:%%s'), '%%H:%%i') as hora
             FROM {$wpdb->prefix}sgep_citas
             WHERE especialista_id = %d 
             AND DATE(fecha) = %s
             AND estado != 'cancelada'",
            $especialista_id, $fecha
        ));
        
        // Convertir a array simple para facilitar la comparación
        $horas_ocupadas = array();
        foreach ($citas_agendadas as $cita) {
            $horas_ocupadas[] = $cita->hora;
        }
        
        // Generar horas disponibles en intervalos de la duración de la consulta
        $horas_disponibles = array();
        $duracion_consulta = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
        $duracion_consulta = $duracion_consulta ? intval($duracion_consulta) : 60; // Valor por defecto: 60 minutos
        
        foreach ($disponibilidad as $slot) {
            $hora_inicio = strtotime($slot->hora_inicio);
            $hora_fin = strtotime($slot->hora_fin);
            
            // Generar intervalos
            for ($hora = $hora_inicio; $hora < $hora_fin; $hora += $duracion_consulta * 60) {
                $hora_formateada = date('H:i', $hora);
                
                // Verificar que la hora no esté ocupada
                if (!in_array($hora_formateada, $horas_ocupadas)) {
                    $horas_disponibles[] = $hora_formateada;
                }
            }
        }
        
        wp_send_json_success(array(
            'horas' => $horas_disponibles
        ));
    }
    
    /**
     * Enviar notificación de cita
     */
    private function enviar_notificacion_cita($cita_id, $tipo) {
        // Verificar si las notificaciones están habilitadas
        $notificaciones = get_option('sgep_email_notifications', 1);
        
        if (!$notificaciones) {
            return;
        }
        
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            return;
        }
        
        $especialista = get_userdata($cita->especialista_id);
        $cliente = get_userdata($cita->cliente_id);
        
        if (!$especialista || !$cliente) {
            return;
        }
        
        $fecha_hora = new DateTime($cita->fecha);
        $fecha_formateada = $fecha_hora->format('d/m/Y H:i');
        
        switch ($tipo) {
            case 'nueva':
                // Notificar al especialista
                $asunto = sprintf(__('Nueva cita agendada para el %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(
                    __('El cliente %s ha agendado una cita contigo para el %s. Por favor, confirma o cancela la cita desde tu panel de especialista.', 'sgep'),
                    $cliente->display_name,
                    $fecha_formateada
                );
                
                wp_mail($especialista->user_email, $asunto, $mensaje);
                break;
                
            case 'confirmada':
                // Notificar al cliente
                $asunto = sprintf(__('Cita confirmada para el %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(
                    __('Tu cita con %s para el %s ha sido confirmada. ', 'sgep'),
                    $especialista->display_name,
                    $fecha_formateada
                );
                
                if (!empty($cita->zoom_link)) {
                    $mensaje .= sprintf(
                        __('Enlace de Zoom: %s (ID: %s, Contraseña: %s)', 'sgep'),
                        $cita->zoom_link,
                        $cita->zoom_id,
                        $cita->zoom_password
                    );
                }
                
                wp_mail($cliente->user_email, $asunto, $mensaje);
                break;
                
            case 'cancelada':
                // Notificar al otro usuario
                $remitente_id = get_current_user_id();
                
                if ($remitente_id == $cita->especialista_id) {
                    // Especialista canceló
                    $destinatario = $cliente;
                    $asunto = sprintf(__('Cita cancelada para el %s', 'sgep'), $fecha_formateada);
                    $mensaje = sprintf(
                        __('Tu cita con %s para el %s ha sido cancelada por el especialista.', 'sgep'),
                        $especialista->display_name,
                        $fecha_formateada
                    );
                } else {
                    // Cliente canceló
                    $destinatario = $especialista;
                    $asunto = sprintf(__('Cita cancelada para el %s', 'sgep'), $fecha_formateada);
                    $mensaje = sprintf(
                        __('La cita con %s para el %s ha sido cancelada por el cliente.', 'sgep'),
                        $cliente->display_name,
                        $fecha_formateada
                    );
                }
                
                wp_mail($destinatario->user_email, $asunto, $mensaje);
                break;
        }
    }
    
    /**
     * Enviar notificación de mensaje
     */
    /**
     * Enviar notificación de mensaje
     */
    private function enviar_notificacion_mensaje($mensaje_id) {
        // Verificar si las notificaciones están habilitadas
        $notificaciones = get_option('sgep_email_notifications', 1);
        
        if (!$notificaciones) {
            return;
        }
        
        global $wpdb;
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_mensajes WHERE id = %d",
            $mensaje_id
        ));
        
        if (!$mensaje) {
            return;
        }
        
        $remitente = get_userdata($mensaje->remitente_id);
        $destinatario = get_userdata($mensaje->destinatario_id);
        
        if (!$remitente || !$destinatario) {
            return;
        }
        
        $asunto = sprintf(__('Nuevo mensaje de %s: %s', 'sgep'), $remitente->display_name, $mensaje->asunto);
        $contenido = sprintf(
            __('Has recibido un nuevo mensaje de %s:<br><br>%s<br><br>Para responder, inicia sesión en tu cuenta.', 'sgep'),
            $remitente->display_name,
            $mensaje->mensaje
        );
        
        wp_mail($destinatario->user_email, $asunto, $contenido);
    }
    /**
 * Añade estas funciones a la clase SGEP_Ajax en includes/class-sgep-ajax.php
 * Puedes añadirlas justo antes del cierre de la clase (antes del último "}")
 */

/**
 * Obtener detalles de un producto
 */
public function obtener_producto_detalles() {
    // Verificar nonce
    check_ajax_referer('sgep_ajax_nonce', 'nonce');
    
    // Obtener ID del producto
    $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
    
    if ($producto_id <= 0) {
        wp_send_json_error(__('ID de producto no válido', 'sgep'));
        return;
    }
    
    // Obtener datos del producto
    global $wpdb;
    $producto = $wpdb->get_row($wpdb->prepare(
        "SELECT p.*, e.display_name as especialista_nombre 
         FROM {$wpdb->prefix}sgep_productos p
         LEFT JOIN {$wpdb->users} e ON p.especialista_id = e.ID
         WHERE p.id = %d",
        $producto_id
    ));
    
    if (!$producto) {
        wp_send_json_error(__('Producto no encontrado', 'sgep'));
        return;
    }
    
    // Construir HTML para el modal
    ob_start();
    ?>
    <div class="sgep-producto-detalle">
        <h3><?php echo esc_html($producto->nombre); ?></h3>
        
        <?php if (!empty($producto->categoria)) : ?>
            <div class="sgep-producto-detalle-categoria">
                <?php 
                $categorias = array(
                    'libros' => __('Libros', 'sgep'),
                    'cursos' => __('Cursos', 'sgep'),
                    'accesorios' => __('Accesorios', 'sgep'),
                    'esencias' => __('Esencias', 'sgep'),
                    'terapias' => __('Terapias', 'sgep'),
                    'aceites' => __('Aceites', 'sgep'),
                    'cristales' => __('Cristales', 'sgep'),
                    'otros' => __('Otros', 'sgep'),
                );
                
                echo isset($categorias[$producto->categoria]) ? 
                    esc_html($categorias[$producto->categoria]) : 
                    esc_html($producto->categoria);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="sgep-producto-detalle-imagen">
            <?php if (!empty($producto->imagen_url)) : ?>
                <img src="<?php echo esc_url($producto->imagen_url); ?>" alt="<?php echo esc_attr($producto->nombre); ?>">
            <?php endif; ?>
        </div>
        
        <div class="sgep-producto-detalle-info">
            <div class="sgep-producto-detalle-precio">
                <strong><?php _e('Precio:', 'sgep'); ?></strong> <?php echo esc_html($producto->precio); ?>
            </div>
            
            <?php if (!empty($producto->descripcion)) : ?>
                <div class="sgep-producto-detalle-descripcion">
                    <strong><?php _e('Descripción:', 'sgep'); ?></strong>
                    <p><?php echo nl2br(esc_html($producto->descripcion)); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($producto->sku)) : ?>
                <div class="sgep-producto-detalle-sku">
                    <strong><?php _e('SKU:', 'sgep'); ?></strong> <?php echo esc_html($producto->sku); ?>
                </div>
            <?php endif; ?>
            
            <div class="sgep-producto-detalle-stock">
                <strong><?php _e('Disponibilidad:', 'sgep'); ?></strong> 
                <?php 
                if ($producto->stock > 0) {
                    echo sprintf(__('%d unidades disponibles', 'sgep'), $producto->stock);
                } elseif ($producto->stock == 0) {
                    echo __('Producto digital / Stock ilimitado', 'sgep');
                } else {
                    echo __('Agotado', 'sgep');
                }
                ?>
            </div>
            
            <div class="sgep-producto-detalle-especialista">
                <strong><?php _e('Creado por:', 'sgep'); ?></strong> <?php echo esc_html($producto->especialista_nombre); ?>
            </div>
        </div>
        
        <?php if ($producto->stock >= 0) : ?>
            <div class="sgep-producto-detalle-acciones">
                <a href="#" class="sgep-button sgep-button-primary sgep-comprar-producto" 
                   data-id="<?php echo esc_attr($producto->id); ?>"
                   data-nombre="<?php echo esc_attr($producto->nombre); ?>"
                   data-precio="<?php echo esc_attr($producto->precio); ?>">
                    <?php _e('Comprar ahora', 'sgep'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="sgep-producto-detalle-agotado">
                <?php _e('Producto agotado', 'sgep'); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html
    ));
}

/**
 * Procesar compra de producto
 */
public function comprar_producto() {
    // Verificar nonce
    check_ajax_referer('sgep_ajax_nonce', 'nonce');
    
    // Verificar si el usuario está logueado
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Debes iniciar sesión para realizar esta acción.', 'sgep'));
        return;
    }
    
    // Obtener ID del producto
    $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
    
    if ($producto_id <= 0) {
        wp_send_json_error(__('ID de producto no válido', 'sgep'));
        return;
    }
    
    // Obtener datos del producto
    global $wpdb;
    $producto = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_productos WHERE id = %d",
        $producto_id
    ));
    
    if (!$producto) {
        wp_send_json_error(__('Producto no encontrado', 'sgep'));
        return;
    }
    
    // Verificar disponibilidad
    if ($producto->stock < 0) {
        wp_send_json_error(__('Producto agotado', 'sgep'));
        return;
    }
    
    // Obtener ID del cliente
    $cliente_id = get_current_user_id();
    
    // Verificar si la tabla de pedidos existe
    $table_pedidos = $wpdb->prefix . 'sgep_pedidos';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_pedidos'") != $table_pedidos) {
        // Crear tabla si no existe
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_pedidos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cliente_id bigint(20) NOT NULL,
            fecha datetime NOT NULL,
            estado varchar(50) NOT NULL DEFAULT 'pendiente',
            total decimal(10,2) NOT NULL,
            notas text,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Crear tabla para detalles de pedido
        $table_detalles = $wpdb->prefix . 'sgep_pedidos_detalle';
        $sql = "CREATE TABLE $table_detalles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pedido_id bigint(20) NOT NULL,
            producto_id bigint(20) NOT NULL,
            cantidad int(11) NOT NULL DEFAULT 1,
            precio_unitario decimal(10,2) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }
    
    // Crear pedido
    $wpdb->insert(
        $table_pedidos,
        array(
            'cliente_id' => $cliente_id,
            'fecha' => current_time('mysql'),
            'estado' => 'pendiente',
            'total' => $producto->precio,
            'notas' => sprintf(__('Compra del producto %s', 'sgep'), $producto->nombre)
        )
    );
    
    $pedido_id = $wpdb->insert_id;
    
    // Agregar detalle de pedido
    $table_detalles = $wpdb->prefix . 'sgep_pedidos_detalle';
    $wpdb->insert(
        $table_detalles,
        array(
            'pedido_id' => $pedido_id,
            'producto_id' => $producto_id,
            'cantidad' => 1,
            'precio_unitario' => $producto->precio
        )
    );
    
    // Actualizar stock del producto si no es ilimitado
    if ($producto->stock > 0) {
        $wpdb->update(
            $wpdb->prefix . 'sgep_productos',
            array('stock' => $producto->stock - 1),
            array('id' => $producto_id)
        );
    }
    
    // Enviar notificación al especialista
    $this->enviar_notificacion_pedido($pedido_id);
    
    wp_send_json_success(array(
        'message' => sprintf(__('¡Compra realizada con éxito! Tu pedido #%d ha sido registrado y está en proceso.', 'sgep'), $pedido_id)
    ));
}

/**
 * Enviar notificación de nuevo pedido
 */
private function enviar_notificacion_pedido($pedido_id) {
    // Verificar si las notificaciones están habilitadas
    $notificaciones = get_option('sgep_email_notifications', 1);
    
    if (!$notificaciones) {
        return;
    }
    
    global $wpdb;
    
    // Obtener datos del pedido
    $pedido = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_pedidos WHERE id = %d",
        $pedido_id
    ));
    
    if (!$pedido) {
        return;
    }
    
    // Obtener detalles del pedido
    $detalles = $wpdb->get_results($wpdb->prepare(
        "SELECT d.*, p.nombre, p.especialista_id 
         FROM {$wpdb->prefix}sgep_pedidos_detalle d
         JOIN {$wpdb->prefix}sgep_productos p ON d.producto_id = p.id
         WHERE d.pedido_id = %d",
        $pedido_id
    ));
    
    if (empty($detalles)) {
        return;
    }
    
    // Obtener datos del cliente
    $cliente = get_userdata($pedido->cliente_id);
    
    if (!$cliente) {
        return;
    }
    
    // Para cada producto, enviar notificación al especialista correspondiente
    foreach ($detalles as $detalle) {
        if (empty($detalle->especialista_id)) {
            continue; // Producto sin especialista asociado (admin)
        }
        
        $especialista = get_userdata($detalle->especialista_id);
        
        if (!$especialista) {
            continue;
        }
        
        // Construir mensaje
        $asunto = sprintf(__('Nuevo pedido #%d - Producto: %s', 'sgep'), $pedido_id, $detalle->nombre);
        $mensaje = sprintf(
            __('Hola %s,

Has recibido un nuevo pedido:

Pedido #: %d
Producto: %s
Cantidad: %d
Precio: %s
Cliente: %s (%s)
Fecha: %s

Por favor, ponte en contacto con el cliente para coordinar la entrega o acceso al producto.

Saludos,
Sistema de Gestión de Especialistas y Pacientes', 'sgep'),
            $especialista->display_name,
            $pedido_id,
            $detalle->nombre,
            $detalle->cantidad,
            $detalle->precio_unitario,
            $cliente->display_name,
            $cliente->user_email,
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($pedido->fecha))
        );
        
        // Enviar correo
        wp_mail($especialista->user_email, $asunto, $mensaje);
    }
    
    // También notificar al cliente
    $asunto_cliente = sprintf(__('Confirmación de pedido #%d', 'sgep'), $pedido_id);
    
    // Construir lista de productos
    $productos_lista = '';
    $total = 0;
    
    foreach ($detalles as $detalle) {
        $productos_lista .= sprintf(
            "- %s: %s x %d = %s\n",
            $detalle->nombre,
            $detalle->precio_unitario,
            $detalle->cantidad,
            number_format($detalle->precio_unitario * $detalle->cantidad, 2)
        );
        
        $total += $detalle->precio_unitario * $detalle->cantidad;
    }
    
    $mensaje_cliente = sprintf(
        __('Hola %s,

Gracias por tu compra. A continuación, los detalles de tu pedido:

Pedido #: %d
Fecha: %s
Estado: %s

Productos:
%s
Total: %s

El especialista se pondrá en contacto contigo próximamente para coordinar los detalles.

Saludos,
Sistema de Gestión de Especialistas y Pacientes', 'sgep'),
        $cliente->display_name,
        $pedido_id,
        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($pedido->fecha)),
        __('Pendiente', 'sgep'),
        $productos_lista,
        number_format($total, 2)
    );
    
    // Enviar correo al cliente
    wp_mail($cliente->user_email, $asunto_cliente, $mensaje_cliente);
}
/**
 * Agrega estas líneas al constructor de la clase SGEP_Ajax en includes/class-sgep-ajax.php
 * Justo después de las otras acciones AJAX
 */

// Acciones AJAX para productos
add_action('wp_ajax_sgep_obtener_producto_detalles', array($this, 'obtener_producto_detalles'));
add_action('wp_ajax_nopriv_sgep_obtener_producto_detalles', array($this, 'obtener_producto_detalles'));
add_action('wp_ajax_sgep_comprar_producto', array($this, 'comprar_producto'));
}