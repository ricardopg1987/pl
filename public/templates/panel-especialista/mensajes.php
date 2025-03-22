<?php
/**
 * Plantilla para la pestaña de mensajes del panel de especialista
 * 
 * Ruta: /public/templates/panel-especialista/mensajes.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener usuario actual
$especialista_id = get_current_user_id();

// Verificar acción
$accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
$mensaje_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$destinatario_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Si es para ver un mensaje específico
if ($accion === 'ver' && $mensaje_id > 0) {
    // Obtener el mensaje
    global $wpdb;
    $mensaje = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, 
            r.display_name as remitente_nombre, r.user_email as remitente_email,
            d.display_name as destinatario_nombre, d.user_email as destinatario_email
        FROM {$wpdb->prefix}sgep_mensajes m
        LEFT JOIN {$wpdb->users} r ON m.remitente_id = r.ID
        LEFT JOIN {$wpdb->users} d ON m.destinatario_id = d.ID
        WHERE m.id = %d AND (m.remitente_id = %d OR m.destinatario_id = %d)",
        $mensaje_id, $especialista_id, $especialista_id
    ));
    
    if (!$mensaje) {
        echo '<p class="sgep-error">' . __('El mensaje no existe o no tienes permisos para verlo.', 'sgep') . '</p>';
        return;
    }
    
    // Si soy destinatario y no está leído, marcarlo como leído
    if ($mensaje->destinatario_id == $especialista_id && !$mensaje->leido) {
        $wpdb->update(
            $wpdb->prefix . 'sgep_mensajes',
            array('leido' => 1),
            array('id' => $mensaje_id)
        );
    }
    
    // Fecha de creación
    $fecha_creacion = new DateTime($mensaje->created_at);
    
    // Determinar con quién estoy hablando para poder responder
    $interlocutor_id = $mensaje->remitente_id == $especialista_id ? $mensaje->destinatario_id : $mensaje->remitente_id;
    $interlocutor_nombre = $mensaje->remitente_id == $especialista_id ? $mensaje->destinatario_nombre : $mensaje->remitente_nombre;
    ?>
    
    <div class="sgep-mensaje-detail">
        <div class="sgep-mensaje-detail-header">
            <h3><?php echo esc_html($mensaje->asunto); ?></h3>
            
            <div class="sgep-mensaje-meta">
                <?php if ($mensaje->remitente_id == $especialista_id) : ?>
                    <span><?php printf(__('Para: %s', 'sgep'), esc_html($mensaje->destinatario_nombre)); ?></span>
                <?php else : ?>
                    <span><?php printf(__('De: %s', 'sgep'), esc_html($mensaje->remitente_nombre)); ?></span>
                <?php endif; ?>
                
                <span><?php echo esc_html($fecha_creacion->format('d/m/Y H:i')); ?></span>
            </div>
        </div>
        
        <div class="sgep-mensaje-content">
            <?php echo wpautop(esc_html($mensaje->mensaje)); ?>
        </div>
        
        <div class="sgep-mensaje-reply">
            <h4><?php printf(__('Responder a %s', 'sgep'), esc_html($interlocutor_nombre)); ?></h4>
            
            <form id="sgep_enviar_mensaje_form" class="sgep-form">
                <input type="hidden" id="sgep_destinatario_id" name="sgep_destinatario_id" value="<?php echo esc_attr($interlocutor_id); ?>">
                
                <div class="sgep-form-field">
                    <label for="sgep_asunto"><?php _e('Asunto', 'sgep'); ?></label>
                    <input type="text" id="sgep_asunto" name="sgep_asunto" value="<?php echo esc_attr('Re: ' . $mensaje->asunto); ?>" required>
                </div>
                
                <div class="sgep-form-field">
                    <label for="sgep_mensaje"><?php _e('Mensaje', 'sgep'); ?></label>
                    <textarea id="sgep_mensaje" name="sgep_mensaje" rows="5" required></textarea>
                </div>
                
                <div class="sgep-form-actions">
                    <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Enviar Respuesta', 'sgep'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="sgep-mensaje-footer">
            <a href="?tab=mensajes" class="sgep-button sgep-button-text"><?php _e('Volver a Mensajes', 'sgep'); ?></a>
        </div>
    </div>
    
    <?php
} elseif ($accion === 'nuevo') {
    // Formulario para enviar nuevo mensaje
    global $wpdb;
    
    // Si se especificó un destinatario, obtener sus datos
    $destinatario_nombre = '';
    if ($destinatario_id > 0) {
        $destinatario = get_userdata($destinatario_id);
        if ($destinatario && in_array('sgep_cliente', $destinatario->roles)) {
            $destinatario_nombre = $destinatario->display_name;
        } else {
            $destinatario_id = 0;
        }
    }
    
    // Si no hay destinatario específico, obtener todos los clientes
    $clientes = array();
    if ($destinatario_id <= 0) {
        // Obtener clientes con los que he tenido citas
        $clientes_citas = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.cliente_id, u.display_name 
            FROM {$wpdb->prefix}sgep_citas c
            LEFT JOIN {$wpdb->users} u ON c.cliente_id = u.ID
            WHERE c.especialista_id = %d
            ORDER BY u.display_name",
            $especialista_id
        ));
        
        foreach ($clientes_citas as $cliente) {
            $clientes[$cliente->cliente_id] = $cliente->display_name;
        }
    }
    ?>
    
    <div class="sgep-nuevo-mensaje">
        <h3><?php _e('Nuevo Mensaje', 'sgep'); ?></h3>
        
        <form id="sgep_enviar_mensaje_form" class="sgep-form">
            <div class="sgep-form-field">
                <label for="sgep_destinatario_id"><?php _e('Destinatario', 'sgep'); ?></label>
                
                <?php if ($destinatario_id > 0) : ?>
                    <input type="hidden" id="sgep_destinatario_id" name="sgep_destinatario_id" value="<?php echo esc_attr($destinatario_id); ?>">
                    <p><?php echo esc_html($destinatario_nombre); ?></p>
                <?php else : ?>
                    <select id="sgep_destinatario_id" name="sgep_destinatario_id" required>
                        <option value=""><?php _e('-- Seleccionar cliente --', 'sgep'); ?></option>
                        <?php foreach ($clientes as $id => $nombre) : ?>
                            <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_asunto"><?php _e('Asunto', 'sgep'); ?></label>
                <input type="text" id="sgep_asunto" name="sgep_asunto" required>
            </div>
            
            <div class="sgep-form-field">
                <label for="sgep_mensaje"><?php _e('Mensaje', 'sgep'); ?></label>
                <textarea id="sgep_mensaje" name="sgep_mensaje" rows="5" required></textarea>
            </div>
            
            <div class="sgep-form-actions">
                <button type="submit" class="sgep-button sgep-button-primary"><?php _e('Enviar Mensaje', 'sgep'); ?></button>
                <a href="?tab=mensajes" class="sgep-button sgep-button-secondary"><?php _e('Cancelar', 'sgep'); ?></a>
            </div>
        </form>
    </div>
    
    <?php
} else {
    // Listado de mensajes
    global $wpdb;
    
    // Obtener mensajes recibidos
    $mensajes_recibidos = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as remitente_nombre
        FROM {$wpdb->prefix}sgep_mensajes m
        LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
        WHERE m.destinatario_id = %d
        ORDER BY m.created_at DESC",
        $especialista_id
    ));
    
    // Obtener mensajes enviados
    $mensajes_enviados = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as destinatario_nombre
        FROM {$wpdb->prefix}sgep_mensajes m
        LEFT JOIN {$wpdb->users} u ON m.destinatario_id = u.ID
        WHERE m.remitente_id = %d
        ORDER BY m.created_at DESC",
        $especialista_id
    ));
    ?>
    
    <div class="sgep-mensajes-wrapper">
        <div class="sgep-mensajes-header">
            <h3><?php _e('Mis Mensajes', 'sgep'); ?></h3>
            <a href="?tab=mensajes&accion=nuevo" class="sgep-button sgep-button-primary"><?php _e('Nuevo Mensaje', 'sgep'); ?></a>
        </div>
        
        <div class="sgep-mensajes-tabs">
            <ul class="sgep-mensajes-tabs-nav">
                <li class="active"><a href="#sgep-tab-recibidos"><?php _e('Recibidos', 'sgep'); ?></a></li>
                <li><a href="#sgep-tab-enviados"><?php _e('Enviados', 'sgep'); ?></a></li>
            </ul>
            
            <div class="sgep-mensajes-tabs-content">
                <div id="sgep-tab-recibidos" class="sgep-mensajes-tab-panel active">
                    <div class="sgep-mensajes-list">
                        <?php if (!empty($mensajes_recibidos)) : ?>
                            <?php foreach ($mensajes_recibidos as $mensaje) : 
                                $fecha = new DateTime($mensaje->created_at);
                            ?>
                                <div class="sgep-mensaje-item <?php echo $mensaje->leido ? '' : 'sgep-mensaje-no-leido'; ?>" data-id="<?php echo $mensaje->id; ?>">
                                    <div class="sgep-mensaje-avatar">
                                        <?php echo get_avatar($mensaje->remitente_id, 50); ?>
                                    </div>
                                    
                                    <div class="sgep-mensaje-info">
                                        <div class="sgep-mensaje-header">
                                            <span class="sgep-mensaje-remitente"><?php echo esc_html($mensaje->remitente_nombre); ?></span>
                                            <span class="sgep-mensaje-fecha"><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></span>
                                        </div>
                                        
                                        <h4 class="sgep-mensaje-asunto"><?php echo esc_html($mensaje->asunto); ?></h4>
                                        <p class="sgep-mensaje-preview"><?php echo esc_html(wp_trim_words($mensaje->mensaje, 20)); ?></p>
                                    </div>
                                    
                                    <div class="sgep-mensaje-actions">
                                        <a href="?tab=mensajes&accion=ver&id=<?php echo $mensaje->id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Ver', 'sgep'); ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="sgep-no-items"><?php _e('No tienes mensajes recibidos.', 'sgep'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="sgep-tab-enviados" class="sgep-mensajes-tab-panel">
                    <div class="sgep-mensajes-list">
                        <?php if (!empty($mensajes_enviados)) : ?>
                            <?php foreach ($mensajes_enviados as $mensaje) : 
                                $fecha = new DateTime($mensaje->created_at);
                            ?>
                                <div class="sgep-mensaje-item">
                                    <div class="sgep-mensaje-avatar">
                                        <?php echo get_avatar($mensaje->destinatario_id, 50); ?>
                                    </div>
                                    
                                    <div class="sgep-mensaje-info">
                                        <div class="sgep-mensaje-header">
                                            <span class="sgep-mensaje-remitente"><?php _e('Para:', 'sgep'); ?> <?php echo esc_html($mensaje->destinatario_nombre); ?></span>
                                            <span class="sgep-mensaje-fecha"><?php echo esc_html($fecha->format('d/m/Y H:i')); ?></span>
                                        </div>
                                        
                                        <h4 class="sgep-mensaje-asunto"><?php echo esc_html($mensaje->asunto); ?></h4>
                                        <p class="sgep-mensaje-preview"><?php echo esc_html(wp_trim_words($mensaje->mensaje, 20)); ?></p>
                                    </div>
                                    
                                    <div class="sgep-mensaje-actions">
                                        <a href="?tab=mensajes&accion=ver&id=<?php echo $mensaje->id; ?>" class="sgep-button sgep-button-sm sgep-button-primary"><?php _e('Ver', 'sgep'); ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="sgep-no-items"><?php _e('No tienes mensajes enviados.', 'sgep'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}