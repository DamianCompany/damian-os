@auth
    @php
        $user = auth()->user();
        $displayName = $user->role === 'gerencia' ? 'Gerencia' : $user->name;
        $roleLabel = $user->role === 'gerencia' ? 'Administración general' : 'Supervisor DAMI 3D';
    @endphp

    <div class="damian-topbar-user" aria-label="Usuario actual">
        <span class="damian-topbar-user__name">{{ $displayName }}</span>
        <span class="damian-topbar-user__role">{{ $roleLabel }}</span>
    </div>
@endauth
