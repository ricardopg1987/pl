<?php
/**
 * Plantilla para la gestión de especialistas
 * 
 * Ruta: /admin/templates/especialistas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar acción
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$especialista_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si es para ver o editar un especialista específico
if (($action === 'view' || $action === 'edit') && $especialista_id > 0) {
    $especialista = get_userdata($especialista_id);
    
    // Si no existe el especialista o no es del rol correcto
    if (!$especialista || !in_array('sgep_especialista', $especialista->roles)) {
        echo '<div class="error"><p>' . __('Especialista no encontrado.', 'sgep') . '</p></div>';
        return;
    }
    
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
    $rating = get_user_meta($especialista_id, 'sgep_rating', true);
    
    // Si es acción de editar y se envió el formulario
    if ($action === 'edit' && isset($_POST['sgep_edit_especialista_nonce']) && wp_verify_nonce($_POST['sgep_edit_especialista_nonce'], 'sgep_edit_especialista')) {
        // Actualizar datos del especialista
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
        $rating = floatval($_POST['sgep_rating']);
        
        // Guardar meta datos
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
        update_user_meta($especialista_id, 'sgep_rating', $rating);
        
        // Mostrar mensaje de éxito
        echo '<div class="updated"><p>' . __('Especialista actualizado con éxito.', 'sgep') . '</p></div>';
    }
    
    // Mostrar formulario o vista de detalle
    ?>
    <div class="wrap sgep-admin-container">
        <div class="sgep-admin-header">
            <h1 class="sgep-admin-title"><?php echo $action === 'edit' ? __('Editar Especialista', 'sgep') : __('Detalles del Especialista', 'sgep'); ?></h1>
            <div class="sgep-admin-actions">
                <?php if ($action === 'view') : ?>
                    <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=edit&id=' . $especialista_id); ?>" class="button button-primary"><?php _e('Editar', 'sgep'); ?></a>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=sgep-especialistas'); ?>" class="button"><?php _e('Volver al listado', 'sgep'); ?></a>
            </div>
        </div>
        
        <div class="sgep-admin-content">
            <?php if ($action === 'edit') : ?>
                <!-- Formulario de edición -->
                <form method="post" class="sgep-admin-form">
                    <?php wp_nonce_field('sgep_edit_especialista', 'sgep_edit_especialista_nonce'); ?>
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Información Personal', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-row">
                            <div class="sgep-form-col">
                                <div class="sgep-form-field">
                                    <label for="sgep_titulo"><?php _e('Título Profesional', 'sgep'); ?></label>
                                    <input type="text" id="sgep_titulo" name="sgep_titulo" value="<?php echo esc_attr($titulo); ?>">
                                </div>
                            </div>
                            
                            <div class="sgep-form-col">
                                <div class="sgep-form-field">
                                    <label for="sgep_especialidad"><?php _e('Especialidad', 'sgep'); ?></label>
                                    <input type="text" id="sgep_especialidad" name="sgep_especialidad" value="<?php echo esc_attr($especialidad); ?>">
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
                            <label for="sgep_descripcion"><?php _e('Descripción / Biografía', 'sgep'); ?></label>
                            <textarea id="sgep_descripcion" name="sgep_descripcion" rows="5"><?php echo esc_textarea($descripcion); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Configuración de Consultas', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-row">
                            <div class="sgep-form-col">
                                <div class="sgep-form-field">
                                    <label for="sgep_precio_consulta"><?php _e('Precio de Consulta', 'sgep'); ?></label>
                                    <input type="text" id="sgep_precio_consulta" name="sgep_precio_consulta" value="<?php echo esc_attr($precio_consulta); ?>">
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
                                        <?php _e('Atiende Online', 'sgep'); ?>
                                    </label>
                                    <label class="sgep-checkbox">
                                        <input type="checkbox" id="sgep_acepta_presencial" name="sgep_acepta_presencial" value="1" <?php checked($acepta_presencial, 1); ?>>
                                        <?php _e('Atiende Presencial', 'sgep'); ?>
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
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Áreas de Especialización', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-field">
                            <label for="sgep_habilidades"><?php _e('Áreas de Especialización', 'sgep'); ?></label>
                            <select id="sgep_habilidades" name="sgep_habilidades[]" class="sgep-habilidades-select" multiple>
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
                                
                                foreach ($habilidades_options as $value => $label) {
                                    $selected = is_array($habilidades) && in_array($value, $habilidades) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Selecciona las áreas en las que te especializas.', 'sgep'); ?></p>
                        </div>
                    </div>
                    
                    <div class="sgep-form-section">
                        <h2 class="sgep-form-section-title"><?php _e('Valoración', 'sgep'); ?></h2>
                        
                        <div class="sgep-form-field">
                            <label for="sgep_rating"><?php _e('Valoración (0-5)', 'sgep'); ?></label>
                            <input type="number" id="sgep_rating" name="sgep_rating" value="<?php echo esc_attr($rating); ?>" min="0" max="5" step="0.1">
                            <p class="description"><?php _e('Valoración promedio del especialista (de 0 a 5 estrellas).', 'sgep'); ?></p>
                        </div>
                    </div>
                    
                    <div class="sgep-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
                        <a href="<?php echo admin_url('admin.php?page=sgep-especialistas'); ?>" class="button"><?php _e('Cancelar', 'sgep'); ?></a>
                    </div>
                </form>
            <?php else : ?>
                <!-- Vista de detalle -->
                <div class="sgep-admin-view">
                    <div class="sgep-admin-view-header">
                        <div class="sgep-admin-view-avatar">
                            <?php echo get_avatar($especialista_id, 100); ?>
                        </div>
                        <div class="sgep-admin-view-title">
                            <h2><?php echo esc_html($especialista->display_name); ?></h2>
                            <?php if (!empty($titulo) || !empty($especialidad)) : ?>
                                <p class="sgep-admin-view-subtitle">
                                    <?php 
                                    if (!empty($titulo)) {
                                        echo esc_html($titulo);
                                    }
                                    if (!empty($titulo) && !empty($especialidad)) {
                                        echo ' - ';
                                    }
                                    if (!empty($especialidad)) {
                                        echo esc_html($especialidad);
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($rating)) : ?>
                                <div class="sgep-admin-view-rating">
                                    <?php
                                    $rating_value = floatval($rating);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating_value) {
                                            echo '<span class="sgep-star sgep-star-full">★</span>';
                                        } elseif ($i - 0.5 <= $rating_value) {
                                            echo '<span class="sgep-star sgep-star-half">★</span>';
                                        } else {
                                            echo '<span class="sgep-star sgep-star-empty">☆</span>';
                                        }
                                    }
                                    ?>
                                    <span class="sgep-rating-value"><?php echo number_format($rating_value, 1); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sgep-admin-view-content">
                        <div class="sgep-admin-view-section">
                            <h3><?php _e('Información Personal', 'sgep'); ?></h3>
                            
                            <table class="sgep-admin-view-table">
                                <tr>
                                    <th><?php _e('Email', 'sgep'); ?></th>
                                    <td><?php echo esc_html($especialista->user_email); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Fecha de Registro', 'sgep'); ?></th>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($especialista->user_registered)); ?></td>
                                </tr>
                                <?php if (!empty($genero)) : ?>
                                    <tr>
                                        <th><?php _e('Género', 'sgep'); ?></th>
                                        <td>
                                            <?php 
                                            if ($genero === 'hombre') {
                                                _e('Hombre', 'sgep');
                                            } elseif ($genero === 'mujer') {
                                                _e('Mujer', 'sgep');
                                            } else {
                                                _e('Otro', 'sgep');
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($experiencia)) : ?>
                                    <tr>
                                        <th><?php _e('Años de Experiencia', 'sgep'); ?></th>
                                        <td><?php echo esc_html($experiencia); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <?php if (!empty($descripcion)) : ?>
                            <div class="sgep-admin-view-section">
                                <h3><?php _e('Biografía', 'sgep'); ?></h3>
                                <div class="sgep-admin-view-bio">
                                    <?php echo wpautop(esc_html($descripcion)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sgep-admin-view-section">
                            <h3><?php _e('Configuración de Consultas', 'sgep'); ?></h3>
                            
                            <table class="sgep-admin-view-table">
                                <?php if (!empty($precio_consulta)) : ?>
                                    <tr>
                                        <th><?php _e('Precio de Consulta', 'sgep'); ?></th>
                                        <td><?php echo esc_html($precio_consulta); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($duracion_consulta)) : ?>
                                    <tr>
                                        <th><?php _e('Duración de Consulta', 'sgep'); ?></th>
                                        <td><?php echo esc_html($duracion_consulta) . ' ' . __('minutos', 'sgep'); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th><?php _e('Modalidades de Atención', 'sgep'); ?></th>
                                    <td>
                                        <?php
                                        $modalidades = array();
                                        if ($acepta_online) {
                                            $modalidades[] = __('Online', 'sgep');
                                        }
                                        if ($acepta_presencial) {
                                            $modalidades[] = __('Presencial', 'sgep');
                                        }
                                        
                                        if (!empty($modalidades)) {
                                            echo esc_html(implode(', ', $modalidades));
                                        } else {
                                            _e('No especificado', 'sgep');
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php if (!empty($metodologias)) : ?>
                                    <tr>
                                        <th><?php _e('Enfoque Terapéutico', 'sgep'); ?></th>
                                        <td>
                                            <?php 
                                            if ($metodologias === 'practico') {
                                                _e('Práctico (ejercicios, tareas)', 'sgep');
                                            } elseif ($metodologias === 'reflexivo') {
                                                _e('Reflexivo (análisis, comprensión)', 'sgep');
                                            } else {
                                                _e('Equilibrio entre ambos', 'sgep');
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <?php if (!empty($habilidades) && is_array($habilidades)) : ?>
                            <div class="sgep-admin-view-section">
                                <h3><?php _e('Áreas de Especialización', 'sgep'); ?></h3>
                                <div class="sgep-admin-view-tags">
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
                                    
                                    foreach ($habilidades as $habilidad) {
                                        if (isset($habilidades_options[$habilidad])) {
                                            echo '<span class="sgep-admin-tag">' . esc_html($habilidades_options[$habilidad]) . '</span>';
                                        } else {
                                            echo '<span class="sgep-admin-tag">' . esc_html($habilidad) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sgep-admin-view-section">
                            <h3><?php _e('Estadísticas', 'sgep'); ?></h3>
                            
                            <?php
                            // Obtener estadísticas
                            global $wpdb;
                            
                            $total_citas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d",
                                $especialista_id
                            ));
                            
                            $citas_confirmadas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d AND estado = 'confirmada'",
                                $especialista_id
                            ));
                            
                            $citas_pendientes = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d AND estado = 'pendiente'",
                                $especialista_id
                            ));
                            
                            $citas_canceladas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_citas WHERE especialista_id = %d AND estado = 'cancelada'",
                                $especialista_id
                            ));
                            
                            $total_matches = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_matches WHERE especialista_id = %d",
                                $especialista_id
                            ));
                            ?>
                            
                            <table class="sgep-admin-view-table">
                                <tr>
                                    <th><?php _e('Total de Citas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($total_citas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Confirmadas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_confirmadas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Pendientes', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_pendientes); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Citas Canceladas', 'sgep'); ?></th>
                                    <td><?php echo esc_html($citas_canceladas); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Total de Matches', 'sgep'); ?></th>
                                    <td><?php echo esc_html($total_matches); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
} else {
    // Listado de especialistas
    ?>
    <div class="wrap sgep-admin-container">
        <div class="sgep-admin-header">
            <h1 class="sgep-admin-title"><?php _e('Gestión de Especialistas', 'sgep'); ?></h1>
            <div class="sgep-admin-actions">
                <a href="<?php echo admin_url('user-new.php?role=sgep_especialista'); ?>" class="button button-primary"><?php _e('Añadir Nuevo Especialista', 'sgep'); ?></a>
            </div>
        </div>
        
        <div class="sgep-admin-content">
            <?php if (!empty($especialistas)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'sgep'); ?></th>
                            <th><?php _e('Avatar', 'sgep'); ?></th>
                            <th><?php _e('Nombre', 'sgep'); ?></th>
                            <th><?php _e('Email', 'sgep'); ?></th>
                            <th><?php _e('Especialidad', 'sgep'); ?></th>
                            <th><?php _e('Rating', 'sgep'); ?></th>
                            <th><?php _e('Modalidad', 'sgep'); ?></th>
                            <th><?php _e('Fecha Registro', 'sgep'); ?></th>
                            <th><?php _e('Acciones', 'sgep'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($especialistas as $especialista) : 
                            $especialidad = get_user_meta($especialista->ID, 'sgep_especialidad', true);
                            $rating = get_user_meta($especialista->ID, 'sgep_rating', true);
                            $acepta_online = get_user_meta($especialista->ID, 'sgep_acepta_online', true);
                            $acepta_presencial = get_user_meta($especialista->ID, 'sgep_acepta_presencial', true);
                        ?>
                            <tr>
                                <td><?php echo esc_html($especialista->ID); ?></td>
                                <td><?php echo get_avatar($especialista->ID, 32); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=view&id=' . $especialista->ID); ?>">
                                            <?php echo esc_html($especialista->display_name); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($especialista->user_email); ?></td>
                                <td><?php echo !empty($especialidad) ? esc_html($especialidad) : '-'; ?></td>
                                <td>
                                    <?php
                                    if (!empty($rating)) {
                                        $rating_value = floatval($rating);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating_value) {
                                                echo '<span class="sgep-star sgep-star-full">★</span>';
                                            } elseif ($i - 0.5 <= $rating_value) {
                                                echo '<span class="sgep-star sgep-star-half">★</span>';
                                            } else {
                                                echo '<span class="sgep-star sgep-star-empty">☆</span>';
                                            }
                                        }
                                        echo ' <span class="sgep-rating-value">(' . number_format($rating_value, 1) . ')</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $modalidades = array();
                                    if ($acepta_online) {
                                        $modalidades[] = __('Online', 'sgep');
                                    }
                                    if ($acepta_presencial) {
                                        $modalidades[] = __('Presencial', 'sgep');
                                    }
                                    
                                    if (!empty($modalidades)) {
                                        echo esc_html(implode(', ', $modalidades));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($especialista->user_registered)); ?></td>
                                <td class="sgep-table-actions">
                                    <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=view&id=' . $especialista->ID); ?>" class="sgep-action-button"><?php _e('Ver', 'sgep'); ?></a>
                                    <a href="<?php echo admin_url('admin.php?page=sgep-especialistas&action=edit&id=' . $especialista->ID); ?>" class="sgep-action-button"><?php _e('Editar', 'sgep'); ?></a>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $especialista->ID); ?>" class="sgep-action-button"><?php _e('Editar Usuario', 'sgep'); ?></a>
                                    <a href="#" class="sgep-action-button sgep-action-delete sgep-delete-especialista" data-id="<?php echo $especialista->ID; ?>"><?php _e('Eliminar', 'sgep'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No hay especialistas registrados.', 'sgep'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}