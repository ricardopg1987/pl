<?php
/**
 * Plantilla para la pestaña de perfil del panel de cliente
 * 
 * Ruta: /public/templates/panel-cliente/perfil.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del cliente
$cliente_id = get_current_user_id();
$usuario = get_userdata($cliente_id);

// Obtener meta datos
$telefono = get_user_meta($cliente_id, 'sgep_telefono', true);
$fecha_nacimiento = get_user_meta($cliente_id, 'sgep_fecha_nacimiento', true);
$intereses = get_user_meta($cliente_id, 'sgep_intereses', true);

// Mensaje para almacenar resultado del procesamiento del formulario
$mensaje_perfil = '';
$redirect = false;

// Procesar envío del formulario
if (isset($_POST['sgep_perfil_nonce']) && wp_verify_nonce($_POST['sgep_perfil_nonce'], 'sgep_actualizar_perfil')) {
    // Procesar datos del formulario
    $telefono = sanitize_text_field($_POST['sgep_telefono']);
    $fecha_nacimiento = sanitize_text_field($_POST['sgep_fecha_nacimiento']);
    $intereses = isset($_POST['sgep_intereses']) ? (array) $_POST['sgep_intereses'] : array();
    
    // Actualizar meta datos
    update_user_meta($cliente_id, 'sgep_telefono', $telefono);
    update_user_meta($cliente_id, 'sgep_fecha_nacimiento', $fecha_nacimiento);
    update_user_meta($cliente_id, 'sgep_intereses', $intereses);
    
    // Configurar mensaje de éxito
    $mensaje_perfil = __('Tu perfil ha sido actualizado correctamente.', 'sgep');
    $redirect = true;
}

// Realizar redirección usando JavaScript si es necesario
if ($redirect) {
    echo '<script>
        // Añadir mensaje al localStorage
        localStorage.setItem("sgep_perfil_mensaje", "' . esc_js($mensaje_perfil) . '");
        // Redireccionar a la misma página
        window.location.href = "?tab=perfil";
    </script>';
    
    // No ejecutar el resto del código
    return;
}

// Verificar si hay mensaje en localStorage para mostrar
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay mensaje en localStorage
    var mensaje = localStorage.getItem('sgep_perfil_mensaje');
    if (mensaje) {
        // Crear y mostrar la notificación
        var notificacion = document.createElement('div');
        notificacion.className = 'sgep-notification';
        notificacion.textContent = mensaje;
        
        // Insertar al principio del contenedor
        var contenedor = document.querySelector('.sgep-perfil-container');
        contenedor.insertBefore(notificacion, contenedor.firstChild);
        
        // Eliminar del localStorage
        localStorage.removeItem('sgep_perfil_mensaje');
        
        // Ocultar después de 5 segundos
        setTimeout(function() {
            notificacion.style.opacity = '0';
            setTimeout(function() {
                notificacion.remove();
            }, 500);
        }, 5000);
    }
});
</script>

<div class="sgep-perfil-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'perfil_actualizado') : ?>
        <div class="sgep-notification">
            <?php _e('Tu perfil ha sido actualizado correctamente.', 'sgep'); ?>
        </div>
    <?php endif; ?>
    
    <div class="sgep-perfil-header">
        <div class="sgep-perfil-avatar">
            <?php echo get_avatar($cliente_id, 100); ?>
        </div>
        <div class="sgep-perfil-info">
            <h3><?php echo esc_html($usuario->display_name); ?></h3>
            <p class="sgep-perfil-meta"><?php echo esc_html($usuario->user_email); ?></p>
        </div>
    </div>
    
    <form method="post" class="sgep-form sgep-perfil-form">
        <?php wp_nonce_field('sgep_actualizar_perfil', 'sgep_perfil_nonce'); ?>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Información Personal', 'sgep'); ?></h4>
            
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
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Áreas de Interés', 'sgep'); ?></h4>
            
            <div class="sgep-form-field">
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
                
                echo '<div class="sgep-intereses-grid">';
                foreach ($intereses_options as $value => $label) :
                    $checked = is_array($intereses) && in_array($value, $intereses) ? 'checked="checked"' : '';
                ?>
                    <label class="sgep-checkbox">
                        <input type="checkbox" name="sgep_intereses[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php 
                endforeach;
                echo '</div>';
                ?>
                <p class="sgep-field-description"><?php _e('Selecciona las áreas en las que estás interesado.', 'sgep'); ?></p>
            </div>
        </div>
        
        <div class="sgep-form-actions">
            <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
        </div>
    </form>
</div>