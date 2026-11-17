(function () {
    'use strict';

    async function getToken(form) {
        if (form._jformsTokenPromise) return form._jformsTokenPromise;

        form._jformsTokenPromise = (async function () {
            var response = await fetch(form.dataset.jformsTokenUrl, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            var result = await response.json();
            if (!response.ok || !result.token) {
                throw new Error(result.message || 'Не вдалося підготувати форму. Оновіть сторінку.');
            }
            var field = form.querySelector('[name="_jforms_token"]');
            if (!field) throw new Error('У формі відсутнє поле токена jForms.');
            field.value = result.token;
            return result.token;
        })().finally(function () {
            form._jformsTokenPromise = null;
        });

        return form._jformsTokenPromise;
    }

    async function submitForm(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var status = form.querySelector('[data-jforms-status]');
        var button = form.querySelector('button[type="submit"]');
        var csrf = form.querySelector('[name="_token"]');
        var token = form.querySelector('[name="_jforms_token"]');

        if (!form.reportValidity()) return;
        if (!status || !button || !token || !csrf) return;

        status.textContent = 'Надсилання…';
        status.removeAttribute('data-state');
        button.disabled = true;

        try {
            if (!token.value) await getToken(form);

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
                if (response.status === 419) {
                    token.value = '';
                    getToken(form).catch(function () {});
                }
                throw new Error(firstError ? (Array.isArray(firstError) ? firstError[0] : firstError) : (result.message || 'Не вдалося надіслати форму.'));
            }

            form.reset();
            token.value = result.next_token || '';
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
            getToken(form).catch(function (error) {
                var status = form.querySelector('[data-jforms-status]');
                if (status) {
                    status.textContent = error.message;
                    status.dataset.state = 'error';
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
