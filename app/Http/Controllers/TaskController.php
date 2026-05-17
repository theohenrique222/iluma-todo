<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $filter = request()->query('filter', 'pending');
        $projectUlid = request()->query('project');

        $baseQuery = auth()->user()->tasks();

        if ($projectUlid) {
            $project = auth()->user()->projects()->where('ulid', $projectUlid)->first();
            if ($project) {
                $baseQuery->where('project_id', $project->id);
            }
        }

        $counts = [
            'pending' => (clone $baseQuery)->whereNull('completed_at')->count(),
            'completed' => (clone $baseQuery)->whereNotNull('completed_at')->count(),
            'trash' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        $query = (clone $baseQuery)->with(['user', 'project'])->latest();

        if ($filter === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($filter === 'trash') {
            $query->onlyTrashed();
        } else {
            $query->whereNull('completed_at');
        }

        $tasks = $query->paginate(10);

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'filter' => $filter,
            'selectedProjectUlid' => $projectUlid,
            'counts' => $counts,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        auth()->user()->tasks()->create($request->validated());

        return back();
    }

    public function update(Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $wasCompleted = $task->isCompleted();

        $task->update([
            'completed_at' => $wasCompleted ? null : now(),
            'status' => $wasCompleted ? TaskStatus::Pending : TaskStatus::Completed,
            'started_at' => $wasCompleted ? null : $task->started_at,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasCompleted ? 'Tarefa reaberta com sucesso!' : 'Tarefa concluída com sucesso!',
        ]);

        return back();
    }

    public function start(Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        if ($task->isCompleted()) {
            return back();
        }

        $task->markAsStarted();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarefa iniciada com sucesso!',
        ]);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $task->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarefa excluída com sucesso!',
        ]);

        return back();
    }

    public function restore(int $task): RedirectResponse
    {
        $task = Task::withTrashed()->findOrFail($task);

        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $task->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarefa restaurada com sucesso!',
        ]);

        return back();
    }

    public function forceDestroy(int $task): RedirectResponse
    {
        $task = Task::withTrashed()->findOrFail($task);

        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $task->forceDelete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarefa excluída permanentemente!',
        ]);

        return back();
    }

    public function updateTitle(Request $request, Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $task->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Título atualizado com sucesso!',
        ]);

        return back();
    }

    public function updateDueDate(Request $request, Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $validated['due_date'] ? 'Data definida com sucesso!' : 'Data removida com sucesso!',
        ]);

        return back();
    }

    public function updatePriority(Request $request, Task $task): RedirectResponse
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'priority' => ['required', 'string', 'in:'.implode(',', array_map(fn (TaskPriority $p) => $p->value, TaskPriority::cases()))],
        ]);

        $task->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Prioridade atualizada com sucesso!',
        ]);

        return back();
    }
}
