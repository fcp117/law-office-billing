<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    kpis: Object,
    unbilledMatters: Array,
    unpaidInvoices: Array,
    unpaidMatters: Array,
    pendingApprovals: Array,
});

// Active Tab & Filters
const activeTab = ref('unbilled'); // 'unbilled' | 'invoices' | 'matters_ar' | 'approvals'
const selectedCurrency = ref('PHP'); // 'PHP' | 'USD' | 'ALL'
const searchQuery = ref('');

const formatMoney = (amount, currency = 'PHP') => {
    const symbol = currency === 'USD' ? '$' : '₱';
    return (
        symbol +
        Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const activeKpi = computed(() => {
    const cur = selectedCurrency.value === 'USD' ? 'USD' : 'PHP';
    return props.kpis?.[cur] || {};
});

const matchesCurrencyAndSearch = (row, fields) => {
    if (selectedCurrency.value !== 'ALL' && row.currency !== selectedCurrency.value) {
        return false;
    }
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return true;
    return fields.some((val) => String(val || '').toLowerCase().includes(q));
};

// ==========================================
// SORTING ENGINE (PER TAB)
// ==========================================
const sortState = reactive({
    unbilled: { key: 'unbilled_amount', dir: 'desc' },
    invoices: { key: 'days_overdue', dir: 'desc' },
    matters_ar: { key: 'balance_due', dir: 'desc' },
    approvals: { key: 'start_time', dir: 'asc' },
});

const toggleSort = (tab, key) => {
    if (sortState[tab].key === key) {
        sortState[tab].dir = sortState[tab].dir === 'asc' ? 'desc' : 'asc';
    } else {
        sortState[tab].key = key;
        sortState[tab].dir = ['amount', 'unbilled_amount', 'balance_due', 'days_overdue', 'days_stale', 'total_billed'].includes(key)
            ? 'desc'
            : 'asc';
    }
};

const sortRows = (rows, tab) => {
    const { key, dir } = sortState[tab];
    const mult = dir === 'asc' ? 1 : -1;

    return [...rows].sort((a, b) => {
        const valA = a[key];
        const valB = b[key];

        if (valA === valB) return 0;
        if (valA === null || valA === undefined) return 1;
        if (valB === null || valB === undefined) return -1;

        if (typeof valA === 'number' && typeof valB === 'number') {
            return (valA - valB) * mult;
        }
        return String(valA).localeCompare(String(valB)) * mult;
    });
};

const sortIndicator = (tab, key) => {
    if (sortState[tab].key !== key) return '↕';
    return sortState[tab].dir === 'asc' ? '↑' : '↓';
};

// Filtered & Sorted Lists
const filteredUnbilled = computed(() => {
    const filtered = props.unbilledMatters.filter((m) =>
        matchesCurrencyAndSearch(m, [m.client_name, m.name, m.billing_type, ...(m.lawyers || [])])
    );
    return sortRows(filtered, 'unbilled');
});

const filteredInvoices = computed(() => {
    const filtered = props.unpaidInvoices.filter((inv) =>
        matchesCurrencyAndSearch(inv, [inv.invoice_number, inv.client_name, inv.matter_name, inv.aging_bucket])
    );
    return sortRows(filtered, 'invoices');
});

const filteredMattersAr = computed(() => {
    const filtered = props.unpaidMatters.filter((m) =>
        matchesCurrencyAndSearch(m, [m.client_name, m.name, m.status])
    );
    return sortRows(filtered, 'matters_ar');
});

const filteredApprovals = computed(() => {
    const filtered = props.pendingApprovals.filter((e) =>
        matchesCurrencyAndSearch(e, [e.client_name, e.matter_name, e.lawyer_name, e.formatted_description])
    );
    return sortRows(filtered, 'approvals');
});

// ==========================================
// RECORD PAYMENT MODAL (FROM INVOICES TAB)
// ==========================================
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

const closePaymentModal = () => {
    isPaymentModalOpen.value = false;
    selectedInvoice.value = null;
    paymentForm.reset();
    paymentForm.clearErrors();
};

const submitPayment = () => {
    paymentForm.post(route('payments.store', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => closePaymentModal(),
    });
};

// ==========================================
// INLINE APPROVAL (FROM APPROVAL QUEUE TAB)
// ==========================================
const approvalDrafts = reactive({});
const savingApprovalId = ref(null);

const getDraftHours = (entry) => {
    if (approvalDrafts[entry.id] === undefined) {
        approvalDrafts[entry.id] = entry.hours;
    }
    return approvalDrafts[entry.id];
};

const approveEntry = (entry) => {
    const val = getDraftHours(entry);
    if (val === '' || val === null || Number(val) < 0) return;

    savingApprovalId.value = entry.id;
    router.patch(
        route('time-entries.approve', entry.id),
        { approved_hours: Number(val) },
        {
            preserveScroll: true,
            onSuccess: () => delete approvalDrafts[entry.id],
            onFinish: () => {
                savingApprovalId.value = null;
            },
        }
    );
};
</script>

<template>
    <Head title="Billing & Collections Overview" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Billing &amp; Collections Overview
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Partner summary of unbilled work in progress, accounts receivable aging, and pending hour approvals
                    </p>
                </div>

                <!-- Currency Switcher -->
                <div class="inline-flex rounded-lg bg-slate-200 p-1 text-xs font-semibold">
                    <button
                        type="button"
                        @click="selectedCurrency = 'PHP'"
                        :class="selectedCurrency === 'PHP' ? 'bg-white text-slate-900 shadow' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-md transition"
                    >
                        PHP (₱)
                    </button>
                    <button
                        type="button"
                        @click="selectedCurrency = 'USD'"
                        :class="selectedCurrency === 'USD' ? 'bg-white text-slate-900 shadow' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-md transition"
                    >
                        USD ($)
                    </button>
                    <button
                        type="button"
                        @click="selectedCurrency = 'ALL'"
                        :class="selectedCurrency === 'ALL' ? 'bg-white text-slate-900 shadow' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-md transition"
                    >
                        All Currencies
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- KPI Summary Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Unbilled WIP -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-indigo-600">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Unbilled Services ({{ selectedCurrency === 'USD' ? 'USD' : 'PHP' }})
                            </span>
                            <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">
                                {{ activeKpi.unbilled_matters_count || 0 }} cases
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(activeKpi.total_wip, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Ready to bill:
                            <strong class="text-emerald-700">
                                {{ formatMoney(activeKpi.ready_to_bill_wip, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                            </strong>
                        </p>
                    </div>

                    <!-- Pending Partner Approval -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-amber-500">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Awaiting Approval
                            </span>
                            <span class="text-xs font-medium text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full">
                                {{ activeKpi.pending_approval_count || 0 }} entries
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(activeKpi.pending_approval_wip, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Realization rate: <strong class="text-slate-700">{{ activeKpi.realization_rate ?? 100 }}%</strong>
                        </p>
                    </div>

                    <!-- Unpaid Invoices (Total A/R) -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-rose-600">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Unpaid Receivables
                            </span>
                            <span class="text-xs font-medium text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">
                                {{ activeKpi.unpaid_invoices_count || 0 }} invoices
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 mt-2">
                            {{ formatMoney(activeKpi.total_ar, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Overdue:
                            <strong class="text-rose-700">
                                {{ formatMoney(activeKpi.overdue_ar, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                            </strong>
                        </p>
                    </div>

                    <!-- Billed vs Collected MTD / YTD -->
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-emerald-600">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Collections Velocity
                        </span>
                        <div class="mt-2 space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">MTD Billed / Paid:</span>
                                <span class="font-semibold text-slate-800">
                                    {{ formatMoney(activeKpi.billed_mtd, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                                    /
                                    <span class="text-emerald-700">{{ formatMoney(activeKpi.collected_mtd, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}</span>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">YTD Billed / Paid:</span>
                                <span class="font-semibold text-slate-800">
                                    {{ formatMoney(activeKpi.billed_ytd, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                                    /
                                    <span class="text-emerald-700">{{ formatMoney(activeKpi.collected_ytd, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- A/R Aging Strip -->
                <div v-if="activeKpi.aging" class="bg-white px-5 py-3.5 rounded-lg shadow flex flex-wrap items-center justify-between gap-4 text-xs">
                    <span class="font-bold uppercase tracking-wider text-slate-600">
                        A/R Aging ({{ selectedCurrency === 'USD' ? 'USD' : 'PHP' }}):
                    </span>
                    <div class="flex flex-wrap items-center gap-4">
                        <div v-for="(amount, bucket) in activeKpi.aging" :key="bucket" class="flex items-center gap-1.5">
                            <span class="text-slate-500">{{ bucket }}:</span>
                            <span
                                class="font-mono font-semibold"
                                :class="bucket === '90+ Days' && amount > 0 ? 'text-rose-700' : 'text-slate-800'"
                            >
                                {{ formatMoney(amount, selectedCurrency === 'USD' ? 'USD' : 'PHP') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Main Tabbed Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Tab Bar & Search -->
                    <div class="border-b border-slate-200 px-6 pt-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Tabs">
                            <button
                                type="button"
                                @click="activeTab = 'unbilled'"
                                :class="activeTab === 'unbilled' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                                class="whitespace-nowrap border-b-2 pb-3 text-sm font-semibold flex items-center gap-2"
                            >
                                <span>Unbilled Cases (WIP)</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                                    {{ filteredUnbilled.length }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'invoices'"
                                :class="activeTab === 'invoices' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                                class="whitespace-nowrap border-b-2 pb-3 text-sm font-semibold flex items-center gap-2"
                            >
                                <span>Unpaid Invoices (A/R)</span>
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700">
                                    {{ filteredInvoices.length }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'matters_ar'"
                                :class="activeTab === 'matters_ar' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                                class="whitespace-nowrap border-b-2 pb-3 text-sm font-semibold flex items-center gap-2"
                            >
                                <span>Unpaid by Case</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                                    {{ filteredMattersAr.length }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'approvals'"
                                :class="activeTab === 'approvals' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                                class="whitespace-nowrap border-b-2 pb-3 text-sm font-semibold flex items-center gap-2"
                            >
                                <span>Approval Queue</span>
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">
                                    {{ filteredApprovals.length }}
                                </span>
                            </button>
                        </nav>

                        <div class="pb-3">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Filter client, case, lawyer, invoice..."
                                class="w-full lg:w-64 rounded-md border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- TAB 1: UNBILLED SERVICES (BY MATTER) -->
                    <div v-if="activeTab === 'unbilled'" class="p-6">
                        <div v-if="filteredUnbilled.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('unbilled', 'client_name')">
                                            Client {{ sortIndicator('unbilled', 'client_name') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('unbilled', 'name')">
                                            Matter {{ sortIndicator('unbilled', 'name') }}
                                        </th>
                                        <th class="p-3 whitespace-nowrap">Type</th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('unbilled', 'oldest_date')">
                                            Oldest Item {{ sortIndicator('unbilled', 'oldest_date') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('unbilled', 'days_stale')">
                                            Idle Days {{ sortIndicator('unbilled', 'days_stale') }}
                                        </th>
                                        <th class="p-3 whitespace-nowrap">Billing Readiness</th>
                                        <th class="p-3 text-right cursor-pointer select-none whitespace-nowrap" @click="toggleSort('unbilled', 'unbilled_amount')">
                                            Unbilled Amount {{ sortIndicator('unbilled', 'unbilled_amount') }}
                                        </th>
                                        <th class="p-3 text-right whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in filteredUnbilled" :key="item.id" class="border-b hover:bg-slate-50/70">
                                        <td class="p-3 font-medium text-slate-800">{{ item.client_name }}</td>
                                        <td class="p-3">
                                            <Link :href="route('matters.show', item.id)" class="font-semibold text-blue-600 hover:underline">
                                                {{ item.name }}
                                            </Link>
                                            <div class="text-xs text-slate-400">{{ item.lawyers.join(', ') }}</div>
                                        </td>
                                        <td class="p-3 capitalize text-xs text-slate-600 whitespace-nowrap">
                                            {{ item.billing_type.replace('_', ' ') }}
                                        </td>
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ formatDate(item.oldest_date) }}</td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span
                                                class="text-xs font-mono"
                                                :class="item.days_stale >= 30 ? 'text-amber-700 font-bold' : 'text-slate-600'"
                                            >
                                                {{ item.days_stale }}d
                                            </span>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span
                                                v-if="item.ready_to_bill"
                                                class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800"
                                            >
                                                Ready to Bill
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800"
                                            >
                                                {{ item.unapproved_count }} Unapproved
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-bold text-slate-900 whitespace-nowrap">
                                            {{ formatMoney(item.unbilled_amount, item.currency) }}
                                        </td>
                                        <td class="p-3 text-right whitespace-nowrap">
                                            <Link
                                                :href="route('matters.show', item.id)"
                                                class="text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded border border-blue-200 hover:bg-blue-600 hover:text-white font-semibold transition"
                                            >
                                                Open Case
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm">No unbilled services found for the current filter.</p>
                    </div>

                    <!-- TAB 2: UNPAID INVOICES (ACCOUNTS RECEIVABLE) -->
                    <div v-if="activeTab === 'invoices'" class="p-6">
                        <div v-if="filteredInvoices.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('invoices', 'invoice_number')">
                                            Invoice # {{ sortIndicator('invoices', 'invoice_number') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('invoices', 'client_name')">
                                            Client / Matter {{ sortIndicator('invoices', 'client_name') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('invoices', 'issue_date')">
                                            Issued {{ sortIndicator('invoices', 'issue_date') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('invoices', 'due_date')">
                                            Due Date {{ sortIndicator('invoices', 'due_date') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('invoices', 'days_overdue')">
                                            Aging {{ sortIndicator('invoices', 'days_overdue') }}
                                        </th>
                                        <th class="p-3 text-right cursor-pointer select-none whitespace-nowrap" @click="toggleSort('invoices', 'balance_due')">
                                            Balance Due {{ sortIndicator('invoices', 'balance_due') }}
                                        </th>
                                        <th class="p-3 text-right whitespace-nowrap">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="inv in filteredInvoices" :key="inv.id" class="border-b hover:bg-slate-50/70">
                                        <td class="p-3 font-medium whitespace-nowrap">
                                            <Link :href="route('invoices.show', inv.id)" class="text-blue-600 hover:underline">
                                                {{ inv.invoice_number }}
                                            </Link>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-medium text-slate-800">{{ inv.client_name }}</div>
                                            <Link :href="route('matters.show', inv.matter_id)" class="text-xs text-slate-500 hover:underline">
                                                {{ inv.matter_name }}
                                            </Link>
                                        </td>
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ formatDate(inv.issue_date) }}</td>
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ formatDate(inv.due_date) }}</td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span
                                                class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': inv.days_overdue === 0,
                                                    'bg-amber-100 text-amber-800': inv.days_overdue > 0 && inv.days_overdue <= 30,
                                                    'bg-orange-100 text-orange-800': inv.days_overdue > 30 && inv.days_overdue <= 60,
                                                    'bg-rose-100 text-rose-800': inv.days_overdue > 60,
                                                }"
                                            >
                                                {{ inv.days_overdue === 0 ? 'Current' : `${inv.days_overdue}d overdue` }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-bold text-slate-900 whitespace-nowrap">
                                            {{ formatMoney(inv.balance_due, inv.currency) }}
                                            <div v-if="inv.total_payments > 0" class="text-[11px] font-normal text-slate-400">
                                                of {{ formatMoney(inv.total_amount, inv.currency) }}
                                            </div>
                                        </td>
                                        <td class="p-3 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                @click="openPaymentModal(inv)"
                                                class="text-xs bg-blue-50 text-blue-600 px-2.5 py-1 rounded border border-blue-200 hover:bg-blue-600 hover:text-white font-semibold transition"
                                            >
                                                Record Payment
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm">No unpaid invoices found for the current filter.</p>
                    </div>

                    <!-- TAB 3: UNPAID BY MATTER -->
                    <div v-if="activeTab === 'matters_ar'" class="p-6">
                        <div v-if="filteredMattersAr.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('matters_ar', 'client_name')">
                                            Client {{ sortIndicator('matters_ar', 'client_name') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('matters_ar', 'name')">
                                            Matter {{ sortIndicator('matters_ar', 'name') }}
                                        </th>
                                        <th class="p-3 whitespace-nowrap">Open Invoices</th>
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('matters_ar', 'oldest_unpaid_due_date')">
                                            Oldest Due Date {{ sortIndicator('matters_ar', 'oldest_unpaid_due_date') }}
                                        </th>
                                        <th class="p-3 text-right cursor-pointer select-none whitespace-nowrap" @click="toggleSort('matters_ar', 'total_billed')">
                                            Total Billed {{ sortIndicator('matters_ar', 'total_billed') }}
                                        </th>
                                        <th class="p-3 text-right whitespace-nowrap">Total Paid</th>
                                        <th class="p-3 text-right cursor-pointer select-none whitespace-nowrap" @click="toggleSort('matters_ar', 'balance_due')">
                                            Balance Due {{ sortIndicator('matters_ar', 'balance_due') }}
                                        </th>
                                        <th class="p-3 text-right whitespace-nowrap">Statement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in filteredMattersAr" :key="m.id" class="border-b hover:bg-slate-50/70">
                                        <td class="p-3 font-medium text-slate-800">{{ m.client_name }}</td>
                                        <td class="p-3">
                                            <Link :href="route('matters.show', m.id)" class="font-semibold text-blue-600 hover:underline">
                                                {{ m.name }}
                                            </Link>
                                        </td>
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ m.unpaid_invoice_count }}</td>
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ formatDate(m.oldest_unpaid_due_date) }}</td>
                                        <td class="p-3 text-right text-slate-600 whitespace-nowrap">{{ formatMoney(m.total_billed, m.currency) }}</td>
                                        <td class="p-3 text-right text-emerald-700 whitespace-nowrap">{{ formatMoney(m.total_paid, m.currency) }}</td>
                                        <td class="p-3 text-right font-bold text-slate-900 whitespace-nowrap">{{ formatMoney(m.balance_due, m.currency) }}</td>
                                        <td class="p-3 text-right whitespace-nowrap">
                                            <Link
                                                :href="route('matters.statement', m.id)"
                                                class="text-xs bg-amber-50 text-amber-700 px-2.5 py-1 rounded border border-amber-200 hover:bg-amber-100 font-semibold transition"
                                            >
                                                SOA
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm">All billed matters are paid in full.</p>
                    </div>

                    <!-- TAB 4: PARTNER APPROVAL QUEUE -->
                    <div v-if="activeTab === 'approvals'" class="p-6">
                        <div v-if="filteredApprovals.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                                        <th class="p-3 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('approvals', 'start_time')">
                                            Date {{ sortIndicator('approvals', 'start_time') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('approvals', 'lawyer_name')">
                                            Lawyer {{ sortIndicator('approvals', 'lawyer_name') }}
                                        </th>
                                        <th class="p-3 cursor-pointer select-none" @click="toggleSort('approvals', 'matter_name')">
                                            Client / Matter {{ sortIndicator('approvals', 'matter_name') }}
                                        </th>
                                        <th class="p-3">Description</th>
                                        <th class="p-3 text-right whitespace-nowrap">Logged</th>
                                        <th class="p-3 text-right cursor-pointer select-none whitespace-nowrap" @click="toggleSort('approvals', 'amount')">
                                            Est. Amount {{ sortIndicator('approvals', 'amount') }}
                                        </th>
                                        <th class="p-3 text-right whitespace-nowrap">Approve Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="entry in filteredApprovals" :key="entry.id" class="border-b hover:bg-slate-50/70">
                                        <td class="p-3 text-slate-600 whitespace-nowrap">{{ formatDate(entry.start_time) }}</td>
                                        <td class="p-3 font-medium text-slate-800 whitespace-nowrap">{{ entry.lawyer_name }}</td>
                                        <td class="p-3">
                                            <div class="text-xs text-slate-500">{{ entry.client_name }}</div>
                                            <Link :href="route('matters.show', entry.matter_id)" class="font-semibold text-blue-600 hover:underline">
                                                {{ entry.matter_name }}
                                            </Link>
                                        </td>
                                        <td class="p-3 text-slate-600">{{ entry.formatted_description }}</td>
                                        <td class="p-3 text-right font-mono text-slate-600 whitespace-nowrap">{{ Number(entry.hours).toFixed(2) }}</td>
                                        <td class="p-3 text-right font-medium text-slate-800 whitespace-nowrap">
                                            {{ formatMoney(entry.amount, entry.currency) }}
                                        </td>
                                        <td class="p-3 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    :value="getDraftHours(entry)"
                                                    @input="approvalDrafts[entry.id] = $event.target.value"
                                                    @keyup.enter="approveEntry(entry)"
                                                    class="w-20 rounded border-slate-300 px-2 py-1 text-right text-xs font-mono shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                />
                                                <button
                                                    type="button"
                                                    @click="approveEntry(entry)"
                                                    :disabled="savingApprovalId === entry.id"
                                                    class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                                >
                                                    {{ savingApprovalId === entry.id ? '...' : 'Approve' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm">All hourly time entries have been approved.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">
                        Payment for {{ selectedInvoice.invoice_number }}
                    </h3>
                    <button @click="closePaymentModal" class="text-slate-400 hover:text-red-500 font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitPayment" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Amount Received ({{ selectedInvoice.currency }})
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            v-model="paymentForm.amount"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        />
                        <div v-if="paymentForm.errors.amount" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.amount }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Date</label>
                        <input
                            type="date"
                            v-model="paymentForm.payment_date"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            v-model="paymentForm.payment_method"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option>Bank Transfer</option>
                            <option>Check</option>
                            <option>Cash</option>
                            <option>Gcash / E-Wallet</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Reference / Check Number</label>
                        <input
                            type="text"
                            v-model="paymentForm.reference_number"
                            placeholder="Optional"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" @click="closePaymentModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200">
                            Cancel
                        </button>
                        <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 disabled:opacity-50">
                            {{ paymentForm.processing ? 'Saving...' : 'Save Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>