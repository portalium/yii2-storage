(function () {
    'use strict';

    var config = window.StorageProviderConfig || {};

    // Import sources authenticate through OAuth, so they have no endpoint or
    // bucket to fill in; only storage backends show the S3 fields.
    var SOURCE_TYPES = ['gdrive', 'dropbox'];

    function toggleFields() {
        var typeField = document.getElementById('provider-type');
        var s3Fields = document.getElementById('provider-s3-fields');

        if (typeField && s3Fields) {
            var isSource = SOURCE_TYPES.indexOf(typeField.value) !== -1;
            s3Fields.hidden = isSource || typeField.value === 'local';
        }

    }


    function showResult(ok, message) {
        var box = document.getElementById('provider-test-result');

        if (!box) {
            return;
        }

        box.innerHTML = '';

        var alert = document.createElement('div');
        alert.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
        alert.textContent = message;
        box.appendChild(alert);
    }

    function runTest() {
        var form = document.getElementById('provider-form');
        var button = document.getElementById('provider-test-button');

        if (!form || !button) {
            return;
        }

        var data = new FormData(form);

        if (config.idProvider) {
            data.append('id_provider', config.idProvider);
        }

        button.disabled = true;
        showResult(true, (config.messages && config.messages.testing) || 'Testing...');

        fetch(config.testUrl, {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                showResult(!!result.success, result.message || '');
            })
            .catch(function (error) {
                showResult(false, String(error));
            })
            .finally(function () {
                button.disabled = false;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var typeField = document.getElementById('provider-type');
        var testButton = document.getElementById('provider-test-button');

        if (typeField) {
            typeField.addEventListener('change', toggleFields);
        }

        toggleFields();

        if (testButton) {
            testButton.addEventListener('click', runTest);
        }
    });
})();
