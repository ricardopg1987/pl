/**
 * Inicializar agendamiento de citas
 */
function initCitas() {
    // Formulario para agendar cita
    $('#sgep_agendar_cita_form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = form.find('[type="submit"]');
        var especialistaId = form.find('#sgep_especialista_id').val();
        var fecha = form.find('#sgep_fecha').val();
        var hora = form.find('#sgep_hora').val();
        var notas = form.find('#sgep_notas').val();
        
        // Validaciones
        if (!especialistaId || !fecha || !hora) {
            alert('Por favor, completa todos los campos obligatorios.');
            return;
        }
        
        // Enviar petición AJAX
        btn.prop('disabled', true).text('Agendando...');
        
        $.ajax({
            url: sgep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sgep_agendar_cita',
                nonce: sgep_ajax.nonce,
                especialista_id: especialistaId,
                fecha: fecha,
                hora: hora,
                notas: notas
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    alert(response.data.message);
                    
                    // Redireccionar a la página de citas
                    window.location.href = '?tab=citas';
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error al agendar la cita. Por favor, intenta nuevamente.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Agendar Cita');
            }
        });
    });
    
    // Cargar horas disponibles al seleccionar fecha
    $('#sgep_fecha').on('change', function() {
        var fecha = $(this).val();
        var especialistaId = $('#sgep_especialista_id').val();
        var horaSelector = $('#sgep_hora');
        
        if (!fecha || !especialistaId) {
            return;
        }
        
        // Limpiar selector de horas
        horaSelector.empty().prop('disabled', true);
        horaSelector.append('<option value="">Cargando horas disponibles...</option>');
        
        // Obtener horas disponibles
        $.ajax({
            url: sgep_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'sgep_obtener_horas_disponibles',
                nonce: sgep_ajax.nonce,
                especialista_id: especialistaId,
                fecha: fecha
            },
            success: function(response) {
                horaSelector.empty();
                
                if (response.success && response.data.horas && response.data.horas.length > 0) {
                    horaSelector.append('<option value="">-- Seleccionar hora --</option>');
                    
                    response.data.horas.forEach(function(hora) {
                        horaSelector.append('<option value="' + hora + '">' + hora + '</option>');
                    });
                    
                    horaSelector.prop('disabled', false);
                } else {
                    horaSelector.append('<option value="">No hay horas disponibles</option>');
                }
            },
            error: function() {
                horaSelector.empty().append('<option value="">Error al cargar horas</option>');
            }
        });
    });
    
    // Cancelar cita - CORREGIDO
    $(document).on('click', '.sgep-cancelar-cita', function(e) {
        e.preventDefault();
        
        if (!confirm('¿Estás seguro de cancelar esta cita?')) {
            return;
        }
        
        var btn = $(this);
        var citaId = btn.data('id');
        
        // Verificar que el ID es válido
        if (!citaId || isNaN(citaId) || citaId <= 0) {
            alert('ID de cita inválido.');
            return;
        }
        
        // Mostrar indicador de carga
        btn.prop('disabled', true).text('Cancelando...');
        
        $.ajax({
            url: sgep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sgep_cancelar_cita',
                nonce: sgep_ajax.nonce,
                cita_id: citaId
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    alert(response.data.message);
                    
                    // Recargar página
                    location.reload();
                } else {
                    alert(response.data || 'Error al cancelar la cita.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                alert('Error al cancelar la cita. Por favor, intenta nuevamente.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Cancelar');
            }
        });
    });
    
    // Confirmar cita (para especialistas) - CORREGIDO
    $(document).on('submit', '#sgep_confirmar_cita_form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = form.find('[type="submit"]');
        var citaId = form.find('#sgep_cita_id').val();
        var zoomLink = form.find('#sgep_zoom_link').val();
        var zoomId = form.find('#sgep_zoom_id').val();
        var zoomPassword = form.find('#sgep_zoom_password').val();
        
        // Validaciones
        if (!citaId || isNaN(citaId) || citaId <= 0) {
            alert('ID de cita inválido.');
            return;
        }
        
        // Enviar petición AJAX
        btn.prop('disabled', true).text('Confirmando...');
        
        $.ajax({
            url: sgep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sgep_confirmar_cita',
                nonce: sgep_ajax.nonce,
                cita_id: citaId,
                zoom_link: zoomLink,
                zoom_id: zoomId,
                zoom_password: zoomPassword
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    alert(response.data.message);
                    
                    // Recargar página
                    location.reload();
                } else {
                    alert(response.data || 'Error al confirmar la cita.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                alert('Error al confirmar la cita. Por favor, intenta nuevamente.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Confirmar Cita');
            }
        });
    });

    // Inicializar manejo de mensajes
    $(document).on('submit', '#sgep_enviar_mensaje_form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = form.find('[type="submit"]');
        var destinatarioId = form.find('#sgep_destinatario_id').val();
        var asunto = form.find('#sgep_asunto').val();
        var mensaje = form.find('#sgep_mensaje').val();
        
        // Validar campos
        if (!destinatarioId || !asunto || !mensaje) {
            alert('Por favor completa todos los campos');
            return;
        }
        
        // Deshabilitar botón mientras se procesa
        btn.prop('disabled', true).text('Enviando...');
        
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
                    
                    // Redireccionar si no estamos en un modal
                    if (!form.hasClass('sgep-mensaje-rapido-form')) {
                        window.location.href = '?tab=mensajes';
                    }
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error al enviar el mensaje. Por favor, intenta nuevamente.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Enviar Mensaje');
            }
        });
    });

    // Tabs de mensajes
    $('.sgep-mensajes-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Activar la pestaña
        $('.sgep-mensajes-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        // Mostrar contenido
        $('.sgep-mensajes-tab-panel').removeClass('active');
        $(target).addClass('active');
    });

    // Inicializar tabs de perfil
    $('.sgep-perfil-tabs-nav a').on('click', function(e) {
        // Si el enlace es un enlace real (con href que no empiece con #), dejarlo pasar
        if (this.getAttribute('href') && this.getAttribute('href').charAt(0) !== '#') {
            return;
        }
        
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Activar la pestaña
        $('.sgep-perfil-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        // Mostrar contenido
        $('.sgep-perfil-tabs-content .sgep-perfil-tab-panel').removeClass('active');
        $(target).addClass('active');
    });

    // Compra de productos
    $(document).on('click', '.sgep-comprar-producto', function(e) {
        e.preventDefault();
        var productoId = $(this).data('id');
        var productoNombre = $(this).data('nombre');
        var productoPrecio = $(this).data('precio');
        
        if (confirm('¿Deseas comprar el producto "' + productoNombre + '" por ' + productoPrecio + '?')) {
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
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al procesar la compra. Por favor, intenta nuevamente.');
                }
            });
        }
    });
}

// Ejecutar la inicialización cuando el documento esté listo
jQuery(document).ready(function($) {
    initCitas();
});