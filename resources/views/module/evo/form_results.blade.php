@extends('jForms::module.evo.layout')
@php($t = fn (string $key) => trans('jForms::module.' . $key, [], $uiLocale))
@section('title', $responses->first()?->form_name ?? $form)

@section('content')
    <a class="evo-ui-jforms__back" href="{{ route('jForms.index') }}"><x-evo::icon name="arrow-left" /> {{ $t('back') }}</a>
    <header class="evo-ui-surface__header evo-ui-jforms__header"><div><h1>{{ $responses->first()?->form_name ?? $form }}</h1><p>{{ $t('responses_description') }}</p></div><span class="evo-ui-badge">{{ $responses->total() }} {{ $t('count') }}</span></header>
    @if ($responses->isEmpty())
        <x-evo::card><p class="evo-ui-empty evo-ui-jforms__empty">{{ $t('empty') }}</p></x-evo::card>
    @else
        <div class="evo-ui-jforms__response-list">
            @foreach ($responses as $response)
                @php($fields = json_decode($response->data, true) ?: [])
                <x-evo::card class="evo-ui-jforms__response">
                    <div class="evo-ui-jforms__response-head"><strong>#{{ $response->id }}</strong><span class="evo-ui-jforms__date"><x-evo::icon name="clock" /> {{ $t('submitted_at') }}: {{ $response->created_at }}</span></div>
                    <div class="evo-ui-jforms__data">
                        @foreach ($fields as $key => $value)
                            <div class="evo-ui-jforms__field"><span class="evo-ui-jforms__field-label">{{ $key }}</span><strong class="evo-ui-jforms__field-value">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
                        @endforeach
                    </div>
                    <div class="evo-ui-jforms__actions">
                        <a class="evo-ui-btn evo-ui-btn--primary evo-ui-btn--filled" href="{{ route('jForms.response', ['form' => $form, 'response' => $response->id]) }}"><x-evo::icon name="eye" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('view_response') }}</span></a>
                        <form method="post" action="{{ route('jForms.destroy', ['id' => $response->id]) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $t('confirm_delete') }}">
                            @csrf
                            <button class="evo-ui-btn evo-ui-btn--danger" type="submit"><x-evo::icon name="trash" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('delete') }}</span></button>
                        </form>
                    </div>
                </x-evo::card>
            @endforeach
        </div>
        {{ $responses->links() }}
    @endif
@endsection
