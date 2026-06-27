<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Folder, Plus, Search } from '@lucide/vue'

const props = defineProps({ projects: Object, filters: Object })

const search = ref(props.filters.search ?? '')
const type   = ref(props.filters.type ?? '')
const status = ref(props.filters.status ?? '')

let timer = null
watch([search, type, status], () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/projects', {
            search: search.value || undefined,
            type:   type.value || undefined,
            status: status.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

const typeLabel = {
    fiction_series: 'Serie', youtube_channel: 'YouTube', real_estate: 'Inmobiliario',
    commercial: 'Comercial', corporate: 'Corporativo', social_media: 'Social', client: 'Cliente',
}

const statusColor = {
    development:    'text-text-muted bg-surface-2',
    pre_production: 'text-info bg-info/10',
    production:     'text-success bg-success/10',
    post_production:'text-warning bg-warning/10',
    completed:      'text-text-secondary bg-surface-2',
    archived:       'text-text-muted bg-surface-2',
}

const statusLabel = {
    development:'Desarrollo', pre_production:'Pre-producción', production:'Producción',
    post_production:'Post-producción', completed:'Terminado', archived:'Archivado',
}
</script>

<template>
    <Head title="Proyectos" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Proyectos</h1>
                <p class="text-sm text-text-muted mt-0.5">Todos tus proyectos de producción</p>
            </div>
            <Link href="/projects/create" class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                <Plus class="w-4 h-4" />
                Nuevo proyecto
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input v-model="search" type="text" placeholder="Buscar proyecto..." class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
            </div>
            <select v-model="type" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                <option value="">Todos los tipos</option>
                <option value="fiction_series">Serie de ficción</option>
                <option value="youtube_channel">Canal YouTube</option>
                <option value="real_estate">Inmobiliario</option>
                <option value="commercial">Comercial</option>
                <option value="corporate">Corporativo</option>
                <option value="social_media">Social media</option>
                <option value="client">Cliente externo</option>
            </select>
            <select v-model="status" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                <option value="">Todos los estados</option>
                <option value="development">Desarrollo</option>
                <option value="pre_production">Pre-producción</option>
                <option value="production">Producción</option>
                <option value="post_production">Post-producción</option>
                <option value="completed">Terminado</option>
                <option value="archived">Archivado</option>
            </select>
        </div>

        <!-- Empty -->
        <div v-if="projects.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <Folder class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm">No hay proyectos aún</p>
            <Link href="/projects/create" class="mt-3 inline-flex text-sm text-amber hover:underline">Crear el primero</Link>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="`/projects/${project.id}`"
                class="group bg-surface-1 border border-border rounded-xl overflow-hidden hover:border-amber/40 transition-colors"
            >
                <div class="aspect-video bg-surface-2 relative">
                    <img v-if="project.thumbnail_path" :src="project.thumbnail_path" :alt="project.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <Folder class="w-10 h-10 text-text-muted" />
                    </div>
                    <!-- Brand colors strip -->
                    <div v-if="project.brand_colors?.length" class="absolute bottom-0 left-0 right-0 h-1 flex">
                        <div v-for="c in project.brand_colors.slice(0,5)" :key="c" class="flex-1" :style="{ backgroundColor: c }" />
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="font-semibold text-text-primary group-hover:text-amber transition-colors">{{ project.name }}</p>
                        <span :class="['text-[10px] font-mono px-1.5 py-0.5 rounded shrink-0', statusColor[project.status]]">
                            {{ statusLabel[project.status] }}
                        </span>
                    </div>
                    <p class="text-xs text-text-muted mb-3">{{ typeLabel[project.type] }}</p>
                    <div class="flex items-center gap-4 text-xs font-mono text-text-muted">
                        <span>{{ project.seasons_count }} temp</span>
                        <span>{{ project.campaigns_count }} camp</span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="projects.last_page > 1" class="flex justify-center gap-2 mt-8">
            <Link v-for="link in projects.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                :class="['px-3 py-1.5 text-sm rounded-lg border transition-colors', link.active ? 'bg-amber text-surface-0 border-amber font-semibold' : link.url ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary' : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40']" />
        </div>
    </AppLayout>
</template>
