<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ matter: Object });

const formatCurrency = (amt) => '₱' + Number(amt || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head :title="`Statement of Account - ${matter.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Statement of Account</h2>
                <a :href="route('matters.statement.download', matter.id)" class="bg-slate-800 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-slate-700 transition">
                    Download PDF
                </a>
            </div>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Client Info Header Section -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-bold text-slate-900">Client: {{ matter.client?.name || 'N/A' }}</h3>
                <p class="text-sm text-gray-600 mt-1">Matter File: {{ matter.name }} | Authority: {{ matter.authority || 'N/A' }}</p>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b">
                            <th class="p-3">Invoice #</th>
                            <th class="p-3">Date Issued</th>
                            <th class="p-3 text-right">Amount</th>
                            <th class="p-3 text-right">Payment</th>
                            <th class="p-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="inv in matter.invoices" :key="inv.id" class="hover:bg-slate-50">
                            <td class="p-3 font-medium">
                                <Link :href="route('invoices.show', inv.id)" class="text-blue-600 hover:underline">{{ inv.invoice_number }}</Link>
                            </td>
                            <td class="p-3 text-slate-600">{{ new Date(inv.issue_date).toLocaleDateString() }}</td>
                            <td class="p-3 text-right font-medium">{{ formatCurrency(inv.total_amount) }}</td>
                            <td class="p-3 text-right text-green-600">{{ formatCurrency(inv.total_payments) }}</td>
                            <td class="p-3 text-right font-bold text-slate-900">{{ formatCurrency(inv.balance_due) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="bg-white p-6 rounded-lg flex flex-col items-end">
                    <h3 class="text-gray-500 text-sm font-semibold">Total Unpaid Amount</h3>
                    <p class="text-xl font-bold text-gray-900 mt-1">
                        {{ parseFloat(matter.total_unpaid_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>