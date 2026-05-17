<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Folder, Plus, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProjectColorDot from '@/components/projects/ProjectColorDot.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ALL_PROJECTS_STYLES,
    DEFAULT_PROJECT_COLOR,
    getProjectColorStyles,
    PROJECT_COLOR_OPTIONS
    
} from '@/lib/project-colors';
import type {ProjectColorKey} from '@/lib/project-colors';
import type { Project } from '@/types';

const props = defineProps<{
    modelValue: string | null;
    projects: Project[];
    totalTaskCount: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const dialogProjectOpen = ref(false);
const editDialogOpen = ref(false);
const editingProject = ref<Project | null>(null);
const projectSelectOpen = ref(false);

const projectForm = useForm({
    name: '',
    color: DEFAULT_PROJECT_COLOR as ProjectColorKey,
});

const editForm = useForm({
    name: '',
});

const selectedProject = computed(
    () => props.projects.find((p) => p.ulid === props.modelValue) ?? null,
);

const selectedProjectColorStyles = computed(() =>
    selectedProject.value
        ? getProjectColorStyles(selectedProject.value.color)
        : ALL_PROJECTS_STYLES,
);

const projectSelectTriggerClass = computed(() =>
    selectedProject.value
        ? getProjectColorStyles(selectedProject.value.color).trigger
        : ALL_PROJECTS_STYLES.trigger,
);

function handleSelectProject(ulid: string) {
    emit('update:modelValue', ulid === 'all' ? null : ulid);
}

function createProject() {
    projectForm.post('/projects', {
        preserveScroll: true,
        onSuccess: () => {
            dialogProjectOpen.value = false;
            projectForm.reset();
            projectForm.color = DEFAULT_PROJECT_COLOR;
        },
    });
}

function openEditDialog(project: Project, event: Event) {
    event.preventDefault();
    event.stopPropagation();
    editingProject.value = project;
    editForm.name = project.name;
    editDialogOpen.value = true;
}

function updateProject() {
    if (!editingProject.value) return;

    editForm.put(`/projects/${editingProject.value.ulid}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editingProject.value = null;
            editForm.reset();
        },
    });
}
</script>

<template>
    <div class="flex items-center self-start gap-x-4">
        <Select
            :model-value="modelValue ?? 'all'"
            :open="projectSelectOpen"
            @update:open="projectSelectOpen = $event"
            @update:model-value="handleSelectProject"
        >
            <SelectTrigger
                class="inline-flex h-9 w-auto max-w-[280px] cursor-pointer rounded-full border bg-transparent px-3 py-2 text-sm font-medium shadow-sm transition-colors outline-none focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50"
                :class="projectSelectTriggerClass"
            >
                <div class="flex max-w-full items-center gap-2 overflow-hidden">
                    <ProjectColorDot
                        v-if="selectedProject"
                        :dot-class="selectedProjectColorStyles.dot"
                    />
                    <Folder
                        v-else
                        class="size-4 shrink-0"
                        :class="ALL_PROJECTS_STYLES.icon"
                    />
                    <span class="block truncate">
                        {{
                            selectedProject
                                ? selectedProject.name
                                : 'Todos os projetos'
                        }}
                    </span>
                </div>
            </SelectTrigger>

            <SelectContent class="w-[320px] rounded-xl p-0" :side-offset="8">
                <SelectGroup class="max-h-[300px] overflow-y-auto py-1">
                    <SelectItem
                        value="all"
                        class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors focus:bg-accent focus:text-accent-foreground"
                        :class="ALL_PROJECTS_STYLES.itemChecked"
                    >
                        <div class="flex w-[260px] items-center gap-3">
                            <Folder
                                class="size-4 shrink-0"
                                :class="
                                    !selectedProject
                                        ? ALL_PROJECTS_STYLES.icon
                                        : 'text-muted-foreground'
                                "
                            />
                            <span
                                class="flex-1 truncate text-sm font-medium"
                                :class="
                                    !selectedProject
                                        ? 'font-medium text-foreground'
                                        : 'text-foreground'
                                "
                            >
                                Todos os projetos
                            </span>
                            <span
                                class="ml-auto inline-flex shrink-0 items-center justify-center rounded-md px-2 py-0.5 text-xs font-medium"
                                :class="
                                    !selectedProject
                                        ? ALL_PROJECTS_STYLES.badge
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ totalTaskCount }}
                                {{
                                    totalTaskCount === 1 ? 'tarefa' : 'tarefas'
                                }}
                            </span>
                        </div>
                    </SelectItem>

                    <SelectItem
                        v-for="project in projects"
                        :key="project.ulid"
                        :value="project.ulid"
                        class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors focus:bg-accent focus:text-accent-foreground"
                        :class="
                            getProjectColorStyles(project.color).itemChecked
                        "
                    >
                        <div class="flex w-[260px] items-center gap-3">
                            <ProjectColorDot
                                :dot-class="
                                    getProjectColorStyles(project.color).dot
                                "
                            />
                            <span
                                class="flex-1 truncate text-sm font-medium"
                                :class="
                                    selectedProject?.ulid === project.ulid
                                        ? getProjectColorStyles(project.color)
                                              .count
                                        : 'text-foreground'
                                "
                            >
                                {{ project.name }}
                            </span>
                            <span
                                v-if="project.tasks_count !== undefined"
                                class="ml-auto inline-flex flex-shrink-0 items-center justify-center rounded-md px-2 py-0.5 text-xs font-medium"
                                :class="
                                    selectedProject?.ulid === project.ulid
                                        ? getProjectColorStyles(project.color)
                                              .badge
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ project.tasks_count }}
                                {{
                                    project.tasks_count === 1
                                        ? 'tarefa'
                                        : 'tarefas'
                                }}
                            </span>
                        </div>
                    </SelectItem>
                </SelectGroup>

                <SelectSeparator class="m-0" />
                <div class="w-full px-2 py-2">
                    <button
                        type="button"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-md py-2 text-sm font-medium text-blue-700 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                        @click.prevent.stop="projectSelectOpen = false; dialogProjectOpen = true"
                    >
                        <Plus class="size-4" />
                        Criar novo projeto
                    </button>
                </div>
            </SelectContent>
        </Select>

        <Dialog v-model:open="dialogProjectOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Novo Projeto</DialogTitle>
                    <DialogDescription>
                        Crie um novo projeto para agrupar suas tarefas.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="createProject">
                    <div class="space-y-2">
                        <label class="text-sm font-medium"
                            >Nome do Projeto</label
                        >
                        <Input
                            v-model="projectForm.name"
                            type="text"
                            placeholder="Ex: Marketing Digital"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Cor</label>
                        <Select v-model="projectForm.color">
                            <SelectTrigger class="w-full">
                                <div class="flex items-center gap-2">
                                    <ProjectColorDot
                                        :dot-class="
                                            getProjectColorStyles(
                                                projectForm.color,
                                            ).dot
                                        "
                                    />
                                    <SelectValue
                                        :placeholder="
                                            getProjectColorStyles(
                                                projectForm.color,
                                            ).label
                                        "
                                    />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in PROJECT_COLOR_OPTIONS"
                                    :key="option.key"
                                    :value="option.key"
                                >
                                    <div class="flex items-center gap-2">
                                        <ProjectColorDot
                                            :dot-class="option.dot"
                                        />
                                        <span>{{ option.label }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="dialogProjectOpen = false"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="projectForm.processing"
                        >
                            Criar Projeto
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Projeto</DialogTitle>
                    <DialogDescription>
                        Altere o nome do projeto.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="updateProject">
                    <div class="space-y-2">
                        <label class="text-sm font-medium"
                            >Nome do Projeto</label
                        >
                        <Input
                            v-model="editForm.name"
                            type="text"
                            placeholder="Ex: Marketing Digital"
                            required
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="editDialogOpen = false"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                        >
                            Salvar
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <button
            v-if="selectedProject"
            type="button"
            class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full border bg-white px-3 py-2 text-sm font-medium shadow-sm transition-colors outline-none hover:bg-muted"
            @click.stop="openEditDialog(selectedProject, $event)"
        >
            <Pencil class="size-4" />
            Editar projeto
        </button>
    </div>
</template>
