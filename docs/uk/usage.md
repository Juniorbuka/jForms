# Інструкція з використання jForms

jForms приймає відправлення форм, перевіряє їх на сервері, зберігає дозволені дані у форматі JSON і показує відповіді в менеджері. Форми створюються звичайною розміткою Blade/HTML; браузер не передає назву форми чи правила валідації.

## 1. Встановлення та підготовка бази даних

Виконайте команди з каталогу `core/` Evolution CMS:

```sh
php artisan package:installrequire jevo/jforms "^1.0"
php artisan migrate
php artisan vendor:publish --tag=jforms-config
```

Файл конфігурації буде опубліковано сюди:

```text
core/custom/config/jevo/jforms.php
```

Якщо в старій інсталяції є `core/custom/config/jforms.php`, перенесіть його налаштування `forms` у новий файл `jevo/jforms.php`. Поки нового файла немає, пакет тимчасово підтримує старий шлях.

## 2. Налаштування форм

Ключ масиву — це ключ форми. Використовуйте літери, цифри, `_` або `-`; ключ входить до POST-маршруту `/jforms/{form}`.

```php
<?php

return [
    'forms' => [
        'appointment' => [
            'name' => 'Запит на прийом',
            'success_message' => 'Дякуємо! Ми скоро з вами зв’яжемося.',
            'rules' => [
                'name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:40'],
                'email' => ['nullable', 'email:rfc', 'max:190'],
            ],
            'messages' => [
                'phone.required' => 'Вкажіть номер телефону.',
            ],
            'honeypot' => 'website',
            'rate_limit' => [
                'attempts' => 5,
                'decay_seconds' => 600,
            ],
        ],
    ],
];
```

Доступні налаштування:

| Налаштування | Призначення |
| --- | --- |
| `name` | Назва форми, яка відображається в менеджері. |
| `success_message` | Повідомлення після успішного збереження відповіді. |
| `rules` | Правила валідації Laravel. Ці ключі також визначають, які поля дозволено зберігати. |
| `messages` | Необов’язкові повідомлення Laravel, наприклад `phone.required`. |
| `honeypot` | Назва прихованого поля для відсікання ботів. За замовчуванням — `website`. |
| `rate_limit.attempts` | Кількість спроб з однієї IP-адреси для цієї форми за період. За замовчуванням — `5`. |
| `rate_limit.decay_seconds` | Період обмеження у секундах. За замовчуванням — `600`; значення менші за `60` буде збільшено до `60`. |

Працюють стандартні правила валідації Laravel. Поля, яких немає в `rules`, відкидаються, навіть якщо браузер їх надіслав. Якщо змінити `name`, попередні відповіді не зміняться: у них збережеться назва, яка була на момент відправлення.

## 3. Додайте Blade-форму

Маршрут пакета приймає JSON за адресою `POST /jforms/{form}`. Вкажіть налаштований ключ форми й додайте CSRF-токен:

```blade
<form id="appointment-form" action="{{ route('jForms.submit', ['form' => 'appointment']) }}" method="post">
    @csrf
    <label>
        Ім’я
        <input name="name" autocomplete="name" required maxlength="120">
    </label>
    <label>
        Телефон
        <input name="phone" type="tel" autocomplete="tel" required maxlength="40">
    </label>
    <label>
        Email
        <input name="email" type="email" autocomplete="email">
    </label>

    <div class="form-honeypot" aria-hidden="true">
        <label>Залиште це поле порожнім <input name="website" type="text" tabindex="-1" autocomplete="off"></label>
    </div>

    <button type="submit">Надіслати</button>
    <p id="appointment-status" role="status" aria-live="polite"></p>
</form>
```

Для `.form-honeypot` використовуйте CSS, який візуально приховує поле, але залишає його в HTML. Звичайний відвідувач має залишити це поле порожнім.

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

## 4. Надсилання через AJAX

```html
<script>
document.querySelector('#appointment-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    const status = document.querySelector('#appointment-status');
    const key = form.dataset.idempotencyKey || crypto.randomUUID();
    form.dataset.idempotencyKey = key;
    status.textContent = 'Надсилання…';

    try {
        const response = await fetch(form.action, {
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

        if (!response.ok) {
            const validationMessages = Object.values(result.errors || {}).flat();
            throw new Error(validationMessages.join(' ') || result.message || 'Не вдалося надіслати форму.');
        }

        status.textContent = result.message;
        form.reset();
        delete form.dataset.idempotencyKey;
    } catch (error) {
        status.textContent = error.message || 'Помилка з’єднання. Спробуйте ще раз.';
    }
});
</script>
```

Використовуйте HTTPS — тоді доступний `crypto.randomUUID()`. Виводьте повідомлення сервера через `textContent`. Під час повторної спроби надіслати ту саму відповідь використовуйте той самий ключ; для нового відправлення створюйте новий.

## 5. Захист і відповіді сервера

- **CSRF:** маршрут працює в middleware-групі `web`. Додайте `@csrf` і передавайте токен у заголовку `X-CSRF-TOKEN`.
- **Лише JSON:** надсилайте `Content-Type: application/json` і `Accept: application/json`.
- **Дозволені поля:** сервер перевіряє і зберігає лише поля, вказані у `rules`. Не формуйте правила чи назву форми з даних запиту.
- **Валідація:** перед записом Laravel перевіряє значення та обрізає пробіли в рядках. Помилки повертаються у JSON зі статусом `422`.
- **Honeypot:** заповнене приховане поле відхиляється зі статусом `422`.
- **Обмеження частоти:** ліміт ведеться окремо для кожної форми та IP-адреси. Після перевищення повертається `429`. Невдалі спроби також враховуються.
- **Захист від дублів:** кожен запит має містити UUID v4 у заголовку `Idempotency-Key`. Повторний запит з тим самим ключем для тієї самої форми не створює нового запису; у відповіді буде `duplicate: true`.
- **Збереження:** у JSON-колонку `data` таблиці `jforms_responses` записуються лише перевірені значення, а також ключ і назва форми та час відправлення. Завантаження файлів не підтримується.
- **Приватність даних:** jForms зберігає значення в базі як JSON і не шифрує їх. Зберігайте лише необхідну сайту інформацію, обмежте доступ до менеджера та бази даних і видаляйте застарілі записи.

Honeypot і обмеження частоти зменшують автоматизований спам, але не є CAPTCHA і не гарантують блокування всіх ботів.

Якщо запит не є JSON, сервер повертає `406`. Якщо ключ форми не налаштований — `404`.

## 6. Валідація і збереження у власному контролері

Для форми, якою керує ваш контролер, викликайте `submitDefinition()`. Назва форми й правила мають залишатися довіреними серверними значеннями:

```php
$result = $jForms->submitDefinition(
    'appointment',
    'Запит на прийом',
    array_replace($request->all(), [
        '_jforms_idempotency' => $request->header('Idempotency-Key'),
    ]),
    [
        'phone' => ['required', 'string', 'max:40'],
        'email' => ['nullable', 'email:rfc', 'max:190'],
    ],
    (string) $request->ip(),
    [
        'honeypot' => 'website',
        'rate_limit' => ['attempts' => 5, 'decay_seconds' => 600],
        'success_message' => 'Дякуємо! Ми скоро з вами зв’яжемося.',
    ]
);

return response()->json(['ok' => true] + $result, 201);
```

Зареєструйте POST-маршрут контролера в middleware-групі `web`. Такий спосіб використовує ту саму валідацію, honeypot, ліміт, захист від повторів і JSON-збереження.

## 7. Перегляд відповідей

Відкрийте в менеджері **«Відповіді форм»**. Виберіть форму, перегляньте її відправлення, відкрийте окрему відповідь або видаліть запис. Якщо встановлено Evo UI, jForms використовує тему менеджера Evo UI. Без Evo UI працює вбудований Blade-інтерфейс.

## Усунення типових проблем

- **Модуль повідомляє, що таблиці немає:** з каталогу сайту `core/` виконайте `php artisan migrate`, підключившись до тієї самої бази, що використовує сайт.
- **Ключ форми повертає 404:** перевірте наявність ключа у `forms` файлу `core/custom/config/jevo/jforms.php`; якщо ввімкнений кеш конфігурації, очистіть його.
- **Запит повертає 419:** перезавантажте сторінку для отримання нового CSRF-токена й перевірте, що POST-маршрут працює в middleware `web`.
- **Запит повертає 406:** надсилайте JSON із заголовками `Content-Type: application/json` та `Accept: application/json`.
- **Після оновлення пакета старий вигляд модуля:** з каталогу `core/` виконайте `php artisan view:clear`.

