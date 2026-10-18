
<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    clients: {
        type: Array,
        default: () => [],
    },
    matter: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const emptyMatter = () => ({
    client_id: '',
    name: '',
    authority: '',
    status: 'Active',
    billing_type: 'hourly',
    fixed_fee_amount: '',
    settlement_judgment: '',
});

const form = useForm(emptyMatter());


watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.clearErrors();
            return;
        }

        const matter = props.matter;

        if (matter?.id) {
            form.defaults({
                client_id: matter.client_id ?? '',
                name: matter.name ?? '',
                authority: matter.authority ?? '',
                status: matter.status ?? 'Active',
                billing_type: (matter.billing_type || 'hourly').toLowerCase(),
                fixed_fee_amount: matter.fixed_fee_amount ?? '',
                contingency_percentage: matter.contingency_percentage ?? '',
                settlement_judgment: matter.settlement_judgment ?? '',
            });
        } else {
            form.defaults(emptyMatter());
        }

        form.reset();
        form.clearErrors();
    },
    { immediate: true }
);

const submit = () => {

    console.log('Modal visible:', props.show);
    console.log('Matter:', props.matter);
    console.log('Matter ID:', props.matter?.id);
    console.log('Form data:', form.data());
    
    if (form.processing) return;

    if (props.matter?.id) {
        form.put(route('matters.update', props.matter.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
            onError: (errors) => {
                console.error('Matter update validation errors:', errors);
            },
            onFinish: () => {
                console.log('Matter update request finished.');
            },
        });
    } else {
        form.post(route('matters.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
            onError: (errors) => {
                console.error('Matter creation validation errors:', errors);
            },
            onFinish: () => {
                console.log('Matter creation request finished.');
            },
        });
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    {{ matter ? 'Edit Matter' : 'Create New Matter' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- FIX 3: Removed HTML5 "required" attributes so Laravel can safely return red Inertia errors -->
            <form @submit.prevent="submit">
                <div class="p-6 space-y-4">
                    
                    <div v-if="Object.keys(form.errors).length > 0" class="p-3 bg-red-50 border border-red-200 rounded text-red-600 text-xs font-semibold">
                        Please check the highlighted fields below.
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Assign to Client *</label>
                        <select v-model="form.client_id" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="" disabled>Select a client...</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Matter / Case Title *</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Smith vs. Jones" class="w-full rounded-md border-slate-300 shadow-sm">
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Authority / Court</label>
                        <input type="text" v-model="form.authority" placeholder="e.g. Quezon City RTC Br. 22" class="w-full rounded-md border-slate-300 shadow-sm">
                        <div v-if="form.errors.authority" class="text-red-500 text-xs mt-1">{{ form.errors.authority }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Billing Type</label>
                        <select v-model="form.billing_type" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="hourly">Hourly</option>
                            <option value="fixed_rate">Fixed Rate</option>
                            <option value="contingency">Contingency</option>
                            <option value="administrative">Administrative (Internal)</option>
                        </select>
                        <div v-if="form.errors.billing_type" class="text-red-500 text-xs mt-1">{{ form.errors.billing_type }}</div>
                    </div>

                    <div v-if="form.billing_type === 'fixed_rate'">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Fixed Fee Amount *</label>
                        <input type="number" step="0.01" v-model="form.fixed_fee_amount" class="w-full rounded-md border-slate-300 shadow-sm">
                        <div v-if="form.errors.fixed_fee_amount" class="text-red-500 text-xs mt-1">{{ form.errors.fixed_fee_amount }}</div>
                    </div>

                    <div v-if="form.billing_type === 'contingency'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Contingency Percentage *</label>
                            <input type="number" step="0.01" v-model="form.contingency_percentage" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <div v-if="form.errors.contingency_percentage" class="text-red-500 text-xs mt-1">{{ form.errors.contingency_percentage }}</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Settlement / Judgment Amount</label>
                            <input type="number" step="0.01" v-model="form.settlement_judgment" placeholder="Amount if finalized" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <div v-if="form.errors.settlement_judgment" class="text-red-500 text-xs mt-1">{{ form.errors.settlement_judgment }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Initial Status</label>
                        <select v-model="form.status" class="w-full rounded-md border-slate-300 shadow-sm">
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Completed">Completed</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t flex justify-end space-x-3 mt-auto">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>{{ matter?.id ? 'Save Changes' : 'Add Matter' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>