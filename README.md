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
            '_jforms_cookie_token' => $request->cookie($jForms->cookieNameForForm('appointment')),
        ]),
        [
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'child_name' => ['nullable', 'string', 'max:120'],
        ],
        (string) $request->ip(),
        [
            'honeypot' => 'website',
            'rate_limit' => ['attempts' => 10, 'decay_seconds' => 60],
            'max_payload_bytes' => 16384,
            'success_message' => 'Thank you. We will contact you soon.',
        ]
    );

    return response()->json(['ok' => true] + $result, 201);
});
```

`submitDefinition()` validates only fields listed in the server rules, discards other request keys, rate-limits submissions by IP and form (10 attempts per minute by default), requires an empty honeypot field, caps the payload at 16 KB, and requires a random, signed, form-bound, expiring one-time token that must match its HttpOnly cookie. The server uses that token as the database uniqueness key, so replay is prevented even if two requests arrive together. A fresh token is returned after a successful save. CSRF is enforced by the `web` middleware. These measures reduce automated abuse without CAPTCHA; they do not guarantee that every bot is blocked.

## Blade form and AJAX

Register the form in `core/custom/config/jevo/jforms.php`, then use the package routes and JavaScript asset:

```blade
<form id="appointment-form"
      data-jforms-form
      data-jforms-token-url="{{ route('jForms.token', ['form' => 'appointment']) }}"
      action="{{ route('jForms.submit', ['form' => 'appointment']) }}"
      method="post">
    @csrf
    <input type="hidden" name="_jforms_token" value="">
    <input type="tel" name="phone" required>
    <input type="email" name="email">
    <input type="text" name="child_name">
    <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="d-none">
    <button type="submit">Send</button>
    <p data-jforms-status role="status" aria-live="polite"></p>
</form>
<script src="{{ route('jForms.asset') }}" defer></script>
```

The package script fetches a token when the page opens, sends JSON with the CSRF token, and updates the hidden token after success. It is served from the package and can be included only on pages that use jForms.

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

`submitDefinition()` перевіряє лише поля, задані сервером, відкидає інші ключі запиту, обмежує частоту надсилань за IP та формою (за замовчуванням 10 спроб за хвилину), вимагає порожнє поле honeypot, обмежує запит до 16 КБ і вимагає випадковий одноразовий токен, прив’язаний до сесії та форми. Сервер використовує токен як унікальний ключ у базі, тому повторне чи паралельне надсилання не створює другу відповідь. Після успішного збереження сервер повертає новий токен. CSRF перевіряється middleware-групою `web`. Перевірки зменшують автоматизований спам без CAPTCHA, але не гарантують блокування всіх ботів.

## Ліцензія

GNU General Public License версії 3 або новішої. Повний текст — у файлі [LICENSE](LICENSE).
