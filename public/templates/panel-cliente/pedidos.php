<?php
/**
 * Plantilla para la pestaña de pedidos del panel de cliente
 * 
 * Ruta: /public/templates/panel-cliente/pedidos.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Obtener cliente actual
$cliente_id = get_current_user_id();

// Verificar acción
$accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si es para ver un pedido específico
if ($accion === 'ver' && $pedido_id > 0) {
    // Obtener el pedido
    $pedido = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_pedidos 
         WHERE id = %d AND cliente_id = %d",
        $pedido_id, $cliente_id
    ));
    
    if (!$pedido) {
        echo '<p class="sgep-error">' . __('El pedido no existe o no tienes permisos para verlo.', 'sgep') . '</p>';
        return;
    }
    
    // Obtener detalles del pedido
    $detalles = $wpdb->get_results($wpdb->prepare(
        "SELECT d.*, p.nombre, p.descripcion, p.imagen_url, p.sku, p.categoria,
                e.display_name as especialista_nombre
         FROM {$wpdb->prefix}sgep_pedidos_detalle d
         JOIN {$wpdb->prefix}sgep_productos p ON d.producto_id = p.id
         LEFT JOIN {$wpdb->users} e ON p.especialista_id = e.ID
         WHERE d.pedido_id = %d",
        $pedido_id
    ));
    
    // Fecha del pedido
    $fecha_pedido = new DateTime($pedido->fecha);
    ?>
    
    <div class="sgep-pedido-detalle">
        <div class="sgep-pedido-header">
            <h3><?php echo sprintf(__('Detalles del Pedido #%d', 'sgep'), $pedido_id); ?></h3>
            
            <div class="sgep-pedido-estado">
                <?php 
                switch ($pedido->estado) {
                    case 'pendiente':
                        echo '<span class="sgep-estado-pendiente">' . __('Pendiente', 'sgep') . '</span>';
                        break;
                    case 'procesando':
                        echo '<span class="sgep-estado-procesando">' . __('Procesando', 'sgep') . '</span>';
                        break;
                    case 'completado':
                        echo '<span class="sgep-estado-completado">' . __('Completado', 'sgep') . '</span>';
                        break;
                    case 'cancelado':
                        echo '<span class="sgep-estado-cancelado">' . __('Cancelado', 'sgep') . '</span>';
                        break;
                    default:
                        echo '<span class="sgep-estado-' . esc_attr($pedido->estado) . '">' . esc_html($pedido->estado) . '</span>';
                }
                ?>
            </div>
        </div>
        
        <div class="sgep-pedido-info">
            <div class="sgep-pedido-row">
                <div class="sgep-pedido-label"><?php _e('Fecha del pedido:', 'sgep'); ?></div>
                <div class="sgep-pedido-value"><?php echo esc_html($fecha_pedido->format('d/m/Y H:i')); ?></div>
            </div>
            
            <div class="sgep-pedido-row">
                <div class="sgep-pedido-label"><?php _e('Total:', 'sgep'); ?></div>
                <div class="sgep-pedido-value sgep-pedido-total"><?php echo esc_html($pedido->total); ?></div>
            </div>
            
            <?php if (!empty($pedido->notas)) : ?>
                <div class="sgep-pedido-row">
                    <div class="sgep-pedido-label"><?php _e('Notas:', 'sgep'); ?></div>
                    <div class="sgep-pedido-value"><?php echo nl2br(esc_html($pedido->notas)); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sgep-pedido-productos">
            <h4><?php _e('Productos', 'sgep'); ?></h4>
            
            <?php if (!empty($detalles)) : ?>
                <div class="sgep-pedido-productos-lista">
                    <?php foreach ($detalles as $detalle) : ?>
                        <div class="sgep-pedido-producto">
                            <div class="sgep-pedido-producto-imagen">
                                <?php if (!empty($detalle->imagen_url)) : ?>
                                    <img src="<?php echo esc_url($detalle->imagen_url); ?>" alt="<?php echo esc_attr($detalle->nombre); ?>">
                                <?php else : ?>
                                    <div class="sgep-producto-no-imagen">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="sgep-pedido-producto-info">
                                <h5><?php echo esc_html($detalle->nombre); ?></h5>
                                
                                <?php if (!empty($detalle->categoria)) : ?>
                                    <span class="sgep-pedido-producto-categoria">
                                        <?php 
                                        $categorias = array(
                                            'libros' => __('Libros', 'sgep'),
                                            'cursos' => __('Cursos', 'sgep'),
                                            'accesorios' => __('Accesorios', 'sgep'),
                                            'esencias' => __('Esencias', 'sgep'),
                                            'terapias' => __('Terapias', 'sgep'),
                                            'aceites' => __('Aceites', 'sgep'),
                                            'cristales' => __('Cristales', 'sgep'),
                                            'otros' => __('Otros', 'sgep'),
                                        );
                                        
                                        echo isset($categorias[$detalle->categoria]) ? 
                                            esc_html($categorias[$detalle->categoria]) : 
                                            esc_html($detalle->categoria);
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($detalle->descripcion)) : ?>
                                    <p class="sgep-pedido-producto-descripcion">
                                        <?php echo wp_trim_words(esc_html($detalle->descripcion), 20); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($detalle->especialista_nombre)) : ?>
                                    <div class="sgep-pedido-producto-especialista">
                                        <?php echo sprintf(__('Creado por: %s', 'sgep'), esc_html($detalle->especialista_nombre)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="sgep-pedido-producto-precio">
                                <div class="sgep-pedido-producto-cantidad">
                                    <?php echo sprintf(__('Cantidad: %d', 'sgep'), $detalle->cantidad); ?>
                                </div>
                                <div class="sgep-pedido-producto-precio-unitario">
                                    <?php echo sprintf(__('Precio: %s', 'sgep'), esc_html($detalle->precio_unitario)); ?>
                                </div>
                                <div class="sgep-pedido-producto-subtotal">
                                    <?php echo sprintf(__('Subtotal: %s', 'sgep'), number_format($detalle->precio_unitario * $detalle->cantidad, 2)); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="sgep-no-items"><?php _e('No hay productos en este pedido.', 'sgep'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="sgep-pedido-footer">
            <a href="?tab=pedidos" class="sgep-button sgep-button-text"><?php _e('Volver a Pedidos', 'sgep'); ?></a>
        </div>
    </div>
    
    <?php
} else {
    // Listado de pedidos
    $pedidos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_pedidos 
         WHERE cliente_id = %d 
         ORDER BY fecha DESC",
        $cliente_id
    ));
    ?>
    
    <div class="sgep-pedidos-wrapper">
        <h3><?php _e('Mis Pedidos', 'sgep'); ?></h3>
        
        <?php if (!empty($pedidos)) : ?>
            <div class="sgep-pedidos-lista">
                <table class="sgep-pedidos-tabla">
                    <thead>
                        <tr>
                            <th><?php _e('Pedido', 'sgep'); ?></th>
                            <th><?php _e('Fecha', 'sgep'); ?></th>
                            <th><?php _e('Estado', 'sgep'); ?></th>
                            <th><?php _e('Total', 'sgep'); ?></th>
                            <th><?php _e('Acciones', 'sgep'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido) : 
                            $fecha = new DateTime($pedido->fecha);
                        ?>
                            <tr>
                                <td>#<?php echo esc_html($pedido->id); ?></td>
                                <td><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></td>
                                <td>
                                    <?php 
                                    switch ($pedido->estado) {
                                        case 'pendiente':
                                            echo '<span class="sgep-estado-pendiente">' . __('Pendiente', 'sgep') . '</span>';
                                            break;
                                        case 'procesando':
                                            echo '<span class="sgep-estado-procesando">' . __('Procesando', 'sgep') . '</span>';
                                            break;
                                        case 'completado':
                                            echo '<span class="sgep-estado-completado">' . __('Completado', 'sgep') . '</span>';
                                            break;
                                        case 'cancelado':
                                            echo '<span class="sgep-estado-cancelado">' . __('Cancelado', 'sgep') . '</span>';
                                            break;
                                        default:
                                            echo '<span class="sgep-estado-' . esc_attr($pedido->estado) . '">' . esc_html($pedido->estado) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($pedido->total); ?></td>
                                <td>
                                    <a href="?tab=pedidos&accion=ver&id=<?php echo $pedido->id; ?>" class="sgep-button sgep-button-sm sgep-button-secondary">
                                        <?php _e('Ver detalles', 'sgep'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p class="sgep-no-items"><?php _e('No tienes pedidos realizados.', 'sgep'); ?></p>
            
            <?php
            // Mostar enlace al directorio de especialistas si existe la página
            $pages = get_option('sgep_pages', array());
            if (isset($pages['sgep-directorio-especialistas'])) :
                $directorio_url = get_permalink($pages['sgep-directorio-especialistas']);
            ?>
                <div class="sgep-pedidos-empty-action">
                    <p><?php _e('Explora nuestro directorio de especialistas para descubrir productos y servicios.', 'sgep'); ?></p>
                    <a href="<?php echo esc_url($directorio_url); ?>" class="sgep-button sgep-button-primary">
                        <?php _e('Ver especialistas', 'sgep'); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}