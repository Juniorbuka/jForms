# jForms usage guide

jForms accepts server-validated form submissions, stores the validated values as JSON, and provides a manager module to review them. Forms are ordinary Blade/HTML; the browser never supplies the form name or validation rules.

## 1. Install and prepare the database

From the Evolution CMS `core/` directory:

```sh
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
php artisan vendor:publish --tag=jforms-config
```

The configuration file is published to:

```text
core/custom/config/jevo/jforms.php
```

If an older installation has `core/custom/config/jforms.php`, move its `forms` configuration into the new `jevo/jforms.php` file. The package temporarily supports the old path when the new file is absent.

## 2. Configure forms

Each array key is a form key. Use letters, numbers, `_`, or `-`; the key becomes part of the POST route `/jforms/{form}`.

```php
<?php

return [
    'forms' => [
        'appointment' => [
            'name' => 'Appointment request',
            'success_message' => 'Thank you. We will contact you soon.',
            'rules' => [
                'name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:40'],
                'email' => ['nullable', 'email:rfc', 'max:190'],
            ],
            'messages' => [
                'phone.required' => 'Enter a phone number.',
            ],
            'honeypot' => 'website',
            'rate_limit' => [
                'attempts' => 10,
                'decay_seconds' => 60,
            ],
        ],
    ],
];
```

Available settings:

| Setting | Purpose |
| --- | --- |
| `name` | Human-readable form name shown in the manager. |
| `success_message` | Message returned after the response is saved. |
| `rules` | Laravel validation rules. These keys are also the allowlist of stored fields. |
| `messages` | Optional Laravel validation messages, such as `phone.required`. |
| `honeypot` | Name of the hidden anti-bot field. Defaults to `website`. |
| `rate_limit.attempts` | Number of allowed attempts per IP address and form during the time window. Defaults to `10`. |
| `rate_limit.decay_seconds` | Rate-limit window in seconds. Defaults to `60`; values below `60` are raised to `60`. |
| `max_payload_bytes` | Maximum request size in bytes. Defaults to `16384` (16 KB). |

Use standard Laravel validation rules. Fields absent from `rules` are discarded even if a browser submits them. A `name` change does not alter old responses; records retain the name saved at submission time.

## 3. Add a Blade form

The package route accepts JSON at `POST /jforms/{form}`. Include the form key configured above and a CSRF token:

```blade
<form id="appointment-form" data-jforms-form action="{{ route('jForms.submit', ['form' => 'appointment']) }}" method="post">
    @csrf
    <label>
        Name
        <input name="name" autocomplete="name" required maxlength="120">
    </label>
    <label>
        Phone
        <input name="phone" type="tel" autocomplete="tel" required maxlength="40">
    </label>
    <label>
        Email
        <input name="email" type="email" autocomplete="email">
    </label>

    <div class="form-honeypot" aria-hidden="true">
        <label>Leave this field empty <input name="website" type="text" tabindex="-1" autocomplete="off"></label>
    </div>

    <button type="submit">Send</button>
    <p id="appointment-status" role="status" aria-live="polite"></p>
</form>
```

Use visually-hidden CSS for `.form-honeypot` rather than removing it from the HTML. The field must remain empty for a normal visitor.

```css
.form-honeypot {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
```

## 4. Send with AJAX

Include the JavaScript file shipped in the package. It posts JSON with the CSRF token and displays the result in `[data-jforms-status]`:

```blade
<p id="appointment-status" data-jforms-status role="status" aria-live="polite"></p>
<script src="{{ route('jForms.asset') }}" defer></script>
```

`jforms.js` belongs to the package and does not depend on the theme's JavaScript. Include it only on pages that use jForms.

## 5. Protection and response behavior

- **CSRF:** the endpoint runs in the `web` middleware group. The form must include `@csrf`; send its token as `X-CSRF-TOKEN`.
- **JSON-only endpoint:** send `Content-Type: application/json` and `Accept: application/json`.
- **Server-side allowlist:** only fields named in `rules` are validated and stored. Never build rules or form names from request input.
- **Validation:** Laravel validates values before insertion and trims string values. Invalid data returns HTTP `422` with JSON validation errors.
- **Honeypot:** the hidden field must be present and empty; a filled or missing field is rejected with HTTP `422`.
- **Rate limiting:** attempts are limited independently by form and client IP. Exceeding the limit returns HTTP `429`. Invalid attempts also count toward the limit.
- **Request size:** requests larger than `max_payload_bytes` are rejected with HTTP `413`.
- **Storage:** only validated values are saved in the JSON `data` column of `jforms_responses`, along with the form key/name and timestamp. File uploads are not supported.
- **Data privacy:** values are stored in the database as JSON and are not encrypted by jForms. Store only the information the site needs, restrict manager/database access, and remove records when they are no longer needed.

The honeypot and rate limit reduce automated abuse but are not a CAPTCHA or a guarantee that every bot will be blocked.

The endpoint also returns `406` when the request is not JSON. A form key that is not configured returns `404`.

## 6. Define validation and storage in your controller instead

For controller-owned forms, use `submitDefinition()`. Its rules and form name must still be constants or trusted server-side values. ```php
$result = $jForms->submitDefinition(
    'appointment',
    'Appointment request',
    $request->all(),
    [
        'phone' => ['required', 'string', 'max:40'],
        'email' => ['nullable', 'email:rfc', 'max:190'],
    ],
    (string) $request->ip(),
    [
        'honeypot' => 'website',
        'rate_limit' => ['attempts' => 5, 'decay_seconds' => 600],
        'success_message' => 'Thank you. We will contact you soon.',
    ]
);

return response()->json(['ok' => true] + $result, 201);
```

Register the controller's POST route inside `web` middleware. This method uses the same validation, honeypot, rate limit, and JSON storage behavior.

## 7. Review saved responses

Open **Form responses** in the Evolution manager. Select a form to view its submissions, open an individual response for all fields, or delete a record. If Evo UI is installed, jForms uses the manager's Evo UI theme. Without it, jForms uses its built-in Blade interface.

## Troubleshooting

- **The module says its table is missing:** from the site's `core/` directory run `php artisan migrate` against the same database used by the website.
- **A form key returns 404:** check that the key exists under `forms` in `core/custom/config/jevo/jforms.php` and clear the application configuration cache if enabled.
- **The request returns 419:** check the CSRF token and confirm the POST route uses `web` middleware.
- **The request returns 406:** send a JSON body with `Content-Type: application/json` and `Accept: application/json`.
- **The manager layout did not change after a package update:** clear compiled views with `php artisan view:clear` from `core/`.

