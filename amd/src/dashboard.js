/* global Chart */
define([], function () {
    return {
        init: function (data, strings) {
            if (typeof Chart === 'undefined') {
                return;
            }

            // ---- Charts Logic ----

            // Status Chart (Doughnut)
            const elStatus = document.getElementById('ih-chart-status');
            if (elStatus) {
                const ctxStatus = elStatus.getContext('2d');
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: [strings.success, strings.failure],
                        datasets: [{
                            data: [data.success || 0, data.fail || 0],
                            backgroundColor: ['#198754', '#dc3545'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        }
                    }
                });
            }

            // Latency Chart (Line)
            const elLatency = document.getElementById('ih-chart-latency');
            if (elLatency) {
                const ctxLatency = elLatency.getContext('2d');
                if (!data.labels || data.labels.length === 0) {
                    ctxLatency.font = "14px sans-serif";
                    ctxLatency.fillStyle = "#6c757d";
                    ctxLatency.textAlign = "center";
                    ctxLatency.fillText("No latency data available yet", elLatency.width / 2, elLatency.height / 2);
                } else {
                    new Chart(ctxLatency, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: strings.avglatency,
                                data: data.latency,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: data.labels.length > 50 ? 0 : 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        autoSkip: true,
                                        maxRotation: 0
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: { display: true, text: 'ms' }
                                }
                            }
                        }
                    });
                }
            }

            // ---- Form Toggle Logic ----
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

            // ---- Dynamic URL Help & Builder ----
            const typeField = document.getElementById('ih-type');
            const urlField = document.getElementById('ih-base_url');
            const urlHelp = document.getElementById('ih-base_url-help');
            const amqpBuilder = document.getElementById('ih-amqp-builder');

            if (typeField && urlHelp) {
                /**
                 * Update the UI fields visibility and help text based on the selected service type.
                 * @returns {void}
                 */
                const updateUiForType = function () {
                    const type = typeField.value || 'rest';
                    if (type === 'amqp') {
                        urlHelp.textContent = strings.url_help_amqp;
                        if (amqpBuilder) {
                            amqpBuilder.classList.remove('d-none');
                        }
                    } else {
                        urlHelp.textContent = strings.url_help_rest;
                        if (amqpBuilder) {
                            amqpBuilder.classList.add('d-none');
                        }
                    }
                };

                /**
                 * Build the AMQP connection URL from the individual builder fields.
                 * @returns {void}
                 */
                const syncAmqpUrl = function () {
                    const host = document.getElementById('ih-amqp_host').value || 'localhost';
                    const port = document.getElementById('ih-amqp_port').value || '5672';
                    const user = document.getElementById('ih-amqp_user').value || 'guest';
                    const pass = document.getElementById('ih-amqp_pass').value || 'guest';
                    let vhost = document.getElementById('ih-amqp_vhost').value || '/';

                    if (vhost !== '/' && vhost.startsWith('/')) {
                        vhost = vhost.substring(1);
                    }

                    const scheme = (port === '5671') ? 'amqps' : 'amqp';
                    urlField.value = `${scheme}://${user}:${pass}@${host}:${port}/${vhost}`;
                };
                typeField.addEventListener('change', updateUiForType);
                document.querySelectorAll('.ih-amqp-sync').forEach(el => {
                    el.addEventListener('input', syncAmqpUrl);
                });
                updateUiForType();
            }
        }
    };
});
