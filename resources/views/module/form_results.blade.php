<div class="container-fluid py-3">
    <p><a href="{{ route('jForms.index') }}">@svg('tabler-arrow-left', '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) {{ __('jForms::module.back') }}</a></p>
    <h1>{{ $responses->first()?->form_name ?? $form }}</h1>

    @if ($responses->isEmpty())
        <p>{{ __('jForms::module.empty') }}</p>
    @else
        @foreach ($responses as $response)
            @php($fields = json_decode($response->data, true) ?: [])
            <section class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>#{{ $response->id }}</strong>
                    <span>{{ __('jForms::module.submitted_at') }}: {{ $response->created_at }}</span>
                </div>
                <div class="card-body">
                    <a class="btn btn-sm btn-primary" href="{{ route('jForms.response', ['form' => $form, 'response' => $response->id]) }}">
                        @svg('tabler-eye', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true'])
                        {{ __('jForms::module.view_response') }}
                    </a>
                    <dl class="row mb-0">
                        @foreach ($fields as $key => $value)
                            <dt class="col-sm-3">{{ $key }}</dt>
                            <dd class="col-sm-9">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
                        @endforeach
                    </dl>
                    <form method="post" action="{{ route('jForms.destroy', ['id' => $response->id]) }}" class="mt-3" onsubmit="return confirm(@json(__('jForms::module.confirm_delete')))">
                        @csrf
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-danger btn-sm" type="submit">
                            @svg('tabler-trash', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true'])
                            {{ __('jForms::module.delete') }}
                        </button>
                    </form>
                </div>
            </section>
        @endforeach
        {{ $responses->links() }}
    @endif
</div>
