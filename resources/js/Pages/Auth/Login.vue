<script setup>
import { useForm, Head } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Acceso" />

    <div class="min-h-screen bg-surface-0 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="text-4xl font-mono font-bold text-amber mb-1">&#9672;</div>
                <h1 class="text-xl font-semibold text-text-primary">Xolr Studio</h1>
                <p class="text-sm text-text-muted mt-1">Where Imagination Renders Reality</p>
            </div>

            <!-- Card -->
            <div class="bg-surface-1 border border-border rounded-xl p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">
                            Email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            :class="[
                                'w-full bg-surface-2 border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted outline-none transition-colors',
                                form.errors.email ? 'border-danger' : 'border-border focus:border-amber'
                            ]"
                            placeholder="tu@email.com"
                        />
                        <p v-if="form.errors.email" class="text-danger text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">
                            Contraseña
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            :class="[
                                'w-full bg-surface-2 border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted outline-none transition-colors',
                                form.errors.password ? 'border-danger' : 'border-border focus:border-amber'
                            ]"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        />
                        <p v-if="form.errors.password" class="text-danger text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-amber hover:bg-amber/90 disabled:opacity-50 text-surface-0 font-semibold rounded-lg px-4 py-2.5 text-sm transition-colors"
                    >
                        {{ form.processing ? 'Accediendo...' : 'Acceder al Studio' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
