@extends('jForms::module.evo.layout')
@php($t = fn (string $key) => trans('jForms::module.' . $key, [], $uiLocale))
@section('title', $t('title'))

@section('content')
    <header class="evo-ui-surface__header evo-ui-jforms__header">
        <div><h1>{{ $t('title') }}</h1><p>{{ $t('forms_description') }}</p></div>
        <x-evo::icon name="inbox" class="evo-ui-jforms__header-icon" />
    </header>
    @if (session('status'))<div class="evo-ui-jforms__notice" role="status">{{ session('status') }}</div>@endif
    @if ($responses->isEmpty())
        <x-evo::card><p class="evo-ui-empty evo-ui-jforms__empty">{{ $t('empty') }}</p></x-evo::card>
    @else
        <div class="evo-ui-jforms__form-grid">
            @foreach ($responses as $response)
                <a class="evo-ui-jforms__form-link" href="{{ route('jForms.show', ['form' => $response->form_key]) }}">
                    <x-evo::icon name="file-text" class="evo-ui-btn__icon" />
                    <span class="evo-ui-jforms__form-info"><strong>{{ $response->form_name }}</strong><small>{{ $response->form_key }}</small></span>
                    <span class="evo-ui-badge">{{ $response->total }} {{ $t('count') }}</span>
                    <span class="evo-ui-jforms__form-date"><small>{{ $t('latest') }}</small>{{ $response->latest }}</span>
                    <x-evo::icon name="chevron-right" class="evo-ui-jforms__form-arrow" />
                </a>
            @endforeach
        </div>
    @endif
@endsection
