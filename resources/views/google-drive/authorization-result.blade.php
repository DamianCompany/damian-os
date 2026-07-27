<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Drive · DAMIAN OS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f3f3f3] text-[#152036] dark:bg-[#152036] dark:text-[#f3f3f3]">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-6 py-12">
        <section class="w-full rounded-3xl border border-[#1bb1e3]/20 bg-white p-8 shadow-2xl shadow-[#152036]/10 dark:bg-[#182b49] sm:p-12">
            <div class="mb-8 flex items-center gap-4">
                <div class="grid size-14 place-items-center rounded-2xl bg-[#22a15e]/15 text-2xl text-[#22a15e]">D</div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.2em] text-[#1bb1e3]">DAMIAN OS</p>
                    <h1 class="text-2xl font-bold sm:text-3xl">Conexión con Google Drive</h1>
                </div>
            </div>

            @if ($refreshToken)
                <div class="rounded-2xl border border-[#22a15e]/30 bg-[#22a15e]/10 p-5">
                    <h2 class="text-lg font-bold text-[#22a15e]">Autorización completada</h2>
                    <p class="mt-2 text-sm text-[#152036]/70 dark:text-[#eaeceb]">
                        Copia este valor ahora y colócalo en <code>GOOGLE_DRIVE_REFRESH_TOKEN</code>. Por seguridad no volverá a mostrarse.
                    </p>
                </div>

                <div class="mt-6 flex gap-3">
                    <input id="refresh-token" type="password" readonly value="{{ $refreshToken }}"
                           class="min-w-0 flex-1 rounded-xl border border-[#99a5b5]/40 bg-[#f3f3f3] px-4 py-3 font-mono text-sm text-[#152036] dark:bg-[#152036] dark:text-[#f3f3f3]">
                    <button type="button" onclick="copyToken(this)"
                            class="rounded-xl bg-[#22a15e] px-6 py-3 font-bold text-white transition hover:bg-[#3caa83]">
                        Copiar
                    </button>
                </div>
            @else
                <div class="rounded-2xl border border-red-400/30 bg-red-500/10 p-5">
                    <h2 class="text-lg font-bold text-red-500">No se pudo completar</h2>
                    <p class="mt-2 text-sm">{{ $error }}</p>
                </div>
            @endif

            <a href="{{ route('filament.admin.pages.dashboard') }}"
               class="mt-8 inline-flex rounded-xl border border-[#1bb1e3]/30 px-5 py-3 font-semibold text-[#1a4e5c] transition hover:bg-[#1bb1e3]/10 dark:text-[#31bae4]">
                Regresar al panel
            </a>
        </section>
    </main>

    <script>
        async function copyToken(button) {
            const input = document.getElementById('refresh-token');
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'Copiado';
            input.type = 'text';
            window.setTimeout(() => {
                button.textContent = 'Copiar';
                input.type = 'password';
            }, 2500);
        }
    </script>
</body>
</html>
