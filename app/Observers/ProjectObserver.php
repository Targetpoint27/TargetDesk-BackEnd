<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ProjectHistory;
use App\Jobs\ProjectCreatedJob;
use App\Jobs\ProjectStatusChangedJob;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function created(Project $project)
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'action_type' => 'created',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => json_encode($project->toArray()),
            'comment' => 'Projet créé',
            'changed_by' => Auth::id()
        ]);

        // if (Auth::check()) {
        //     ProjectCreatedJob::dispatch($project, Auth::user());
        // }
    }

    public function updated(Project $project)
    {
        $changes = $project->getChanges();

        if (!empty($changes)) {
            $description = $this->generateUpdateDescription($changes);

            ProjectHistory::create([
                'project_id' => $project->id,
                'action_type' => 'updated',
                'field_changed' => implode(',', array_keys($changes)),
                'old_value' => null,
                'new_value' => json_encode($changes),
                'comment' => $description,
                'changed_by' => Auth::id()
            ]);
        }
    }

    public function deleting(Project $project)
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'action_type' => 'deleted',
            'field_changed' => null,
            'old_value' => json_encode($project->toArray()),
            'new_value' => null,
            'comment' => 'Projet supprimé',
            'changed_by' => Auth::id()
        ]);
    }

    private function generateUpdateDescription(array $changes): string
    {
        $descriptions = [];

        $fieldLabels = [
            'name' => 'nom',
            'status' => 'statut',
            'progress_percentage' => 'progression',
            'project_manager_id' => 'chef de projet',
            'planned_end_date' => 'date de fin prévue',
            'estimated_budget' => 'budget estimé',
            'risk_indicator' => 'indicateur de risque',
            'client_id' => 'client',
            'client_type' => 'type de client'
        ];

        foreach ($changes as $field => $value) {
            if (isset($fieldLabels[$field])) {
                $descriptions[] = "Modification du {$fieldLabels[$field]}";
            }
        }

        return empty($descriptions) ? 'Projet modifié' : implode(', ', $descriptions);
    }
}
