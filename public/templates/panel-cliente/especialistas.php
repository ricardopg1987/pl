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
// Verificar si viene de un enlace "Ver Perfil" desde los resultados del test
$ver_especialista = isset($_GET['ver']) ? intval($_GET['ver']) : 0;
if ($ver_especialista > 0) {
    $especialista_id = $ver_especialista;
}

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
    $conocimientos = get_user_meta($especialista_id, 'sgep_conocimientos', true);
    $precio_consulta = get_user_meta($especialista_id, 'sgep_precio_consulta', true);
    $duracion_consulta = get_user_meta($especialista_id, 'sgep_duracion_consulta', true);
    $acepta_online = get_user_meta($especialista_id, 'sgep_acepta_online', true);
    $acepta_presencial = get_user_meta($especialista_id, 'sgep_acepta_presencial', true);
    $habilidades = get_user_meta($especialista_id, 'sgep_habilidades', true);
    $metodologias = get_user_meta($especialista_id, 'sgep_metodologias', true);
    $genero = get_user_meta($especialista_id, 'sgep_genero', true);
    $rating = get_user_meta($especialista_id, 'sgep_rating', true);
    $imagen_perfil = get_user_meta($especialista_id, 'sgep_imagen_perfil', true);
    
    // Nuevos campos
    $actividades = get_user_meta($especialista_id, 'sgep_actividades', true);
    $intereses = get_user_meta($especialista_id, 'sgep_intereses', true);
    $filosofia = get_user_meta($especialista_id, 'sgep_filosofia', true);
    
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
    
    // Obtener productos del especialista
    $productos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_productos 
         WHERE especialista_id = %d 
         ORDER BY nombre ASC",
        $especialista_id
    ));
    
    // Obtener mensajes entre el cliente y el especialista (últimos 5)
    $mensajes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sgep_mensajes 
         WHERE (remitente_id = %d AND destinatario_id = %d) 
         OR (remitente_id = %d AND destinatario_id = %d) 
         ORDER BY created_at DESC LIMIT 5",
        $cliente_id, $especialista_id, $especialista_id, $cliente_id
    ));
    
    // Vista de pestañas para organizar la información
    $tab_perfil = isset($_GET['tab_perfil']) ? sanitize_text_field($_GET['tab_perfil']) : 'info';
    ?>
    
    <div class="sgep-especialista-perfil">
        <div class="sgep-especialista-perfil-header">
            <div class="sgep-especialista-perfil-avatar">
                <?php if (!empty($imagen_perfil)) : ?>
                    <img src="<?php echo esc_url($imagen_perfil); ?>" alt="<?php echo esc_attr($especialista->display_name); ?>">
                <?php else : ?>
                    <?php echo get_avatar($especialista_id, 150); ?>
                <?php endif; ?>
                
                <?php if ($match) : ?>
                    <div class="sgep-compatibilidad-badge">
                        <span><?php echo $compatibilidad; ?>%</span>
                        <span><?php _e('Compatibilidad', 'sgep'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sgep-especialista-perfil-info">
                <h3><?php echo esc_html($especialista->display_name); ?></h3>
                
                <?php if (!empty($conocimientos) || !empty($especialidad)) : ?>
                    <p class="sgep-especialista-perfil-subtitulo">
                        <?php 
                        if (!empty($conocimientos)) {
                            echo esc_html($conocimientos);
                        }
                        if (!empty($conocimientos) && !empty($especialidad)) {
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
                    <a href="?tab=mensajes&accion=nuevo&especialista_id=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-secondary"><?php _e('Enviar Mensaje', 'sgep'); ?></a>
                </div>
            </div>
        </div>
        
        <!-- Pestañas de navegación -->
        <div class="sgep-perfil-tabs">
            <ul class="sgep-perfil-tabs-nav">
                <li class="<?php echo $tab_perfil === 'info' ? 'active' : ''; ?>">
                    <a href="?tab=especialistas&ver=<?php echo $especialista_id; ?>&tab_perfil=info"><?php _e('Información', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab_perfil === 'productos' ? 'active' : ''; ?>">
                    <a href="?tab=especialistas&ver=<?php echo $especialista_id; ?>&tab_perfil=productos"><?php _e('Productos', 'sgep'); ?></a>
                </li>
                <li class="<?php echo $tab_perfil === 'mensajes' ? 'active' : ''; ?>">
                    <a href="?tab=especialistas&ver=<?php echo $especialista_id; ?>&tab_perfil=mensajes"><?php _e('Mensajes', 'sgep'); ?></a>
                </li>
            </ul>
        </div>
        
        <div class="sgep-perfil-tabs-content">
            <?php if ($tab_perfil === 'info') : ?>
                <!-- Información del especialista -->
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
                
                <?php if (!empty($filosofia)) : ?>
                    <div class="sgep-especialista-perfil-seccion">
                        <h4><?php _e('Filosofía Personal', 'sgep'); ?></h4>
                        <div class="sgep-especialista-perfil-bio">
                            <?php echo wpautop(esc_html($filosofia)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($actividades)) : ?>
                    <div class="sgep-especialista-perfil-seccion">
                        <h4><?php _e('Actividades', 'sgep'); ?></h4>
                        <div class="sgep-especialista-perfil-bio">
                            <?php echo wpautop(esc_html($actividades)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($intereses)) : ?>
                    <div class="sgep-especialista-perfil-seccion">
                        <h4><?php _e('Intereses', 'sgep'); ?></h4>
                        <div class="sgep-especialista-perfil-bio">
                            <?php echo wpautop(esc_html($intereses)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
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
                                'reiki' => __('Reiki', 'sgep'),
                                'acupuntura' => __('Acupuntura', 'sgep'),
                                'terapia_sonido' => __('Terapia de Sonido', 'sgep'),
                                'sanacion_energetica' => __('Sanación Energética', 'sgep'),
                                'cristales' => __('Terapia con Cristales', 'sgep'),
                                'mindfulness' => __('Mindfulness', 'sgep'),
                                'meditacion' => __('Meditación', 'sgep'),
                                'yoga' => __('Yoga', 'sgep'),
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
            
            <?php elseif ($tab_perfil === 'productos') : ?>
                <!-- Productos del especialista -->
                <div class="sgep-especialista-perfil-seccion">
                    <h4><?php _e('Productos y Servicios', 'sgep'); ?></h4>
                    
                    <?php if (!empty($productos)) : ?>
                        <div class="sgep-productos-grid">
                            <?php foreach ($productos as $producto) : ?>
                                <div class="sgep-producto-card">
                                    <div class="sgep-producto-imagen">
                                        <?php if (!empty($producto->imagen_url)) : ?>
                                            <img src="<?php echo esc_url($producto->imagen_url); ?>" alt="<?php echo esc_attr($producto->nombre); ?>">
                                        <?php else : ?>
                                            <div class="sgep-producto-no-imagen">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="sgep-producto-detalles">
                                        <h5 class="sgep-producto-nombre"><?php echo esc_html($producto->nombre); ?></h5>
                                        
                                        <?php if (!empty($producto->categoria)) : ?>
                                            <span class="sgep-producto-categoria">
                                                <?php 
                                                $categorias = array(
                                                    'libros' => __('Libros', 'sgep'),
                                                    'cursos' => __('Cursos', 'sgep'),
                                                    'accesorios' => __('Accesorios', 'sgep'),
                                                    'esencias' => __('Esencias', 'sgep'),
                                                    'terapias' => __('Terapias', 'sgep'),
                                                    'aceites' => __('Aceites', 'sgep'),
                                                    'cristales' => __('Cristales', 'sgep'),
                                                    'otros' => __('Otros', 'sgep'),
                                                );
                                                
                                                echo isset($categorias[$producto->categoria]) ? 
                                                    esc_html($categorias[$producto->categoria]) : 
                                                    esc_html($producto->categoria);
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($producto->descripcion)) : ?>
                                            <p class="sgep-producto-descripcion">
                                                <?php echo wp_trim_words(esc_html($producto->descripcion), 20); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="sgep-producto-precio">
                                            <?php echo esc_html($producto->precio); ?>
                                        </div>
                                        
                                        <?php if ($producto->stock > 0 || $producto->stock == 0) : // 0 = ilimitado ?>
                                            <div class="sgep-producto-acciones">
                                                <a href="#" class="sgep-button sgep-button-secondary sgep-comprar-producto" 
                                                   data-id="<?php echo esc_attr($producto->id); ?>"
                                                   data-nombre="<?php echo esc_attr($producto->nombre); ?>"
                                                   data-precio="<?php echo esc_attr($producto->precio); ?>">
                                                    <?php _e('Comprar', 'sgep'); ?>
                                                </a>
                                                <a href="#" class="sgep-button sgep-button-text sgep-producto-detalles-btn" 
                                                   data-id="<?php echo esc_attr($producto->id); ?>">
                                                    <?php _e('Ver detalles', 'sgep'); ?>
                                                </a>
                                            </div>
                                        <?php else : ?>
                                            <div class="sgep-producto-agotado">
                                                <?php _e('Agotado', 'sgep'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="sgep-no-items"><?php _e('Este especialista no tiene productos disponibles actualmente.', 'sgep'); ?></p>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($tab_perfil === 'mensajes') : ?>
                <!-- Historial de mensajes -->
                <div class="sgep-especialista-perfil-seccion">
                    <h4><?php _e('Historial de Mensajes', 'sgep'); ?></h4>
                    
                    <?php if (!empty($mensajes)) : ?>
                        <div class="sgep-mensajes-historial">
                            <?php foreach ($mensajes as $mensaje) : 
                                $fecha = new DateTime($mensaje->created_at);
                                $es_mio = $mensaje->remitente_id == $cliente_id;
                            ?>
                                <div class="sgep-mensaje-item <?php echo $es_mio ? 'sgep-mensaje-mio' : 'sgep-mensaje-otro'; ?>">
                                    <div class="sgep-mensaje-cabecera">
                                        <span class="sgep-mensaje-remitente">
                                            <?php echo $es_mio ? __('Tú', 'sgep') : esc_html($especialista->display_name); ?>
                                        </span>
                                        <span class="sgep-mensaje-fecha">
                                            <?php echo esc_html($fecha->format('d/m/Y H:i')); ?>
                                        </span>
                                    </div>
                                    <div class="sgep-mensaje-contenido">
                                        <h5 class="sgep-mensaje-asunto"><?php echo esc_html($mensaje->asunto); ?></h5>
                                        <div class="sgep-mensaje-texto">
                                            <?php echo wpautop(esc_html($mensaje->mensaje)); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="sgep-mensaje-ver-todos">
                            <a href="?tab=mensajes" class="sgep-button sgep-button-secondary">
                                <?php _e('Ver todos los mensajes', 'sgep'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <p class="sgep-no-items"><?php _e('No tienes mensajes con este especialista.', 'sgep'); ?></p>
                        
                        <div class="sgep-mensaje-nuevo">
                            <a href="?tab=mensajes&accion=nuevo&especialista_id=<?php echo $especialista_id; ?>" class="sgep-button sgep-button-primary">
                                <?php _e('Enviar un mensaje', 'sgep'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulario rápido para enviar mensaje -->
                    <div class="sgep-mensaje-rapido">
                        <h4><?php _e('Enviar un mensaje rápido', 'sgep'); ?></h4>
                        <form id="sgep_mensaje_rapido_form" class="sgep-form">
                            <input type="hidden" id="sgep_destinatario_id" name="sgep_destinatario_id" value="<?php echo esc_attr($especialista_id); ?>">
                            
                            <div class="sgep-form-field">
                                <label for="sgep_asunto"><?php _e('Asunto', 'sgep'); ?></label>
                                <input type="text" id="sgep_asunto" name="sgep_asunto" required>
                            </div>
                            
                            <div class="sgep-form-field">
                                <label for="sgep_mensaje"><?php _e('Mensaje', 'sgep'); ?></label>
                                <textarea id="sgep_mensaje" name="sgep_mensaje" rows="4" required></textarea>
                            </div>
                            
                            <div class="sgep-form-actions">
                                <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Enviar Mensaje', 'sgep'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sgep-especialista-perfil-footer">
            <a href="?tab=especialistas" class="sgep-button sgep-button-text"><?php _e('Volver a Especialistas Recomendados', 'sgep'); ?></a>
        </div>
    </div>
    
    <!-- Modal para detalles de producto -->
    <div id="sgep-producto-modal" class="sgep-modal" style="display: none;">
        <div class="sgep-modal-content">
            <span class="sgep-modal-close">&times;</span>
            <div id="sgep-producto-modal-contenido"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Manejo del modal de producto
        $('.sgep-producto-detalles-btn').on('click', function(e) {
            e.preventDefault();
            var productoId = $(this).data('id');
            
            // Cargar detalles del producto con AJAX
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'sgep_obtener_producto_detalles',
                    nonce: sgep_ajax.nonce,
                    producto_id: productoId
                },
                success: function(response) {
                    if (response.success) {
                        $('#sgep-producto-modal-contenido').html(response.data.html);
                        $('#sgep-producto-modal').fadeIn(300);
                    } else {
                        alert(response.data);
                    }
                }
            });
        });
        
        // Cerrar modal
        $(document).on('click', '.sgep-modal-close', function() {
            $('#sgep-producto-modal').fadeOut(300);
        });
        
        // Cerrar al hacer clic fuera del modal
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('sgep-modal')) {
                $('.sgep-modal').fadeOut(300);
            }
        });
        
        // Manejar envío de mensaje rápido
        $('#sgep_mensaje_rapido_form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var destinatarioId = $('#sgep_destinatario_id').val();
            var asunto = $('#sgep_asunto').val();
            var mensaje = $('#sgep_mensaje').val();
            
            // Validar campos
            if (!asunto || !mensaje) {
                alert('<?php _e('Por favor completa todos los campos', 'sgep'); ?>');
                return;
            }
            
            // Deshabilitar botón mientras se procesa
            btn.prop('disabled', true).text('<?php _e('Enviando...', 'sgep'); ?>');
            
            // Enviar mensaje por AJAX
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_enviar_mensaje',
                    nonce: sgep_ajax.nonce,
                    destinatario_id: destinatarioId,
                    asunto: asunto,
                    mensaje: mensaje
                },
                success: function(response) {
                    if (response.success) {
                        // Limpiar formulario
                        form[0].reset();
                        
                        // Mostrar mensaje de éxito
                        alert(response.data.message);
                        
                        // Recargar la página para mostrar el nuevo mensaje
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('Error al enviar el mensaje. Por favor, intenta nuevamente.', 'sgep'); ?>');
                },
                complete: function() {
                    btn.prop('disabled', false).text('<?php _e('Enviar Mensaje', 'sgep'); ?>');
                }
            });
        });
        
        // Manejar compra de producto
        $('.sgep-comprar-producto').on('click', function(e) {
            e.preventDefault();
            var productoId = $(this).data('id');
            var productoNombre = $(this).data('nombre');
            var productoPrecio = $(this).data('precio');
            
            if (confirm('<?php _e('¿Deseas comprar el producto', 'sgep'); ?> "' + productoNombre + '" <?php _e('por', 'sgep'); ?> ' + productoPrecio + '?')) {
                // Implementar lógica de compra
                $.ajax({
                    url: sgep_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sgep_comprar_producto',
                        nonce: sgep_ajax.nonce,
                        producto_id: productoId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error al procesar la compra. Por favor, intenta nuevamente.', 'sgep'); ?>');
                    }
                });
            }
        });
    });
    </script>
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
                    $imagen_perfil = get_user_meta($especialista_id, 'sgep_imagen_perfil', true);
                    
                    // Calcular porcentaje de compatibilidad
                    $compatibilidad = round(($match->puntaje / 100) * 100);
                    $compatibilidad = min(100, max(0, $compatibilidad));
                ?>
                    <div class="sgep-especialista-recomendado-card">
                        <div class="sgep-especialista-recomendado-avatar">
                            <?php if (!empty($imagen_perfil)) : ?>
                                <img src="<?php echo esc_url($imagen_perfil); ?>" alt="<?php echo esc_attr($match->especialista_nombre); ?>">
                            <?php else : ?>
                                <?php echo get_avatar($especialista_id, 80); ?>
                            <?php endif; ?>
                            
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
                            
                            <?php
                            // Contar el número de productos del especialista
                            $num_productos = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}sgep_productos WHERE especialista_id = %d",
                                $especialista_id
                            ));
                            
                            if ($num_productos > 0) : ?>
                                <div class="sgep-especialista-productos-badge">
                                    <span><?php echo sprintf(_n('%d producto', '%d productos', $num_productos, 'sgep'), $num_productos); ?></span>
                                </div>
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