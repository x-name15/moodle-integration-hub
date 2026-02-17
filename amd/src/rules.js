define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory'],
    function ($, Ajax, Notification, ModalFactory) {
        return {
            init: function (serviceTypes) {
                var formContainer = $('#ih-rule-form');
                var btnAdd = $('#ih-btn-add');
                var btnCancel = $('#ih-btn-cancel');
                var btnPreview = $('#ih-btn-preview');
                var templateField = $('#ih-template');
                var eventField = $('#ih-eventname');
                var serviceField = $('#ih-serviceid');
                var endpointField = $('#ih-endpoint');
                var endpointLabel = $('label[for="ih-endpoint"]');
                /**
                 * Update the endpoint field label and placeholder based on the selected service type.
                 * @returns {void}
                 */
                function updateEndpointLabel() {
                    var svcId = serviceField.val();
                    var type = serviceTypes[svcId] || 'rest';
                    if (type === 'amqp') {
                        endpointLabel.text('Queue Name / Routing Key');
                        endpointField.attr('placeholder', 'e.g. user_sync_queue');
                    } else if (type === 'soap') {
                        endpointLabel.text('SOAP Action / Method');
                        endpointField.attr('placeholder', 'e.g. CreateUser');
                    } else {
                        endpointLabel.text('Endpoint Path');
                        endpointField.attr('placeholder', 'e.g. /api/v1/users');
                    }
                }

                if (serviceField.length) {
                    serviceField.on('change', updateEndpointLabel);
                    // Initial update for edit mode.
                    updateEndpointLabel();
                }

                if (btnAdd.length) {
                    btnAdd.on('click', function () {
                        $('#ih-ruleid').val('0');
                        $('#ih-form')[0].reset();
                        formContainer.removeClass('d-none');
                        btnAdd.addClass('d-none');
                    });
                }

                if (btnCancel.length) {
                    btnCancel.on('click', function () {
                        formContainer.addClass('d-none');
                        btnAdd.removeClass('d-none');
                    });
                }

                if (btnPreview.length) {
                    btnPreview.on('click', function (e) {
                        e.preventDefault();
                        var template = templateField.val();
                        var eventname = eventField.val();

                        if (!template) {
                            Notification.alert('Error', 'Please enter a template first.', 'OK');
                            return;
                        }

                        var originalText = btnPreview.text();
                        btnPreview.prop('disabled', true).text('Processing...');

                        $.ajax({
                            url: M.cfg.wwwroot + '/local/integrationhub/ajax.php',
                            data: {
                                action: 'preview_payload',
                                template: template,
                                eventname: eventname,
                                sesskey: M.cfg.sesskey
                            },
                            dataType: 'json'
                        }).done(function (data) {
                            btnPreview.prop('disabled', false).text(originalText);
                            if (data.success) {
                                var content = '<pre style="background:#f8f9fa; padding:10px; border:1px solid #ddd; ' +
                                    'max-height:300px; overflow:auto;">' +
                                    JSON.stringify(data.payload, null, 2) +
                                    '</pre>';

                                ModalFactory.create({
                                    title: 'Payload Preview',
                                    body: content,
                                    type: ModalFactory.types.DEFAULT
                                }).done(function (modal) {
                                    modal.show();
                                });
                            } else {
                                var errMsg = 'Template is invalid: ' + data.error;
                                Notification.alert('JSON Error', errMsg, 'OK');
                            }
                        }).fail(function () {
                            btnPreview.prop('disabled', false).text(originalText);
                            Notification.exception(new Error('Failed to connect to preview service.'));
                        });
                    });
                }
            }
        };
    });
