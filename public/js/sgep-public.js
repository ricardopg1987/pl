/**
 * JavaScript para la parte pública del plugin
 * 
 * Ruta: /public/js/sgep-public.js
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    var sgep_ajax = window.sgep_ajax || {};
    
    /**
     * Inicializar componentes
     */
    function initComponents() {
        // Inicializar círculos de compatibilidad
        initCompatibilityCircles();
        
        // Inicializar gestión de disponibilidad
        initDisponibilidad();
        
        // Inicializar agendamiento de citas
        initCitas();
        
        // Inicializar mensajes
        initMensajes();
    }
    
    /**
     * Inicializar círculos de compatibilidad
     */
    function initCompatibilityCircles() {
        $('.sgep-compatibility-circle').each(function() {
            var percentage = $(this).data('percentage');
            var color;
            
            // Asignar color según porcentaje
            if (percentage >= 80) {
                color = '#4caf50'; // Verde
            } else if (percentage >= 60) {
                color = '#2196f3'; // Azul
            } else if (percentage >= 40) {
                color = '#ff9800'; // Naranja
            } else {
                color = '#f44336'; // Rojo
            }
            
            $(this).css('background-color', color);
        });
    }
    
    /**
     * Inicializar gestión de disponibilidad (panel especialista)
     */
    function initDisponibilidad() {
        // Mostrar formulario para agregar disponibilidad
        $('.sgep-disponibilidad-add').on('click', function() {
            var dia = $(this).data('dia');
            $('#sgep_dia_semana').val(dia);
            $('.sgep-disponibilidad-form').slideDown();
            $('html, body').animate({
                scrollTop: $('.sgep-disponibilidad-form').offset().top - 100
            }, 500);
        });
        
        // Cancelar formulario
        $('#sgep_disponibilidad_cancel').on('click', function(e) {
            e.preventDefault();
            $('.sgep-disponibilidad-form').slideUp();
            $('#sgep_disponibilidad_form')[0].reset();
        });
        
        // Guardar disponibilidad
        $('#sgep_disponibilidad_form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var diaSelector = form.find('#sgep_dia_semana');
            var horaInicio = form.find('#sgep_hora_inicio').val();
            var horaFin = form.find('#sgep_hora_fin').val();
            
            // Validaciones
            if (!horaInicio || !horaFin) {
                alert('Por favor, especifica la hora de inicio y fin.');
                return;
            }
            
            // Enviar petición AJAX
            btn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_guardar_disponibilidad',
                    nonce: sgep_ajax.nonce,
                    dia_semana: diaSelector.val(),
                    hora_inicio: horaInicio,
                    hora_fin: horaFin
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        alert(response.data.message);
                        
                        // Recargar página
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al guardar la disponibilidad. Por favor, intenta nuevamente.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Guardar Disponibilidad');
                }
            });
        });
        
        // Eliminar disponibilidad
        $('.sgep-disponibilidad-action').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de eliminar este horario de disponibilidad?')) {
                return;
            }
            
            var btn = $(this);
            var id = btn.data('id');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_eliminar_disponibilidad',
                    nonce: sgep_ajax.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        // Eliminar elemento del DOM
                        btn.closest('.sgep-disponibilidad-slot').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al eliminar la disponibilidad. Por favor, intenta nuevamente.');
                }
            });
        });
    }
    
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
        
        // Cancelar cita
        $('.sgep-cancelar-cita').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de cancelar esta cita?')) {
                return;
            }
            
            var btn = $(this);
            var citaId = btn.data('id');
            
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
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al cancelar la cita. Por favor, intenta nuevamente.');
                }
            });
        });
        
        // Confirmar cita (para especialistas)
        $('#sgep_confirmar_cita_form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var citaId = form.find('#sgep_cita_id').val();
            var zoomLink = form.find('#sgep_zoom_link').val();
            var zoomId = form.find('#sgep_zoom_id').val();
            var zoomPassword = form.find('#sgep_zoom_password').val();
            
            // Validaciones
            if (!citaId) {
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
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al confirmar la cita. Por favor, intenta nuevamente.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Confirmar Cita');
                }
            });
        });
    }
    
    /**
     * Inicializar mensajes
     */
    function initMensajes() {
        // Formulario para enviar mensaje
        $('#sgep_enviar_mensaje_form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('[type="submit"]');
            var destinatarioId = form.find('#sgep_destinatario_id').val();
            var asunto = form.find('#sgep_asunto').val();
            var mensaje = form.find('#sgep_mensaje').val();
            
            // Validaciones
            if (!destinatarioId || !asunto || !mensaje) {
                alert('Por favor, completa todos los campos.');
                return;
            }
            
            // Enviar petición AJAX
            btn.prop('disabled', true).text('Enviando...');
            
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
                        // Mostrar mensaje de éxito
                        alert(response.data.message);
                        
                        // Redireccionar a la página de mensajes
                        window.location.href = '?tab=mensajes';
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
        
        // Marcar mensaje como leído
        $('.sgep-mensaje-item').on('click', function() {
            var item = $(this);
            
            if (!item.hasClass('sgep-mensaje-no-leido')) {
                return;
            }
            
            var mensajeId = item.data('id');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sgep_marcar_mensaje_leido',
                    nonce: sgep_ajax.nonce,
                    mensaje_id: mensajeId
                },
                success: function(response) {
                    if (response.success) {
                        item.removeClass('sgep-mensaje-no-leido');
                    }
                }
            });
        });
    }
    
    /**
     * Inicializar filtrado de especialistas
     */
    function initFiltradoEspecialistas() {
        // Filtrado AJAX de especialistas (para directorio público)
        $('#sgep_filtrar_especialistas').on('change', function() {
            var form = $(this).closest('form');
            var especialidad = form.find('[name="especialidad"]').val();
            var modalidad = form.find('[name="modalidad"]').val();
            var genero = form.find('[name="genero"]').val();
            var resultadosContainer = $('.sgep-directorio-results');
            
            // Mostrar indicador de carga
            resultadosContainer.html('<div class="sgep-loading">Cargando especialistas...</div>');
            
            $.ajax({
                url: sgep_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'sgep_obtener_especialistas',
                    especialidad: especialidad,
                    modalidad: modalidad,
                    genero: genero
                },
                success: function(response) {
                    if (response.success && response.data.especialistas) {
                        renderizarEspecialistas(response.data.especialistas, resultadosContainer);
                    } else {
                        resultadosContainer.html('<p class="sgep-no-results">No se encontraron especialistas que coincidan con los filtros aplicados.</p>');
                    }
                },
                error: function() {
                    resultadosContainer.html('<p class="sgep-no-results">Error al cargar especialistas. Por favor, intenta nuevamente.</p>');
                }
            });
        });
    }
    
    /**
     * Renderizar especialistas filtrados
     */
    function renderizarEspecialistas(especialistas, container) {
        if (!especialistas || especialistas.length === 0) {
            container.html('<p class="sgep-no-results">No se encontraron especialistas que coincidan con los filtros aplicados.</p>');
            return;
        }
        
        var html = '<div class="sgep-especialistas-grid">';
        
        especialistas.forEach(function(especialista) {
            html += '<div class="sgep-especialista-card">';
            html += '<div class="sgep-especialista-header">';
            html += '<div class="sgep-especialista-avatar"><img src="' + especialista.avatar + '" alt="Avatar"></div>';
            
            if (especialista.rating) {
                html += '<div class="sgep-especialista-rating">';
                
                // Generar estrellas según rating
                var ratingValue = parseFloat(especialista.rating);
                for (var i = 1; i <= 5; i++) {
                    if (i <= ratingValue) {
                        html += '<span class="sgep-star sgep-star-full">★</span>';
                    } else if (i - 0.5 <= ratingValue) {
                        html += '<span class="sgep-star sgep-star-half">★</span>';
                    } else {
                        html += '<span class="sgep-star sgep-star-empty">☆</span>';
                    }
                }
                
                html += '<span class="sgep-rating-value">' + ratingValue.toFixed(1) + '</span>';
                html += '</div>';
            }
            
            html += '</div>';
            html += '<div class="sgep-especialista-content">';
            html += '<h3>' + especialista.nombre + '</h3>';
            
            if (especialista.especialidad) {
                html += '<p class="sgep-especialista-specialty">' + especialista.especialidad + '</p>';
            }
            
            html += '<div class="sgep-especialista-tags">';
            
            if (especialista.online) {
                html += '<span class="sgep-tag">Online</span>';
            }
            
            if (especialista.presencial) {
                html += '<span class="sgep-tag">Presencial</span>';
            }
            
            html += '</div>';
            
            if (especialista.precio) {
                html += '<p class="sgep-especialista-price">Precio consulta: ' + especialista.precio + '</p>';
            }
            
            html += '</div>';
            html += '<div class="sgep-especialista-actions">';
            html += '<a href="?ver_especialista=' + especialista.id + '" class="sgep-button sgep-button-secondary">Ver Perfil</a>';
            
            // Verificar si el usuario está logueado
            if (sgep_ajax.is_logged_in) {
                html += '<a href="?agendar_con=' + especialista.id + '" class="sgep-button sgep-button-primary">Agendar Cita</a>';
            } else {
                html += '<a href="' + sgep_ajax.login_url + '" class="sgep-button sgep-button-primary">Iniciar Sesión para Agendar</a>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        container.html(html);
    }
    
    // Inicializar todos los componentes
    initComponents();
    initFiltradoEspecialistas();
});