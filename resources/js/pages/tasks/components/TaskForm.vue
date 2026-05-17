<script setup lang="ts">
import { Form, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DEFAULT_PROJECT_COLOR,
    getProjectColorStyles,
    PROJECT_COLOR_OPTIONS,
} from '@/lib/project-colors';
import type { ProjectColorKey } from '@/lib/project-colors';
import type { Project } from '@/types';

const props = defineProps<{
    processing?: boolean;
    projects?: Project[];
    defaultProjectId?: string;
}>();

const emits = defineEmits<{
    submit: [
        formData: {
            title: string;
            due_date: string;
            priority: string;
            project_id: string;
        },
    ];
    cancel: [];
}>();

const projectDialogOpen = ref(false);
const selectedProjectId = ref<string>(props.defaultProjectId ?? '');

watch(
    () => props.defaultProjectId,
    (newId) => {
        selectedProjectId.value = newId ?? '';
    },
);

const projectForm = useForm({
    name: '',
    color: DEFAULT_PROJECT_COLOR as ProjectColorKey,
});

function closeProjectDialog(): void {
    projectDialogOpen.value = false;
    projectForm.reset();
    projectForm.color = DEFAULT_PROJECT_COLOR;
}

function createProject(): void {
    const createdProjectName = projectForm.name.trim();

    projectForm.post('/projects', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            router.reload({
                only: ['projects'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: (page) => {
                    const latestProject = [...((page.props.projects as Project[]) ?? [])]
                        .reverse()
                        .find((project) => project.name === createdProjectName);

                    if (latestProject) {
                        selectedProjectId.value = String(latestProject.id);
                    }
                },
            });

            closeProjectDialog();
        },
    });
}
</script>

<template>
    <Form
        action="/tasks"
        method="post"
        reset-on-success
        v-slot="{ errors, processing: formProcessing, data }"
        @success="
            emits(
                'submit',
                data as {
                    title: string;
                    due_date: string;
                    priority: string;
                    project_id: string;
                },
            )
        "
    >
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm leading-none font-medium text-foreground">
                    Título da Tarefa
                </label>
                <Input
                    type="text"
                    name="title"
                    placeholder="O que precisa ser feito?"
                    class="w-full"
                    required
                />
                <InputError :message="errors.title" />
            </div>

            <div class="space-y-2">
                <label class="text-sm leading-none font-medium text-foreground">
                    Descrição
                </label>
                <textarea
                    name="description"
                    placeholder="Adicione detalhes sobre a tarefa (opcional)"
                    rows="3"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                ></textarea>
                <InputError :message="errors.description" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <label class="text-sm leading-none font-medium text-foreground">
                        Projeto
                    </label>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        @click="projectDialogOpen = true"
                    >
                        <Plus class="size-3.5" />
                        Criar projeto
                    </Button>
                </div>
                <select
                    v-model="selectedProjectId"
                    name="project_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">Sem projeto</option>
                    <option
                        v-for="project in props.projects ?? []"
                        :key="project.id"
                        :value="String(project.id)"
                    >
                        {{ project.name }}
                    </option>
                </select>
                <InputError :message="errors.project_id" />
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1 space-y-2">
                    <label
                        class="text-sm leading-none font-medium text-foreground"
                    >
                        Data de Vencimento
                    </label>
                    <Input type="date" name="due_date" class="w-full" />
                </div>

                <div class="flex-1 space-y-2">
                    <label
                        class="text-sm leading-none font-medium text-foreground"
                    >
                        Prioridade
                    </label>
                    <select
                        name="priority"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="low">Baixa</option>
                        <option value="medium" selected>Média</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="emits('cancel')"
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    :disabled="props.processing || formProcessing"
                >
                    <Plus class="size-4" />
                    <span>{{
                        props.processing || formProcessing
                            ? 'Adicionando...'
                            : 'Adicionar Tarefa'
                    }}</span>
                </Button>
            </div>
        </div>
    </Form>

    <Dialog v-model:open="projectDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Novo Projeto</DialogTitle>
                <DialogDescription>
                    Crie um novo projeto para agrupar suas tarefas.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="createProject">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Nome do Projeto</label>
                    <Input
                        v-model="projectForm.name"
                        type="text"
                        placeholder="Ex: Marketing Digital"
                        required
                    />
                    <InputError :message="projectForm.errors.name" />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Cor</label>
                    <Select v-model="projectForm.color">
                        <SelectTrigger class="w-full">
                            <div class="flex items-center gap-2">
                                <ProjectColorDot
                                    :dot-class="getProjectColorStyles(projectForm.color).dot"
                                />
                                <SelectValue
                                    :placeholder="getProjectColorStyles(projectForm.color).label"
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
                                    <ProjectColorDot :dot-class="option.dot" />
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
                        @click="closeProjectDialog"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="projectForm.processing">
                        Criar Projeto
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
