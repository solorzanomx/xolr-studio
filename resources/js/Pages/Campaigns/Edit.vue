<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Image, Video, Mic, CheckSquare } from '@lucide/vue'

const props = defineProps({ campaign: Object, projects: Array })

const form = useForm({
    name:        props.campaign.name,
    project_id:  props.campaign.project_id,
    property_id: props.campaign.property_id ?? null,
    type:        props.campaign.type,
    description: props.campaign.description ?? '',
    status:      props.campaign.status,
    deadline:    props.campaign.deadline ?? '',
})

const selectedProject = computed(() =>
    props.projects.find(p => String(p.id) === String(form.project_id))
)

const checklistPreview = computed(() => {
    const templates = {
        real_estate: [
            { label: 'Render de fachada exterior', shot_type: 'image' },
            { label: 'Interior — sala', shot_type: 'image' },
            { label: 'Interior — cocina', shot_type: 'image' },
            { label: 'Interior — recámara principal', shot_type: 'image' },
            { label: 'Foto broker en propiedad', shot_type: 'image' },
            { label: 'Video presentación 60s', shot_type: 'talking' },
            { label: 'Carrusel frame 1', shot_type: 'image' },
            { label: 'Carrusel frame 2', shot_type: 'image' },
            { label: 'Carrusel frame 3', shot_type: 'image' },
            { label: 'Story vertical 9:16', shot_type: 'image' },
            { label: 'Thumbnail de reel', shot_type: 'image' },
        ],
        youtube: [
            { label: 'Thumbnail principal', shot_type: 'image' },
            { label: 'Thumbnail variante A', shot_type: 'image' },
            { label: 'Thumbnail variante B', shot_type: 'image' },
            { label: 'Intro del host', shot_type: 'talking' },
            { label: 'Hero visual del destino', shot_type: 'image' },
        ],
        social: [
            { label: 'Hero visual', shot_type: 'image' },
            { label: 'Post principal', shot_type: 'image' },
            { label: 'Story vertical', shot_type: 'image' },
        ],
    }
    return templates[form.type] ?? templates.social
})

const shotIcon = { image: Image, video: Video, talking: Mic }

function submit() { form.put(`/campaigns/${props.campaign.id}`) }
</script>

<template>
    <Head :title="`Editar — ${campaign.name}`" />
    <AppLayout>
        <div class="max-w-3xl">
            <Link :href="`/campaigns/${campaign.id}`" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" /> {{ campaign.name }}
            </Link>
            <h1 class="text-xl font-semibold text-text-primary mb-6">Editar campaña</h1>

            <div class="grid grid-cols-3 gap-6">
                <!-- Form -->
                <form @submit.prevent="submit" class="col-span-2 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                        <input v-model="form.name" type="text" placeholder="Ej: Torre Ándalus — Lanzamiento" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-xs text-danger mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Proyecto *</label>
                            <select v-model="form.project_id" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="">Seleccionar...</option>
                                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Tipo *</label>
                            <select v-model="form.type" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="real_estate">Inmobiliaria</option>
                                <option value="product">Producto</option>
                                <option value="brand">Marca</option>
                                <option value="event">Evento</option>
                                <option value="youtube">YouTube</option>
                                <option value="social">Social Media</option>
                            </select>
                        </div>
                    </div>

                    <!-- Propiedad (solo si hay proyecto seleccionado con propiedades) -->
                    <div v-if="selectedProject?.properties?.length">
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Propiedad</label>
                        <select v-model="form.property_id" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option :value="null">Sin propiedad</option>
                            <option v-for="prop in selectedProject.properties" :key="prop.id" :value="prop.id">{{ prop.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Estado</label>
                            <select v-model="form.status" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="planning">Planeación</option>
                                <option value="production">Producción</option>
                                <option value="review">Revisión</option>
                                <option value="completed">Completada</option>
                                <option value="archived">Archivada</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Deadline</label>
                            <input v-model="form.deadline" type="date" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción</label>
                        <textarea v-model="form.description" rows="3" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                        </button>
                        <Link :href="`/campaigns/${campaign.id}`" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                    </div>
                </form>

                <!-- Checklist preview -->
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <CheckSquare class="w-4 h-4 text-amber" />
                        <p class="text-xs font-medium text-text-secondary">Checklist actual</p>
                    </div>
                    <div class="space-y-1.5">
                        <div v-for="item in campaign.asset_checklist ?? checklistPreview" :key="item.label ?? item.id" class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded border border-border shrink-0" />
                            <component :is="shotIcon[item.shot_type]" class="w-3 h-3 text-text-muted shrink-0" />
                            <span class="text-[11px] text-text-secondary">{{ item.label }}</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-text-muted mt-3">
                        {{ (campaign.asset_checklist ?? checklistPreview).length }} assets
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
