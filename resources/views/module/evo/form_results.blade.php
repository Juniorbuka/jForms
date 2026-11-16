@extends('jForms::module.evo.layout')
@php($t = fn (string $key, array $replace = []) => trans('jForms::module.' . $key, $replace, $uiLocale))
@section('title', $responses->first()?->form_name ?? $form)

@section('content')
    <a class="evo-ui-jforms__back" href="{{ route('jForms.index') }}"><x-evo::icon name="arrow-left" /> {{ $t('back') }}</a>
    <header class="evo-ui-surface__header evo-ui-jforms__header"><div><h1>{{ $responses->first()?->form_name ?? $form }}</h1><p>{{ $t('responses_description') }}</p></div><span class="evo-ui-badge">{{ $responses->total() }} {{ $t('count') }}</span></header>
    @if (session('status'))<div class="evo-ui-alert evo-ui-jforms__notice">{{ session('status') }}</div>@endif

    <form class="evo-ui-jforms__filters" method="get" action="{{ route('jForms.show', ['form' => $form]) }}">
        <label class="evo-ui-jforms__search">{{ $t('search') }}<input class="evo-ui-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $t('search_placeholder') }}"></label>
        <label>{{ $t('date_from') }}<input class="evo-ui-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
        <label>{{ $t('date_to') }}<input class="evo-ui-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
        <button class="evo-ui-btn evo-ui-btn--primary evo-ui-btn--filled" type="submit">{{ $t('apply_filters') }}</button>
        <a class="evo-ui-btn" href="{{ route('jForms.show', ['form' => $form]) }}">{{ $t('clear_filters') }}</a>
    </form>

    @if ($responses->isNotEmpty())
        <div class="evo-ui-jforms__bulk"><label><input type="checkbox" data-jforms-select-all> {{ $t('select_all') }}</label><form id="jforms-bulk-delete" method="post" action="{{ route('jForms.bulkDestroy', ['form' => $form]) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $t('confirm_bulk_delete') }}">@csrf</form><button class="evo-ui-btn evo-ui-btn--danger" type="submit" form="jforms-bulk-delete" data-jforms-bulk-delete disabled><x-evo::icon name="trash" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('bulk_delete') }}</span></button></div>
    @endif

    @if ($responses->isEmpty())
        <x-evo::card><p class="evo-ui-empty evo-ui-jforms__empty">{{ $t('empty') }}</p></x-evo::card>
    @else
        <div class="evo-ui-jforms__response-list">
            @foreach ($responses as $response)
                @php($fields = json_decode($response->data, true) ?: [])
                <x-evo::card class="evo-ui-jforms__response">
                    <div class="evo-ui-jforms__response-head"><label class="evo-ui-jforms__select"><input type="checkbox" name="ids[]" value="{{ $response->id }}" form="jforms-bulk-delete" class="jforms-select-response" aria-label="{{ $t('select_response', ['id' => $response->id]) }}"><strong>#{{ $response->id }}</strong></label><span class="evo-ui-jforms__date"><x-evo::icon name="clock" /> {{ $response->created_at }}</span></div>
                    <div class="evo-ui-jforms__data">
                        @foreach ($fields as $key => $value)
                            <div class="evo-ui-jforms__field"><span class="evo-ui-jforms__field-label">{{ $key }}</span><strong class="evo-ui-jforms__field-value">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
                        @endforeach
                    </div>
                    <div class="evo-ui-jforms__actions">
                        <a class="evo-ui-btn evo-ui-btn--primary evo-ui-btn--filled" href="{{ route('jForms.response', ['form' => $form, 'response' => $response->id]) }}"><x-evo::icon name="eye" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('view_response') }}</span></a>
                        <form method="post" action="{{ route('jForms.destroy', ['id' => $response->id]) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $t('confirm_delete') }}">@csrf<button class="evo-ui-btn evo-ui-btn--danger" type="submit"><x-evo::icon name="trash" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('delete') }}</span></button></form>
                    </div>
                </x-evo::card>
            @endforeach
        </div>
        <nav class="evo-ui-jforms__pagination" aria-label="{{ $t('pagination') }}">{{ $responses->links() }}</nav>
    @endif
    <script>
    document.querySelector('[data-jforms-select-all]')?.addEventListener('change', function () {
        document.querySelectorAll('.jforms-select-response').forEach((checkbox) => { checkbox.checked = this.checked; });
        document.querySelector('[data-jforms-bulk-delete]').disabled = !this.checked;
    });
    document.querySelectorAll('.jforms-select-response').forEach((checkbox) => checkbox.addEventListener('change', () => {
        document.querySelector('[data-jforms-bulk-delete]').disabled = !document.querySelector('.jforms-select-response:checked');
    }));
    </script>
@endsection
