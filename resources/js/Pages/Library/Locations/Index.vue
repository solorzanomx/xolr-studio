<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { MapPin, Plus, Search } from '@lucide/vue'

const props = defineProps({ locations: Object, filters: Object })

const search = ref(props.filters.search ?? '')
const type = ref(props.filters.type ?? '')
const active = ref(!!props.filters.active)

let timer = null
watch([search, type, active], () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/library/locations', {
            search: search.value || undefined,
            type: type.value || undefined,
            active: active.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

const typeColor = { interior: 'text-amber', exterior: 'text-success', mixed: 'text-info' }
const typeLabel = { interior: 'Interior', exterior: 'Exterior', mixed: 'Mixto' }
</script>

<template>
    <Head title="Locaciones — Biblioteca" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Locaciones</h1>
                <p class="text-sm text-text-muted mt-0.5">Escenarios y ambientaciones reutilizables</p>
            </div>
            <Link href="/library/locations/create" class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                <Plus class="w-4 h-4" />
                Nueva locación
            </Link>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input v-model="search" type="text" placeholder="Buscar locación..." class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
            </div>
            <select v-model="type" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                <option value="">Todos los tipos</option>
                <option value="interior">Interior</option>
                <option value="exterior">Exterior</option>
                <option value="mixed">Mixto</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer select-none">
                <input v-model="active" type="checkbox" class="accent-amber" />
                Solo activas
            </label>
        </div>

        <div v-if="locations.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <MapPin class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm">No hay locaciones aún</p>
            <Link href="/library/locations/create" class="mt-3 inline-flex text-sm text-amber hover:underline">Crear la primera</Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <Link
                v-for="loc in locations.data"
                :key="loc.id"
                :href="`/library/locations/${loc.id}`"
                class="group bg-surface-1 border border-border rounded-xl overflow-hidden hover:border-amber/40 transition-colors"
            >
                <div class="aspect-video bg-surface-2 relative">
                    <img v-if="loc.thumbnail_path" :src="loc.thumbnail_path" :alt="loc.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <MapPin class="w-8 h-8 text-text-muted" />
                    </div>
                    <span :class="['absolute top-2 right-2 w-2 h-2 rounded-full', loc.is_active ? 'bg-success' : 'bg-surface-3']" />
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-text-primary truncate">{{ loc.name }}</p>
                    <span :class="['text-xs font-mono', typeColor[loc.type]]">{{ typeLabel[loc.type] }}</span>
                </div>
            </Link>
        </div>

        <div v-if="locations.last_page > 1" class="flex justify-center gap-2 mt-8">
            <Link v-for="link in locations.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                :class="['px-3 py-1.5 text-sm rounded-lg border transition-colors', link.active ? 'bg-amber text-surface-0 border-amber font-semibold' : link.url ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary' : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40']" />
        </div>
    </AppLayout>
</template>
