<?php
/**
 * Plantilla para la pestaña de citas del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/citas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global al inicio del archivo
global $wpdb;

// Obtener especialista actual
$especialista_id = get_current_user_id();

// Verificar acción
$accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
$cita_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Filtro
$filtro = isset($_GET['filtro']) ? sanitize_text_field($_GET['filtro']) : '';

// Si es para ver una cita específica
if ($accion === 'ver' && $cita_id > 0) {
    // Obtener la cita
    $cita = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, cl.display_name as cliente_nombre, cl.user_email as cliente_email
        FROM {$wpdb->prefix}sgep_citas c
        LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
        WHERE c.id = %d AND c.especialista_id = %d",
        $cita_id, $especialista_id
    ));
    
    if (!$cita) {
        echo '<p class="sgep-error">' . __('La cita no existe o no tienes permisos para verla.', 'sgep') . '</p>';
        return;
    }
    
    // Obtener datos del cliente
    $cliente_telefono = get_user_meta($cita->cliente_id, 'sgep_telefono', true);
    
    // Fecha y hora de la cita
    $fecha_cita = new DateTime($cita->fecha);
    $fecha_creacion = new DateTime($cita->created_at);
    
    // Comprobar si hay una fecha propuesta
    $tiene_fecha_propuesta = !empty($cita->fecha_propuesta);
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
                <span class="sgep-cita-label"><?php _e('Cliente:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($cita->cliente_nombre); ?></span>
            </div>
            
            <div class="sgep-cita-row">
                <span class="sgep-cita-label"><?php _e('Email:', 'sgep'); ?></span>
                <span class="sgep-cita-value"><?php echo esc_html($cita->cliente_email); ?></span>
            </div>
            
            <?php if (!empty($cliente_telefono)) : ?>
                <div class="sgep-cita-row">
                    <span class="sgep-cita-label"><?php _e('Teléfono:', 'sgep'); ?></span>
                    <span class="sgep-cita-value"><?php echo esc_html($cliente_telefono); ?></span>
                </div>
            <?php endif; ?>
            
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
            <div class="sgep-cita-section">
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
                        <a href="<?php echo esc_url($cita->zoom_link); ?>" target="_blank"><?php echo esc_url($cita->zoom_link); ?></a>
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
        
        <div class="sgep-cita-actions">
            <?php if ($cita->estado === 'pendiente') : ?>
                <h4><?php _e('Acciones', 'sgep'); ?></h4>
                
                <div class="sgep-cita-acciones-tabs">
                    <ul class="sgep-cita-acciones-nav">
                        <li class="active"><a href="#sgep-tab-confirmar"><?php _e('Confirmar', 'sgep'); ?></a></li>
                        <li><a href="#sgep-tab-proponer"><?php _e('Proponer nueva fecha', 'sgep'); ?></a></li>
                        <li><a href="#sgep-tab-rechazar"><?php _e('Rechazar', 'sgep'); ?></a></li>
                    </ul>
                    
                    <div class="sgep-cita-acciones-contenido">
                        <div id="sgep-tab-confirmar" class="sgep-cita-accion-panel active">
                            <form id="sgep_confirmar_cita_form" class="sgep-form">
                                <input type="hidden" id="sgep_cita_id" value="<?php echo esc_attr($cita_id); ?>">
                                
                                <div class="sgep-form-field">
                                    <label for="sgep_zoom_link"><?php _e('Enlace de Zoom (opcional)', 'sgep'); ?></label>
                                    <input type="url" id="sgep_zoom_link" name="sgep_zoom_link" placeholder="https://zoom.us/j/...">
                                </div>
                                
                                <div class="sgep-form-row">
                                    <div class="sgep-form-col">
                                        <div class="sgep-form-field">
                                            <label for="sgep_zoom_id"><?php _e('ID de Reunión (opcional)', 'sgep'); ?></label>
                                            <input type="text" id="sgep_zoom_id" name="sgep_zoom_id">
                                        </div>
                                    </div>
                                    
                                    <div class="sgep-form-col">
                                        <div class="sgep-form-field">
                                            <label for="sgep_zoom_password"><?php _e('Contraseña (opcional)', 'sgep'); ?></label>
                                            <input type="text" id="sgep_zoom_password" name="sgep_zoom_password">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sgep-form-actions">
                                    <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Confirmar Cita', 'sgep'); ?></button>
                                </div>
                            </form>
                        </div>
                        
                        <div id="sgep-tab-proponer" class="sgep-cita-accion-panel">
                            <form id="sgep_proponer_fecha_form" class="sgep-form">
                                <input type="hidden" name="cita_id" value="<?php echo esc_attr($cita_id); ?>">
                                
                                <div class="sgep-form-row">
                                    <div class="sgep-form-col">
                                        <div class="sgep-form-field">
                                            <label for="sgep_nueva_fecha"><?php _e('Nueva Fecha', 'sgep'); ?></label>
                                            <input type="date" id="sgep_nueva_fecha" name="nueva_fecha" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="sgep-form-col">
                                        <div class="sgep-form-field">
                                            <label for="sgep_nueva_hora"><?php _e('Nueva Hora', 'sgep'); ?></label>
                                            <input type="time" id="sgep_nueva_hora" name="nueva_hora" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sgep-form-actions">
                                    <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Proponer Nueva Fecha', 'sgep'); ?></button>
                                </div>
                            </form>
                        </div>
                        
                        <div id="sgep-tab-rechazar" class="sgep-cita-accion-panel">
                            <form id="sgep_rechazar_cita_form" class="sgep-form">
                                <input type="hidden" name="cita_id" value="<?php echo esc_attr($cita_id); ?>">
                                
                                <div class="sgep-form-field">
                                    <label for="sgep_motivo_rechazo"><?php _e('Motivo del rechazo', 'sgep'); ?></label>
                                    <textarea id="sgep_motivo_rechazo" name="motivo" rows="4" required></textarea>
                                    <p class="sgep-field-description"><?php _e('Proporciona una razón para el rechazo de la cita. Esta información será enviada al cliente.', 'sgep'); ?></p>
                                </div>
                                
                                <div class="sgep-form-actions">
                                    <button type="submit" class="sgep-button sgep-button-danger"><?php _e('Rechazar Cita', 'sgep'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($cita->estado === 'fecha_propuesta') : ?>
                <div class="sgep-cita-propuesta-info">
                    <p><?php _e('Has propuesto una nueva fecha para esta cita. Esperando respuesta del cliente.', 'sgep'); ?></p>
                </div>
                
                <div class="sgep-form-actions">
                    <a href="#" class="sgep-button sgep-button-secondary sgep-cancelar-cita" data-id="<?php echo esc_attr($cita_id); ?>"><?php _e('Cancelar Cita', 'sgep'); ?></a>
                </div>
                
            <?php elseif ($cita->estado === 'confirmada') : ?>
                <div class="sgep-form-actions">
                    <a href="#" class="sgep-button sgep-button-secondary sgep-cancelar-cita" data-id="<?php echo esc_attr($cita_id); ?>"><?php _e('Cancelar Cita', 'sgep'); ?></a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sgep-cita-footer">
            <a href="?tab=citas" class="sgep-button sgep-button-text"><?php _e('Volver a Citas', 'sgep'); ?></a>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Tabs para acciones de cita
        $('.sgep-cita-acciones-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Cambiar tab activa
            $('.sgep-cita-acciones-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Mostrar panel correspondiente
            $('.sgep-cita-accion-panel').removeClass('active');
            $(target).addClass('active');
        });
        
        // Formulario para rechazar cita
        $('#sgep_rechazar_cita_form').on('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de rechazar esta cita?')) {
                return;
            }
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var citaId = form.find('input[name="cita_id"]').val();
            var motivo = form.find('#sgep_motivo_rechazo').val();
            
            btn.prop('disabled', true).text('Procesando...');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_rechazar_cita',
                    nonce: sgep_ajax.nonce,
                    cita_id: citaId,
                    motivo: motivo
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.href = '?tab=citas';
                    } else {
                        alert(response.data);
                        btn.prop('disabled', false).text('Rechazar Cita');
                    }
                },
                error: function() {
                    alert('Error al rechazar la cita. Por favor, intenta nuevamente.');
                    btn.prop('disabled', false).text('Rechazar Cita');
                }
            });
        });
        
        // Formulario para proponer nueva fecha
        $('#sgep_proponer_fecha_form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var citaId = form.find('input[name="cita_id"]').val();
            var nuevaFecha = form.find('#sgep_nueva_fecha').val();
            var nuevaHora = form.find('#sgep_nueva_hora').val();
            
            if (!nuevaFecha || !nuevaHora) {
                alert('Por favor, especifica la nueva fecha y hora.');
                return;
            }
            
            btn.prop('disabled', true).text('Procesando...');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_proponer_nueva_fecha',
                    nonce: sgep_ajax.nonce,
                    cita_id: citaId,
                    nueva_fecha: nuevaFecha,
                    nueva_hora: nuevaHora
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.href = '?tab=citas';
                    } else {
                        alert(response.data);
                        btn.prop('disabled', false).text('Proponer Nueva Fecha');
                    }
                },
                error: function() {
                    alert('Error al proponer la nueva fecha. Por favor, intenta nuevamente.');
                    btn.prop('disabled', false).text('Proponer Nueva Fecha');
                }
            });
        });
    });

    