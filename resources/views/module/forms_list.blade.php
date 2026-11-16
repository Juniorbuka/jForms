<div class="container-fluid py-3">
    <h1>@svg('tabler-list-details', '', ['width' => 24, 'height' => 24, 'class' => 'me-1', 'aria-hidden' => 'true']) {{ __('jForms::module.title') }}</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($responses->isEmpty())
        <p>{{ __('jForms::module.empty') }}</p>
    @else
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr>
                    <th>{{ __('jForms::module.form') }}</th>
                    <th>{{ __('jForms::module.count') }}</th>
                    <th>{{ __('jForms::module.latest') }}</th>
                </tr></thead>
                <tbody>
                @foreach ($responses as $response)
                    <tr>
                        <td><a href="{{ route('jForms.show', ['form' => $response->form_key]) }}">{{ $response->form_name }}</a></td>
                        <td>{{ $response->total }}</td>
                        <td>{{ $response->latest }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
