@extends ('domains.device.update-layout')

@section ('content')

<div class="box p-5 mt-5">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium">{{ __('Geofencing Management') }}</h2>
        <a href="{{ route('geofence.create') }}" class="btn btn-primary">
            @icon('plus', 'w-4 h-4 mr-2')
            {{ __('Create New Geofence') }}
        </a>
    </div>

    @if ($geofences->isEmpty())
    <div class="alert alert-info">
        {{ __('No geofences created yet. Create your first geofence to start monitoring device location.') }}
    </div>
    @else
    
    <div class="table-responsive mt-4">
        <table class="table table-report">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Mode') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($geofences as $geofence)
                @php
                    $attached = $deviceGeofences->firstWhere('id', $geofence->id);
                @endphp
                <tr class="{{ $attached ? 'bg-success-light' : '' }}">
                    <td>
                        <div class="flex items-center">
                            <span class="w-4 h-4 rounded-full mr-2" style="background-color: {{ $geofence->color }}"></span>
                            <strong>{{ $geofence->name }}</strong>
                        </div>
                        @if ($geofence->description)
                        <div class="text-sm text-gray-500">{{ $geofence->description }}</div>
                        @endif
                    </td>
                    <td>
                        @if ($geofence->type === 'circle')
                        <span class="badge badge-info">
                            @icon('circle', 'w-3 h-3 mr-1')
                            {{ __('Circle') }} ({{ number_format($geofence->radius) }}m)
                        </span>
                        @else
                        <span class="badge badge-primary">
                            @icon('map', 'w-3 h-3 mr-1')
                            {{ __('Polygon') }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if ($attached)
                            @if ($attached->pivot->enabled)
                            <span class="badge badge-success">@icon('check-circle', 'w-3 h-3 mr-1') {{ __('Active') }}</span>
                            @else
                            <span class="badge badge-warning">@icon('pause', 'w-3 h-3 mr-1') {{ __('Paused') }}</span>
                            @endif
                        @else
                        <span class="badge badge-secondary">{{ __('Not Attached') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($attached)
                            @if ($attached->pivot->mode === 'inside')
                            <span class="badge badge-success">{{ __('Alarm if OUTSIDE') }}</span>
                            @else
                            <span class="badge badge-danger">{{ __('Alarm if INSIDE') }}</span>
                            @endif
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($attached)
                        <form method="POST" action="{{ route('device.geofence.detach', [$device->id, $geofence->id]) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Detach this geofence from device?') }}')">
                                @icon('x', 'w-4 h-4')
                                {{ __('Detach') }}
                            </button>
                        </form>
                        @else
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#attach-modal-{{ $geofence->id }}">
                            @icon('link', 'w-4 h-4')
                            {{ __('Attach') }}
                        </button>
                        @endif
                        
                        <a href="{{ route('geofence.update', $geofence->id) }}" class="btn btn-sm btn-outline-secondary ml-2">
                            @icon('edit', 'w-4 h-4')
                        </a>
                    </td>
                </tr>

                <!-- Attach Modal -->
                @if (!$attached)
                <div class="modal" id="attach-modal-{{ $geofence->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('device.geofence.attach', [$device->id, $geofence->id]) }}">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Attach Geofence') }}: {{ $geofence->name }}</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>{{ __('Alarm Mode') }}</label>
                                        <select name="mode" class="form-control" required>
                                            <option value="inside">{{ __('Alarm when device is OUTSIDE this area') }}</option>
                                            <option value="outside">{{ __('Alarm when device is INSIDE this area') }}</option>
                                        </select>
                                        <small class="text-gray-500">
                                            {{ __('Choose when to trigger alarms for this geofence') }}
                                        </small>
                                    </div>
                                    <div class="form-check mt-3">
                                        <input type="checkbox" name="enabled" value="1" checked class="form-check-input" id="enabled-{{ $geofence->id }}">
                                        <label class="form-check-label" for="enabled-{{ $geofence->id }}">
                                            {{ __('Enable monitoring immediately') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Attach Geofence') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @endif
</div>

<!-- Map Preview -->
<div class="box p-5 mt-5">
    <h3 class="text-lg font-medium mb-4">{{ __('Geofences Map') }}</h3>
    <div id="geofences-map" data-map-render style="height: 500px; border-radius: 0.5rem;"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const render = document.getElementById('geofences-map');
    
    if (!render || typeof window.Map === 'undefined') {
        return;
    }

    const map = new window.Map(render);
    const geofences = @json($geofences);
    const deviceGeofences = @json($deviceGeofences->pluck('id'));

    // Set default view
    map.getMap().setView([30.0444, 31.2357], 6);

    const layers = [];

    geofences.forEach(geofence => {
        if (!geofence.geojson) return;
        
        const isAttached = deviceGeofences.includes(geofence.id);
        const color = geofence.color || '#3B82F6';
        const options = {
            color: color,
            fillColor: color,
            fillOpacity: isAttached ? 0.3 : 0.1,
            weight: isAttached ? 3 : 1
        };

        let layer;
        const geojson = geofence.geojson;

        if (geojson.properties.type === 'circle') {
            layer = L.circle(
                [geojson.geometry.coordinates[1], geojson.geometry.coordinates[0]],
                {
                    ...options,
                    radius: geojson.properties.radius
                }
            );
        } else {
            layer = L.geoJSON(geojson.geometry, {
                style: options
            });
        }

        layer.bindPopup(`
            <strong>${geofence.name}</strong><br>
            ${isAttached ? '<span style="color: #10b981;">✓ Attached</span>' : '<span style="color: #6b7280;">Not attached</span>'}
        `);

        layer.addTo(map.getMap());
        layers.push(layer);
    });

    // Fit map to show all geofences
    if (layers.length > 0) {
        setTimeout(() => {
            const group = L.featureGroup(layers);
            map.getMap().fitBounds(group.getBounds().pad(0.1));
        }, 100);
    }
});
</script>
@stop
