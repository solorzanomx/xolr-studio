<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Star, ArrowLeft } from '@lucide/vue'

const props = defineProps({
    projects: Array,
    series: Array,
    selectedProjectId: Number,
})

const form = useForm({
    project_id:      props.selectedProjectId ?? '',
    video_series_id: '',
    title:           '',
    hook:            '',
    status:          'idea',
    rating:          null,
})

const availableSeries = ref(props.series ?? [])

watch(() => form.project_id, async (newId) => {
    if (!newId) { availableSeries.value = []; return }
    const res = await fetch(`/content-machine?project=${newId}&_series_only=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    // simple approach: filter from projects prop is not available, just reset
    availableSeries.value = []
    form.video_series_id = ''
})

function setRating(n) {
    form.rating = form.rating === n ? null : n
}
</script>

<template>
    <Head title="Nueva Idea — Content Machine" />
    <AppLayout>
        <div class="max-w-xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link href="/content-machine" class="p-1.5 text-text-muted hover:text-text-primary transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Nueva Idea</h1>
                    <p class="text-sm text-text-muted mt-0.5">Agrega una idea al banco — el AI puede desarrollarla después</p>
                </div>
            </div>

            <form @submit.prevent="form.post('/content-machine/concepts')" class="space-y-4">
                <div class="bg-surface-1 border border-border rounded-xl p-5 space-y-4">

                    <!-- Project -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1.5">Proyecto</label>
                        <select v-model="form.project_id" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="">Seleccionar proyecto...</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <p v-if="form.errors.project_id" class="mt-1 text-xs text-danger">{{ form.errors.project_id }}</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1.5">Título del video *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Ej: Walking Tour por el Centro Histórico de Oaxaca 4K"
                            class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                            required
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-danger">{{ form.errors.title }}</p>
                    </div>

                    <!-- Hook (optional, AI can generate) -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1.5">
                            Hook inicial
                            <span class="text-text-muted font-normal ml-1">(o deja vacío para generar con IA)</span>
                        </label>
                        <textarea
                            v-model="form.hook"
                            rows="2"
                            placeholder="¿Sabías que Oaxaca tiene más de 700 años de historia...?"
                            class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                        />
                    </div>

                    <!-- Series + Status row -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-text-secondary mb-1.5">Serie</label>
                            <select v-model="form.video_series_id" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="">Sin serie</option>
                                <option v-for="s in availableSeries" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-secondary mb-1.5">Estado</label>
                            <select v-model="form.status" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="idea">Idea</option>
                                <option value="scripted">Guionizado</option>
                                <option value="production">Producción</option>
                                <option value="published">Publicado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">Valoración de la idea</label>
                        <div class="flex gap-1">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                @click="setRating(n)"
                                :class="['p-1 transition-colors', (form.rating ?? 0) >= n ? 'text-amber' : 'text-border hover:text-amber/50']"
                            >
                                <Star :class="['w-5 h-5', (form.rating ?? 0) >= n ? 'fill-amber' : '']" />
                            </button>
                            <span v-if="form.rating" class="ml-2 text-sm text-text-muted self-center">{{ form.rating }}/5</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link href="/content-machine" class="px-4 py-2 text-sm text-text-muted hover:text-text-primary transition-colors">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Crear idea' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
