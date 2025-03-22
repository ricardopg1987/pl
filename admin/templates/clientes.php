<?php
/**
 * Plantilla para la gestión de clientes
 * 
 * Ruta: /admin/templates/clientes.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar acción
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si es para ver o editar un cliente específico
if (($action === 'view' || $action === 'edit') && $cliente_id > 0) {
    $cliente = get_userdata($cliente_id);
    
    // Si no existe el cliente o no es del rol correcto
    if (!$cliente || !in_array('sgep_cliente', $cliente->roles)) {
        echo '<div class="error"><p>' . __('Cliente no encontrado.', 'sgep') . '</p></div>';
        return;
    }
    
    // Obtener meta datos
    $telefono = get_user_meta($cliente_id, 'sgep_telefono', true);
    $fecha_nacimiento = get_user_meta($cliente_id, 'sgep_fecha_nacimiento', true);
    $intereses = get_user_meta($cliente_id, 'sgep_intereses', true);
    
    // Si es acción de editar y se envió el formulario
    if ($action === 'edit' && isset($_POST['sgep_edit_cliente_nonce']) && wp_verify_nonce($_POST['sgep_edit_cliente_nonce'], 'sgep_edit_cliente')) {
        // Actualizar datos del cliente
        $telefono = sanitize_text_field($_POST['sgep_telefono']);
        $fecha_nacimiento = sanitize_text_field($_POST['sgep_fecha_nacimiento']);
        $intereses = isset($_POST['sgep_intereses']) ? (array) $_POST['sgep_intereses'] : array();
        
        // Guardar meta datos
        update_user_meta($cliente_id, 'sgep_telefono', $telefono);
        update_user_meta($cliente_id, 'sgep_fecha_nacimiento', $fecha_nacimiento);
        update_user_meta($cliente_id, 'sgep_intereses', $intereses);
        
        // Mostrar mensaje de éxito
        echo '<div class="updated"><p>' . __('Cliente actualizado con éxito.', 'sgep') . '</p></div>';
    }
    
    // Mostrar formulario o vista de detalle
    ?>
    <div class="wrap sgep-admin-container">
        <div class="sgep-admin-header">
            <h1 class="sgep-admin-title"><?php echo $action === 'edit' ? __('Editar Cliente', 'sgep') : __('Detalles del Cliente', 'sgep'); ?></h1>
            <div class="sgep-admin-actions">
                <?php if ($action === 'view') : ?>
                    <a href="<?php echo admin_url('admin.php?page=sgep-clientes&action=edit&id=' . $cliente_id); ?>" class="button button-primary"><?php _e('Editar', 'sgep'); ?></a>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=sgep-clientes'); ?>" class="button"><?php _e('Volver al listado', 'sgep'); ?></a>
            </div>
        </div>
        
        <div class="sgep-admin-content">
            <?php if ($action === 'edit') : ?>
                <!-- Formulario de edición -->
                <form method="post" class="sgep-admin-form">
                    <?php wp_nonce_field('sgep_edit_cliente', 'sgep_edit_cliente_nonce'); ?>
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Información Personal', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-row">
                            <div class="sgep-form-col">
                                <div class="sgep-form-field">
                                    <label for="sgep_telefono"><?php _e('Teléfono', 'sgep'); ?></label>
                                    <input type="text" id="sgep_telefono" name="sgep_telefono" value="<?php echo esc_attr($telefono); ?>">
                                </div>
                            </div>
                            
                            <div class="sgep-form-col">
                                <div class="sgep-form-field">
                                    <label for="sgep_fecha_nacimiento"><?php _e('Fecha de Nacimiento', 'sgep'); ?></label>
                                    <input type="date" id="sgep_fecha_nacimiento" name="sgep_fecha_nacimiento" value="<?php echo esc_attr($fecha_nacimiento); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Intereses', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-field">
                            <label for="sgep_intereses"><?php _e('Áreas de Interés', 'sgep'); ?></label>
                            <select id="sgep_intereses" name="sgep_intereses[]" class="sgep-intereses-select" multiple>
                                <?php
                                $intereses_options = array(
                                    'ansiedad' => __('Ansiedad', 'sgep'),
                                    'depresion' => __('Depresión', 'sgep'),
                                    'estres' => __('Estrés', 'sgep'),
                                    'autoestima' => __('Autoestima', 'sgep'),
                                    'relaciones' => __('Relaciones', 'sgep'),
                                    'duelo' => __('Duelo', 'sgep'),
                                    'trauma' => __('Trauma', 'sgep'),
                                    'adicciones' => __('Adicciones', 'sgep'),
                                    'alimentacion' => __('Trastornos Alimenticios', 'sgep'),
                                    'sueno' => __('Problemas de Sueño', 'sgep'),
                                    'desarrollo_personal' => __('Desarrollo Personal', 'sgep'),
                                    'coaching' => __('Coaching', 'sgep'),
                                    'familiar' => __('Terapia Familiar', 'sgep'),
                                    'pareja' => __('Terapia de Pareja', 'sgep'),
                                );
                                
                                foreach ($intereses_options as $value => $label) {
                                    $selected = is_array($intereses) && in_array($value, $intereses) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Selecciona las áreas de interés para el cliente.', 'sgep'); ?></p>
                        </div>
                    </div>
                    
                    <div class="sgep-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
                        <a href="<?php echo admin_url('admin.php?page=sgep-clientes'); ?>" class="button"><?php _e('Cancelar', 'sgep'); ?></a>
                    </div>
                </form>
            <?php else : ?>
                <!-- Vista de detalle -->
                <div class="sgep-admin-view">
                    <div class="sgep-admin-view-header">
                        <div class="sgep-admin-view-avatar">
                            <?php echo get_avatar($cliente_id, 100); ?>
                        </div>
                        <div class="sgep-admin-view-title">
                            <h2><?php echo esc_html($cliente->display_name); ?></h2>
                        </div>
                    </div>
                    
                    <div class="sgep-admin-view-content">
                        <div class="sgep-admin-view-section">
                            <h3><?php _e('Información Personal', 'sgep'); ?></h3>
                            
                            <table class="sgep-admin-view-table">
                                <tr>
                                    <th><?php _e('Email', 'sgep'); ?></th>
                                    <td><?php echo esc_html($cliente->user_email); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Fecha de Registro', 'sgep'); ?></th>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($cliente->user_registered)); ?></td>
                                </tr>
                                <?php if (!empty($telefono)) : ?>
                                    <tr>
                                        <th><?php _e('Teléfono', 'sgep'); ?></th>
                                        <td><?php echo esc_html($telefono); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($fecha_nacimiento)) : ?>
                                    <tr>
                                        <th><?php _e('Fecha de Nacimiento', 'sgep'); ?></th>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($fecha_nacimiento)); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <?php if (!empty($intereses) && is_array($intereses)) : ?>
                            <div class="sgep-admin-view-section">
                                <h3><?php _e('Intereses', 'sgep'); ?></h3>
                                <div class="sgep-admin-view-tags">
                                    <?php
                                    $intereses_options = array(
                                        'ansiedad' => __('Ansiedad', 'sgep'),
                                        'depresion' => __('Depresión', 'sgep'),
                                        'estres' => __('Estrés', 'sgep'),
                                        'autoestima' => __('Autoestima', 'sgep'),
                                        'relaciones' => __('Relaciones', 'sgep'),
                                        'duelo' => __('Duelo', 'sgep'),
                                        'trauma' => __('Trauma', 'sgep'),
                                        'adicciones' => __('Adicciones', 'sgep'),
                                        'alimentacion' => __('Trastornos Alimenticios', 'sgep'),
                                        'sueno' => __('Problemas de Sueño', 'sgep'),
                                        'desarrollo_personal' => __('Desarrollo Personal', 'sgep'),
                                        'coaching' => __('Coaching', 'sgep'),
                                        'familiar' => __('Terapia Familiar', 'sgep'),
                                        'pareja' => __('Terapia de Pareja', 'sgep'),
                                    );
                                    
                                    foreach ($intereses as $interes) {
                                        if (isset($intereses_options[$interes])) {
                                            echo '<span class="sgep-admin-tag">' . esc_html($intereses_options[$interes]) . '</span>';
                                        } else {
                                            echo '<span class="sgep-admin-tag">' . esc_html($interes) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sgep-admin-view-section">
                            <h3><?php _e('Estadísticas', 'sgep'); ?></h3>
                            
                            <?php
                            // Obtener estadísticas
                            global $wpdb;
                            
                            $total_citas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE cliente_id = %d",
                                $cliente_id
                            ));
                            
                            $citas_confirmadas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE cliente_id = %d AND estado = 'confirmada'",
                                $cliente_id
                            ));
                            
                            $citas_pendientes = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE cliente_id = %d AND estado = 'pendiente'",
                                $cliente_id
                            ));
                            
                            $citas_canceladas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE cliente_id = %d AND estado = 'cancelada'",
                                $cliente_id
                            ));
                            
                            $test_realizado = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d",
                                $cliente_id
                            ));
                            
                            $total_matches = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_matches WHERE cliente_id = %d",
                                $cliente_id
                            ));
                            ?>
                            
                            <table class="sgep-admin-view-table">
                                <tr>
                                    <th><?php _e('Total de Citas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($total_citas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Confirmadas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_confirmadas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Pendientes', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_pendientes); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Canceladas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_canceladas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Test de Compatibilidad', 'sgep'); ?></th>
                                    <td>
                                        <?php if ($test_realizado > 0) : ?>
                                            <?php _e('Realizado', 'sgep'); ?> 
                                            <a href="#" class="sgep-ver-test" data-id="<?php echo $cliente_id; ?>"><?php _e('(Ver resultados)', 'sgep'); ?></a>
                                        <?php else : ?>
                                            <?php _e('No realizado', 'sgep'); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Total de Matches', 'sgep'); ?></th>
                                    <td><?php echo esc_html($total_matches); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal para ver resultados del test -->
    <div id="sgep-test-resultados-modal" class="sgep-modal">
        <div class="sgep-modal-content">
            <span class="sgep-modal-close">&times;</span>
            <h2><?php _e('Resultados del Test de Compatibilidad', 'sgep'); ?></h2>
            <div id="sgep-test-resultados-content"></div>
        </div>
    </div>
    <?php
} else {
    // Listado de clientes
    ?>
    <div class="wrap sgep-admin-container">
        <div class="sgep-admin-header">
            <h1 class="sgep-admin-title"><?php _e('Gestión de Clientes', 'sgep'); ?></h1>
            <div class="sgep-admin-actions">
                <a href="<?php echo admin_url('user-new.php?role=sgep_cliente'); ?>" class="button button-primary"><?php _e('Añadir Nuevo Cliente', 'sgep'); ?></a>
            </div>
        </div>
        
        <div class="sgep-admin-content">
            <?php if (!empty($clientes)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'sgep'); ?></th>
                            <th><?php _e('Avatar', 'sgep'); ?></th>
                            <th><?php _e('Nombre', 'sgep'); ?></th>
                            <th><?php _e('Email', 'sgep'); ?></th>
                            <th><?php _e('Teléfono', 'sgep'); ?></th>
                            <th><?php _e('Test', 'sgep'); ?></th>
                            <th><?php _e('Citas', 'sgep'); ?></th>
                            <th><?php _e('Fecha Registro', 'sgep'); ?></th>
                            <th><?php _e('Acciones', 'sgep'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente) : 
                            $telefono = get_user_meta($cliente->ID, 'sgep_telefono', true);
                            
                            // Verificar si ha realizado el test
                            $test_realizado = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d",
                                $cliente->ID
                            ));
                            
                            // Obtener total de citas
                            $total_citas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE cliente_id = %d",
                                $cliente->ID
                            ));
                        ?>
                            <tr>
                                <td><?php echo esc_html($cliente->ID); ?></td>
                                <td><?php echo get_avatar($cliente->ID, 32); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=sgep-clientes&action=view&id=' . $cliente->ID); ?>">
                                            <?php echo esc_html($cliente->display_name); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($cliente->user_email); ?></td>
                                <td><?php echo !empty($telefono) ? esc_html($telefono) : '-'; ?></td>
                                <td>
                                    <?php if ($test_realizado > 0) : ?>
                                        <span class="sgep-status sgep-status-success"><?php _e('Realizado', 'sgep'); ?></span>
                                        <a href="#" class="sgep-ver-test" data-id="<?php echo $cliente->ID; ?>"><?php _e('Ver', 'sgep'); ?></a>
                                    <?php else : ?>
                                        <span class="sgep-status sgep-status-warning"><?php _e('No realizado', 'sgep'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($total_citas); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($cliente->user_registered)); ?></td>
                                <td class="sgep-table-actions">
                                    <a href="<?php echo admin_url('admin.php?page=sgep-clientes&action=view&id=' . $cliente->ID); ?>" class="sgep-action-button"><?php _e('Ver', 'sgep'); ?></a>
                                    <a href="<?php echo admin_url('admin.php?page=sgep-clientes&action=edit&id=' . $cliente->ID); ?>" class="sgep-action-button"><?php _e('Editar', 'sgep'); ?></a>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $cliente->ID); ?>" class="sgep-action-button"><?php _e('Editar Usuario', 'sgep'); ?></a>
                                    <a href="#" class="sgep-action-button sgep-action-delete sgep-delete-cliente" data-id="<?php echo $cliente->ID; ?>"><?php _e('Eliminar', 'sgep'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No hay clientes registrados.', 'sgep'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal para ver resultados del test -->
    <div id="sgep-test-resultados-modal" class="sgep-modal">
        <div class="sgep-modal-content">
            <span class="sgep-modal-close">&times;</span>
            <h2><?php _e('Resultados del Test de Compatibilidad', 'sgep'); ?></h2>
            <div id="sgep-test-resultados-content"></div>
        </div>
    </div>
    <?php
}