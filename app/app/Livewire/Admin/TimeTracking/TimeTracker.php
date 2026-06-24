<?php

namespace App\Livewire\Admin\TimeTracking;

use App\Enums\TimeEntryType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TimeTracker extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public Task $task;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $entryForm = null;

    public function mount(Project $project, Task $task): void
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $this->project = $project;
        $this->task = $task;

        $this->authorize('view', $task);
    }

    public function openManualEntryForm(): void
    {
        $this->authorize('create', [TimeEntry::class, $this->project]);

        $this->entryForm = [
            'mode' => 'create',
            'entry_id' => null,
            'description' => '',
            'minutes' => null,
            'billed' => false,
        ];
        $this->resetErrorBag();
    }

    public function openEditForm(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('update', $entry);

        $this->entryForm = [
            'mode' => 'edit',
            'entry_id' => $entry->id,
            'description' => $entry->description ?? '',
            'minutes' => $entry->minutes,
            'billed' => $entry->isBillable(),
        ];
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->entryForm = null;
        $this->resetErrorBag();
    }

    public function saveManualEntry(): void
    {
        if ($this->entryForm === null) {
            return;
        }

        $isEdit = ($this->entryForm['mode'] ?? null) === 'edit';
        $rules = [
            'entryForm.description' => ['nullable', 'string', 'max:5000'],
            'entryForm.minutes' => ['required', 'integer', 'min:1', 'max:60000'],
            'entryForm.billed' => ['nullable', 'boolean'],
        ];
        $this->validate($rules);

        $service = app(TimeTrackingService::class);
        $payload = [
            'description' => $this->entryForm['description'] ?? null,
            'minutes' => (int) $this->entryForm['minutes'],
            'billed' => (bool) ($this->entryForm['billed'] ?? false),
        ];

        if ($isEdit) {
            $entry = TimeEntry::find($this->entryForm['entry_id']);
            if ($entry === null) {
                $this->closeForm();

                return;
            }
            $this->authorize('update', $entry);
            $service->updateEntry($entry, $payload);
        } else {
            $this->authorize('create', [TimeEntry::class, $this->project]);
            $payload['type'] = TimeEntryType::Manual->value;
            $payload['entry_date'] = Carbon::now()->toDateString();
            $service->createManualEntry($this->task, Auth::user(), $payload);
        }

        $this->closeForm();
        $this->dispatch('time-tracker-updated');
    }

    public function deleteEntry(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('delete', $entry);
        $entry->delete();
        $this->dispatch('time-tracker-updated');
    }

    public function toggleBilled(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('update', $entry);

        if ($entry->isBillable()) {
            $entry->markAsUnbilled();
        } else {
            $entry->markAsBilled();
        }
        $this->dispatch('time-tracker-updated');
    }

    #[Computed]
    public function entries()
    {
        return $this->task->timeEntries()->with('user')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.time-tracking.time-tracker', [
            'entries' => $this->entries,
        ]);
    }
}
