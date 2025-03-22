<?php
/**
 * Plantilla para el panel de cliente
 * 
 * Ruta: /public/templates/panel-cliente.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del cliente
$cliente_id = $user->ID;

// Obtener páginas
$pages = get_option('sgep_pages', array());

// Obtener mensaje de notificación
$mensaje = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';

// Verificar si ya realizó el test
global $wpdb;
$test_realizado = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}sgep_test_resultados WHERE cliente_id = %d LIMIT 1",
    $cliente_id
));
?>

<div class="sgep-panel-container">
    <div class="sgep-panel-header">
        <h2><?php _e('Panel del Cliente', 'sgep'); ?></h2>
        <p class="sgep-welcome"><?php printf(__('Bienvenido/a, %s', 'sgep'), $user->display_name); ?></p>
        
        <?php if (!empty($mensaje)) : ?>
            <div class="sgep-notification">
                <?php echo esc_html($mensaje); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="sgep-panel-content">
        <div class="sgep-panel-tabs">
            <ul>
                <li class="<?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                    <a href="?tab=dashboard"><?php _e('Dashboard', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab === 'perfil' ? 'active' : ''; ?>">
                    <a href="?tab=perfil"><?php _e('Mi Perfil', 'sgep'); ?></a>
                </li>
                <?php if ($test_realizado) : ?>
                <li class="<?php echo $tab === 'especialistas' ? 'active' : ''; ?>">
                    <a href="?tab=especialistas"><?php _e('Mis Especialistas', 'sgep'); ?></a>
                </li>
                <?php endif; ?>
                <li class="<?php echo $tab === 'citas' ? 'active' : ''; ?>">
                    <a href="?tab=citas"><?php _e('Mis Citas', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab === 'mensajes' ? 'active' : ''; ?>">
                    <a href="?tab=mensajes"><?php _e('Mensajes', 'sgep'); ?></a>
                </li>
                <?php if (!$test_realizado) : ?>
                <li class="<?php echo $tab === 'test' ? 'active' : ''; ?>">
                    <a href="?tab=test"><?php _e('Test de Compatibilidad', 'sgep'); ?></a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="sgep-panel-tab-content">
            <?php
            switch ($tab) {
                case 'perfil':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/perfil.php');
                    break;
                    
                case 'especialistas':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/especialistas.php');
                    break;
                    
                case 'citas':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/citas.php');
                    break;
                    
                case 'mensajes':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/mensajes.php');
                    break;
                    
                case 'test':
                    // Redireccionar a la página del test
                    if (!$test_realizado && isset($pages['sgep-test-match'])) {
                        echo '<p>' . __('Redirigiendo al test de compatibilidad...', 'sgep') . '</p>';
                        echo '<script>window.location.href = "' . esc_url(get_permalink($pages['sgep-test-match'])) . '";</script>';
                    } else {
                        include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/dashboard.php');
                    }
                    break;
                    
                default:
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/dashboard.php');
                    break;
            }
            ?>
        </div>
    </div>
</div>