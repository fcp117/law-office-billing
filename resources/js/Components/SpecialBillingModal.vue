<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    arrangement: { type: Object, default: null },
    clients: { type: Array, default: () => [] },
    matters: { type: Array, default: () => [] },
    lawyers: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    client_id: '',
    matter_id: '',
    type: 'retainer',
    beneficiary_user_id: '',
    description: 'Monthly General Retainer Fee',
    amount: '',
    currency: 'PHP',
    is_vatable: true,
    billing_cycle: 'monthly',
    billing_day: 1,
    grouping_mode: 'individual',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    status: 'Active',
});

const form = useForm(emptyForm());

const filteredMatters = computed(() => {
    if (!form.client_id) return [];
    return props.matters.filter((m) => Number(m.client_id) === Number(form.client_id));
});

const requiresLawyer = computed(() => ['secondment', 'directors_fee'].includes(form.type));

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.clearErrors();
            return;
        }
        if (props.arrangement?.id) {
            const a = props.arrangement;
            form.defaults({
                client_id: a.client_id ?? '',
                matter_id: a.matter_id ?? '',
                type: a.type ?? 'retainer',
                beneficiary_user_id: a.beneficiary_user_id ?? '',
                description: a.description ?? '',
                amount: a.amount ?? '',
                currency: a.currency ?? 'PHP',
                is_vatable: Boolean(a.is_vatable),
                billing_cycle: a.billing_cycle ?? 'monthly',
                billing_day: a.billing_day ?? 1,
                grouping_mode: a.grouping_mode ?? 'individual',
                start_date: a.start_date ? a.start_date.split('T')[0] : '',
                end_date: a.end_date ? a.end_date.split('T')[0] : '',
                status: a.status ?? 'Active',
            });
        } else {
            form.defaults(emptyForm());
        }
        form.reset();
        form.clearErrors();
    },
    { immediate: true }
);

const onTypeChange = () => {
    if (props.arrangement?.id) return;
    if (form.type === 'retainer') {
        form.description = 'Monthly General Retainer Fee';
        form.is_vatable = true;
        form.grouping_mode = 'individual';
        form.beneficiary_user_id = '';
    } else if (form.type === 'secondment') {
        form.description = 'Legal Secondment Fee (On-Site Schedule)';
        form.is_vatable = true;
        form.grouping_mode = 'individual';
    } else if (form.type === 'directors_fee') {
        form.description = "Director's Fee";
        form.is_vatable = false;
        form.grouping_mode = 'grouped';
    }
};

const submit = () => {
    if (form.processing) return;
    if (props.arrangement?.id) {
        form.put(route('special-billing.update', props.arrangement.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('special-billing.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[92vh]">
            <div class="bg-slate-50 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    {{ arrangement?.id ? 'Edit Recurring Arrangement' : 'New Recurring Arrangement' }}
                </h3>
                <button @click="emit('close')" class="text-slate-400 hover:text-red-500 font-bold">&times;</button>
            </div>

            <form @submit.prevent="submit" class="overflow-y-auto p-6 space-y-4 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Arrangement Type *</label>
                        <select v-model="form.type" @change="onTypeChange" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="retainer">Retainer Fee</option>
                            <option value="secondment">Secondment Fee</option>
                            <option value="directors_fee">Director's Fee</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Client *</label>
                        <select v-model="form.client_id" @change="form.matter_id = ''" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <option value="" disabled>Select client...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <div v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="requiresLawyer">
                        <label class="block font-semibold text-slate-700 mb-1">
                            {{ form.type === 'directors_fee' ? 'Lawyer-Director (Beneficiary) *' : 'Seconded Lawyer *' }}
                        </label>
                        <select v-model="form.beneficiary_user_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <option value="" disabled>Select lawyer...</option>
                            <option v-for="l in lawyers" :key="l.id" :value="l.id">{{ l.name }} ({{ l.role }})</option>
                        </select>
                        <div v-if="form.errors.beneficiary_user_id" class="text-red-500 text-xs mt-1">{{ form.errors.beneficiary_user_id }}</div>
                    </div>

                    <div :class="requiresLawyer ? '' : 'sm:col-span-2'">
                        <label class="block font-semibold text-slate-700 mb-1">Linked Matter File (Optional)</label>
                        <select v-model="form.matter_id" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">None (Client-level billing)</option>
                            <option v-for="m in filteredMatters" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Invoice Line Description *</label>
                    <input
                        type="text"
                        v-model="form.description"
                        placeholder="e.g. Monthly Secondment Fee (Every Wednesday on-site)"
                        class="w-full rounded-md border-slate-300 shadow-sm"
                        required
                    />
                    <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Currency *</label>
                        <select v-model="form.currency" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="PHP">PHP (₱)</option>
                            <option value="USD">USD ($)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Periodic Amount *</label>
                        <input type="number" step="0.01" v-model="form.amount" class="w-full rounded-md border-slate-300 shadow-sm" required />
                        <div v-if="form.errors.amount" class="text-red-500 text-xs mt-1">{{ form.errors.amount }}</div>
                    </div>

                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_vatable" class="rounded border-slate-300 text-blue-600" />
                            <span class="font-semibold text-slate-700">Subject to 12% VAT</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Billing Cycle *</label>
                        <select v-model="form.billing_cycle" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi_annual">Semi-Annual</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Billing Day (1–28) *</label>
                        <input type="number" min="1" max="28" v-model="form.billing_day" class="w-full rounded-md border-slate-300 shadow-sm" required />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Invoice Grouping *</label>
                        <select v-model="form.grouping_mode" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="grouped">Group per Client &amp; Type</option>
                            <option value="individual">Separate Invoice</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Start Date *</label>
                        <input type="date" v-model="form.start_date" class="w-full rounded-md border-slate-300 shadow-sm" required />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">End Date (Optional)</label>
                        <input type="date" v-model="form.end_date" class="w-full rounded-md border-slate-300 shadow-sm" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="Active">Active</option>
                            <option value="Paused">Paused</option>
                            <option value="Ended">Ended</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="emit('close')" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save Arrangement' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>