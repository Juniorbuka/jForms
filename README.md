# jForms

[![Evolution CMS 3.5.7+](https://img.shields.io/badge/Evolution%20CMS-3.5.7%2B-blue.svg)](https://evo.im)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-8892BF.svg)](https://php.net)
[![License: GPL-3.0-or-later](https://img.shields.io/badge/License-GPL--3.0--or--later-green.svg)](LICENSE)

Secure AJAX form submission and response storage package for **Evolution CMS 3.5+**.

Build forms with clean Blade/HTML markup, define validation rules securely on the server (in configuration or controllers), and let **jForms** handle verification, spam prevention, rate limiting, and response storage with a native manager interface.

[🇺🇦 Документація українською мовою](#українська)

---

## Key Features

- 🛡️ **Signed One-Time CSRF Tokens** — Every submission requires a cryptographic token signed with HMAC-SHA256 (`UUID + timestamp + signature`). Once used, the token hash is permanently recorded to prevent replay attacks and race conditions.
- 🍯 **Honeypot Protection** — Invisible trap field (`website`) to quietly drop automated bot submissions without annoying users with CAPTCHA.
- 🚦 **Dual-Tier Rate Limiting** — Configurable per-IP rate limiting (e.g., 10 requests / min) plus global form rate limiting (e.g., 120 requests / min) to defend against botnets.
- 🧹 **Strict Allowlist & XSS Sanitization** — Only fields explicitly defined in server-side rules are accepted. Unlisted fields are discarded, and string values are sanitized before storage.
- ⚡ **Zero-Dependency Vanilla JS** — Lightweight client script (`jforms.min.js`, ~3 KB) with automatic background token prefetching and event hooks (`jforms:success`, `jforms:error`).
- 📊 **Manager Administration Module** — View, search, filter, and manage submissions in the Evolution CMS Manager. Automatically adopts **Evo UI** styling if installed.

---

## Requirements

- **Evolution CMS** `^3.5.7`
- **PHP** `^8.3`
- **Composer**

---

## Installation

Run from the Evolution CMS `core/` directory:

```bash
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
php artisan vendor:publish --tag=jforms-config
php artisan vendor:publish --tag=jforms-assets
```

Publishing copies the default config to `core/custom/config/jevo/jforms.php` and the assets to `public/assets/modules/jforms/jforms.min.js`.

> [!TIP]
> **Token Secret Configuration:** Ensure `APP_KEY` is set in your `.env`, or configure `'token_secret' => 'your-secret-key'` in `config/jevo/jforms.php` for strong HMAC signing.

---

## Quick Start (Config-driven)

### 1. Define Form Rules (`core/custom/config/jevo/jforms.php`)

```php
<?php

return [
    'forms' => [
        'contact-us' => [
            'name' => 'Contact Us Form',
            'success_message' => 'Thank you! We will get in touch with you shortly.',
            'rules' => [
                'name'    => ['required', 'string', 'max:120'],
                'phone'   => ['required', 'string', 'max:40'],
                'email'   => ['nullable', 'email:rfc', 'max:190'],
                'message' => ['nullable', 'string', 'max:2000'],
            ],
            'messages' => [
                'name.required'  => 'Please enter your name.',
                'phone.required' => 'Please enter your phone number.',
            ],
            'honeypot' => 'website',
            'rate_limit' => [
                'attempts'              => 10,  // Max attempts per IP
                'decay_seconds'         => 60,  // Per-IP window in seconds
                'global_attempts'       => 120, // Max global attempts
                'global_decay_seconds'  => 60,  // Global window
            ],
            'max_payload_bytes' => 16384,
            'token_ttl_seconds' => 3600,
        ],
    ],
];
```

### 2. Create Blade Template

```blade
<form data-jforms-form
      data-jforms-token-url="{{ route('jForms.token', ['form' => 'contact-us']) }}"
      action="{{ route('jForms.submit', ['form' => 'contact-us']) }}"
      method="post"
      novalidate>
    @csrf

    {{-- One-time cryptographic token --}}
    <input type="hidden" name="_jforms_token" value="{{ app(\Jevo\JForms\SubmissionService::class)->issueToken('contact-us') }}">

    {{-- Honeypot field (hidden from real users) --}}
    <div style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">
        <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="form-field">
        <label for="contact-name">Your Name</label>
        <input type="text" id="contact-name" name="name" required maxlength="120">
    </div>

    <div class="form-field">
        <label for="contact-phone">Phone Number</label>
        <input type="tel" id="contact-phone" name="phone" required maxlength="40">
    </div>

    <div class="form-field">
        <label for="contact-email">Email Address</label>
        <input type="email" id="contact-email" name="email" maxlength="190">
    </div>

    <div class="form-field">
        <label for="contact-message">Message</label>
        <textarea id="contact-message" name="message" rows="4" maxlength="2000"></textarea>
    </div>

    <button type="submit" class="btn">Send Request</button>

    {{-- Accessibility status container --}}
    <p data-jforms-status role="status" aria-live="polite"></p>
</form>
```

> [!NOTE]
> When Blade compiles a view containing `data-jforms-form`, the package automatically injects `/assets/modules/jforms/jforms.min.js`.

---

## JavaScript Events

jForms dispatches standard CustomEvents on the `form` element that bubble up to `document`:

```javascript
// Successful submission
document.addEventListener('jforms:success', function (event) {
    console.log('Form sent successfully:', event.detail.message);
    // e.g., show custom modal, toast, or trigger analytics
});

// Submission error (validation, network, or rate limit)
document.addEventListener('jforms:error', function (event) {
    console.error('Submission error:', event.detail.message, 'Status:', event.detail.status);
});
```

---

## Controller-driven Usage

You can also define forms directly in controller closures or methods without modifying configuration files:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Jevo\JForms\SubmissionService;

Route::middleware('web')->post('/api/quick-lead', function (Request $request, SubmissionService $jForms) {
    $result = $jForms->submitDefinition(
        formKey: 'quick-lead',
        formName: 'Quick Lead Form',
        input: $request->all(),
        rules: [
            'phone' => ['required', 'string', 'max:40'],
        ],
        ip: (string) $request->ip(),
        options: [
            'honeypot' => 'website',
            'rate_limit' => ['attempts' => 5, 'decay_seconds' => 60],
            'success_message' => 'Thanks! We will call you back.',
        ]
    );

    return response()->json(['ok' => true] + $result, 201);
});
```

---

## Configuration Reference

| Option | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | Form key | Human-readable form label in Manager module |
| `success_message` | `string` | Localized default | Message returned upon successful submission |
| `rules` | `array` | `[]` | Laravel validation rules. Only listed fields are accepted. |
| `messages` | `array` | `[]` | Custom validation error messages |
| `honeypot` | `string` | `'website'` | Field name that must be present and empty |
| `rate_limit.attempts` | `int` | `10` | Max submissions per IP within window |
| `rate_limit.decay_seconds` | `int` | `60` | Duration of per-IP window in seconds |
| `rate_limit.global_attempts` | `int` | `120` | Max total submissions across all IPs |
| `rate_limit.global_decay_seconds` | `int` | `60` | Duration of global window in seconds |
| `max_payload_bytes` | `int` | `16384` | Max JSON payload size allowed (16 KB) |
| `token_ttl_seconds` | `int` | `3600` | Expiration time for cryptographic tokens (1 hour) |

---

<a name="українська"></a>
# Українська

Пакет для безпечної обробки Blade-форм через AJAX та збереження відповідей у **Evolution CMS 3.5+**.

Створюйте форми за допомогою чистої Blade/HTML розмітки, визначайте правила валідації на сервері (у конфігурації або контролерах), а **jForms** візьме на себе перевірку, захист від спаму, лімітування запитів та збереження заявок із вбудованим модулем керування.

## Головні можливості

- 🛡️ **Одноразові підписані CSRF-токени** — Кожна відправка вимагає токен, підписаний HMAC-SHA256 (`UUID + timestamp + підпис`). Після використання хеш токена записується в БД, що повністю виключає повторні атаки та стан гонитви (race conditions).
- 🍯 **Honeypot-захист** — Прихована пастка (`website`) для тихого відсікання автоматичних ботів без набридливих CAPTCHA.
- 🚦 **Дворівневий Rate Limiting** — Незалежний ліміт на IP (наприклад, 10 спроб / хв) плюс загальний ліміт на всю форму (наприклад, 120 спроб / хв) для захисту від розподілених ботнетів.
- 🧹 **Allowlist-валідація та очищення XSS** — У базу потрапляють лише поля, явно описані в правилах сервера. Зайві поля ігноруються, а текст очищається від небезпечних HTML-тегів (`strip_tags`).
- ⚡ **Легкий Vanilla JS без залежностей** — Скрипт розміром ~3 KB (`jforms.min.js`) з фоновим prefetch токенів та подіями `jforms:success` і `jforms:error`.
- 📊 **Модуль керування для менеджера** — Перегляд, пошук, фільтрація за датами та видалення відповідей в адмін-панелі Evolution CMS. Підтримує тему **Evo UI**.

## Встановлення

З кореневої папки `core/` Evolution CMS:

```bash
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
php artisan vendor:publish --tag=jforms-config
php artisan vendor:publish --tag=jforms-assets
```

## Ліцензія

GNU General Public License версії 3.0 або пізнішої. Дивіться файл [LICENSE](LICENSE).
