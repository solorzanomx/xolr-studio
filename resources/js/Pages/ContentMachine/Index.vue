<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Lightbulb, Plus, Search, Star, Trash2, ChevronRight, Tag, List, Sparkles } from '@lucide/vue'

const props = defineProps({
    concepts: Object,
    projects: Array,
    series: Array,
    activeProjectId: Number,
    filters: Object,
})

const search     = ref(props.filters.search ?? '')
const status     = ref(props.filters.status ?? '')
const seriesId   = ref(props.filters.series ?? '')
const rating     = ref(props.filters.rating ?? '')
const projectId  = ref(props.filters.project ?? props.activeProjectId ?? '')

let timer = null
watch([search, status, seriesId, rating, projectId], () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/content-machine', {
            search:   search.value  || undefined,
            status:   status.value  || undefined,
            series:   seriesId.value || undefined,
            rating:   rating.value  || undefined,
            project:  projectId.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

// Series inline create
const showSeriesForm = ref(false)
const seriesForm = useForm({ project_id: props.activeProjectId ?? '', name: '', description: '' })

function createSeries() {
    seriesForm.project_id = projectId.value || props.activeProjectId
    seriesForm.post('/content-machine/series', {
        preserveState: true,
        onSuccess: () => { seriesForm.reset(); showSeriesForm.value = false },
    })
}

function deleteConcept(id, title) {
    if (!confirm(`¿Eliminar "${title}"?`)) return
    router.delete(`/content-machine/concepts/${id}`)
}

const statusColors = {
    idea:        'bg-surface-2 text-text-muted',
    scripted:    'bg-violet/20 text-violet',
    production:  'bg-amber/20 text-amber',
    published:   'bg-success/20 text-success',
}
const statusLabels = { idea: 'Idea', scripted: 'Guionizado', production: 'Producción', published: 'Publicado' }
</script>

<template>
    <Head title="Content Machine — Ideas Bank" />
    <AppLayout>
        <div class="flex gap-6 min-h-0">

            <!-- Sidebar — Series Manager -->
            <aside class="w-52 shrink-0">
                <div class="bg-surface-1 border border-border rounded-xl p-3">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Series</p>
                        <button @click="showSeriesForm = !showSeriesForm" class="p-0.5 text-text-muted hover:text-text-primary transition-colors">
                            <Plus class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Inline create series -->
                    <form v-if="showSeriesForm" @submit.prevent="createSeries" class="mb-3 space-y-1.5">
                        <input
                            v-model="seriesForm.name"
                            type="text"
                            placeholder="Nombre de la serie"
                            class="w-full bg-surface-2 border border-border rounded px-2 py-1 text-xs text-text-primary placeholder-text-muted focus:outline-none focus:border-amber"
                        />
                        <div class="flex gap-1">
                            <button type="submit" class="flex-1 bg-amber text-surface-0 text-xs font-semibold rounded px-2 py-1 hover:bg-amber/90">Crear</button>
                            <button type="button" @click="showSeriesForm = false" class="flex-1 bg-surface-2 text-text-muted text-xs rounded px-2 py-1 hover:text-text-primary">Cancelar</button>
                        </div>
                    </form>

                    <!-- All ideas -->
                    <button
                        @click="seriesId = ''"
                        :class="['w-full text-left flex items-center gap-2 px-2 py-1.5 rounded text-xs transition-colors', !seriesId ? 'bg-surface-2 text-text-primary' : 'text-text-secondary hover:text-text-primary hover:bg-surface-2']"
                    >
                        <List class="w-3.5 h-3.5 shrink-0" />
                        Todas las ideas
                    </button>

                    <button
                        v-for="s in series"
                        :key="s.id"
                        @click="seriesId = s.id"
                        :class="['w-full text-left flex items-center gap-2 px-2 py-1.5 rounded text-xs transition-colors mt-0.5', seriesId == s.id ? 'bg-surface-2 text-text-primary' : 'text-text-secondary hover:text-text-primary hover:bg-surface-2']"
                    >
                        <Tag class="w-3.5 h-3.5 shrink-0" />
                        <span class="truncate">{{ s.name }}</span>
                        <span class="ml-auto text-text-muted font-mono text-[10px]">{{ s.concepts_count }}</span>
                    </button>

                    <p v-if="series.length === 0 && !showSeriesForm" class="text-[11px] text-text-muted px-2 py-2">
                        Sin series aún
                    </p>
                </div>

                <!-- Hooks Bank link -->
                <Link href="/content-machine/hooks" class="mt-3 flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-xl text-xs text-text-secondary hover:text-text-primary transition-colors group">
                    <Sparkles class="w-3.5 h-3.5 text-violet" />
                    Banco de Hooks
                    <ChevronRight class="w-3 h-3 ml-auto group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </aside>

            <!-- Main content -->
            <div class="flex-1 min-w-0">
                <!-- Header -->
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h1 class="text-xl font-semibold text-text-primary flex items-center gap-2">
                            <Lightbulb class="w-5 h-5 text-amber" />
                            Ideas Bank
                        </h1>
                        <p class="text-sm text-text-muted mt-0.5">Conceptos de video para The Walking Video Guy</p>
                    </div>
                    <Link
                        :href="`/content-machine/concepts/create${projectId ? '?project_id=' + projectId : ''}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                        Nueva idea
                    </Link>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2 mb-5">
                    <!-- Project selector -->
                    <select v-model="projectId" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos los proyectos</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <!-- Search -->
                    <div class="relative flex-1 min-w-40">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                        <input v-model="search" type="text" placeholder="Buscar concepto..." class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                    </div>

                    <!-- Status -->
                    <select v-model="status" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos los estados</option>
                        <option value="idea">Idea</option>
                        <option value="scripted">Guionizado</option>
                        <option value="production">Producción</option>
                        <option value="published">Publicado</option>
                    </select>

                    <!-- Rating filter -->
                    <select v-model="rating" class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todas las valoraciones</option>
                        <option value="5">★★★★★ 5 estrellas</option>
                        <option value="4">★★★★ 4+</option>
                        <option value="3">★★★ 3+</option>
                    </select>
                </div>

                <!-- Empty state -->
                <div v-if="concepts.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
                    <Lightbulb class="w-10 h-10 text-text-muted mx-auto mb-3" />
                    <p class="text-text-muted text-sm">No hay conceptos aún</p>
                    <Link :href="`/content-machine/concepts/create${projectId ? '?project_id=' + projectId : ''}`" class="mt-3 inline-flex text-sm text-amber hover:underline">
                        Crear el primero
                    </Link>
                </div>

                <!-- Concepts grid -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    <div
                        v-for="concept in concepts.data"
                        :key="concept.id"
                        class="bg-surface-1 border border-border rounded-xl p-4 flex flex-col gap-3 hover:border-border/70 transition-colors"
                    >
                        <!-- Header row -->
                        <div class="flex items-start justify-between gap-2">
                            <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full shrink-0', statusColors[concept.status]]">
                                {{ statusLabels[concept.status] }}
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <Link :href="`/content-machine/concepts/${concept.id}`" class="p-1 text-text-muted hover:text-text-primary transition-colors">
                                    <ChevronRight class="w-3.5 h-3.5" />
                                </Link>
                                <button @click="deleteConcept(concept.id, concept.title)" class="p-1 text-text-muted hover:text-danger transition-colors">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Title -->
                        <Link :href="`/content-machine/concepts/${concept.id}`" class="text-sm font-semibold text-text-primary hover:text-amber transition-colors line-clamp-2 leading-snug">
                            {{ concept.title }}
                        </Link>

                        <!-- Hook preview -->
                        <p v-if="concept.hook" class="text-xs text-text-muted line-clamp-2 leading-relaxed italic">
                            "{{ concept.hook }}"
                        </p>
                        <p v-else class="text-xs text-text-muted opacity-50">Sin hook generado</p>

                        <!-- Footer -->
                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-border">
                            <!-- Stars -->
                            <div class="flex items-center gap-0.5">
                                <Star
                                    v-for="n in 5"
                                    :key="n"
                                    :class="['w-3 h-3 transition-colors', (concept.rating ?? 0) >= n ? 'text-amber fill-amber' : 'text-border']"
                                />
                            </div>
                            <!-- Series + SEO indicator -->
                            <div class="flex items-center gap-2">
                                <span v-if="concept.series" class="text-[10px] text-violet font-mono">{{ concept.series.name }}</span>
                                <span v-if="concept.youtube_seo" class="text-[10px] text-success font-mono">SEO ✓</span>
                                <span v-if="concept.thumbnail_shots_count > 0" class="text-[10px] text-text-muted font-mono">{{ concept.thumbnail_shots_count }} thumb</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="concepts.last_page > 1" class="flex justify-center gap-2 mt-8">
                    <Link v-for="link in concepts.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                        :class="['px-3 py-1.5 text-sm rounded-lg border transition-colors', link.active ? 'bg-amber text-surface-0 border-amber font-semibold' : link.url ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary' : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40']" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
