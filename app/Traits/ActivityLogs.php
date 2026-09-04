<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait ActivityLogs
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('system')
            ->logExcept([
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->setDescriptionForEvent(function (string $eventName) {
                $causer = Auth::user()->name ?? 'System';
                $email = Auth::user()->email ?? 'System';

                $eventLocalzed = match ($eventName) {
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                    default => $eventName,
                };

                return "Data ini di {$eventLocalzed} oleh $causer ( $email ) pada ".now()->format('d M Y H:i:s');
            });
    }
}
