@if(auth()->check() && in_array(auth()->user()->role, $roles, true))
    {{ $slot }}
@endif
