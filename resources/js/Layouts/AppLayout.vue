<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from '@/Components/Layout/Sidebar.vue'
import AppHeader from '@/Components/Layout/AppHeader.vue'

const sidebarOpen = ref(true)
const page = usePage()
const user = computed(() => page.props.auth?.user)
</script>

<template>
    <div class="flex h-screen bg-surface-0 overflow-hidden">
        <Sidebar :open="sidebarOpen" @toggle="sidebarOpen = !sidebarOpen" />

        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <AppHeader
                :sidebar-open="sidebarOpen"
                :user="user"
                @toggle-sidebar="sidebarOpen = !sidebarOpen"
            />

            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
