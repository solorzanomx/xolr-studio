<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Zap, ChevronDown, LogOut, User } from '@lucide/vue'

const props = defineProps({
    sidebarOpen: Boolean,
    user: Object,
})
defineEmits(['toggle-sidebar'])

const userMenuOpen = ref(false)

function logout() {
    router.post('/logout')
}
</script>

<template>
    <header class="h-14 bg-surface-1 border-b border-border flex items-center px-4 gap-4 shrink-0">
        <!-- Active jobs indicator -->
        <div class="flex items-center gap-1.5 text-sm">
            <Zap class="w-3.5 h-3.5 text-amber" />
            <span class="font-mono text-text-secondary text-xs">
                <span class="text-text-primary font-semibold" id="active-jobs-count">0</span> jobs
            </span>
        </div>

        <!-- Cost today -->
        <div class="font-mono text-xs text-text-secondary">
            <span class="text-text-primary font-semibold" id="today-cost">$0.00</span>
            <span class="ml-1">hoy</span>
        </div>

        <div class="flex-1" />

        <!-- User menu -->
        <div class="relative" v-if="user">
            <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2 text-sm text-text-secondary hover:text-text-primary transition-colors"
            >
                <div class="w-7 h-7 rounded-full bg-amber/20 flex items-center justify-center">
                    <span class="text-amber font-semibold text-xs">
                        {{ user.name?.charAt(0) }}
                    </span>
                </div>
                <span class="text-sm hidden sm:block">{{ user.name?.split(' ')[0] }}</span>
                <ChevronDown class="w-3.5 h-3.5" />
            </button>

            <div
                v-if="userMenuOpen"
                class="absolute right-0 top-full mt-2 w-44 bg-surface-2 border border-border rounded-lg shadow-xl py-1 z-50"
            >
                <Link
                    href="/settings"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface-3 transition-colors"
                    @click="userMenuOpen = false"
                >
                    <User class="w-4 h-4" />
                    Configuración
                </Link>
                <div class="border-t border-border my-1" />
                <button
                    @click="logout"
                    class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-danger hover:bg-surface-3 transition-colors"
                >
                    <LogOut class="w-4 h-4" />
                    Cerrar sesión
                </button>
            </div>
        </div>
    </header>
</template>
