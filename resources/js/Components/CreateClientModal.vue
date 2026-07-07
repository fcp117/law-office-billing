<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

// Initialize the form with the fields from your table
const form = useForm({
    name: '',
    email: '',
    address: '',
    contact_person: '',
    contact_person_email: '',
    start_date: '',
    retainer_amount: '',
    partner_in_charge: '',
});

const submit = () => {
    form.post(route('clients.store'), {
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
        
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    Create New Client
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form @submit.prevent="submit" id="create-client-form" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div class="md:col-span-2 border-b pb-2 mb-2">
                        <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Primary Information</h4>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client / Company Name *</label>
                        <input type="text" v-model="form.name" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <input type="text" v-model="form.address" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Primary Email</label>
                        <input type="email" v-model="form.email" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="md:col-span-2 border-b pb-2 mb-2 mt-4">
                        <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Contact Person (Optional)</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                        <input type="text" v-model="form.contact_person" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Direct Email</label>
                        <input type="email" v-model="form.contact_person_email" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div class="md:col-span-2 border-b pb-2 mb-2 mt-4">
                        <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Billing & Assignment</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Retainer Amount (Php)</label>
                        <input type="number" step="0.01" v-model="form.retainer_amount" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Partner-in-Charge</label>
                        <input type="text" v-model="form.partner_in_charge" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit" form="create-client-form" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50">
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Client</span>
                </button>
            </div>
            
        </div>
    </div>
</template>