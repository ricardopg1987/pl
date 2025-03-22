<?php
/**
 * Plantilla para el formulario de test de compatibilidad
 * 
 * Ruta: /public/templates/test-match-form.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sgep-test-container">
    <h2><?php _e('Test de Compatibilidad', 'sgep'); ?></h2>
    <p class="sgep-test-intro"><?php _e('Por favor, responde a las siguientes preguntas para ayudarnos a encontrar el especialista ideal para ti.', 'sgep'); ?></p>
    
    <form method="post" class="sgep-form sgep-test-form">
        <?php wp_nonce_field('sgep_test_submit', 'sgep_test_nonce'); ?>
        
        <?php foreach ($preguntas as $pregunta) : ?>
            <div class="sgep-test-question">
                <h3><?php echo esc_html($pregunta['pregunta']); ?></h3>
                
                <?php if ($pregunta['tipo'] === 'multiple') : ?>
                    <div class="sgep-test-options sgep-test-multiple">
                        <?php foreach ($pregunta['opciones'] as $valor => $etiqueta) : ?>
                            <label>
                                <input type="checkbox" name="sgep_question_<?php echo $pregunta['id']; ?>[]" value="<?php echo esc_attr($valor); ?>">
                                <?php echo esc_html($etiqueta); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="sgep-test-options sgep-test-radio">
                        <?php foreach ($pregunta['opciones'] as $valor => $etiqueta) : ?>
                            <label>
                                <input type="radio" name="sgep_question_<?php echo $pregunta['id']; ?>" value="<?php echo esc_attr($valor); ?>" required>
                                <?php echo esc_html($etiqueta); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="sgep-form-actions">
            <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Enviar Respuestas', 'sgep'); ?></button>
        </div>
    </form>
</div>