<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Plus, Film, ChevronRight, Trash2 } from '@lucide/vue'

const props = defineProps({ project: Object, season: Object })

const showEpForm = ref(false)
const epForm = useForm({ title: '', logline: '', status: 'concept' })

function storeEpisode() {
    epForm.post(`/seasons/${props.season.id}/episodes`, {
        onSuccess: () => { showEpForm.value = false; epForm.reset() },
    })
}

function deleteEpisode(id) {
    if (!confirm('¿Eliminar episodio?')) return
    router.delete(`/episodes/${id}`)
}

const epStatusColor = {
    concept:'text-text-muted', outline:'text-info', scripted:'text-violet',
    production:'text-success', completed:'text-amber', published:'text-success',
}
const epStatusLabel = {
    concept:'Concepto', outline:'Outline', scripted:'Scriptado',
    production:'Producción', completed:'Completado', published:'Publicado',
}
</script>

<template>
    <Head :title="`T${season.number} — ${project.name}`" />
    <AppLayout>
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-text-muted mb-6">
            <Link href="/projects" class="hover:text-text-primary transition-colors">Proyectos</Link>
            <span>/</span>
            <Link :href="`/projects/${project.id}`" class="hover:text-text-primary transition-colors">{{ project.name }}</Link>
            <span>/</span>
            <span class="text-text-primary">Temporada {{ season.number }}</span>
        </div>

        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">
                    Temporada {{ season.number }}{{ season.title ? ` — ${season.title}` : '' }}
                </h1>
                <p class="text-sm text-text-muted mt-0.5">{{ season.episodes?.length ?? 0 }} episodios</p>
            </div>
            <button @click="showEpForm = !showEpForm" class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                <Plus class="w-4 h-4" /> Nuevo episodio
            </button>
        </div>

        <!-- Episode form -->
        <div v-if="showEpForm" class="bg-surface-1 border border-amber/30 rounded-xl p-4 mb-4 space-y-3">
            <h3 class="text-sm font-medium text-text-primary">Nuevo episodio</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-text-muted mb-1">Título *</label>
                    <input v-model="epForm.title" type="text" placeholder="Ej: El primer encuentro" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                </div>
                <div>
                    <label class="block text-xs text-text-muted mb-1">Estado</label>
                    <select v-model="epForm.status" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="concept">Concepto</option>
                        <option value="outline">Outline</option>
                        <option value="scripted">Scriptado</option>
                        <option value="production">Producción</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs text-text-muted mb-1">Logline</label>
                <input v-model="epForm.logline" type="text" placeholder="Una línea que resume el episodio..." class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
            </div>
            <div class="flex gap-3">
                <button @click="storeEpisode" :disabled="epForm.processing" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                <button @click="showEpForm = false; epForm.reset()" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
            </div>
        </div>

        <!-- Episodes list -->
        <div v-if="!season.episodes?.length" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
            <Film class="w-8 h-8 text-text-muted mx-auto mb-2" />
            <p class="text-sm text-text-muted">Sin episodios aún</p>
        </div>
        <div v-else class="space-y-2">
            <div
                v-for="ep in season.episodes"
                :key="ep.id"
                class="flex items-center gap-4 bg-surface-1 border border-border rounded-xl px-5 py-4 hover:border-amber/40 transition-colors group"
            >
                <div class="w-10 h-10 rounded-lg bg-surface-2 flex items-center justify-center shrink-0">
                    <span class="text-sm font-mono font-bold text-amber">{{ String(ep.number).padStart(2, '0') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-text-primary">{{ ep.title }}</p>
                    <div class="flex items-center gap-3 mt-0.5">
                        <span :class="['text-xs font-mono', epStatusColor[ep.status]]">{{ epStatusLabel[ep.status] }}</span>
                        <span class="text-xs text-text-muted">{{ ep.scenes_count }} escenas</span>
                        <span v-if="ep.logline" class="text-xs text-text-muted truncate max-w-48">{{ ep.logline }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="deleteEpisode(ep.id)" class="p-1.5 text-text-muted hover:text-danger transition-colors opacity-0 group-hover:opacity-100">
                        <Trash2 class="w-4 h-4" />
                    </button>
                    <Link :href="`/episodes/${ep.id}`" class="p-1.5 text-text-muted hover:text-amber transition-colors">
                        <ChevronRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
