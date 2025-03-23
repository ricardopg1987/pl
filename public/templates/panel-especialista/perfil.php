<?php
/**
 * Plantilla para la pestaña de perfil del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/perfil.php
 * Versión con estilo original pero con capacidad de carga de imágenes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del especialista
$especialista_id = get_current_user_id();
$usuario = get_userdata($especialista_id);

// Obtener meta datos
$especialidad = get_user_meta($especialista_id, 'sgep_especialidad', true);
$descripcion = get_user_meta($especialista_id, 'sgep_descripcion', true);
$experiencia = get_user_meta($especialista_id, 'sgep_experiencia', true);
$titulo = get_user_meta($especialista_id, 'sgep_titulo', true);
$precio_consulta = get_user_meta($especialista_id, 'sgep_precio_consulta', true);
$duracion_consulta = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
$acepta_online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
$acepta_presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
$habilidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
$metodologias = get_user_meta($especialista_id, 'sgep_metodologias', true);
$genero = get_user_meta($especialista_id, 'sgep_genero', true);
$imagen_perfil = get_user_meta($especialista_id, 'sgep_imagen_perfil', true);

// Mensaje para almacenar resultado del procesamiento del formulario
$mensaje_perfil = '';
$redirect = false;

// Procesar envío del formulario
if (isset($_POST['sgep_perfil_nonce']) && wp_verify_nonce($_POST['sgep_perfil_nonce'], 'sgep_actualizar_perfil')) {
    // Procesar datos del formulario
    $especialidad = sanitize_text_field($_POST['sgep_especialidad']);
    $descripcion = sanitize_textarea_field($_POST['sgep_descripcion']);
    $experiencia = sanitize_text_field($_POST['sgep_experiencia']);
    $titulo = sanitize_text_field($_POST['sgep_titulo']);
    $precio_consulta = sanitize_text_field($_POST['sgep_precio_consulta']);
    $duracion_consulta = intval($_POST['sgep_duracion_consulta']);
    $acepta_online = isset($_POST['sgep_acepta_online']) ? 1 : 0;
    $acepta_presencial = isset($_POST['sgep_acepta_presencial']) ? 1 : 0;
    $habilidades = isset($_POST['sgep_habilidades']) ? (array) $_POST['sgep_habilidades'] : array();
    $metodologias = sanitize_text_field($_POST['sgep_metodologias']);
    $genero = sanitize_text_field($_POST['sgep_genero']);
    
    // Manejo de la imagen de perfil
    if (!empty($_FILES['sgep_imagen_perfil']['name'])) {
        // Requerir los archivos de WordPress necesarios para la carga de imágenes
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Manejar la carga del archivo
        $attachment_id = media_handle_upload('sgep_imagen_perfil', 0);
        
        if (is_wp_error($attachment_id)) {
            $mensaje_perfil = __('Error al subir la imagen: ', 'sgep') . $attachment_id->get_error_message();
        } else {
            // Eliminar imagen anterior si existe
            $imagen_anterior_id = get_user_meta($especialista_id, 'sgep_imagen_perfil_id', true);
            if ($imagen_anterior_id) {
                wp_delete_attachment($imagen_anterior_id, true);
            }
            
            // Guardar ID de la nueva imagen
            update_user_meta($especialista_id, 'sgep_imagen_perfil_id', $attachment_id);
            
            // Obtener URL de la imagen
            $imagen_url = wp_get_attachment_url($attachment_id);
            update_user_meta($especialista_id, 'sgep_imagen_perfil', $imagen_url);
            $imagen_perfil = $imagen_url;
        }
    }
    
    // Actualizar meta datos
    update_user_meta($especialista_id, 'sgep_especialidad', $especialidad);
    update_user_meta($especialista_id, 'sgep_descripcion', $descripcion);
    update_user_meta($especialista_id, 'sgep_experiencia', $experiencia);
    update_user_meta($especialista_id, 'sgep_titulo', $titulo);
    update_user_meta($especialista_id, 'sgep_precio_consulta', $precio_consulta);
    update_user_meta($especialista_id, 'sgep_duracion_consulta', $duracion_consulta);
    update_user_meta($especialista_id, 'sgep_acepta_online', $acepta_online);
    update_user_meta($especialista_id, 'sgep_acepta_presencial', $acepta_presencial);
    update_user_meta($especialista_id, 'sgep_habilidades', $habilidades);
    update_user_meta($especialista_id, 'sgep_metodologias', $metodologias);
    update_user_meta($especialista_id, 'sgep_genero', $genero);
    
    // Configurar mensaje de éxito
    if (empty($mensaje_perfil)) {
        $mensaje_perfil = __('Tu perfil ha sido actualizado correctamente.', 'sgep');
        $redirect = true;
    }
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
    
    // Vista previa de la imagen
    var inputImagen = document.getElementById('sgep_imagen_perfil');
    var previewImagen = document.getElementById('sgep_preview_imagen');
    var previewContainer = document.getElementById('sgep_preview_container');
    
    if (inputImagen && previewImagen) {
        inputImagen.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImagen.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<div class="sgep-perfil-container">
    <?php if (!empty($mensaje_perfil)) : ?>
        <div class="sgep-notification">
            <?php echo esc_html($mensaje_perfil); ?>
        </div>
    <?php endif; ?>
    
    <div class="sgep-perfil-header">
        <div class="sgep-perfil-avatar">
            <?php if (!empty($imagen_perfil)) : ?>
                <img src="<?php echo esc_url($imagen_perfil); ?>" alt="<?php echo esc_attr($usuario->display_name); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
            <?php else : ?>
                <?php echo get_avatar($especialista_id, 100); ?>
            <?php endif; ?>
        </div>
        <div class="sgep-perfil-info">
            <h3><?php echo esc_html($usuario->display_name); ?></h3>
            <p class="sgep-perfil-meta"><?php echo esc_html($usuario->user_email); ?></p>
        </div>
    </div>
    
    <form method="post" class="sgep-form sgep-perfil-form" enctype="multipart/form-data">
        <?php wp_nonce_field('sgep_actualizar_perfil', 'sgep_perfil_nonce'); ?>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Imagen de Perfil', 'sgep'); ?></h4>
            
            <div class="sgep-form-field">
                <label for="sgep_imagen_perfil"><?php _e('Seleccionar imagen', 'sgep'); ?></label>
                <input type="file" id="sgep_imagen_perfil" name="sgep_imagen_perfil" accept="image/*">
                <p class="sgep-field-description"><?php _e('Sube una imagen profesional para tu perfil. Formatos: JPG, PNG. Tamaño máximo: 2MB.', 'sgep'); ?></p>
                
                <div id="sgep_preview_container" style="display: none; margin-top: 10px;">
                    <p><?php _e('Vista previa:', 'sgep'); ?></p>
                    <img src="" id="sgep_preview_imagen" alt="<?php _e('Vista previa', 'sgep'); ?>" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
        </div>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Información Personal', 'sgep'); ?></h4>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_titulo"><?php _e('Título Profesional', 'sgep'); ?></label>
                        <input type="text" id="sgep_titulo" name="sgep_titulo" value="<?php echo esc_attr($titulo); ?>">
                        <p class="sgep-field-description"><?php _e('Ejemplo: Psicólogo Clínico, Terapeuta, etc.', 'sgep'); ?></p>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_especialidad"><?php _e('Especialidad', 'sgep'); ?></label>
                        <input type="text" id="sgep_especialidad" name="sgep_especialidad" value="<?php echo esc_attr($especialidad); ?>">
                        <p class="sgep-field-description"><?php _e('Ejemplo: Terapia Cognitivo-Conductual, Psicoanálisis, etc.', 'sgep'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_genero"><?php _e('Género', 'sgep'); ?></label>
                        <select id="sgep_genero" name="sgep_genero">
                            <option value="hombre" <?php selected($genero, 'hombre'); ?>><?php _e('Hombre', 'sgep'); ?></option>
                            <option value="mujer" <?php selected($genero, 'mujer'); ?>><?php _e('Mujer', 'sgep'); ?></option>
                            <option value="otro" <?php selected($genero, 'otro'); ?>><?php _e('Otro', 'sgep'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_experiencia"><?php _e('Años de Experiencia', 'sgep'); ?></label>
                        <input type="number" id="sgep_experiencia" name="sgep_experiencia" value="<?php echo esc_attr($experiencia); ?>" min="0">
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_descripcion"><?php _e('Biografía', 'sgep'); ?></label>
                <textarea id="sgep_descripcion" name="sgep_descripcion" rows="5"><?php echo esc_textarea($descripcion); ?></textarea>
                <p class="sgep-field-description"><?php _e('Breve descripción o biografía que será visible para los clientes.', 'sgep'); ?></p>
            </div>
        </div>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Configuración de Consultas', 'sgep'); ?></h4>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_precio_consulta"><?php _e('Precio de Consulta', 'sgep'); ?></label>
                        <input type="text" id="sgep_precio_consulta" name="sgep_precio_consulta" value="<?php echo esc_attr($precio_consulta); ?>">
                        <p class="sgep-field-description"><?php _e('Ejemplo: $50, €40, etc.', 'sgep'); ?></p>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_duracion_consulta"><?php _e('Duración de Consulta (minutos)', 'sgep'); ?></label>
                        <input type="number" id="sgep_duracion_consulta" name="sgep_duracion_consulta" value="<?php echo esc_attr($duracion_consulta); ?>" min="15" step="5">
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label><?php _e('Modalidades de Atención', 'sgep'); ?></label>
                        <label class="sgep-checkbox">
                            <input type="checkbox" id="sgep_acepta_online" name="sgep_acepta_online" value="1" <?php checked($acepta_online, 1); ?>>
                            <?php _e('Atiendo Online', 'sgep'); ?>
                        </label>
                        <label class="sgep-checkbox">
                            <input type="checkbox" id="sgep_acepta_presencial" name="sgep_acepta_presencial" value="1" <?php checked($acepta_presencial, 1); ?>>
                            <?php _e('Atiendo Presencial', 'sgep'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_metodologias"><?php _e('Enfoque Terapéutico Principal', 'sgep'); ?></label>
                        <select id="sgep_metodologias" name="sgep_metodologias">
                            <option value="practico" <?php selected($metodologias, 'practico'); ?>><?php _e('Práctico (ejercicios, tareas)', 'sgep'); ?></option>
                            <option value="reflexivo" <?php selected($metodologias, 'reflexivo'); ?>><?php _e('Reflexivo (análisis, comprensión)', 'sgep'); ?></option>
                            <option value="ambos" <?php selected($metodologias, 'ambos'); ?>><?php _e('Equilibrio entre ambos', 'sgep'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Áreas de Especialización', 'sgep'); ?></h4>
            
            <div class="sgep-form-field">
                <?php
                $habilidades_options = array(
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
                    'infantil' => __('Psicología Infantil', 'sgep'),
                    'adolescentes' => __('Psicología de Adolescentes', 'sgep'),
                );
                
                foreach ($habilidades_options as $value => $label) :
                    $checked = is_array($habilidades) && in_array($value, $habilidades) ? 'checked="checked"' : '';
                ?>
                    <label class="sgep-checkbox">
                        <input type="checkbox" name="sgep_habilidades[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php 
                endforeach;
                ?>
                <p class="sgep-field-description"><?php _e('Selecciona las áreas en las que te especializas.', 'sgep'); ?></p>
            </div>
        </div>
        
        <div class="sgep-form-actions">
            <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
        </div>
    </form>
</div>

<style>
/* Estilos mínimos para vista previa de imagen */
#sgep_preview_container {
    margin-top: 10px;
    padding: 10px;
    border: 1px dashed #ddd;
    background-color: #f9f9f9;
    border-radius: 4px;
}
#sgep_preview_imagen {
    max-width: 200px;
    max-height: 200px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Mejoras para las checkboxes de habilidades */
.sgep-checkbox {
    display: block;
    margin-bottom: 8px;
}

/* Asegurar que las imágenes se muestren correctamente */
.sgep-perfil-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
}
</style>