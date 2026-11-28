<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SpecialBillingModal from '@/Components/SpecialBillingModal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    arrangements: Array,
    invoices: Array,
    clients: Array,
    matters: Array,
    lawyers: Array,
});

const activeTab = ref('queue'); // 'queue' | 'arrangements' | 'remittances'
const selectedCurrency = ref('PHP'); // 'PHP' | 'USD'
const timeframe = ref('ytd'); // 'ytd' | 'month' | 'all'
const selectedType = ref('ALL'); // 'ALL' | 'retainer' | 'secondment' | 'directors_fee'
const selectedStatus = ref('ALL');
const searchQuery = ref('');

const isModalOpen = ref(false);
const selectedArrangement = ref(null);
const targetMonth = ref(new Date().toISOString().slice(0, 7));
const generating = ref(false);

const formatMoney = (amount, currency = 'PHP') => {
    const symbol = currency === 'USD' ? '$' : '₱';
    return symbol + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const currentYear = String(new Date().getFullYear());
const currentMonthPrefix = new Date().toISOString().slice(0, 7);

const matchesTimeframe = (dateStr) => {
    if (timeframe.value === 'all' || !dateStr) return true;
    if (timeframe.value === 'ytd') return String(dateStr).startsWith(currentYear);
    if (timeframe.value === 'month') return String(dateStr).startsWith(currentMonthPrefix);
    return true;
};

// ==========================================
// KPI STATISTICS & CATEGORY BREAKDOWNS
// ==========================================
const stats = computed(() => {
    const cur = selectedCurrency.value;
    const emptyBreakdown = () => ({ retainer: 0, secondment: 0, directors_fee: 0 });

    const queued = { total: 0, count: 0, byType: emptyBreakdown() };
    const unpaid = { total: 0, count: 0, byType: emptyBreakdown() };
    const collected = { total: 0, count: 0, byType: emptyBreakdown() };
    const pendingPayouts = { total: 0, count: 0, collectedReady: 0 };

    for (const inv of props.invoices || []) {
        if ((inv.currency || 'PHP') !== cur) continue;

        // 1. Queued (Always live)
        if (inv.status === 'Queued') {
            queued.total += Number(inv.total_amount || 0);
            queued.count++;
            for (const item of inv.items || []) {
                if (queued.byType[item.type] !== undefined) {
                    queued.byType[item.type] += Number(item.gross_amount || 0);
                }
            }
        }

        // 2. Sent & Unpaid A/R (Always live)
        if (inv.status === 'Sent' && Number(inv.balance_due || 0) > 0) {
            unpaid.total += Number(inv.balance_due || 0);
            unpaid.count++;
            const ratio = Number(inv.unpaid_ratio ?? 1);
            for (const item of inv.items || []) {
                if (unpaid.byType[item.type] !== undefined) {
                    unpaid.byType[item.type] += Number(item.gross_amount || 0) * ratio;
                }
            }
        }

        // 3. Collected (Filtered by YTD / Month / All Time)
        if (Number(inv.total_payments || 0) > 0 && matchesTimeframe(inv.issue_date)) {
            collected.total += Number(inv.total_payments || 0);
            collected.count++;
            const paidRatio = Number(inv.total_amount || 0) > 0
                ? Math.min(1, Number(inv.total_payments) / Number(inv.total_amount))
                : 0;
            for (const item of inv.items || []) {
                if (collected.byType[item.type] !== undefined) {
                    collected.byType[item.type] += Number(item.gross_amount || 0) * paidRatio;
                }
            }
        }

        // 4. Unremitted Lawyer / Director Beneficiary Shares (Live)
        if (inv.status !== 'Queued') {
            for (const item of inv.items || []) {
                if (item.beneficiary_name && !item.beneficiary_remitted_at) {
                    pendingPayouts.total += Number(item.amount || 0);
                    pendingPayouts.count++;
                    if (inv.status === 'Paid') {
                        pendingPayouts.collectedReady += Number(item.amount || 0);
                    }
                }
            }
        }
    }

    return { queued, unpaid, collected, pendingPayouts };
});

// ==========================================
// FILTERED LISTS PER TAB
// ==========================================
const switchTab = (tab) => {
    activeTab.value = tab;
    selectedStatus.value = 'ALL';
};

const filteredInvoices = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return (props.invoices || []).filter((inv) => {
        if ((inv.currency || 'PHP') !== selectedCurrency.value) return false;
        if (selectedStatus.value !== 'ALL' && inv.status !== selectedStatus.value) return false;
        if (selectedType.value !== 'ALL' && !(inv.types || []).includes(selectedType.value)) return false;
        if (inv.status !== 'Queued' && !matchesTimeframe(inv.issue_date)) return false;

        if (!q) return true;
        const itemText = (inv.items || []).map((i) => `${i.description} ${i.beneficiary_name || ''}`).join(' ');
        return [inv.invoice_number, inv.client_name, inv.period_key, inv.status, itemText]
            .some((v) => String(v || '').toLowerCase().includes(q));
    });
});

const filteredArrangements = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return (props.arrangements || []).filter((arr) => {
        if ((arr.currency || 'PHP') !== selectedCurrency.value) return false;
        if (selectedStatus.value !== 'ALL' && arr.status !== selectedStatus.value) return false;
        if (selectedType.value !== 'ALL' && arr.type !== selectedType.value) return false;

        if (!q) return true;
        return [arr.client?.name, arr.type_label, arr.description, arr.beneficiary?.name, arr.cycle_label]
            .some((v) => String(v || '').toLowerCase().includes(q));
    });
});

const beneficiaryItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const rows = [];
    for (const inv of props.invoices || []) {
        if ((inv.currency || 'PHP') !== selectedCurrency.value) continue;
        for (const item of inv.items || []) {
            if (!item.beneficiary_name) continue;
            if (selectedType.value !== 'ALL' && item.type !== selectedType.value) continue;
            if (selectedStatus.value === 'Remitted' && !item.beneficiary_remitted_at) continue;
            if (selectedStatus.value === 'Unremitted' && item.beneficiary_remitted_at) continue;

            if (q) {
                const match = [item.beneficiary_name, inv.client_name, item.description, inv.invoice_number, inv.period_key]
                    .some((v) => String(v || '').toLowerCase().includes(q));
                if (!match) continue;
            }

            rows.push({
                ...item,
                invoice_id: inv.id,
                invoice_number: inv.invoice_number,
                invoice_status: inv.status,
                client_name: inv.client_name,
                currency: inv.currency,
                period_key: inv.period_key,
            });
        }
    }
    return rows;
});

// ==========================================
// ACTIONS & MODALS
// ==========================================
const openModal = (arrangement = null) => {
    selectedArrangement.value = arrangement;
    isModalOpen.value = true;
};

const deleteArrangement = (arr) => {
    if (confirm(`Delete "${arr.description}" for ${arr.client?.name}?`)) {
        router.delete(route('special-billing.destroy', arr.id), { preserveScroll: true });
    }
};

const runQueueGenerator = () => {
    generating.value = true;
    router.post(
        route('special-billing.generate'),
        { target_month: targetMonth.value },
        {
            preserveScroll: true,
            onFinish: () => {
                generating.value = false;
            },
        }
    );
};

const approveAndSend = (invoice) => {
    if (confirm(`Approve and mark ${invoice.invoice_number} as Sent?`)) {
        router.patch(route('special-billing.send', invoice.id), {}, { preserveScroll: true });
    }
};

const isPaymentModalOpen = ref(false);
const selectedInvoice = ref(null);
const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Bank Transfer',
    reference_number: '',
});

const openPaymentModal = (invoice) => {
    selectedInvoice.value = invoice;
    paymentForm.amount = invoice.balance_due;
    isPaymentModalOpen.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('payments.store', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isPaymentModalOpen.value = false;
            paymentForm.reset();
        },
    });
};

const toggleRemittance = (item) => {
    router.patch(route('special-billing.remit', item.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Special Billing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Special Billing (Retainers, Secondments &amp; Director's Fees)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Manage recurring fixed-schedule engagements, generate monthly/quarterly queues, and monitor collections
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Currency Toggle -->
                    <div class="inline-flex rounded-lg bg-slate-200 p-1 text-xs font-semibold">
                        <button
                            type="button"
                            @click="selectedCurrency = 'PHP'"
                            :class="selectedCurrency === 'PHP' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                            class="px-2.5 py-1 rounded-md transition"
                        >
                            PHP (₱)
                        </button>
                        <button
                            type="button"
                            @click="selectedCurrency = 'USD'"
                            :class="selectedCurrency === 'USD' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                            class="px-2.5 py-1 rounded-md transition"
                        >
                            USD ($)
                        </button>
                    </div>

                    <input
                        type="month"
                        v-model="targetMonth"
                        class="rounded-md border-slate-300 text-xs shadow-sm"
                    />
                    <button
                        type="button"
                        @click="runQueueGenerator"
                        :disabled="generating"
                        class="rounded-md bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                        {{ generating ? 'Generating...' : 'Generate Queue' }}
                    </button>
                    <button
                        type="button"
                        @click="openModal()"
                        class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                    >
                        + New Arrangement
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="$page.props.flash?.success" class="rounded-md bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-medium">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.errors?.message" class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-800 font-medium">
                    {{ $page.props.errors.message }}
                </div>

                <!-- KPI SUMMARY STRIP WITH CATEGORY BREAKDOWNS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Card 1: Queued Bills -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-amber-500">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                On Queue ({{ selectedCurrency }})
                            </span>
                            <span class="text-xs font-medium text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full">
                                {{ stats.queued.count }} bills
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(stats.queued.total, selectedCurrency) }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-slate-100 space-y-0.5 text-[11px] text-slate-500">
                            <div class="flex justify-between"><span>Retainer:</span><strong class="text-slate-700">{{ formatMoney(stats.queued.byType.retainer, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Secondment:</span><strong class="text-slate-700">{{ formatMoney(stats.queued.byType.secondment, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Director's Fee:</span><strong class="text-slate-700">{{ formatMoney(stats.queued.byType.directors_fee, selectedCurrency) }}</strong></div>
                        </div>
                    </div>

                    <!-- Card 2: Invoiced but Unpaid -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-rose-600">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Sent &amp; Unpaid (A/R)
                            </span>
                            <span class="text-xs font-medium text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">
                                {{ stats.unpaid.count }} bills
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(stats.unpaid.total, selectedCurrency) }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-slate-100 space-y-0.5 text-[11px] text-slate-500">
                            <div class="flex justify-between"><span>Retainer:</span><strong class="text-slate-700">{{ formatMoney(stats.unpaid.byType.retainer, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Secondment:</span><strong class="text-slate-700">{{ formatMoney(stats.unpaid.byType.secondment, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Director's Fee:</span><strong class="text-slate-700">{{ formatMoney(stats.unpaid.byType.directors_fee, selectedCurrency) }}</strong></div>
                        </div>
                    </div>

                    <!-- Card 3: Collected (YTD / Period) -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-emerald-600">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Collected ({{ timeframe === 'ytd' ? 'YTD' : timeframe === 'month' ? 'This Month' : 'All Time' }})
                            </span>
                            <span class="text-xs font-medium text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full">
                                {{ stats.collected.count }} bills
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(stats.collected.total, selectedCurrency) }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-slate-100 space-y-0.5 text-[11px] text-slate-500">
                            <div class="flex justify-between"><span>Retainer:</span><strong class="text-slate-700">{{ formatMoney(stats.collected.byType.retainer, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Secondment:</span><strong class="text-slate-700">{{ formatMoney(stats.collected.byType.secondment, selectedCurrency) }}</strong></div>
                            <div class="flex justify-between"><span>Director's Fee:</span><strong class="text-slate-700">{{ formatMoney(stats.collected.byType.directors_fee, selectedCurrency) }}</strong></div>
                        </div>
                    </div>

                    <!-- Card 4: Pending Beneficiary Payouts -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-indigo-600">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Unremitted Payouts
                            </span>
                            <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">
                                {{ stats.pendingPayouts.count }} items
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(stats.pendingPayouts.total, selectedCurrency) }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-slate-100 space-y-1 text-[11px] text-slate-500">
                            <div class="flex justify-between">
                                <span>Collected &amp; Ready to Pay:</span>
                                <strong class="text-emerald-700">{{ formatMoney(stats.pendingPayouts.collectedReady, selectedCurrency) }}</strong>
                            </div>
                            <p class="text-[10px] text-slate-400">
                                Director's &amp; seconded lawyer shares pending remittance
                            </p>
                        </div>
                    </div>
                </div>

                <!-- MAIN TABBED CARD -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Tabs + Filter & Search Toolbar -->
                    <div class="border-b border-slate-200 px-6 pt-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <nav class="-mb-px flex flex-wrap gap-6">
                            <button
                                @click="switchTab('queue')"
                                :class="activeTab === 'queue' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Billing Queue &amp; Issued Bills ({{ filteredInvoices.length }})
                            </button>
                            <button
                                @click="switchTab('arrangements')"
                                :class="activeTab === 'arrangements' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Recurring Schedules ({{ filteredArrangements.length }})
                            </button>
                            <button
                                @click="switchTab('remittances')"
                                :class="activeTab === 'remittances' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Director / Lawyer Payouts ({{ beneficiaryItems.length }})
                            </button>
                        </nav>

                        <!-- Filter & Search Controls -->
                        <div class="pb-3 flex flex-wrap items-center gap-2">
                            <select v-model="timeframe" class="rounded-md border-slate-300 text-xs shadow-sm">
                                <option value="ytd">YTD ({{ currentYear }})</option>
                                <option value="month">This Month</option>
                                <option value="all">All Time</option>
                            </select>

                            <select v-model="selectedType" class="rounded-md border-slate-300 text-xs shadow-sm">
                                <option value="ALL">All Categories</option>
                                <option value="retainer">Retainer Fee</option>
                                <option value="secondment">Secondment Fee</option>
                                <option value="directors_fee">Director's Fee</option>
                            </select>

                            <select v-if="activeTab === 'queue'" v-model="selectedStatus" class="rounded-md border-slate-300 text-xs shadow-sm">
                                <option value="ALL">All Statuses</option>
                                <option value="Queued">Queued</option>
                                <option value="Sent">Sent (Unpaid)</option>
                                <option value="Paid">Paid</option>
                            </select>

                            <select v-else-if="activeTab === 'arrangements'" v-model="selectedStatus" class="rounded-md border-slate-300 text-xs shadow-sm">
                                <option value="ALL">All Statuses</option>
                                <option value="Active">Active</option>
                                <option value="Paused">Paused</option>
                                <option value="Ended">Ended</option>
                            </select>

                            <select v-else v-model="selectedStatus" class="rounded-md border-slate-300 text-xs shadow-sm">
                                <option value="ALL">All Payouts</option>
                                <option value="Unremitted">Unremitted</option>
                                <option value="Remitted">Remitted</option>
                            </select>

                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Search client, lawyer, invoice..."
                                class="w-48 sm:w-56 rounded-md border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- TAB 1: QUEUE & INVOICES -->
                    <div v-if="activeTab === 'queue'" class="p-6 overflow-x-auto">
                        <table v-if="filteredInvoices.length > 0" class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                    <th class="p-3">Invoice #</th>
                                    <th class="p-3">Client &amp; Line Items</th>
                                    <th class="p-3">Period</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Total</th>
                                    <th class="p-3 text-right">Balance</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="inv in filteredInvoices" :key="inv.id" class="border-b hover:bg-slate-50/70 align-top">
                                    <td class="p-3 font-medium whitespace-nowrap">
                                        <Link :href="route('invoices.show', inv.id)" class="text-blue-600 hover:underline">
                                            {{ inv.invoice_number }}
                                        </Link>
                                        <div class="text-xs text-slate-400">Issued: {{ inv.issue_date }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-800">{{ inv.client_name }}</div>
                                        <ul class="mt-1 space-y-0.5 text-xs text-slate-600">
                                            <li v-for="item in inv.items" :key="item.id">
                                                &bull; {{ item.description }} ({{ formatMoney(item.amount + item.vat_amount, inv.currency) }})
                                            </li>
                                        </ul>
                                    </td>
                                    <td class="p-3 text-xs font-mono text-slate-600 whitespace-nowrap">{{ inv.period_key }}</td>
                                    <td class="p-3 whitespace-nowrap">
                                        <span
                                            class="px-2 py-0.5 text-xs font-bold rounded-full"
                                            :class="{
                                                'bg-amber-100 text-amber-800': inv.status === 'Queued',
                                                'bg-blue-100 text-blue-800': inv.status === 'Sent',
                                                'bg-emerald-100 text-emerald-800': inv.status === 'Paid',
                                            }"
                                        >
                                            {{ inv.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-semibold whitespace-nowrap">{{ formatMoney(inv.total_amount, inv.currency) }}</td>
                                    <td class="p-3 text-right font-bold whitespace-nowrap">{{ formatMoney(inv.balance_due, inv.currency) }}</td>
                                    <td class="p-3 text-right whitespace-nowrap space-x-2">
                                        <button
                                            v-if="inv.status === 'Queued'"
                                            @click="approveAndSend(inv)"
                                            class="text-xs bg-emerald-600 text-white px-2.5 py-1 rounded font-semibold hover:bg-emerald-700"
                                        >
                                            Mark as Sent
                                        </button>
                                        <button
                                            v-else-if="inv.status !== 'Paid'"
                                            @click="openPaymentModal(inv)"
                                            class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded font-semibold hover:bg-blue-600 hover:text-white"
                                        >
                                            Record Payment
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic text-sm">No special billing invoices match your current filter.</p>
                    </div>

                    <!-- TAB 2: RECURRING ARRANGEMENTS -->
                    <div v-if="activeTab === 'arrangements'" class="p-6 overflow-x-auto">
                        <table v-if="filteredArrangements.length > 0" class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                    <th class="p-3">Client</th>
                                    <th class="p-3">Type</th>
                                    <th class="p-3">Description / Lawyer</th>
                                    <th class="p-3">Schedule</th>
                                    <th class="p-3">Grouping</th>
                                    <th class="p-3 text-right">Amount</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="arr in filteredArrangements" :key="arr.id" class="border-b hover:bg-slate-50/70">
                                    <td class="p-3 font-semibold text-slate-800">{{ arr.client?.name }}</td>
                                    <td class="p-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs font-semibold">
                                            {{ arr.type_label }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-slate-800">{{ arr.description }}</div>
                                        <div v-if="arr.beneficiary" class="text-xs text-indigo-700 font-medium">
                                            Lawyer: {{ arr.beneficiary.name }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-xs text-slate-600 whitespace-nowrap">
                                        {{ arr.cycle_label }} (Day {{ arr.billing_day }})
                                    </td>
                                    <td class="p-3 text-xs capitalize text-slate-600">{{ arr.grouping_mode }}</td>
                                    <td class="p-3 text-right font-bold whitespace-nowrap">
                                        {{ formatMoney(arr.amount, arr.currency) }}
                                        <span class="block text-[10px] font-normal text-slate-400">
                                            {{ arr.is_vatable ? '+ 12% VAT' : 'Non-VAT' }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                            :class="arr.status === 'Active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                                        >
                                            {{ arr.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap space-x-2 text-xs">
                                        <button @click="openModal(arr)" class="text-blue-600 hover:underline font-semibold">Edit</button>
                                        <button @click="deleteArrangement(arr)" class="text-red-600 hover:underline font-semibold">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic text-sm">No recurring arrangements match your current filter.</p>
                    </div>

                    <!-- TAB 3: BENEFICIARY / DIRECTOR PAYOUTS -->
                    <div v-if="activeTab === 'remittances'" class="p-6 overflow-x-auto">
                        <table v-if="beneficiaryItems.length > 0" class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                    <th class="p-3">Lawyer / Director</th>
                                    <th class="p-3">Client / Description</th>
                                    <th class="p-3">Invoice #</th>
                                    <th class="p-3">Client Payment</th>
                                    <th class="p-3 text-right">Fee Share</th>
                                    <th class="p-3 text-right">Remittance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in beneficiaryItems" :key="item.id" class="border-b hover:bg-slate-50/70">
                                    <td class="p-3 font-semibold text-slate-800">{{ item.beneficiary_name }}</td>
                                    <td class="p-3">
                                        <div class="font-medium text-slate-700">{{ item.client_name }}</div>
                                        <div class="text-xs text-slate-500">{{ item.description }}</div>
                                    </td>
                                    <td class="p-3 text-xs font-mono">{{ item.invoice_number }}</td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                            :class="item.invoice_status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        >
                                            {{ item.invoice_status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-bold">{{ formatMoney(item.amount, item.currency) }}</td>
                                    <td class="p-3 text-right">
                                        <button
                                            @click="toggleRemittance(item)"
                                            :class="item.beneficiary_remitted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="px-2.5 py-1 rounded text-xs font-semibold"
                                        >
                                            {{ item.beneficiary_remitted_at ? `Remitted (${item.beneficiary_remitted_at})` : 'Mark Remitted' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic text-sm">No director or secondment beneficiary items match your current filter.</p>
                    </div>
                </div>
            </div>
        </div>

        <SpecialBillingModal
            :show="isModalOpen"
            :arrangement="selectedArrangement"
            :clients="clients"
            :matters="matters"
            :lawyers="lawyers"
            @close="isModalOpen = false"
        />

        <!-- Record Payment Modal -->
        <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Payment for {{ selectedInvoice.invoice_number }}</h3>
                    <button @click="isPaymentModalOpen = false" class="text-slate-400 hover:text-red-500 font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitPayment" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Amount Received ({{ selectedInvoice.currency }})</label>
                        <input type="number" step="0.01" v-model="paymentForm.amount" class="w-full rounded-md border-slate-300 shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Date</label>
                        <input type="date" v-model="paymentForm.payment_date" class="w-full rounded-md border-slate-300 shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select v-model="paymentForm.payment_method" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option>Bank Transfer</option>
                            <option>Check</option>
                            <option>Cash</option>
                            <option>Gcash / E-Wallet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Reference / Check Number</label>
                        <input type="text" v-model="paymentForm.reference_number" class="w-full rounded-md border-slate-300 shadow-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="isPaymentModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md">Cancel</button>
                        <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>