@if (session()->has('status'))
    <flux:callout variant="success" icon="check-circle">
        {{ session('status') }}
    </flux:callout>
@endif

@if ($errors->any())
    <flux:callout variant="danger" icon="exclamation-triangle">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </flux:callout>
@endif
