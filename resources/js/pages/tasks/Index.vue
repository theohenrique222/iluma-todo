<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ListTodo,
    Trash2,
    CalendarDays,
    Flag,
    CheckCircle2,
    CircleDot,
    ChevronLeft,
    ChevronRight,
    RotateCcw,
    CirclePlay,
    Folder,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import ProjectFilterSelect from '@/components/projects/ProjectFilterSelect.vue';
import TaskDetailSheet from '@/pages/tasks/components/TaskDetailSheet.vue';
import { index as tasksIndex } from '@/routes/tasks';
import type { Project } from '@/types';

interface Task {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    completed_at: string | null;
    created_at: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status: string;
    started_at: string | null;
    user: {
        name: string;
    };
    project: {
        id: number;
        ulid: string;
        name: string;
        color?: string;
    } | null;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTasks {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
    data: Task[];
}

interface SharedProps {
    projects: Project[];
}

const props = defineProps<{
    tasks: PaginatedTasks;
    filter?: 'pending' | 'completed' | 'trash';
    selectedProjectUlid?: string | null;
    counts: {
        pending: number;
        completed: number;
        trash: number;
    };
}>();

const page = usePage<SharedProps>();
const sharedProjects = computed(() => page.props.projects ?? []);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Minhas Tarefas',
                href: tasksIndex(),
            },
        ],
    },
});

const selectedTask = ref<Task | null>(null);
const sheetOpen = ref(false);
const fadingTaskIds = ref<Set<number>>(new Set());
const localFilter = ref<'pending' | 'completed' | 'trash'>(
    props.filter ?? 'pending',
);
const localTasks = ref<Task[]>([...props.tasks.data]);
const localPaginator = ref<PaginatedTasks>(props.tasks);
const localProjectUlid = ref<string | null>(props.selectedProjectUlid || null);

const notStartedTasks = computed(() =>
    localTasks.value.filter((t) => t.status !== 'in_progress'),
);

const inProgressTasks = computed(() =>
    localTasks.value.filter((t) => t.status === 'in_progress'),
);

watch(
    () => props.tasks,
    (newTasks) => {
        localTasks.value = [...newTasks.data];
        localPaginator.value = newTasks;
        fadingTaskIds.value.clear();

        if (selectedTask.value) {
            const updated = newTasks.data.find(
                (t) => t.id === selectedTask.value!.id,
            );

            if (updated) {
                selectedTask.value = updated;
            }
        }
    },
);

watch(
    () => props.filter,
    (newFilter) => {
        if (newFilter) {
            localFilter.value = newFilter;
        }
    },
);

watch(
    () => props.selectedProjectUlid,
    (newUlid) => {
        localProjectUlid.value = newUlid || null;
    },
);

function setFilter(filter: 'pending' | 'completed' | 'trash') {
    localFilter.value = filter;
    const params: Record<string, string> = { filter };

    if (localProjectUlid.value) {
        params.project = localProjectUlid.value;
    }

    router.get(tasksIndex().url, params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function setProject(ulid: string | null) {
    localProjectUlid.value = ulid;
    const params: Record<string, string> = { filter: localFilter.value };

    if (ulid) {
        params.project = ulid;
    }

    router.get(tasksIndex().url, params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function getPageFromUrl(url: string | null): number | null {
    if (!url) {
        return null;
    }

    const match = url.match(/[?&]page=(\d+)/);

    return match ? parseInt(match[1], 10) : null;
}

function goToPage(url: string | null) {
    const page = getPageFromUrl(url);

    if (page) {
        const params: Record<string, string | number> = {
            filter: localFilter.value,
            page,
        };

        if (localProjectUlid.value) {
            params.project = localProjectUlid.value;
        }

        router.get(tasksIndex().url, params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }
}

function openTaskSheet(task: Task) {
    selectedTask.value = task;
    sheetOpen.value = true;
}

function completeTask(task: Task, event: Event) {
    event.stopPropagation();
    fadingTaskIds.value.add(task.id);

    setTimeout(() => {
        localTasks.value = localTasks.value.filter((t) => t.id !== task.id);
        fadingTaskIds.value.delete(task.id);
    }, 1000);

    router.patch(
        `/tasks/${task.id}`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function startTask(task: Task, event: Event) {
    event.stopPropagation();
    fadingTaskIds.value.add(task.id);

    setTimeout(() => {
        const taskIndex = localTasks.value.findIndex((t) => t.id === task.id);

        if (taskIndex !== -1) {
            localTasks.value[taskIndex] = {
                ...localTasks.value[taskIndex],
                status: 'in_progress',
                started_at: new Date().toISOString(),
            };
        }

        fadingTaskIds.value.delete(task.id);
    }, 1000);

    router.patch(
        `/tasks/${task.id}/start`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
    });
}

function getPriorityColor(priority: string): {
    bg: string;
    text: string;
    border: string;
    badge: string;
} {
    const colors = {
        low: {
            bg: 'bg-emerald-50 dark:bg-emerald-950/30',
            text: 'text-emerald-700 dark:text-emerald-400',
            border: 'border-emerald-200 dark:border-emerald-800',
            badge: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
        },
        medium: {
            bg: 'bg-amber-50 dark:bg-amber-950/30',
            text: 'text-amber-700 dark:text-amber-400',
            border: 'border-amber-200 dark:border-amber-800',
            badge: 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
        },
        high: {
            bg: 'bg-orange-50 dark:bg-orange-950/30',
            text: 'text-orange-700 dark:text-orange-400',
            border: 'border-orange-200 dark:border-orange-800',
            badge: 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300',
        },
        urgent: {
            bg: 'bg-red-50 dark:bg-red-950/30',
            text: 'text-red-700 dark:text-red-400',
            border: 'border-red-200 dark:border-red-800',
            badge: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
        },
    };

    return colors[priority as keyof typeof colors] || colors.medium;
}

function getPriorityLabel(priority: string): string {
    return (
        {
            low: 'Baixa',
            medium: 'Média',
            high: 'Alta',
            urgent: 'Urgente',
        }[priority] || priority
    );
}

function restoreTask(task: Task, event: Event) {
    event.stopPropagation();
    fadingTaskIds.value.add(task.id);

    setTimeout(() => {
        localTasks.value = localTasks.value.filter((t) => t.id !== task.id);
        fadingTaskIds.value.delete(task.id);
    }, 1000);

    router.post(
        `/tasks/${task.id}/restore`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function forceDeleteTask(task: Task, event: Event) {
    event.stopPropagation();
    fadingTaskIds.value.add(task.id);

    setTimeout(() => {
        localTasks.value = localTasks.value.filter((t) => t.id !== task.id);
        fadingTaskIds.value.delete(task.id);
    }, 1000);

    router.delete(
        `/tasks/${task.id}/force`,
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Tarefas" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:gap-8 md:p-6 lg:p-8">
        <p class="text-sm text-muted-foreground">
            Organize seu dia, uma tarefa de cada vez
        </p>

        <div class="flex flex-col gap-4">
            <div class="-mb-1 flex overflow-x-auto pb-1">
                <div class="inline-flex rounded-lg border bg-muted/50 p-1">
                    <button
                        type="button"
                        @click="setFilter('pending')"
                        class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium whitespace-nowrap transition-all duration-200"
                        :class="
                            localFilter === 'pending'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        <CircleDot class="size-4" />
                        Pendentes
                        <span
                            class="ml-1.5 flex h-5 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium text-muted-foreground"
                            :class="
                                localFilter === 'pending'
                                    ? 'bg-primary/10 text-primary'
                                    : ''
                            "
                        >
                            {{ counts.pending }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="setFilter('completed')"
                        class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium whitespace-nowrap transition-all duration-200"
                        :class="
                            localFilter === 'completed'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        <CheckCircle2 class="size-4" />
                        Concluídas
                        <span
                            class="ml-1.5 flex h-5 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium text-muted-foreground"
                            :class="
                                localFilter === 'completed'
                                    ? 'bg-primary/10 text-primary'
                                    : ''
                            "
                        >
                            {{ counts.completed }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="setFilter('trash')"
                        class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium whitespace-nowrap transition-all duration-200"
                        :class="
                            localFilter === 'trash'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        <Trash2 class="size-4" />
                        Lixeira
                        <span
                            class="ml-1.5 flex h-5 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium text-muted-foreground"
                            :class="
                                localFilter === 'trash'
                                    ? 'bg-primary/10 text-primary'
                                    : ''
                            "
                        >
                            {{ counts.trash }}
                        </span>
                    </button>
                </div>
            </div>

            <ProjectFilterSelect
                v-if="sharedProjects.length > 0"
                :model-value="localProjectUlid"
                :projects="sharedProjects"
                :total-task-count="
                    counts.pending + counts.completed + counts.trash
                "
                @update:model-value="setProject"
            />
        </div>

        <div v-if="localTasks.length === 0" class="py-16 text-center">
            <div
                class="mx-auto flex size-16 items-center justify-center rounded-full bg-muted"
            >
                <ListTodo class="size-8 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-medium text-foreground">
                {{
                    localFilter === 'pending'
                        ? 'Nenhuma tarefa pendente'
                        : localFilter === 'completed'
                          ? 'Nenhuma tarefa concluída'
                          : 'Lixeira vazia'
                }}
            </h3>
            <p class="mt-2 text-sm text-muted-foreground">
                {{
                    localFilter === 'pending'
                        ? 'Crie sua primeira tarefa acima para começar.'
                        : localFilter === 'completed'
                          ? 'Complete uma tarefa para vê-la aqui.'
                          : 'Tarefas excluídas aparecerão aqui.'
                }}
            </p>
        </div>

        <template v-else-if="localFilter === 'pending'">
            <div v-if="notStartedTasks.length > 0" class="space-y-2">
                <h2
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Não iniciadas
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="task in notStartedTasks"
                        :key="task.id"
                        class="group cursor-pointer"
                        @click="openTaskSheet(task)"
                    >
                        <div
                            class="flex items-center gap-3 rounded-lg border bg-card px-4 py-3 transition-all duration-1000 hover:shadow-md"
                            :class="[
                                {
                                    '-translate-x-4 scale-95 opacity-0':
                                        fadingTaskIds.has(task.id),
                                },
                            ]"
                        >
                            <button
                                type="button"
                                class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200"
                                :class="
                                    task.completed_at
                                        ? 'border-emerald-500 bg-emerald-500 text-white dark:border-emerald-400 dark:bg-emerald-400'
                                        : 'border-muted-foreground/30 hover:border-primary hover:bg-primary/5'
                                "
                                @click.stop="completeTask(task, $event)"
                            >
                                <svg
                                    v-if="task.completed_at"
                                    class="size-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="3"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 12.75l6 6 9-13.5"
                                    />
                                </svg>
                            </button>

                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <span
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ task.title }}
                                </span>

                                <p
                                    v-if="task.description"
                                    class="line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ task.description }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="task.project"
                                        class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-400"
                                    >
                                        <Folder class="size-2.5" />
                                        {{ task.project.name }}
                                    </span>

                                    <span
                                        v-if="task.due_date"
                                        class="inline-flex items-center gap-1 rounded-md border bg-background/80 px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                    >
                                        <CalendarDays class="size-2.5" />
                                        {{ formatDate(task.due_date) }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="[
                                            getPriorityColor(task.priority)
                                                .badge,
                                            getPriorityColor(task.priority)
                                                .border,
                                        ]"
                                    >
                                        <Flag class="size-2.5" />
                                        {{ getPriorityLabel(task.priority) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-amber-500/10 hover:text-amber-500"
                                    @click.stop="startTask(task, $event)"
                                >
                                    <CirclePlay class="size-4" />
                                </button>

                                <Link
                                    :href="`/tasks/${task.id}`"
                                    method="delete"
                                    as="button"
                                    class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-destructive/10 hover:text-destructive"
                                    @click.stop
                                >
                                    <Trash2 class="size-4" />
                                </Link>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-if="inProgressTasks.length > 0" class="mt-6 space-y-2">
                <h2
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Iniciadas
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="task in inProgressTasks"
                        :key="task.id"
                        class="group cursor-pointer"
                        @click="openTaskSheet(task)"
                    >
                        <div
                            class="flex items-center gap-3 rounded-lg border bg-card px-4 py-3 transition-all duration-1000 hover:shadow-md"
                            :class="[
                                {
                                    '-translate-x-4 scale-95 opacity-0':
                                        fadingTaskIds.has(task.id),
                                },
                            ]"
                        >
                            <button
                                type="button"
                                class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200"
                                :class="
                                    task.completed_at
                                        ? 'border-emerald-500 bg-emerald-500 text-white dark:border-emerald-400 dark:bg-emerald-400'
                                        : 'border-muted-foreground/30 hover:border-primary hover:bg-primary/5'
                                "
                                @click.stop="completeTask(task, $event)"
                            >
                                <svg
                                    v-if="task.completed_at"
                                    class="size-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="3"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 12.75l6 6 9-13.5"
                                    />
                                </svg>
                            </button>

                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <span
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ task.title }}
                                </span>

                                <p
                                    v-if="task.description"
                                    class="line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ task.description }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="task.project"
                                        class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-400"
                                    >
                                        <Folder class="size-2.5" />
                                        {{ task.project.name }}
                                    </span>

                                    <span
                                        v-if="task.due_date"
                                        class="inline-flex items-center gap-1 rounded-md border bg-background/80 px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                    >
                                        <CalendarDays class="size-2.5" />
                                        {{ formatDate(task.due_date) }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="[
                                            getPriorityColor(task.priority)
                                                .badge,
                                            getPriorityColor(task.priority)
                                                .border,
                                        ]"
                                    >
                                        <Flag class="size-2.5" />
                                        {{ getPriorityLabel(task.priority) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <Link
                                    :href="`/tasks/${task.id}`"
                                    method="delete"
                                    as="button"
                                    class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-destructive/10 hover:text-destructive"
                                    @click.stop
                                >
                                    <Trash2 class="size-4" />
                                </Link>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </template>

        <ul v-else class="space-y-2">
            <li
                v-for="task in localTasks"
                :key="task.id"
                class="group cursor-pointer"
                @click="openTaskSheet(task)"
            >
                <div
                    class="flex items-center gap-3 rounded-lg border bg-card px-4 py-3 transition-all duration-1000 hover:shadow-md"
                    :class="[
                        {
                            '-translate-x-4 scale-95 opacity-0':
                                fadingTaskIds.has(task.id),
                        },
                        task.completed_at ? 'opacity-75' : '',
                    ]"
                >
                    <button
                        v-if="localFilter !== 'trash'"
                        type="button"
                        class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200"
                        :class="
                            task.completed_at
                                ? 'border-emerald-500 bg-emerald-500 text-white dark:border-emerald-400 dark:bg-emerald-400'
                                : 'border-muted-foreground/30 hover:border-primary hover:bg-primary/5'
                        "
                        @click.stop="completeTask(task, $event)"
                    >
                        <svg
                            v-if="task.completed_at"
                            class="size-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4.5 12.75l6 6 9-13.5"
                            />
                        </svg>
                    </button>

                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <span
                            class="truncate text-sm font-medium"
                            :class="
                                task.completed_at
                                    ? 'text-muted-foreground line-through'
                                    : 'text-foreground'
                            "
                        >
                            {{ task.title }}
                        </span>

                        <p
                            v-if="task.description"
                            class="line-clamp-2 text-xs text-muted-foreground"
                        >
                            {{ task.description }}
                        </p>

                        <div class="flex items-center gap-2">
                            <span
                                v-if="task.project"
                                class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-400"
                            >
                                <Folder class="size-2.5" />
                                {{ task.project.name }}
                            </span>

                            <span
                                v-if="task.due_date"
                                class="inline-flex items-center gap-1 rounded-md border bg-background/80 px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                            >
                                <CalendarDays class="size-2.5" />
                                {{ formatDate(task.due_date) }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                :class="[
                                    getPriorityColor(task.priority).badge,
                                    getPriorityColor(task.priority).border,
                                ]"
                            >
                                <Flag class="size-2.5" />
                                {{ getPriorityLabel(task.priority) }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="localFilter === 'trash'"
                        class="flex shrink-0 items-center gap-1"
                    >
                        <button
                            type="button"
                            class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-emerald-500/10 hover:text-emerald-500"
                            @click.stop="restoreTask(task, $event)"
                        >
                            <RotateCcw class="size-4" />
                        </button>

                        <button
                            type="button"
                            class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-destructive/10 hover:text-destructive"
                            @click.stop="forceDeleteTask(task, $event)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                    <Link
                        v-else
                        :href="`/tasks/${task.id}`"
                        method="delete"
                        as="button"
                        class="hidden rounded-md p-1.5 text-muted-foreground transition-all duration-200 group-hover:inline-flex hover:bg-destructive/10 hover:text-destructive"
                        @click.stop
                    >
                        <Trash2 class="size-4" />
                    </Link>
                </div>
            </li>
        </ul>

        <div
            v-if="localPaginator.last_page > 1"
            class="flex items-center justify-between gap-4"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ localPaginator.from }} a {{ localPaginator.to }} de
                {{ localPaginator.total }} resultados
            </p>

            <nav class="inline-flex items-center gap-1">
                <button
                    type="button"
                    @click="goToPage(localPaginator.prev_page_url)"
                    :disabled="!localPaginator.prev_page_url"
                    class="inline-flex size-8 items-center justify-center rounded-md border bg-background text-sm transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                >
                    <ChevronLeft class="size-4" />
                </button>

                <template
                    v-for="(link, index) in localPaginator.links"
                    :key="index"
                >
                    <button
                        v-if="
                            link.url &&
                            !link.label.includes('Previous') &&
                            !link.label.includes('Next')
                        "
                        type="button"
                        @click="goToPage(link.url)"
                        class="inline-flex size-8 items-center justify-center rounded-md border text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground hover:bg-primary/90'
                                : 'bg-background hover:bg-muted'
                        "
                    >
                        {{
                            link.label
                                .replace('&laquo;', '«')
                                .replace('&raquo;', '»')
                        }}
                    </button>
                </template>

                <button
                    type="button"
                    @click="goToPage(localPaginator.next_page_url)"
                    :disabled="!localPaginator.next_page_url"
                    class="inline-flex size-8 items-center justify-center rounded-md border bg-background text-sm transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                >
                    <ChevronRight class="size-4" />
                </button>
            </nav>
        </div>

        <TaskDetailSheet
            :task="selectedTask"
            :open="sheetOpen"
            @update:open="sheetOpen = $event"
        />
    </div>
</template>
