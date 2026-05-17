<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    List,
    ListOrdered,
    Link as LinkIcon,
    Unlink,
    Strikethrough,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
    modelValue: string | null;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const editor = useEditor({
    content: props.modelValue ?? '',
    extensions: [
        StarterKit.configure({
            heading: false,
            codeBlock: false,
            blockquote: false,
            horizontalRule: false,
        }),
        Underline,
        Link.configure({
            openOnClick: false,
        }),
        Placeholder.configure({
            placeholder: props.placeholder ?? 'Adicione detalhes sobre a tarefa (opcional)',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert max-w-none focus:outline-none min-h-[120px] px-3 py-2 text-sm',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        emit('update:modelValue', html === '<p></p>' ? null : html);
    },
});

const isActive = computed(() => editor.value?.isActive.bind(editor.value));

watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value) {
            const current = editor.value.getHTML();
            if (current !== (newValue ?? '')) {
                editor.value.commands.setContent(newValue ?? '');
            }
        }
    },
);

function setLink(): void {
    if (!editor.value) {
        return;
    }

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('URL do link', previousUrl);

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div class="flex w-full flex-col rounded-md border border-input bg-background shadow-sm transition-colors focus-within:ring-1 focus-within:ring-ring">
        <div
            v-if="editor"
            class="flex items-center gap-0.5 border-b border-input bg-muted/50 px-2 py-1.5"
        >
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('bold') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-3.5" />
            </button>
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('italic') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-3.5" />
            </button>
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('underline') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleUnderline().run()"
            >
                <UnderlineIcon class="size-3.5" />
            </button>
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('strike') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <Strikethrough class="size-3.5" />
            </button>

            <div class="mx-1 h-4 w-px bg-border" />

            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('bulletList') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-3.5" />
            </button>
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('orderedList') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-3.5" />
            </button>

            <div class="mx-1 h-4 w-px bg-border" />

            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="
                    isActive?.('link') ? 'bg-accent text-accent-foreground' : ''
                "
                @click="setLink"
            >
                <LinkIcon class="size-3.5" />
            </button>
            <button
                type="button"
                class="inline-flex size-7 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :disabled="!isActive?.('link')"
                @click="
                    editor.chain().focus().extendMarkRange('link').unsetLink().run()
                "
            >
                <Unlink class="size-3.5" />
            </button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: hsl(var(--muted-foreground));
    pointer-events: none;
    height: 0;
}

.tiptap ul,
.tiptap ol {
    padding-left: 1.5rem;
    margin: 0.5rem 0;
}

.tiptap ul {
    list-style-type: disc;
}

.tiptap ol {
    list-style-type: decimal;
}

.tiptap li {
    margin: 0.25rem 0;
}

.tiptap a {
    color: hsl(var(--primary));
    text-decoration: underline;
    cursor: pointer;
}

.tiptap strong {
    font-weight: 600;
}

.tiptap em {
    font-style: italic;
}

.tiptap u {
    text-decoration: underline;
}

.tiptap s {
    text-decoration: line-through;
}
</style>
