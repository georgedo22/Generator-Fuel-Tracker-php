<?php
/** @var array $old */
?>
<div class="flex min-h-screen bg-white">

    <!-- Left Pane: Branding & Visuals -->
    <div class="relative hidden w-0 flex-1 lg:block">
        <!-- Dynamic Gradient Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0B2136] via-[#103458] to-[#0A1929] overflow-hidden">
            <!-- Decorative Background Elements for Glassmorphism & Depth -->
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="absolute bottom-1/4 right-0 w-80 h-80 rounded-full bg-brand-400/10 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 w-[500px] h-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-600/10 blur-[100px]"></div>
            
            <!-- Subtle Grid Overlay -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNykiLz48L3N2Zz4=')]"></div>
        </div>

        <div class="relative flex h-full flex-col items-center justify-center px-12 text-center">
            <!-- The New "H" Triangle Logo -->
            <div class="mb-10 flex h-32 w-32 items-center justify-center rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
                <svg width="80" height="80" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="drop-shadow-lg">
                    <!-- Left Column -->
                    <polygon points="20,10 40,20 20,30" fill="#E2E8F0" />
                    <polygon points="40,20 40,40 20,30" fill="#94A3B8" />
                    <polygon points="20,30 40,40 20,50" fill="#E2E8F0" />
                    <polygon points="40,40 40,60 20,50" fill="#94A3B8" />
                    <polygon points="20,50 40,60 20,70" fill="#E2E8F0" />
                    <polygon points="40,60 40,80 20,70" fill="#94A3B8" />
                    <polygon points="20,70 40,80 20,90" fill="#E2E8F0" />
                    
                    <!-- Right Column -->
                    <polygon points="80,10 60,20 80,30" fill="#E2E8F0" />
                    <polygon points="60,20 60,40 80,30" fill="#94A3B8" />
                    <polygon points="80,30 60,40 80,50" fill="#E2E8F0" />
                    <polygon points="60,40 60,60 80,50" fill="#94A3B8" />
                    <polygon points="80,50 60,60 80,70" fill="#E2E8F0" />
                    <polygon points="60,60 60,80 80,70" fill="#94A3B8" />
                    <polygon points="80,70 60,80 80,90" fill="#E2E8F0" />

                    <!-- Crossbar -->
                    <polygon points="40,40 60,40 50,50" fill="#CBD5E1" />
                    <polygon points="40,60 60,60 50,50" fill="#CBD5E1" />
                    <polygon points="40,40 50,50 40,60" fill="#94A3B8" />
                    <polygon points="60,40 50,50 60,60" fill="#94A3B8" />
                </svg>
            </div>
            
            <h1 class="font-outfit text-5xl font-extrabold tracking-tight text-white mb-4">
                Tracker <span class="text-brand-400">Hub</span>
            </h1>
            <p class="mt-4 max-w-md text-lg text-slate-300 font-light leading-relaxed">
                Empowering your operations with precise generator management and real-time fuel insights.
            </p>


        </div>
    </div>

    <!-- Right Pane: Login Form -->
    <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-24 xl:px-32 bg-slate-50">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            
            <!-- Mobile Logo (visible only on small screens) -->
            <div class="lg:hidden mb-10 flex flex-col items-center">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-[#0B2136] to-[#103458] shadow-lg">
                    <svg width="50" height="50" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="20,10 40,20 20,30" fill="#E2E8F0" />
                        <polygon points="40,20 40,40 20,30" fill="#94A3B8" />
                        <polygon points="20,30 40,40 20,50" fill="#E2E8F0" />
                        <polygon points="40,40 40,60 20,50" fill="#94A3B8" />
                        <polygon points="20,50 40,60 20,70" fill="#E2E8F0" />
                        <polygon points="40,60 40,80 20,70" fill="#94A3B8" />
                        <polygon points="20,70 40,80 20,90" fill="#E2E8F0" />
                        <polygon points="80,10 60,20 80,30" fill="#E2E8F0" />
                        <polygon points="60,20 60,40 80,30" fill="#94A3B8" />
                        <polygon points="80,30 60,40 80,50" fill="#E2E8F0" />
                        <polygon points="60,40 60,60 80,50" fill="#94A3B8" />
                        <polygon points="80,50 60,60 80,70" fill="#E2E8F0" />
                        <polygon points="60,60 60,80 80,70" fill="#94A3B8" />
                        <polygon points="80,70 60,80 80,90" fill="#E2E8F0" />
                        <polygon points="40,40 60,40 50,50" fill="#CBD5E1" />
                        <polygon points="40,60 60,60 50,50" fill="#CBD5E1" />
                        <polygon points="40,40 50,50 40,60" fill="#94A3B8" />
                        <polygon points="60,40 50,50 60,60" fill="#94A3B8" />
                    </svg>
                </div>
                <h2 class="font-outfit text-3xl font-bold tracking-tight text-slate-900">Tracker Hub</h2>
            </div>

            <div class="mb-8">
                <h2 class="font-outfit text-3xl font-bold tracking-tight text-slate-900">Welcome back</h2>
                <p class="mt-2 text-sm text-slate-500">Please enter your details to sign in.</p>
            </div>

            <div class="mt-8">
                <?php foreach (($flash ?? []) as $message): ?>
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm flex items-start gap-3">
                        <svg class="h-5 w-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm font-medium text-red-800"><?= \App\Core\Helpers::e((string) ($message['message'] ?? '')) ?></span>
                    </div>
                <?php endforeach; ?>

                <form method="post" action="<?= url('/login') ?>" class="space-y-6">
                    <?= \App\Core\Csrf::field() ?>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                        <div class="mt-2 relative">
                            <input id="email" name="email" type="email" required autocomplete="email"
                                value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES) ?>"
                                placeholder="admin@example.com"
                                class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm sm:leading-6 transition-all duration-200 bg-white hover:ring-slate-300">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                            <div class="text-sm">
                                <a href="#" class="font-medium text-brand-600 hover:text-brand-500 transition-colors">Forgot password?</a>
                            </div>
                        </div>
                        <div class="mt-2 relative">
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm sm:leading-6 transition-all duration-200 bg-white hover:ring-slate-300">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600 transition-colors">
                        <label for="remember-me" class="block text-sm text-slate-700">Remember me for 30 days</label>
                    </div>

                    <div>
                        <button type="submit"
                            class="group relative flex w-full justify-center rounded-xl bg-[#103458] px-3 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-[#0B2136] hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#103458] transition-all duration-200 ease-out transform hover:-translate-y-[1px]">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-brand-300 group-hover:text-brand-200 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>