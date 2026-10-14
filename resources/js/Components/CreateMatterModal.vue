<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    clientId: [Number, String],
});

const emit = defineEmits(['close']);

const form = useForm({
    client_id: props.clientId,
    name: '',
    authority: '',
    status: 'Active',
    billing_type: 'hourly',
    fixed_fee_amount: '',
});

watch(() => props.clientId, (newId) => {
    form.client_id = newId;
});

const submit = () => {
    form.post(route('matters.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    Create New Matter
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form @submit.prevent="submit" id="create-matter-form" class="space-y-4">
                    <div v-if="form.errors.client_id" class="text-red-500 text-xs mb-2">{{ form.errors.client_id }}</div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Matter / Case Title *</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Smith vs. Jones" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Authority / Court</label>
                        <input type="text" v-model="form.authority" placeholder="e.g. Quezon City RTC Br. 22" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div v-if="form.errors.authority" class="text-red-500 text-xs mt-1">{{ form.errors.authority }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Billing Type</label>
                        <select v-model="form.billing_type" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="hourly">Hourly</option>
                            <option value="fixed_rate">Fixed Rate</option>
                            <option value="administrative">Administrative (Internal)</option>
                        </select>
                        <div v-if="form.errors.billing_type" class="text-red-500 text-xs mt-1">{{ form.errors.billing_type }}</div>
                    </div>

                    <div v-if="form.billing_type === 'fixed_rate'">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Fixed Fee Amount *</label>
                        <input type="number" step="0.01" v-model="form.fixed_fee_amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        <div v-if="form.errors.fixed_fee_amount" class="text-red-500 text-xs mt-1">{{ form.errors.fixed_fee_amount }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Initial Status</label>
                        <select v-model="form.status" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit" form="create-matter-form" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50">
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Matter</span>
                </button>
            </div>
            
        </div>
    </div>
</template>