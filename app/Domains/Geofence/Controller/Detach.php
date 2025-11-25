<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Domains\Device\Model\Device as DeviceModel;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Detach extends ControllerWebAbstract
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

        $device->geofences()->detach($geofence_id);

        return back()->with('success', __('Geofence detached from device successfully'));
    }
}
