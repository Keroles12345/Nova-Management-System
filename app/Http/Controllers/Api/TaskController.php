<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);
        $tasks = Task::query()
            ->when(! in_array($request->user()->role, ['admin', 'manager'], true), fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = $request->user()->tasks()->create($data);

        return response()->json(['data' => $task], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        return response()->json(['data' => $task->load('user:id,name')]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);
        $task->update($data);

        return response()->json(['data' => $task->fresh()]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);
        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    private function authorizeTask(Request $request, Task $task): void
    {
        abort_unless(
            $task->user_id === $request->user()->id
                || in_array($request->user()->role, ['admin', 'manager'], true),
            403,
        );
    }
}
