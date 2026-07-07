<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    matterId: [Number, String],
    event: Object, 
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    scheduled_at: '',
});

// Format datetime-local string (YYYY-MM-DDTHH:MM)
const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

watch(() => props.event, (newEvent) => {
    if (newEvent) {
        form.title = newEvent.title || '';
        form.scheduled_at = formatDateTime(newEvent.scheduled_at);
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.event) {
        form.put(route('events.update', props.event.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('matters.events.store', props.matterId), {
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
                    {{ event ? 'Edit Event' : 'Create New Event' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- The Form now wraps everything, including the buttons -->
            <form @submit.prevent="submit">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Event Title *</label>
                        <input type="text" v-model="form.title" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Date & Time *</label>
                        <input type="datetime-local" v-model="form.scheduled_at" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        <div v-if="form.errors.scheduled_at" class="text-red-500 text-xs mt-1">{{ form.errors.scheduled_at }}</div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t flex justify-end space-x-3 mt-auto">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                    <!-- Button is now safely inside the form -->
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ event ? 'Save Changes' : 'Add Event' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>