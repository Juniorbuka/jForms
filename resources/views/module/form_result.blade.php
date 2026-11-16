@include('jForms::module.styles')
<main class="jf-admin">
    <a class="jf-back" href="{{ route('jForms.show', ['form' => $form]) }}">@svg('tabler-arrow-left', '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) {{ __('jForms::module.back_to_responses') }}</a>
    <header class="jf-heading">
        <span class="jf-heading__icon">@svg('tabler-file-text', '', ['width' => 25, 'height' => 25, 'aria-hidden' => 'true'])</span>
        <div><h1>{{ $result->form_name }}</h1><p>#{{ $result->id }} · {{ __('jForms::module.submitted_at') }}: {{ $result->created_at }}</p></div>
    </header>

    <section class="jf-response-card jf-detail-card">
        <h2>{{ __('jForms::module.response_data') }}</h2>
        <div class="jf-data-grid jf-data-grid--detail">
            @foreach ($fields as $key => $value)
                <div class="jf-data-item"><span>{{ $key }}</span><strong>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
            @endforeach
        </div>
        <form method="post" action="{{ route('jForms.destroy', ['id' => $result->id]) }}" onsubmit="return confirm(@json(__('jForms::module.confirm_delete')))" >
            @csrf
            <button class="jf-button jf-button--danger" type="submit">@svg('tabler-trash', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ __('jForms::module.delete') }}</button>
        </form>
    </section>
</main>
