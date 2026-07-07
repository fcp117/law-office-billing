<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    client: Object, // The client we are editing
});

const emit = defineEmits(['close']);

// Initialize empty form
const form = useForm({
    name: '',
    email: '',
    address: '',
    contact_person: '',
    contact_person_email: '',
    retainer_amount: '',
    partner_in_charge: '',
});

// Watch for the modal opening to populate the form with the selected client's data
watch(() => props.client, (newClient) => {
    if (newClient) {
        form.name = newClient.name || '';
        form.email = newClient.email || '';
        form.address = newClient.address || '';
        form.contact_person = newClient.contact_person || '';
        form.contact_person_email = newClient.contact_person_email || '';
        form.retainer_amount = newClient.retainer_amount || '';
        form.partner_in_charge = newClient.partner_in_charge || '';
    }
}, { immediate: true });

const submit = () => {
    form.put(route('clients.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Edit Client: {{ form.name }}</h3>
                <button @click="$emit('close')" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form @submit.prevent="submit" id="edit-client-form" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Company Name *</label>
                        <input type="text" v-model="form.name" class="w-full rounded-md border-slate-300" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <input type="text" v-model="form.address" class="w-full rounded-md border-slate-300">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Primary Email *</label>
                        <input type="email" v-model="form.email" class="w-full rounded-md border-slate-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Person</label>
                        <input type="text" v-model="form.contact_person" class="w-full rounded-md border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Email</label>
                        <input type="email" v-model="form.contact_person_email" class="w-full rounded-md border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Retainer (Php)</label>
                        <input type="number" step="0.01" v-model="form.retainer_amount" class="w-full rounded-md border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Partner-in-Charge</label>
                        <input type="text" v-model="form.partner_in_charge" class="w-full rounded-md border-slate-300">
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" @click="$emit('close')" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                <button type="submit" form="edit-client-form" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
            </div>
        </div>
    </div>
</template>