<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <!-- 1. The outermost div is a full-height flex column -->
    <div class="h-screen bg-gray-100 flex flex-col overflow-hidden">
        
        <!-- Top Navigation Bar (Optional: Keep your existing top nav here if you still want it) -->
        <nav class="bg-white border-b border-gray-100">
            <!-- Your existing top nav code -->
        </nav>

        <!-- 2. The middle container flexes horizontally -->
        <div class="flex flex-1 overflow-hidden">
            
            <!-- 3. THE NEW SIDEBAR -->
            <aside class="w-64 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex md:flex-col border-r border-slate-800">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-white">Firm Management</h2>
                </div>
                
                <nav class="flex-1 px-4 space-y-2 overflow-y-auto font-medium">
                    <Link :href="route('dashboard')" class="block px-4 py-2 rounded-md hover:bg-slate-800 hover:text-white transition">Dashboard</Link>
                    <Link :href="route('clients')" class="block px-4 py-2 rounded-md hover:bg-slate-800 hover:text-white transition">Clients</Link>
                    <!-- Update these routes to match your actual named routes in web.php -->
                    <Link href="/matters" class="block px-4 py-2 rounded-md hover:bg-slate-800 hover:text-white transition">Matters</Link>
                    <Link href="/lawyers" class="block px-4 py-2 rounded-md hover:bg-slate-800 hover:text-white transition">Lawyers</Link>
                    <Link href="/invoices" class="block px-4 py-2 rounded-md hover:bg-slate-800 hover:text-white transition">Invoices</Link>
                </nav>
            </aside>

            <!-- 4. THE EXISTING PAGE CONTENT -->
            <!-- <main class="flex-1 overflow-y-auto bg-slate-50">
                
                <Page Header Slot
                <header class="bg-white shadow-sm border-b border-slate-200" v-if="$slots.header">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header> -->

                <!-- Page Main Content Slot -->
            <main>
                <slot />
            </main>
            
        </div>
    </div>
</template>