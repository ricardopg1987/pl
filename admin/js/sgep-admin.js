/**
 * JavaScript para la parte administrativa del plugin
 * 
 * Ruta: /admin/js/sgep-admin.js
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    var sgep_admin = window.sgep_admin || {};
    
    /**
     * Inicializar componentes
     */
    function initComponents() {
        // Inicializar gestión de preguntas del test
        initTestQuestions();
        
        // Inicializar gestión de especialistas
        initEspecialistas();
        
        // Inicializar gestión de clientes
        initClientes();
        
        // Inicializar gestión de citas
        initCitas();
    }
    
    /**
     * Inicializar gestión de preguntas del test
     */
    function initTestQuestions() {
        // Variables
        var $questionContainer = $('.sgep-test-questions');
        var questionTemplate = $('#sgep-question-template').html();
        var optionTemplate = $('#sgep-option-template').html();
        var nextQuestionId = $questionContainer.data('next-id') || 1;
        
        // Agregar nueva pregunta
        $('#sgep-add-question').on('click', function(e) {
            e.preventDefault();
            
            var newQuestion = questionTemplate.replace(/\{id\}/g, nextQuestionId);
            $questionContainer.append(newQuestion);
            nextQuestionId++;
        });
        
        // Eliminar pregunta
        $(document).on('click', '.sgep-delete-question', function(e) {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de eliminar esta pregunta?')) {
                $(this).closest('.sgep-test-question').remove();
            }
        });
        
        // Agregar nueva opción
        $(document).on('click', '.sgep-add-option', function(e) {
            e.preventDefault();
            
            var $question = $(this).closest('.sgep-test-question');
            var questionId = $question.data('id');
            var $optionsContainer = $question.find('.sgep-test-question-options');
            
            var newOption = optionTemplate.replace(/\{question_id\}/g, questionId);
            $optionsContainer.append(newOption);
        });
        
        // Eliminar opción
        $(document).on('click', '.sgep-delete-option', function(e) {
            e.preventDefault();
            
            $(this).closest('.sgep-test-option').remove();
        });
        
        // Cambiar tipo de pregunta
        $(document).on('change', '.sgep-question-type', function() {
            var type = $(this).val();
            var $question = $(this).closest('.sgep-test-question');
            
            if (type === 'multiple') {
                $question.find('.sgep-option-description').text('Los usuarios podrán seleccionar múltiples opciones.');
            } else {
                $question.find('.sgep-option-description').text('Los usuarios solo podrán seleccionar una opción.');
            }
        });
    }
    
    /**
     * Inicializar gestión de especialistas
     */
    function initEspecialistas() {
        // Eliminar especialista
        $('.sgep-delete-especialista').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de eliminar este especialista? Esta acción no se puede deshacer.')) {
                return;
            }
            
            var especialistaId = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sgep_eliminar_especialista',
                    nonce: sgep_admin.nonce,
                    especialista_id: especialistaId
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al eliminar el especialista. Por favor, intenta nuevamente.');
                }
            });
        });
        
        // Selección múltiple de habilidades
        if ($.fn.select2) {
            $('.sgep-habilidades-select').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: 'Selecciona o ingresa habilidades',
                allowClear: true
            });
        }
    }
    
    /**
     * Inicializar gestión de clientes
     */
    function initClientes() {
        // Eliminar cliente
        $('.sgep-delete-cliente').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.')) {
                return;
            }
            
            var clienteId = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sgep_eliminar_cliente',
                    nonce: sgep_admin.nonce,
                    cliente_id: clienteId
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al eliminar el cliente. Por favor, intenta nuevamente.');
                }
            });
        });
        
        // Mostrar resultados del test
        $('.sgep-ver-test').on('click', function(e) {
            e.preventDefault();
            
            var clienteId = $(this).data('id');
            var modalContent = $('#sgep-test-resultados-content');
            
            // Limpiar contenido previo
            modalContent.html('<p>Cargando resultados...</p>');
            
            // Mostrar modal
            $('#sgep-test-resultados-modal').fadeIn(300);
            
            // Cargar resultados del test
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'sgep_obtener_test_resultados',
                    nonce: sgep_admin.nonce,
                    cliente_id: clienteId
                },
                success: function(response) {
                    if (response.success) {
                        modalContent.html(response.data.html);
                    } else {
                        modalContent.html('<p>Error al cargar los resultados: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    modalContent.html('<p>Error al cargar los resultados. Por favor, intenta nuevamente.</p>');
                }
            });
        });
        
        // Cerrar modal
        $('.sgep-modal-close').on('click', function() {
            $('.sgep-modal').fadeOut(300);
        });
        
        // Cerrar modal al hacer clic fuera
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('sgep-modal')) {
                $('.sgep-modal').fadeOut(300);
            }
        });
    }
    
    /**
     * Inicializar gestión de citas
     */
    function initCitas() {
        // Ver detalles de cita
        $('.sgep-ver-cita').on('click', function(e) {
            e.preventDefault();
            
            var citaId = $(this).data('id');
            var modalContent = $('#sgep-cita-detalles-content');
            
            // Limpiar contenido previo
            modalContent.html('<p>Cargando detalles...</p>');
            
            // Mostrar modal
            $('#sgep-cita-detalles-modal').fadeIn(300);
            
            // Cargar detalles de la cita
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'sgep_obtener_cita_detalles',
                    nonce: sgep_admin.nonce,
                    cita_id: citaId
                },
                success: function(response) {
                    if (response.success) {
                        modalContent.html(response.data.html);
                    } else {
                        modalContent.html('<p>Error al cargar los detalles: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    modalContent.html('<p>Error al cargar los detalles. Por favor, intenta nuevamente.</p>');
                }
            });
        });
        
        // Cambiar estado de cita
        $(document).on('click', '.sgep-cambiar-estado', function(e) {
            e.preventDefault();
            
            var citaId = $(this).data('id');
            var nuevoEstado = $(this).data('estado');
            var btn = $(this);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sgep_cambiar_estado_cita',
                    nonce: sgep_admin.nonce,
                    cita_id: citaId,
                    estado: nuevoEstado
                },
                success: function(response) {
                    if (response.success) {
                        // Cerrar modal
                        $('.sgep-modal').fadeOut(300);
                        
                        // Recargar página
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error al cambiar el estado de la cita. Por favor, intenta nuevamente.');
                }
            });
        });
    }
    
    // Inicializar todos los componentes
    initComponents();
});