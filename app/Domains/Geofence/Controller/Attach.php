<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Domains\Device\Model\Device as DeviceModel;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Attach extends ControllerWebAbstract
{
    /**
     * @param int $device_id
     * @param int $geofence_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $device_id, int $geofence_id): RedirectResponse
    {
        $device = DeviceModel::query()
            ->byUserId(Auth::id())
            ->findOrFail($device_id);

        $geofence = GeofenceModel::query()
            ->where('user_id', Auth::id())
            ->findOrFail($geofence_id);

        $mode = request()->input('mode', 'inside');
        $enabled = request()->boolean('enabled', true);

        // Check if already attached
        if ($device->geofences()->where('geofence_id', $geofence_id)->exists()) {
            // Update existing
            $device->geofences()->updateExistingPivot($geofence_id, [
                'mode' => $mode,
                'enabled' => $enabled,
            ]);
        } else {
            // Attach new
            $device->geofences()->attach($geofence_id, [
                'mode' => $mode,
                'enabled' => $enabled,
            ]);
        }

        return back()->with('success', __('Geofence attached to device successfully'));
    }
}
