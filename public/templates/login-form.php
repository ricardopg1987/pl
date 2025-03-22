<?php
/**
 * Plantilla para el formulario de login
 * 
 * Ruta: /public/templates/login-form.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sgep-login-form-container">
    <h2><?php _e('Iniciar Sesión', 'sgep'); ?></h2>
    
    <?php if (!empty($error)) : ?>
        <div class="sgep-error">
            <?php echo esc_html($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="sgep-form sgep-login-form">
        <?php wp_nonce_field('sgep_login', 'sgep_login_nonce'); ?>
        
        <div class="sgep-form-field">
            <label for="sgep_username"><?php _e('Usuario o Email', 'sgep'); ?></label>
            <input type="text" name="sgep_username" id="sgep_username" required>
        </div>
        
        <div class="sgep-form-field">
            <label for="sgep_password"><?php _e('Contraseña', 'sgep'); ?></label>
            <input type="password" name="sgep_password" id="sgep_password" required>
        </div>
        
        <div class="sgep-form-field sgep-checkbox">
            <label>
                <input type="checkbox" name="sgep_remember" id="sgep_remember">
                <?php _e('Recordarme', 'sgep'); ?>
            </label>
        </div>
        
        <div class="sgep-form-actions">
            <button type="submit" class="sgep-button"><?php _e('Iniciar Sesión', 'sgep'); ?></button>
        </div>
    </form>
    
    <div class="sgep-links">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('¿Olvidaste tu contraseña?', 'sgep'); ?></a>
        <a href="<?php echo esc_url($registro_url); ?>"><?php _e('Registrarse', 'sgep'); ?></a>
    </div>
</div>