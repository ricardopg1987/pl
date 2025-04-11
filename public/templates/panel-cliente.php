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

// Verificar si hay parámetros para ver perfil de especialista o agendar cita
$ver_especialista = isset($_GET['ver']) ? intval($_GET['ver']) : 0;
$agendar_con = isset($_GET['agendar_con']) ? intval($_GET['agendar_con']) : 0;

// Ajustar la pestaña activa en base a estos parámetros
if ($ver_especialista > 0) {
    $tab = 'especialistas';
}

if ($agendar_con > 0) {
    $tab = 'citas';
}
?>

<div class="sgep-panel-container">
    <!-- Añadimos estilo CSS para el botón de cierre de sesión en el menú -->
    <style>
        .sgep-panel-tabs ul li.sgep-logout-button {
            float: right;
            margin-right: 0;
        }
        .sgep-panel-tabs ul li.sgep-logout-button a:hover {
            color: #b32d2e !important;
            font-weight: bold;
        }
    </style>
    <!-- Se ha eliminado el botón superior y se ha movido al menú de navegación -->
    
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
        <!-- 
Busca la sección del menú de navegación en el archivo public/templates/panel-cliente.php
Aproximadamente en la línea 60-80, reemplaza el bloque de código del menú por este: 
-->

<div class="sgep-panel-tabs">
    <ul>
        <li class="<?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
            <a href="?tab=dashboard"><?php _e('Dashboard', 'sgep'); ?></a>
        </li>
        <li class="<?php echo $tab === 'perfil' ? 'active' : ''; ?>">
            <a href="?tab=perfil"><?php _e('Mi Perfil', 'sgep'); ?></a>
        </li>
        <li class="<?php echo $tab === 'especialistas' ? 'active' : ''; ?>">
            <a href="?tab=especialistas"><?php _e('Especialistas', 'sgep'); ?></a>
        </li>
        <li class="<?php echo $tab === 'citas' ? 'active' : ''; ?>">
            <a href="?tab=citas"><?php _e('Mis Citas', 'sgep'); ?></a>
        </li>
        <li class="<?php echo $tab === 'mensajes' ? 'active' : ''; ?>">
            <a href="?tab=mensajes"><?php _e('Mensajes', 'sgep'); ?></a>
        </li>
        <li class="<?php echo $tab === 'pedidos' ? 'active' : ''; ?>">
            <a href="?tab=pedidos"><?php _e('Mis Pedidos', 'sgep'); ?></a>
        </li>
        <?php if (!$test_realizado) : ?>
        <li class="<?php echo $tab === 'test' ? 'active' : ''; ?>">
            <a href="?tab=test"><?php _e('Test de Compatibilidad', 'sgep'); ?></a>
        </li>
        <?php endif; ?>
        <!-- Botón de cierre de sesión en el menú -->
        <li class="sgep-logout-button">
            <a href="<?php echo wp_logout_url(home_url()); ?>" style="color: #d63638;">
                <?php _e('Cerrar Sesión', 'sgep'); ?>
            </a>
        </li>
    </ul>
</div>
        
        <!-- 
Busca la sección del switch case en el archivo public/templates/panel-cliente.php
Aproximadamente en la línea 90-110, añade el nuevo case para la pestaña de pedidos:
-->

<div class="sgep-panel-tab-content">
    <?php
    switch ($tab) {
        case 'perfil':
            include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/perfil.php');
            break;
            
        case 'especialistas':
            // Pasar el ID del especialista a la vista si es necesario ver un perfil específico
            if ($ver_especialista > 0) {
                $_GET['ver'] = $ver_especialista;
            }
            include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/especialistas.php');
            break;
            
        case 'citas':
            // Pasar el ID del especialista a la vista si es necesario agendar con uno específico
            if ($agendar_con > 0) {
                $_GET['agendar_con'] = $agendar_con;
            }
            include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/citas.php');
            break;
            
        case 'mensajes':
            include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/mensajes.php');
            break;
        
        case 'pedidos':
            include(SGEP_PLUGIN_DIR . 'public/templates/panel-cliente/pedidos.php');
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