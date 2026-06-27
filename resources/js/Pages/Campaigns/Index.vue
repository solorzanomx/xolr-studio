<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Megaphone, Plus, Search, CheckCircle } from '@lucide/vue'

const props = defineProps({ campaigns: Object, projects: Array, filters: Object })

const search  = ref(props.filters.search ?? '')
const project = ref(props.filters.project ?? '')
const status  = ref(props.filters.status ?? '')

let timer = null
watch([search, project, status], () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/campaigns', {
            search:  search.value || undefined,
            project: project.value || undefined,
            status:  status.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

const typeLabel = {
    real_estate: 'Inmobiliaria', product: 'Producto', brand: 'Marca',
    event: 'Evento', youtube: 'YouTube', social: 'Social Media',
}
const statusColor = {
    planning:   'text-text-muted bg-surface-2',
    production: 'text-info bg-info/10',
    review:     'text-warning bg-warning/10',
    completed:  'text-success bg-success/10',
    archived:   'text-text-muted bg-surface-2',
}
const statusLabel = {
    planning: 'Planeación', production: 'Producción', review: 'Revisión',
    completed: 'Completada', archived: 'Archivada',
}

function progress(campaign) {
    const items = campaign.asset_checklist ?? []
    if (!items.length) return 0
    return Math.round(items.filter(i => i.status === 'completed').length / items.length * 100)
}
</script>

<template>
    <Head title="Campañas" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Campañas</h1>
                <p class="text-sm text-text-muted mt-0.5">Asset production por campaña</p>
            </div>
            <Link href="/campaigns/create" class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                <Plus class="w-4 h-4" /> Nueva campaña
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input v-model="search" type="text" placeholder="Buscar campaña..." class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
            </div>
            <select v-model="project" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                <option value="">Todos los proyectos</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="status" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                <option value="">Todos los estados</option>
                <option value="planning">Planeación</option>
                <option value="production">Producción</option>
                <option value="review">Revisión</option>
                <option value="completed">Completada</option>
                <option value="archived">Archivada</option>
            </select>
        </div>

        <!-- Empty -->
        <div v-if="campaigns.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <Megaphone class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm">No hay campañas aún</p>
            <Link href="/campaigns/create" class="mt-3 inline-flex text-sm text-amber hover:underline">Crear la primera</Link>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="c in campaigns.data"
                :key="c.id"
                :href="`/campaigns/${c.id}`"
                class="group bg-surface-1 border border-border rounded-xl p-5 hover:border-amber/40 transition-colors"
            >
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div>
                        <p class="font-semibold text-text-primary group-hover:text-amber transition-colors">{{ c.name }}</p>
                        <p class="text-xs text-text-muted mt-0.5">{{ c.project?.name }} · {{ typeLabel[c.type] }}</p>
                    </div>
                    <span :class="['text-[10px] font-mono px-1.5 py-0.5 rounded shrink-0', statusColor[c.status]]">
                        {{ statusLabel[c.status] }}
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] text-text-muted">Progreso</span>
                        <span class="text-[10px] font-mono text-amber">
                            {{ (c.asset_checklist ?? []).filter(i => i.status === 'completed').length }}/{{ (c.asset_checklist ?? []).length }} assets
                        </span>
                    </div>
                    <div class="h-1 bg-surface-2 rounded-full overflow-hidden">
                        <div class="h-full bg-amber rounded-full transition-all" :style="{ width: progress(c) + '%' }" />
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-text-muted">
                    <span>{{ c.shots_count }} shots</span>
                    <span v-if="c.property">{{ c.property.name }}</span>
                    <span v-if="c.deadline">{{ new Date(c.deadline).toLocaleDateString('es-MX', { month: 'short', day: 'numeric' }) }}</span>
                </div>
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="campaigns.last_page > 1" class="flex justify-center gap-2 mt-8">
            <Link v-for="link in campaigns.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                :class="['px-3 py-1.5 text-sm rounded-lg border transition-colors', link.active ? 'bg-amber text-surface-0 border-amber font-semibold' : link.url ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary' : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40']" />
        </div>
    </AppLayout>
</template>
