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
        add_action('wp_ajax_sgep_obtener_horas_disponibles', array($this, 'obtener_horas_disponibles'));
        
        // Acciones para usuarios no logueados
        add_action('wp_ajax_nopriv_sgep_obtener_especialistas', array($this, 'obtener_especialistas'));
        add_action('wp_ajax_sgep_obtener_especialistas', array($this, 'obtener_especialistas'));

        // Acciones para gestión avanzada de citas
        add_action('wp_ajax_sgep_rechazar_cita', array($this, 'rechazar_cita'));
        add_action('wp_ajax_sgep_proponer_nueva_fecha', array($this, 'proponer_nueva_fecha'));
        add_action('wp_ajax_sgep_aceptar_nueva_fecha', array($this, 'aceptar_nueva_fecha'));
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
            return;
        }
        
        // Obtener datos
        $dia_semana = isset($_POST['dia_semana']) ? intval($_POST['dia_semana']) : 0;
        $hora_inicio = isset($_POST['hora_inicio']) ? sanitize_text_field($_POST['hora_inicio']) : '';
        $hora_fin = isset($_POST['hora_fin']) ? sanitize_text_field($_POST['hora_fin']) : '';
        
        // Validaciones
        if ($dia_semana < 0 || $dia_semana > 6) {
            wp_send_json_error(__('Día de la semana inválido.', 'sgep'));
            return;
        }
        
        if (empty($hora_inicio) || empty($hora_fin)) {
            wp_send_json_error(__('Debes especificar la hora de inicio y fin.', 'sgep'));
            return;
        }
        
        // Validar que la hora de inicio sea anterior a la hora de fin
        $inicio_timestamp = strtotime("1970-01-01 " . $hora_inicio);
        $fin_timestamp = strtotime("1970-01-01 " . $hora_fin);
        
        if ($inicio_timestamp >= $fin_timestamp) {
            wp_send_json_error(__('La hora de inicio debe ser anterior a la hora de fin.', 'sgep'));
            return;
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
            return;
        }
        
        $id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'id' => $id,
            'message' => __('Disponibilidad guardada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Rechazar cita (especialista rechaza cita de cliente)
     */
    public function rechazar_cita() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';
        
        if ($cita_id <= 0) {
            wp_send_json_error(__('ID de cita inválido.', 'sgep'));
            return;
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
            return;
        }
        
        // Verificar permisos
        $usuario_actual = get_current_user_id();
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($usuario_actual) && $cita->especialista_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para rechazar esta cita.', 'sgep'));
            return;
        }
        
        // Verificar que la cita esté pendiente
        if ($cita->estado !== 'pendiente') {
            wp_send_json_error(__('Solo se pueden rechazar citas pendientes.', 'sgep'));
            return;
        }
        
        // Actualizar estado de la cita
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_citas',
            array(
                'estado' => 'rechazada',
                'motivo_rechazo' => $motivo,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $cita_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al rechazar la cita.', 'sgep'));
            return;
        }
        
        // Enviar notificación al cliente
        $this->enviar_notificacion_cita($cita_id, 'rechazada');
        
        wp_send_json_success(array(
            'message' => __('Cita rechazada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Proponer nueva fecha para una cita
     */
    public function proponer_nueva_fecha() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        $nueva_fecha = isset($_POST['nueva_fecha']) ? sanitize_text_field($_POST['nueva_fecha']) : '';
        $nueva_hora = isset($_POST['nueva_hora']) ? sanitize_text_field($_POST['nueva_hora']) : '';
        
        if ($cita_id <= 0 || empty($nueva_fecha) || empty($nueva_hora)) {
            wp_send_json_error(__('Datos inválidos. Por favor, proporciona la nueva fecha y hora.', 'sgep'));
            return;
        }
        
        // Validar formato de fecha y hora
        $fecha_valida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $nueva_fecha);
        $hora_valida = preg_match('/^\d{2}:\d{2}$/', $nueva_hora);
        
        if (!$fecha_valida || !$hora_valida) {
            wp_send_json_error(__('Formato de fecha u hora inválido.', 'sgep'));
            return;
        }
        
        // Validar que la fecha no sea pasada
        $fecha_actual = new DateTime('now', new DateTimeZone(wp_timezone_string()));
        $fecha_propuesta = new DateTime($nueva_fecha . ' ' . $nueva_hora, new DateTimeZone(wp_timezone_string()));
        
        if ($fecha_propuesta < $fecha_actual) {
            wp_send_json_error(__('No puedes proponer una fecha pasada.', 'sgep'));
            return;
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
            return;
        }
        
        // Verificar permisos
        $usuario_actual = get_current_user_id();
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($usuario_actual) && $cita->especialista_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para modificar esta cita.', 'sgep'));
            return;
        }
        
        // Verificar que la cita esté pendiente
        if ($cita->estado !== 'pendiente') {
            wp_send_json_error(__('Solo se pueden modificar citas pendientes.', 'sgep'));
            return;
        }
        
        // Formatear la nueva fecha y hora
        $fecha_hora_propuesta = $nueva_fecha . ' ' . $nueva_hora;
        
        // Actualizar la cita con la propuesta
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_citas',
            array(
                'estado' => 'fecha_propuesta',
                'fecha_propuesta' => $fecha_hora_propuesta,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $cita_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al proponer la nueva fecha.', 'sgep'));
            return;
        }
        
        // Enviar notificación al cliente
        $this->enviar_notificacion_cita($cita_id, 'fecha_propuesta');
        
        wp_send_json_success(array(
            'message' => __('Nueva fecha propuesta correctamente. Esperando confirmación del cliente.', 'sgep')
        ));
    }
    
    /**
     * Aceptar o rechazar la nueva fecha propuesta (para clientes)
     */
    public function aceptar_nueva_fecha() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        $aceptar = isset($_POST['aceptar']) ? (bool)$_POST['aceptar'] : false;
        
        if ($cita_id <= 0) {
            wp_send_json_error(__('ID de cita inválido.', 'sgep'));
            return;
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
            return;
        }
        
        // Verificar permisos
        $usuario_actual = get_current_user_id();
        $roles = new SGEP_Roles();
        
        if ($roles->is_cliente($usuario_actual) && $cita->cliente_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para aceptar esta propuesta.', 'sgep'));
            return;
        }
        
        // Verificar que la cita tenga una fecha propuesta
        if ($cita->estado !== 'fecha_propuesta' || empty($cita->fecha_propuesta)) {
            wp_send_json_error(__('Esta cita no tiene una propuesta de fecha pendiente.', 'sgep'));
            return;
        }
        
        if ($aceptar) {
            // El cliente acepta la nueva fecha
            $resultado = $wpdb->update(
                $wpdb->prefix . 'sgep_citas',
                array(
                    'estado' => 'pendiente',
                    'fecha' => $cita->fecha_propuesta,
                    'fecha_propuesta' => null,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $cita_id)
            );
            
            $mensaje = __('Has aceptado la nueva fecha. La cita está pendiente de confirmación por el especialista.', 'sgep');
            $tipo_notificacion = 'nueva_fecha_aceptada';
        } else {
            // El cliente rechaza la nueva fecha (se mantiene la cita como pendiente con la fecha original)
            $resultado = $wpdb->update(
                $wpdb->prefix . 'sgep_citas',
                array(
                    'estado' => 'pendiente',
                    'fecha_propuesta' => null,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $cita_id)
            );
            
            $mensaje = __('Has rechazado la nueva fecha. La cita mantiene la fecha original y está pendiente de confirmación.', 'sgep');
            $tipo_notificacion = 'nueva_fecha_rechazada';
        }
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al procesar la respuesta.', 'sgep'));
            return;
        }
        
        // Enviar notificación al especialista
        $this->enviar_notificacion_cita($cita_id, $tipo_notificacion);
        
        wp_send_json_success(array(
            'message' => $mensaje
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
            return;
        }
        
        // Obtener datos
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(__('ID de disponibilidad inválido.', 'sgep'));
            return;
        }
        
        // Verificar que la disponibilidad pertenezca al especialista actual
        global $wpdb;
        $disponibilidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_disponibilidad WHERE id = %d",
            $id
        ));
        
        if (!$disponibilidad || $disponibilidad->especialista_id != get_current_user_id()) {
            wp_send_json_error(__('No tienes permisos para eliminar esta disponibilidad.', 'sgep'));
            return;
        }
        
        // Eliminar disponibilidad
        $resultado = $wpdb->delete(
            $wpdb->prefix . 'sgep_disponibilidad',
            array('id' => $id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al eliminar la disponibilidad.', 'sgep'));
            return;
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
            return;
        }
        
        // Obtener datos
        $especialista_id = isset($_POST['especialista_id']) ? intval($_POST['especialista_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $hora = isset($_POST['hora']) ? sanitize_text_field($_POST['hora']) : '';
        $notas = isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '';
        
        // Validaciones
        if ($especialista_id <= 0) {
            wp_send_json_error(__('Especialista inválido.', 'sgep'));
            return;
        }
        
        if (empty($fecha) || empty($hora)) {
            wp_send_json_error(__('Debes especificar la fecha y hora de la cita.', 'sgep'));
            return;
        }
        
        // Verificar que el especialista exista
        $roles = new SGEP_Roles();
        if (!$roles->is_especialista($especialista_id)) {
            wp_send_json_error(__('El especialista seleccionado no existe.', 'sgep'));
            return;
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
            return;
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
            return;
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
            return;
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
            return;
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
            return;
        }
        
        // Verificar permisos
        $usuario_actual = get_current_user_id();
        $roles = new SGEP_Roles();
        
        if ($roles->is_especialista($usuario_actual) && $cita->especialista_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para cancelar esta cita.', 'sgep'));
            return;
        }
        
        if ($roles->is_cliente($usuario_actual) && $cita->cliente_id != $usuario_actual) {
            wp_send_json_error(__('No tienes permisos para cancelar esta cita.', 'sgep'));
            return;
        }
        
        // Verificar que la cita no esté ya cancelada
        if ($cita->estado === 'cancelada') {
            wp_send_json_error(__('La cita ya ha sido cancelada.', 'sgep'));
            return;
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
            return;
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
            return;
        }
        
        // Obtener datos
        $cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
        $zoom_link = isset($_POST['zoom_link']) ? esc_url_raw($_POST['zoom_link']) : '';
        $zoom_id = isset($_POST['zoom_id']) ? sanitize_text_field($_POST['zoom_id']) : '';
        $zoom_password = isset($_POST['zoom_password']) ? sanitize_text_field($_POST['zoom_password']) : '';
        
        if ($cita_id <= 0) {
            wp_send_json_error(__('ID de cita inválido.', 'sgep'));
            return;
        }
        
        // Obtener cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_citas WHERE id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            wp_send_json_error(__('La cita no existe.', 'sgep'));
            return;
        }
        
        // Verificar que el especialista sea el dueño de la cita
        if ($cita->especialista_id != get_current_user_id()) {
            wp_send_json_error(__('No tienes permisos para confirmar esta cita.', 'sgep'));
            return;
        }
        
        // Verificar que la cita esté pendiente
        if ($cita->estado !== 'pendiente') {
            wp_send_json_error(__('Solo se pueden confirmar citas pendientes.', 'sgep'));
            return;
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
            return;
        }
        
        // Enviar notificación
        $this->enviar_notificacion_cita($cita_id, 'confirmada');
        
        wp_send_json_success(array(
            'message' => __('Cita confirmada correctamente.', 'sgep')
        ));
    }
    
    /**
     * Obtener horas disponibles para una fecha específica
     */
    public function obtener_horas_disponibles() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Obtener datos
        $especialista_id = isset($_GET['especialista_id']) ? intval($_GET['especialista_id']) : 0;
        $fecha = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
        
        // Validar datos
        if ($especialista_id <= 0 || empty($fecha)) {
            wp_send_json_error(__('Parámetros inválidos.', 'sgep'));
            return;
        }
        
        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            wp_send_json_error(__('Formato de fecha inválido.', 'sgep'));
            return;
        }
        
        // Obtener día de la semana para la fecha seleccionada
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
        
        if (empty($disponibilidad)) {
            wp_send_json_success(array('horas' => array()));
            return;
        }
        
        // Obtener citas ya agendadas para esa fecha
        $citas = $wpdb->get_results($wpdb->prepare(
            "SELECT TIME_FORMAT(TIME(fecha), '%%H:%%i') as hora,
            TIME_FORMAT(DATE_ADD(fecha, INTERVAL duracion MINUTE), '%%H:%%i') as hora_fin 
            FROM {$wpdb->prefix}sgep_citas 
            WHERE especialista_id = %d 
            AND DATE(fecha) = %s 
            AND estado != 'cancelada'",
            $especialista_id, $fecha
        ));
        
        $horas_ocupadas = array();
        foreach ($citas as $cita) {
            // Considerar el intervalo completo de la cita
            $inicio = strtotime($cita->hora);
            $fin = strtotime($cita->hora_fin);
            
            // Guardar todas las horas que se solapan con la cita
            for ($hora = $inicio; $hora < $fin; $hora += 30 * 60) {
                $horas_ocupadas[] = date('H:i', $hora);
            }
        }
        
        // Generar horas disponibles basadas en la disponibilidad del especialista
        $horas_disponibles = array();
        $duracion_consulta = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
        $duracion_consulta = $duracion_consulta ? intval($duracion_consulta) : 60;
        $intervalo_minutos = $duracion_consulta;
        
        foreach ($disponibilidad as $slot) {
            $hora_inicio = strtotime($slot->hora_inicio);
            $hora_fin = strtotime($slot->hora_fin);
            
            // Iterar por los intervalos dentro del horario disponible
            for ($hora = $hora_inicio; $hora < $hora_fin; $hora += $intervalo_minutos * 60) {
                $hora_formateada = date('H:i', $hora);
                
                // Verificar si la hora ya está ocupada
                if (!in_array($hora_formateada, $horas_ocupadas)) {
                    $horas_disponibles[] = $hora_formateada;
                }
            }
        }
        
        wp_send_json_success(array('horas' => $horas_disponibles));
    }
    
    /**
     * Enviar mensaje
     */
    public function enviar_mensaje() {
        // Verificar nonce
        check_ajax_referer('sgep_ajax_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('sgep_send_messages')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'sgep'));
            return;
        }
        
        // Obtener datos
        $destinatario_id = isset($_POST['destinatario_id']) ? intval($_POST['destinatario_id']) : 0;
        $asunto = isset($_POST['asunto']) ? sanitize_text_field($_POST['asunto']) : '';
        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
        
        // Validaciones
        if ($destinatario_id <= 0) {
            wp_send_json_error(__('Destinatario inválido.', 'sgep'));
            return;
        }
        
        if (empty($asunto) || empty($mensaje)) {
            wp_send_json_error(__('El asunto y el mensaje son obligatorios.', 'sgep'));
            return;
        }
        
        // Verificar que el destinatario exista
        $destinatario = get_userdata($destinatario_id);
        if (!$destinatario) {
            wp_send_json_error(__('El destinatario no existe.', 'sgep'));
            return;
        }
        
        // Verificar que el destinatario sea cliente o especialista
        $roles = new SGEP_Roles();
        if (!$roles->is_cliente($destinatario_id) && !$roles->is_especialista($destinatario_id)) {
            wp_send_json_error(__('El destinatario no es un cliente o especialista.', 'sgep'));
            return;
        }
        
        // Verificar que tengamos relación con el destinatario (si soy especialista, solo puedo enviar a mis clientes)
        $remitente_id = get_current_user_id();
        if ($roles->is_especialista($remitente_id)) {
            // Verificar si el cliente ha tenido alguna cita con este especialista
            global $wpdb;
            $cita_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d AND cliente_id = %d",
                $remitente_id, $destinatario_id
            ));
            
            if ($cita_existe == 0) {
                wp_send_json_error(__('Solo puedes enviar mensajes a tus clientes.', 'sgep'));
                return;
            }
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
            return;
        }
        
        // Enviar notificación por email
        $this->enviar_notificacion_mensaje($wpdb->insert_id);
        
        wp_send_json_success(array(
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
            return;
        }
        
        // Verificar que el mensaje exista y sea para el usuario actual
        global $wpdb;
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sgep_mensajes WHERE id = %d AND destinatario_id = %d",
            $mensaje_id, get_current_user_id()
        ));
        
        if (!$mensaje) {
            wp_send_json_error(__('El mensaje no existe o no tienes permisos para marcarlo como leído.', 'sgep'));
            return;
        }
        
        // Marcar como leído
        $resultado = $wpdb->update(
            $wpdb->prefix . 'sgep_mensajes',
            array('leido' => 1),
            array('id' => $mensaje_id)
        );
        
        if ($resultado === false) {
            wp_send_json_error(__('Error al marcar el mensaje como leído.', 'sgep'));
            return;
        }
        
        wp_send_json_success();
    }
    
    /**
     * Obtener especialistas filtrados
     */
    public function obtener_especialistas() {
        // No verificamos nonce para permitir acceso público
        
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
                $habilidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
                if (!is_array($habilidades) || !in_array($especialidad, $habilidades)) {
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
            
            // Preparar datos del especialista
            $especialista_data = array(
                'id' => $especialista_id,
                'nombre' => $especialista->display_name,
                'email' => $especialista->user_email,
                'avatar' => get_avatar_url($especialista_id),
                'especialidad' => get_user_meta($especialista_id, 'sgep_especialidad', true),
                'descripcion' => get_user_meta($especialista_id, 'sgep_descripcion', true),
                'precio' => get_user_meta($especialista_id, 'sgep_precio_consulta', true),
                'online' => (bool) get_user_meta($especialista_id, 'sgep_acepta_online', true),
                'presencial' => (bool) get_user_meta($especialista_id, 'sgep_acepta_presencial', true),
                'rating' => get_user_meta($especialista_id, 'sgep_rating', true)
            );
            
            $especialistas_filtrados[] = $especialista_data;
        }
        
        wp_send_json_success(array(
            'especialistas' => $especialistas_filtrados
        ));
    }
    
    /**
     * Enviar notificaciones sobre citas
     */
    private function enviar_notificacion_cita($cita_id, $tipo) {
        // Verificar que esté habilitada la notificación por email
        $notificaciones_email = get_option('sgep_email_notifications', 1);
        if (!$notificaciones_email) {
            return;
        }
        
        // Obtener información de la cita
        global $wpdb;
        $cita = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, e.display_name as especialista_nombre, e.user_email as especialista_email,
            cl.display_name as cliente_nombre, cl.user_email as cliente_email
            FROM {$wpdb->prefix}sgep_citas c
            LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
            LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
            WHERE c.id = %d",
            $cita_id
        ));
        
        if (!$cita) {
            return;
        }
        
        // Preparar datos para el email
        $fecha = new DateTime($cita->fecha);
        $fecha_formateada = $fecha->format('d/m/Y H:i');
        
        switch ($tipo) {
            case 'nueva':
                // Notificación de nueva cita al especialista
                $asunto = sprintf(__('Nueva cita agendada - %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(__("Hola %s,\n\nTienes una nueva cita agendada:\n\nFecha: %s\nCliente: %s\n\nPor favor, ingresa a tu panel para confirmar o rechazar la cita.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                    $cita->especialista_nombre, 
                    $fecha_formateada, 
                    $cita->cliente_nombre
                );
                
                $destinatario = $cita->especialista_email;
                break;
                
            case 'confirmada':
                // Notificación de cita confirmada al cliente
                $asunto = sprintf(__('Cita confirmada - %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(__("Hola %s,\n\nTu cita ha sido confirmada:\n\nFecha: %s\nEspecialista: %s\n\n", 'sgep'),
                    $cita->cliente_nombre, 
                    $fecha_formateada, 
                    $cita->especialista_nombre
                );
                
                // Agregar información de Zoom si está disponible
                if (!empty($cita->zoom_link)) {
                    $mensaje .= sprintf(__("Enlace de Zoom: %s\n", 'sgep'), $cita->zoom_link);
                    
                    if (!empty($cita->zoom_id)) {
                        $mensaje .= sprintf(__("ID de Zoom: %s\n", 'sgep'), $cita->zoom_id);
                    }
                    
                    if (!empty($cita->zoom_password)) {
                        $mensaje .= sprintf(__("Contraseña de Zoom: %s\n", 'sgep'), $cita->zoom_password);
                    }
                }
                
                $mensaje .= __("\nSaludos,\nEl equipo de SGEP", 'sgep');
                
                $destinatario = $cita->cliente_email;
                break;
                
            case 'cancelada':
                // Determinar quién la canceló
                $roles = new SGEP_Roles();
                $usuario_actual = get_current_user_id();
                
                if ($roles->is_especialista($usuario_actual)) {
                    // Especialista canceló, notificar al cliente
                    $asunto = sprintf(__('Cita cancelada - %s', 'sgep'), $fecha_formateada);
                    $mensaje = sprintf(__("Hola %s,\n\nLamentamos informarte que tu cita ha sido cancelada:\n\nFecha: %s\nEspecialista: %s\n\nPor favor, ingresa a tu panel para agendar una nueva cita.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                        $cita->cliente_nombre, 
                        $fecha_formateada, 
                        $cita->especialista_nombre
                    );
                    
                    $destinatario = $cita->cliente_email;
                } else {
                    // Cliente canceló, notificar al especialista
                    $asunto = sprintf(__('Cita cancelada - %s', 'sgep'), $fecha_formateada);
                    $mensaje = sprintf(__("Hola %s,\n\nUna cita ha sido cancelada:\n\nFecha: %s\nCliente: %s\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                        $cita->especialista_nombre, 
                        $fecha_formateada, 
                        $cita->cliente_nombre
                    );
                    
                    $destinatario = $cita->especialista_email;
                }
                break;
                
            case 'rechazada':
                // Notificación de cita rechazada al cliente
                $asunto = sprintf(__('Cita rechazada - %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(__("Hola %s,\n\nLamentamos informarte que tu cita ha sido rechazada:\n\nFecha: %s\nEspecialista: %s\n\n", 'sgep'), 
                    $cita->cliente_nombre, 
                    $fecha_formateada, 
                    $cita->especialista_nombre
                );
                
                // Agregar motivo si está disponible
                if (!empty($cita->motivo_rechazo)) {
                    $mensaje .= sprintf(__("Motivo: %s\n\n", 'sgep'), $cita->motivo_rechazo);
                }
                
                $mensaje .= __("Por favor, ingresa a tu panel para agendar una nueva cita.\n\nSaludos,\nEl equipo de SGEP", 'sgep');
                
                $destinatario = $cita->cliente_email;
                break;
                
            case 'fecha_propuesta':
                // Notificación de nueva fecha propuesta al cliente
                $fecha_propuesta = new DateTime($cita->fecha_propuesta);
                $fecha_propuesta_formateada = $fecha_propuesta->format('d/m/Y H:i');
                
                $asunto = sprintf(__('Nueva fecha propuesta para tu cita - %s', 'sgep'), $fecha_propuesta_formateada);
                $mensaje = sprintf(__("Hola %s,\n\nTu especialista ha propuesto una nueva fecha para tu cita:\n\nFecha original: %s\nNueva fecha propuesta: %s\nEspecialista: %s\n\nPor favor, ingresa a tu panel para aceptar o rechazar esta propuesta.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                    $cita->cliente_nombre, 
                    $fecha_formateada,
                    $fecha_propuesta_formateada,
                    $cita->especialista_nombre
                );
                
                $destinatario = $cita->cliente_email;
                break;
                
            case 'nueva_fecha_aceptada':
                // Notificación al especialista que el cliente aceptó la nueva fecha
                $asunto = sprintf(__('Nueva fecha aceptada - %s', 'sgep'), date('d/m/Y H:i', strtotime($cita->fecha)));
                $mensaje = sprintf(__("Hola %s,\n\nEl cliente ha aceptado la nueva fecha propuesta para la cita:\n\nNueva fecha: %s\nCliente: %s\n\nLa cita está pendiente de confirmación. Por favor, ingresa a tu panel para confirmarla.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                    $cita->especialista_nombre, 
                    date('d/m/Y H:i', strtotime($cita->fecha)), 
                    $cita->cliente_nombre
                );
                
                $destinatario = $cita->especialista_email;
                break;
                
            case 'nueva_fecha_rechazada':
                // Notificación al especialista que el cliente rechazó la nueva fecha
                $asunto = sprintf(__('Nueva fecha rechazada - %s', 'sgep'), $fecha_formateada);
                $mensaje = sprintf(__("Hola %s,\n\nEl cliente ha rechazado la nueva fecha propuesta para la cita. Se mantiene la fecha original:\n\nFecha: %s\nCliente: %s\n\nLa cita está pendiente de confirmación. Por favor, ingresa a tu panel para confirmarla o proponer otra fecha.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
                    $cita->especialista_nombre, 
                    $fecha_formateada, 
                    $cita->cliente_nombre
                );
                
                $destinatario = $cita->especialista_email;
                break;
                
            default:
                return;
        }
        
        // Enviar email
        wp_mail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Enviar notificación de mensaje nuevo
     */
    private function enviar_notificacion_mensaje($mensaje_id) {
        // Verificar que esté habilitada la notificación por email
        $notificaciones_email = get_option('sgep_email_notifications', 1);
        if (!$notificaciones_email) {
            return;
        }
        
        // Obtener información del mensaje
        global $wpdb;
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, r.display_name as remitente_nombre, d.display_name as destinatario_nombre, d.user_email as destinatario_email
            FROM {$wpdb->prefix}sgep_mensajes m
            LEFT JOIN {$wpdb->users} r ON m.remitente_id = r.ID
            LEFT JOIN {$wpdb->users} d ON m.destinatario_id = d.ID
            WHERE m.id = %d",
            $mensaje_id
        ));
        
        if (!$mensaje) {
            return;
        }
        
        // Preparar datos para el email
        $asunto_email = sprintf(__('Nuevo mensaje de %s - %s', 'sgep'), $mensaje->remitente_nombre, $mensaje->asunto);
        $contenido_email = sprintf(__("Hola %s,\n\nHas recibido un nuevo mensaje de %s:\n\nAsunto: %s\n\nPara leer el mensaje completo, ingresa a tu panel.\n\nSaludos,\nEl equipo de SGEP", 'sgep'), 
            $mensaje->destinatario_nombre, 
            $mensaje->remitente_nombre, 
            $mensaje->asunto
        );
        
        // Enviar email
        wp_mail($mensaje->destinatario_email, $asunto_email, $contenido_email);
    }
}