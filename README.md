# jForms

Secure AJAX form submission and response storage for Evolution CMS 3. Build forms with ordinary Blade/HTML, define validation rules in trusted server-side configuration or controller code, and let jForms validate and store responses as JSON.

[Українська версія](#українська)

Usage guides: [English](docs/en/usage.md) · [Українська](docs/uk/usage.md)

## Requirements

- Evolution CMS `^3.5.7`
- PHP `^8.3`
- Composer

## Installation

Run from the Evolution CMS `core/` directory:

```sh
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
```

The migration creates the `jforms_responses` table. The manager module **Form responses / Відповіді форм** lists forms with responses and lets authorized manager users browse and delete submissions. When Evo UI is installed, the module uses its manager theme and components; otherwise, it uses the built-in Blade interface.

To publish the default configuration to `core/custom/config/jevo/jforms.php`:

```sh
php artisan vendor:publish --tag=jforms-config
```

File uploads are not supported.

## Controller

Register a POST route inside the `web` middleware group so CSRF protection applies. Keep form names and validation rules in trusted PHP code; never accept them from the request.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Jevo\JForms\SubmissionService;

Route::middleware('web')->post('/appointment', function (Request $request, SubmissionService $jForms) {
    $result = $jForms->submitDefinition(
        'appointment',
        'Appointment request',
        array_replace($request->all(), [
            '_jforms_idempotency' => $request->header('Idempotency-Key'),
        ]),
        [
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'child_name' => ['nullable', 'string', 'max:120'],
        ],
        (string) $request->ip(),
        [
            'honeypot' => 'website',
            'rate_limit' => ['attempts' => 5, 'decay_seconds' => 600],
            'success_message' => 'Thank you. We will contact you soon.',
        ]
    );

    return response()->json(['ok' => true] + $result, 201);
});
```

`submitDefinition()` validates only fields listed in the server rules, discards other request keys, rate-limits submissions by IP and form, checks a honeypot, prevents duplicate submissions using `Idempotency-Key`, and stores validated values in JSON. Validation errors return Laravel's JSON response with status `422`.

## Blade form and AJAX

```blade
<form id="appointment-form">
    @csrf
    <input type="tel" name="phone" required>
    <input type="email" name="email">
    <input type="text" name="child_name">
    <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="d-none">
    <button type="submit">Send</button>
    <p id="appointment-message" aria-live="polite"></p>
</form>
<script>
document.querySelector('#appointment-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const key = form.dataset.submissionKey || crypto.randomUUID();
    form.dataset.submissionKey = key;
    const response = await fetch('/appointment', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            'Idempotency-Key': key,
        },
        body: JSON.stringify(Object.fromEntries(new FormData(form).entries())),
    });
    const result = await response.json();
    const message = document.querySelector('#appointment-message');
    message.textContent = response.ok ? result.message : Object.values(result.errors || {}).flat().join(' ');
    if (response.ok) {
        form.reset();
        delete form.dataset.submissionKey;
    }
});
</script>
```

Use `textContent` when displaying server responses. Serve the site over HTTPS so `crypto.randomUUID()` is available.

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).

---

# Українська

Докладні інструкції: [українською](docs/uk/usage.md) · [English](docs/en/usage.md)

`jForms` — пакет для AJAX-відправлення Blade-форм і збереження відповідей у JSON для Evolution CMS 3. Розмітку форми пишіть звичайним Blade/HTML, правила перевірки задавайте у довіреній серверній конфігурації або коді контролера.

## Вимоги

- Evolution CMS `^3.5.7`
- PHP `^8.3`
- Composer

## Встановлення

Виконайте команди з каталогу `core/` Evolution CMS:

```sh
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
```

Міграція створює таблицю `jforms_responses`. У менеджері з’явиться модуль **«Відповіді форм» / “Form responses”**. Він показує форми з відповідями, дозволяє переглядати їх і видаляти відповідно до прав менеджера. Якщо встановлено Evo UI, модуль використовує його тему й компоненти; без Evo UI працює вбудований Blade-інтерфейс.

Щоб опублікувати стандартну конфігурацію в `core/custom/config/jevo/jforms.php`:

```sh
php artisan vendor:publish --tag=jforms-config
```

Завантаження файлів поки не підтримується.

## Контролер і Blade

Зареєструйте POST-маршрут у middleware-групі `web`, щоб діяла CSRF-перевірка. Назву форми й правила валідації задавайте в довіреному PHP-коді сервера, а не в запиті браузера. Приклади контролера та Blade/AJAX-форми наведені в англійській секції вище.

`submitDefinition()` перевіряє лише поля, задані сервером, відкидає інші ключі запиту, обмежує частоту відправлень за IP та формою, перевіряє honeypot і захищає від повторного збереження за `Idempotency-Key`. Валідовані дані записуються у JSON. Помилки валідації повертаються у JSON зі статусом `422`.

## Ліцензія

GNU General Public License версії 3 або новішої. Повний текст — у файлі [LICENSE](LICENSE).
