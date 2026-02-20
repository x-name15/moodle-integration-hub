define(
    [
        'jquery',
        'core/ajax',
        'core/notification'
    ],
    function (
        $,
        Ajax,
        Notification
    ) {

        return {
            init: function (serviceTypes) {

                try {
                    var formContainer = $('#ih-rule-form');
                    var btnAdd = $('#ih-btn-add');
                    var btnCancel = $('#ih-btn-cancel');
                    var btnPreview = $('#ih-btn-preview');
                    var templateField = $('#ih-template');
                    var serviceField = $('#ih-serviceid');
                    var endpointField = $('#ih-endpoint');
                    var endpointLabel =
                        $('label[for="ih-endpoint"]');
                    var methodContainer =
                        $('#ih-method-container');

                    var updateEndpointLabel =
                        function () {

                            var svcId =
                                serviceField.val();

                            var type =
                                serviceTypes[svcId] ||
                                'rest';

                            if (type === 'amqp') {
                                endpointLabel.text(
                                    'Queue Name / Routing Key'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. user_sync_queue'
                                );

                                methodContainer.addClass(
                                    'd-none'
                                );
                            } else if (type === 'soap') {
                                endpointLabel.text(
                                    'SOAP Action / Method'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. CreateUser'
                                );

                                methodContainer.addClass(
                                    'd-none'
                                );
                            } else {
                                endpointLabel.text(
                                    'Endpoint Path'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. /api/v1/users'
                                );

                                methodContainer.removeClass(
                                    'd-none'
                                );
                            }
                        };

                    if (serviceField.length) {
                        serviceField.on(
                            'change',
                            updateEndpointLabel
                        );

                        updateEndpointLabel();
                    }

                    if (btnAdd.length) {
                        btnAdd.on('click', function () {

                            $('#ih-ruleid').val('0');
                            $('#ih-form')[0].reset();

                            formContainer.removeClass(
                                'd-none'
                            );

                            btnAdd.addClass('d-none');
                        });
                    }

                    if (btnCancel.length) {
                        btnCancel.on('click', function () {

                            formContainer.addClass(
                                'd-none'
                            );

                            btnAdd.removeClass('d-none');
                        });
                    }

                    if (btnPreview.length) {
                        btnPreview.on(
                            'click',
                            function (e) {

                                e.preventDefault();

                                var template =
                                    templateField.val();

                                if (!template) {
                                    Notification.alert(
                                        'Error',
                                        'Please enter a template first.',
                                        'OK'
                                    );
                                    return;
                                }

                                Notification.alert(
                                    'Info',
                                    'Preview feature ready.',
                                    'OK'
                                );
                            }
                        );
                    }
                } catch (e) {
                    Notification.exception(e);
                }
            }
        };
    }
);
