<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft } from '@lucide/vue'

const props = defineProps({ episode: Object })

const form = useForm({
    title:    props.episode.title,
    logline:  props.episode.logline ?? '',
    synopsis: props.episode.synopsis ?? '',
    status:   props.episode.status,
})

function submit() { form.put(`/episodes/${props.episode.id}`) }
</script>

<template>
    <Head :title="`Editar — ${episode.title}`" />
    <AppLayout>
        <div class="max-w-2xl">
            <Link :href="`/episodes/${episode.id}`" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" /> {{ episode.title }}
            </Link>
            <h1 class="text-xl font-semibold text-text-primary mb-6">Editar episodio</h1>
            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Título *</label>
                        <input v-model="form.title" type="text" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.title }" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Estado</label>
                        <select v-model="form.status" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="concept">Concepto</option>
                            <option value="outline">Outline</option>
                            <option value="scripted">Scriptado</option>
                            <option value="production">Producción</option>
                            <option value="completed">Completado</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Logline</label>
                    <input v-model="form.logline" type="text" placeholder="Una línea que resume el episodio..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Sinopsis</label>
                    <textarea v-model="form.synopsis" rows="5" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                    <Link :href="`/episodes/${episode.id}`" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
