<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    invoice: {
        type: Object,
        required: true
    }
});

// Format date to match 'F d, Y' (e.g., October 9, 2026)
const formatLongDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
};

// Format date to match 'm/d/Y' for line items
const formatShortDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
    });
};

// PHP-style currency formatting with the Philippine Peso symbol
const formatCurrency = (amount) => {
    return '₱' + Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};
</script>

<template>
    <Head :title="`Invoice - ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <!-- Print-hidden navigation & download header toolbar -->
        <template #header>
            <div class="flex justify-between items-center print:hidden">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Invoice: {{ invoice.invoice_number }}
                </h2>
                <div class="space-x-3">
                    <a :href="route('invoices.download', invoice.id)" 
                       class="inline-flex items-center bg-slate-800 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-slate-700 transition shadow-sm">
                        Download PDF
                    </a>
                    <a :href="route('invoices.ledes', invoice.id)" 
                       class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                        Download LEDES
                    </a>
                </div>
            </div>
        </template>

        <div class="py-12">
            <!-- Main Statement Document Wrapper (Styled like an official paper sheet) -->
            <div class="max-w-4xl mx-auto bg-white p-10 shadow-sm rounded-lg border border-slate-200 space-y-8 print:shadow-none print:border-none print:p-0">
                
                <!-- Firm Letterhead & Invoice Header Table -->
                <table class="w-full border-collapse">
                    <tr>
                        <td class="align-top">
                            <div class="text-lg font-bold uppercase tracking-wide text-slate-900">
                                Ortega, Bacorro, Odulio, Calma & Carbonell
                            </div>
                            <div class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Attorneys-at-Law<br>
                                Metro Manila, Philippines<br>
                                Email: info@ocbocc.com
                            </div>
                        </td>
                        <td class="align-top text-right">
                            <div class="text-xl font-bold uppercase text-slate-900 tracking-wide mb-1">
                                Statement of Account
                            </div>
                            <div class="text-xs text-slate-500 leading-relaxed">
                                <strong>Invoice No:</strong> {{ invoice.invoice_number }}<br>
                                <strong>Date:</strong> {{ formatLongDate(invoice.issue_date) }}<br>
                                <strong>Due Date:</strong> {{ formatLongDate(invoice.due_date) }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Client and Matter Particulars Box -->
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-md">
                    <table class="w-full border-collapse text-xs">
                        <tr>
                            <td class="font-bold text-slate-700 py-1" style="width: 15%;">FOR:</td>
                            <td class="text-slate-800 py-1" style="width: 35%;">{{ invoice.matter?.client?.name || 'Valued Client' }}</td>
                            <td class="font-bold text-slate-700 py-1" style="width: 15%;">MATTER FILE:</td>
                            <td class="text-slate-800 py-1" style="width: 35%;">{{ invoice.matter?.name || 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold text-slate-700 py-1">ATTENTION:</td>
                            <td class="text-slate-800 py-1">Client Billing Department</td>
                            <td class="font-bold text-slate-700 py-1">AUTHORITY:</td>
                            <td class="text-slate-800 py-1">{{ invoice.matter?.authority || 'N/A' }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Professional Services Section Title -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">
                        Professional Services Rendered
                    </h4>
                    
                    <!-- Itemized Time Entries Table -->
                    <div class="overflow-x-auto border border-slate-200 rounded-md">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-900 text-white uppercase tracking-wider">
                                    <th class="py-2.5 px-3" style="width: 12%;">Date</th>
                                    <th class="py-2.5 px-3" style="width: 15%;">Timekeeper</th>
                                    <th class="py-2.5 px-3" style="width: 43%;">Description of Services</th>
                                    <th class="py-2.5 px-3 text-center" style="width: 10%;">Hours</th>
                                    <th class="py-2.5 px-3 text-right" style="width: 10%;">Rate</th>
                                    <th class="py-2.5 px-3 text-right" style="width: 10%;">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="entry in invoice.time_entries" :key="entry.id" class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3 text-slate-600">{{ formatShortDate(entry.start_time) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800">{{ entry.user?.name || 'Lawyer' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600">{{ entry.description }}</td>
                                    <td class="py-2.5 px-3 text-center text-slate-600">{{ Number(entry.hours).toFixed(2) }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-600">{{ formatCurrency(entry.hourly_rate) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ formatCurrency(entry.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals Calculation Block -->
                <div class="flex justify-end pt-2">
                    <div class="w-72 border border-slate-200 rounded-md overflow-hidden text-xs">
                        <table class="w-full border-collapse">
                            <tr>
                                <td class="p-2.5 text-slate-600 border-b border-slate-200"><strong>Our Fees:</strong></td>
                                <td class="p-2.5 text-right font-bold text-slate-900 border-b border-slate-200">{{ formatCurrency(invoice.total_amount / 1.12) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2.5 text-slate-600 border-b border-slate-200"><strong>+ 12% VAT:</strong></td>
                                <td class="p-2.5 text-right font-bold text-slate-900 border-b border-slate-200">{{ formatCurrency((invoice.total_amount / 1.12) * 0.12) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2.5 text-slate-600 border-b border-slate-200"><strong>Total Fees:</strong></td>
                                <td class="p-2.5 text-right font-bold text-slate-900 border-b border-slate-200">{{ formatCurrency(invoice.total_amount) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2.5 text-slate-600 border-b border-slate-200"><strong>Previous Balance:</strong></td>
                                <td class="p-2.5 text-right text-slate-600 border-b border-slate-200">₱0.00</td>
                            </tr>
                            <tr class="bg-slate-100">
                                <td class="p-2.5 font-bold text-slate-900"><strong>Total Balance Due:</strong></td>
                                <td class="p-2.5 text-right font-bold text-slate-900 text-sm"><strong>{{ formatCurrency(invoice.total_amount) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Remarks / Payment Instructions -->
                <div v-if="invoice.remarks" class="text-xs text-slate-700 mt-6 pt-4 border-t border-slate-200">
                    <strong class="text-slate-900">Remarks / Payment Instructions:</strong><br>
                    <p class="mt-1 text-slate-600">{{ invoice.remarks }}</p>
                </div>

                <!-- Footer Check Payment Notice -->
                <div class="text-center text-[10px] text-slate-400 mt-12 pt-4 border-t border-slate-200">
                    Please make checks payable to <strong>Ortega, Bacorro, Odulio, Calma & Carbonell</strong>. Thank you for your continued confidence and patronage.
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>