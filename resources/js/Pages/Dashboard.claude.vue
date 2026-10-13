<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

// Props map 1:1 to what DashboardController@index passes to Inertia::render()
const props = defineProps({
  kpis: { type: Object, default: () => ({ hours: 0, billed: 0, unbilled: 0 }) },
  events: { type: Array, default: () => [] },   // each: { id, title?, scheduled_at, matter: {id, name} }
  tasks: { type: Array, default: () => [] },    // each: { id, title?, deadline, status, matter: {id, name} }
  matters: { type: Array, default: () => [] },  // each: { id, name, status, client: {id, name} }
  clients: { type: Array, default: () => [] },  // each: { id, name }
})

const CURRENCY = 'PHP' // change to 'USD' etc.

const money = (v) =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency: CURRENCY }).format(Number(v) || 0)

const hours = computed(() => (Number(props.kpis.hours) || 0).toFixed(1))

const totalAmount = computed(
  () => (Number(props.kpis.billed) || 0) + (Number(props.kpis.unbilled) || 0)
)
const billedPct = computed(() =>
  totalAmount.value ? Math.round((Number(props.kpis.billed) / totalAmount.value) * 100) : 0
)

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : '—'
const fmtTime = (d) =>
  d ? new Date(d).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : ''

const isOverdue = (d) => d && new Date(d) < new Date()

// Show the next 5 tasks; controller returns all pending ones
const showAllTasks = ref(false)
const visibleTasks = computed(() => (showAllTasks.value ? props.tasks : props.tasks.slice(0, 5)))

// Add Matter modal
const showModal = ref(false)
const form = useForm({ name: '', client_id: '' })

const submit = () => {
  form.post('/matters', {
    onSuccess: () => {
      showModal.value = false
      form.reset()
    },
  })
}
</script>

<template>
  <Head title="Dashboard" />

  <div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
      <!-- Header -->
      <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
          <p class="text-sm text-slate-500">
            {{ new Date().toLocaleDateString(undefined, { month: 'long', year: 'numeric' }) }}
          </p>
        </div>
        <button
          class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2"
          @click="showModal = true"
        >
          Add matter
        </button>
      </header>

      <!-- KPIs: the unbilled figure is the one that needs attention, so it leads -->
      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 md:col-span-1">
          <p class="text-sm text-amber-800">Unbilled this month</p>
          <p class="mt-1 text-3xl font-semibold tabular-nums">{{ money(kpis.unbilled) }}</p>
          <p class="mt-2 text-xs text-amber-800">Time logged but not yet on an invoice</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
          <p class="text-sm text-slate-500">Billed this month</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums">{{ money(kpis.billed) }}</p>
          <div class="mt-3 h-1.5 rounded-full bg-slate-100" role="img" :aria-label="`${billedPct}% of this month's time is billed`">
            <div class="h-1.5 rounded-full bg-emerald-600" :style="{ width: billedPct + '%' }" />
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ billedPct }}% of logged time</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
          <p class="text-sm text-slate-500">Hours logged</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums">{{ hours }}</p>
          <p class="mt-2 text-xs text-slate-500">Your entries this month</p>
        </div>
      </section>

      <div class="grid gap-8 lg:grid-cols-2">
        <!-- Upcoming events -->
        <section>
          <h2 class="mb-3 text-lg font-semibold">Upcoming events</h2>
          <ul v-if="events.length" class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
            <li v-for="event in events" :key="event.id" class="flex items-start gap-4 p-4">
              <div class="w-14 shrink-0 text-center">
                <p class="text-sm font-semibold">{{ fmtDate(event.scheduled_at) }}</p>
                <p class="text-xs text-slate-500">{{ fmtTime(event.scheduled_at) }}</p>
              </div>
              <div class="min-w-0">
                <p class="truncate font-medium">{{ event.title ?? event.name ?? 'Untitled event' }}</p>
                <Link
                  v-if="event.matter"
                  :href="`/matters/${event.matter.id}`"
                  class="text-sm text-slate-500 hover:text-slate-900 hover:underline"
                >
                  {{ event.matter.name }}
                </Link>
              </div>
            </li>
          </ul>
          <p v-else class="rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-500">
            Nothing scheduled. Events you add to a matter will show up here.
          </p>
        </section>

        <!-- Pending tasks -->
        <section>
          <h2 class="mb-3 text-lg font-semibold">Pending tasks</h2>
          <ul v-if="tasks.length" class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
            <li v-for="task in visibleTasks" :key="task.id" class="flex items-start justify-between gap-4 p-4">
              <div class="min-w-0">
                <p class="truncate font-medium">{{ task.title ?? task.name ?? 'Untitled task' }}</p>
                <Link
                  v-if="task.matter"
                  :href="`/matters/${task.matter.id}`"
                  class="text-sm text-slate-500 hover:text-slate-900 hover:underline"
                >
                  {{ task.matter.name }}
                </Link>
              </div>
              <span
                class="shrink-0 rounded px-2 py-0.5 text-xs font-medium"
                :class="isOverdue(task.deadline) ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700'"
              >
                {{ isOverdue(task.deadline) ? 'Overdue ' : 'Due ' }}{{ fmtDate(task.deadline) }}
              </span>
            </li>
          </ul>
          <p v-else class="rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-500">
            No pending tasks.
          </p>
          <button
            v-if="tasks.length > 5"
            class="mt-2 text-sm text-slate-600 hover:text-slate-900 hover:underline"
            @click="showAllTasks = !showAllTasks"
          >
            {{ showAllTasks ? 'Show fewer' : `Show all ${tasks.length} tasks` }}
          </button>
        </section>
      </div>

      <!-- Active matters -->
      <section>
        <h2 class="mb-3 text-lg font-semibold">Active matters</h2>
        <div v-if="matters.length" class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
              <tr>
                <th class="px-4 py-2 font-medium">Matter</th>
                <th class="px-4 py-2 font-medium">Client</th>
                <th class="px-4 py-2 font-medium">Opened</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="matter in matters" :key="matter.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <Link :href="`/matters/${matter.id}`" class="font-medium hover:underline">
                    {{ matter.name }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ matter.client?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-600">{{ fmtDate(matter.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-500">
          No active matters. Use “Add matter” to open your first one.
        </p>
      </section>
    </div>

    <!-- Add matter modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
      @keydown.esc="showModal = false"
      @click.self="showModal = false"
    >
      <div role="dialog" aria-modal="true" aria-labelledby="add-matter-title" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h3 id="add-matter-title" class="text-lg font-semibold">Add matter</h3>

        <div class="mt-4 space-y-4">
          <div>
            <label for="matter-name" class="block text-sm font-medium">Matter name</label>
            <input
              id="matter-name"
              v-model="form.name"
              type="text"
              autofocus
              class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="matter-client" class="block text-sm font-medium">Client</label>
            <select
              id="matter-client"
              v-model="form.client_id"
              class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
            >
              <option value="" disabled>Select a client</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="showModal = false">
            Cancel
          </button>
          <button
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
          >
            Add matter
          </button>
        </div>
      </div>
    </div>
  </div>
</template>