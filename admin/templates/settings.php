<?php
/**
 * Plantilla para la configuración general del plugin
 * 
 * Ruta: /admin/templates/settings.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title"><?php _e('Configuración del Plugin', 'sgep'); ?></h1>
    </div>
    
    <div class="sgep-admin-content">
        <form method="post" action="options.php" class="sgep-admin-settings">
            <?php settings_fields('sgep_settings'); ?>
            <?php do_settings_sections('sgep_settings'); ?>
            
            <div class="sgep-settings-section">
                <h2 class="sgep-settings-title"><?php _e('Configuración de Zoom', 'sgep'); ?></h2>
                <p class="description"><?php _e('Configura las credenciales de la API de Zoom para permitir la integración con las citas online.', 'sgep'); ?></p>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('API Key', 'sgep'); ?></th>
                        <td>
                            <input type="text" name="sgep_zoom_api_key" value="<?php echo esc_attr(get_option('sgep_zoom_api_key')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Ingresa tu API Key de Zoom. La puedes obtener en tu cuenta de desarrollador de Zoom.', 'sgep'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('API Secret', 'sgep'); ?></th>
                        <td>
                            <input type="password" name="sgep_zoom_api_secret" value="<?php echo esc_attr(get_option('sgep_zoom_api_secret')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Ingresa tu API Secret de Zoom. La puedes obtener en tu cuenta de desarrollador de Zoom.', 'sgep'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="sgep-settings-section">
                <h2 class="sgep-settings-title"><?php _e('Configuración de Notificaciones', 'sgep'); ?></h2>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Notificaciones por Email', 'sgep'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sgep_email_notifications" value="1" <?php checked(get_option('sgep_email_notifications', 1), 1); ?> />
                                <?php _e('Habilitar notificaciones por email para citas y mensajes', 'sgep'); ?>
                            </label>
                            <p class="description"><?php _e('Si se habilita, los usuarios recibirán notificaciones por email cuando se agende una cita, se confirme, se cancele o reciban un mensaje.', 'sgep'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="sgep-settings-section">
                <h2 class="sgep-settings-title"><?php _e('Páginas del Plugin', 'sgep'); ?></h2>
                <p class="description"><?php _e('Estas son las páginas creadas por el plugin y los shortcodes que contienen.', 'sgep'); ?></p>
                
                <?php
                $pages = get_option('sgep_pages', array());
                
                if (!empty($pages)) :
                ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Página', 'sgep'); ?></th>
                                <th><?php _e('Shortcode', 'sgep'); ?></th>
                                <th><?php _e('Acciones', 'sgep'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $page_shortcodes = array(
                                'sgep-login' => '[sgep_login]',
                                'sgep-registro' => '[sgep_registro]',
                                'sgep-panel-especialista' => '[sgep_panel_especialista]',
                                'sgep-panel-cliente' => '[sgep_panel_cliente]',
                                'sgep-test-match' => '[sgep_test_match]',
                                'sgep-resultados-match' => '[sgep_resultados_match]',
                                'sgep-directorio-especialistas' => '[sgep_directorio_especialistas]',
                            );
                            
                            foreach ($pages as $slug => $page_id) :
                                $page = get_post($page_id);
                                
                                if (!$page) {
                                    continue;
                                }
                                
                                $shortcode = isset($page_shortcodes[$slug]) ? $page_shortcodes[$slug] : '';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($page->post_title); ?></strong><br>
                                        <small><?php echo esc_html($slug); ?></small>
                                    </td>
                                    <td><code><?php echo esc_html($shortcode); ?></code></td>
                                    <td>
                                        <a href="<?php echo get_permalink($page_id); ?>" target="_blank"><?php _e('Ver', 'sgep'); ?></a> | 
                                        <a href="<?php echo admin_url('post.php?post=' . $page_id . '&action=edit'); ?>"><?php _e('Editar', 'sgep'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e('No se encontraron páginas creadas por el plugin.', 'sgep'); ?></p>
                <?php endif; ?>
            </div>
            
            <?php submit_button(__('Guardar Cambios', 'sgep')); ?>
        </form>
    </div>
</div>