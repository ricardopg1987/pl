<?php
/**
 * Plantilla para el dashboard de administración
 * 
 * Ruta: /admin/templates/dashboard.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Obtener estadísticas
$total_especialistas = count_users()['avail_roles']['sgep_especialista'] ?? 0;
$total_clientes = count_users()['avail_roles']['sgep_cliente'] ?? 0;

$total_citas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas");
$citas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'pendiente'");
$citas_confirmadas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'confirmada'");
$citas_canceladas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE estado = 'cancelada'");

$total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sgep_test_resultados");
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title"><?php _e('Dashboard - Sistema de Gestión de Especialistas y Pacientes', 'sgep'); ?></h1>
    </div>
    
    <div class="sgep-admin-dashboard">
        <h2><?php _e('Resumen del sistema', 'sgep'); ?></h2>
        
        <div class="sgep-admin-stats">
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($total_especialistas); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Especialistas registrados', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($total_clientes); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Clientes registrados', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($total_citas); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Citas totales', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($citas_pendientes); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Citas pendientes', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($citas_confirmadas); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Citas confirmadas', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($citas_canceladas); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Citas canceladas', 'sgep'); ?></div>
            </div>
            
            <div class="sgep-admin-stat-card">
                <div class="sgep-admin-stat-value"><?php echo esc_html($total_tests); ?></div>
                <div class="sgep-admin-stat-label"><?php _e('Tests de compatibilidad realizados', 'sgep'); ?></div>
            </div>
        </div>
        
        <div class="sgep-admin-boxes">
            <div class="sgep-admin-box">
                <div class="sgep-admin-box-header">
                    <?php _e('Últimas citas agendadas', 'sgep'); ?>
                </div>
                <div class="sgep-admin-box-content">
                    <?php
                    // Obtener últimas citas
                    $ultimas_citas = $wpdb->get_results(
                        "SELECT c.*, e.display_name as especialista_nombre, cl.display_name as cliente_nombre 
                        FROM {$wpdb->prefix}sgep_citas c
                        LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
                        LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
                        ORDER BY c.created_at DESC
                        LIMIT 5"
                    );
                    
                    if (!empty($ultimas_citas)) : 
                    ?>
                        <table class="sgep-admin-table">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'sgep'); ?></th>
                                    <th><?php _e('Fecha', 'sgep'); ?></th>
                                    <th><?php _e('Especialista', 'sgep'); ?></th>
                                    <th><?php _e('Cliente', 'sgep'); ?></th>
                                    <th><?php _e('Estado', 'sgep'); ?></th>
                                    <th><?php _e('Acciones', 'sgep'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_citas as $cita) : 
                                    $fecha = new DateTime($cita->fecha);
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($cita->id); ?></td>
                                        <td><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></td>
                                        <td><?php echo esc_html($cita->especialista_nombre); ?></td>
                                        <td><?php echo esc_html($cita->cliente_nombre); ?></td>
                                        <td>
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
                                        </td>
                                        <td class="sgep-table-actions">
                                            <a href="<?php echo admin_url('admin.php?page=sgep-citas&action=view&id=' . $cita->id); ?>" class="sgep-action-button"><?php _e('Ver', 'sgep'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="sgep-view-all">
                            <a href="<?php echo admin_url('admin.php?page=sgep-citas'); ?>"><?php _e('Ver todas las citas', 'sgep'); ?> &rarr;</a>
                        </p>
                    <?php else : ?>
                        <p><?php _e('No hay citas agendadas.', 'sgep'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sgep-admin-box">
                <div class="sgep-admin-box-header">
                    <?php _e('Últimos usuarios registrados', 'sgep'); ?>
                </div>
                <div class="sgep-admin-box-content">
                    <?php
                    // Obtener últimos usuarios registrados
                    $ultimos_usuarios = get_users(array(
                        'role__in' => array('sgep_especialista', 'sgep_cliente'),
                        'orderby' => 'registered',
                        'order' => 'DESC',
                        'number' => 5
                    ));
                    
                    if (!empty($ultimos_usuarios)) : 
                    ?>
                        <table class="sgep-admin-table">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'sgep'); ?></th>
                                    <th><?php _e('Usuario', 'sgep'); ?></th>
                                    <th><?php _e('Email', 'sgep'); ?></th>
                                    <th><?php _e('Rol', 'sgep'); ?></th>
                                    <th><?php _e('Registro', 'sgep'); ?></th>
                                    <th><?php _e('Acciones', 'sgep'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos_usuarios as $usuario) : 
                                    $roles = new SGEP_Roles();
                                    $registro = new DateTime($usuario->user_registered);
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($usuario->ID); ?></td>
                                        <td><?php echo esc_html($usuario->display_name); ?></td>
                                        <td><?php echo esc_html($usuario->user_email); ?></td>
                                        <td>
                                            <?php
                                            if ($roles->is_especialista($usuario->ID)) {
                                                echo __('Especialista', 'sgep');
                                            } elseif ($roles->is_cliente($usuario->ID)) {
                                                echo __('Cliente', 'sgep');
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($registro->format('d/m/Y H:i')); ?></td>
                                        <td class="sgep-table-actions">
                                            <?php
                                            if ($roles->is_especialista($usuario->ID)) {
                                                echo '<a href="' . admin_url('admin.php?page=sgep-especialistas&action=view&id=' . $usuario->ID) . '" class="sgep-action-button">' . __('Ver', 'sgep') . '</a>';
                                            } elseif ($roles->is_cliente($usuario->ID)) {
                                                echo '<a href="' . admin_url('admin.php?page=sgep-clientes&action=view&id=' . $usuario->ID) . '" class="sgep-action-button">' . __('Ver', 'sgep') . '</a>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="sgep-view-all">
                            <a href="<?php echo admin_url('admin.php?page=sgep-especialistas'); ?>"><?php _e('Ver todos los especialistas', 'sgep'); ?> &rarr;</a> | 
                            <a href="<?php echo admin_url('admin.php?page=sgep-clientes'); ?>"><?php _e('Ver todos los clientes', 'sgep'); ?> &rarr;</a>
                        </p>
                    <?php else : ?>
                        <p><?php _e('No hay usuarios registrados.', 'sgep'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="sgep-admin-info-cards">
            <div class="sgep-admin-info-card">
                <h3><?php _e('Enlaces rápidos', 'sgep'); ?></h3>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=sgep-especialistas'); ?>"><?php _e('Gestionar especialistas', 'sgep'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=sgep-clientes'); ?>"><?php _e('Gestionar clientes', 'sgep'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=sgep-citas'); ?>"><?php _e('Gestionar citas', 'sgep'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=sgep-test'); ?>"><?php _e('Configurar test de compatibilidad', 'sgep'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=sgep-settings'); ?>"><?php _e('Configuración del plugin', 'sgep'); ?></a></li>
                </ul>
            </div>
            
            <div class="sgep-admin-info-card">
                <h3><?php _e('Shortcodes disponibles', 'sgep'); ?></h3>
                <table class="sgep-shortcodes-table">
                    <tr>
                        <td><code>[sgep_login]</code></td>
                        <td><?php _e('Formulario de inicio de sesión', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_registro]</code></td>
                        <td><?php _e('Formulario de registro', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_panel_especialista]</code></td>
                        <td><?php _e('Panel del especialista', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_panel_cliente]</code></td>
                        <td><?php _e('Panel del cliente', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_test_match]</code></td>
                        <td><?php _e('Test de compatibilidad', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_resultados_match]</code></td>
                        <td><?php _e('Resultados del test de compatibilidad', 'sgep'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[sgep_directorio_especialistas]</code></td>
                        <td><?php _e('Directorio de especialistas', 'sgep'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>