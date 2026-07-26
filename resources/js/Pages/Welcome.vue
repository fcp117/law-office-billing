<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome | Case Information Management System" />
    
    <div class="bg-slate-50 text-slate-800 min-h-screen flex flex-col selection:bg-blue-600 selection:text-white">
        <div class="relative flex min-h-screen flex-col items-center justify-center p-6 lg:p-8">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl flex-1 flex flex-col">
                
                <!-- Navigation Header -->
                <header class="flex justify-end py-6">
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-transparent transition hover:text-slate-900 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-transparent transition hover:text-slate-900 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            >
                                Log in
                            </Link>

                           <!-- <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-transparent transition hover:text-slate-900 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            >
                                Register
                            </Link>

                        -->
                        </template>
                    </nav>
                </header>

                <!-- Main Content -->
                <main class="flex-1 flex flex-col items-center justify-center text-center">
                    <div class="space-y-6 max-w-3xl">
                        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl md:text-6xl">
                            Ortega, Bacorro, Odulio, Calma & Carbonell
                        </h1>
                        <div class="h-1 w-20 bg-blue-600 mx-auto rounded"></div>
                        <p class="text-xl text-slate-600 font-medium tracking-wide uppercase">
                            Case Information Management System
                        </p>
                    </div>
                </main>

                <!-- Footer -->
                <footer class="py-8 text-center text-sm text-slate-400">
                    System v1.0 | Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </footer>
                
            </div>
        </div>
    </div>
</template>