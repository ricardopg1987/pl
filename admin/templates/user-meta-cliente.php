<?php
/**
 * Plantilla para los metadatos de cliente en la pantalla de edición de usuario
 * 
 * Ruta: /admin/templates/user-meta-cliente.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php _e('Información del Cliente', 'sgep'); ?></h3>

<table class="form-table">
    <tr>
        <th><label for="sgep_telefono"><?php _e('Teléfono', 'sgep'); ?></label></th>
        <td>
            <input type="text" name="sgep_telefono" id="sgep_telefono" value="<?php echo esc_attr($telefono); ?>" class="regular-text" />
            <p class="description"><?php _e('Número de teléfono de contacto.', 'sgep'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_fecha_nacimiento"><?php _e('Fecha de Nacimiento', 'sgep'); ?></label></th>
        <td>
            <input type="date" name="sgep_fecha_nacimiento" id="sgep_fecha_nacimiento" value="<?php echo esc_attr($fecha_nacimiento); ?>" class="regular-text" />
        </td>
    </tr>
    
    <tr>
        <th><label for="sgep_intereses"><?php _e('Áreas de Interés', 'sgep'); ?></label></th>
        <td>
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
            
            foreach ($intereses_options as $value => $label) :
                $checked = is_array($intereses) && in_array($value, $intereses) ? 'checked="checked"' : '';
            ?>
                <label>
                    <input type="checkbox" name="sgep_intereses[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?> />
                    <?php echo esc_html($label); ?>
                </label><br>
            <?php endforeach; ?>
            <p class="description"><?php _e('Selecciona las áreas en las que estás interesado.', 'sgep'); ?></p>
        </td>
    </tr>
</table>