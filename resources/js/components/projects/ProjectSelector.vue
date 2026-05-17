<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Folder, Plus } from 'lucide-vue-next';
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

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        projects: Project[];
        placeholder?: string;
        showCreateButton?: boolean;
        triggerClass?: string;
    }>(),
    {
        modelValue: '',
        placeholder: 'Selecionar projeto',
        showCreateButton: true,
        triggerClass: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    projectCreated: [];
}>();

const dialogOpen = ref(false);

const selectValue = computed(() => props.modelValue || 'none');

const selectedProject = computed(
    () => props.projects.find((p) => String(p.id) === props.modelValue) ?? null,
);

const defaultTriggerClass =
    'h-10 w-full cursor-pointer rounded-full border bg-transparent px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50';

const triggerClass = computed(() => {
    if (props.triggerClass) {
        return props.triggerClass;
    }

    if (selectedProject.value) {
        const styles = getProjectColorStyles(selectedProject.value.color);

        return [
            defaultTriggerClass,
            styles.trigger
                .replace('focus-visible:ring-ring/20', 'focus-visible:ring-ring')
                .replace('data-[state=open]:', ''),
        ];
    }

    return [defaultTriggerClass, 'border-input bg-background'];
});

const projectForm = useForm({
    name: '',
    color: DEFAULT_PROJECT_COLOR as ProjectColorKey,
});

function handleSelect(value: string) {
    emit('update:modelValue', value === 'none' ? '' : value);
}

function createProject() {
    projectForm.post('/projects', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            dialogOpen.value = false;
            projectForm.reset();
            projectForm.color = DEFAULT_PROJECT_COLOR;
            emit('projectCreated');
        },
    });
}
</script>

<template>
    <div>
        <Select :model-value="selectValue" @update:model-value="handleSelect">
            <SelectTrigger :class="triggerClass">
                <div class="flex items-center gap-2">
                    <ProjectColorDot
                        v-if="selectedProject"
                        :dot-class="getProjectColorStyles(selectedProject.color).dot"
                    />
                    <Folder
                        v-else
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <SelectValue :placeholder="placeholder" />
                </div>
            </SelectTrigger>

            <SelectContent class="z-[60]">
                <SelectGroup class="max-h-[300px] overflow-y-auto">
                    <SelectItem value="none">
                        <div class="flex items-center gap-2">
                            <Folder class="size-4 shrink-0 text-muted-foreground" />
                            <span>Sem projeto</span>
                        </div>
                    </SelectItem>

                    <SelectItem
                        v-for="project in projects"
                        :key="project.id"
                        :value="String(project.id)"
                    >
                        <div class="flex items-center gap-2">
                            <ProjectColorDot
                                :dot-class="getProjectColorStyles(project.color).dot"
                            />
                            <span>{{ project.name }}</span>
                        </div>
                    </SelectItem>
                </SelectGroup>

                <div v-if="showCreateButton" class="border-t px-2 py-2">
                    <button
                        type="button"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-md py-2 text-sm font-medium text-blue-700 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                        @click.prevent.stop="dialogOpen = true"
                    >
                        <Plus class="size-4" />
                        Criar novo projeto
                    </button>
                </div>
            </SelectContent>
        </Select>

        <Dialog v-model:open="dialogOpen">
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
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Cor</label>
                        <Select v-model="projectForm.color">
                            <SelectTrigger class="w-full">
                                <div class="flex items-center gap-2">
                                    <ProjectColorDot
                                        :dot-class="
                                            getProjectColorStyles(projectForm.color).dot
                                        "
                                    />
                                    <SelectValue
                                        :placeholder="
                                            getProjectColorStyles(projectForm.color).label
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
                                        <ProjectColorDot :dot-class="option.dot" />
                                        <span>{{ option.label }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="dialogOpen = false">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="projectForm.processing">
                            Criar Projeto
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
