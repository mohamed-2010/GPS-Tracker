@extends ('layouts.in')

@section ('body')

<form method="POST" action="{{ route('geofence.patch', $geofence->id) }}">
    @csrf
    @method('PATCH')

    <div class="box p-5 mt-5">
        <div class="p-2">
            <h2 class="text-lg font-medium mb-4">{{ __('Edit Geofence') }}</h2>

            <div class="mb-4">
                <label for="geofence-name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="geofence-name" class="form-control" value="{{ $geofence->name }}" required>
            </div>

            <input type="hidden" name="type" id="geofence-type" value="{{ $geofence->type }}">
            <div class="mb-4">
                <small class="text-gray-600">{{ __('Type cannot be changed after creation') }}</small>
            </div>

            <div class="mb-4">
                <label class="form-label">{{ __('Edit Geofence on Map') }}</label>
                <div id="map" data-map-render style="height: 500px; border-radius: 8px;"></div>
                <input type="hidden" name="geometry" id="geometry-data" value="{{ $geofence->geometry }}" required>
            </div>

            <div class="mb-4">
                <label for="geofence-color" class="form-label">{{ __('Color') }}</label>
                <input type="color" name="color" id="geofence-color" class="form-control" value="{{ $geofence->color }}">
            </div>

            <div class="text-right">
                <a href="{{ url()->previous() }}" class="btn btn-secondary mr-2">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Update Geofence') }}</button>
            </div>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const render = document.getElementById('map');
    
    if (!render || typeof window.Map === 'undefined') {
        console.error('Map class not found');
        return;
    }

    const mapInstance = new window.Map(render);
    
    // Set default view (center and zoom) for the map
    const existingGeometry = @json($geofence->geometry);
    if (!existingGeometry) {
        mapInstance.getMap().setView([30.0444, 31.2357], 6); // Default to Egypt center
    }
    
    if (typeof window.GeofenceDraw !== 'undefined') {
        new window.GeofenceDraw(mapInstance, {
            geometryInputId: 'geometry-data',
            colorInputId: 'geofence-color',
            typeSelectId: 'geofence-type',
            existingGeometry: @json($geofence->geometry),
            color: '{{ $geofence->color }}',
            allowRemove: false
        });
    }
});
</script>
@stop