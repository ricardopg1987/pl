<?php
/**
 * Plantilla para el panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global aquí también
global $wpdb;

// Obtener datos del especialista
$especialista_id = $user->ID;

// Obtener páginas
$pages = get_option('sgep_pages', array());

// Obtener mensaje de notificación
$mensaje = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
?>

<div class="sgep-panel-container">
    <div class="sgep-panel-header">
        <div class="sgep-header-main">
            <h2><?php _e('Panel del Especialista', 'sgep'); ?></h2>
            <p class="sgep-welcome"><?php printf(__('Bienvenido/a, %s', 'sgep'), $user->display_name); ?></p>
        </div>
        
        <div class="sgep-header-actions">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="sgep-button sgep-logout-btn"><?php _e('Cerrar Sesión', 'sgep'); ?></a>
        </div>
        
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
                <li class="<?php echo $tab === 'disponibilidad' ? 'active' : ''; ?>">
                    <a href="?tab=disponibilidad"><?php _e('Disponibilidad', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab === 'citas' ? 'active' : ''; ?>">
                    <a href="?tab=citas"><?php _e('Citas', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab === 'mensajes' ? 'active' : ''; ?>">
                    <a href="?tab=mensajes"><?php _e('Mensajes', 'sgep'); ?></a>
                </li>
            </ul>
        </div>
        
        <div class="sgep-panel-tab-content">
            <?php
            switch ($tab) {
                case 'perfil':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista/perfil.php');
                    break;
                    
                case 'disponibilidad':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista/disponibilidad.php');
                    break;
                    
                case 'citas':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista/citas.php');
                    break;
                    
                case 'mensajes':
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista/mensajes.php');
                    break;
                    
                default:
                    include(SGEP_PLUGIN_DIR . 'public/templates/panel-especialista/dashboard.php');
                    break;
            }
            ?>
        </div>
    </div>
</div>