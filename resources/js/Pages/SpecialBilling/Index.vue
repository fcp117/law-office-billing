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
const isModalOpen = ref(false);
const selectedArrangement = ref(null);
const targetMonth = ref(new Date().toISOString().slice(0, 7));
const generating = ref(false);

const formatMoney = (amount, currency = 'PHP') => {
    const symbol = currency === 'USD' ? '$' : '₱';
    return symbol + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

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

// Payment Modal
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

// Flatten paid/sent items that have a lawyer beneficiary (Director's Fees & Secondments)
const beneficiaryItems = computed(() => {
    const rows = [];
    for (const inv of props.invoices || []) {
        for (const item of inv.items || []) {
            if (item.beneficiary_name) {
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
    }
    return rows;
});

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
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Tabs -->
                    <div class="border-b border-slate-200 px-6 pt-4">
                        <nav class="-mb-px flex gap-6">
                            <button
                                @click="activeTab = 'queue'"
                                :class="activeTab === 'queue' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Billing Queue &amp; Issued Bills ({{ invoices.length }})
                            </button>
                            <button
                                @click="activeTab = 'arrangements'"
                                :class="activeTab === 'arrangements' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Recurring Schedules ({{ arrangements.length }})
                            </button>
                            <button
                                @click="activeTab = 'remittances'"
                                :class="activeTab === 'remittances' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="border-b-2 pb-3 text-sm font-semibold"
                            >
                                Director / Lawyer Payouts ({{ beneficiaryItems.length }})
                            </button>
                        </nav>
                    </div>

                    <!-- TAB 1: QUEUE & INVOICES -->
                    <div v-if="activeTab === 'queue'" class="p-6 overflow-x-auto">
                        <table v-if="invoices.length > 0" class="w-full text-left text-sm">
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
                                <tr v-for="inv in invoices" :key="inv.id" class="border-b hover:bg-slate-50/70 align-top">
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
                                            Approve &amp; Send
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
                        <p v-else class="text-slate-500 italic text-sm">No queued or issued special bills yet. Select a month above and click "Generate Queue".</p>
                    </div>

                    <!-- TAB 2: RECURRING ARRANGEMENTS -->
                    <div v-if="activeTab === 'arrangements'" class="p-6 overflow-x-auto">
                        <table v-if="arrangements.length > 0" class="w-full text-left text-sm">
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
                                <tr v-for="arr in arrangements" :key="arr.id" class="border-b hover:bg-slate-50/70">
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
                        <p v-else class="text-slate-500 italic text-sm">No recurring arrangements configured yet.</p>
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
                        <p v-else class="text-slate-500 italic text-sm">No director or secondment beneficiary items generated yet.</p>
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