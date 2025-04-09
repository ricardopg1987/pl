<?php
/**
 * Plantilla para la gestión de productos
 * 
 * Ruta: /admin/templates/productos.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title"><?php _e('Gestión de Productos', 'sgep'); ?></h1>
        <div class="sgep-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=sgep-productos&accion=crear'); ?>" class="button button-primary"><?php _e('Añadir Nuevo Producto', 'sgep'); ?></a>
        </div>
    </div>
    
    <?php if (isset($mensaje)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($mensaje); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="sgep-admin-content">
        <?php if (!empty($productos)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sgep'); ?></th>
                        <th><?php _e('SKU', 'sgep'); ?></th>
                        <th><?php _e('Imagen', 'sgep'); ?></th>
                        <th><?php _e('Nombre', 'sgep'); ?></th>
                        <th><?php _e('Precio', 'sgep'); ?></th>
                        <th><?php _e('Stock', 'sgep'); ?></th>
                        <th><?php _e('Categoría', 'sgep'); ?></th>
                        <th><?php _e('Especialista', 'sgep'); ?></th>
                        <th><?php _e('Acciones', 'sgep'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto) : ?>
                        <tr>
                            <td><?php echo esc_html($producto->id); ?></td>
                            <td><?php echo esc_html($producto->sku); ?></td>
                            <td>
                                <?php if (!empty($producto->imagen_url)) : ?>
                                    <img src="<?php echo esc_url($producto->imagen_url); ?>" alt="<?php echo esc_attr($producto->nombre); ?>" width="50" height="50">
                                <?php else : ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><a href="<?php echo admin_url('admin.php?page=sgep-productos&accion=editar&id=' . $producto->id); ?>"><?php echo esc_html($producto->nombre); ?></a></strong>
                            </td>
                            <td><?php echo esc_html($producto->precio); ?></td>
                            <td><?php echo esc_html($producto->stock); ?></td>
                            <td><?php echo esc_html($producto->categoria); ?></td>
                            <td>
                                <?php if (!empty($producto->especialista_id)) : ?>
                                    <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=view&id=' . $producto->especialista_id); ?>">
                                        <?php echo esc_html($producto->especialista_nombre); ?>
                                    </a>
                                <?php else : ?>
                                    <?php _e('Admin', 'sgep'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="sgep-table-actions">
                                <a href="<?php echo admin_url('admin.php?page=sgep-productos&accion=editar&id=' . $producto->id); ?>" class="sgep-action-button"><?php _e('Editar', 'sgep'); ?></a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sgep-productos&accion=eliminar&id=' . $producto->id), 'eliminar_producto_' . $producto->id); ?>" class="sgep-action-button sgep-action-delete" onclick="return confirm('<?php _e('¿Estás seguro de eliminar este producto?', 'sgep'); ?>')"><?php _e('Eliminar', 'sgep'); ?></a>
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
            <p><?php _e('No hay productos registrados.', 'sgep'); ?></p>
        <?php endif; ?>
    </div>
</div>