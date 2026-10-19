<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    matterId: [Number, String],
    timeEntry: Object, 
    tasks: { type: Array, default: () => [] },   // Ensure these are passed from Show.vue
    events: { type: Array, default: () => [] },  // Ensure these are passed from Show.vue
});

const emit = defineEmits(['close']);

// Combine Tasks and Events into one list for the dropdown
const billableOptions = computed(() => {
    const options = [];
    props.tasks.forEach(task => options.push({ id: task.id, type: 'App\\Models\\Task', label: `[Task] ${task.title}` }));
    props.events.forEach(event => options.push({ id: event.id, type: 'App\\Models\\Event', label: `[Event] ${event.title}` }));
    return options;
});

const form = useForm({
    start_time: '',
     /* end_time: '', */
    hours: '',
    description: '',
    billable_selection: '', // A temporary field to hold the combined ID and Type
    billable_id: null,
    billable_type: null,
});

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

watch(() => props.timeEntry, (newEntry) => {
    if (newEntry && newEntry.id) {
        form.start_time = formatDateTime(newEntry.start_time);
        form.end_time = formatDateTime(newEntry.end_time);
        form.hours = newEntry.hours || '';
        form.description = newEntry.description || '';
        if (newEntry.billable_id && newEntry.billable_type) {
            form.billable_selection = `${newEntry.billable_type}|${newEntry.billable_id}`;
        }
    } else {
        form.reset();
    }
}, { immediate: true });

/* watch([() => form.start_time, () => form.end_time], ([start, end]) => {
    if (start && end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const diffMs = endDate - startDate;
        
        if (diffMs > 0) {
            form.hours = (diffMs / (1000 * 60 * 60)).toFixed(2);
        } else {
            form.hours = 0;
        }
    }
}); */

const submit = () => {
    // Split the selection back into the database columns
    if (form.billable_selection) {
        const [type, id] = form.billable_selection.split('|');
        form.billable_type = type;
        form.billable_id = id;
    } else {
        form.billable_type = null;
        form.billable_id = null;
    }

    if (props.timeEntry && props.timeEntry.id) {
        form.put(route('time-entries.update', props.timeEntry.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('matters.time-entries.store', props.matterId), { 
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
                    
                    <!-- NEW SELECTION DROPDOWN -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Link to Task or Event *</label>
                        <select v-model="form.billable_selection" class="w-full rounded-md border-slate-300 shadow-sm bg-white" required>
                            <option value="" disabled>Select a task or event...</option> <!-- Changed this line -->
                            <option v-for="option in billableOptions" :key="option.type + option.id" :value="`${option.type}|${option.id}`">
                                {{ option.label }}
                            </option>
                        </select>
                        <div v-if="form.errors.billable_selection" class="text-red-500 text-xs mt-1">{{ form.errors.billable_selection }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Work Description *</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Start Time *</label>
                            <input type="date" v-model="form.start_time" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        </div>
                        <!-- <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">End Time *</label>
                            <input type="datetime-local" v-model="form.end_time" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        </div> -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Hours *</label>
                            <input type="number" step="0.25" v-model="form.hours" class="w-full rounded-md border-slate-300 shadow-sm">
                            <div v-if="form.errors.hours" class="text-red-500 text-xs mt-1">{{ form.errors.hours }}</div>
                        </div>
                    </div>
                    
                    <!-- <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Hours</label>
                        <input type="number" step="0.01" v-model="form.hours" class="w-full rounded-md border-slate-300 shadow-sm bg-slate-50 text-slate-500 font-bold" readonly>
                        <div v-if="form.errors.hours" class="text-red-500 text-xs mt-1">{{ form.errors.hours }}</div>
                    </div> -->

                    
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t flex justify-end space-x-3 mt-auto">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ timeEntry ? 'Save Changes' : 'Log Time' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>