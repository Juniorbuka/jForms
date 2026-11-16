<div class="container-fluid py-3">
    <p>
        <a href="{{ route('jForms.show', ['form' => $form]) }}">
            @svg('tabler-arrow-left', '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true'])
            {{ __('jForms::module.back_to_responses') }}
        </a>
    </p>
    <h1>
        @svg('tabler-file-text', '', ['width' => 24, 'height' => 24, 'class' => 'me-1', 'aria-hidden' => 'true'])
        {{ $result->form_name }} — #{{ $result->id }}
    </h1>

    <div class="card">
        <div class="card-header">{{ __('jForms::module.submitted_at') }}: {{ $result->created_at }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr>
                        <th>{{ __('jForms::module.field') }}</th>
                        <th>{{ __('jForms::module.value') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($fields as $key => $value)
                        <tr>
                            <th scope="row">{{ $key }}</th>
                            <td>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('jForms.destroy', ['id' => $result->id]) }}" onsubmit="return confirm(@json(__('jForms::module.confirm_delete')))">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">
                    @svg('tabler-trash', '', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true'])
                    {{ __('jForms::module.delete') }}
                </button>
            </form>
        </div>
    </div>
</div>
