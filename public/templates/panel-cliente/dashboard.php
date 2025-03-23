<?php
/**
 * Plantilla para el dashboard del panel de cliente
 * 
 * Ruta: /public/templates/panel-cliente/dashboard.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Obtener datos del cliente
$cliente_id = get_current_user_id();

// Obtener estadísticas
$citas_pendientes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas 
    WHERE cliente_id = %d AND estado = 'pendiente'",
    $cliente_id
));

$citas_confirmadas = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas 
    WHERE cliente_id = %d AND estado = 'confirmada'",
    $cliente_id
));

$proxima_cita = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, e.display_name as especialista_nombre
    FROM {$wpdb->prefix}sgep_citas c
    LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
    WHERE c.cliente_id = %d 
    AND c.fecha >= NOW() 
    AND c.estado = 'confirmada'
    ORDER BY c.fecha ASC
    LIMIT 1",
    $cliente_id
));

$mensajes_no_leidos = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_mensajes 
    WHERE destinatario_id = %d AND leido = 0",
    $cliente_id
));

// Verificar si ya realizó el test
$test_realizado = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d LIMIT 1",
    $cliente_id
));

// Obtener especialistas recomendados si ha realizado el test
$especialistas_recomendados = array();

if ($test_realizado) {
    $especialistas_recomendados = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, e.display_name as especialista_nombre 
        FROM {$wpdb->prefix}sgep_matches m
        LEFT JOIN {$wpdb->users} e ON m.especialista_id = e.ID
        WHERE m.cliente_id = %d
        ORDER BY m.puntaje DESC
        LIMIT 3",
        $cliente_id
    ));
}

// Obtener páginas
$pages = get_option('sgep_pages', array());
$test_url = isset($pages['sgep-test-match']) ? get_permalink($pages['sgep-test-match']) : '#';
$resultados_url = isset($pages['sgep-resultados-match']) ? get_permalink($pages['sgep-resultados-match']) : '#';
?>

<div class="sgep-dashboard">
    <div class="sgep-dashboard-header">
        <h3><?php _e('Dashboard', 'sgep'); ?></h3>
    </div>
    
    <?php if (!$test_realizado) : ?>
        <div class="sgep-dashboard-alert">
            <div class="sgep-alert-content">
                <h4><?php _e('¡Realiza el test de compatibilidad!', 'sgep'); ?></h4>
                <p><?php _e('Para encontrar el especialista ideal para ti, te recomendamos realizar nuestro test de compatibilidad.', 'sgep'); ?></p>
                <a href="<?php echo esc_url($test_url); ?>" class="sgep-button sgep-button-primary"><?php _e('Realizar Test', 'sgep'); ?></a>
            </div>
        </div>
    <?php endif; ?>
    
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
    
    <?php if ($proxima_cita) : 
        $fecha = new DateTime($proxima_cita->fecha);
    ?>
        <div class="sgep-dashboard-section">
            <h3><?php _e('Próxima cita', 'sgep'); ?></h3>
            
            <div class="sgep-proxima-cita-card">
                <div class="sgep-proxima-cita-fecha">
                    <div class="sgep-fecha-dia"><?php echo esc_html($fecha->format('d')); ?></div>
                    <div class="sgep-fecha-mes"><?php echo esc_html($fecha->format('M')); ?></div>
                </div>
                
                <div class="sgep-proxima-cita-info">
                    <h4><?php echo esc_html($proxima_cita->especialista_nombre); ?></h4>
                    <p class="sgep-proxima-cita-hora"><?php echo esc_html($fecha->format('H:i')); ?> hrs</p>
                    
                    <?php if (!empty($proxima_cita->zoom_link)) : ?>
                        <p class="sgep-proxima-cita-zoom">
                            <?php _e('Zoom', 'sgep'); ?>: 
                            <a href="<?php echo esc_url($proxima_cita->zoom_link); ?>" target="_blank" class="sgep-zoom-link">
                                <?php _e('Entrar a la reunión', 'sgep'); ?>
                            </a>
                            
                            <?php if (!empty($proxima_cita->zoom_id) || !empty($proxima_cita->zoom_password)) : ?>
                                <span class="sgep-zoom-details">
                                    <?php if (!empty($proxima_cita->zoom_id)) : ?>
                                        <?php _e('ID', 'sgep'); ?>: <?php echo esc_html($proxima_cita->zoom_id); ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($proxima_cita->zoom_password)) : ?>
                                        <?php _e('Contraseña', 'sgep'); ?>: <?php echo esc_html($proxima_cita->zoom_password); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-proxima-cita-actions">
                    <a href="?tab=citas&accion=ver&id=<?php echo $proxima_cita->id; ?>" class="sgep-button sgep-button-secondary"><?php _e('Ver detalles', 'sgep'); ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($test_realizado && !empty($especialistas_recomendados)) : ?>
        <div class="sgep-dashboard-section">
            <h3><?php _e('Especialistas recomendados', 'sgep'); ?></h3>
            
            <div class="sgep-especialistas-recomendados">
                <?php foreach ($especialistas_recomendados as $match) : 
                    $especialista_id = $match->especialista_id;
                    $especialidad = get_user_meta($especialista_id, 'sgep_especialidad', true);
                    $compatibilidad = round(($match->puntaje / 100) * 100);
                    $compatibilidad = min(100, max(0, $compatibilidad));
                ?>
                    <div class="sgep-especialista-recomendado-card">
                        <div class="sgep-especialista-recomendado-avatar">
                            <?php echo get_avatar($especialista_id, 64); ?>
                            
                            <div class="sgep-compatibilidad-badge">
                                <span><?php echo $compatibilidad; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="sgep-especialista-recomendado-info">
                            <h4><?php echo esc_html($match->especialista_nombre); ?></h4>
                            
                            <?php if (!empty($especialidad)) : ?>
                                <p class="sgep-especialista-recomendado-especialidad"><?php echo esc_html($especialidad); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sgep-especialista-recomendado-actions">
                            <a href="?tab=especialistas&ver=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-sm sgep-button-secondary"><?php _e('Ver perfil', 'sgep'); ?></a>
                            <a href="?tab=citas&agendar_con=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Agendar', 'sgep'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="sgep-dashboard-actions">
                <a href="<?php echo esc_url($resultados_url); ?>" class="sgep-button sgep-button-outline"><?php _e('Ver todos los resultados', 'sgep'); ?></a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="sgep-dashboard-section">
        <h3><?php _e('Acciones rápidas', 'sgep'); ?></h3>
        
        <div class="sgep-quick-actions">
            <a href="?tab=citas&accion=agendar" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-cita"></div>
                <span><?php _e('Agendar cita', 'sgep'); ?></span>
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