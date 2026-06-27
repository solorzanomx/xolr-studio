<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import NavIcon from '@/Components/Layout/NavIcon.vue'

defineProps({ open: Boolean })
defineEmits(['toggle'])

const page = usePage()
const currentRoute = computed(() => page.url)

const navSections = [
    {
        items: [
            { label: 'Dashboard', href: '/', icon: 'grid' },
        ],
    },
    {
        label: 'Biblioteca',
        items: [
            { label: 'Personajes', href: '/library/characters', icon: 'users' },
            { label: 'Talent Virtual', href: '/library/virtual-talents', icon: 'star' },
            { label: 'Locaciones', href: '/library/locations', icon: 'map-pin' },
            { label: 'Estilos', href: '/library/styles', icon: 'palette' },
            { label: 'Props', href: '/library/props', icon: 'package' },
        ],
    },
    {
        label: 'Producción',
        items: [
            { label: 'Proyectos', href: '/projects', icon: 'folder' },
            { label: 'Campañas', href: '/campaigns', icon: 'megaphone' },
            { label: 'Shots', href: '/shots', icon: 'camera', soon: true },
            { label: 'Renders', href: '/renders', icon: 'image', soon: true },
        ],
    },
    {
        label: 'Audio & Video',
        items: [
            { label: 'Audio Studio', href: '/audio', icon: 'mic', soon: true },
            { label: 'Lip Sync', href: '/lipsync', icon: 'video', soon: true },
        ],
    },
    {
        label: 'Inteligencia',
        items: [
            { label: 'AI Director', href: '/ai-director', icon: 'sparkles', soon: true },
            { label: 'Intelligence', href: '/intelligence', icon: 'bar-chart', soon: true },
        ],
    },
    {
        label: 'Publicación',
        items: [
            { label: 'Calendario', href: '/calendar', icon: 'calendar', soon: true },
            { label: 'Analytics', href: '/analytics', icon: 'trending-up', soon: true },
        ],
    },
    {
        label: 'Sistema',
        items: [
            { label: 'Configuración', href: '/settings', icon: 'settings' },
        ],
    },
]

function isActive(href) {
    if (href === '/') return currentRoute.value === '/'
    return currentRoute.value.startsWith(href)
}
</script>

<template>
    <aside
        :class="[
            'flex flex-col bg-surface-1 border-r border-border transition-all duration-300 shrink-0',
            open ? 'w-56' : 'w-14'
        ]"
    >
        <!-- Logo -->
        <div class="flex items-center h-14 px-4 border-b border-border shrink-0">
            <button @click="$emit('toggle')" class="flex items-center gap-2.5 w-full min-w-0">
                <span class="text-amber font-mono font-bold text-lg shrink-0">◈</span>
                <span
                    v-if="open"
                    class="text-text-primary font-semibold text-sm truncate"
                >
                    Xolr Studio
                </span>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto py-3 space-y-4">
            <div v-for="(section, i) in navSections" :key="i" class="px-2">
                <p
                    v-if="section.label && open"
                    class="text-text-muted text-[10px] font-semibold uppercase tracking-widest px-2 pb-1"
                >
                    {{ section.label }}
                </p>
                <div v-else-if="section.label && !open" class="border-t border-border mx-2 mb-2" />

                <Link
                    v-for="item in section.items"
                    :key="item.href"
                    :href="item.soon ? '#' : item.href"
                    :class="[
                        'flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm transition-colors group relative',
                        isActive(item.href) && !item.soon
                            ? 'bg-surface-2 text-text-primary'
                            : 'text-text-secondary hover:text-text-primary hover:bg-surface-2',
                        item.soon ? 'opacity-40 cursor-default' : ''
                    ]"
                >
                    <NavIcon :name="item.icon" class="w-4 h-4 shrink-0" />
                    <span v-if="open" class="truncate">{{ item.label }}</span>
                    <span
                        v-if="open && item.soon"
                        class="ml-auto text-[9px] font-mono text-text-muted border border-border rounded px-1"
                    >
                        pronto
                    </span>
                    <!-- Tooltip cuando sidebar está cerrada -->
                    <span
                        v-if="!open"
                        class="absolute left-full ml-2 px-2 py-1 bg-surface-2 text-text-primary text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 border border-border"
                    >
                        {{ item.label }}
                    </span>
                </Link>
            </div>
        </nav>
    </aside>
</template>
