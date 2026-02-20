/* global Chart */
define(
    ['core/notification'],
    function (Notification) {

        return {
            init: function (data, strings) {

                try {
                    const form = document.getElementById('ih-service-form');
                    const btnAdd = document.getElementById('ih-btn-add');
                    const btnCancel = document.getElementById('ih-btn-cancel');

                    if (btnAdd && form) {
                        btnAdd.addEventListener('click', function () {
                            const svcId = document.getElementById('ih-serviceid');
                            const ihForm = document.getElementById('ih-form');

                            if (svcId) {
                                svcId.value = '0';
                            }

                            if (ihForm) {
                                ihForm.reset();
                            }

                            form.classList.remove('d-none');
                            btnAdd.classList.add('d-none');

                            const nameField = document.getElementById('ih-name');
                            if (nameField) {
                                nameField.focus();
                            }
                        });
                    }

                    if (btnCancel && form) {
                        btnCancel.addEventListener('click', function () {
                            form.classList.add('d-none');
                            if (btnAdd) {
                                btnAdd.classList.remove('d-none');
                            }
                        });
                    }

                    const typeField = document.getElementById('ih-type');
                    const urlField = document.getElementById('ih-base_url');
                    const urlHelp = document.getElementById('ih-base_url-help');
                    const amqpBuilder = document.getElementById('ih-amqp-builder');
                    const baseUrlContainer =
                        document.querySelector('.ih-base-url-container');

                    const updateUiForType = function () {
                        const type = typeField.value || 'rest';

                        if (type === 'amqp') {
                            urlHelp.textContent = strings.url_help_amqp;

                            if (amqpBuilder) {
                                amqpBuilder.classList.remove('d-none');
                            }

                            if (baseUrlContainer) {
                                baseUrlContainer.classList.add('d-none');
                            }
                        } else {
                            // If switching away from AMQP and the URL is an AMQP one, clear it.
                            if (urlField.value && (urlField.value.startsWith('amqp://') || urlField.value.startsWith('amqps://'))) {
                                urlField.value = '';
                            }

                            urlHelp.textContent = strings.url_help_rest;

                            if (amqpBuilder) {
                                amqpBuilder.classList.add('d-none');
                            }

                            if (baseUrlContainer) {
                                baseUrlContainer.classList.remove('d-none');
                            }
                        }
                    };

                    const syncAmqpUrl = function () {
                        const host =
                            document.getElementById('ih-amqp_host').value ||
                            'localhost';

                        const port =
                            document.getElementById('ih-amqp_port').value ||
                            '5672';

                        const user =
                            document.getElementById('ih-amqp_user').value ||
                            'guest';

                        const pass =
                            document.getElementById('ih-amqp_pass').value ||
                            'guest';

                        let vhost =
                            document.getElementById('ih-amqp_vhost').value || '/';

                        const exchange =
                            document.getElementById('ih-amqp_exchange').value;

                        const routingKey =
                            document.getElementById('ih-amqp_routing_key').value;

                        const queueDeclare =
                            document.getElementById('ih-amqp_queue_declare').value;

                        const dlq =
                            document.getElementById('ih-amqp_dlq').value;

                        if (vhost !== '/' && vhost.startsWith('/')) {
                            vhost = vhost.substring(1);
                        }

                        const eUser = encodeURIComponent(user);
                        const ePass = encodeURIComponent(pass);
                        const eHost = encodeURIComponent(host);

                        let eVhost = '';
                        if (vhost !== '/') {
                            eVhost = encodeURIComponent(vhost);
                        }

                        const scheme = (port === '5671') ? 'amqps' : 'amqp';

                        let url =
                            scheme + '://' + eUser + ':' + ePass + '@' +
                            eHost + ':' + port + '/' + eVhost;

                        const params = [];

                        if (exchange) {
                            params.push(
                                'exchange=' +
                                encodeURIComponent(exchange)
                            );
                        }

                        if (routingKey) {
                            params.push(
                                'routing_key=' +
                                encodeURIComponent(routingKey)
                            );
                        }

                        if (queueDeclare) {
                            params.push(
                                'queue_declare=' +
                                encodeURIComponent(queueDeclare)
                            );
                        }

                        if (dlq) {
                            params.push(
                                'dlq=' +
                                encodeURIComponent(dlq)
                            );
                        }

                        if (params.length > 0) {
                            url += '?' + params.join('&');
                        }

                        if (urlField) {
                            urlField.value = url;
                        }
                    };

                    if (typeField && urlHelp) {
                        typeField.addEventListener(
                            'change',
                            updateUiForType
                        );

                        typeField.addEventListener('change', function () {
                            if (typeField.value === 'amqp') {
                                syncAmqpUrl();
                            }
                        });

                        document
                            .querySelectorAll('.ih-amqp-sync')
                            .forEach(function (el) {
                                el.addEventListener(
                                    'input',
                                    syncAmqpUrl
                                );
                            });

                        updateUiForType();

                        if (typeField.value === 'amqp') {
                            syncAmqpUrl();
                        }
                    }
                } catch (e) {
                    Notification.exception(e);
                }

                try {
                    const elStatus =
                        document.getElementById('ih-chart-status');

                    if (typeof Chart !== 'undefined' && elStatus) {
                        const ctxStatus =
                            elStatus.getContext('2d');

                        new Chart(ctxStatus, {
                            type: 'doughnut',
                            data: {
                                labels: [
                                    strings.success,
                                    strings.failure
                                ],
                                datasets: [{
                                    data: [
                                        data.success || 0,
                                        data.fail || 0
                                    ],
                                    backgroundColor: [
                                        '#198754',
                                        '#dc3545'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    }

                    const elLatency =
                        document.getElementById('ih-chart-latency');

                    if (typeof Chart !== 'undefined' && elLatency) {
                        const ctxLatency =
                            elLatency.getContext('2d');

                        if (!data.labels ||
                            data.labels.length === 0) {
                            ctxLatency.font =
                                '14px sans-serif';

                            ctxLatency.fillStyle =
                                '#6c757d';

                            ctxLatency.textAlign =
                                'center';

                            ctxLatency.fillText(
                                'No latency data available yet',
                                elLatency.width / 2,
                                elLatency.height / 2
                            );
                        } else {
                            new Chart(ctxLatency, {
                                type: 'line',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label:
                                            strings.avglatency,
                                        data: data.latency,
                                        borderColor:
                                            '#0d6efd',
                                        backgroundColor:
                                            'rgba(13,110,253,0.1)',
                                        tension: 0.3,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });
                        }
                    }
                } catch (e) {
                    Notification.exception(e);
                }
            }
        };
    }
);
