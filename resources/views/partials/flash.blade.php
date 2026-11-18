@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if (session('demo_code'))
    <div class="alert alert-info">
        <strong>Demo mode:</strong> email is not really sent on a local machine, so here is your code:
        <div class="demo-code" data-demo-code>{{ session('demo_code') }}</div>
        <span class="small">It is also written to <code>storage/logs/laravel.log</code>.</span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        Please fix the following:
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
