@include('jForms::module.styles')
<main class="jf-admin">
    <a class="jf-back" href="{{ route('jForms.index') }}">@svg('tabler-arrow-left', '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) {{ __('jForms::module.back') }}</a>
    <header class="jf-heading jf-heading--spread">
        <div><h1>{{ $responses->first()?->form_name ?? $form }}</h1><p>{{ __('jForms::module.responses_description') }}</p></div>
        <span class="jf-count jf-count--large">{{ $responses->total() }} {{ __('jForms::module.count') }}</span>
    </header>

    @if ($responses->isEmpty())
        <div class="jf-empty">@svg('tabler-inbox', '', ['width' => 32, 'height' => 32, 'aria-hidden' => 'true'])<p>{{ __('jForms::module.empty') }}</p></div>
    @else
        <div class="jf-response-list">
            @foreach ($responses as $response)
                @php($fields = json_decode($response->data, true) ?: [])
                <section class="jf-response-card">
                    <div class="jf-response-card__head">
                        <strong class="jf-response-id">#{{ $response->id }}</strong>
                        <span class="jf-date">@svg('tabler-clock', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ $response->created_at }}</span>
                    </div>
                    <div class="jf-data-grid">
                        @foreach ($fields as $key => $value)
                            <div class="jf-data-item"><span>{{ $key }}</span><strong>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
                        @endforeach
                    </div>
                    <div class="jf-response-card__actions">
                        <a class="jf-button jf-button--primary" href="{{ route('jForms.response', ['form' => $form, 'response' => $response->id]) }}">@svg('tabler-eye', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ __('jForms::module.view_response') }}</a>
                        <form method="post" action="{{ route('jForms.destroy', ['id' => $response->id]) }}" onsubmit="return confirm(@json(__('jForms::module.confirm_delete')))" >
                            @csrf
                            <button class="jf-button jf-button--danger" type="submit">@svg('tabler-trash', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ __('jForms::module.delete') }}</button>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>
        {{ $responses->links() }}
    @endif
</main>
