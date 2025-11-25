<?php declare(strict_types=1);

namespace App\Domains\Geofence\Action;

use App\Domains\Geofence\Model\Geofence as Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Store
{
    /**
     * @param array $data
     * @return \App\Domains\Geofence\Model\Geofence
     */
    public function __invoke(array $data): Model
    {
        $geojson = json_decode($data['geometry'], true);
        
        if (!$geojson || !isset($geojson['geometry'])) {
            throw new \InvalidArgumentException('Invalid GeoJSON format');
        }
        
        $geometry = $geojson['geometry'];
        $properties = $geojson['properties'] ?? [];
        
        $attributes = [
            'name' => $data['name'],
            'type' => $properties['type'] ?? $data['type'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#FF0000',
            'enabled' => true,
            'user_id' => Auth::id(),
        ];
        
        if ($properties['type'] === 'circle' && $geometry['type'] === 'Point') {
            // Circle: store center point and radius
            $coordinates = $geometry['coordinates']; // [lng, lat]
            $radius = $properties['radius'] ?? 1000;
            
            $attributes['center'] = DB::raw("ST_GeomFromText('POINT({$coordinates[0]} {$coordinates[1]})', 4326)");
            $attributes['radius'] = $radius;
            $attributes['geom'] = null;
            
        } else if ($geometry['type'] === 'Polygon') {
            // Polygon: store geometry
            $coordinates = $geometry['coordinates'][0];
            $wkt = $this->coordinatesToWKT($coordinates, 'POLYGON');
            
            $attributes['geom'] = DB::raw("ST_GeomFromText('{$wkt}', 4326)");
            $attributes['center'] = null;
            $attributes['radius'] = null;
        }
        
        return Model::create($attributes);
    }
    
    /**
     * Convert GeoJSON coordinates to WKT format
     *
     * @param array $coordinates [[lng, lat], [lng, lat], ...]
     * @param string $type
     * @return string
     */
    protected function coordinatesToWKT(array $coordinates, string $type = 'POLYGON'): string
    {
        $points = array_map(function($coord) {
            return "{$coord[0]} {$coord[1]}";
        }, $coordinates);
        
        return "{$type}((" . implode(', ', $points) . "))";
    }
}
