@if (session('success'))<x-portal.alert type="success" class="portal-shell portal-flash">{{ session('success') }}</x-portal.alert>@endif
@if ($errors->has('auth'))<x-portal.alert type="error" class="portal-shell portal-flash">{{ $errors->first('auth') }}</x-portal.alert>@endif
