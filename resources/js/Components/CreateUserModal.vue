<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    role: 'Associate',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('lawyers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div class="border-b border-gray-200 pb-3">
                <h2 class="text-lg font-bold text-gray-900">Add New Lawyer / Staff</h2>
                <p class="text-xs text-gray-500 mt-0.5">Create a user account and assign their firm role.</p>
            </div>

            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Atty. Juan Dela Cruz"
                    required
                    autofocus
                />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    placeholder="jdelacruz@ocbocc.com"
                    required
                />
                <InputError :message="form.errors.email" class="mt-1" />
            </div>

            <div>
                <InputLabel for="role" value="Role" />
                <select
                    id="role"
                    v-model="form.role"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    required
                >
                    <option value="Partner">Partner</option>
                    <option value="Associate">Associate</option>
                    <option value="Paralegal">Paralegal</option>
                    <option value="Admin">Admin</option>
                </select>
                <InputError :message="form.errors.role" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Temporary Password" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.password" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-1" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                <SecondaryButton type="button" @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Lawyer' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>