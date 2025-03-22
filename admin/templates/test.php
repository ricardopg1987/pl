<?php
/**
 * Plantilla para la configuración del test de compatibilidad
 * 
 * Ruta: /admin/templates/test.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title"><?php _e('Configuración del Test de Compatibilidad', 'sgep'); ?></h1>
    </div>
    
    <div class="sgep-admin-content">
        <?php if (isset($mensaje)) : ?>
            <div class="updated"><p><?php echo esc_html($mensaje); ?></p></div>
        <?php endif; ?>
        
        <form method="post" action="" class="sgep-admin-form">
            <?php wp_nonce_field('sgep_save_test_questions', 'sgep_test_questions_nonce'); ?>
            
            <p><?php _e('Configura las preguntas del test de compatibilidad que se mostrará a los clientes. Estas preguntas ayudarán a realizar el match con los especialistas más adecuados.', 'sgep'); ?></p>
            
            <div class="sgep-test-questions" data-next-id="<?php echo count($preguntas) + 1; ?>">
                <?php if (!empty($preguntas)) : ?>
                    <?php foreach ($preguntas as $index => $pregunta) : ?>
                        <div class="sgep-test-question" data-id="<?php echo esc_attr($pregunta['id']); ?>">
                            <div class="sgep-test-question-header">
                                <h3 class="sgep-test-question-title"><?php printf(__('Pregunta %d', 'sgep'), $index + 1); ?></h3>
                                <div class="sgep-test-question-actions">
                                    <a href="#" class="sgep-delete-question button button-small button-link-delete"><?php _e('Eliminar', 'sgep'); ?></a>
                                </div>
                            </div>
                            
                            <div class="sgep-test-question-content">
                                <div class="sgep-form-field">
                                    <label for="sgep_test_questions[<?php echo $index; ?>][pregunta]"><?php _e('Texto de la pregunta', 'sgep'); ?></label>
                                    <input type="text" name="sgep_test_questions[<?php echo $index; ?>][pregunta]" value="<?php echo esc_attr($pregunta['pregunta']); ?>" class="regular-text" required>
                                    <input type="hidden" name="sgep_test_questions[<?php echo $index; ?>][id]" value="<?php echo esc_attr($pregunta['id']); ?>">
                                </div>
                                
                                <div class="sgep-form-field">
                                    <label for="sgep_test_questions[<?php echo $index; ?>][tipo]"><?php _e('Tipo de pregunta', 'sgep'); ?></label>
                                    <select name="sgep_test_questions[<?php echo $index; ?>][tipo]" class="sgep-question-type">
                                        <option value="radio" <?php selected($pregunta['tipo'], 'radio'); ?>><?php _e('Selección única', 'sgep'); ?></option>
                                        <option value="multiple" <?php selected($pregunta['tipo'], 'multiple'); ?>><?php _e('Selección múltiple', 'sgep'); ?></option>
                                    </select>
                                    <p class="description sgep-option-description">
                                        <?php 
                                        if ($pregunta['tipo'] === 'multiple') {
                                            _e('Los usuarios podrán seleccionar múltiples opciones.', 'sgep');
                                        } else {
                                            _e('Los usuarios solo podrán seleccionar una opción.', 'sgep');
                                        }
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="sgep-test-question-options">
                                    <h4><?php _e('Opciones de respuesta', 'sgep'); ?></h4>
                                    
                                    <?php if (!empty($pregunta['opciones']) && is_array($pregunta['opciones'])) : ?>
                                        <?php foreach ($pregunta['opciones'] as $key => $value) : ?>
                                            <div class="sgep-test-option">
                                                <div class="sgep-test-option-key">
                                                    <input type="text" name="sgep_test_questions[<?php echo $index; ?>][opciones][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" placeholder="<?php esc_attr_e('Texto de la opción', 'sgep'); ?>" required>
                                                </div>
                                                <a href="#" class="sgep-delete-option button button-small button-link-delete"><?php _e('Eliminar', 'sgep'); ?></a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="sgep-test-add-option">
                                    <a href="#" class="button button-secondary"><?php _e('Añadir Opción', 'sgep'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="sgep-test-actions">
                <button type="button" id="sgep-add-question" class="button button-secondary"><?php _e('Añadir Pregunta', 'sgep'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Guardar Cambios', 'sgep'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Plantilla para nueva pregunta -->
<script type="text/html" id="sgep-question-template">
    <div class="sgep-test-question" data-id="{id}">
        <div class="sgep-test-question-header">
            <h3 class="sgep-test-question-title"><?php _e('Nueva Pregunta', 'sgep'); ?></h3>
            <div class="sgep-test-question-actions">
                <a href="#" class="sgep-delete-question button button-small button-link-delete"><?php _e('Eliminar', 'sgep'); ?></a>
            </div>
        </div>
        
        <div class="sgep-test-question-content">
            <div class="sgep-form-field">
                <label for="sgep_test_questions[{id}][pregunta]"><?php _e('Texto de la pregunta', 'sgep'); ?></label>
                <input type="text" name="sgep_test_questions[{id}][pregunta]" class="regular-text" required>
                <input type="hidden" name="sgep_test_questions[{id}][id]" value="{id}">
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_test_questions[{id}][tipo]"><?php _e('Tipo de pregunta', 'sgep'); ?></label>
                <select name="sgep_test_questions[{id}][tipo]" class="sgep-question-type">
                    <option value="radio"><?php _e('Selección única', 'sgep'); ?></option>
                    <option value="multiple"><?php _e('Selección múltiple', 'sgep'); ?></option>
                </select>
                <p class="description sgep-option-description"><?php _e('Los usuarios solo podrán seleccionar una opción.', 'sgep'); ?></p>
            </div>
            
            <div class="sgep-test-question-options">
                <h4><?php _e('Opciones de respuesta', 'sgep'); ?></h4>
            </div>
            
            <div class="sgep-test-add-option">
                <a href="#" class="button button-secondary"><?php _e('Añadir Opción', 'sgep'); ?></a>
            </div>
        </div>
    </div>
</script>

<!-- Plantilla para nueva opción -->
<script type="text/html" id="sgep-option-template">
    <div class="sgep-test-option">
        <div class="sgep-test-option-key">
            <input type="text" name="sgep_test_questions[{question_id}][opciones][option_<?php echo time(); ?>_<?php echo mt_rand(); ?>]" placeholder="<?php esc_attr_e('Texto de la opción', 'sgep'); ?>" required>
        </div>
        <a href="#" class="sgep-delete-option button button-small button-link-delete"><?php _e('Eliminar', 'sgep'); ?></a>
    </div>
</script>