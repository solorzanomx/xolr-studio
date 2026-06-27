<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Image, Video, Mic, CheckSquare, User } from '@lucide/vue'

const props = defineProps({ projects: Array, virtualTalents: Array })

const form = useForm({
    name:               '',
    project_id:         '',
    property_id:        null,
    virtual_talent_id:  null,
    type:               'real_estate',
    template:           'launch',
    description:        '',
    status:             'planning',
    deadline:           '',
})

// Sincronizar type con template
watch(() => form.template, (t) => {
    form.type = ['launch','available','tips','market_analysis','closing'].includes(t)
        ? 'real_estate'
        : t === 'youtube' ? 'youtube' : 'social'
})

const templateLabel = {
    launch:          'Lanzamiento de desarrollo',
    available:       'Propiedad disponible',
    tips:            'Tips informativos (Sofía)',
    market_analysis: 'Análisis de mercado (Diego)',
    closing:         'Cierre exitoso',
    youtube:         'YouTube',
    social:          'Social Media',
}

const selectedProject = computed(() =>
    props.projects.find(p => String(p.id) === String(form.project_id))
)

const checklistPreviews = {
    launch: [
        { group: 'Infomerciales', items: ['Video presentación broker 60s', 'Video bienvenida al desarrollo'] },
        { group: 'Carrusel Instagram', items: ['Portada', 'Fachada exterior', 'Sala', 'Cocina', 'Recámara', 'Amenidades'] },
        { group: 'Posts Facebook & Instagram', items: ['Post hero — Fachada', 'Post interior'] },
        { group: 'Stories', items: ['Story broker 9:16', 'Story precio/CTA', 'Story video broker'] },
        { group: 'Reel', items: ['Thumbnail', 'Video para reel'] },
    ],
    available: [
        { group: 'Infomerciales', items: ['Video presentación 30s con broker'] },
        { group: 'Carrusel Instagram', items: ['Fachada', 'Interior sala', 'Interior recámara', 'Precio y CTA'] },
        { group: 'Posts Facebook & Instagram', items: ['Post hero', 'Post interior 2', 'Post interior 3'] },
        { group: 'Stories', items: ['Story precio', 'Story broker 9:16'] },
    ],
    tips: [
        { group: 'Infomerciales', items: ['Reel tip 30s con Sofía'] },
        { group: 'Posts Facebook & Instagram', items: ['Imagen del tip', 'Portada visual'] },
        { group: 'Stories', items: ['Story del tip vertical'] },
    ],
    market_analysis: [
        { group: 'Infomerciales', items: ['Video análisis de mercado 90s'] },
        { group: 'Carrusel Instagram', items: ['Portada', 'Dato 1: precio m²', 'Dato 2: plusvalía', 'Dato 3: tendencia', 'Dato 4: comparativa', 'CTA inversión'] },
        { group: 'Posts Facebook & Instagram', items: ['Post datos clave', 'Post de Diego con datos'] },
    ],
    closing: [
        { group: 'Posts Facebook & Instagram', items: ['Post celebración del cierre', 'Post broker con cliente'] },
        { group: 'Stories', items: ['Story testimonio', 'Story celebración animada'] },
    ],
}

const currentPreview = computed(() => checklistPreviews[form.template] ?? [
    { group: 'Posts Facebook & Instagram', items: ['Hero visual', 'Post Instagram'] },
    { group: 'Stories', items: ['Story vertical 9:16'] },
])

const totalPreviewItems = computed(() =>
    currentPreview.value.reduce((a, g) => a + g.items.length, 0)
)

const shotIcon = { image: Image, video: Video, talking: Mic }

const groupIcon = (group) => {
    if (group.includes('Infomercial')) return Mic
    if (group.includes('Carrusel'))    return Image
    if (group.includes('Facebook'))    return Image
    if (group.includes('Stories'))     return Image
    if (group.includes('Reel'))        return Video
    return Image
}

function submit() { form.post('/campaigns') }
</script>

<template>
    <Head title="Nueva campaña" />
    <AppLayout>
        <div class="max-w-4xl">
            <Link href="/campaigns" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" /> Campañas
            </Link>
            <h1 class="text-xl font-semibold text-text-primary mb-6">Nueva campaña</h1>

            <div class="grid grid-cols-3 gap-6">
                <!-- Form -->
                <form @submit.prevent="submit" class="col-span-2 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                        <input v-model="form.name" type="text" placeholder="Ej: Torre Ándalus — Lanzamiento" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.name }" />
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

                    <!-- Propiedad (si hay proyecto con propiedades) -->
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
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Deadline</label>
                            <input v-model="form.deadline" type="date" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción / Brief</label>
                        <textarea v-model="form.description" rows="3" placeholder="Brief de la campaña, propiedad, objetivo..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Creando...' : 'Crear campaña' }}
                        </button>
                        <Link href="/campaigns" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                    </div>
                </form>

                <!-- Checklist preview agrupado -->
                <div class="bg-surface-1 border border-border rounded-xl p-4 h-fit">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-text-secondary">{{ templateLabel[form.template] }}</p>
                        <span class="text-[10px] font-mono text-amber">{{ totalPreviewItems }} assets</span>
                    </div>
                    <div class="space-y-3">
                        <div v-for="group in currentPreview" :key="group.group">
                            <p class="text-[10px] font-semibold text-text-muted uppercase tracking-wider mb-1">{{ group.group }}</p>
                            <div class="space-y-1">
                                <div v-for="item in group.items" :key="item" class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 rounded border border-border shrink-0" />
                                    <span class="text-[10px] text-text-secondary">{{ item }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
