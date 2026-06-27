<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Palette, Camera } from '@lucide/vue'

defineProps({ visualStyles: Array, cameraStyles: Array })
</script>

<template>
    <Head title="Estilos — Biblioteca" />
    <AppLayout>
        <div class="mb-8">
            <h1 class="text-xl font-semibold text-text-primary">Estilos</h1>
            <p class="text-sm text-text-muted mt-0.5">Estilos visuales y de cámara disponibles en el Prompt Engine</p>
        </div>

        <!-- Visual Styles -->
        <section class="mb-10">
            <div class="flex items-center gap-2 mb-4">
                <Palette class="w-4 h-4 text-amber" />
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">Estilos visuales</h2>
                <span class="text-xs font-mono text-text-muted bg-surface-2 rounded px-1.5 py-0.5">{{ visualStyles.length }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                <div v-for="style in visualStyles" :key="style.id" class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                    <div class="aspect-video bg-surface-2 flex items-center justify-center">
                        <img v-if="style.thumbnail_path" :src="style.thumbnail_path" :alt="style.name" class="w-full h-full object-cover" />
                        <Palette v-else class="w-6 h-6 text-text-muted" />
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-text-primary">{{ style.name }}</p>
                        <p v-if="style.description" class="text-xs text-text-muted mt-1 line-clamp-2">{{ style.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Camera Styles -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <Camera class="w-4 h-4 text-violet" />
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">Estilos de cámara</h2>
                <span class="text-xs font-mono text-text-muted bg-surface-2 rounded px-1.5 py-0.5">{{ cameraStyles.length }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-for="style in cameraStyles" :key="style.id" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-sm font-medium text-text-primary mb-1">{{ style.name }}</p>
                    <p v-if="style.description" class="text-xs text-text-muted mb-2">{{ style.description }}</p>
                    <pre v-if="style.prompt_fragment" class="text-xs font-mono text-violet/70 bg-violet/5 rounded px-2 py-1.5 whitespace-pre-wrap">{{ style.prompt_fragment }}</pre>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
