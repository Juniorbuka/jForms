(function () {
    'use strict';

    async function submitForm(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var status = form.querySelector('[data-jforms-status]');
        var button = form.querySelector('button[type="submit"]');
        var csrf = form.querySelector('[name="_token"]');

        if (!form.reportValidity()) return;
        if (!status || !button || !csrf) return;

        status.textContent = 'Надсилання…';
        status.removeAttribute('data-state');
        button.disabled = true;

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf.value
                },
                credentials: 'same-origin',
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
            });
            var result = await response.json();

            if (!response.ok) {
                var firstError = result.errors && Object.values(result.errors)[0];
                throw new Error(firstError ? (Array.isArray(firstError) ? firstError[0] : firstError) : (result.message || 'Не вдалося надіслати форму.'));
            }

            form.reset();
            status.textContent = result.message || 'Дякуємо! Дані надіслано.';
            status.dataset.state = 'success';
        } catch (error) {
            status.textContent = error instanceof SyntaxError
                ? 'Сервер повернув некоректну відповідь. Спробуйте пізніше.'
                : (error.message || 'Помилка з’єднання. Спробуйте ще раз.');
            status.dataset.state = 'error';
        } finally {
            button.disabled = false;
        }
    }

    function init() {
        document.querySelectorAll('[data-jforms-form]').forEach(function (form) {
            form.addEventListener('submit', submitForm);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
