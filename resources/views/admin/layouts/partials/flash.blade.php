@if (session('success'))
    <div class="alert alert-success">
        {{ \App\Models\AdminDisplay::text(session('success')) }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        {{ \App\Models\AdminDisplay::text(session('error')) }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <ul class="admin-alert-list">
            @foreach ($errors->all() as $error)
                <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
            @endforeach
        </ul>
    </div>
@endif
