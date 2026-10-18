<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
    matters: Array,
});

const userForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'Associate',
});

const assignForm = useForm({
    user_id: '',
    matter_id: '',
});

const submitUser = () => {
    userForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => userForm.reset(),
    });
};

const submitAssignment = () => {
    assignForm.post(route('admin.assign'), {
        preserveScroll: true,
        onSuccess: () => assignForm.reset(),
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Administration</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Create User Section -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Create New User</h3>
                    
                    <form @submit.prevent="submitUser" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" v-model="userForm.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="userForm.errors.name" class="text-red-500 text-xs mt-1">{{ userForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" v-model="userForm.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="userForm.errors.email" class="text-red-500 text-xs mt-1">{{ userForm.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" v-model="userForm.password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="userForm.errors.password" class="text-red-500 text-xs mt-1">{{ userForm.errors.password }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select v-model="userForm.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="Admin">Admin / Superuser</option>
                                <option value="Partner">Partner</option>
                                <option value="Associate">Associate</option>
                                <option value="Paralegal">Paralegal</option>
                            </select>
                            <div v-if="userForm.errors.role" class="text-red-500 text-xs mt-1">{{ userForm.errors.role }}</div>
                        </div>

                        <button type="submit" :disabled="userForm.processing" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 disabled:opacity-50">
                            Create User
                        </button>
                    </form>
                </div>

                <!-- Assign User to Matter Section -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Assign User to Matter</h3>
                    
                    <form @submit.prevent="submitAssignment" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select User</label>
                            <select v-model="assignForm.user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="" disabled>Choose a user...</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.role }})
                                </option>
                            </select>
                            <div v-if="assignForm.errors.user_id" class="text-red-500 text-xs mt-1">{{ assignForm.errors.user_id }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select Matter</label>
                            <select v-model="assignForm.matter_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="" disabled>Choose a matter...</option>
                                <option v-for="matter in matters" :key="matter.id" :value="matter.id">
                                    {{ matter.name }}
                                </option>
                            </select>
                            <div v-if="assignForm.errors.matter_id" class="text-red-500 text-xs mt-1">{{ assignForm.errors.matter_id }}</div>
                        </div>
                        
                        <p class="text-xs text-gray-500 italic mt-2">
                            * The selected user's primary role will automatically be assigned as their role in this matter.
                        </p>

                        <button type="submit" :disabled="assignForm.processing" class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 mt-4 disabled:opacity-50">
                            Assign to Matter
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>