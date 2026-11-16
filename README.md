# jForms

Secure AJAX submission and response storage for Evolution CMS 3. Forms are ordinary Blade/HTML markup; field allowlists and Laravel validation rules are configured on the server.

## Install

From `core/`, install the Composer package, then migrate:

```sh
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
```

## Configure a form

Copy `config/jforms.php` to `core/custom/config/jforms.php`, then define form keys and server-side rules under `forms`. Only keys listed in `rules` are retained. Example:

```php
'callback' => [
    'name' => 'Зворотний дзвінок',
    'rules' => ['phone' => ['required', 'string', 'max:40']],
    'honeypot' => 'website',
    'rate_limit' => ['attempts' => 5, 'decay_seconds' => 600],
],
```

## Blade form

Render ordinary fields and post JSON to `route('jForms.submit', ['form' => 'callback'])`. Include Laravel's CSRF token in a header or form field. The request must accept JSON; successful responses return `{ "ok": true, "message": "..." }` and validation errors use Laravel's JSON error response.

```blade
<form id="callback-form">
    @csrf
    <input type="tel" name="phone" required>
    <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="d-none">
    <button type="submit">Надіслати</button>
</form>
<script>
document.querySelector('#callback-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const body = Object.fromEntries(new FormData(form).entries());
    const key = form.dataset.submissionKey || crypto.randomUUID();
    form.dataset.submissionKey = key;
    const response = await fetch(@json(route('jForms.submit', ['form' => 'callback'])), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            'Idempotency-Key': key,
        },
        body: JSON.stringify(body),
    });
    const result = await response.json();
    if (response.ok) {
        form.reset();
        delete form.dataset.submissionKey;
    }
    // Render result/errors with textContent in the site's UI.
});
</script>
```

For a controller-owned form, put the validation rules in the controller and delegate validation/storage to jForms:

```php
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;

public function submitAppointment(Request $request, SubmissionService $jForms)
{
    $result = $jForms->submitDefinition(
        'appointment',
        'Запис на прийом',
        array_replace($request->all(), [
            '_jforms_idempotency' => $request->header('Idempotency-Key'),
        ]),
        [
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'child_name' => ['nullable', 'string', 'max:120'],
        ],
        (string) $request->ip(),
        ['success_message' => 'Дякуємо! Ми зв’яжемося з вами.']
    );

    return response()->json(['ok' => true] + $result, 201);
}
```

Register that controller action as a POST route inside the `web` middleware group. The Blade template remains ordinary HTML, and the rules/options must be trusted PHP code. This reuses jForms rate limiting, duplicate submission protection, and storage.

Uploads are not supported. Responses are stored in the `jforms_responses` table and can be reviewed in the manager module **Відповіді форм**.
