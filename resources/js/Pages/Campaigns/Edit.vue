<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Image, Video, Mic, CheckSquare, User } from '@lucide/vue'

const props = defineProps({ campaign: Object, projects: Array, virtualTalents: Array })

const form = useForm({
    name:               props.campaign.name,
    project_id:         props.campaign.project_id,
    property_id:        props.campaign.property_id ?? null,
    virtual_talent_id:  props.campaign.virtual_talent_id ?? null,
    type:               props.campaign.type,
    template:           props.campaign.template ?? 'launch',
    description:        props.campaign.description ?? '',
    status:             props.campaign.status,
    deadline:           props.campaign.deadline ?? '',
})

// Sincronizar type con template
watch(() => form.template, (t) => {
    form.type = ['launch','available','tips','market_analysis','closing'].includes(t)
        ? 'real_estate'
        : t === 'youtube' ? 'youtube' : 'social'
})

const selectedProject = computed(() =>
    props.projects.find(p => String(p.id) === String(form.project_id))
)

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
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Template de campaña *</label>
                            <select v-model="form.template" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <optgroup label="Home del Valle">
                                    <option value="launch">Lanzamiento de desarrollo</option>
                                    <option value="available">Propiedad disponible</option>
                                    <option value="tips">Tips informativos (Sofía)</option>
                                    <option value="market_analysis">Análisis de mercado (Diego)</option>
                                    <option value="closing">Cierre exitoso</option>
                                </optgroup>
                                <optgroup label="Otros">
                                    <option value="youtube">YouTube</option>
                                    <option value="social">Social Media</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Virtual Talent -->
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">
                            <User class="w-3 h-3 inline mr-1" />
                            Broker / Virtual Talent asignado
                        </label>
                        <select v-model="form.virtual_talent_id" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option :value="null">Sin asignar</option>
                            <option v-for="vt in virtualTalents" :key="vt.virtual_talent?.id ?? vt.id" :value="vt.virtual_talent?.id ?? vt.id">
                                {{ vt.virtual_talent?.name ?? vt.name }}
                            </option>
                        </select>
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

                <!-- Checklist actual -->
                <div class="bg-surface-1 border border-border rounded-xl p-4 h-fit">
                    <div class="flex items-center gap-2 mb-3">
                        <CheckSquare class="w-4 h-4 text-amber" />
                        <p class="text-xs font-medium text-text-secondary">Checklist actual</p>
                    </div>
                    <div class="space-y-1.5">
                        <div v-for="item in campaign.asset_checklist" :key="item.id ?? item.label" class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded border border-border shrink-0" />
                            <component :is="shotIcon[item.shot_type]" class="w-3 h-3 text-text-muted shrink-0" />
                            <span class="text-[11px] text-text-secondary">{{ item.label }}</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-text-muted mt-3">
                        {{ (campaign.asset_checklist ?? []).length }} assets
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
