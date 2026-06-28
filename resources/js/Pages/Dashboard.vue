<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Folder, Zap, DollarSign, Image, Clock } from '@lucide/vue'

const props = defineProps({
    projects: Array,
    stats: Object,
    recentRenders: Array,
})

const projectTypeLabel = {
    fiction_series: 'Serie de ficción',
    youtube_channel: 'Canal YouTube',
    real_estate: 'Inmobiliario',
    commercial: 'Comercial',
    corporate: 'Corporativo',
    social_media: 'Social media',
    client: 'Cliente externo',
}

const statusColor = {
    development: 'text-text-muted',
    pre_production: 'text-info',
    production: 'text-success',
    post_production: 'text-warning',
    completed: 'text-text-secondary',
    archived: 'text-text-muted',
}

const tierColor = {
    draft: 'text-text-muted',
    standard: 'text-info',
    final: 'text-amber',
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <!-- Page header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Studio Dashboard</h1>
                <p class="text-sm text-text-muted mt-0.5">Vista general de tu producci&#243;n</p>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-surface-1 border border-border rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <Zap class="w-4 h-4 text-amber" />
                    <span class="text-xs text-text-muted font-medium">Jobs activos</span>
                </div>
                <span class="text-2xl font-mono font-bold text-text-primary">{{ stats.activeJobs }}</span>
            </div>

            <div class="bg-surface-1 border border-border rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <Image class="w-4 h-4 text-violet" />
                    <span class="text-xs text-text-muted font-medium">Renders</span>
                </div>
                <span class="text-2xl font-mono font-bold text-text-primary">{{ stats.activeRenders }}</span>
            </div>

            <div class="bg-surface-1 border border-border rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <DollarSign class="w-4 h-4 text-success" />
                    <span class="text-xs text-text-muted font-medium">Costo hoy</span>
                </div>
                <span class="text-2xl font-mono font-bold text-text-primary">${{ stats.todayCost }}</span>
            </div>

            <div class="bg-surface-1 border border-border rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <Folder class="w-4 h-4 text-info" />
                    <span class="text-xs text-text-muted font-medium">Proyectos</span>
                </div>
                <span class="text-2xl font-mono font-bold text-text-primary">{{ projects.length }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Projects -->
            <div class="lg:col-span-2">
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide mb-3">Proyectos activos</h2>

                <div v-if="projects.length === 0" class="bg-surface-1 border border-border rounded-xl p-8 text-center">
                    <Folder class="w-8 h-8 text-text-muted mx-auto mb-2" />
                    <p class="text-sm text-text-muted">No hay proyectos a&#250;n</p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="bg-surface-1 border border-border rounded-xl p-4 flex items-center gap-4 hover:border-surface-3 transition-colors"
                    >
                        <div class="w-10 h-10 rounded-lg bg-surface-2 flex items-center justify-center shrink-0">
                            <Folder class="w-5 h-5 text-amber" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-text-primary truncate">{{ project.name }}</span>
                                <span :class="['text-xs font-mono', statusColor[project.status] ?? 'text-text-muted']">
                                    {{ project.status }}
                                </span>
                            </div>
                            <span class="text-xs text-text-muted">{{ projectTypeLabel[project.type] ?? project.type }}</span>
                        </div>
                        <span class="text-xs font-mono text-text-muted shrink-0">
                            {{ project.seasons_count }} temp · {{ project.campaigns_count }} camp
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent renders -->
            <div>
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide mb-3">Renders recientes</h2>

                <div v-if="recentRenders.length === 0" class="bg-surface-1 border border-border rounded-xl p-8 text-center">
                    <Clock class="w-8 h-8 text-text-muted mx-auto mb-2" />
                    <p class="text-sm text-text-muted">Sin actividad a&#250;n</p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="render in recentRenders"
                        :key="render.id"
                        class="bg-surface-1 border border-border rounded-lg p-3 flex items-center gap-3"
                    >
                        <div class="w-8 h-8 rounded bg-surface-2 shrink-0 overflow-hidden">
                            <img v-if="render.thumbnail_url" :src="render.thumbnail_url" class="w-full h-full object-cover" />
                            <Image v-else class="w-4 h-4 text-text-muted m-2" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <span :class="['text-xs font-mono', tierColor[render.quality_tier] ?? 'text-text-muted']">
                                {{ render.quality_tier }}
                            </span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span :class="['w-1.5 h-1.5 rounded-full shrink-0', render.is_approved ? 'bg-success' : 'bg-surface-3']" />
                                <span class="text-xs text-text-muted truncate">{{ render.status }}</span>
                            </div>
                        </div>
                        <span v-if="render.gpu_cost_usd" class="text-xs font-mono text-text-muted shrink-0">
                            ${{ Number(render.gpu_cost_usd).toFixed(3) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
