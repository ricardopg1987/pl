<?php
/**
 * Plantilla para la pestaña de citas del panel de cliente
 * Modificada para gestionar propuestas de nuevas fechas
 * 
 * Ruta: /public/templates/panel-cliente/citas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener cliente actual
$cliente_id = get_current_user_id();

// Verificar acción
$accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
$cita_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar si hay un parámetro agendar_con desde los resultados del test
$agendar_con = isset($_GET['agendar_con']) ? intval($_GET['agendar_con']) : 0;
if ($agendar_con > 0) {
    $accion = 'agendar';
    $especialista_id = $agendar_con;
} else {
    $especialista_id = isset($_GET['especialista_id']) ? intval($_GET['especialista_id']) : 0;
}

// Filtro
$filtro = isset($_GET['filtro']) ? sanitize_text_field($_GET['filtro']) : '';

// Si es para ver una cita específica
if ($accion === 'ver' && $cita_id > 0) {
    // Obtener la cita
    global $wpdb;
    $cita = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, e.display_name as especialista_nombre, e.user_email as especialista_email
        FROM {$wpdb->prefix}sgep_citas c
        LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
        WHERE c.id = %d AND c.cliente_id = %d",
        $cita_id, $cliente_id
    ));
    
    if (!$cita) {
        echo '<p class="sgep-error">' . __('La cita no existe o no tienes permisos para verla.', 'sgep') . '</p>';
        return;
    }
    
    // Obtener datos del especialista
    $especialista_especialidad = get_user_meta($cita->especialista_id, 'sgep_especialidad', true);
    
    // Fecha y hora de la cita
    $fecha_cita = new DateTime($cita->fecha);
    $fecha_creacion = new DateTime($cita->created_at);
    
    // Comprobar si hay una fecha propuesta
    $tiene_fecha_propuesta = !empty($cita->fecha_propuesta) && $cita->estado === 'fecha_propuesta';
    if ($tiene_fecha_propuesta) {
        $fecha_propuesta = new DateTime($cita->fecha_propuesta);
    }
    ?>
    
    <div class="sgep-cita-detail">
        <div class="sgep-cita-header">
            <h3><?php _e('Detalles de la Cita', 'sgep'); ?></h3>
            
            <div class="sgep-cita-estado">
                <?php
                switch ($cita->estado) {
                    case 'pendiente':
                        echo '<span class="sgep-estado-pendiente">' . __('Pendiente', 'sgep') . '</span>';
                        break;
                    case 'confirmada':
                        echo '<span class="sgep-estado-confirmada">' . __('Confirmada', 'sgep') . '</span>';
                        break;
                    case 'cancelada':
                        echo '<span class="sgep-estado-cancelada">' . __('Cancelada', 'sgep') . '</span>';
                        break;
                    case 'rechazada':
                        echo '<span class="sgep-estado-rechazada">' . __('Rechazada', 'sgep') . '</span>';
                        break;
                    case 'fecha_propuesta':
                        echo '<span class="sgep-estado-propuesta">' . __('Fecha propuesta', 'sgep') . '</span>';
                        break;
                    default:
                        echo esc_html($cita->estado);
                }
                ?>
            </div>
        </div>
        
        <div class="sgep-cita-section">
            <h4><?php _e('Información General', 'sgep'); ?></h4>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Especialista:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($cita->especialista_nombre); ?></span>
            </div>
            
            <?php if (!empty($especialista_especialidad)) : ?>
                <div class="sgep-cita-row">
                    <span class="sgep-cita-label"><?php _e('Especialidad:', 'sgep'); ?></span>
                    <span class="sgep-cita-value"><?php echo esc_html($especialista_especialidad); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Email:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($cita->especialista_email); ?></span>
            </div>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Fecha y Hora:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($fecha_cita->format('d/m/Y H:i')); ?></span>
            </div>
            
            <?php if ($tiene_fecha_propuesta) : ?>
                <div class="sgep-cita-row sgep-cita-row-highlight">
                    <span class="sgep-cita-label"><?php _e('Nueva Fecha Propuesta:', 'sgep'); ?></span>
                    <span class="sgep-cita-value"><?php echo esc_html($fecha_propuesta->format('d/m/Y H:i')); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Duración:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($cita->duracion) . ' ' . __('minutos', 'sgep'); ?></span>
            </div>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Creada el:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($fecha_creacion->format('d/m/Y H:i')); ?></span>
            </div>
        </div>
        
        <?php if (!empty($cita->notas)) : ?>
            <div class="sgep-cita-section">
                <h4><?php _e('Notas', 'sgep'); ?></h4>
                <div class="sgep-cita-notas">
                    <?php echo wpautop(esc_html($cita->notas)); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cita->motivo_rechazo)) : ?>
            <div class="sgep-cita-section sgep-cita-section-rechazo">
                <h4><?php _e('Motivo del Rechazo', 'sgep'); ?></h4>
                <div class="sgep-cita-motivo-rechazo">
                    <?php echo wpautop(esc_html($cita->motivo_rechazo)); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($cita->estado === 'confirmada' && !empty($cita->zoom_link)) : ?>
            <div class="sgep-cita-section">
                <h4><?php _e('Información de Zoom', 'sgep'); ?></h4>
                
                <div class="sgep-cita-row">
                    <span class="sgep-cita-label"><?php _e('Enlace:', 'sgep'); ?></span>
                    <span class="sgep-cita-value">
                        <a href="<?php echo esc_url($cita->zoom_link); ?>" target="_blank" class="sgep-zoom-link"><?php _e('Entrar a la reunión', 'sgep'); ?></a>
                    </span>
                </div>
                
                <?php if (!empty($cita->zoom_id)) : ?>
                    <div class="sgep-cita-row">
                        <span class="sgep-cita-label"><?php _e('ID de Reunión:', 'sgep'); ?></span>
                        <span class="sgep-cita-value"><?php echo esc_html($cita->zoom_id); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($cita->zoom_password)) : ?>
                    <div class="sgep-cita-row">
                        <span class="sgep-cita-label"><?php _e('Contraseña:', 'sgep'); ?></span>
                        <span class="sgep-cita-value"><?php echo esc_html($cita->zoom_password); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($tiene_fecha_propuesta) : ?>
            <div class="sgep-cita-nueva-fecha">
                <h4><?php _e('Nueva Fecha Propuesta', 'sgep'); ?></h4>
                <p><?php _e('El especialista ha propuesto una nueva fecha para tu cita. Por favor, indica si aceptas esta propuesta:', 'sgep'); ?></p>
                
                <div class="sgep-cita-actions">
                    <button type="button" class="sgep-button sgep-button-primary sgep-aceptar-nueva-fecha" data-id="<?php echo esc_attr($cita_id); ?>" data-aceptar="1"><?php _e('Aceptar Nueva Fecha', 'sgep'); ?></button>
                    <button type="button" class="sgep-button sgep-button-secondary sgep-aceptar-nueva-fecha" data-id="<?php echo esc_attr($cita_id); ?>" data-aceptar="0"><?php _e('Rechazar Nueva Fecha', 'sgep'); ?></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($cita->estado !== 'cancelada' && $cita->estado !== 'rechazada') : ?>
            <div class="sgep-cita-actions">
                <a href="#" class="sgep-button sgep-button-secondary sgep-cancelar-cita" data-id="<?php echo esc_attr($cita_id); ?>"><?php _e('Cancelar Cita', 'sgep'); ?></a>
            </div>
        <?php endif; ?>
        
        <div class="sgep-cita-footer">
            <a href="?tab=citas" class="sgep-button sgep-button-text"><?php _e('Volver a Citas', 'sgep'); ?></a>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Aceptar o rechazar nueva fecha propuesta
        $('.sgep-aceptar-nueva-fecha').on('click', function() {
            var btn = $(this);
            var citaId = btn.data('id');
            var aceptar = btn.data('aceptar');
            var confirmMsg = aceptar ? 
                '¿Estás seguro de aceptar la nueva fecha propuesta?' : 
                '¿Estás seguro de rechazar la nueva fecha propuesta? Se mantendrá la fecha original.';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            $('.sgep-aceptar-nueva-fecha').prop('disabled', true);
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_aceptar_nueva_fecha',
                    nonce: sgep_ajax.nonce,
                    cita_id: citaId,
                    aceptar: aceptar
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data);
                        $('.sgep-aceptar-nueva-fecha').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error al procesar la respuesta. Por favor, intenta nuevamente.');
                    $('.sgep-aceptar-nueva-fecha').prop('disabled', false);
                }
            });
        });
    });
    </script>
    
    <?php
} elseif ($accion === 'agendar' || $especialista_id > 0) {
    // Formulario para agendar cita
    
    // Si no se especificó especialista, mostrar selector
    if ($especialista_id <= 0) {
        // Obtener todos los especialistas
        $roles = new SGEP_Roles();
        $especialistas = $roles->get_all_especialistas();
    } else {
        // Verificar que el especialista exista
        $especialista = get_userdata($especialista_id);
        
        if (!$especialista || !in_array('sgep_especialista', $especialista->roles)) {
            echo '<p class="sgep-error">' . __('Especialista no encontrado.', 'sgep') . '</p>';
            return;
        }
    }
    ?>
    
    <div class="sgep-agendar-cita">
        <h3><?php _e('Agendar Cita', 'sgep'); ?></h3>
        
        <form id="sgep_agendar_cita_form" class="sgep-form">
            <div class="sgep-form-field">
                <label for="sgep_especialista_id"><?php _e('Especialista', 'sgep'); ?></label>
                
                <?php if ($especialista_id > 0) : ?>
                    <input type="hidden" id="sgep_especialista_id" name="sgep_especialista_id" value="<?php echo esc_attr($especialista_id); ?>">
                    <p><?php echo esc_html($especialista->display_name); ?></p>
                <?php else : ?>
                    <select id="sgep_especialista_id" name="sgep_especialista_id" required>
                        <option value=""><?php _e('-- Seleccionar especialista --', 'sgep'); ?></option>
                        <?php foreach ($especialistas as $esp) : ?>
                            <option value="<?php echo esc_attr($esp->ID); ?>"><?php echo esc_html($esp->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_fecha"><?php _e('Fecha', 'sgep'); ?></label>
                        <input type="date" id="sgep_fecha" name="sgep_fecha" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_hora"><?php _e('Hora', 'sgep'); ?></label>
                        <select id="sgep_hora" name="sgep_hora" required disabled>
                            <option value=""><?php _e('-- Selecciona primero una fecha --', 'sgep'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_notas"><?php _e('Notas (opcional)', 'sgep'); ?></label>
                <textarea id="sgep_notas" name="sgep_notas" rows="3"></textarea>
                <p class="sgep-field-description"><?php _e('Puedes incluir información adicional que consideres relevante para el especialista.', 'sgep'); ?></p>
            </div>
            
            <div class="sgep-form-actions">
                <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Agendar Cita', 'sgep'); ?></button>
                <a href="?tab=citas" class="sgep-button sgep-button-secondary"><?php _e('Cancelar', 'sgep'); ?></a>
            </div>
        </form>
    </div>
    
    <?php
} else {
    // Listado de citas
    global $wpdb;
    
    // Consulta base
    $query = "SELECT c.*, e.display_name as especialista_nombre 
              FROM {$wpdb->prefix}sgep_citas c
              LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
              WHERE c.cliente_id = %d";
    $query_args = array($cliente_id);
    
    // Aplicar filtro
    if ($filtro === 'pendiente') {
        $query .= " AND (c.estado = 'pendiente' OR c.estado = 'fecha_propuesta')";
    } elseif ($filtro === 'confirmada') {
        $query .= " AND c.estado = 'confirmada'";
    } elseif ($filtro === 'cancelada') {
        $query .= " AND c.estado = 'cancelada'";
    } elseif ($filtro === 'rechazada') {
        $query .= " AND c.estado = 'rechazada'";
    } elseif ($filtro === 'proximas') {
        $query .= " AND c.fecha >= NOW() AND c.estado != 'cancelada' AND c.estado != 'rechazada'";
    } elseif ($filtro === 'pasadas') {
        $query .= " AND c.fecha < NOW() AND c.estado = 'confirmada'";
    }
    
    // Ordenar
    $query .= " ORDER BY c.fecha DESC";
    
    // Obtener citas
    $citas = $wpdb->get_results($wpdb->prepare($query, $query_args));
    ?>
    
    <div class="sgep-citas-wrapper">
        <div class="sgep-citas-header">
            <h3><?php _e('Mis Citas', 'sgep'); ?></h3>
            <a href="?tab=citas&accion=agendar" class="sgep-button sgep-button-primary"><?php _e('Agendar Nueva Cita', 'sgep'); ?></a>
        </div>
        
        <!-- Filtros -->
        <div class="sgep-citas-filter">
            <a href="?tab=citas" class="sgep-button <?php echo empty($filtro) ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Todas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=pendiente" class="sgep-button <?php echo $filtro === 'pendiente' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Pendientes', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=confirmada" class="sgep-button <?php echo $filtro === 'confirmada' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Confirmadas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=proximas" class="sgep-button <?php echo $filtro === 'proximas' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Próximas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=pasadas" class="sgep-button <?php echo $filtro === 'pasadas' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Pasadas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=cancelada" class="sgep-button <?php echo $filtro === 'cancelada' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Canceladas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=rechazada" class="sgep-button <?php echo $filtro === 'rechazada' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Rechazadas', 'sgep'); ?></a>
        </div>
        
        <!-- Listado de citas -->
        <div class="sgep-citas-list">
            <?php if (!empty($citas)) : ?>
                <?php foreach ($citas as $cita) : 
                    $fecha = new DateTime($cita->fecha);
                    $tiene_fecha_propuesta = !empty($cita->fecha_propuesta) && $cita->estado === 'fecha_propuesta';
                    
                    if ($tiene_fecha_propuesta) {
                        $fecha_propuesta = new DateTime($cita->fecha_propuesta);
                    }
                ?>
                    <div class="sgep-cita-item">
                        <div class="sgep-cita-fecha">
                            <div class="sgep-fecha-dia"><?php echo esc_html($fecha->format('d')); ?></div>
                            <div class="sgep-fecha-mes"><?php echo esc_html($fecha->format('M')); ?></div>
                        </div>
                        
                        <div class="sgep-cita-info">
                            <h4><?php echo esc_html($cita->especialista_nombre); ?></h4>
                            <span class="sgep-cita-hora"><?php echo esc_html($fecha->format('H:i')); ?> hrs</span>
                            <span class="sgep-cita-estado sgep-estado-<?php echo esc_attr($cita->estado); ?>">
                                <?php
                                switch ($cita->estado) {
                                    case 'pendiente':
                                        _e('Pendiente', 'sgep');
                                        break;
                                    case 'confirmada':
                                        _e('Confirmada', 'sgep');
                                        break;
                                    case 'cancelada':
                                        _e('Cancelada', 'sgep');
                                        break;
                                    case 'rechazada':
                                        _e('Rechazada', 'sgep');
                                        break;
                                    case 'fecha_propuesta':
                                        _e('Fecha propuesta', 'sgep');
                                        break;
                                    default:
                                        echo esc_html($cita->estado);
                                }
                                ?>
                            </span>
                            
                            <?php if ($tiene_fecha_propuesta) : ?>
                                <span class="sgep-cita-nueva-fecha-badge">
                                    <?php printf(__('Nueva fecha: %s', 'sgep'), esc_html($fecha_propuesta->format('d/m/Y H:i'))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sgep-cita-actions">
                            <a href="?tab=citas&accion=ver&id=<?php echo $cita->id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Ver', 'sgep'); ?></a>
                            
                            <?php if ($cita->estado !== 'cancelada' && $cita->estado !== 'rechazada') : ?>
                                <a href="#" class="sgep-button sgep-button-sm sgep-button-outline sgep-cancelar-cita" data-id="<?php echo $cita->id; ?>"><?php _e('Cancelar', 'sgep'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="sgep-no-items"><?php _e('No se encontraron citas.', 'sgep'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}