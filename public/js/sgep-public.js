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
    $('.sgep-cancelar-cita').on('click', function(e) {
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
    $('#sgep_confirmar_cita_form').on('submit', function(e) {
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
}