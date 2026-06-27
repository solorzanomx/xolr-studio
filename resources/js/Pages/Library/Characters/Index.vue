<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Users, Plus, Search, Cpu, Mic } from '@lucide/vue'

const props = defineProps({
    characters: Object,
    filters: Object,
})

const search = ref(props.filters.search ?? '')
const type = ref(props.filters.type ?? '')
const active = ref(!!props.filters.active)
const hasLora = ref(!!props.filters.has_lora)

let debounceTimer = null
watch([search, type, active, hasLora], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => applyFilters(), 300)
})

function applyFilters() {
    router.get('/library/characters', {
        search: search.value || undefined,
        type: type.value || undefined,
        active: active.value || undefined,
        has_lora: hasLora.value || undefined,
    }, { preserveState: true, replace: true })
}

const typeLabel = {
    fictional: 'Ficción',
    virtual_talent: 'Talent Virtual',
    mascot: 'Mascota',
}

const typeColor = {
    fictional: 'text-amber bg-amber/10',
    virtual_talent: 'text-violet bg-violet/10',
    mascot: 'text-info bg-info/10',
}
</script>

<template>
    <Head title="Personajes — Biblioteca" />
    <AppLayout>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Personajes</h1>
                <p class="text-sm text-text-muted mt-0.5">Biblioteca global de personajes y talento virtual</p>
            </div>
            <Link
                href="/library/characters/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors"
            >
                <Plus class="w-4 h-4" />
                Nuevo personaje
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar personaje..."
                    class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                />
            </div>
            <select
                v-model="type"
                class="bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
            >
                <option value="">Todos los tipos</option>
                <option value="fictional">Ficción</option>
                <option value="virtual_talent">Talent Virtual</option>
                <option value="mascot">Mascota</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer select-none">
                <input v-model="active" type="checkbox" class="accent-amber" />
                Solo activos
            </label>
            <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer select-none">
                <input v-model="hasLora" type="checkbox" class="accent-amber" />
                Con LoRA
            </label>
        </div>

        <!-- Grid -->
        <div v-if="characters.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <Users class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm">No hay personajes aún</p>
            <Link href="/library/characters/create" class="mt-3 inline-flex text-sm text-amber hover:underline">
                Crear el primero
            </Link>
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <Link
                v-for="char in characters.data"
                :key="char.id"
                :href="`/library/characters/${char.id}`"
                class="group bg-surface-1 border border-border rounded-xl overflow-hidden hover:border-amber/40 transition-colors"
            >
                <!-- Thumbnail -->
                <div class="aspect-square bg-surface-2 relative">
                    <img
                        v-if="char.thumbnail_path"
                        :src="char.thumbnail_path"
                        :alt="char.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <Users class="w-8 h-8 text-text-muted" />
                    </div>
                    <!-- Active indicator -->
                    <span
                        :class="['absolute top-2 right-2 w-2 h-2 rounded-full', char.is_active ? 'bg-success' : 'bg-surface-3']"
                    />
                    <!-- LoRA badge -->
                    <span
                        v-if="char.lora_path"
                        class="absolute bottom-2 left-2 text-[9px] font-mono bg-violet/20 text-violet border border-violet/20 rounded px-1 py-0.5"
                    >
                        LoRA
                    </span>
                </div>
                <!-- Info -->
                <div class="p-2.5">
                    <p class="text-xs font-medium text-text-primary truncate">{{ char.name }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span :class="['text-[10px] font-mono rounded px-1 py-0.5', typeColor[char.type]]">
                            {{ typeLabel[char.type] }}
                        </span>
                        <div class="flex items-center gap-1 text-text-muted">
                            <Cpu class="w-3 h-3" />
                            <span class="text-[10px] font-mono">{{ char.outfits_count }}</span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="characters.last_page > 1" class="flex justify-center gap-2 mt-8">
            <Link
                v-for="link in characters.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="[
                    'px-3 py-1.5 text-sm rounded-lg border transition-colors',
                    link.active
                        ? 'bg-amber text-surface-0 border-amber font-semibold'
                        : link.url
                            ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary'
                            : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40'
                ]"
            />
        </div>
    </AppLayout>
</template>
