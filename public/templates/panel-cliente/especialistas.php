<?php
/**
 * Plantilla para la pestaña de especialistas recomendados del panel de cliente
 * 
 * Ruta: /public/templates/panel-cliente/especialistas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Declarar $wpdb como global
global $wpdb;

// Obtener cliente actual
$cliente_id = get_current_user_id();

// Verificar acción
$accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
$especialista_id = isset($_GET['ver']) ? intval($_GET['ver']) : 0;

// Si es para ver un especialista específico
if ($especialista_id > 0) {
    // Obtener datos del especialista
    $especialista = get_userdata($especialista_id);
    
    // Si no existe el especialista o no es del rol correcto
    if (!$especialista || !in_array('sgep_especialista', $especialista->roles)) {
        echo '<p class="sgep-error">' . __('Especialista no encontrado.', 'sgep') . '</p>';
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
    
    // Verificar si tenemos un match con este especialista
    $match = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_matches 
        WHERE cliente_id = %d AND especialista_id = %d 
        ORDER BY puntaje DESC LIMIT 1",
        $cliente_id, $especialista_id
    ));
    
    $compatibilidad = 0;
    if ($match) {
        $compatibilidad = round(($match->puntaje / 100) * 100);
        $compatibilidad = min(100, max(0, $compatibilidad));
    }
    ?>
    
    <div class="sgep-especialista-perfil">
        <div class="sgep-especialista-perfil-header">
            <div class="sgep-especialista-perfil-avatar">
                <?php echo get_avatar($especialista_id, 150); ?>
                
                <?php if ($match) : ?>
                    <div class="sgep-compatibilidad-badge">
                        <span><?php echo $compatibilidad; ?>%</span>
                        <span><?php _e('Compatibilidad', 'sgep'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sgep-especialista-perfil-info">
                <h3><?php echo esc_html($especialista->display_name); ?></h3>
                
                <?php if (!empty($titulo) || !empty($especialidad)) : ?>
                    <p class="sgep-especialista-perfil-subtitulo">
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
                    <div class="sgep-especialista-perfil-rating">
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
                
                <div class="sgep-especialista-perfil-tags">
                    <?php if ($acepta_online) : ?>
                        <span class="sgep-tag"><?php _e('Online', 'sgep'); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($acepta_presencial) : ?>
                        <span class="sgep-tag"><?php _e('Presencial', 'sgep'); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($metodologias)) : ?>
                        <span class="sgep-tag">
                            <?php 
                            if ($metodologias === 'practico') {
                                _e('Enfoque Práctico', 'sgep');
                            } elseif ($metodologias === 'reflexivo') {
                                _e('Enfoque Reflexivo', 'sgep');
                            } else {
                                _e('Enfoque Balanceado', 'sgep');
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="sgep-especialista-perfil-acciones">
                    <a href="?tab=citas&agendar_con=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-primary"><?php _e('Agendar Cita', 'sgep'); ?></a>
                    <a href="?tab=mensajes&accion=nuevo&cliente_id=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-secondary"><?php _e('Enviar Mensaje', 'sgep'); ?></a>
                </div>
            </div>
        </div>
        
        <?php if (!empty($descripcion)) : ?>
            <div class="sgep-especialista-perfil-seccion">
                <h4><?php _e('Biografía', 'sgep'); ?></h4>
                <div class="sgep-especialista-perfil-bio">
                    <?php echo wpautop(esc_html($descripcion)); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="sgep-especialista-perfil-seccion">
            <h4><?php _e('Información General', 'sgep'); ?></h4>
            
            <div class="sgep-especialista-perfil-grid">
                <?php if (!empty($experiencia)) : ?>
                    <div class="sgep-especialista-perfil-item">
                        <div class="sgep-especialista-perfil-item-icono sgep-icono-experiencia"></div>
                        <div class="sgep-especialista-perfil-item-contenido">
                            <h5><?php _e('Experiencia', 'sgep'); ?></h5>
                            <p><?php echo esc_html($experiencia) . ' ' . __('años', 'sgep'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($precio_consulta)) : ?>
                    <div class="sgep-especialista-perfil-item">
                        <div class="sgep-especialista-perfil-item-icono sgep-icono-precio"></div>
                        <div class="sgep-especialista-perfil-item-contenido">
                            <h5><?php _e('Precio de Consulta', 'sgep'); ?></h5>
                            <p><?php echo esc_html($precio_consulta); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($duracion_consulta)) : ?>
                    <div class="sgep-especialista-perfil-item">
                        <div class="sgep-especialista-perfil-item-icono sgep-icono-duracion"></div>
                        <div class="sgep-especialista-perfil-item-contenido">
                            <h5><?php _e('Duración de Consulta', 'sgep'); ?></h5>
                            <p><?php echo esc_html($duracion_consulta) . ' ' . __('minutos', 'sgep'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($genero)) : ?>
                    <div class="sgep-especialista-perfil-item">
                        <div class="sgep-especialista-perfil-item-icono sgep-icono-genero"></div>
                        <div class="sgep-especialista-perfil-item-contenido">
                            <h5><?php _e('Género', 'sgep'); ?></h5>
                            <p>
                                <?php 
                                if ($genero === 'hombre') {
                                    _e('Hombre', 'sgep');
                                } elseif ($genero === 'mujer') {
                                    _e('Mujer', 'sgep');
                                } else {
                                    _e('Otro', 'sgep');
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($habilidades) && is_array($habilidades)) : ?>
            <div class="sgep-especialista-perfil-seccion">
                <h4><?php _e('Áreas de Especialización', 'sgep'); ?></h4>
                
                <div class="sgep-especialista-perfil-tags">
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
                            echo '<span class="sgep-tag">' . esc_html($habilidades_options[$habilidad]) . '</span>';
                        } else {
                            echo '<span class="sgep-tag">' . esc_html($habilidad) . '</span>';
                        }
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="sgep-especialista-perfil-footer">
            <a href="?tab=especialistas" class="sgep-button sgep-button-text"><?php _e('Volver a Especialistas Recomendados', 'sgep'); ?></a>
        </div>
    </div>
    
    <?php
} else {
    // Listado de especialistas recomendados
    
    // Obtener matches del cliente
    $matches = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as especialista_nombre 
        FROM {$wpdb->prefix}sgep_matches m
        LEFT JOIN {$wpdb->users} u ON m.especialista_id = u.ID
        WHERE m.cliente_id = %d
        ORDER BY m.puntaje DESC",
        $cliente_id
    ));
    ?>
    
    <div class="sgep-especialistas-wrapper">
        <h3><?php _e('Especialistas Recomendados', 'sgep'); ?></h3>
        
        <p class="sgep-especialistas-intro">
            <?php _e('Basado en tus respuestas al test de compatibilidad, estos son los especialistas que mejor se adaptan a tus necesidades.', 'sgep'); ?>
        </p>
        
        <div class="sgep-especialistas-recomendados">
            <?php if (!empty($matches)) : ?>
                <?php foreach ($matches as $match) : 
                    $especialista_id = $match->especialista_id;
                    $especialidad = get_user_meta($especialista_id, 'sgep_especialidad', true);
                    $precio_consulta = get_user_meta($especialista_id, 'sgep_precio_consulta', true);
                    $acepta_online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
                    $acepta_presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
                    $rating = get_user_meta($especialista_id, 'sgep_rating', true);
                    
                    // Calcular porcentaje de compatibilidad
                    $compatibilidad = round(($match->puntaje / 100) * 100);
                    $compatibilidad = min(100, max(0, $compatibilidad));
                ?>
                    <div class="sgep-especialista-recomendado-card">
                        <div class="sgep-especialista-recomendado-avatar">
                            <?php echo get_avatar($especialista_id, 80); ?>
                            
                            <div class="sgep-compatibilidad-badge">
                                <span><?php echo $compatibilidad; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="sgep-especialista-recomendado-info">
                            <h4><?php echo esc_html($match->especialista_nombre); ?></h4>
                            
                            <?php if (!empty($especialidad)) : ?>
                                <p class="sgep-especialista-recomendado-especialidad"><?php echo esc_html($especialidad); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($rating)) : ?>
                                <div class="sgep-especialista-recomendado-rating">
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
                                </div>
                            <?php endif; ?>
                            
                            <div class="sgep-especialista-recomendado-tags">
                                <?php if ($acepta_online) : ?>
                                    <span class="sgep-tag"><?php _e('Online', 'sgep'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($acepta_presencial) : ?>
                                    <span class="sgep-tag"><?php _e('Presencial', 'sgep'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($precio_consulta)) : ?>
                                <p class="sgep-especialista-recomendado-precio"><?php echo esc_html($precio_consulta); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sgep-especialista-recomendado-actions">
                           <a href="?tab=especialistas&ver=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-sm sgep-button-secondary"><?php _e('Ver perfil', 'sgep'); ?></a>
                           <a href="?tab=citas&agendar_con=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Agendar', 'sgep'); ?></a>
                       </div>
                   </div>
               <?php endforeach; ?>
           <?php else : ?>
               <p class="sgep-no-items"><?php _e('No se encontraron especialistas recomendados. Por favor, realiza el test de compatibilidad.', 'sgep'); ?></p>
           <?php endif; ?>
       </div>
       
       <?php
       // Mostrar enlace al directorio si existe la página
       $pages = get_option('sgep_pages', array());
       if (isset($pages['sgep-directorio-especialistas'])) :
           $directorio_url = get_permalink($pages['sgep-directorio-especialistas']);
       ?>
           <div class="sgep-especialistas-footer">
               <a href="<?php echo esc_url($directorio_url); ?>" class="sgep-button sgep-button-outline"><?php _e('Ver todos los especialistas', 'sgep'); ?></a>
           </div>
       <?php endif; ?>
   </div>
   <?php
}