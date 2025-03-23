<?php
/**
 * Plantilla para los resultados del test de compatibilidad
 * 
 * Ruta: /public/templates/resultados-match.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener páginas
$pages = get_option('sgep_pages', array());
$directorio_url = isset($pages['sgep-directorio-especialistas']) ? get_permalink($pages['sgep-directorio-especialistas']) : '#';
$panel_cliente_url = isset($pages['sgep-panel-cliente']) ? get_permalink($pages['sgep-panel-cliente']) : '#';
?>

<div class="sgep-resultados-match-container">
    <h2><?php _e('Resultados del Test de Compatibilidad', 'sgep'); ?></h2>
    
    <div class="sgep-resultados-intro">
        <p><?php _e('Hemos encontrado los siguientes especialistas que mejor se adaptan a tus necesidades:', 'sgep'); ?></p>
    </div>
    
    <div class="sgep-matches-list">
        <?php if (!empty($matches)) : ?>
            <?php foreach ($matches as $index => $match) : 
                $especialista = get_userdata($match->especialista_id);
                
                if (!$especialista) {
                    continue;
                }
                
                $especialidad = get_user_meta($match->especialista_id, 'sgep_especialidad', true);
                $descripcion = get_user_meta($match->especialista_id, 'sgep_descripcion', true);
                $aceptaOnline = get_user_meta($match->especialista_id, 'sgep_acepta_online', true);
                $aceptaPresencial = get_user_meta($match->especialista_id, 'sgep_acepta_presencial', true);
                $precioConsulta = get_user_meta($match->especialista_id, 'sgep_precio_consulta', true);
                $habilidades = get_user_meta($match->especialista_id, 'sgep_habilidades', true);
                
                // Calcular porcentaje de compatibilidad (base sobre 100)
                $compatibilidad = round(($match->puntaje / 100) * 100);
                $compatibilidad = min(100, max(0, $compatibilidad)); // Asegurar que esté entre 0 y 100
                
                // Determinar clase de posición
                $positionClass = '';
                if ($index === 0) {
                    $positionClass = 'sgep-match-first';
                } elseif ($index === 1) {
                    $positionClass = 'sgep-match-second';
                } elseif ($index === 2) {
                    $positionClass = 'sgep-match-third';
                }
                ?>
                
                <div class="sgep-match-item <?php echo $positionClass; ?>">
                    <div class="sgep-match-header">
                        <?php if ($index === 0) : ?>
                            <div class="sgep-match-badge"><?php _e('Mejor Match', 'sgep'); ?></div>
                        <?php endif; ?>
                        
                        <div class="sgep-match-compatibility">
                            <div class="sgep-compatibility-circle" data-percentage="<?php echo $compatibilidad; ?>">
                                <span><?php echo $compatibilidad; ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sgep-match-content">
                        <div class="sgep-match-avatar">
                            <?php echo get_avatar($match->especialista_id, 96); ?>
                        </div>
                        
                        <div class="sgep-match-details">
                            <h3><?php echo esc_html($especialista->display_name); ?></h3>
                            
                            <?php if (!empty($especialidad)) : ?>
                                <p class="sgep-match-specialty"><?php echo esc_html($especialidad); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($descripcion)) : ?>
                                <p class="sgep-match-description"><?php echo wp_trim_words(esc_html($descripcion), 20); ?></p>
                            <?php endif; ?>
                            
                            <div class="sgep-match-tags">
                                <?php if ($aceptaOnline) : ?>
                                    <span class="sgep-tag"><?php _e('Online', 'sgep'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($aceptaPresencial) : ?>
                                    <span class="sgep-tag"><?php _e('Presencial', 'sgep'); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($habilidades) && is_array($habilidades)) : ?>
                                    <?php foreach (array_slice($habilidades, 0, 3) as $habilidad) : ?>
                                        <span class="sgep-tag"><?php echo esc_html($habilidad); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($precioConsulta)) : ?>
                                <p class="sgep-match-price"><?php printf(__('Precio consulta: %s', 'sgep'), esc_html($precioConsulta)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sgep-match-actions">
                        <?php
                        // Enlaces correctos con rutas completas
                        $ver_perfil_url = add_query_arg('ver', $match->especialista_id, $panel_cliente_url . '?tab=especialistas');
                        $agendar_cita_url = add_query_arg('agendar_con', $match->especialista_id, $panel_cliente_url . '?tab=citas');
                        ?>
                        <a href="<?php echo esc_url($ver_perfil_url); ?>" class="sgep-button sgep-button-secondary"><?php _e('Ver Perfil', 'sgep'); ?></a>
                        <a href="<?php echo esc_url($agendar_cita_url); ?>" class="sgep-button sgep-button-primary"><?php _e('Agendar Cita', 'sgep'); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="sgep-no-results"><?php _e('No se encontraron especialistas que coincidan con tus preferencias.', 'sgep'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="sgep-resultados-footer">
        <p><?php _e('También puedes explorar nuestro directorio completo de especialistas:', 'sgep'); ?></p>
        <a href="<?php echo esc_url($directorio_url); ?>" class="sgep-button sgep-button-outline"><?php _e('Ver todos los especialistas', 'sgep'); ?></a>
    </div>
</div>