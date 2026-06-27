<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, AtSign, Star, MessageSquare, Shirt, Mic, Sparkles } from '@lucide/vue'
import { ref } from 'vue'

const props = defineProps({
    character: Object,
    talent: Object,
})

const activeTab = ref('perfil')

const outfitContextLabel = {
    formal: 'Formal', casual: 'Casual', action: 'Acción',
    historical: 'Histórico', fantasy: 'Fantasía', uniform: 'Uniforme', other: 'Otro',
}

const outfitsByContext = computed(() => {
    if (!props.character.outfits?.length) return {}
    return props.character.outfits.reduce((acc, outfit) => {
        if (!acc[outfit.context]) acc[outfit.context] = []
        acc[outfit.context].push(outfit)
        return acc
    }, {})
})
</script>

<script>
import { computed } from 'vue'
export default {}
</script>

<template>
    <Head :title="character.name" />
    <AppLayout>
        <!-- Back + actions -->
        <div class="flex items-start justify-between mb-6">
            <Link href="/library/virtual-talents" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary transition-colors">
                <ChevronLeft class="w-4 h-4" />
                Talent Virtual
            </Link>
            <Link
                :href="`/library/virtual-talents/${character.id}/edit`"
                class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors"
            >
                <Pencil class="w-4 h-4" />
                Editar perfil
            </Link>
        </div>

        <!-- Hero -->
        <div class="bg-surface-1 border border-border rounded-2xl overflow-hidden mb-6">
            <div class="aspect-[4/1] bg-gradient-to-r from-violet/20 via-surface-2 to-amber/10 relative">
                <div class="absolute bottom-0 left-8 translate-y-1/2">
                    <div class="w-24 h-24 rounded-2xl border-4 border-surface-1 overflow-hidden bg-surface-2">
                        <img v-if="character.thumbnail_path" :src="character.thumbnail_path" :alt="character.name" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <Star class="w-8 h-8 text-violet" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="pt-16 pb-6 px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-text-primary">{{ character.name }}</h1>
                        <p v-if="talent?.title" class="text-violet text-sm font-medium mt-0.5">{{ talent.title }}</p>
                        <div v-if="talent?.social_handle" class="flex items-center gap-1 mt-1 text-text-muted text-sm">
                            <AtSign class="w-3.5 h-3.5" />
                            {{ talent.social_handle }}
                        </div>
                    </div>
                    <!-- Brand colors -->
                    <div v-if="talent?.brand_colors?.length" class="flex gap-1.5">
                        <div
                            v-for="color in talent.brand_colors"
                            :key="color"
                            class="w-6 h-6 rounded-full border border-border"
                            :style="{ backgroundColor: color }"
                            :title="color"
                        />
                    </div>
                </div>

                <!-- Signature phrase -->
                <blockquote v-if="talent?.signature_phrase" class="mt-4 text-lg italic text-text-secondary border-l-2 border-violet pl-4">
                    "{{ talent.signature_phrase }}"
                </blockquote>

                <!-- Specialties -->
                <div v-if="talent?.specialties?.length" class="flex flex-wrap gap-2 mt-4">
                    <span
                        v-for="s in talent.specialties"
                        :key="s"
                        class="text-xs bg-violet/10 text-violet border border-violet/20 rounded-full px-3 py-1 font-medium"
                    >
                        {{ s }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-border mb-6">
            <button
                v-for="tab in [
                    { id: 'perfil', label: 'Perfil', icon: Star },
                    { id: 'looks', label: 'Looks', icon: Shirt, count: character.outfits?.length },
                    { id: 'voz', label: 'Voz & Estilo', icon: MessageSquare },
                ]"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === tab.id
                        ? 'border-violet text-text-primary'
                        : 'border-transparent text-text-muted hover:text-text-secondary'
                ]"
            >
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.label }}
                <span v-if="tab.count" class="text-[10px] font-mono bg-surface-2 text-text-muted rounded-full px-1.5 py-0.5">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Perfil tab -->
        <div v-if="activeTab === 'perfil'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div v-if="talent?.bio_short" class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2 flex items-center gap-2">
                        <AtSign class="w-3 h-3" /> Bio corta (redes)
                    </p>
                    <p class="text-text-primary leading-relaxed">{{ talent.bio_short }}</p>
                </div>
                <div v-if="talent?.bio_full" class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-3">Bio completa</p>
                    <div class="text-sm text-text-secondary leading-relaxed whitespace-pre-wrap">{{ talent.bio_full }}</div>
                </div>
                <div v-if="!talent?.bio_short && !talent?.bio_full" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                    <Sparkles class="w-8 h-8 text-violet mx-auto mb-2" />
                    <p class="text-sm text-text-muted mb-3">Sin bio configurada</p>
                    <Link :href="`/library/virtual-talents/${character.id}/edit`" class="text-sm text-violet hover:underline">
                        Editar perfil y generar bio con IA
                    </Link>
                </div>
            </div>
            <div class="space-y-4">
                <!-- Character stats -->
                <div class="bg-surface-1 border border-border rounded-xl p-4 space-y-3">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider">Stats</p>
                    <div v-if="character.approval_rate" class="flex justify-between text-sm">
                        <span class="text-text-muted">Aprobación</span>
                        <span class="font-mono text-success">{{ character.approval_rate }}%</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-muted">Outfits</span>
                        <span class="font-mono text-text-secondary">{{ character.outfits?.length ?? 0 }}</span>
                    </div>
                    <div v-if="character.lora_trigger_word" class="flex justify-between text-sm">
                        <span class="text-text-muted">LoRA trigger</span>
                        <span class="font-mono text-violet text-xs">{{ character.lora_trigger_word }}</span>
                    </div>
                </div>
                <!-- Personality traits -->
                <div v-if="character.personality_traits?.length" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Personalidad</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="trait in character.personality_traits" :key="trait"
                            class="text-xs bg-surface-2 text-text-secondary border border-border rounded-full px-2.5 py-1">
                            {{ trait }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Looks tab -->
        <div v-if="activeTab === 'looks'">
            <div v-if="!character.outfits?.length" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                <Shirt class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted">Sin looks configurados</p>
                <Link :href="`/library/characters/${character.id}`" class="mt-2 inline-flex text-sm text-amber hover:underline">
                    Ir al personaje → añadir outfits
                </Link>
            </div>
            <div v-else class="space-y-6">
                <div v-for="(outfits, context) in outfitsByContext" :key="context">
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">
                        {{ outfitContextLabel[context] ?? context }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="outfit in outfits" :key="outfit.id"
                            class="bg-surface-1 border border-border rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-text-primary">{{ outfit.name }}</p>
                                <span :class="['w-1.5 h-1.5 rounded-full', outfit.is_active ? 'bg-success' : 'bg-surface-3']" />
                            </div>
                            <p v-if="outfit.prompt_fragment" class="text-xs font-mono text-text-muted line-clamp-2">
                                {{ outfit.prompt_fragment }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voz & Estilo tab -->
        <div v-if="activeTab === 'voz'">
            <div v-if="talent?.communication_style" class="bg-surface-1 border border-border rounded-xl p-5 mb-4">
                <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-3 flex items-center gap-2">
                    <MessageSquare class="w-3 h-3" /> Guía de comunicación
                </p>
                <div class="text-sm text-text-secondary leading-relaxed whitespace-pre-wrap">{{ talent.communication_style }}</div>
            </div>
            <div v-else class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                <MessageSquare class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted">Sin guía de comunicación</p>
            </div>
        </div>
    </AppLayout>
</template>
