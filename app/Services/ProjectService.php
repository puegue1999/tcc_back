<?php

namespace App\Services;

use App\Helpers\Help;
use App\Models\Project;

class ProjectService
{
    public function generateExtId($project)
    {
        if (!isset($project->external_id)) {
            $external_id = Help::generateExtId('PROJECT');

            $cleanExtenalId = $this->removeSpecialCharactersFromExternalId($external_id);

            $project->external_id = $cleanExtenalId;
        } else {
            $project->external_id = $this->removeSpecialCharactersFromExternalId($project->external_id);
        }
    }

    public function removeSpecialCharactersFromExternalId($external_id)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $external_id);
    }

    public function getProject($request)
    {
        return Project::where('external_id', $request)->first();
    }

    public function getTime($request)
    {
        return Project::where('external_id', $request)
            ->selectRaw('TIMEDIFF(updated_at, created_at) as tempo_total')
            ->first();
    }

    public function saveOutput($externalId, $result)
    {
        return Project::where('external_id', $externalId)->update(['qobject_result' => $result]);
    }
}