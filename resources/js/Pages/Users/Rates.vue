<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    lawyer: {
        type: Object,
        required: true, // { id, name, email }
    },
    lawyers: {
        type: Array,
        default: () => [], // [{ id, name, email }, ...]
    },
    rates: {
        type: Array,
        default: () => [],
    },
    activeRate: {
        type: Object,
        default: null,
    },
    ranks: {
        type: Array,
        default: () => [],
    },
    unbilledCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const todayStr = new Date().toISOString().split('T')[0];

const selectedLawyerId = ref(props.lawyer.id);

const form = useForm({
    rank: props.activeRate?.rank || props.ranks[0] || '',
    hourly_rate: props.activeRate?.hourly_rate || '',
    effective_from: todayStr,
    recalculate_unbilled: false,
});

// Sync form defaults and dropdown state when switching to another lawyer
watch(
    () => props.lawyer.id,
    (newId) => {
        selectedLawyerId.value = newId;
        form.clearErrors();
        form.rank = props.activeRate?.rank || props.ranks[0] || '';
        form.hourly_rate = props.activeRate?.hourly_rate || '';
        form.effective_from = todayStr;
        form.recalculate_unbilled = false;
    }
);

const switchLawyer = () => {
    if (selectedLawyerId.value && selectedLawyerId.value !== props.lawyer.id) {
        router.get(route('users.rates.index', selectedLawyerId.value));
    }
};

const deletingId = ref(null);

const submitRate = () => {
    form.post(route('users.rates.store', props.lawyer.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('recalculate_unbilled');
            form.effective_from = todayStr;
            if (props.activeRate?.rank) {
                form.rank = props.activeRate.rank;
            }
            if (props.activeRate?.hourly_rate) {
                form.hourly_rate = props.activeRate.hourly_rate;
            }
        },
    });
};

const confirmDelete = (rate) => {
    if (
        !confirm(
            `Remove ${props.lawyer.name}'s ${rate.rank} rate ($${Number(rate.hourly_rate).toFixed(2)}/hr) effective ${formatDate(rate.effective_from)}?`
        )
    ) {
        return;
    }

    deletingId.value = rate.id;
    router.delete(route('lawyer-rates.destroy', rate.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const getRateStatus = (rate) => {
    if (props.activeRate && props.activeRate.id === rate.id) {
        return 'Active';
    }
    const effectiveDate = rate.effective_from.split('T')[0];
    if (effectiveDate > todayStr) {
        return 'Scheduled';
    }
    return 'Historical';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(Number(amount || 0));
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const [year, month, day] = dateString.split('T')[0].split('-');
    const date = new Date(Number(year), Number(month) - 1, Number(day));
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="`Billing Rates - ${lawyer.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <Link
                            v-if="route().has('lawyers')"
                            :href="route('lawyers')"
                            class="hover:text-gray-800 hover:underline"
                        >
                            Lawyers
                        </Link>
                        <span v-if="route().has('lawyers')">/</span>
                        <span>Rate Schedule</span>
                    </div>

                    <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            Billing Rates
                        </h2>

                        <!-- Lawyer Switcher -->
                        <div v-if="lawyers.length > 0" class="flex items-center gap-2">
                            <label for="lawyer_selector" class="sr-only">Select Lawyer</label>
                            <select
                                id="lawyer_selector"
                                v-model="selectedLawyerId"
                                @change="switchLawyer"
                                class="rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm font-medium text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option
                                    v-for="item in lawyers"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.name }} ({{ item.email }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">
                        Managing schedule for <span class="font-medium text-gray-700">{{ lawyer.name }}</span> ({{ lawyer.email }})
                    </p>
                </div>

                <!-- Summary Badges -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2">
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Current Rate</div>
                        <div class="mt-0.5 text-base font-bold text-gray-900">
                            <template v-if="activeRate">
                                {{ formatCurrency(activeRate.hourly_rate) }}/hr
                                <span class="ml-1 text-xs font-normal text-gray-600">({{ activeRate.rank }})</span>
                            </template>
                            <span v-else class="text-sm font-normal text-amber-600">No active rate set</span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2">
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Unbilled Entries</div>
                        <div class="mt-0.5 text-base font-bold text-gray-900">
                            {{ unbilledCount }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Flash Notifications -->
            <div
                v-if="flashSuccess"
                class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                <span>{{ flashSuccess }}</span>
            </div>

            <div
                v-if="flashError"
                class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                <span>{{ flashError }}</span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column: Add New Rate Form -->
                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Add Rate Schedule</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Assign a rank and hourly billing rate for <span class="font-medium text-gray-700">{{ lawyer.name }}</span>.
                        </p>

                        <form @submit.prevent="submitRate" class="mt-5 space-y-4">
                            <!-- Rank -->
                            <div>
                                <label for="rank" class="block text-sm font-medium text-gray-700">
                                    Rank / Title
                                </label>
                                <select
                                    id="rank"
                                    v-model="form.rank"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="" disabled>Select rank</option>
                                    <option v-for="rankOption in ranks" :key="rankOption" :value="rankOption">
                                        {{ rankOption }}
                                    </option>
                                </select>
                                <p v-if="form.errors.rank" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.rank }}
                                </p>
                            </div>

                            <!-- Hourly Rate -->
                            <div>
                                <label for="hourly_rate" class="block text-sm font-medium text-gray-700">
                                    Hourly Rate (USD)
                                </label>
                                <div class="relative mt-1 rounded-lg shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input
                                        id="hourly_rate"
                                        v-model="form.hourly_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        placeholder="0.00"
                                        class="block w-full rounded-lg border-gray-300 pl-7 pr-12 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-xs text-gray-400">/hr</span>
                                    </div>
                                </div>
                                <p v-if="form.errors.hourly_rate" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.hourly_rate }}
                                </p>
                            </div>

                            <!-- Effective From -->
                            <div>
                                <label for="effective_from" class="block text-sm font-medium text-gray-700">
                                    Effective From
                                </label>
                                <input
                                    id="effective_from"
                                    v-model="form.effective_from"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.effective_from" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.effective_from }}
                                </p>
                            </div>

                            <!-- Recalculate Unbilled Checkbox -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <label class="flex items-start gap-2.5">
                                    <input
                                        v-model="form.recalculate_unbilled"
                                        type="checkbox"
                                        class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-xs text-gray-700">
                                        <span class="font-medium text-gray-900">Recalculate unbilled time entries</span>
                                        <span class="mt-0.5 block text-gray-500">
                                            Update {{ lawyer.name }}'s unbilled entries logged on or after the effective date ({{ unbilledCount }} total unbilled).
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Saving Rate...</span>
                                    <span v-else>Save Rate for {{ lawyer.name }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Rate History Table -->
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                Rate History &amp; Schedule — {{ lawyer.name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Ordered from newest effective date to oldest.
                            </p>
                        </div>

                        <div v-if="rates.length === 0" class="px-6 py-12 text-center">
                            <p class="text-sm font-medium text-gray-900">No billing rates configured</p>
                            <p class="mt-1 text-xs text-gray-500">
                                Use the form on the left to assign an initial rank and hourly rate for {{ lawyer.name }}.
                            </p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Effective From</th>
                                        <th scope="col" class="px-6 py-3">Rank</th>
                                        <th scope="col" class="px-6 py-3">Hourly Rate</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Added By</th>
                                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="rate in rates"
                                        :key="rate.id"
                                        :class="getRateStatus(rate) === 'Active' ? 'bg-blue-50/40' : ''"
                                    >
                                        <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                            {{ formatDate(rate.effective_from) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-gray-700">
                                            {{ rate.rank }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">
                                            {{ formatCurrency(rate.hourly_rate) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                v-if="getRateStatus(rate) === 'Active'"
                                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                            >
                                                Active
                                            </span>
                                            <span
                                                v-else-if="getRateStatus(rate) === 'Scheduled'"
                                                class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                                            >
                                                Scheduled
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                            >
                                                Historical
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                            {{ rate.creator?.name || 'System' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <button
                                                type="button"
                                                @click="confirmDelete(rate)"
                                                :disabled="deletingId === rate.id"
                                                class="rounded-md px-2.5 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50 hover:text-red-800 disabled:opacity-50"
                                            >
                                                {{ deletingId === rate.id ? 'Removing...' : 'Delete' }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>