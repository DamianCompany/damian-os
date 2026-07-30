@auth
    @php
        $user = auth()->user();
        $displayName = $user->role === 'gerencia' ? 'Gerencia' : $user->name;
        $roleLabel = match ($user->role) {
            'gerencia' => 'Administración general',
            'dami_3d' => 'Supervisor DAMI 3D',
            'investiga_lab' => 'Supervisor InvestigaLab',
            'automation' => 'Supervisor Damian Automation',
            default => 'Acceso DAMIAN OS',
        };
    @endphp

    <div class="damian-topbar-user" aria-label="Usuario actual">
        <span class="damian-topbar-user__name">{{ $displayName }}</span>
        <span class="damian-topbar-user__role">{{ $roleLabel }}</span>
    </div>
@endauth
