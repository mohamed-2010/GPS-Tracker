<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Create extends ControllerWebAbstract
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function __invoke(): Response
    {
        return $this->page('geofence.create', [
            'geofence' => new GeofenceModel(),
        ]);
    }
}
