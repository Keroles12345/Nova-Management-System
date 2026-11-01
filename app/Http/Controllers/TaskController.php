<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $request->user()->tasks()->create($data);

        return redirect()->route('dashboard', ['tab' => 'tasks'])->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($request, $task);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);
        $task->update($data);

        return redirect()->route('dashboard', ['tab' => 'tasks'])->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($request, $task);
        $task->delete();

        return redirect()->route('dashboard', ['tab' => 'tasks'])->with('success', 'Task deleted successfully.');
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
