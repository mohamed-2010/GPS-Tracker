<?php declare(strict_types=1);

namespace App\Domains\Geofence\Model;

use App\Domains\CoreApp\Model\PivotAbstract;
use App\Domains\Device\Model\Device as DeviceModel;

class DeviceGeofence extends PivotAbstract
{
    /**
     * @var string
     */
    protected $table = 'device_geofence';

    /**
     * @const string
     */
    public const TABLE = 'device_geofence';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'geofence_id',
        'mode',
        'enabled',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo(DeviceModel::class, DeviceModel::FOREIGN);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function geofence()
    {
        return $this->belongsTo(Geofence::class, Geofence::FOREIGN);
    }
}
