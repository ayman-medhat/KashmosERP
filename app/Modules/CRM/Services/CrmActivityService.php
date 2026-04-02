<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmTask;

class CrmActivityService
{
    public function completeActivity(CrmActivity $activity): CrmActivity
    {
        if ($activity->status === 'completed') {
            return $activity;
        }

        $activity->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        return $activity->refresh();
    }

    public function completeTask(CrmTask $task): CrmTask
    {
        if ($task->status === 'completed') {
            return $task;
        }

        $task->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        return $task->refresh();
    }
}

