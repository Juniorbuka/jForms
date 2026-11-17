@include('jForms::module.styles')
<main class="jf-admin">
    <a class="jf-back" href="{{ route('jForms.index') }}">@svg('tabler-arrow-left', '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) {{ __('jForms::module.back') }}</a>
    <header class="jf-heading jf-heading--spread">
        <div><h1>{{ $responses->first()?->form_name ?? $form }}</h1><p>{{ __('jForms::module.responses_description') }}</p></div>
        <span class="jf-count jf-count--large">{{ $responses->total() }} {{ __('jForms::module.count') }}</span>
    </header>

    @if (session('status'))<div class="jf-notice">{{ session('status') }}</div>@endif

    <form class="jf-filters" method="get" action="{{ route('jForms.show', ['form' => $form]) }}">
        <label class="jf-search">{{ __('jForms::module.search') }}<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('jForms::module.search_placeholder') }}"></label>
        <label>{{ __('jForms::module.date_from') }}<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
        <label>{{ __('jForms::module.date_to') }}<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
        <button class="jf-button jf-button--primary" type="submit">{{ __('jForms::module.apply_filters') }}</button>
        <a class="jf-button jf-button--plain" href="{{ route('jForms.show', ['form' => $form]) }}">{{ __('jForms::module.clear_filters') }}</a>
    </form>

    @if ($responses->isNotEmpty())
        <div class="jf-bulk-bar">
            <label><input type="checkbox" data-jforms-select-all> {{ __('jForms::module.select_all') }}</label>
            <form id="jforms-bulk-delete" method="post" action="{{ route('jForms.bulkDestroy', ['form' => $form]) }}" onsubmit="return confirm(@json(__('jForms::module.confirm_bulk_delete')))">@csrf</form>
            <button class="jf-button jf-button--danger" type="submit" form="jforms-bulk-delete" data-jforms-bulk-delete disabled>@svg('tabler-trash', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ __('jForms::module.bulk_delete') }}</button>
        </div>
    @endif

    @if ($responses->isEmpty())
        <div class="jf-empty">@svg('tabler-inbox', '', ['width' => 32, 'height' => 32, 'aria-hidden' => 'true'])<p>{{ __('jForms::module.empty') }}</p></div>
    @else
        <div class="jf-response-list">
            @foreach ($responses as $response)
                @php($fields = json_decode($response->data, true) ?: [])
                <section class="jf-response-card">
                    <div class="jf-response-card__head">
                        <label class="jf-select-response"><input type="checkbox" name="ids[]" value="{{ $response->id }}" form="jforms-bulk-delete" class="jforms-select-response" aria-label="{{ __('jForms::module.select_response', ['id' => $response->id]) }}"><strong class="jf-response-id">#{{ $response->id }}</strong></label>
                        <span class="jf-date">@svg('tabler-clock', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) {{ $response->created_at }}</span>
                    </div>
                    <div class="jf-response-card__row">
                        <div class="jf-data-grid">
                            @foreach ($fields as $key => $value)
                                <div class="jf-data-item"><span>{{ $key }}</span><strong>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
                            @endforeach
                        </div>
                        <div class="jf-response-card__actions">
                            <a class="jf-button jf-button--icon" href="{{ route('jForms.response', ['form' => $form, 'response' => $response->id]) }}" aria-label="{{ __('jForms::module.view_response') }}" title="{{ __('jForms::module.view_response') }}">@svg('tabler-eye', '', ['width' => 17, 'height' => 17, 'aria-hidden' => 'true'])</a>
                            <form method="post" action="{{ route('jForms.destroy', ['id' => $response->id]) }}" onsubmit="return confirm(@json(__('jForms::module.confirm_delete')))" >@csrf<button class="jf-button jf-button--danger jf-button--icon" type="submit" aria-label="{{ __('jForms::module.delete') }}" title="{{ __('jForms::module.delete') }}">@svg('tabler-trash', '', ['width' => 17, 'height' => 17, 'aria-hidden' => 'true'])</button></form>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
        <nav class="jf-pagination" aria-label="{{ __('jForms::module.pagination') }}">{{ $responses->links() }}</nav>
    @endif
</main>
<script>
document.querySelector('[data-jforms-select-all]')?.addEventListener('change', function () {
    document.querySelectorAll('.jforms-select-response').forEach((checkbox) => { checkbox.checked = this.checked; });
    document.querySelector('[data-jforms-bulk-delete]').disabled = !this.checked;
});
document.querySelectorAll('.jforms-select-response').forEach((checkbox) => checkbox.addEventListener('change', () => {
    document.querySelector('[data-jforms-bulk-delete]').disabled = !document.querySelector('.jforms-select-response:checked');
}));
</script>
