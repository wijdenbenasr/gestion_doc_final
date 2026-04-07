<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait OptimisticLocking
{
    public static function bootOptimisticLocking()
    {
        static::updating(function (Model $model) {
            $lockVersionColumn = 'lock_version';

            if ($model->isDirty()) {
                $originalVersion = $model->getOriginal($lockVersionColumn);
                $currentVersion = $model->$lockVersionColumn;

                if ($originalVersion !== $currentVersion) {
                    throw new \Exception('Erreur de concurrence : Le document a été modifié par un autre utilisateur.');
                }

                $model->$lockVersionColumn++;
            }
        });
    }
}
