<div class="min-h-svh bg-[#f4f8fa] font-sans text-[#152036] transition-colors duration-300 dark:bg-[#0b1628] dark:text-[#f3f3f3]">
    <div class="grid min-h-svh lg:grid-cols-[minmax(0,1.05fr)_minmax(32rem,.95fr)]">
        <aside class="relative hidden min-h-svh overflow-hidden border-r border-[#1bb1e3]/10 bg-[#eaf7fa] lg:flex dark:border-white/5 dark:bg-[#0e2035]">
            <div class="absolute inset-0 damian-grid-pattern" aria-hidden="true"></div>
            <div class="absolute -left-28 -top-28 size-96 rounded-full bg-[#1bb1e3]/10 blur-3xl dark:bg-[#1bb1e3]/8" aria-hidden="true"></div>
            <div class="absolute -bottom-32 right-0 size-[30rem] rounded-full bg-[#22a15e]/10 blur-3xl dark:bg-[#22a15e]/8" aria-hidden="true"></div>

            <div class="relative z-10 flex w-full flex-col px-[clamp(3rem,6vw,6.5rem)] py-12">
                <div class="flex items-center gap-3 text-xl font-semibold tracking-[.08em]">
                    <span class="grid size-11 place-items-center rounded-xl bg-white shadow-sm ring-1 ring-[#1bb1e3]/15 dark:bg-[#182b49] dark:ring-white/10">
                        <svg class="size-7 text-[#22a15e]" viewBox="0 0 52 52" aria-hidden="true">
                            <path d="M8 8h17c11 0 19 7.2 19 18s-8 18-19 18H8V33h17c4.7 0 8-2.8 8-7s-3.3-7-8-7H19v10H8V8Z" fill="currentColor"/>
                            <path d="M8 8h11v11H8z" fill="#31bae4"/>
                        </svg>
                    </span>
                    <span>DAMIAN <strong class="font-semibold text-[#22a15e]">OS</strong></span>
                </div>

                <div class="my-auto grid items-center gap-10 xl:grid-cols-[minmax(18rem,.72fr)_minmax(20rem,1fr)]">
                    <div class="relative z-20 max-w-xl">
                        <span class="mb-7 block h-1 w-14 rounded-full bg-[#1bb1e3]" aria-hidden="true"></span>
                        <h1 class="text-[clamp(2.75rem,4.4vw,5.15rem)] font-semibold leading-[.98] tracking-[-.055em]">
                            Todo DAMIAN,<br>
                            <span class="text-[#168fdd] dark:text-[#31bae4]">conectado.</span>
                        </h1>
                        <p class="mt-7 max-w-md text-base leading-7 text-[#65738a] dark:text-[#a5aebd]">
                            Un espacio operativo para coordinar equipos, procesos y nuevas ideas desde un solo lugar.
                        </p>
                    </div>

                    <div class="damian-orbit mx-auto" aria-hidden="true">
                        <div class="damian-orbit__halo damian-orbit__halo--outer"></div>
                        <div class="damian-orbit__halo damian-orbit__halo--inner"></div>
                        <div class="damian-orbit__connector damian-orbit__connector--one"></div>
                        <div class="damian-orbit__connector damian-orbit__connector--two"></div>
                        <div class="damian-orbit__connector damian-orbit__connector--three"></div>
                        <div class="damian-orbit__core">
                            <svg viewBox="0 0 52 52">
                                <path d="M8 8h17c11 0 19 7.2 19 18s-8 18-19 18H8V33h17c4.7 0 8-2.8 8-7s-3.3-7-8-7H19v10H8V8Z" fill="currentColor"/>
                                <path d="M8 8h11v11H8z" fill="#31bae4"/>
                            </svg>
                        </div>
                        <div class="damian-orbit__node damian-orbit__node--one">
                            <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>
                        </div>
                        <div class="damian-orbit__node damian-orbit__node--two">
                            <svg viewBox="0 0 24 24"><path d="M12 2v4m0 12v4M4.9 4.9l2.8 2.8m8.6 8.6 2.8 2.8M2 12h4m12 0h4M4.9 19.1l2.8-2.8m8.6-8.6 2.8-2.8"/><circle cx="12" cy="12" r="4"/></svg>
                        </div>
                        <div class="damian-orbit__node damian-orbit__node--three">
                            <svg viewBox="0 0 24 24"><path d="M12 3 4 7l8 4 8-4-8-4Z"/><path d="m4 12 8 4 8-4M4 17l8 4 8-4"/></svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs font-medium text-[#65738a] dark:text-[#99a5b5]">
                    <span class="grid size-8 place-items-center rounded-full bg-[#22a15e]/10 text-[#22a15e]">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 5 6v5c0 4.6 2.8 8.4 7 10 4.2-1.6 7-5.4 7-10V6l-7-3Z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                    </span>
                    Acceso administrado y protegido por Gerencia
                </div>
            </div>
        </aside>

        <main class="relative flex min-h-svh items-center justify-center px-5 py-20 sm:px-10 lg:px-[clamp(3rem,6vw,7rem)]">
            <div class="absolute right-5 top-5 z-20 sm:right-8 sm:top-8" aria-label="Apariencia">
                <x-filament-panels::theme-switcher />
            </div>

            <section class="w-full max-w-[29rem]" aria-labelledby="damian-login-heading">
                <div class="mb-10 flex items-center justify-center gap-3 text-lg font-semibold tracking-[.08em] lg:hidden">
                    <span class="grid size-11 place-items-center rounded-xl bg-white shadow-sm ring-1 ring-[#1bb1e3]/15 dark:bg-[#182b49] dark:ring-white/10">
                        <svg class="size-7 text-[#22a15e]" viewBox="0 0 52 52" aria-hidden="true">
                            <path d="M8 8h17c11 0 19 7.2 19 18s-8 18-19 18H8V33h17c4.7 0 8-2.8 8-7s-3.3-7-8-7H19v10H8V8Z" fill="currentColor"/>
                            <path d="M8 8h11v11H8z" fill="#31bae4"/>
                        </svg>
                    </span>
                    <span>DAMIAN <strong class="font-semibold text-[#22a15e]">OS</strong></span>
                </div>

                <header class="mb-9">
                    <h2 id="damian-login-heading" class="text-4xl font-semibold tracking-[-.045em] sm:text-[2.65rem]">Bienvenido</h2>
                    <p class="mt-3 text-[.95rem] leading-6 text-[#65738a] dark:text-[#99a5b5]">Ingresa tus credenciales para continuar.</p>
                </header>

                <div class="damian-login-form">
                    {{ $this->content }}
                </div>

                <div class="mt-9 flex items-center gap-3 border-t border-[#dbe5e8] pt-6 text-xs text-[#65738a] dark:border-white/10 dark:text-[#99a5b5]">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#22a15e] opacity-30"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#22a15e]"></span>
                    </span>
                    Sistema disponible · Acceso seguro
                </div>
            </section>
        </main>
    </div>
</div>
