<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Star, Plus, Users, AtSign } from '@lucide/vue'

defineProps({ talents: Array })

const typeColor = {
    fictional: 'text-amber bg-amber/10',
    virtual_talent: 'text-violet bg-violet/10',
    mascot: 'text-info bg-info/10',
}
</script>

<template>
    <Head title="Talent Virtual — Biblioteca" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Talent Virtual</h1>
                <p class="text-sm text-text-muted mt-0.5">Identidades digitales con perfil de marca propio</p>
            </div>
            <Link
                href="/library/characters/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-surface-1 border border-border text-sm text-text-secondary rounded-lg hover:text-text-primary transition-colors"
            >
                <Plus class="w-4 h-4" />
                Nuevo personaje
            </Link>
        </div>

        <div v-if="!talents.length" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <Star class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm mb-1">No hay talent virtual aún</p>
            <p class="text-text-muted text-xs">Crea un personaje de tipo "Talent Virtual" en la Biblioteca</p>
            <Link href="/library/characters/create" class="mt-4 inline-flex text-sm text-violet hover:underline">
                Crear talent virtual
            </Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="talent in talents"
                :key="talent.id"
                :href="`/library/virtual-talents/${talent.id}`"
                class="group bg-surface-1 border border-border rounded-xl overflow-hidden hover:border-violet/40 transition-colors"
            >
                <!-- Cover / thumbnail -->
                <div class="aspect-[4/3] bg-surface-2 relative">
                    <img
                        v-if="talent.thumbnail_path"
                        :src="talent.thumbnail_path"
                        :alt="talent.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <Users class="w-10 h-10 text-text-muted" />
                    </div>
                    <!-- Public badge -->
                    <span
                        v-if="talent.virtual_talent?.is_public"
                        class="absolute top-2 left-2 text-[10px] font-mono bg-success/20 text-success border border-success/20 rounded px-1.5 py-0.5"
                    >
                        público
                    </span>
                    <!-- Active dot -->
                    <span :class="['absolute top-2 right-2 w-2 h-2 rounded-full', talent.is_active ? 'bg-success' : 'bg-surface-3']" />
                </div>

                <!-- Info -->
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <div>
                            <p class="font-semibold text-text-primary">{{ talent.name }}</p>
                            <p v-if="talent.virtual_talent?.title" class="text-xs text-violet mt-0.5">{{ talent.virtual_talent.title }}</p>
                        </div>
                        <span v-if="talent.virtual_talent?.social_handle" class="flex items-center gap-1 text-xs text-text-muted shrink-0">
                            <AtSign class="w-3 h-3" />
                            {{ talent.virtual_talent.social_handle }}
                        </span>
                    </div>

                    <p v-if="talent.virtual_talent?.bio_short" class="text-xs text-text-secondary mt-2 line-clamp-2">
                        {{ talent.virtual_talent.bio_short }}
                    </p>
                    <p v-else class="text-xs text-text-muted mt-2 italic">Sin perfil configurado</p>

                    <!-- Specialties -->
                    <div v-if="talent.virtual_talent?.specialties?.length" class="flex flex-wrap gap-1 mt-3">
                        <span
                            v-for="s in talent.virtual_talent.specialties.slice(0, 3)"
                            :key="s"
                            class="text-[10px] bg-violet/10 text-violet border border-violet/20 rounded-full px-2 py-0.5"
                        >
                            {{ s }}
                        </span>
                        <span v-if="talent.virtual_talent?.specialties?.length > 3" class="text-[10px] text-text-muted">
                            +{{ talent.virtual_talent.specialties.length - 3 }}
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>
