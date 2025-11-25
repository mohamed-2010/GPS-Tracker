<?php declare(strict_types=1);

namespace App\Domains\Geofence\Action;

use App\Domains\Geofence\Model\Geofence as Model;
use Illuminate\Support\Facades\DB;

class Update
{
    /**
     * @param \App\Domains\Geofence\Model\Geofence $model
     * @param array $data
     * @return \App\Domains\Geofence\Model\Geofence
     */
    public function __invoke(Model $model, array $data): Model
    {
        $geojson = json_decode($data['geometry'], true);
        
        if (!$geojson || !isset($geojson['geometry'])) {
            throw new \InvalidArgumentException('Invalid GeoJSON format');
        }
        
        $geometry = $geojson['geometry'];
        $properties = $geojson['properties'] ?? [];
        
        $attributes = [
            'name' => $data['name'],
            'color' => $data['color'] ?? $model->color,
            'description' => $data['description'] ?? $model->description,
        ];
        
        if ($properties['type'] === 'circle' && $geometry['type'] === 'Point') {
            // Circle: update center point and radius
            $coordinates = $geometry['coordinates']; // [lng, lat]
            $radius = $properties['radius'] ?? $model->radius;
            
            $attributes['center'] = DB::raw("ST_GeomFromText('POINT({$coordinates[0]} {$coordinates[1]})', 4326)");
            $attributes['radius'] = $radius;
            
        } else if ($geometry['type'] === 'Polygon') {
            // Polygon: update geometry
            $coordinates = $geometry['coordinates'][0];
            $wkt = $this->coordinatesToWKT($coordinates, 'POLYGON');
            
            $attributes['geom'] = DB::raw("ST_GeomFromText('{$wkt}', 4326)");
        }
        
        $model->update($attributes);
        
        return $model->fresh();
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
