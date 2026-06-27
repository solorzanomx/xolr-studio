<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import PreviewLayout from '@/Layouts/PreviewLayout.vue'
import { Eye, Film, Image, Video, Users, Clock } from '@lucide/vue'

const props = defineProps({
    share:       Object,
    type:        String,
    title:       String,
    subtitle:    String,
    synopsis:    String,
    logline:     String,
    scenes:      Array,
    seasons:     Array,
    sceneCount:  Number,
    shotCount:   Number,
    renderCount: Number,
})

// --- Animatic player (episode mode) ---
const allShots = computed(() =>
    props.scenes?.flatMap(s => s.shots.filter(sh => sh.render)) ?? []
)

const playerActive   = ref(false)
const playerIndex    = ref(0)
const playerInterval = ref(null)
const SLIDE_DURATION = 3000

function startPlayer() {
    playerActive.value = true
    playerIndex.value  = 0
    clearInterval(playerInterval.value)
    playerInterval.value = setInterval(() => {
        if (playerIndex.value < allShots.value.length - 1) {
            playerIndex.value++
        } else {
            clearInterval(playerInterval.value)
            playerActive.value = false
        }
    }, SLIDE_DURATION)
}

function stopPlayer() {
    clearInterval(playerInterval.value)
    playerActive.value = false
}

const currentSlide = computed(() => allShots.value[playerIndex.value])

// --- Lightbox ---
const lightbox = ref(null)

const MOOD_COLOR = {
    tense:      'text-red-400',
    action:     'text-orange-400',
    dramatic:   'text-purple-400',
    calm:       'text-emerald-400',
    mysterious: 'text-indigo-400',
    romantic:   'text-pink-400',
    comedic:    'text-yellow-400',
    horror:     'text-red-600',
    other:      'text-text-muted',
}
</script>

<template>
    <PreviewLayout :title="title" :subtitle="subtitle">
        <Head :title="title + ' — Preview'" />

        <!-- EPISODE MODE -->
        <template v-if="type === 'episode'">

            <!-- Hero -->
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-mono px-2 py-0.5 bg-amber/10 text-amber rounded-lg">Episodio</span>
                    <span class="text-xs text-text-muted">{{ subtitle }}</span>
                </div>
                <h1 class="text-4xl font-bold text-text-primary mb-3">{{ title }}</h1>
                <p v-if="logline" class="text-lg text-text-secondary italic mb-4">{{ logline }}</p>
                <p v-if="synopsis" class="text-sm text-text-muted leading-relaxed max-w-2xl">{{ synopsis }}</p>

                <!-- Stats -->
                <div class="flex items-center gap-5 mt-5">
                    <div class="flex items-center gap-1.5 text-sm text-text-muted">
                        <Film class="w-4 h-4" />
                        <span>{{ sceneCount }} escenas</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-sm text-text-muted">
                        <Image class="w-4 h-4" />
                        <span>{{ shotCount }} shots</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-sm text-text-muted">
                        <Eye class="w-4 h-4" />
                        <span>{{ renderCount }} renders aprobados</span>
                    </div>
                </div>
            </div>

            <!-- Animatic player -->
            <div v-if="allShots.length" class="mb-10">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold text-text-primary">Animático</h2>
                    <button v-if="!playerActive" @click="startPlayer"
                        class="px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors flex items-center gap-2">
                        <Video class="w-4 h-4" /> Reproducir
                    </button>
                    <button v-else @click="stopPlayer"
                        class="px-4 py-2 bg-surface-2 border border-border text-text-secondary text-sm rounded-lg hover:text-text-primary transition-colors">
                        Detener
                    </button>
                </div>

                <!-- Player screen -->
                <div v-if="playerActive && currentSlide" class="relative bg-black rounded-xl overflow-hidden aspect-video mb-3">
                    <img v-if="currentSlide.render.file_type === 'image'"
                        :src="currentSlide.render.url"
                        :alt="currentSlide.label"
                        class="w-full h-full object-contain" />
                    <video v-else :src="currentSlide.render.url" autoplay muted loop class="w-full h-full object-contain" />

                    <!-- Overlay info -->
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                        <p class="text-sm font-semibold text-white">{{ currentSlide.label }}</p>
                        <p v-if="currentSlide.characters?.length" class="text-xs text-white/60">
                            {{ currentSlide.characters.join(', ') }}
                        </p>
                    </div>

                    <!-- Progress -->
                    <div class="absolute bottom-0 inset-x-0 h-1 bg-white/20">
                        <div class="h-full bg-amber transition-all duration-300"
                            :style="{ width: ((playerIndex + 1) / allShots.length * 100) + '%' }" />
                    </div>

                    <!-- Counter -->
                    <div class="absolute top-3 right-3 text-xs font-mono text-white/60 bg-black/40 px-2 py-0.5 rounded">
                        {{ playerIndex + 1 }} / {{ allShots.length }}
                    </div>
                </div>

                <!-- Thumbnail strip -->
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <button
                        v-for="(shot, i) in allShots" :key="shot.id"
                        @click="playerIndex = i; playerActive = true"
                        class="shrink-0 w-20 h-14 rounded-lg overflow-hidden border-2 transition-all"
                        :class="playerIndex === i && playerActive ? 'border-amber' : 'border-transparent opacity-60 hover:opacity-100'"
                    >
                        <img v-if="shot.render.file_type === 'image'" :src="shot.render.url"
                            class="w-full h-full object-cover" :alt="shot.label" />
                        <div v-else class="w-full h-full bg-surface-2 flex items-center justify-center">
                            <Video class="w-4 h-4 text-text-muted" />
                        </div>
                    </button>
                </div>
            </div>

            <!-- Scenes breakdown -->
            <div>
                <h2 class="text-base font-semibold text-text-primary mb-4">Desglose de escenas</h2>
                <div class="space-y-4">
                    <div v-for="scene in scenes" :key="scene.id"
                        class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                        <!-- Scene header -->
                        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-text-primary">{{ scene.title }}</p>
                                <p v-if="scene.description" class="text-xs text-text-muted mt-0.5 max-w-lg">{{ scene.description }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span v-if="scene.time_of_day && scene.time_of_day !== 'unspecified'"
                                    class="text-[10px] px-2 py-0.5 bg-surface-2 text-text-muted rounded-lg font-mono">
                                    {{ scene.time_of_day }}
                                </span>
                                <span v-if="scene.mood && scene.mood !== 'other'"
                                    class="text-[10px] font-semibold" :class="MOOD_COLOR[scene.mood]">
                                    {{ scene.mood }}
                                </span>
                            </div>
                        </div>

                        <!-- Shot grid -->
                        <div v-if="scene.shots.length" class="p-3 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                            <div v-for="shot in scene.shots" :key="shot.id"
                                class="group relative rounded-lg overflow-hidden bg-surface-2 aspect-square cursor-pointer"
                                @click="shot.render && (lightbox = shot)">
                                <img v-if="shot.render?.file_type === 'image'" :src="shot.render.url"
                                    :alt="shot.label" class="w-full h-full object-cover" loading="lazy" />
                                <video v-else-if="shot.render?.file_type === 'video'" :src="shot.render.url"
                                    muted class="w-full h-full object-cover"
                                    @mouseenter="$event.target.play()"
                                    @mouseleave="$event.target.pause()" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Image class="w-5 h-5 text-text-muted" />
                                </div>
                                <!-- Hover label -->
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-1.5
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                    <p class="text-[9px] text-white truncate">{{ shot.label }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- PROJECT MODE -->
        <template v-else-if="type === 'project'">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-mono px-2 py-0.5 bg-violet/10 text-violet rounded-lg">Proyecto</span>
                    <span class="text-xs text-text-muted">{{ subtitle }}</span>
                </div>
                <h1 class="text-4xl font-bold text-text-primary mb-3">{{ title }}</h1>
                <p v-if="synopsis" class="text-sm text-text-muted leading-relaxed max-w-2xl">{{ synopsis }}</p>
            </div>

            <div class="space-y-6">
                <div v-for="season in seasons" :key="season.id"
                    class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-border">
                        <p class="text-sm font-semibold text-text-primary">{{ season.title }}</p>
                        <p class="text-xs text-text-muted">{{ season.episodes?.length ?? 0 }} episodios</p>
                    </div>
                    <div class="divide-y divide-border">
                        <div v-for="ep in season.episodes" :key="ep.id"
                            class="flex items-center justify-between px-5 py-3">
                            <div>
                                <p class="text-sm text-text-primary font-medium">{{ ep.title }}</p>
                                <p v-if="ep.synopsis" class="text-xs text-text-muted mt-0.5 max-w-lg truncate">{{ ep.synopsis }}</p>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 bg-surface-2 text-text-muted rounded-lg font-mono shrink-0">
                                {{ ep.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Lightbox -->
        <div v-if="lightbox" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
            @click.self="lightbox = null">
            <div class="relative max-w-3xl w-full mx-4">
                <button @click="lightbox = null"
                    class="absolute -top-10 right-0 text-white/60 hover:text-white text-sm">✕ Cerrar</button>
                <img v-if="lightbox.render?.file_type === 'image'"
                    :src="lightbox.render.url" :alt="lightbox.label"
                    class="max-h-[80vh] mx-auto rounded-xl object-contain" />
                <video v-else :src="lightbox.render?.url" controls autoplay
                    class="max-h-[80vh] mx-auto rounded-xl w-full" />
                <div class="mt-3 text-center">
                    <p class="text-sm font-semibold text-white">{{ lightbox.label }}</p>
                    <p v-if="lightbox.characters?.length" class="text-xs text-white/50">{{ lightbox.characters.join(', ') }}</p>
                </div>
            </div>
        </div>
    </PreviewLayout>
</template>
