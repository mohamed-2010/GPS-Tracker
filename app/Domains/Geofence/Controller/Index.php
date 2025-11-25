<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Device\Model\Device as DeviceModel;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Index extends ControllerWebAbstract
{
    /**
     * @param int $device_id
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(int $device_id): Response
    {
        $device = DeviceModel::query()
            ->byUserId(Auth::id())
            ->findOrFail($device_id);

        $geofences = GeofenceModel::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        $deviceGeofences = $device->geofences()
            ->withPivot('mode', 'enabled')
            ->get();

        return $this->page('geofence.index', [
            'row' => $device,  // Changed from 'device' to 'row' to match layout expectations
            'device' => $device,
            'geofences' => $geofences,
            'deviceGeofences' => $deviceGeofences,
        ]);
    }
}
