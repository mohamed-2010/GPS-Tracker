<?php declare(strict_types=1);

namespace App\Domains\Geofence\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\CoreApp\Model\ModelAbstract;
use App\Domains\Device\Model\Device as DeviceModel;
use App\Domains\AlarmNotification\Model\AlarmNotification as AlarmNotificationModel;
use App\Domains\User\Model\User as UserModel;

class Geofence extends ModelAbstract
{
    /**
     * @var string
     */
    protected $table = 'geofence';

    /**
     * @const string
     */
    public const TABLE = 'geofence';

    /**
     * @const string
     */
    public const FOREIGN = 'geofence_id';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'radius' => 'float',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'geom',
        'center',
        'radius',
        'color',
        'enabled',
        'user_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, UserModel::FOREIGN);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(DeviceModel::class, DeviceGeofence::TABLE)
            ->withPivot('mode', 'enabled')
            ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AlarmNotificationModel::class, static::FOREIGN);
    }

    /**
     * Check if a point is inside this geofence
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if ($this->type === 'circle') {
            return $this->containsPointInCircle($latitude, $longitude);
        }

        return $this->containsPointInPolygon($latitude, $longitude);
    }

    /**
     * Check if point is inside circle
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    protected function containsPointInCircle(float $latitude, float $longitude): bool
    {
        if (!$this->center || !$this->radius) {
            return false;
        }

        // Use ST_Distance_Sphere to calculate distance
        $result = \DB::selectOne("
            SELECT ST_Distance_Sphere(
                ST_GeomFromText(?, 4326),
                ?
            ) as distance
        ", [
            "POINT({$longitude} {$latitude})",
            $this->center,
        ]);

        return $result && $result->distance <= $this->radius;
    }

    /**
     * Check if point is inside polygon
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    protected function containsPointInPolygon(float $latitude, float $longitude): bool
    {
        if (!$this->geom) {
            return false;
        }

        $result = \DB::selectOne("
            SELECT ST_Contains(
                ?,
                ST_GeomFromText(?, 4326)
            ) as contains
        ", [
            $this->geom,
            "POINT({$longitude} {$latitude})",
        ]);

        return $result && (bool) $result->contains;
    }

    /**
     * Get latitude from center point
     *
     * @return float|null
     */
    public function getCenterLatitudeAttribute(): ?float
    {
        if (!$this->center) {
            return null;
        }

        $result = \DB::selectOne("SELECT ST_Y(?) as lat", [$this->center]);

        return $result ? (float) $result->lat : null;
    }

    /**
     * Get longitude from center point
     *
     * @return float|null
     */
    public function getCenterLongitudeAttribute(): ?float
    {
        if (!$this->center) {
            return null;
        }

        $result = \DB::selectOne("SELECT ST_X(?) as lng", [$this->center]);

        return $result ? (float) $result->lng : null;
    }

    /**
     * Get GeoJSON representation
     *
     * @return array|null
     */
    public function getGeojsonAttribute(): ?array
    {
        if ($this->type === 'circle' && $this->center && $this->radius) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$this->center_longitude, $this->center_latitude],
                ],
                'properties' => [
                    'type' => 'circle',
                    'radius' => $this->radius,
                    'color' => $this->color,
                ],
            ];
        }

        if (!$this->geom) {
            return null;
        }

        $result = \DB::selectOne("SELECT ST_AsGeoJSON(?) as geojson", [$this->geom]);

        if (!$result || !$result->geojson) {
            return null;
        }

        return [
            'type' => 'Feature',
            'geometry' => json_decode($result->geojson, true),
            'properties' => [
                'type' => $this->type,
                'color' => $this->color,
            ],
        ];
    }
}
