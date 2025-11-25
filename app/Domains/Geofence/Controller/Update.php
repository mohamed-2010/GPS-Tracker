<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Update extends ControllerWebAbstract
{
    /**
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(int $id): Response
    {
        $geofence = GeofenceModel::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return $this->page('geofence.update', [
            'geofence' => $geofence,
        ]);
    }
}
