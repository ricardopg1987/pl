<?php
/**
 * Plantilla para la pestaña de perfil del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/perfil.php
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
// Cambio: Reemplazar "título profesional" por "conocimientos"
$conocimientos = get_user_meta($especialista_id, 'sgep_conocimientos', true);
$precio_consulta = get_user_meta($especialista_id, 'sgep_precio_consulta', true);
$duracion_consulta = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
$acepta_online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
$acepta_presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
$habilidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
$metodologias = get_user_meta($especialista_id, 'sgep_metodologias', true);
$genero = get_user_meta($especialista_id, 'sgep_genero', true);
$imagen_perfil = get_user_meta($especialista_id, 'sgep_imagen_perfil', true);
// Nuevos campos
$actividades = get_user_meta($especialista_id, 'sgep_actividades', true);
$intereses = get_user_meta($especialista_id, 'sgep_intereses', true);
$filosofia = get_user_meta($especialista_id, 'sgep_filosofia', true);

// Mensaje para almacenar resultado del procesamiento del formulario
$mensaje_perfil = '';
$redirect = false;

// Procesar envío del formulario
if (isset($_POST['sgep_perfil_nonce']) && wp_verify_nonce($_POST['sgep_perfil_nonce'], 'sgep_actualizar_perfil')) {
    // Procesar datos del formulario
    $especialidad = sanitize_text_field($_POST['sgep_especialidad']);
    $descripcion = sanitize_textarea_field($_POST['sgep_descripcion']);
    $experiencia = sanitize_text_field($_POST['sgep_experiencia']);
    // Actualizar campo de conocimientos en lugar de título
    $conocimientos = sanitize_text_field($_POST['sgep_conocimientos']);
    $precio_consulta = sanitize_text_field($_POST['sgep_precio_consulta']);
    $duracion_consulta = intval($_POST['sgep_duracion_consulta']);
    $acepta_online = isset($_POST['sgep_acepta_online']) ? 1 : 0;
    $acepta_presencial = isset($_POST['sgep_acepta_presencial']) ? 1 : 0;
    $habilidades = isset($_POST['sgep_habilidades']) ? (array) $_POST['sgep_habilidades'] : array();
    $metodologias = sanitize_text_field($_POST['sgep_metodologias']);
    $genero = sanitize_text_field($_POST['sgep_genero']);
    // Procesar nuevos campos
    $actividades = sanitize_textarea_field($_POST['sgep_actividades']);
    $intereses = sanitize_textarea_field($_POST['sgep_intereses']);
    $filosofia = sanitize_textarea_field($_POST['sgep_filosofia']);
    
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
    // Actualizar conocimientos en lugar de título
    update_user_meta($especialista_id, 'sgep_conocimientos', $conocimientos);
    update_user_meta($especialista_id, 'sgep_precio_consulta', $precio_consulta);
    update_user_meta($especialista_id, 'sgep_duracion_consulta', $duracion_consulta);
    update_user_meta($especialista_id, 'sgep_acepta_online', $acepta_online);
    update_user_meta($especialista_id, 'sgep_acepta_presencial', $acepta_presencial);
    update_user_meta($especialista_id, 'sgep_habilidades', $habilidades);
    update_user_meta($especialista_id, 'sgep_metodologias', $metodologias);
    update_user_meta($especialista_id, 'sgep_genero', $genero);
    // Actualizar nuevos campos
    update_user_meta($especialista_id, 'sgep_actividades', $actividades);
    update_user_meta($especialista_id, 'sgep_intereses', $intereses);
    update_user_meta($especialista_id, 'sgep_filosofia', $filosofia);
    
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
});
</script>

<div class="sgep-perfil-container">
    <?php if (!empty($mensaje_perfil)) : ?>
        <div class="sgep-notification">
            <?php echo esc_html($mensaje_perfil); ?>
        </div>
    <?php endif; ?>
    
    <div class="sgep-perfil-destacado">
        <div class="sgep-perfil-card">
            <div class="sgep-perfil-header">
                <div class="sgep-perfil-imagen">
                    <?php if (!empty($imagen_perfil)) : ?>
                        <img src="<?php echo esc_url($imagen_perfil); ?>" alt="<?php echo esc_attr($usuario->display_name); ?>">
                    <?php else : ?>
                        <?php echo get_avatar($especialista_id, 120); ?>
                    <?php endif; ?>
                </div>
                <div class="sgep-perfil-info">
                    <h3><?php echo esc_html($usuario->display_name); ?></h3>
                    <div class="sgep-perfil-meta">
                        <?php if (!empty($conocimientos)) : ?>
                            <span class="sgep-perfil-titulo"><?php echo esc_html($conocimientos); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($conocimientos) && !empty($especialidad)) : ?>
                            <span class="sgep-perfil-separador">|</span>
                        <?php endif; ?>
                        <?php if (!empty($especialidad)) : ?>
                            <span class="sgep-perfil-especialidad"><?php echo esc_html($especialidad); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="sgep-perfil-contacto"><?php echo esc_html($usuario->user_email); ?></p>
                </div>
            </div>
            
            <div class="sgep-perfil-stats">
                <?php if (!empty($experiencia)) : ?>
                    <div class="sgep-stat-item">
                        <div class="sgep-stat-value"><?php echo esc_html($experiencia); ?></div>
                        <div class="sgep-stat-label"><?php _e('Años de Experiencia', 'sgep'); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="sgep-stat-item">
                    <div class="sgep-stat-value">
                        <?php 
                            // Contar número de citas
                            global $wpdb;
                            $total_citas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d AND estado = 'confirmada'",
                                $especialista_id
                            ));
                            echo esc_html($total_citas);
                        ?>
                    </div>
                    <div class="sgep-stat-label"><?php _e('Consultas Realizadas', 'sgep'); ?></div>
                </div>
                
                <?php if (!empty($precio_consulta)) : ?>
                    <div class="sgep-stat-item">
                        <div class="sgep-stat-value"><?php echo esc_html($precio_consulta); ?></div>
                        <div class="sgep-stat-label"><?php _e('Precio Consulta', 'sgep'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <form method="post" class="sgep-form sgep-perfil-form" enctype="multipart/form-data">
        <?php wp_nonce_field('sgep_actualizar_perfil', 'sgep_perfil_nonce'); ?>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Imagen de Perfil', 'sgep'); ?></h4>
            
            <div class="sgep-img-upload">
                <div class="sgep-current-img">
                    <?php if (!empty($imagen_perfil)) : ?>
                        <img src="<?php echo esc_url($imagen_perfil); ?>" alt="<?php echo esc_attr($usuario->display_name); ?>">
                    <?php else : ?>
                        <?php echo get_avatar($especialista_id, 150); ?>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-upload-controls">
                    <label for="sgep_imagen_perfil" class="sgep-upload-label">
                        <span class="dashicons dashicons-camera"></span>
                        <?php _e('Seleccionar imagen', 'sgep'); ?>
                    </label>
                    <input type="file" id="sgep_imagen_perfil" name="sgep_imagen_perfil" accept="image/*" class="sgep-file-input">
                    <p class="sgep-field-description"><?php _e('Sube una imagen profesional para tu perfil. Formatos: JPG, PNG. Tamaño máximo: 2MB.', 'sgep'); ?></p>
                    
                    <div id="sgep_preview_container" class="sgep-preview-container" style="display: none;">
                        <p><?php _e('Vista previa:', 'sgep'); ?></p>
                        <img src="" id="sgep_preview_imagen" class="sgep-preview-img" alt="<?php _e('Vista previa', 'sgep'); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sgep-perfil-section">
            <h4><?php _e('Información Personal', 'sgep'); ?></h4>
            
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_conocimientos"><?php _e('Conocimientos', 'sgep'); ?></label>
                        <input type="text" id="sgep_conocimientos" name="sgep_conocimientos" value="<?php echo esc_attr($conocimientos); ?>">
                        <p class="sgep-field-description"><?php _e('Especifica tus conocimientos y formación en terapias alternativas/holísticas.', 'sgep'); ?></p>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_especialidad"><?php _e('Especialidad', 'sgep'); ?></label>
                        <input type="text" id="sgep_especialidad" name="sgep_especialidad" value="<?php echo esc_attr($especialidad); ?>">
                        <p class="sgep-field-description"><?php _e('Ejemplo: Terapia holística, Sanación energética, etc.', 'sgep'); ?></p>
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
            
            <!-- Nuevos campos para actividades, intereses y filosofía personal -->
            <div class="sgep-form-field">
                <label for="sgep_actividades"><?php _e('Actividades', 'sgep'); ?></label>
                <textarea id="sgep_actividades" name="sgep_actividades" rows="3"><?php echo esc_textarea($actividades); ?></textarea>
                <p class="sgep-field-description"><?php _e('Describe las actividades que realizas en tus sesiones o talleres.', 'sgep'); ?></p>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_intereses"><?php _e('Intereses', 'sgep'); ?></label>
                <textarea id="sgep_intereses" name="sgep_intereses" rows="3"><?php echo esc_textarea($intereses); ?></textarea>
                <p class="sgep-field-description"><?php _e('Comparte tus intereses profesionales y personales relacionados con las terapias.', 'sgep'); ?></p>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_filosofia"><?php _e('Filosofía Personal', 'sgep'); ?></label>
                <textarea id="sgep_filosofia" name="sgep_filosofia" rows="3"><?php echo esc_textarea($filosofia); ?></textarea>
                <p class="sgep-field-description"><?php _e('Describe tu filosofía y enfoque terapéutico personal.', 'sgep'); ?></p>
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
                        <div class="sgep-checkbox-group">
                            <label class="sgep-fancy-checkbox">
                                <input type="checkbox" id="sgep_acepta_online" name="sgep_acepta_online" value="1" <?php checked($acepta_online, 1); ?>>
                                <span class="sgep-checkbox-indicator"></span>
                                <?php _e('Atiendo Online', 'sgep'); ?>
                            </label>
                            
                            <label class="sgep-fancy-checkbox">
                                <input type="checkbox" id="sgep_acepta_presencial" name="sgep_acepta_presencial" value="1" <?php checked($acepta_presencial, 1); ?>>
                                <span class="sgep-checkbox-indicator"></span>
                                <?php _e('Atiendo Presencial', 'sgep'); ?>
                            </label>
                        </div>
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
            
            <div class="sgep-habilidades-grid">
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
                    'reiki' => __('Reiki', 'sgep'),
                    'acupuntura' => __('Acupuntura', 'sgep'),
                    'terapia_sonido' => __('Terapia de Sonido', 'sgep'),
                    'sanacion_energetica' => __('Sanación Energética', 'sgep'),
                    'cristales' => __('Terapia con Cristales', 'sgep'),
                    'mindfulness' => __('Mindfulness', 'sgep'),
                    'meditacion' => __('Meditación', 'sgep'),
                    'yoga' => __('Yoga', 'sgep'),
                );
                
                foreach ($habilidades_options as $value => $label) :
                    $checked = is_array($habilidades) && in_array($value, $habilidades) ? 'checked="checked"' : '';
                ?>
                    <label class="sgep-fancy-checkbox">
                        <input type="checkbox" name="sgep_habilidades[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?>>
                        <span class="sgep-checkbox-indicator"></span>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php 
                endforeach;
                ?>
            </div>
            <p class="sgep-field-description"><?php _e('Selecciona las áreas en las que te especializas.', 'sgep'); ?></p>
        </div>
        
        <div class="sgep-form-actions">
            <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
        </div>
    </form>
</div>