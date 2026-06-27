<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Sparkles, ChevronRight, Clock, CheckCircle, XCircle, Loader, Play, Eye } from '@lucide/vue'

const props = defineProps({
    episodes: Object,
})

const STATUS_MAP = {
    pending:    { label: 'Pendiente',    color: 'text-text-muted',  icon: Clock },
    processing: { label: 'Procesando',   color: 'text-amber',       icon: Loader },
    completed:  { label: 'Listo',        color: 'text-violet',      icon: CheckCircle },
    failed:     { label: 'Error',        color: 'text-danger',      icon: XCircle },
    applied:    { label: 'Aplicado',     color: 'text-evergreen',   icon: CheckCircle },
}

function latestResult(episode) {
    return episode.ai_director_results?.[0] ?? null
}

function runDirector(episodeId) {
    router.post(`/episodes/${episodeId}/ai-director`)
}
</script>

<template>
    <AppLayout>
        <Head title="AI Director" />

        <div class="max-w-4xl mx-auto px-6 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-1">
                    <Sparkles class="w-5 h-5 text-violet" />
                    <h1 class="text-xl font-semibold text-text-primary">AI Director</h1>
                </div>
                <p class="text-sm text-text-muted">
                    Analiza el script de un episodio y genera automáticamente la estructura de escenas y shots.
                </p>
            </div>

            <!-- Empty state -->
            <div v-if="!episodes.data?.length" class="text-center py-16 border border-dashed border-border rounded-xl">
                <Sparkles class="w-10 h-10 text-text-muted mx-auto mb-3" />
                <p class="text-text-muted text-sm">No hay episodios aún.</p>
                <Link href="/projects" class="text-violet text-sm hover:underline mt-1 inline-block">Ir a Proyectos</Link>
            </div>

            <!-- Episodes list -->
            <div v-else class="space-y-3">
                <div
                    v-for="episode in episodes.data"
                    :key="episode.id"
                    class="bg-surface-1 border border-border rounded-xl p-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-[10px] font-mono text-text-muted uppercase tracking-widest">
                                    {{ episode.season?.project?.name }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-text-primary truncate">
                                E{{ episode.number }} — {{ episode.title }}
                            </p>
                            <p v-if="episode.logline" class="text-xs text-text-muted mt-0.5 truncate">
                                {{ episode.logline }}
                            </p>
                        </div>

                        <!-- Status + actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <template v-if="latestResult(episode)">
                                <div class="flex items-center gap-1.5">
                                    <component
                                        :is="STATUS_MAP[latestResult(episode).status]?.icon ?? Clock"
                                        class="w-3.5 h-3.5"
                                        :class="STATUS_MAP[latestResult(episode).status]?.color"
                                    />
                                    <span class="text-xs" :class="STATUS_MAP[latestResult(episode).status]?.color">
                                        {{ STATUS_MAP[latestResult(episode).status]?.label }}
                                    </span>
                                </div>

                                <Link
                                    v-if="latestResult(episode).status === 'completed'"
                                    :href="`/ai-director/${latestResult(episode).id}`"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-violet/10 text-violet text-xs font-medium rounded-lg hover:bg-violet/20 transition-colors"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    Revisar
                                </Link>

                                <button
                                    v-if="['completed', 'failed', 'applied'].includes(latestResult(episode).status)"
                                    @click="runDirector(episode.id)"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-surface-2 text-text-muted text-xs rounded-lg hover:text-text-primary hover:bg-surface-3 transition-colors"
                                >
                                    <Play class="w-3.5 h-3.5" />
                                    Reanálisis
                                </button>
                            </template>

                            <template v-else>
                                <button
                                    @click="runDirector(episode.id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-violet text-white text-xs font-semibold rounded-lg hover:bg-violet/90 transition-colors"
                                >
                                    <Sparkles class="w-3.5 h-3.5" />
                                    Analizar
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Ghost Director profile summary -->
                    <div
                        v-if="latestResult(episode)?.ghost_profile_snapshot?.total_approved_renders > 0"
                        class="mt-3 pt-3 border-t border-border flex items-center gap-4 flex-wrap"
                    >
                        <span class="text-[10px] text-text-muted font-mono uppercase tracking-widest">Ghost Director</span>
                        <div class="flex gap-2 flex-wrap">
                            <span
                                v-for="(count, style) in latestResult(episode).ghost_profile_snapshot.top_camera_styles"
                                :key="style"
                                class="text-[10px] px-1.5 py-0.5 bg-amber/10 text-amber rounded"
                            >
                                {{ style }}
                            </span>
                            <span
                                v-for="(count, style) in latestResult(episode).ghost_profile_snapshot.top_visual_styles"
                                :key="style"
                                class="text-[10px] px-1.5 py-0.5 bg-violet/10 text-violet rounded"
                            >
                                {{ style }}
                            </span>
                        </div>
                        <span class="text-[10px] text-text-muted">
                            {{ latestResult(episode).ghost_profile_snapshot.total_approved_renders }} renders aprobados
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="episodes.last_page > 1" class="flex justify-center gap-2 mt-8">
                <Link
                    v-for="link in episodes.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 text-xs rounded-lg border transition-colors',
                        link.active ? 'bg-violet text-white border-violet' : 'border-border text-text-muted hover:text-text-primary',
                        !link.url ? 'opacity-40 pointer-events-none' : ''
                    ]"
                />
            </div>
        </div>
    </AppLayout>
</template>
