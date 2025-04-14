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
        
        // Generar horas
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