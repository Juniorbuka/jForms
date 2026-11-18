(function () {
    'use strict';

    var messages = document.documentElement.lang.toLowerCase().startsWith('en')
        ? {
            sending: 'Sending…',
            success: 'Thank you! Your response was sent.',
            error: 'Unable to send the form. Please try again.',
            token: 'Unable to prepare the form. Reload the page.',
            invalidResponse: 'The server returned an invalid response. Please try again later.'
        }
        : {
            sending: 'Надсилання…',
            success: 'Дякуємо! Дані надіслано.',
            error: 'Не вдалося надіслати форму. Спробуйте ще раз.',
            token: 'Не вдалося підготувати форму. Оновіть сторінку.',
            invalidResponse: 'Сервер повернув некоректну відповідь. Спробуйте пізніше.'
        };

    async function fetchToken(form) {
        var response = await fetch(form.dataset.jformsTokenUrl, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        var result = await response.json();

        if (!response.ok || !result.token) {
            throw new Error(result.message || messages.token);
        }

        return result.token;
    }

    document.addEventListener('focusin', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) return;
        var form = target.closest('form[data-jforms-form]');
        if (!form) return;
        var tokenInput = form.querySelector('[name="_jforms_token"]');
        if (tokenInput && !tokenInput.value && form.dataset.jformsTokenUrl) {
            fetchToken(form).then(function (token) {
                if (token && !tokenInput.value) tokenInput.value = token;
            }).catch(function () {});
        }
    }, true);

    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-jforms-form]')) return;

        event.preventDefault();

        var status = form.querySelector('[data-jforms-status]');
        var button = form.querySelector('button[type="submit"], input[type="submit"]');
        var csrf = form.querySelector('[name="_token"]');
        var submissionToken = form.querySelector('[name="_jforms_token"]');

        if (!form.reportValidity() || !status || !button || !csrf || !submissionToken) return;

        status.textContent = messages.sending;
        status.removeAttribute('data-state');
        button.disabled = true;

        var response;
        try {
            if (!submissionToken.value) {
                submissionToken.value = await fetchToken(form);
            }
            response = await fetch(form.action, {
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
                throw new Error(firstError ? (Array.isArray(firstError) ? firstError[0] : firstError) : (result.message || messages.error));
            }

            form.reset();
            submissionToken.value = result.next_token || '';
            status.textContent = result.message || messages.success;
            status.dataset.state = 'success';
            form.dispatchEvent(new CustomEvent('jforms:success', {
                bubbles: true,
                detail: { message: status.textContent }
            }));
        } catch (error) {
            if (response && response.status === 419) {
                try {
                    submissionToken.value = await fetchToken(form);
                } catch (tokenError) {
                    submissionToken.value = '';
                }
            }

            status.textContent = error instanceof SyntaxError
                ? messages.invalidResponse
                : (error.message || messages.error);
            status.dataset.state = 'error';
            form.dispatchEvent(new CustomEvent('jforms:error', {
                bubbles: true,
                detail: { message: status.textContent, status: response ? response.status : null }
            }));
        } finally {
            button.disabled = false;
        }
    });
}());
