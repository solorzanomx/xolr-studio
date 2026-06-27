<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    User, Lock, Plug, CheckCircle, XCircle, ExternalLink,
    Save, Eye, EyeOff, Monitor, Smartphone, Globe, Trash2,
    ShieldCheck, AlertTriangle, LogOut, Palette, Clock, Mail,
    Zap, Loader2, Wifi, WifiOff,
} from '@lucide/vue'

const props = defineProps({ integrations: Object })

const page    = usePage()
const user    = computed(() => page.props.auth?.user ?? {})
const tab     = ref('profile')

// ── Avatar color picker ───────────────────────────────────────────
const PRESET_COLORS = [
    '#B8743A', '#A0252A', '#2E5147', '#3F6B7F',
    '#6B1F2A', '#1C1A18', '#7C3AED', '#0F766E',
    '#B45309', '#1D4ED8', '#BE185D', '#047857',
]

// ── Profile form ──────────────────────────────────────────────────
const profileForm = useForm({
    name:         user.value.name         ?? '',
    email:        user.value.email        ?? '',
    bio:          user.value.bio          ?? '',
    timezone:     user.value.timezone     ?? 'America/Mexico_City',
    avatar_color: user.value.avatar_color ?? '#B8743A',
})

function saveProfile() {
    profileForm.put('/profile', { preserveScroll: true })
}

// ── Password form ─────────────────────────────────────────────────
const showCurrentPw = ref(false)
const showNewPw     = ref(false)
const showConfirmPw = ref(false)

const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
})

function savePassword() {
    passwordForm.put('/profile/password', {
        onSuccess: () => passwordForm.reset(),
    })
}

// Password strength
const strength = computed(() => {
    const pw = passwordForm.password
    if (!pw) return { score: 0, label: '', checks: {} }
    const checks = {
        length:    pw.length >= 8,
        upper:     /[A-Z]/.test(pw),
        lower:     /[a-z]/.test(pw),
        number:    /[0-9]/.test(pw),
        special:   /[^A-Za-z0-9]/.test(pw),
    }
    const score = Object.values(checks).filter(Boolean).length
    const labels = ['', 'Muy débil', 'Débil', 'Regular', 'Buena', 'Excelente']
    const colors = ['', 'bg-danger', 'bg-orange-500', 'bg-amber', 'bg-emerald-400', 'bg-emerald-400']
    return { score, label: labels[score], color: colors[score], checks }
})

// ── Sessions ──────────────────────────────────────────────────────
const sessions    = ref([])
const loadingSessions = ref(false)
const killAllForm = useForm({ password: '' })
const showKillPw  = ref(false)

async function fetchSessions() {
    loadingSessions.value = true
    try {
        const res = await fetch('/profile/sessions', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        sessions.value = await res.json()
    } finally {
        loadingSessions.value = false
    }
}

function killSession(id) {
    router.delete(`/profile/sessions/${id}`, {
        onSuccess: fetchSessions,
        preserveScroll: true,
    })
}

function killOtherSessions() {
    killAllForm.delete('/profile/sessions', {
        onSuccess: () => { killAllForm.reset(); fetchSessions() },
        preserveScroll: true,
    })
}

onMounted(fetchSessions)

const DEVICE_ICON = {
    iOS: Smartphone, Android: Smartphone,
    macOS: Monitor, Windows: Monitor, Linux: Monitor,
    Desconocido: Globe,
}

// ── Initials avatar preview ───────────────────────────────────────
const initials = computed(() => {
    const parts = (profileForm.name || user.value.name || '?').split(' ')
    return parts.map(p => p[0]).slice(0, 2).join('').toUpperCase()
})

// ── RunPod connection test ────────────────────────────────────────
const runpodTest    = ref(null)   // null | { ok, message, raw }
const testingRunpod = ref(false)

async function testRunPod() {
    testingRunpod.value = true
    runpodTest.value    = null
    try {
        const res  = await fetch('/settings/runpod/ping', {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        })
        runpodTest.value = await res.json()
    } catch (e) {
        runpodTest.value = { ok: false, message: 'Error de red: ' + e.message }
    } finally {
        testingRunpod.value = false
    }
}

const TIMEZONES = [
    'America/Mexico_City', 'America/Monterrey', 'America/Cancun',
    'America/New_York', 'America/Chicago', 'America/Los_Angeles',
    'America/Bogota', 'America/Lima', 'America/Santiago',
    'America/Argentina/Buenos_Aires', 'America/Sao_Paulo',
    'Europe/Madrid', 'Europe/London', 'Europe/Paris',
    'Asia/Tokyo', 'UTC',
]
</script>

<template>
    <AppLayout>
        <Head title="Configuración" />

        <div class="max-w-3xl mx-auto">

            <!-- Page header -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-text-primary">Configuración</h1>
                <p class="text-sm text-text-muted mt-0.5">Perfil, seguridad e integraciones del studio</p>
            </div>

            <!-- Tab nav -->
            <div class="flex gap-1 bg-surface-1 border border-border rounded-xl p-1 mb-7 w-fit">
                <button v-for="[key, label, Icon] in [['profile','Perfil',User],['security','Seguridad',Lock],['integrations','Integraciones',Plug]]"
                    :key="key" @click="tab = key"
                    :class="[
                        'flex items-center gap-2 px-4 py-1.5 text-sm font-semibold rounded-lg transition-colors',
                        tab === key ? 'bg-surface-0 text-text-primary shadow-sm' : 'text-text-muted hover:text-text-primary'
                    ]">
                    <component :is="Icon" class="w-3.5 h-3.5" />
                    {{ label }}
                </button>
            </div>

            <!-- ════════════════ TAB: PERFIL ════════════════ -->
            <div v-if="tab === 'profile'" class="space-y-5">

                <!-- Avatar + color -->
                <div class="bg-surface-1 border border-border rounded-2xl p-6">
                    <h2 class="text-sm font-semibold text-text-primary mb-5">Avatar</h2>
                    <div class="flex items-center gap-6">
                        <!-- Live preview -->
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-bold text-white shrink-0 shadow-lg transition-colors duration-300"
                            :style="{ backgroundColor: profileForm.avatar_color }">
                            {{ initials }}
                        </div>
                        <div>
                            <p class="text-xs text-text-muted mb-3">Color del avatar</p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="color in PRESET_COLORS" :key="color"
                                    @click="profileForm.avatar_color = color"
                                    class="w-7 h-7 rounded-lg border-2 transition-all"
                                    :style="{ backgroundColor: color }"
                                    :class="profileForm.avatar_color === color ? 'border-white scale-110 shadow-md' : 'border-transparent hover:scale-105'" />
                                <!-- Custom color -->
                                <label class="w-7 h-7 rounded-lg border-2 border-dashed border-border flex items-center justify-center cursor-pointer hover:border-amber transition-colors" title="Color personalizado">
                                    <span class="text-[10px] text-text-muted">+</span>
                                    <input type="color" v-model="profileForm.avatar_color" class="sr-only" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info personal -->
                <div class="bg-surface-1 border border-border rounded-2xl p-6">
                    <h2 class="text-sm font-semibold text-text-primary mb-5">Información personal</h2>
                    <form @submit.prevent="saveProfile" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-text-muted mb-1.5">Nombre completo</label>
                                <div class="relative">
                                    <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                                    <input v-model="profileForm.name" type="text" required
                                        class="w-full bg-surface-2 border border-border rounded-lg pl-9 pr-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                                        :class="profileForm.errors.name ? 'border-danger' : ''" />
                                </div>
                                <p v-if="profileForm.errors.name" class="text-[11px] text-danger mt-1">{{ profileForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1.5">Email</label>
                                <div class="relative">
                                    <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                                    <input v-model="profileForm.email" type="email" required
                                        class="w-full bg-surface-2 border border-border rounded-lg pl-9 pr-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                                        :class="profileForm.errors.email ? 'border-danger' : ''" />
                                </div>
                                <p v-if="profileForm.errors.email" class="text-[11px] text-danger mt-1">{{ profileForm.errors.email }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-text-muted mb-1.5">Bio</label>
                            <textarea v-model="profileForm.bio" rows="2" placeholder="Director creativo, productora de contenido AI..."
                                class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                            <p class="text-[10px] text-text-muted mt-1 text-right">{{ profileForm.bio?.length ?? 0 }}/300</p>
                        </div>

                        <div>
                            <label class="block text-xs text-text-muted mb-1.5 flex items-center gap-1">
                                <Clock class="w-3 h-3" /> Zona horaria
                            </label>
                            <select v-model="profileForm.timezone"
                                class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option v-for="tz in TIMEZONES" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <p v-if="profileForm.recentlySuccessful" class="text-xs text-emerald-400 flex items-center gap-1">
                                <CheckCircle class="w-3.5 h-3.5" /> Guardado
                            </p>
                            <p v-else class="text-xs text-text-muted">Miembro desde {{ new Date(user.created_at).toLocaleDateString('es', { year: 'numeric', month: 'long' }) }}</p>
                            <button type="submit" :disabled="profileForm.processing"
                                class="flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4" /> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ════════════════ TAB: SEGURIDAD ════════════════ -->
            <div v-else-if="tab === 'security'" class="space-y-5">

                <!-- Change password -->
                <div class="bg-surface-1 border border-border rounded-2xl p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <Lock class="w-4 h-4 text-amber" />
                        <h2 class="text-sm font-semibold text-text-primary">Cambiar contraseña</h2>
                    </div>

                    <form @submit.prevent="savePassword" class="space-y-4">
                        <!-- Current password -->
                        <div>
                            <label class="block text-xs text-text-muted mb-1.5">Contraseña actual</label>
                            <div class="relative">
                                <input v-model="passwordForm.current_password"
                                    :type="showCurrentPw ? 'text' : 'password'"
                                    placeholder="Tu contraseña actual"
                                    class="w-full bg-surface-2 border border-border rounded-lg px-3 pr-10 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                                    :class="passwordForm.errors.current_password ? 'border-danger' : ''" />
                                <button type="button" @click="showCurrentPw = !showCurrentPw"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors">
                                    <EyeOff v-if="showCurrentPw" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-[11px] text-danger mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <!-- New password -->
                        <div>
                            <label class="block text-xs text-text-muted mb-1.5">Nueva contraseña</label>
                            <div class="relative">
                                <input v-model="passwordForm.password"
                                    :type="showNewPw ? 'text' : 'password'"
                                    placeholder="Mínimo 8 caracteres"
                                    class="w-full bg-surface-2 border border-border rounded-lg px-3 pr-10 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                                    :class="passwordForm.errors.password ? 'border-danger' : ''" />
                                <button type="button" @click="showNewPw = !showNewPw"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors">
                                    <EyeOff v-if="showNewPw" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.password" class="text-[11px] text-danger mt-1">{{ passwordForm.errors.password }}</p>

                            <!-- Strength meter -->
                            <div v-if="passwordForm.password" class="mt-2 space-y-2">
                                <div class="flex gap-1">
                                    <div v-for="i in 5" :key="i"
                                        class="flex-1 h-1 rounded-full transition-all duration-300"
                                        :class="i <= strength.score ? strength.color : 'bg-surface-3'" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px]" :class="{
                                        'text-danger': strength.score <= 1,
                                        'text-orange-400': strength.score === 2,
                                        'text-amber': strength.score === 3,
                                        'text-emerald-400': strength.score >= 4,
                                    }">{{ strength.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <span v-for="[key, label] in [['length','8+'],['upper','A'],['lower','a'],['number','1'],['special','!@']]"
                                            :key="key"
                                            class="text-[9px] font-mono px-1.5 py-0.5 rounded transition-colors"
                                            :class="strength.checks[key] ? 'bg-emerald-400/20 text-emerald-400' : 'bg-surface-3 text-text-muted'">
                                            {{ label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm password -->
                        <div>
                            <label class="block text-xs text-text-muted mb-1.5">Confirmar nueva contraseña</label>
                            <div class="relative">
                                <input v-model="passwordForm.password_confirmation"
                                    :type="showConfirmPw ? 'text' : 'password'"
                                    placeholder="Repite la contraseña"
                                    class="w-full bg-surface-2 border border-border rounded-lg px-3 pr-10 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                                    :class="passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation ? 'border-danger' : (passwordForm.password && passwordForm.password === passwordForm.password_confirmation ? 'border-emerald-400' : '')" />
                                <button type="button" @click="showConfirmPw = !showConfirmPw"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors">
                                    <EyeOff v-if="showConfirmPw" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.password_confirmation" class="text-[11px] text-danger mt-1">{{ passwordForm.errors.password_confirmation }}</p>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <p class="text-[11px] text-text-muted flex items-center gap-1">
                                <ShieldCheck class="w-3.5 h-3.5 text-amber" />
                                Cerrarás sesión en todos los dispositivos al cambiar la contraseña.
                            </p>
                            <button type="submit"
                                :disabled="passwordForm.processing || strength.score < 3 || passwordForm.password !== passwordForm.password_confirmation"
                                class="flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-40 transition-colors">
                                <Lock class="w-4 h-4" /> Actualizar contraseña
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Active sessions -->
                <div class="bg-surface-1 border border-border rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2">
                            <Monitor class="w-4 h-4 text-amber" />
                            <h2 class="text-sm font-semibold text-text-primary">Sesiones activas</h2>
                            <span class="text-[10px] font-mono text-text-muted bg-surface-2 px-1.5 py-0.5 rounded">{{ sessions.length }}</span>
                        </div>
                        <button @click="fetchSessions" :disabled="loadingSessions"
                            class="text-xs text-text-muted hover:text-text-primary transition-colors">
                            {{ loadingSessions ? 'Cargando…' : 'Actualizar' }}
                        </button>
                    </div>

                    <div class="space-y-2 mb-5">
                        <div v-for="s in sessions" :key="s.id"
                            class="flex items-center gap-4 bg-surface-2 rounded-xl px-4 py-3">
                            <component :is="DEVICE_ICON[s.device] ?? Globe" class="w-5 h-5 text-text-muted shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-text-primary">{{ s.browser }} en {{ s.device }}</p>
                                    <span v-if="s.is_current"
                                        class="text-[9px] font-semibold px-1.5 py-0.5 bg-emerald-400/10 text-emerald-400 rounded">
                                        Esta sesión
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-[11px] text-text-muted font-mono">{{ s.ip_address }}</span>
                                    <span class="text-[11px] text-text-muted">{{ s.last_active }}</span>
                                </div>
                            </div>
                            <button v-if="!s.is_current" @click="killSession(s.id)"
                                class="p-1.5 text-text-muted hover:text-danger hover:bg-danger/10 rounded-lg transition-colors" title="Cerrar sesión">
                                <LogOut class="w-4 h-4" />
                            </button>
                        </div>
                        <div v-if="!sessions.length && !loadingSessions" class="text-center py-4 text-sm text-text-muted">
                            Sin sesiones registradas.
                        </div>
                    </div>

                    <!-- Kill all others -->
                    <div v-if="sessions.length > 1" class="border-t border-border pt-4">
                        <p class="text-xs text-text-muted mb-3">Cierra todas las sesiones excepto la actual. Se requiere tu contraseña.</p>
                        <form @submit.prevent="killOtherSessions" class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input v-model="killAllForm.password"
                                    :type="showKillPw ? 'text' : 'password'"
                                    placeholder="Confirma tu contraseña"
                                    class="w-full bg-surface-2 border border-border rounded-lg px-3 pr-10 py-2 text-sm text-text-primary focus:outline-none focus:border-danger transition-colors"
                                    :class="killAllForm.errors.password ? 'border-danger' : ''" />
                                <button type="button" @click="showKillPw = !showKillPw"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">
                                    <EyeOff v-if="showKillPw" class="w-3.5 h-3.5" />
                                    <Eye v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <button type="submit" :disabled="killAllForm.processing || !killAllForm.password"
                                class="flex items-center gap-1.5 px-3 py-2 bg-danger/10 text-danger text-sm font-semibold rounded-lg hover:bg-danger/20 disabled:opacity-40 transition-colors whitespace-nowrap">
                                <Trash2 class="w-4 h-4" /> Cerrar otras sesiones
                            </button>
                        </form>
                        <p v-if="killAllForm.errors.password" class="text-[11px] text-danger mt-1">{{ killAllForm.errors.password }}</p>
                    </div>
                </div>
            </div>

            <!-- ════════════════ TAB: INTEGRACIONES ════════════════ -->
            <div v-else-if="tab === 'integrations'">
                <div class="bg-surface-1 border border-border rounded-2xl divide-y divide-border overflow-hidden mb-3">
                    <div v-for="(integration, key) in integrations" :key="key">
                        <div class="flex items-center gap-4 px-5 py-4">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="integration.configured ? 'bg-emerald-400/10' : 'bg-surface-2'">
                                <CheckCircle v-if="integration.configured" class="w-4 h-4 text-emerald-400" />
                                <XCircle v-else class="w-4 h-4 text-text-muted" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-text-primary">{{ integration.label }}</p>
                                <p class="text-xs text-text-muted mt-0.5">{{ integration.description }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Test button only for RunPod -->
                                <button v-if="key === 'runpod' && integration.configured"
                                    @click="testRunPod"
                                    :disabled="testingRunpod"
                                    class="flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-amber/40 text-amber hover:bg-amber/10 disabled:opacity-50 transition-colors">
                                    <Loader2 v-if="testingRunpod" class="w-3 h-3 animate-spin" />
                                    <Zap v-else class="w-3 h-3" />
                                    {{ testingRunpod ? 'Probando…' : 'Probar' }}
                                </button>
                                <span class="text-[10px] font-mono px-2 py-1 rounded-lg"
                                    :class="integration.configured ? 'bg-emerald-400/10 text-emerald-400' : 'bg-surface-2 text-text-muted'">
                                    {{ integration.configured ? 'configurado' : 'sin configurar' }}
                                </span>
                            </div>
                        </div>
                        <!-- RunPod test result -->
                        <div v-if="key === 'runpod' && runpodTest" class="px-5 pb-4">
                            <div class="flex items-start gap-2.5 rounded-xl px-3 py-2.5 text-xs"
                                :class="runpodTest.ok ? 'bg-emerald-400/10 text-emerald-400' : 'bg-danger/10 text-danger'">
                                <Wifi v-if="runpodTest.ok" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                                <WifiOff v-else class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                                <div>
                                    <p class="font-semibold">{{ runpodTest.ok ? 'Conexión exitosa' : 'Error de conexión' }}</p>
                                    <p class="opacity-80 mt-0.5">{{ runpodTest.message }}</p>
                                    <div v-if="runpodTest.raw && runpodTest.ok" class="mt-1.5 font-mono text-[10px] opacity-60 flex gap-3">
                                        <span>ready: {{ runpodTest.raw.ready ?? 0 }}</span>
                                        <span>idle: {{ runpodTest.raw.idle ?? 0 }}</span>
                                        <span>running: {{ runpodTest.raw.running ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-text-muted flex items-center gap-1.5 px-1">
                    <ExternalLink class="w-3 h-3" />
                    Configura las API keys en el archivo <code class="font-mono text-text-secondary bg-surface-2 px-1 py-0.5 rounded">.env</code> del servidor via nano.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
