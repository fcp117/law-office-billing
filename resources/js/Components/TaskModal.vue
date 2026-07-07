<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    matterId: [Number, String],
    task: Object, // If passed, we are Editing. If null, we are Creating.
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    deadline: '',
    status: 'Pending',
});

// Watch for the modal opening to populate the form if we are editing
watch(() => props.task, (newTask) => {
    if (newTask) {
        form.title = newTask.title || '';
        form.deadline = newTask.deadline ? newTask.deadline.split('T')[0] : '';
        form.status = newTask.status || 'Pending';
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.task) {
        // Edit Mode
        form.put(route('tasks.update', props.task.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        // Create Mode
        form.post(route('matters.tasks.store', props.matterId), {
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
                    {{ task ? 'Edit Task' : 'Create New Task' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- The Form now wraps everything, including the buttons -->
            <form @submit.prevent="submit">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Task Title *</label>
                        <input type="text" v-model="form.title" class="w-full rounded-md border-slate-300 shadow-sm" required>
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>              
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Deadline *</label>
                            <input type="date" v-model="form.deadline" class="w-full rounded-md border-slate-300 shadow-sm" required>
                            <div v-if="form.errors.deadline" class="text-red-500 text-xs mt-1">{{ form.errors.deadline }}</div>
                        </div>
                        <div v-if="task">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
                            <select v-model="form.status" class="w-full rounded-md border-slate-300 shadow-sm">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t flex justify-end space-x-3 mt-auto">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-200">Cancel</button>
                    <!-- Button is now safely inside the form -->
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ task ? 'Save Changes' : 'Add Task' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>