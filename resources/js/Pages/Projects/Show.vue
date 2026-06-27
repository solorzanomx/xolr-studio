<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, Trash2, Plus, Tv, ChevronRight } from '@lucide/vue'

const props = defineProps({ project: Object })

const showSeasonForm = ref(false)
const seasonForm = useForm({ title: '', description: '', status: 'planned' })

function storeSeason() {
    seasonForm.post(`/projects/${props.project.id}/seasons`, {
        onSuccess: () => { showSeasonForm.value = false; seasonForm.reset() },
    })
}

function deleteProject() {
    if (!confirm(`¿Eliminar "${props.project.name}"?`)) return
    router.delete(`/projects/${props.project.id}`)
}

const statusColor = {
    development:'text-text-muted', pre_production:'text-info', production:'text-success',
    post_production:'text-warning', completed:'text-text-secondary', archived:'text-text-muted',
}
const statusLabel = {
    development:'Desarrollo', pre_production:'Pre-prod', production:'Producción',
    post_production:'Post-prod', completed:'Terminado', archived:'Archivado',
}
const typeLabel = {
    fiction_series:'Serie', youtube_channel:'YouTube', real_estate:'Inmobiliario',
    commercial:'Comercial', corporate:'Corporativo', social_media:'Social', client:'Cliente',
}
const seasonStatusLabel = { planned:'Planeada', writing:'Escritura', production:'Producción', completed:'Completada' }
const seasonStatusColor = { planned:'text-text-muted', writing:'text-info', production:'text-success', completed:'text-text-secondary' }
</script>

<template>
    <Head :title="project.name" />
    <AppLayout>
        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <Link href="/projects" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-3 transition-colors">
                    <ChevronLeft class="w-4 h-4" /> Proyectos
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-text-primary">{{ project.name }}</h1>
                    <span class="text-xs font-mono text-text-muted bg-surface-2 border border-border rounded px-2 py-0.5">{{ typeLabel[project.type] }}</span>
                    <span :class="['text-xs font-mono', statusColor[project.status]]">{{ statusLabel[project.status] }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <Link :href="`/projects/${project.id}/edit`" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                    <Pencil class="w-4 h-4" /> Editar
                </Link>
                <button @click="deleteProject" class="px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-danger hover:bg-danger/10 transition-colors">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main: seasons -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">Temporadas ({{ project.seasons?.length ?? 0 }})</h2>
                    <button @click="showSeasonForm = !showSeasonForm" class="inline-flex items-center gap-2 px-3 py-1.5 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                        <Plus class="w-4 h-4" /> Añadir temporada
                    </button>
                </div>

                <!-- Season form -->
                <div v-if="showSeasonForm" class="bg-surface-1 border border-amber/30 rounded-xl p-4 mb-4 space-y-3">
                    <h3 class="text-sm font-medium text-text-primary">Nueva temporada</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Título (opcional)</label>
                            <input v-model="seasonForm.title" type="text" placeholder="Ej: El Despertar" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Estado</label>
                            <select v-model="seasonForm.status" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="planned">Planeada</option>
                                <option value="writing">Escritura</option>
                                <option value="production">Producción</option>
                                <option value="completed">Completada</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="storeSeason" :disabled="seasonForm.processing" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                        <button @click="showSeasonForm = false; seasonForm.reset()" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                    </div>
                </div>

                <!-- Seasons list -->
                <div v-if="!project.seasons?.length" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                    <Tv class="w-8 h-8 text-text-muted mx-auto mb-2" />
                    <p class="text-sm text-text-muted">Sin temporadas aún</p>
                </div>
                <div v-else class="space-y-2">
                    <Link
                        v-for="season in project.seasons"
                        :key="season.id"
                        :href="`/projects/${project.id}/seasons/${season.id}`"
                        class="flex items-center gap-4 bg-surface-1 border border-border rounded-xl px-5 py-4 hover:border-amber/40 transition-colors group"
                    >
                        <div class="w-10 h-10 rounded-lg bg-surface-2 flex items-center justify-center shrink-0">
                            <span class="text-lg font-mono font-bold text-amber">{{ season.number }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text-primary">
                                Temporada {{ season.number }}{{ season.title ? ` — ${season.title}` : '' }}
                            </p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span :class="['text-xs font-mono', seasonStatusColor[season.status]]">{{ seasonStatusLabel[season.status] }}</span>
                                <span class="text-xs text-text-muted">{{ season.episodes_count }} episodios</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-text-muted group-hover:text-amber transition-colors shrink-0" />
                    </Link>
                </div>
            </div>

            <!-- Sidebar: info -->
            <div class="space-y-4">
                <!-- Brand colors -->
                <div v-if="project.brand_colors?.length" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-3">Colores de marca</p>
                    <div class="flex gap-2 flex-wrap">
                        <div v-for="c in project.brand_colors" :key="c" class="w-8 h-8 rounded-lg border border-border" :style="{ backgroundColor: c }" :title="c" />
                    </div>
                </div>
                <!-- Synopsis -->
                <div v-if="project.synopsis" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Sinopsis</p>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ project.synopsis }}</p>
                </div>
                <!-- Budget -->
                <div v-if="project.monthly_budget_usd" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-1">Presupuesto mensual</p>
                    <p class="text-2xl font-mono font-bold text-success">${{ Number(project.monthly_budget_usd).toLocaleString() }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
