@include('jForms::module.styles')
<main class="jf-admin">
    <header class="jf-heading">
        <span class="jf-heading__icon">@svg('tabler-inbox', '', ['width' => 25, 'height' => 25, 'aria-hidden' => 'true'])</span>
        <div><h1>{{ __('jForms::module.title') }}</h1><p>{{ __('jForms::module.forms_description') }}</p></div>
    </header>

    @if (session('status'))<div class="jf-notice">{{ session('status') }}</div>@endif

    @if ($responses->isEmpty())
        <div class="jf-empty">@svg('tabler-inbox', '', ['width' => 32, 'height' => 32, 'aria-hidden' => 'true'])<p>{{ __('jForms::module.empty') }}</p></div>
    @else
        <div class="jf-grid">
            @foreach ($responses as $response)
                <a class="jf-form-card" href="{{ route('jForms.show', ['form' => $response->form_key]) }}">
                    <span class="jf-form-card__icon">@svg('tabler-file-text', '', ['width' => 22, 'height' => 22, 'aria-hidden' => 'true'])</span>
                    <span class="jf-form-card__main"><strong>{{ $response->form_name }}</strong><small>{{ $response->form_key }}</small></span>
                    <span class="jf-count">{{ $response->total }} {{ __('jForms::module.count') }}</span>
                    <span class="jf-form-card__date"><small>{{ __('jForms::module.latest') }}</small>{{ $response->latest }}</span>
                    @svg('tabler-chevron-right', '', ['width' => 18, 'height' => 18, 'class' => 'jf-chevron', 'aria-hidden' => 'true'])
                </a>
            @endforeach
        </div>
    @endif
</main>
