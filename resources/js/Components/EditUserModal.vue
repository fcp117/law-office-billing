<script setup>
import { useForm } from '@inertiajs/vue3';
import { watchEffect, ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    user: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    role: 'Associate',
    standard_hourly_rate: '',
});

watchEffect(() => {
    if (props.user) {
        form.name = props.user.name || '';
        form.email = props.user.email || '';
        form.role = props.user.role || 'Associate';
        form.standard_hourly_rate = props.user.standard_hourly_rate || '';
    }
});

const submit = () => {
    form.put(route('lawyers.update', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const closeModal = () => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    Edit Lawyer Details
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" v-model="form.name" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" v-model="form.email" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Role / Designation *</label>
                    <select v-model="form.role" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        <option value="Partner">Partner</option>
                        <option value="Associate">Associate</option>
                    </select>
                    <div v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Standard Hourly Rate (Php)</label>
                    <input type="number" step="0.01" v-model="form.standard_hourly_rate" placeholder="e.g. 3500.00" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div v-if="form.errors.standard_hourly_rate" class="text-red-500 text-xs mt-1">{{ form.errors.standard_hourly_rate }}</div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>