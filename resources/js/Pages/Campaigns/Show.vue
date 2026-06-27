<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, Trash2, Zap, CheckSquare, Square, Image, Video, Mic, Home, User } from '@lucide/vue'

const props = defineProps({
    campaign:         Object,
    completedItems:   Number,
    totalItems:       Number,
    checklistByGroup: Object,
})

const progress = computed(() =>
    props.totalItems > 0 ? Math.round(props.completedItems / props.totalItems * 100) : 0
)

// Property form inline
const showPropertyForm = ref(false)
const propertyForm = useForm({
    name: '', type: 'apartment', location_description: '',
    price: '', currency: 'MXN', bedrooms: '', bathrooms: '',
    area_sqm: '', description: '', status: 'available',
})
function storeProperty() {
    propertyForm.post(`/projects/${props.campaign.project_id}/properties`, {
        onSuccess: () => { showPropertyForm.value = false; propertyForm.reset() },
    })
}

function generateShots() {
    router.post(`/campaigns/${props.campaign.id}/generate-shots`)
}

function deleteCampaign() {
    if (!confirm(`¿Eliminar "${props.campaign.name}"?`)) return
    router.delete(`/campaigns/${props.campaign.id}`)
}

const hasPendingItems = computed(() =>
    (props.campaign.asset_checklist ?? []).some(i => i.shot_id === null)
)

const groupIcon = (group) => {
    if (group.includes('Infomercial')) return Mic
    if (group.includes('Carrusel'))    return Image
    if (group.includes('Facebook'))    return Image
    if (group.includes('Stories'))     return Video
    if (group.includes('Reel'))        return Video
    return Image
}

const itemTypeIcon = { image: Image, video: Video, talking: Mic }

const itemStatusColor = {
    pending:     'text-text-muted',
    in_progress: 'text-amber',
    completed:   'text-success',
}

const statusColor = {
    planning:   'text-text-muted bg-surface-2',
    production: 'text-info bg-info/10',
    review:     'text-warning bg-warning/10',
    completed:  'text-success bg-success/10',
    archived:   'text-text-muted bg-surface-2',
}
const statusLabel = {
    planning: 'Planeación', production: 'Producción', review: 'Revisión',
    completed: 'Completada', archived: 'Archivada',
}

const templateLabel = {
    launch: 'Lanzamiento', available: 'Propiedad disponible',
    tips: 'Tips informativos', market_analysis: 'Análisis de mercado',
    closing: 'Cierre exitoso', youtube: 'YouTube', social: 'Social',
}
</script>

<template>
    <Head :title="campaign.name" />
    <AppLayout>
        <div class="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link href="/campaigns" class="hover:text-text-primary transition-colors">Campañas</Link>
            <span>/</span>
            <span class="text-text-primary">{{ campaign.name }}</span>
        </div>

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <h1 class="text-2xl font-bold text-text-primary">{{ campaign.name }}</h1>
                    <span :class="['text-xs font-mono px-2 py-0.5 rounded', statusColor[campaign.status]]">{{ statusLabel[campaign.status] }}</span>
                    <span v-if="campaign.template" class="text-xs font-mono text-text-muted bg-surface-2 border border-border rounded px-2 py-0.5">{{ templateLabel[campaign.template] }}</span>
                </div>
                <div class="flex items-center gap-3 flex-wrap text-sm text-text-muted">
                    <span>{{ campaign.project?.name }}</span>
                    <span v-if="campaign.virtualTalent"> · <User class="w-3 h-3 inline" /> {{ campaign.virtualTalent?.name ?? campaign.virtualTalent?.character?.name }}</span>
                    <span v-if="campaign.property"> · {{ campaign.property.name }}</span>
                    <span v-if="campaign.deadline"> · Deadline: {{ new Date(campaign.deadline).toLocaleDateString('es-MX', { month: 'long', day: 'numeric' }) }}</span>
                </div>

                <!-- Progress -->
                <div class="mt-3 max-w-md">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-text-muted">{{ completedItems }}/{{ totalItems }} assets completados</span>
                        <span class="text-xs font-mono text-amber">{{ progress }}%</span>
                    </div>
                    <div class="h-2 bg-surface-2 rounded-full overflow-hidden">
                        <div class="h-full bg-amber rounded-full transition-all duration-500" :style="{ width: progress + '%' }" />
                    </div>
                </div>
            </div>
            <div class="flex gap-2 shrink-0 ml-4">
                <Link :href="`/campaigns/${campaign.id}/edit`" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                    <Pencil class="w-4 h-4" /> Editar
                </Link>
                <button @click="deleteCampaign" class="px-3 py-2 bg-surface-1 border border-border rounded-lg text-danger hover:bg-danger/10 transition-colors">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main: checklist por grupos -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">Asset Checklist</h2>
                    <button
                        v-if="hasPendingItems"
                        @click="generateShots"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-violet text-white text-xs font-semibold rounded-lg hover:bg-violet/90 transition-colors"
                    >
                        <Zap class="w-3.5 h-3.5" /> Generar shots pendientes
                    </button>
                </div>

                <!-- Grupos del checklist -->
                <div v-for="(items, group) in checklistByGroup" :key="group" class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                    <!-- Group header -->
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-surface-2 border-b border-border">
                        <component :is="groupIcon(group)" class="w-3.5 h-3.5 text-amber" />
                        <p class="text-xs font-semibold text-text-secondary">{{ group }}</p>
                        <span class="text-[10px] font-mono text-text-muted ml-auto">
                            {{ items.filter(i => i.status === 'completed').length }}/{{ items.length }}
                        </span>
                    </div>
                    <!-- Group items -->
                    <div>
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center gap-3 px-4 py-2.5 border-b border-border/50 last:border-0"
                        >
                            <component
                                :is="item.status === 'completed' ? CheckSquare : Square"
                                :class="['w-4 h-4 shrink-0', itemStatusColor[item.status]]"
                            />
                            <component :is="itemTypeIcon[item.shot_type]" class="w-3.5 h-3.5 text-text-muted shrink-0" />
                            <span class="text-sm text-text-primary flex-1">{{ item.label }}</span>
                            <div class="shrink-0 flex items-center gap-2">
                                <span v-if="item.shot_id" class="text-[10px] font-mono text-success">✓ Shot</span>
                                <span v-else class="text-[10px] font-mono text-text-muted">Pendiente</span>
                                <span :class="['text-[10px] font-mono', item.shot_type === 'talking' ? 'text-violet' : 'text-text-muted']">
                                    {{ item.shot_type === 'talking' ? 'Talking' : item.shot_type === 'video' ? 'Video' : 'Imagen' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shots generados -->
                <div v-if="campaign.shots?.length" class="mt-2">
                    <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide mb-3">
                        Shots generados ({{ campaign.shots.length }})
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div v-for="shot in campaign.shots" :key="shot.id" class="bg-surface-1 border border-border rounded-xl p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-mono text-amber font-bold">S{{ String(shot.number).padStart(2, '0') }}</span>
                                <component :is="{ image: Image, video: Video, talking: Mic }[shot.shot_type]" class="w-3.5 h-3.5 text-text-muted" />
                            </div>
                            <p class="text-xs text-text-secondary line-clamp-2">{{ shot.description }}</p>
                            <span :class="['text-[10px] font-mono mt-1 block', shot.shot_type === 'talking' ? 'text-violet' : 'text-text-muted']">
                                {{ shot.shot_type }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Virtual Talent -->
                <div v-if="campaign.virtualTalent" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">
                        <User class="w-3 h-3 inline mr-1" /> Broker asignado
                    </p>
                    <p class="text-sm font-medium text-text-primary">
                        {{ campaign.virtualTalent.name ?? campaign.virtualTalent.character?.name }}
                    </p>
                </div>

                <!-- Propiedad -->
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <Home class="w-4 h-4 text-amber" />
                            <p class="text-xs font-medium text-text-secondary uppercase tracking-wider">Propiedad</p>
                        </div>
                        <button v-if="!campaign.property" @click="showPropertyForm = !showPropertyForm" class="text-xs text-amber hover:underline">+ Agregar</button>
                    </div>

                    <div v-if="showPropertyForm" class="space-y-2">
                        <input v-model="propertyForm.name" type="text" placeholder="Nombre *" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        <div class="grid grid-cols-2 gap-2">
                            <select v-model="propertyForm.type" class="bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="apartment">Departamento</option>
                                <option value="house">Casa</option>
                                <option value="penthouse">Penthouse</option>
                                <option value="commercial">Comercial</option>
                                <option value="land">Terreno</option>
                                <option value="development">Desarrollo</option>
                            </select>
                            <input v-model="propertyForm.price" type="number" placeholder="Precio" class="bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                        <input v-model="propertyForm.location_description" type="text" placeholder="Ubicación" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                        <div class="grid grid-cols-3 gap-2">
                            <input v-model="propertyForm.bedrooms" type="number" placeholder="Rec." class="bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                            <input v-model="propertyForm.bathrooms" type="number" step="0.5" placeholder="Baños" class="bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                            <input v-model="propertyForm.area_sqm" type="number" placeholder="m²" class="bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button @click="storeProperty" :disabled="propertyForm.processing" class="px-3 py-1 bg-amber text-surface-0 text-xs font-semibold rounded hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                            <button @click="showPropertyForm = false" class="text-xs text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                        </div>
                    </div>

                    <div v-else-if="campaign.property" class="space-y-2">
                        <p class="font-medium text-text-primary text-sm">{{ campaign.property.name }}</p>
                        <p v-if="campaign.property.location_description" class="text-xs text-text-muted">{{ campaign.property.location_description }}</p>
                        <p v-if="campaign.property.price" class="text-lg font-mono font-bold text-amber">
                            ${{ Number(campaign.property.price).toLocaleString() }} {{ campaign.property.currency }}
                        </p>
                        <div v-if="campaign.property.bedrooms || campaign.property.area_sqm" class="flex gap-3 text-xs text-text-muted font-mono">
                            <span v-if="campaign.property.bedrooms">{{ campaign.property.bedrooms }} rec.</span>
                            <span v-if="campaign.property.bathrooms">{{ campaign.property.bathrooms }} baños</span>
                            <span v-if="campaign.property.area_sqm">{{ campaign.property.area_sqm }} m²</span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-text-muted">Sin propiedad asignada</div>
                </div>

                <!-- Descripción -->
                <div v-if="campaign.description" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Brief</p>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ campaign.description }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
