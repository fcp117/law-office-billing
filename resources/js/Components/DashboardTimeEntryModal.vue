<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, computed, ref } from 'vue';

const props = defineProps({
    show: Boolean,
    timeEntry: Object, 
    matters: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const showQuickCreate = ref(false);
const newBillableType = ref('App\\Models\\Task');
const newBillableTitle = ref('');

const form = useForm({
    matter_id: '',
    start_time: '',
    hours: '',
    description: '',
    billable_selection: '',
    billable_id: null,
    billable_type: null,
});

// 1. Get tasks and events for the selected matter
const selectedMatter = computed(() => {
    return props.matters.find(m => m.id === form.matter_id);
});

const billableOptions = computed(() => {
    if (!selectedMatter.value) return [];
    
    const options = [];
    (selectedMatter.value.tasks || []).forEach(task => {
        options.push({ id: task.id, type: 'App\\Models\\Task', label: `[Task] ${task.title}` });
    });
    (selectedMatter.value.events || []).forEach(event => {
        options.push({ id: event.id, type: 'App\\Models\\Event', label: `[Event] ${event.title}` });
    });
    return options;
});

// Reset billable_selection when matter changes
watch(() => form.matter_id, () => {
    if (!props.timeEntry) {
        form.billable_selection = '';
    }
});

watch(() => props.timeEntry, (newEntry) => {
    if (newEntry) {
        form.matter_id = newEntry.matter_id || (newEntry.matter?.id) || '';
        form.start_time = newEntry.start_time ? newEntry.start_time.slice(0, 10) : '';
        form.hours = newEntry.hours || '';
        form.description = newEntry.description || '';
        if (newEntry.billable_id && newEntry.billable_type) {
            form.billable_selection = `${newEntry.billable_type}|${newEntry.billable_id}`;
        }
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (form.billable_selection) {
        const [type, id] = form.billable_selection.split('|');
        form.billable_type = type;
        form.billable_id = id;
    } else {
        form.billable_type = null;
        form.billable_id = null;
    }

    if (props.timeEntry) {
        form.put(route('time-entries.update', props.timeEntry.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
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
    showQuickCreate.value = false;
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
                    <!-- MATTER SELECTOR -->
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

                    <!-- TASK / EVENT DROPDOWN -->
                    <div v-if="form.matter_id">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-sm font-semibold text-slate-700">Link to Task or Event</label>
                        </div>

                        <select v-model="form.billable_selection" class="w-full rounded-md border-slate-300 shadow-sm bg-white">
                            <option value="">None (General Matter Time)</option>
                            <option v-for="option in billableOptions" :key="option.type + option.id" :value="`${option.type}|${option.id}`">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- DATE AND HOURS -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Date *</label>
                            <input type="date" v-model="form.start_time" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <div v-if="form.errors.start_time" class="text-red-500 text-xs mt-1">{{ form.errors.start_time }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Hours *</label>
                            <input type="number" step="0.25" v-model="form.hours" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <div v-if="form.errors.hours" class="text-red-500 text-xs mt-1">{{ form.errors.hours }}</div>
                        </div>
                    </div>

                    <!-- WORK DESCRIPTION -->
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