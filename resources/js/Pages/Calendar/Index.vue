<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronLeft, ChevronRight, Plus, X,
    CheckCircle, Clock, AlertCircle, Loader, Send
} from '@lucide/vue'

const props = defineProps({
    posts:       Object,  // keyed by 'YYYY-MM-DD'
    month:       Number,
    year:        Number,
    projectId:   Number,
    projects:    Array,
    daysInMonth: Number,
    firstDayDow: Number,  // 0=Sunday
})

// Calendar grid
const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
const DAYS   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']

const cells = computed(() => {
    const grid = []
    // Empty cells before first day
    for (let i = 0; i < props.firstDayDow; i++) grid.push(null)
    for (let d = 1; d <= props.daysInMonth; d++) grid.push(d)
    return grid
})

function dateKey(day) {
    return `${props.year}-${String(props.month).padStart(2,'0')}-${String(day).padStart(2,'0')}`
}

function postsForDay(day) {
    return props.posts?.[dateKey(day)] ?? []
}

function navigate(delta) {
    let m = props.month + delta
    let y = props.year
    if (m > 12) { m = 1; y++ }
    if (m < 1)  { m = 12; y-- }
    router.get('/calendar', { month: m, year: y, project_id: props.projectId }, { preserveState: false })
}

// Post creation modal
const showModal   = ref(false)
const selectedDay = ref(null)

function openModal(day) {
    selectedDay.value = day
    postForm.reset()
    postForm.scheduled_for = `${dateKey(day)}T10:00`
    postForm.project_id    = props.projectId ?? (props.projects[0]?.id ?? '')
    showModal.value = true
}

const postForm = useForm({
    project_id:    '',
    platform:      'instagram',
    post_type:     'post',
    caption:       '',
    hashtags:      '',
    scheduled_for: '',
})

function submitPost() {
    postForm.post('/social-posts', {
        onSuccess: () => { showModal.value = false; postForm.reset() },
    })
}

// Publish now
function publishNow(postId) {
    router.post(`/social-posts/${postId}/publish`, {}, { preserveScroll: true })
}

// Delete post
function deletePost(postId) {
    if (!confirm('¿Eliminar este post?')) return
    router.delete(`/social-posts/${postId}`, { preserveScroll: true })
}

const PLATFORM_COLOR = {
    instagram: 'bg-pink-500/10 text-pink-400',
    youtube:   'bg-red-500/10 text-red-400',
    tiktok:    'bg-text-primary/10 text-text-primary',
    facebook:  'bg-blue-500/10 text-blue-400',
    linkedin:  'bg-blue-600/10 text-blue-300',
}

const STATUS_ICON = {
    draft:       Clock,
    scheduled:   Clock,
    publishing:  Loader,
    published:   CheckCircle,
    failed:      AlertCircle,
}

const STATUS_COLOR = {
    draft:      'text-text-muted',
    scheduled:  'text-amber',
    publishing: 'text-violet',
    published:  'text-emerald-400',
    failed:     'text-danger',
}
</script>

<template>
    <AppLayout>
        <Head title="Calendario de contenido" />

        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Calendario</h1>
                    <p class="text-sm text-text-muted">Programación de publicaciones en redes sociales</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Project filter -->
                    <select
                        :value="projectId"
                        @change="router.get('/calendar', { month, year, project_id: $event.target.value })"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                    >
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <button @click="showModal = true; selectedDay = null; postForm.scheduled_for = ''"
                        class="flex items-center gap-2 px-3 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                        <Plus class="w-4 h-4" /> Nuevo post
                    </button>
                </div>
            </div>

            <!-- Month nav -->
            <div class="flex items-center justify-between bg-surface-1 border border-border rounded-xl px-4 py-3 mb-4">
                <button @click="navigate(-1)" class="p-1 text-text-muted hover:text-text-primary transition-colors rounded">
                    <ChevronLeft class="w-5 h-5" />
                </button>
                <h2 class="text-base font-semibold text-text-primary">{{ MONTHS[month - 1] }} {{ year }}</h2>
                <button @click="navigate(1)" class="p-1 text-text-muted hover:text-text-primary transition-colors rounded">
                    <ChevronRight class="w-5 h-5" />
                </button>
            </div>

            <!-- Calendar grid -->
            <div class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                <!-- Day headers -->
                <div class="grid grid-cols-7 border-b border-border">
                    <div v-for="d in DAYS" :key="d" class="py-2 text-center text-xs font-semibold text-text-muted">{{ d }}</div>
                </div>

                <!-- Cells -->
                <div class="grid grid-cols-7">
                    <div
                        v-for="(cell, i) in cells"
                        :key="i"
                        :class="[
                            'min-h-[90px] border-b border-r border-border last:border-r-0 p-1.5',
                            cell ? 'cursor-pointer hover:bg-surface-2 transition-colors' : 'bg-surface-0',
                            dateKey(cell) === new Date().toISOString().split('T')[0] ? 'bg-amber/5' : '',
                        ]"
                        @click="cell && openModal(cell)"
                    >
                        <template v-if="cell">
                            <p class="text-xs font-mono text-text-muted mb-1"
                               :class="dateKey(cell) === new Date().toISOString().split('T')[0] ? 'text-amber font-bold' : ''">
                                {{ cell }}
                            </p>
                            <div class="space-y-0.5">
                                <div
                                    v-for="post in postsForDay(cell)"
                                    :key="post.id"
                                    @click.stop
                                    class="flex items-center gap-1 px-1 py-0.5 rounded text-[10px] truncate group"
                                    :class="PLATFORM_COLOR[post.platform]"
                                >
                                    <component :is="STATUS_ICON[post.status]" class="w-2.5 h-2.5 shrink-0" :class="STATUS_COLOR[post.status]" />
                                    <span class="truncate flex-1">{{ post.caption?.slice(0, 20) || post.post_type }}</span>
                                    <button
                                        v-if="post.status === 'scheduled'"
                                        @click.stop="publishNow(post.id)"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity ml-0.5"
                                        title="Publicar ahora"
                                    >
                                        <Send class="w-2.5 h-2.5" />
                                    </button>
                                    <button
                                        @click.stop="deletePost(post.id)"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Eliminar"
                                    >
                                        <X class="w-2.5 h-2.5" />
                                    </button>
                                </div>
                            </div>
                            <button
                                class="mt-1 text-[9px] text-text-muted opacity-0 hover:opacity-100 transition-opacity flex items-center gap-0.5"
                                @click.stop="openModal(cell)"
                            >
                                <Plus class="w-2.5 h-2.5" /> add
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Upcoming posts list -->
            <div class="mt-6 bg-surface-1 border border-border rounded-xl">
                <div class="p-4 border-b border-border">
                    <p class="text-xs font-semibold text-text-primary">Próximos posts programados</p>
                </div>
                <div class="divide-y divide-border">
                    <template v-for="(dayPosts, date) in posts" :key="date">
                        <div
                            v-for="post in dayPosts"
                            :key="post.id"
                            class="flex items-center justify-between px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono text-text-muted w-24">{{ new Date(post.scheduled_for).toLocaleDateString('es', { day: '2-digit', month: 'short' }) }}</span>
                                <span class="text-xs px-2 py-0.5 rounded" :class="PLATFORM_COLOR[post.platform]">{{ post.platform }}</span>
                                <span class="text-xs px-1.5 py-0.5 bg-surface-2 text-text-muted rounded">{{ post.post_type }}</span>
                                <p class="text-sm text-text-secondary truncate max-w-xs">{{ post.caption || '(sin caption)' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <component :is="STATUS_ICON[post.status]" class="w-4 h-4" :class="STATUS_COLOR[post.status]" />
                                <span class="text-xs" :class="STATUS_COLOR[post.status]">{{ post.status }}</span>
                                <button
                                    v-if="post.status === 'scheduled'"
                                    @click="publishNow(post.id)"
                                    class="px-2 py-1 text-xs bg-violet/10 text-violet rounded-lg hover:bg-violet/20 transition-colors"
                                >
                                    Publicar ahora
                                </button>
                                <button @click="deletePost(post.id)" class="p-1 text-text-muted hover:text-danger transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                    <div v-if="!Object.keys(posts ?? {}).length" class="px-4 py-8 text-center text-sm text-text-muted">
                        No hay posts programados este mes.
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Post Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showModal = false">
            <div class="bg-surface-1 border border-border rounded-2xl p-6 w-full max-w-md mx-4 shadow-2xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-text-primary">
                        Nuevo post{{ selectedDay ? ` — día ${selectedDay}` : '' }}
                    </h3>
                    <button @click="showModal = false" class="text-text-muted hover:text-text-primary transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitPost" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Proyecto</label>
                            <select v-model="postForm.project_id" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Plataforma</label>
                            <select v-model="postForm.platform" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="instagram">Instagram</option>
                                <option value="youtube">YouTube</option>
                                <option value="tiktok">TikTok</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Tipo</label>
                            <select v-model="postForm.post_type" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="post">Post</option>
                                <option value="carousel">Carrusel</option>
                                <option value="story">Story</option>
                                <option value="reel">Reel</option>
                                <option value="video">Video</option>
                                <option value="short">Short</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Fecha y hora</label>
                            <input v-model="postForm.scheduled_for" type="datetime-local"
                                class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Caption</label>
                        <textarea v-model="postForm.caption" rows="3" placeholder="Escribe el texto del post..."
                            class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Hashtags</label>
                        <input v-model="postForm.hashtags" type="text" placeholder="#xolrstudio #ia #contenido"
                            class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="postForm.processing"
                            class="flex-1 py-2.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                            Programar post
                        </button>
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2.5 bg-surface-2 text-text-secondary text-sm rounded-lg hover:text-text-primary transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
