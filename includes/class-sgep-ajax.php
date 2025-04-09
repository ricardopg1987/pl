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
}