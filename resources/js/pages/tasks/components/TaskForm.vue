<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import ProjectSelector from '@/components/projects/ProjectSelector.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
            description: string | null;
            due_date: string;
            priority: string;
            project_id: string;
        },
    ];
    cancel: [];
}>();

const selectedProjectId = ref<string>(props.defaultProjectId ?? '');
const description = ref<string | null>(null);

watch(
    () => props.defaultProjectId,
    (newId) => {
        selectedProjectId.value = newId ?? '';
    },
);

function onProjectCreated(): void {
    router.reload({
        only: ['projects'],
        preserveScroll: true,
        preserveState: true,
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
                {
                    title: data.title,
                    description: description.value,
                    due_date: data.due_date,
                    priority: data.priority,
                    project_id: data.project_id,
                } as {
                    title: string;
                    description: string | null;
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
                <RichTextEditor
                    v-model="description"
                    placeholder="Adicione detalhes sobre a tarefa (opcional)"
                />
                <input type="hidden" name="description" :value="description" />
                <InputError :message="errors.description" />
            </div>

            <div class="space-y-2">
                <label class="text-sm leading-none font-medium text-foreground">
                    Projeto
                </label>
                <div>
                    <ProjectSelector
                        v-model="selectedProjectId"
                        :projects="props.projects ?? []"
                        placeholder="Sem projeto"
                        @project-created="onProjectCreated"
                    />
                    <input type="hidden" name="project_id" :value="selectedProjectId" />
                </div>
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
</template>
