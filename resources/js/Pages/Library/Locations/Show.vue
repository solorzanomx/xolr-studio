<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, Trash2, MapPin, Sun, Cloud, Sunset, Moon } from '@lucide/vue'

const props = defineProps({ location: Object })

const typeColor = { interior: 'text-amber bg-amber/10', exterior: 'text-success bg-success/10', mixed: 'text-info bg-info/10' }
const typeLabel = { interior: 'Interior', exterior: 'Exterior', mixed: 'Mixto' }

const lightingIcons = { morning: Sun, day: Cloud, golden_hour: Sunset, night: Moon }
const lightingLabels = { morning: 'Mañana', day: 'Día', golden_hour: 'Hora dorada', night: 'Noche' }

function deleteLocation() {
    if (!confirm(`¿Eliminar "${props.location.name}"?`)) return
    router.delete(`/library/locations/${props.location.id}`)
}
</script>

<template>
    <Head :title="location.name" />
    <AppLayout>
        <div class="flex items-start justify-between mb-6">
            <div>
                <Link href="/library/locations" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-3 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                    Locaciones
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-text-primary">{{ location.name }}</h1>
                    <span :class="['text-xs font-mono px-2 py-0.5 rounded', typeColor[location.type]]">{{ typeLabel[location.type] }}</span>
                    <span :class="['w-2 h-2 rounded-full', location.is_active ? 'bg-success' : 'bg-surface-3']" />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="`/library/locations/${location.id}/edit`" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                    <Pencil class="w-4 h-4" />
                    Editar
                </Link>
                <button @click="deleteLocation" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-danger hover:bg-danger/10 transition-colors">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Thumbnail -->
            <div>
                <div class="aspect-video bg-surface-1 border border-border rounded-xl overflow-hidden mb-4">
                    <img v-if="location.thumbnail_path" :src="location.thumbnail_path" :alt="location.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <MapPin class="w-12 h-12 text-text-muted" />
                    </div>
                </div>
                <!-- Lighting by time -->
                <div v-if="location.lighting_by_time" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-3">Iluminación</p>
                    <div class="space-y-3">
                        <div v-for="(label, key) in lightingLabels" :key="key">
                            <div v-if="location.lighting_by_time[key]" class="flex items-start gap-2">
                                <component :is="lightingIcons[key]" class="w-3.5 h-3.5 text-amber mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-xs text-text-muted">{{ label }}</p>
                                    <p class="text-xs font-mono text-text-secondary mt-0.5">{{ location.lighting_by_time[key] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="lg:col-span-2 space-y-4">
                <div v-if="location.description" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Descripción</p>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ location.description }}</p>
                </div>
                <div v-if="location.base_prompt" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Prompt base</p>
                    <pre class="text-xs font-mono text-text-secondary whitespace-pre-wrap leading-relaxed">{{ location.base_prompt }}</pre>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
