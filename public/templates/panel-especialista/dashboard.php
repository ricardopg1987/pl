<?php
/**
 * Plantilla para el dashboard del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/dashboard.php
 * Versión mejorada con mejor visualización de citas y estadísticas
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

// Citas de hoy
$citas_de_hoy = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name as cliente_nombre, u.user_email as cliente_email
    FROM {$wpdb->prefix}sgep_citas c
    LEFT JOIN {$wpdb->users} u ON c.cliente_id = u.ID
    WHERE c.especialista_id = %d 
    AND DATE(c.fecha) = CURDATE() 
    AND c.estado = 'confirmada'
    ORDER BY c.fecha ASC",
    $especialista_id
));

// Próximas citas (excluyendo las de hoy)
$proximas_citas = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name as cliente_nombre
    FROM {$wpdb->prefix}sgep_citas c
    LEFT JOIN {$wpdb->users} u ON c.cliente_id = u.ID
    WHERE c.especialista_id = %d 
    AND c.fecha > DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    AND c.estado = 'confirmada'
    ORDER BY c.fecha ASC
    LIMIT 5",
    $especialista_id
));

// Total de clientes atendidos
$total_clientes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT cliente_id) FROM {$wpdb->prefix}sgep_citas 
    WHERE especialista_id = %d AND estado = 'confirmada'",
    $especialista_id
));
?>

<div class="sgep-dashboard">
    <div class="sgep-dashboard-header">
        <h3><?php _e('Dashboard', 'sgep'); ?></h3>
    </div>
    
    <?php if ($citas_hoy > 0) : ?>
    <div class="sgep-dashboard-alert sgep-dashboard-today">
        <h4><?php _e('Tienes citas programadas para hoy', 'sgep'); ?></h4>
        <p><?php echo sprintf(_n('Tienes %d cita para hoy.', 'Tienes %d citas para hoy.', $citas_hoy, 'sgep'), $citas_hoy); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="sgep-stats-grid">
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-citas-pendientes">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas pendientes', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_pendientes); ?></p>
                <?php if ($citas_pendientes > 0) : ?>
                    <a href="?tab=citas&filtro=pendiente" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-citas-confirmadas">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas confirmadas', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_confirmadas); ?></p>
                <?php if ($citas_confirmadas > 0) : ?>
                    <a href="?tab=citas&filtro=confirmada" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card <?php echo ($citas_hoy > 0) ? 'sgep-stat-highlight' : ''; ?>">
            <div class="sgep-stat-icon sgep-icon-citas-hoy">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="sgep-stat-content">
                <h4><?php _e('Citas para hoy', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($citas_hoy); ?></p>
                <?php if ($citas_hoy > 0) : ?>
                    <a href="?tab=citas&filtro=hoy" class="sgep-stat-link"><?php _e('Ver citas', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-mensajes">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div class="sgep-stat-content">
                <h4><?php _e('Mensajes no leídos', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($mensajes_no_leidos); ?></p>
                <?php if ($mensajes_no_leidos > 0) : ?>
                    <a href="?tab=mensajes" class="sgep-stat-link"><?php _e('Ver mensajes', 'sgep'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sgep-stat-card">
            <div class="sgep-stat-icon sgep-icon-clientes">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="sgep-stat-content">
                <h4><?php _e('Clientes atendidos', 'sgep'); ?></h4>
                <p class="sgep-stat-value"><?php echo esc_html($total_clientes); ?></p>
            </div>
        </div>
    </div>
    
    <?php if (!empty($citas_de_hoy)) : ?>
    <div class="sgep-dashboard-section">
        <h3><?php _e('Citas de hoy', 'sgep'); ?></h3>
        
        <div class="sgep-citas-hoy-grid">
            <?php foreach ($citas_de_hoy as $cita) : 
                $fecha = new DateTime($cita->fecha);
                $hora_actual = new DateTime();
                $minutos_faltantes = ($fecha->getTimestamp() - $hora_actual->getTimestamp()) / 60;
                $es_proxima = $minutos_faltantes > 0 && $minutos_faltantes < 60;
            ?>
            <div class="sgep-cita-hoy-card <?php echo $es_proxima ? 'sgep-cita-proxima' : ''; ?>">
                <div class="sgep-cita-hoy-time">
                    <div class="sgep-cita-hoy-hora"><?php echo esc_html($fecha->format('H:i')); ?></div>
                    <?php if ($es_proxima) : ?>
                    <div class="sgep-cita-countdown"><?php echo sprintf(__('En %d minutos', 'sgep'), round($minutos_faltantes)); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-cita-hoy-info">
                    <h4><?php echo esc_html($cita->cliente_nombre); ?></h4>
                    <p class="sgep-cita-hoy-email"><?php echo esc_html($cita->cliente_email); ?></p>
                    
                    <?php if (!empty($cita->zoom_link)) : ?>
                    <div class="sgep-cita-hoy-zoom">
                        <a href="<?php echo esc_url($cita->zoom_link); ?>" target="_blank" class="sgep-zoom-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.6 11.6L22 7v10l-6.4-4.5v-1z"></path><rect width="15" height="10" x="1" y="7" rx="2" ry="2"></rect></svg>
                            <?php _e('Iniciar Zoom', 'sgep'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-cita-hoy-actions">
                    <a href="?tab=citas&accion=ver&id=<?php echo $cita->id; ?>" class="sgep-button sgep-button-sm sgep-button-outline">
                        <?php _e('Ver detalles', 'sgep'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="sgep-dashboard-section">
        <h3><?php _e('Próximas citas', 'sgep'); ?></h3>
        
        <?php if (!empty($proximas_citas)) : ?>
            <div class="sgep-proximas-citas-table">
                <table class="sgep-table sgep-citas-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'sgep'); ?></th>
                            <th><?php _e('Hora', 'sgep'); ?></th>
                            <th><?php _e('Cliente', 'sgep'); ?></th>
                            <th><?php _e('Estado', 'sgep'); ?></th>
                            <th><?php _e('Acciones', 'sgep'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximas_citas as $cita) : 
                            $fecha = new DateTime($cita->fecha);
                        ?>
                            <tr>
                                <td><?php echo esc_html($fecha->format('d/m/Y')); ?></td>
                                <td><?php echo esc_html($fecha->format('H:i')); ?></td>
                                <td><?php echo esc_html($cita->cliente_nombre); ?></td>
                                <td>
                                    <span class="sgep-estado-<?php echo esc_attr($cita->estado); ?>">
                                        <?php
                                        switch ($cita->estado) {
                                            case 'pendiente':
                                                _e('Pendiente', 'sgep');
                                                break;
                                            case 'confirmada':
                                                _e('Confirmada', 'sgep');
                                                break;
                                            default:
                                                echo esc_html($cita->estado);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?tab=citas&accion=ver&id=<?php echo $cita->id; ?>" class="sgep-button sgep-button-sm">
                                        <?php _e('Ver', 'sgep'); ?>
                                    </a>
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
                <div class="sgep-quick-action-icon sgep-icon-disponibilidad">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <span><?php _e('Gestionar disponibilidad', 'sgep'); ?></span>
            </a>
            
            <a href="?tab=perfil" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-perfil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span><?php _e('Actualizar perfil', 'sgep'); ?></span>
            </a>
            
            <a href="?tab=mensajes&accion=nuevo" class="sgep-quick-action">
                <div class="sgep-quick-action-icon sgep-icon-mensaje">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                </div>
                <span><?php _e('Enviar mensaje', 'sgep'); ?></span>
            </a>
        </div>
    </div>
</div>

<!-- Estilos adicionales para el dashboard -->
<style>
.sgep-dashboard-today {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    margin-bottom: 20px;
}

.sgep-stat-highlight {
    border-left: 4px solid #4caf50;
    background: linear-gradient(to right, rgba(76, 175, 80, 0.1), transparent);
}

.sgep-citas-hoy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.sgep-cita-hoy-card {
    display: flex;
    background-color: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 3px solid #2196f3;
}

.sgep-cita-proxima {
    border-left: 3px solid #ff9800;
    background-color: #fff9c4;
}

.sgep-cita-hoy-time {
    width: 70px;
    text-align: center;
    margin-right: 15px;
}

.sgep-cita-hoy-hora {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.sgep-cita-countdown {
    font-size: 12px;
    color: #ff5722;
    font-weight: 500;
    margin-top: 5px;
}

.sgep-cita-hoy-info {
    flex: 1;
}

.sgep-cita-hoy-info h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.sgep-cita-hoy-email {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 13px;
}

.sgep-cita-hoy-zoom {
    margin-top: 10px;
}

.sgep-zoom-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background-color: #2D8CFF;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.sgep-zoom-button:hover {
    background-color: #2681eb;
    color: white;
}

.sgep-table {
    width: 100%;
    border-collapse: collapse;
}

.sgep-table th {
    text-align: left;
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    color: #495057;
}

.sgep-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
}

.sgep-estado-confirmada {
    background-color: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.sgep-estado-pendiente {
    background-color: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

/* Icono para la acción rápida */
.sgep-quick-action-icon svg {
    width: 24px;
    height: 24px;
    color: #0073aa;
}

/* Responsive */
@media (max-width: 768px) {
    .sgep-citas-hoy-grid {
        grid-template-columns: 1fr;
    }
    
    .sgep-proximas-citas-table {
        overflow-x: auto;
    }
    
    .sgep-table {
        min-width: 600px;
    }
}
</style>