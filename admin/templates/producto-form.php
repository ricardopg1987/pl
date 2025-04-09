<?php
/**
 * Plantilla para el formulario de producto
 * 
 * Ruta: /admin/templates/producto-form.php
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Si estamos editando, obtener valores del producto
$id = $accion === 'editar' && $producto ? $producto->id : 0;
$nombre = $accion === 'editar' && $producto ? $producto->nombre : '';
$descripcion = $accion === 'editar' && $producto ? $producto->descripcion : '';
$precio = $accion === 'editar' && $producto ? $producto->precio : '';
$sku = $accion === 'editar' && $producto ? $producto->sku : '';
$especialista_id = $accion === 'editar' && $producto ? $producto->especialista_id : 0;
$stock = $accion === 'editar' && $producto ? $producto->stock : 0;
$categoria = $accion === 'editar' && $producto ? $producto->categoria : '';
$imagen_url = $accion === 'editar' && $producto ? $producto->imagen_url : '';
?>

<div class="wrap sgep-admin-container">
    <div class="sgep-admin-header">
        <h1 class="sgep-admin-title">
            <?php echo $accion === 'editar' ? __('Editar Producto', 'sgep') : __('Crear Nuevo Producto', 'sgep'); ?>
        </h1>
        <div class="sgep-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=sgep-productos'); ?>" class="button"><?php _e('Volver al listado', 'sgep'); ?></a>
        </div>
    </div>
    
    <?php if (isset($mensaje)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="sgep-admin-content">
        <form method="post" class="sgep-admin-form" enctype="multipart/form-data">
            <?php wp_nonce_field('guardar_producto', 'sgep_producto_nonce'); ?>
            
            <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
            
            <div class="sgep-form-section">
                <h2 class="sgep-form-section-title"><?php _e('Información del Producto', 'sgep'); ?></h2>
                
                <div class="sgep-form-row">
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="nombre"><?php _e('Nombre del Producto', 'sgep'); ?> <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo esc_attr($nombre); ?>" required>
                        </div>
                    </div>
                    
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="sku"><?php _e('SKU', 'sgep'); ?> <span class="required">*</span></label>
                            <input type="text" id="sku" name="sku" value="<?php echo esc_attr($sku); ?>" required>
                            <p class="description"><?php _e('Código único para identificar el producto.', 'sgep'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="sgep-form-field">
                    <label for="descripcion"><?php _e('Descripción', 'sgep'); ?></label>
                    <textarea id="descripcion" name="descripcion" rows="5"><?php echo esc_textarea($descripcion); ?></textarea>
                </div>
                
                <div class="sgep-form-row">
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="precio"><?php _e('Precio', 'sgep'); ?> <span class="required">*</span></label>
                            <input type="number" id="precio" name="precio" value="<?php echo esc_attr($precio); ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="stock"><?php _e('Stock', 'sgep'); ?></label>
                            <input type="number" id="stock" name="stock" value="<?php echo esc_attr($stock); ?>" min="0">
                            <p class="description"><?php _e('Deja en 0 para productos ilimitados o digitales.', 'sgep'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="sgep-form-row">
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="categoria"><?php _e('Categoría', 'sgep'); ?></label>
                            <select id="categoria" name="categoria">
                                <option value="" <?php selected($categoria, ''); ?>><?php _e('-- Seleccionar categoría --', 'sgep'); ?></option>
                                <option value="libros" <?php selected($categoria, 'libros'); ?>><?php _e('Libros', 'sgep'); ?></option>
                                <option value="cursos" <?php selected($categoria, 'cursos'); ?>><?php _e('Cursos', 'sgep'); ?></option>
                                <option value="accesorios" <?php selected($categoria, 'accesorios'); ?>><?php _e('Accesorios', 'sgep'); ?></option>
                                <option value="esencias" <?php selected($categoria, 'esencias'); ?>><?php _e('Esencias', 'sgep'); ?></option>
                                <option value="terapias" <?php selected($categoria, 'terapias'); ?>><?php _e('Terapias', 'sgep'); ?></option>
                                <option value="aceites" <?php selected($categoria, 'aceites'); ?>><?php _e('Aceites', 'sgep'); ?></option>
                                <option value="cristales" <?php selected($categoria, 'cristales'); ?>><?php _e('Cristales', 'sgep'); ?></option>
                                <option value="otros" <?php selected($categoria, 'otros'); ?>><?php _e('Otros', 'sgep'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sgep-form-col">
                        <div class="sgep-form-field">
                            <label for="especialista_id"><?php _e('Especialista Asociado', 'sgep'); ?></label>
                            <select id="especialista_id" name="especialista_id">
                                <option value="0" <?php selected($especialista_id, 0); ?>><?php _e('-- Ninguno (Admin) --', 'sgep'); ?></option>
                                <?php foreach ($especialistas as $especialista) : ?>
                                    <option value="<?php echo esc_attr($especialista->ID); ?>" <?php selected($especialista_id, $especialista->ID); ?>>
                                        <?php echo esc_html($especialista->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Si el producto está asociado a un especialista específico, selecciónalo aquí.', 'sgep'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="sgep-form-field">
                    <label for="imagen_url"><?php _e('URL de Imagen', 'sgep'); ?></label>
                    <input type="url" id="imagen_url" name="imagen_url" value="<?php echo esc_url($imagen_url); ?>" class="regular-text">
                    <p class="description"><?php _e('URL de la imagen del producto. Usa el botón para subir una nueva.', 'sgep'); ?></p>
                    
                    <div class="sgep-media-upload">
                        <input type="button" id="sgep-upload-image" class="button" value="<?php _e('Subir Imagen', 'sgep'); ?>">
                        
                        <?php if (!empty($imagen_url)) : ?>
                            <div class="sgep-image-preview">
                                <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php _e('Previsualización', 'sgep'); ?>" style="max-width: 200px; max-height: 200px; margin-top: 10px;">
                            </div>
                        <?php else : ?>
                            <div class="sgep-image-preview" style="display: none;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="sgep-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Guardar Producto', 'sgep'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=sgep-productos'); ?>" class="button"><?php _e('Cancelar', 'sgep'); ?></a>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Inicializar Media Uploader de WordPress
    $('#sgep-upload-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: '<?php _e('Seleccionar Imagen', 'sgep'); ?>',
            button: {
                text: '<?php _e('Usar esta imagen', 'sgep'); ?>'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#imagen_url').val(attachment.url);
            
            $('.sgep-image-preview').html('<img src="' + attachment.url + '" alt="<?php _e('Previsualización', 'sgep'); ?>" style="max-width: 200px; max-height: 200px; margin-top: 10px;">').show();
        });
        
        mediaUploader.open();
    });
});
</script>