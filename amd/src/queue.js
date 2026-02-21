define(
    [
        'jquery',
        'core/modal_factory',
        'core/modal_events',
        'core/notification'
    ],
    function(
        $,
        ModalFactory,
        ModalEvents,
        Notification
    ) {
        return {
            init: function() {
                $('body')
                    .off('click.ihpayload')
                    .on(
                        'click.ihpayload',
                        '.ih-view-payload',
                        function(e) {
                            e.preventDefault();
                            var trigger = $(this);
                            var payload = trigger.attr('data-payload');
                            var title = trigger.attr('data-title') || 'Payload';
                            ModalFactory.create({
                                type: ModalFactory.types.CANCEL,
                                title: title,
                                body: $('<pre>')
                                    .css({
                                        'max-height': '500px',
                                        'overflow': 'auto'
                                    })
                                    .text(payload)
                            })
                                .then(function(modal) {

                                    modal.show();

                                    modal.getRoot().on(
                                        ModalEvents.hidden,
                                        function() {
                                            modal.destroy();
                                        }
                                    );
                                    return null;
                                })
                                .catch(Notification.exception);
                        }
                    );
            }
        };
    }
);
