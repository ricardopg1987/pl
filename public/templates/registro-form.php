<?php
/**
 * Plantilla para el formulario de registro
 * 
 * Ruta: /public/templates/registro-form.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sgep-registro-form-container">
    <h2><?php _e('Crear una cuenta', 'sgep'); ?></h2>
    
    <?php if (!empty($error)) : ?>
        <div class="sgep-error">
            <?php echo esc_html($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)) : ?>
        <div class="sgep-success">
            <?php echo esc_html($success); ?>
            <p><a href="<?php echo esc_url($login_url); ?>"><?php _e('Iniciar Sesión', 'sgep'); ?></a></p>
        </div>
    <?php else : ?>
        <form method="post" class="sgep-form sgep-registro-form">
            <?php wp_nonce_field('sgep_registro', 'sgep_registro_nonce'); ?>
            
            <div class="sgep-form-field">
                <label for="sgep_username"><?php _e('Nombre de Usuario', 'sgep'); ?></label>
                <input type="text" name="sgep_username" id="sgep_username" required value="<?php echo isset($_POST['sgep_username']) ? esc_attr($_POST['sgep_username']) : ''; ?>">
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_email"><?php _e('Email', 'sgep'); ?></label>
                <input type="email" name="sgep_email" id="sgep_email" required value="<?php echo isset($_POST['sgep_email']) ? esc_attr($_POST['sgep_email']) : ''; ?>">
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_password"><?php _e('Contraseña', 'sgep'); ?></label>
                <input type="password" name="sgep_password" id="sgep_password" required>
                <p class="sgep-field-description"><?php _e('La contraseña debe tener al menos 8 caracteres.', 'sgep'); ?></p>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_password_confirm"><?php _e('Confirmar Contraseña', 'sgep'); ?></label>
                <input type="password" name="sgep_password_confirm" id="sgep_password_confirm" required>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_role"><?php _e('Tipo de Cuenta', 'sgep'); ?></label>
                <select name="sgep_role" id="sgep_role" required>
                    <option value=""><?php _e('-- Selecciona una opción --', 'sgep'); ?></option>
                    <option value="cliente" <?php selected(isset($_POST['sgep_role']) && $_POST['sgep_role'] === 'cliente'); ?>><?php _e('Cliente / Paciente', 'sgep'); ?></option>
                    <option value="especialista" <?php selected(isset($_POST['sgep_role']) && $_POST['sgep_role'] === 'especialista'); ?>><?php _e('Especialista', 'sgep'); ?></option>
                </select>
            </div>
            
            <div class="sgep-form-actions">
                <button type="submit" class="sgep-button"><?php _e('Registrarse', 'sgep'); ?></button>
            </div>
        </form>
        
        <div class="sgep-links">
            <p><?php _e('¿Ya tienes una cuenta?', 'sgep'); ?> <a href="<?php echo esc_url($login_url); ?>"><?php _e('Iniciar Sesión', 'sgep'); ?></a></p>
        </div>
    <?php endif; ?>
</div>