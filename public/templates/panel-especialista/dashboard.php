<?php
/**
 * Plantilla para el dashboard del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/dashboard.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Obtener datos del especialista
$especialista_id = $user->ID;

// Obtener estadísticas
$citas_pendientes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas 
    WHERE especialista_id = %d AND estado = 'pendiente'",
    $especialista_id
));

$citas_confirmadas = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas 
    WHERE especialista_id = %d AND estado = 'confirmada'",
    $especialista_id
));

$citas_hoy = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas 
    WHERE especialista_id = %d AND DATE(fecha) = CURDATE() AND estado = 'confirmada'",
    $especialista_id
));

$mensajes_no_leidos = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_mensajes 
    WHERE destinatario_id = %d AND leido = 0",
    $especialista_id
));

// Próximas citas
$proximas_citas = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name as cliente_nombre
    FROM {$wpdb->prefix}sgep_citas c
    LEFT JOIN {$wpdb->users} u ON c.cliente_id = u.ID
    WHERE c.especialista_id = %d 
    AND c.fecha >= NOW() 
    AND c.estado = 'confirmada'
    ORDER BY c.fecha ASC
    LIMIT 5",
    $especialista_id
));
?>

<div class="sgep-dashboard">
    <div class="sgep-dashboard-header">
        <h3><?php _e('Dashboard', 'sgep'); ?></h3>
    </div>
    
    <div class="sgep-stats-grid">
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-citas-pendientes"></div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas pendientes', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_pendientes); ?></p>
                <?php if ($citas_pendientes > 0) : ?>
                    <a href="?tab=citas&filtro=pendiente" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-citas-confirmadas"></div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas confirmadas', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_confirmadas); ?></p>
                <?php if ($citas_confirmadas > 0) : ?>
                    <a href="?tab=citas&filtro=confirmada" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-citas-hoy"></div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas para hoy', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_hoy); ?></p>
                <?php if ($citas_hoy > 0) : ?>
                    <a href="?tab=citas&filtro=hoy" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-mensajes"></div>
            <div class="sgep-stat-content">
                <h4><?php _e('Mensajes no leídos', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($mensajes_no_leidos); ?></p>
                <?php if ($mensajes_no_leidos > 0) : ?>
                    <a href="?tab=mensajes" class="sgep-stat-link"><?php _e('Ver mensajes', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sgep-dashboard-section">
        <h3><?php _e('Próximas citas', 'sgep'); ?></h3>
        
        <?php if (!empty($proximas_citas)) : ?>
            <div class="sgep-table-responsive">
                <table class="sgep-table sgep-citas-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha y Hora', 'sgep'); ?></th>
                            <th><?php _e('Cliente', 'sgep'); ?></th>
                            <th><?php _e('Detalles', 'sgep'); ?></th>
                            <th><?php _e('Acciones', 'sgep'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximas_citas as $cita) : 
                            $fecha = new DateTime($cita->fecha);
                        ?>
                            <tr>
                                <td><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></td>
                                <td><?php echo esc_html($cita->cliente_nombre); ?></td>
                                <td>
                                    <?php if (!empty($cita->zoom_link)) : ?>
                                        <p><?php _e('Zoom', 'sgep'); ?>: <a href="<?php echo esc_url($cita->zoom_link); ?>" target="_blank"><?php _e('Enlace', 'sgep'); ?></a></p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?tab=citas&accion=ver&id=<?php echo $cita->id; ?>" class="sgep-button sgep-button-sm"><?php _e('Ver', 'sgep'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p class="sgep-no-items"><?php _e('No tienes citas programadas próximamente.', 'sgep'); ?></p>
        <?php endif; ?>
        
        <div class="sgep-dashboard-actions">
            <a href="?tab=citas" class="sgep-button sgep-button-secondary"><?php _e('Ver todas las citas', 'sgep'); ?></a>
        </div>
    </div>
    
    <div class="sgep-dashboard-section">
        <h3><?php _e('Acciones rápidas', 'sgep'); ?></h3>
        
        <div class="sgep-quick-actions">
            <a href="?tab=disponibilidad" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-disponibilidad"></div>
                <span><?php _e('Gestionar disponibilidad', 'sgep'); ?></span>
            </a>
            
            <a href="?tab=perfil" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-perfil"></div>
                <span><?php _e('Actualizar perfil', 'sgep'); ?></span>
            </a>
            
            <a href="?tab=mensajes&accion=nuevo" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-mensaje"></div>
                <span><?php _e('Enviar mensaje', 'sgep'); ?></span>
            </a>
        </div>
    </div>
</div>