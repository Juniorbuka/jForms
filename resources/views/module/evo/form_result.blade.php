@extends('jForms::module.evo.layout')
@php($t = fn (string $key) => trans('jForms::module.' . $key, [], $uiLocale))
@section('title', $result->form_name . ' — #' . $result->id)

@section('content')
    <a class="evo-ui-jforms__back" href="{{ route('jForms.show', ['form' => $form]) }}"><x-evo::icon name="arrow-left" /> {{ $t('back_to_responses') }}</a>
    <header class="evo-ui-surface__header evo-ui-jforms__header"><div><h1>{{ $result->form_name }}</h1><p>#{{ $result->id }} · {{ $t('submitted_at') }}: {{ $result->created_at }}</p></div><x-evo::icon name="file-text" class="evo-ui-jforms__header-icon" /></header>
    <x-evo::card class="evo-ui-jforms__detail">
        <header class="evo-ui-surface__header"><h2>{{ $t('response_data') }}</h2></header>
        <div class="evo-ui-jforms__data">
            @foreach ($fields as $key => $value)
                <div class="evo-ui-jforms__field"><span class="evo-ui-jforms__field-label">{{ $key }}</span><strong class="evo-ui-jforms__field-value">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</strong></div>
            @endforeach
        </div>
        <div class="evo-ui-jforms__actions">
            <form method="post" action="{{ route('jForms.destroy', ['id' => $result->id]) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $t('confirm_delete') }}">
                @csrf
                <button class="evo-ui-btn evo-ui-btn--danger" type="submit"><x-evo::icon name="trash" class="evo-ui-btn__icon" /><span class="evo-ui-btn__label">{{ $t('delete') }}</span></button>
            </form>
        </div>
    </x-evo::card>
@endsection
