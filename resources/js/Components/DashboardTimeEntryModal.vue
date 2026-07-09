<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    timeEntry: Object, 
    matters: Array, // Remove this line if updating TimeEntryModal.vue
});

const emit = defineEmits(['close']);

const form = useForm({
    matter_id: '', // Remove this line if updating TimeEntryModal.vue
    start_time: '',
    end_time: '',
    hours: '',
    description: '',
});

// Helper to format dates for datetime-local inputs
const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

// 1. Watch for modal open/close to fill data
watch(() => props.timeEntry, (newEntry) => {
    if (newEntry) {
        form.matter_id = newEntry.matter_id || (newEntry.matter?.id) || ''; // Remove if TimeEntryModal
        form.start_time = formatDateTime(newEntry.start_time);
        form.end_time = formatDateTime(newEntry.end_time);
        form.hours = newEntry.hours || '';
        form.description = newEntry.description || '';
    } else {
        form.reset();
    }
}, { immediate: true });

// 2. SMART FEATURE: Auto-calculate hours when times change
watch([() => form.start_time, () => form.end_time], ([start, end]) => {
    if (start && end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const diffMs = endDate - startDate;
        
        if (diffMs > 0) {
            // Convert milliseconds to hours and round to 2 decimals
            form.hours = (diffMs / (1000 * 60 * 60)).toFixed(2);
        } else {
            form.hours = 0;
        }
    }
});

const submit = () => {
    if (props.timeEntry) {
        form.put(route('time-entries.update', props.timeEntry.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        // Change `form.matter_id` to `props.matterId` if updating TimeEntryModal.vue
        form.post(route('matters.time-entries.store', form.matter_id), { 
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    {{ timeEntry ? 'Edit Time Entry' : 'Log Billable Time' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="p-6 space-y-4">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Assign to Matter *</label>
                        <select v-model="form.matter_id" class="w-full rounded-md border-slate-300 shadow-sm" required :disabled="timeEntry">
                            <option value="" disabled>Select a matter...</option>
                            <option v-for="matter in matters" :key="matter.id" :value="matter.id">
                                {{ matter.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.matter_id" class="text-red-500 text-xs mt-1">{{ form.errors.matter_id }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Start Time *</label>
                            <input type="datetime-local" v-model="form.start_time" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <div v-if="form.errors.start_time" class="text-red-500 text-xs mt-1">{{ form.errors.start_time }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">End Time *</label>
                            <input type="datetime-local" v-model="form.end_time" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <div v-if="form.errors.end_time" class="text-red-500 text-xs mt-1">{{ form.errors.end_time }}</div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Total Hours</label>
                        <input type="number" step="0.01" v-model="form.hours" class="w-full rounded-md border-slate-300 shadow-sm bg-slate-50 text-slate-500 font-bold" readonly>
                        <p class="text-xs text-slate-400 mt-1">Calculated automatically.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Work Description *</label>
                        <textarea v-model="form.description" rows="3" placeholder="Briefly describe the work done..." class="w-full rounded-md border-slate-300 shadow-sm" required></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t flex justify-end space-x-3 mt-auto">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ timeEntry ? 'Save Changes' : 'Log Time' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>