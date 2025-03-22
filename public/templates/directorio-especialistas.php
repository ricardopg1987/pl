<?php
/**
 * Plantilla para el directorio de especialistas
 * 
 * Ruta: /public/templates/directorio-especialistas.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener especialidades disponibles para filtros
$especialidades_array = array();
foreach ($especialistas as $especialista) {
    $habilidades = get_user_meta($especialista->ID, 'sgep_habilidades', true);
    if (is_array($habilidades)) {
        $especialidades_array = array_merge($especialidades_array, $habilidades);
    }
}
$especialidades_array = array_unique($especialidades_array);
sort($especialidades_array);
?>

<div class="sgep-directorio-container">
    <h2><?php _e('Directorio de Especialistas', 'sgep'); ?></h2>
    
    <div class="sgep-directorio-filters">
        <form method="get" class="sgep-filter-form">
            <div class="sgep-filter-group">
                <label for="especialidad"><?php _e('Especialidad', 'sgep'); ?></label>
                <select name="especialidad" id="especialidad">
                    <option value=""><?php _e('Todas', 'sgep'); ?></option>
                    <?php foreach ($especialidades_array as $esp) : ?>
                        <option value="<?php echo esc_attr($esp); ?>" <?php selected($especialidad, $esp); ?>><?php echo esc_html($esp); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="sgep-filter-group">
                <label for="modalidad"><?php _e('Modalidad', 'sgep'); ?></label>
                <select name="modalidad" id="modalidad">
                    <option value=""><?php _e('Todas', 'sgep'); ?></option>
                    <option value="online" <?php selected($modalidad, 'online'); ?>><?php _e('Online', 'sgep'); ?></option>
                    <option value="presencial" <?php selected($modalidad, 'presencial'); ?>><?php _e('Presencial', 'sgep'); ?></option>
                </select>
            </div>
            
            <div class="sgep-filter-group">
                <label for="genero"><?php _e('Género', 'sgep'); ?></label>
                <select name="genero" id="genero">
                    <option value=""><?php _e('Todos', 'sgep'); ?></option>
                    <option value="hombre" <?php selected($genero, 'hombre'); ?>><?php _e('Hombre', 'sgep'); ?></option>
                    <option value="mujer" <?php selected($genero, 'mujer'); ?>><?php _e('Mujer', 'sgep'); ?></option>
                </select>
            </div>
            
            <div class="sgep-filter-actions">
                <button type="submit" class="sgep-button sgep-button-secondary"><?php _e('Filtrar', 'sgep'); ?></button>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="sgep-button sgep-button-text"><?php _e('Limpiar filtros', 'sgep'); ?></a>
            </div>
        </form>
    </div>
    
    <div class="sgep-directorio-results">
        <?php if (!empty($especialistas_paginados)) : ?>
            <div class="sgep-especialistas-grid">
                <?php foreach ($especialistas_paginados as $especialista) : 
                    $especialidad = get_user_meta($especialista->ID, 'sgep_especialidad', true);
                    $descripcion = get_user_meta($especialista->ID, 'sgep_descripcion', true);
                    $aceptaOnline = get_user_meta($especialista->ID, 'sgep_acepta_online', true);
                    $aceptaPresencial = get_user_meta($especialista->ID, 'sgep_acepta_presencial', true);
                    $precioConsulta = get_user_meta($especialista->ID, 'sgep_precio_consulta', true);
                    $rating = get_user_meta($especialista->ID, 'sgep_rating', true);
                    $habilidades = get_user_meta($especialista->ID, 'sgep_habilidades', true);
                ?>
                    <div class="sgep-especialista-card">
                        <div class="sgep-especialista-header">
                            <div class="sgep-especialista-avatar">
                                <?php echo get_avatar($especialista->ID, 80); ?>
                            </div>
                            
                            <?php if (!empty($rating)) : ?>
                                <div class="sgep-especialista-rating">
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
                        
                        <div class="sgep-especialista-content">
                            <h3><?php echo esc_html($especialista->display_name); ?></h3>
                            
                            <?php if (!empty($especialidad)) : ?>
                                <p class="sgep-especialista-specialty"><?php echo esc_html($especialidad); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($descripcion)) : ?>
                                <p class="sgep-especialista-description"><?php echo wp_trim_words(esc_html($descripcion), 20); ?></p>
                            <?php endif; ?>
                            
                            <div class="sgep-especialista-tags">
                                <?php if ($aceptaOnline) : ?>
                                    <span class="sgep-tag"><?php _e('Online', 'sgep'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($aceptaPresencial) : ?>
                                    <span class="sgep-tag"><?php _e('Presencial', 'sgep'); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($habilidades) && is_array($habilidades)) : ?>
                                    <?php foreach (array_slice($habilidades, 0, 2) as $habilidad) : ?>
                                        <span class="sgep-tag"><?php echo esc_html($habilidad); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($precioConsulta)) : ?>
                                <p class="sgep-especialista-price"><?php printf(__('Precio consulta: %s', 'sgep'), esc_html($precioConsulta)); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sgep-especialista-actions">
                            <a href="?ver_especialista=<?php echo $especialista->ID; ?>" class="sgep-button sgep-button-secondary"><?php _e('Ver Perfil', 'sgep'); ?></a>
                            
                            <?php if (is_user_logged_in() && (new SGEP_Roles())->is_cliente()) : ?>
                                <a href="?agendar_con=<?php echo $especialista->ID; ?>" class="sgep-button sgep-button-primary"><?php _e('Agendar Cita', 'sgep'); ?></a>
                            <?php else : ?>
                                <?php
                                $pages = get_option('sgep_pages', array());
                                $login_url = isset($pages['sgep-login']) ? get_permalink($pages['sgep-login']) : wp_login_url();
                                ?>
                                <a href="<?php echo esc_url($login_url); ?>" class="sgep-button sgep-button-primary"><?php _e('Iniciar Sesión para Agendar', 'sgep'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1) : ?>
                <div class="sgep-pagination">
                    <?php
                    $current_url = remove_query_arg('pag', add_query_arg($_GET, get_permalink()));
                    
                    // Anterior
                    if ($current_page > 1) {
                        echo '<a href="' . esc_url(add_query_arg('pag', $current_page - 1, $current_url)) . '" class="sgep-pagination-prev">&laquo; ' . __('Anterior', 'sgep') . '</a>';
                    }
                    
                    // Números de página
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i === $current_page) {
                            echo '<span class="sgep-pagination-current">' . $i . '</span>';
                        } elseif ($i === 1 || $i === $total_pages || abs($i - $current_page) <= 2) {
                            echo '<a href="' . esc_url(add_query_arg('pag', $i, $current_url)) . '">' . $i . '</a>';
                        } elseif (abs($i - $current_page) === 3) {
                            echo '<span class="sgep-pagination-dots">...</span>';
                        }
                    }
                    
                    // Siguiente
                    if ($current_page < $total_pages) {
                        echo '<a href="' . esc_url(add_query_arg('pag', $current_page + 1, $current_url)) . '" class="sgep-pagination-next">' . __('Siguiente', 'sgep') . ' &raquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p class="sgep-no-results"><?php _e('No se encontraron especialistas que coincidan con los filtros aplicados.', 'sgep'); ?></p>
        <?php endif; ?>
    </div>
</div>