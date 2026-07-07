<script setup>
import {Head, Link} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    matters: Array
});
</script>


<template>
    <Head title="OCBOCC Case Information Management System" />

    <AuthenticatedLayout>

    
        <table>
            <thead>
                <tr class="bg-slate-50 text-slate-600 text-sm">
                    <th class="p-3">Name</th>
                    <th class="p-3">Description</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Practice Area</th>
                    <th class="p-3">Type</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="matter in matters" :key="matter.id" class="border-b">
                    <td class="p-3 font-medium">{{ matter.name }}</td>
                    <td class="p-3 text-slate-600">{{matter.description}}</td>
                    <td class="p-3 text-slate-600">{{ matter.client.name }}</td>
                    <td class="p-3 text-slate-600">{{ matter.practice_area }}</td>
                    <td class="p-3 text-slate-600">{{matter.type}}</td>
                </tr>
            
            </tbody>
        </table>
    

    </AuthenticatedLayout>
</template>


    
