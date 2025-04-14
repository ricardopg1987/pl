<?php
/**
 * Plantilla para la pestaña de disponibilidad del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/disponibilidad.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global al inicio del archivo
global $wpdb;

// Obtener especialista actual
$especialista_id = get_current_user_id();

// Obtener disponibilidad actual
$disponibilidad = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sgep_disponibilidad WHERE especialista_id = %d ORDER BY dia_semana, hora_inicio",
    $especialista_id
));

// Organizar disponibilidad por día de la semana
$disponibilidad_por_dia = array();
for ($i = 0; $i < 7; $i++) {
    $disponibilidad_por_dia[$i] = array();
}

if (!empty($disponibilidad)) {
    foreach ($disponibilidad as $slot) {
        $disponibilidad_por_dia[$slot->dia_semana][] = $slot;
    }
}

// Nombres de los días de la semana
$dias_semana = array(
    __('Domingo', 'sgep'),
    __('Lunes', 'sgep'),
    __('Martes', 'sgep'),
    __('Miércoles', 'sgep'),
    __('Jueves', 'sgep'),
    __('Viernes', 'sgep'),
    __('Sábado', 'sgep')
);
?>

<div class="sgep-disponibilidad-wrapper">
    <h3><?php _e('Gestionar Disponibilidad', 'sgep'); ?></h3>
    
    <p class="sgep-disponibilidad-intro">
        <?php _e('Configura los días y horarios en los que estás disponible para atender consultas. Los clientes solo podrán agendar citas en los horarios que establezcas aquí.', 'sgep'); ?>
    </p>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'disponibilidad_actualizada') : ?>
        <div class="sgep-notification">
            <?php _e('Tu disponibilidad ha sido actualizada correctamente.', 'sgep'); ?>
        </div>
    <?php endif; ?>
    
    <div class="sgep-disponibilidad-container">
        <?php foreach ($dias_semana as $dia_index => $dia_nombre) : ?>
            <div class="sgep-disponibilidad-dia">
                <h4><?php echo esc_html($dia_nombre); ?></h4>
                
                <div class="sgep-disponibilidad-slots">
                    <?php if (!empty($disponibilidad_por_dia[$dia_index])) : ?>
                        <?php foreach ($disponibilidad_por_dia[$dia_index] as $slot) : ?>
                            <div class="sgep-disponibilidad-slot">
                                <span class="sgep-disponibilidad-horario">
                                    <?php echo esc_html(substr($slot->hora_inicio, 0, 5) . ' - ' . substr($slot->hora_fin, 0, 5)); ?>
                                </span>
                                <button type="button" class="sgep-disponibilidad-action" data-id="<?php echo esc_attr($slot->id); ?>">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="sgep-no-items"><?php _e('No hay horarios configurados', 'sgep'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-disponibilidad-add" data-dia="<?php echo esc_attr($dia_index); ?>">
                    <span><?php _e('+ Añadir horario', 'sgep'); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="sgep-disponibilidad-form" style="display: none;">
        <h3><?php _e('Añadir Horario de Disponibilidad', 'sgep'); ?></h3>
        
        <form id="sgep_disponibilidad_form" class="sgep-form">
            <div class="sgep-form-row">
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_dia_semana"><?php _e('Día de la Semana', 'sgep'); ?></label>
                        <select id="sgep_dia_semana" name="sgep_dia_semana" required>
                            <?php foreach ($dias_semana as $dia_index => $dia_nombre) : ?>
                                <option value="<?php echo esc_attr($dia_index); ?>"><?php echo esc_html($dia_nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_hora_inicio"><?php _e('Hora de Inicio', 'sgep'); ?></label>
                        <input type="time" id="sgep_hora_inicio" name="sgep_hora_inicio" required>
                    </div>
                </div>
                
                <div class="sgep-form-col">
                    <div class="sgep-form-field">
                        <label for="sgep_hora_fin"><?php _e('Hora de Fin', 'sgep'); ?></label>
                        <input type="time" id="sgep_hora_fin" name="sgep_hora_fin" required>
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-actions">
                <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Guardar Disponibilidad', 'sgep'); ?></button>
                <button type="button" id="sgep_disponibilidad_cancel" class="sgep-button sgep-button-secondary"><?php _e('Cancelar', 'sgep'); ?></button>
            </div>
        </form>
    </div>
</div>