<?php

namespace App\Http\Controllers\projects;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('content.projects.index', [
            'projects' => Project::withCount('domains')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('content.projects.create', [
            'statuses' => ProjectStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProject($request);

        $project = Project::create($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Proyecto creado.');
    }

    public function show(Project $project): View
    {
        $project->load('domains');

        return view('content.projects.show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): View
    {
        return view('content.projects.edit', [
            'project' => $project,
            'statuses' => ProjectStatus::cases(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateProject($request);

        $project->update($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->domains()->exists()) {
            return redirect()->route('projects.index')
                ->with('error', 'No se puede eliminar: hay dominios vinculados a este proyecto.');
        }

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProject(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'in:' . implode(',', array_column(ProjectStatus::cases(), 'value'))],
        ]);
    }
}
