<?php
/**
 * Plantilla para la pestaña de citas del panel de cliente
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
    $especialista_id = isset($_GET['agendar_con']) ? intval($_GET['agendar_con']) : 0;
}

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
                        <a href="#" class="sgep-button sgep-button-secondary sgep-cancelar-cita" data-id="<?php echo esc_attr($cita_id); ?>"><?php _e('Cancelar Cita', 'sgep'); ?></a>
                    </div>
                </form>
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
    
    <?php
} else {
    // Listado de citas
    
    // Consulta base
    $query = "SELECT c.*, cl.display_name as cliente_nombre 
              FROM {$wpdb->prefix}sgep_citas c
              LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
              WHERE c.especialista_id = %d";
    $query_args = array($especialista_id);
    
    // Aplicar filtro
    if ($filtro === 'pendiente') {
        $query .= " AND c.estado = 'pendiente'";
    } elseif ($filtro === 'confirmada') {
        $query .= " AND c.estado = 'confirmada'";
    } elseif ($filtro === 'cancelada') {
        $query .= " AND c.estado = 'cancelada'";
    } elseif ($filtro === 'hoy') {
        $query .= " AND DATE(c.fecha) = CURDATE() AND c.estado = 'confirmada'";
    } elseif ($filtro === 'proximas') {
        $query .= " AND c.fecha >= NOW() AND c.estado = 'confirmada'";
    } elseif ($filtro === 'pasadas') {
        $query .= " AND c.fecha < NOW() AND c.estado = 'confirmada'";
    }
    
    // Ordenar
    $query .= " ORDER BY c.fecha DESC";
    
    // Obtener citas
    $citas = $wpdb->get_results($wpdb->prepare($query, $query_args));
    ?>
    
    <div class="sgep-citas-wrapper">
        <h3><?php _e('Mis Citas', 'sgep'); ?></h3>
        
        <!-- Filtros -->
        <div class="sgep-citas-filter">
            <a href="?tab=citas" class="sgep-button <?php echo empty($filtro) ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Todas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=pendiente" class="sgep-button <?php echo $filtro === 'pendiente' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Pendientes', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=confirmada" class="sgep-button <?php echo $filtro === 'confirmada' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Confirmadas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=hoy" class="sgep-button <?php echo $filtro === 'hoy' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Hoy', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=proximas" class="sgep-button <?php echo $filtro === 'proximas' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Próximas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=pasadas" class="sgep-button <?php echo $filtro === 'pasadas' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Pasadas', 'sgep'); ?></a>
            <a href="?tab=citas&filtro=cancelada" class="sgep-button <?php echo $filtro === 'cancelada' ? 'sgep-button-primary' : 'sgep-button-outline'; ?>"><?php _e('Canceladas', 'sgep'); ?></a>
        </div>
        
        <!-- Listado de citas -->
        <div class="sgep-citas-list">
            <?php if (!empty($citas)) : ?>
                <?php foreach ($citas as $cita) : 
                    $fecha = new DateTime($cita->fecha);
                ?>
                    <div class="sgep-cita-item">
                        <div class="sgep-cita-fecha">
                            <div class="sgep-fecha-dia"><?php echo esc_html($fecha->format('d')); ?></div>
                            <div class="sgep-fecha-mes"><?php echo esc_html($fecha->format('M')); ?></div>
                        </div>
                        
                        <div class="sgep-cita-info">
                            <h4><?php echo esc_html($cita->cliente_nombre); ?></h4>
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
                                    default:
                                        echo esc_html($cita->estado);
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="sgep-cita-actions">
                            <a href="?tab=citas&accion=ver&id=<?php echo $cita->id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Ver', 'sgep'); ?></a>
                            
                            <?php if ($cita->estado === 'pendiente') : ?>
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