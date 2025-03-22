<?php
/**
 * Clase para manejar la desactivación del plugin
 * 
 * Ruta: /includes/class-sgep-deactivator.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SGEP_Deactivator {
    
    /**
     * Método que se ejecuta cuando se desactiva el plugin
     */
    public static function deactivate() {
        // No eliminamos los roles ni las tablas en la desactivación
        // solo limpiamos las reglas de reescritura
        flush_rewrite_rules();
        
        // Desregistrar eventos cron
        wp_clear_scheduled_hook('sgep_daily_notifications');
    }
}