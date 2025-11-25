<?php declare(strict_types=1);

namespace App\Domains\Geofence\Service;

use App\Domains\Device\Model\Device as DeviceModel;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\Position\Model\Position as PositionModel;
use App\Domains\AlarmNotification\Model\AlarmNotification as AlarmNotificationModel;

class GeofenceChecker
{
    /**
     * Check if position violates any geofences and create alarms
     *
     * @param \App\Domains\Position\Model\Position $position
     * @return array Array of created alarm notifications
     */
    public function checkPosition(PositionModel $position): array
    {
        $device = $position->device;
        
        if (!$device) {
            return [];
        }

        // Get all active geofences for this device
        $geofences = $device->geofences()
            ->wherePivot('enabled', true)
            ->where('enabled', true)
            ->get();

        if ($geofences->isEmpty()) {
            return [];
        }

        $alarms = [];
        $latitude = $position->latitude;
        $longitude = $position->longitude;

        foreach ($geofences as $geofence) {
            $isInside = $geofence->containsPoint($latitude, $longitude);
            $mode = $geofence->pivot->mode; // 'inside' or 'outside'

            // Determine if this is a violation
            $violation = ($mode === 'outside' && $isInside) || ($mode === 'inside' && !$isInside);

            if ($violation) {
                $alarm = $this->createAlarm($position, $geofence, $mode, $isInside);
                if ($alarm) {
                    $alarms[] = $alarm;
                }
            }
        }

        return $alarms;
    }

    /**
     * Create alarm notification for geofence violation
     *
     * @param \App\Domains\Position\Model\Position $position
     * @param \App\Domains\Geofence\Model\Geofence $geofence
     * @param string $mode
     * @param bool $isInside
     * @return \App\Domains\AlarmNotification\Model\AlarmNotification|null
     */
    protected function createAlarm(
        PositionModel $position,
        GeofenceModel $geofence,
        string $mode,
        bool $isInside
    ): ?AlarmNotificationModel {
        // Check if there's already a recent alarm (within last 5 minutes) to avoid spam
        $recentAlarm = AlarmNotificationModel::where('geofence_id', $geofence->id)
            ->where('vehicle_id', $position->vehicle_id)
            ->where('closed_at', null)
            ->where('date_at', '>=', now()->subMinutes(5))
            ->first();

        if ($recentAlarm) {
            return null; // Don't create duplicate alarm
        }

        $config = [
            'geofence_name' => $geofence->name,
            'geofence_mode' => $mode,
            'violation_type' => $isInside ? 'entered' : 'exited',
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
        ];

        $name = $isInside 
            ? __('Geofence Entered: :name', ['name' => $geofence->name])
            : __('Geofence Exited: :name', ['name' => $geofence->name]);

        try {
            $alarm = AlarmNotificationModel::create([
                'name' => $name,
                'type' => 'geofence',
                'config' => $config,
                'point' => $position->point,
                'dashboard' => true,
                'telegram' => true,
                'date_at' => $position->date_at,
                'date_utc_at' => $position->date_utc_at,
                'alarm_id' => null,
                'geofence_id' => $geofence->id,
                'position_id' => $position->id,
                'trip_id' => $position->trip_id,
                'vehicle_id' => $position->vehicle_id,
            ]);

            logger()->info('Geofence alarm created', [
                'geofence_id' => $geofence->id,
                'geofence_name' => $geofence->name,
                'vehicle_id' => $position->vehicle_id,
                'violation_type' => $isInside ? 'entered' : 'exited',
            ]);

            return $alarm;
        } catch (\Exception $e) {
            logger()->error('Failed to create geofence alarm', [
                'error' => $e->getMessage(),
                'geofence_id' => $geofence->id,
                'position_id' => $position->id,
            ]);

            return null;
        }
    }

    /**
     * Check geofences for a device in real-time (for API/WebSocket)
     *
     * @param \App\Domains\Device\Model\Device $device
     * @param float $latitude
     * @param float $longitude
     * @return array Status of each geofence
     */
    public function checkDeviceLocation(DeviceModel $device, float $latitude, float $longitude): array
    {
        $geofences = $device->geofences()
            ->wherePivot('enabled', true)
            ->where('enabled', true)
            ->get();

        $status = [];

        foreach ($geofences as $geofence) {
            $isInside = $geofence->containsPoint($latitude, $longitude);
            $mode = $geofence->pivot->mode;
            $violation = ($mode === 'outside' && $isInside) || ($mode === 'inside' && !$isInside);

            $status[] = [
                'geofence_id' => $geofence->id,
                'geofence_name' => $geofence->name,
                'is_inside' => $isInside,
                'mode' => $mode,
                'violation' => $violation,
                'color' => $geofence->color,
            ];
        }

        return $status;
    }
}
