<?php
/**
 * Plantilla para los metadatos de especialista en la pantalla de edición de usuario
 * 
 * Ruta: /admin/templates/user-meta-especialista.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php _e('Información del Especialista', 'sgep'); ?></h3>

<table class="form-table">
    <tr>
        <th><label for="sgep_titulo"><?php _e('Título Profesional', 'sgep'); ?></label></th>
        <td>
            <input type="text" name="sgep_titulo" id="sgep_titulo" value="<?php echo esc_attr($titulo); ?>" class="regular-text" />
            <p class="description"><?php _e('Ejemplo: Psicólogo Clínico, Terapeuta, etc.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_especialidad"><?php _e('Especialidad', 'sgep'); ?></label></th>
        <td>
            <input type="text" name="sgep_especialidad" id="sgep_especialidad" value="<?php echo esc_attr($especialidad); ?>" class="regular-text" />
            <p class="description"><?php _e('Ejemplo: Terapia Cognitivo-Conductual, Psicoanálisis, etc.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_descripcion"><?php _e('Descripción / Biografía', 'sgep'); ?></label></th>
        <td>
            <textarea name="sgep_descripcion" id="sgep_descripcion" rows="5" cols="30" class="large-text"><?php echo esc_textarea($descripcion); ?></textarea>
            <p class="description"><?php _e('Breve descripción o biografía del especialista que será visible para los clientes.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_experiencia"><?php _e('Años de Experiencia', 'sgep'); ?></label></th>
        <td>
            <input type="number" name="sgep_experiencia" id="sgep_experiencia" value="<?php echo esc_attr($experiencia); ?>" class="small-text" min="0" />
            <p class="description"><?php _e('Cantidad de años de experiencia profesional.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_precio_consulta"><?php _e('Precio de Consulta', 'sgep'); ?></label></th>
        <td>
            <input type="text" name="sgep_precio_consulta" id="sgep_precio_consulta" value="<?php echo esc_attr($precio_consulta); ?>" class="regular-text" />
            <p class="description"><?php _e('Ejemplo: $50, €40, etc.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_duracion_consulta"><?php _e('Duración de Consulta (minutos)', 'sgep'); ?></label></th>
        <td>
            <input type="number" name="sgep_duracion_consulta" id="sgep_duracion_consulta" value="<?php echo esc_attr($duracion_consulta); ?>" class="small-text" min="15" step="5" />
            <p class="description"><?php _e('Duración estándar de cada consulta en minutos.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><?php _e('Modalidades de Atención', 'sgep'); ?></th>
        <td>
            <label for="sgep_acepta_online">
                <input type="checkbox" name="sgep_acepta_online" id="sgep_acepta_online" value="1" <?php checked($acepta_online, 1); ?> />
                <?php _e('Atiende Online', 'sgep'); ?>
            </label><br>
            
            <label for="sgep_acepta_presencial">
                <input type="checkbox" name="sgep_acepta_presencial" id="sgep_acepta_presencial" value="1" <?php checked($acepta_presencial, 1); ?> />
                <?php _e('Atiende Presencial', 'sgep'); ?>
            </label>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_genero"><?php _e('Género', 'sgep'); ?></label></th>
        <td>
            <select name="sgep_genero" id="sgep_genero">
                <option value="hombre" <?php selected($genero, 'hombre'); ?>><?php _e('Hombre', 'sgep'); ?></option>
                <option value="mujer" <?php selected($genero, 'mujer'); ?>><?php _e('Mujer', 'sgep'); ?></option>
                <option value="otro" <?php selected($genero, 'otro'); ?>><?php _e('Otro', 'sgep'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_metodologias"><?php _e('Enfoque Terapéutico Principal', 'sgep'); ?></label></th>
        <td>
            <select name="sgep_metodologias" id="sgep_metodologias">
                <option value="practico" <?php selected($metodologias, 'practico'); ?>><?php _e('Práctico (ejercicios, tareas)', 'sgep'); ?></option>
                <option value="reflexivo" <?php selected($metodologias, 'reflexivo'); ?>><?php _e('Reflexivo (análisis, comprensión)', 'sgep'); ?></option>
                <option value="ambos" <?php selected($metodologias, 'ambos'); ?>><?php _e('Equilibrio entre ambos', 'sgep'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_habilidades"><?php _e('Áreas de Especialización', 'sgep'); ?></label></th>
        <td>
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
                <label>
                    <input type="checkbox" name="sgep_habilidades[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?> />
                    <?php echo esc_html($label); ?>
                </label><br>
            <?php endforeach; ?>
            <p class="description"><?php _e('Selecciona las áreas en las que te especializas.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_rating"><?php _e('Valoración (0-5)', 'sgep'); ?></label></th>
        <td>
            <input type="number" name="sgep_rating" id="sgep_rating" value="<?php echo esc_attr($rating); ?>" class="small-text" min="0" max="5" step="0.1" />
            <p class="description"><?php _e('Valoración promedio del especialista (de 0 a 5 estrellas).', 'sgep'); ?></p>
        </td>
    </tr>
</table>