<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SidebarTrigger } from '@/components/ui/sidebar';
import TaskForm from '@/pages/tasks/components/TaskForm.vue';
import type { BreadcrumbItem, Project } from '@/types';

const TASK_FORM_KEYBOARD_SHORTCUT = 'n';

const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPod|iPad/i.test(navigator.userAgent);
const taskFormShortcutLabel = isMac ? '⌘⌥N' : 'Ctrl+Alt+N';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<{ projects: Project[]; selectedProjectUlid?: string | null }>();
const projects = computed(() => page.props.projects ?? []);
const selectedProjectUlid = computed(() => page.props.selectedProjectUlid ?? null);

const defaultProjectId = computed(() => {
    if (!selectedProjectUlid.value || !projects.value.length) {
        return undefined;
    }

    const project = projects.value.find((p) => p.ulid === selectedProjectUlid.value);

    return project ? String(project.id) : undefined;
});

const dialogOpen = ref(false);

function handleTaskSubmit() {
    dialogOpen.value = false;
    toast.success('Tarefa adicionada com sucesso!');
}

function handleTaskCancel() {
    dialogOpen.value = false;
}

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key.toLowerCase() !== TASK_FORM_KEYBOARD_SHORTCUT) {
        return;
    }

    if (!(event.ctrlKey || event.metaKey) || !event.altKey) {
        return;
    }

    const target = event.target as HTMLElement | null;

    if (target?.closest('input, textarea, select, [contenteditable="true"]')) {
        return;
    }

    if (dialogOpen.value) {
        return;
    }

    event.preventDefault();
    dialogOpen.value = true;
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length === 1">
                <h1
                    class="text-xl font-semibold tracking-tight text-foreground"
                >
                    {{ breadcrumbs[0].title }}
                </h1>
            </template>
            <template v-else-if="breadcrumbs && breadcrumbs.length > 1">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Button
            variant="outline"
            size="sm"
            class="gap-2"
            @click="dialogOpen = true"
        >
            <Plus class="size-4" />
            <span>Adicionar tarefa</span>
            <kbd
                class="pointer-events-none hidden h-5 items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100 select-none sm:inline-flex"
            >
                {{ taskFormShortcutLabel }}
            </kbd>
        </Button>
    </header>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nova Tarefa</DialogTitle>
                <DialogDescription>
                    Preencha os dados para criar uma nova tarefa.
                </DialogDescription>
            </DialogHeader>
            <TaskForm
                :projects="projects"
                :default-project-id="defaultProjectId"
                @submit="handleTaskSubmit"
                @cancel="handleTaskCancel"
            />
        </DialogContent>
    </Dialog>
</template>
