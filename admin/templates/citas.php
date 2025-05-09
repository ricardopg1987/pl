<?php
/**
 * Plantilla para la gestión de citas
 * 
 * Ruta: /admin/templates/citas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Parámetros de paginación
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Filtros
$estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$especialista_id = isset($_GET['especialista_id']) ? intval($_GET['especialista_id']) : 0;
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Consulta base
$query = "SELECT c.*, e.display_name as especialista_nombre, cl.display_name as cliente_nombre 
          FROM {$wpdb->prefix}sgep_citas c
          LEFT JOIN {$wpdb->users} e ON c.especialista_id = e.ID
          LEFT JOIN {$wpdb->users} cl ON c.cliente_id = cl.ID
          WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE 1=1";
$query_args = array();

// Agregar filtros
if (!empty($estado)) {
    $query .= " AND c.estado = %s";
    $count_query .= " AND estado = %s";
    $query_args[] = $estado;
}

if ($especialista_id > 0) {
    $query .= " AND c.especialista_id = %d";
    $count_query .= " AND especialista_id = %d";
    $query_args[] = $especialista_id;
}

if ($cliente_id > 0) {
    $query .= " AND c.cliente_id = %d";
    $count_query .= " AND cliente_id = %d";
    $query_args[] = $cliente_id;
}

// Ordenar
$query .= " ORDER BY c.fecha DESC";

// Paginación
$query .= " LIMIT %d OFFSET %d";
$query_args[] = $per_page;
$query_args[] = $offset;

// Obtener resultados
$citas = $wpdb->get_results($wpdb->prepare($query, $query_args));
$total_items = $wpdb->get_var($wpdb->prepare($count_query, array_slice($query_args, 0, -2)));
$total_pages = ceil($total_items / $per_page);

// Obtener especialistas y clientes para los filtros
$roles = new SGEP_Roles();
$especialistas = $roles->get_all_especialistas();
$clientes = $roles->get_all_clientes();
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title"><?php _e('Gestión de Citas', 'sgep'); ?></h1>
    </div>
    
    <div class="sgep-admin-content">
        <!-- Filtros -->
        <div class="sgep-table-filters">
            <form method="get" class="sgep-filter-form">
                <input type="hidden" name="page" value="sgep-citas">
                
                <div class="sgep-filter-group">
                    <label for="estado"><?php _e('Estado', 'sgep'); ?></label>
                    <select name="estado" id="estado">
                        <option value=""><?php _e('Todos', 'sgep'); ?></option>
                        <option value="pendiente" <?php selected($estado, 'pendiente'); ?>><?php _e('Pendiente', 'sgep'); ?></option>
                        <option value="confirmada" <?php selected($estado, 'confirmada'); ?>><?php _e('Confirmada', 'sgep'); ?></option>
                        <option value="cancelada" <?php selected($estado, 'cancelada'); ?>><?php _e('Cancelada', 'sgep'); ?></option>
                    </select>
                </div>
                
                <div class="sgep-filter-group">
                    <label for="especialista_id"><?php _e('Especialista', 'sgep'); ?></label>
                    <select name="especialista_id" id="especialista_id">
                        <option value=""><?php _e('Todos', 'sgep'); ?></option>
                        <?php foreach ($especialistas as $especialista) : ?>
                            <option value="<?php echo esc_attr($especialista->ID); ?>" <?php selected($especialista_id, $especialista->ID); ?>><?php echo esc_html($especialista->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sgep-filter-group">
                    <label for="cliente_id"><?php _e('Cliente', 'sgep'); ?></label>
                    <select name="cliente_id" id="cliente_id">
                        <option value=""><?php _e('Todos', 'sgep'); ?></option>
                        <?php foreach ($clientes as $cliente) : ?>
                            <option value="<?php echo esc_attr($cliente->ID); ?>" <?php selected($cliente_id, $cliente->ID); ?>><?php echo esc_html($cliente->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sgep-filter-actions">
                    <button type="submit" class="button"><?php _e('Filtrar', 'sgep'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=sgep-citas'); ?>" class="button"><?php _e('Limpiar', 'sgep'); ?></a>
                </div>
            </form>
        </div>
        
        <!-- Listado de citas -->
        <?php if (!empty($citas)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sgep'); ?></th>
                        <th><?php _e('Fecha y Hora', 'sgep'); ?></th>
                        <th><?php _e('Especialista', 'sgep'); ?></th>
                        <th><?php _e('Cliente', 'sgep'); ?></th>
                        <th><?php _e('Estado', 'sgep'); ?></th>
                        <th><?php _e('Zoom', 'sgep'); ?></th>
                        <th><?php _e('Fecha Creación', 'sgep'); ?></th>
                        <th><?php _e('Acciones', 'sgep'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $cita) : 
                        $fecha = new DateTime($cita->fecha);
                        $creacion = new DateTime($cita->created_at);
                    ?>
                        <tr>
                            <td><?php echo esc_html($cita->id); ?></td>
                            <td><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=view&id=' . $cita->especialista_id); ?>">
                                    <?php echo esc_html($cita->especialista_nombre); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=sgep-clientes&action=view&id=' . $cita->cliente_id); ?>">
                                    <?php echo esc_html($cita->cliente_nombre); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                switch ($cita->estado) {
                                    case 'pendiente':
                                        echo '<span class="sgep-status sgep-status-warning">' . __('Pendiente', 'sgep') . '</span>';
                                        break;
                                    case 'confirmada':
                                        echo '<span class="sgep-status sgep-status-success">' . __('Confirmada', 'sgep') . '</span>';
                                        break;
                                    case 'cancelada':
                                        echo '<span class="sgep-status sgep-status-error">' . __('Cancelada', 'sgep') . '</span>';
                                        break;
                                    default:
                                        echo esc_html($cita->estado);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($cita->zoom_link)) : ?>
                                    <a href="<?php echo esc_url($cita->zoom_link); ?>" target="_blank"><?php _e('Enlace Zoom', 'sgep'); ?></a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($creacion->format('d/m/Y H:i')); ?></td>
                            <td class="sgep-table-actions">
                                <a href="#" class="sgep-action-button sgep-ver-cita" data-id="<?php echo $cita->id; ?>"><?php _e('Ver', 'sgep'); ?></a>
                                <?php if ($cita->estado === 'pendiente') : ?>
                                    <a href="#" class="sgep-action-button sgep-cambiar-estado" data-id="<?php echo $cita->id; ?>" data-estado="confirmada"><?php _e('Confirmar', 'sgep'); ?></a>
                                <?php endif; ?>
                                <?php if ($cita->estado !== 'cancelada') : ?>
                                    <a href="#" class="sgep-action-button sgep-cambiar-estado" data-id="<?php echo $cita->id; ?>" data-estado="cancelada"><?php _e('Cancelar', 'sgep'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1) : ?>
                <div class="sgep-admin-pagination">
                    <?php
                    $url = add_query_arg($_GET, admin_url('admin.php'));
                    
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $class = $i === $paged ? 'sgep-pagination-current' : '';
                        $page_url = add_query_arg('paged', $i, $url);
                        
                        echo '<a href="' . esc_url($page_url) . '" class="sgep-pagination-link ' . $class . '">' . $i . '</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
            <?php else : ?>
           <p><?php _e('No se encontraron citas con los filtros aplicados.', 'sgep'); ?></p>
       <?php endif; ?>
   </div>
</div>

<!-- Modal para ver detalles de cita -->
<div id="sgep-cita-detalles-modal" class="sgep-modal">
   <div class="sgep-modal-content">
       <span class="sgep-modal-close">&times;</span>
       <h2><?php _e('Detalles de la Cita', 'sgep'); ?></h2>
       <div id="sgep-cita-detalles-content"></div>
   </div>
</div>