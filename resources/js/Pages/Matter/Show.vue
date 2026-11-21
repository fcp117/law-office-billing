<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

// Import our Modals
import TaskModal from '@/Components/TaskModal.vue';
import EventModal from '@/Components/EventModal.vue';
import TimeEntryModal from '@/Components/TimeEntryModal.vue';
import DashboardMatterModal from '@/Components/DashboardMatterModal.vue';

const props = defineProps({
    matter: Object,
    clients: Array,
    lawyers: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);
const isPartner = computed(() => page.props.auth?.user?.role === 'Partner');

const matterCurrency = computed(() => props.matter.currency || 'PHP');
const currencySymbol = computed(() => (matterCurrency.value === 'PHP' ? '₱' : '$'));

const formatCurrency = (amount) => {
    const locale = matterCurrency.value === 'PHP' ? 'en-PH' : 'en-US';
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: matterCurrency.value,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount || 0));
};

// ==========================================
// 0. HANDLING LAWYERS ASSIGNMENT (PARTNER ONLY)
// ==========================================
const assignLawyerForm = useForm({
    user_id: '',
});

const availableLawyers = computed(() => {
    const assignedIds = new Set((props.matter.users || []).map((u) => u.id));
    return props.lawyers.filter((lawyer) => !assignedIds.has(lawyer.id));
});

const assignLawyer = () => {
    if (!assignLawyerForm.user_id || assignLawyerForm.processing) return;

    assignLawyerForm.post(route('matters.lawyers.store', props.matter.id), {
        preserveScroll: true,
        onSuccess: () => assignLawyerForm.reset('user_id'),
    });
};

const removingLawyerId = ref(null);

const removeLawyer = (user) => {
    if (!confirm(`Remove ${user.name} from this matter?`)) return;

    removingLawyerId.value = user.id;
    router.delete(route('matters.lawyers.destroy', [props.matter.id, user.id]), {
        preserveScroll: true,
        onFinish: () => {
            removingLawyerId.value = null;
        },
    });
};

// ==========================================
// 1. INVOICING & PAYMENTS LOGIC
// ==========================================
const isPaymentModalOpen = ref(false);
const selectedInvoice = ref(null);

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Bank Transfer',
    reference_number: '',
});

const openPaymentModal = (invoice) => {
    selectedInvoice.value = invoice;
    form.amount = invoice.balance_due ?? invoice.total_amount;
    isPaymentModalOpen.value = true;
};

const closePaymentModal = () => {
    isPaymentModalOpen.value = false;
    selectedInvoice.value = null;
    form.reset();
    form.clearErrors();
};

const submitPayment = () => {
    form.post(route('payments.store', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => closePaymentModal(),
    });
};

// ==========================================
// 1B. TIME ENTRIES & INLINE APPROVAL LOGIC
// ==========================================
const isTimeEntryModalOpen = ref(false);
const selectedTimeEntry = ref(null);

const openTimeEntryModal = (entry = null) => {
    selectedTimeEntry.value = entry;
    isTimeEntryModalOpen.value = true;
};

const canManageTimeEntry = (entry) => {
    if (entry.invoice_id !== null || props.matter.status === 'Completed') {
        return false;
    }
    if (isPartner.value) {
        return true;
    }
    return entry.user_id === currentUserId.value && entry.approved_hours === null;
};

const deleteTimeEntry = (entry) => {
    if (confirm(`Delete this time entry of ${entry.hours} hours?`)) {
        router.delete(route('time-entries.destroy', entry.id), { preserveScroll: true });
    }
};

// Inline approval state per entry ID
const inlineEditingId = ref(null);
const inlineDrafts = reactive({});
const savingInlineId = ref(null);

const getInlineValue = (entry) => {
    if (inlineDrafts[entry.id] === undefined) {
        inlineDrafts[entry.id] = entry.approved_hours !== null ? entry.approved_hours : entry.hours;
    }
    return inlineDrafts[entry.id];
};

const startInlineEdit = (entry) => {
    inlineDrafts[entry.id] = entry.approved_hours !== null ? entry.approved_hours : entry.hours;
    inlineEditingId.value = entry.id;
};

const cancelInlineEdit = (entry) => {
    delete inlineDrafts[entry.id];
    inlineEditingId.value = null;
};

const saveInlineApproval = (entry) => {
    const rawValue = getInlineValue(entry);
    if (rawValue === '' || rawValue === null || Number(rawValue) < 0) return;

    savingInlineId.value = entry.id;
    router.patch(
        route('time-entries.approve', entry.id),
        { approved_hours: Number(rawValue) },
        {
            preserveScroll: true,
            onSuccess: () => {
                inlineEditingId.value = null;
                delete inlineDrafts[entry.id];
            },
            onFinish: () => {
                savingInlineId.value = null;
            },
        }
    );
};

// ==========================================
// 2. TASKS LOGIC
// ==========================================
const isTaskModalOpen = ref(false);
const selectedTask = ref(null);

const openTaskModal = (task = null) => {
    selectedTask.value = task;
    isTaskModalOpen.value = true;
};

const deleteTask = (task) => {
    if (confirm(`Delete task "${task.title}"?`)) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
};

// ==========================================
// 3. EVENTS LOGIC
// ==========================================
const isEventModalOpen = ref(false);
const selectedEvent = ref(null);

const openEventModal = (event = null) => {
    selectedEvent.value = event;
    isEventModalOpen.value = true;
};

const deleteEvent = (event) => {
    if (confirm(`Delete event "${event.title}"?`)) {
        router.delete(route('events.destroy', event.id), { preserveScroll: true });
    }
};

// ==========================================
// 4. GENERATE INVOICE LOGIC
// ==========================================
const unapprovedHourlyCount = computed(() => {
    if ((props.matter.billing_type || '').toLowerCase() !== 'hourly') return 0;
    return (props.matter.time_entries || []).filter(
        (e) => e.invoice_id === null && e.approved_hours === null
    ).length;
});

const generateInvoice = () => {
    if (confirm('Generate an invoice for all unbilled time entries?')) {
        const today = new Date();
        const dueDate = new Date();
        dueDate.setDate(today.getDate() + 30);

        router.post(
            route('invoices.store', props.matter.id),
            {
                issue_date: today.toISOString().split('T')[0],
                due_date: dueDate.toISOString().split('T')[0],
                remarks: 'Thank you for your business.',
            },
            {
                preserveScroll: true,
            }
        );
    }
};

// ==========================================
// 5. CREATE AND EDIT MATTERS
// ==========================================
const isMatterModalOpen = ref(false);

const openMatterModal = () => {
    isMatterModalOpen.value = true;
};

const deleteMatter = () => {
    if (
        confirm(
            `Are you sure you want to completely delete "${props.matter.name}"? This action will permanently wipe all associated tasks, events, and time entries.`
        )
    ) {
        router.delete(route('matters.destroy', props.matter.id));
    }
};
</script>

<template>
    <Head :title="matter.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Matter Details
                </h2>
                <Link :href="route('dashboard')" class="text-sm text-blue-600 hover:underline">
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Header Section -->
                <div class="bg-slate-800 text-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-start">
                        <div class="bg-gray-800 text-white p-6 rounded-lg shadow">
                            <h1 class="text-3xl font-bold">{{ matter.name }}</h1>
                            <p class="mt-2 text-gray-300">Client: {{ matter.client.name }} | Authority: {{ matter.authority }}</p>

                            <!-- Billing Information Summary -->
                            <div class="mt-4 p-3 bg-slate-700/50 rounded border border-slate-600 inline-flex flex-wrap items-center gap-y-2 text-sm">
                                <span>
                                    <span class="text-gray-400">Currency:</span>
                                    <span class="ml-1 mr-4 px-2 py-0.5 rounded bg-slate-600 text-white font-semibold text-xs">
                                        {{ matterCurrency }} ({{ currencySymbol }})
                                    </span>
                                </span>

                                <span class="border-l border-slate-500 pl-4">
                                    <span class="text-gray-400">Billing Type:</span>
                                    <span class="text-white font-medium capitalize ml-1 mr-4">
                                        {{ matter.billing_type ? matter.billing_type.replace('_', ' ') : 'N/A' }}
                                    </span>
                                </span>

                                <template v-if="matter.billing_type === 'fixed_rate'">
                                    <span class="text-gray-400 border-l border-slate-500 pl-4">Fixed Fee:</span>
                                    <span class="text-white font-medium ml-1">
                                        {{ formatCurrency(matter.fixed_fee_amount) }}
                                    </span>
                                </template>

                                <template v-if="matter.billing_type === 'contingency'">
                                    <span class="text-gray-400 border-l border-slate-500 pl-4">Contingency Rate:</span>
                                    <span class="text-white font-medium ml-1 mr-4">
                                        {{ matter.contingency_percentage || 0 }}%
                                    </span>

                                    <span class="text-gray-400 border-l border-slate-500 pl-4">Settlement / Judgment:</span>
                                    <span class="text-white font-medium ml-1">
                                        {{ formatCurrency(matter.settlement_judgment) }}
                                    </span>
                                </template>
                            </div>

                            <!-- Edit Button -->
                            <div class="mt-4 space-x-3">
                                <button @click="openMatterModal" class="text-sm bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-semibold transition">
                                    Edit Matter Details
                                </button>
                            </div>
                        </div>

                        <span class="px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full">
                            {{ matter.status }}
                        </span>
                    </div>

                    <!-- Handling Lawyers -->
                    <div class="border-t border-slate-700 pt-4 mt-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-400 uppercase">Handling Lawyers</h3>

                            <!-- Partner-only Assign Lawyer Control -->
                            <form
                                v-if="isPartner && availableLawyers.length > 0"
                                @submit.prevent="assignLawyer"
                                class="flex items-center gap-2"
                            >
                                <select
                                    v-model="assignLawyerForm.user_id"
                                    class="rounded-md border-slate-600 bg-slate-700 text-sm text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="" disabled>Select Partner or Associate...</option>
                                    <option
                                        v-for="lawyer in availableLawyers"
                                        :key="lawyer.id"
                                        :value="lawyer.id"
                                    >
                                        {{ lawyer.name }} ({{ lawyer.role }})
                                    </option>
                                </select>
                                <button
                                    type="submit"
                                    :disabled="!assignLawyerForm.user_id || assignLawyerForm.processing"
                                    class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    {{ assignLawyerForm.processing ? 'Assigning...' : '+ Assign Lawyer' }}
                                </button>
                            </form>
                        </div>

                        <div v-if="assignLawyerForm.errors.user_id" class="mb-2 text-xs text-red-400">
                            {{ assignLawyerForm.errors.user_id }}
                        </div>

                        <div class="flex flex-wrap gap-2.5">
                            <div
                                v-for="user in matter.users"
                                :key="user.id"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-700/80 border border-slate-600 px-3 py-1 text-sm"
                            >
                                <span class="font-semibold text-white">{{ user.name }}</span>
                                <span class="text-xs text-slate-300">({{ user.pivot?.assignment_role || user.role }})</span>
                                <button
                                    v-if="isPartner && matter.users.length > 1"
                                    type="button"
                                    @click="removeLawyer(user)"
                                    :disabled="removingLawyerId === user.id"
                                    title="Remove lawyer from matter"
                                    class="ml-1 text-slate-400 hover:text-red-400 font-bold text-xs leading-none disabled:opacity-50"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Stack -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tasks Section -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-xl font-bold mb-4 border-b pb-2">Tasks</h2>
                            <button v-if="matter.status !== 'Completed'" @click="openTaskModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                + Add Task
                            </button>
                        </div>
                        <table class="w-full text-left" v-if="matter.tasks && matter.tasks.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Title</th>
                                    <th class="p-3">Deadline</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in matter.tasks" :key="task.id" class="border-b">
                                    <td class="p-3 font-medium">{{ task.title }}</td>
                                    <td class="p-3 text-slate-600">{{ new Date(task.deadline).toLocaleDateString() }}</td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full"
                                            :class="task.status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                        >
                                            {{ task.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openTaskModal(task)" class="text-green-500 hover:bg-green-600 font-medium text-sm py-1 px-3 rounded">Edit</button>
                                            <button @click="deleteTask(task)" class="text-red-500 hover:bg-red-600 font-medium text-sm py-1 px-3 rounded">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No tasks assigned to this matter.</p>
                    </div>

                    <!-- Events Section -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-xl font-bold mb-4 border-b pb-2">Events</h2>
                            <button v-if="matter.status !== 'Completed'" @click="openEventModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                + Add Event
                            </button>
                        </div>

                        <table class="w-full text-left" v-if="matter.events && matter.events.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Title</th>
                                    <th class="p-3">Scheduled For</th>
                                    <th class="p-3">Location</th>
                                    <th class="p-3">Actions</th>
                                    <th class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="event in matter.events" :key="event.id" class="border-b">
                                    <td class="p-3 font-medium">{{ event.title }}</td>
                                    <td class="p-3 text-slate-600">{{ new Date(event.scheduled_at).toLocaleString() }}</td>
                                    <td class="p-3 text-slate-600">{{ event.location || 'N/A' }}</td>
                                    <td>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openEventModal(event)" class="text-green-500 text-sm hover:bg-green-600 font-medium py-1 px-3 rounded">Edit</button>
                                            <button @click="deleteEvent(event)" class="text-red-500 text-sm hover:bg-red-600 font-medium py-1 px-3 rounded">Delete</button>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full"
                                            :class="event.status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"
                                        >
                                            {{ event.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No events scheduled.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6 px-12">
            <!-- Time Entries Section -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex flex-wrap justify-between items-center gap-3 border-b pb-2 mb-4">
                    <h2 class="text-xl font-bold">Time Entries (Running Billable — {{ matterCurrency }})</h2>
                    <div class="flex items-center space-x-3">
                        <button
                            v-if="matter.status !== 'Completed'"
                            @click="openTimeEntryModal()"
                            class="bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1.5 rounded text-sm font-semibold hover:bg-purple-200 transition"
                        >
                            + Log Hours
                        </button>
                        <button
                            v-if="isPartner"
                            @click="generateInvoice"
                            class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700 transition"
                        >
                            Generate Invoice
                        </button>
                    </div>
                </div>

                <!-- Validation / Status Feedback -->
                <div v-if="$page.props.errors?.message" class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    {{ $page.props.errors.message }}
                </div>
                <div v-else-if="isPartner && unapprovedHourlyCount > 0" class="mb-4 rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                    {{ unapprovedHourlyCount }} unbilled time {{ unapprovedHourlyCount === 1 ? 'entry awaits' : 'entries await' }} Partner approval before an invoice can be generated.
                </div>

                <div class="overflow-x-auto" v-if="matter.time_entries && matter.time_entries.length > 0">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-sm">
                                <th class="p-3">Lawyer</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Description</th>
                                <th class="p-3 text-right">Logged Hrs</th>
                                <th class="p-3 text-right">Approved Hrs</th>
                                <th class="p-3 text-right">Amount ({{ matterCurrency }})</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in matter.time_entries" :key="entry.id" class="border-b hover:bg-slate-50/70">
                                <td class="p-3 font-medium">
                                    {{ entry.user?.name || 'Unknown User' }}
                                    <span v-if="entry.invoice_id" class="ml-1.5 inline-block px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-200 text-slate-700">
                                        Billed
                                    </span>
                                </td>
                                <td class="p-3 text-slate-600 whitespace-nowrap">{{ new Date(entry.start_time).toLocaleDateString() }}</td>
                                <td class="p-3 text-slate-600">{{ entry.description }}</td>
                                <td class="p-3 text-right text-slate-600 font-mono">{{ Number(entry.hours).toFixed(2) }}</td>

                                <!-- Inline Approved Hours Column -->
                                <td class="p-3 text-right whitespace-nowrap">
                                    <!-- Partner Inline Editor (shown when unapproved OR actively editing inline) -->
                                    <template v-if="isPartner && entry.invoice_id === null && matter.status !== 'Completed' && (entry.approved_hours === null || inlineEditingId === entry.id)">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                :value="getInlineValue(entry)"
                                                @input="inlineDrafts[entry.id] = $event.target.value"
                                                @keyup.enter="saveInlineApproval(entry)"
                                                @keyup.esc="cancelInlineEdit(entry)"
                                                class="w-20 rounded border-slate-300 px-2 py-1 text-right text-xs font-mono shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                            <button
                                                type="button"
                                                @click="saveInlineApproval(entry)"
                                                :disabled="savingInlineId === entry.id"
                                                title="Approve hours"
                                                class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                            >
                                                {{ savingInlineId === entry.id ? '...' : 'Approve' }}
                                            </button>
                                            <button
                                                v-if="inlineEditingId === entry.id"
                                                type="button"
                                                @click="cancelInlineEdit(entry)"
                                                title="Cancel"
                                                class="rounded bg-slate-200 px-1.5 py-1 text-xs text-slate-600 hover:bg-slate-300"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Already Approved Display -->
                                    <template v-else-if="entry.approved_hours !== null">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 font-mono">
                                            {{ Number(entry.approved_hours).toFixed(2) }}
                                            <button
                                                v-if="isPartner && entry.invoice_id === null && matter.status !== 'Completed'"
                                                type="button"
                                                @click="startInlineEdit(entry)"
                                                title="Edit approved hours inline"
                                                class="text-emerald-700 hover:text-emerald-950 underline text-[11px] ml-1"
                                            >
                                                Edit
                                            </button>
                                        </span>
                                    </template>

                                    <!-- Non-partner / Closed Matter Pending Display -->
                                    <template v-else>
                                        <span class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                            Pending
                                        </span>
                                    </template>
                                </td>

                                <td class="p-3 text-right text-slate-800 font-medium whitespace-nowrap">
                                    {{ formatCurrency(entry.amount) }}
                                </td>

                                <!-- Row Actions -->
                                <td class="p-3 text-right whitespace-nowrap">
                                    <div v-if="canManageTimeEntry(entry)" class="inline-flex items-center space-x-2 text-xs">
                                        <button
                                            type="button"
                                            @click="openTimeEntryModal(entry)"
                                            class="text-blue-600 hover:text-blue-800 font-semibold"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteTimeEntry(entry)"
                                            class="text-red-600 hover:text-red-800 font-semibold"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                    <span v-else-if="entry.approved_hours !== null && !isPartner && entry.invoice_id === null" class="text-xs text-slate-400 italic">
                                        Locked (Approved)
                                    </span>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-slate-500 italic">No time logged for this matter yet.</p>
            </div>

            <!-- Invoices Section -->
            <div class="bg-white p-6 rounded-lg shadow mt-6">
                <h2 class="text-xl font-bold mb-4 border-b pb-2">Invoices & Payments ({{ matterCurrency }})</h2>

                <table class="w-full text-left" v-if="matter.invoices && matter.invoices.length > 0">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-sm">
                            <th class="p-3">Invoice #</th>
                            <th class="p-3">Issue Date</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Total Amount</th>
                            <th class="p-3">Total Payments</th>
                            <th class="p-3">Balance Due</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in matter.invoices" :key="invoice.id" class="border-b">
                            <td class="p-3 font-medium">
                                <Link :href="route('invoices.show', invoice.id)" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ invoice.invoice_number }}
                                </Link>
                            </td>
                            <td class="p-3 text-slate-600">{{ new Date(invoice.issue_date).toLocaleDateString() }}</td>
                            <td class="p-3">
                                <span
                                    class="px-2 py-1 text-xs font-bold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': invoice.status === 'Paid',
                                        'bg-blue-100 text-blue-800': invoice.status === 'Sent',
                                        'bg-slate-100 text-slate-800': invoice.status === 'Draft',
                                    }"
                                >
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="p-3 font-medium text-slate-800">
                                {{ formatCurrency(invoice.total_amount) }}
                            </td>
                            <td class="p-3 font-medium text-slate-800">
                                {{ formatCurrency(invoice.total_payments) }}
                            </td>
                            <td class="p-3 font-medium text-slate-800">
                                {{ formatCurrency(invoice.balance_due) }}
                            </td>
                            <td class="p-3 text-right">
                                <button
                                    v-if="invoice.status !== 'Paid'"
                                    @click="openPaymentModal(invoice)"
                                    class="text-sm bg-blue-50 text-blue-600 px-3 py-1 rounded border border-blue-200 hover:bg-blue-600 hover:text-white font-semibold transition"
                                >
                                    Record Payment
                                </button>
                                <button v-else class="text-sm text-slate-500 hover:text-slate-800 underline transition">
                                    View Receipt
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-slate-500 italic">No invoices have been generated for this matter yet.</p>
            </div>

            <!-- Total Unpaid Amount Card -->
            <div class="bg-white p-6 rounded-lg shadow border-b-4 border-amber-500">
                <h3 class="text-gray-500 text-sm font-semibold">Total Unpaid Amount ({{ matterCurrency }})</h3>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    {{ formatCurrency(matter.total_unpaid_amount) }}
                </p>
                <div class="mt-4">
                    <Link :href="route('matters.statement', matter.id)" class="text-xs bg-amber-50 text-amber-700 px-3 py-1.5 rounded font-semibold border border-amber-200 hover:bg-amber-100 transition">
                        View Statement of Account
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Record Payment Modal -->
    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    Payment for {{ selectedInvoice.invoice_number }}
                </h3>
                <button @click="closePaymentModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Amount Received ({{ currencySymbol }} {{ matterCurrency }})</label>
                    <input type="number" step="0.01" v-model="form.amount" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.amount" class="text-red-500 text-xs mt-1">{{ form.errors.amount }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Date</label>
                    <input type="date" v-model="form.payment_date" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.payment_date" class="text-red-500 text-xs mt-1">{{ form.errors.payment_date }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Method</label>
                    <select v-model="form.payment_method" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option>Bank Transfer</option>
                        <option>Check</option>
                        <option>Cash</option>
                        <option>Gcash / E-Wallet</option>
                    </select>
                    <div v-if="form.errors.payment_method" class="text-red-500 text-xs mt-1">{{ form.errors.payment_method }}</div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Reference / Check Number</label>
                    <input type="text" v-model="form.reference_number" placeholder="Optional" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div v-if="form.errors.reference_number" class="text-red-500 text-xs mt-1">{{ form.errors.reference_number }}</div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="closePaymentModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50 flex items-center">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <TaskModal
        :show="isTaskModalOpen"
        :matter-id="matter.id"
        :task="selectedTask"
        @close="isTaskModalOpen = false"
    />

    <EventModal
        :show="isEventModalOpen"
        :matter-id="matter.id"
        :event="selectedEvent"
        @close="isEventModalOpen = false"
    />

    <TimeEntryModal
        :show="isTimeEntryModalOpen"
        :matter-id="matter.id"
        :time-entry="selectedTimeEntry"
        :tasks="matter.tasks"
        :events="matter.events"
        @close="isTimeEntryModalOpen = false"
    />

    <DashboardMatterModal
        :show="isMatterModalOpen"
        :clients="clients"
        :matter="matter"
        @close="isMatterModalOpen = false"
    />
</template>