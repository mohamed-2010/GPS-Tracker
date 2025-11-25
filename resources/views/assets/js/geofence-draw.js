import L from "leaflet";
import "leaflet-draw";

export default class GeofenceDraw {
    constructor(mapInstance, options = {}) {
        this.map = mapInstance.getMap();
        this.options = options;
        this.drawnItems = new L.FeatureGroup();
        this.map.addLayer(this.drawnItems);

        this.geometryInput = document.getElementById(options.geometryInputId || 'geometry-data');
        this.colorInput = document.getElementById(options.colorInputId || 'geofence-color');
        this.typeSelect = document.getElementById(options.typeSelectId || 'geofence-type');

        this.init();
    }

    init() {
        // Load existing geometry if provided
        if (this.options.existingGeometry) {
            this.loadExistingGeometry(this.options.existingGeometry, this.options.color);
        }

        // Setup draw controls
        this.setupDrawControls();

        // Setup event listeners
        this.setupEventListeners();
    }

    loadExistingGeometry(geojson, color) {
        if (!geojson || !geojson.geometry) {
            return;
        }

        let layer;
        const geometry = geojson.geometry;
        const properties = geojson.properties || {};

        if (properties.type === 'circle' && geometry.type === 'Point') {
            // Circle geofence
            const [lng, lat] = geometry.coordinates;
            layer = L.circle([lat, lng], {
                radius: properties.radius || 1000,
                color: color || properties.color || '#3B82F6'
            });
            this.map.setView([lat, lng], 13);
        } else if (geometry.type === 'Polygon') {
            // Polygon geofence - coordinates format: [[[lng, lat], [lng, lat], ...]]
            const coordinates = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);
            layer = L.polygon(coordinates, {
                color: color || properties.color || '#3B82F6'
            });
            this.map.fitBounds(layer.getBounds());
        }

        if (layer) {
            this.drawnItems.addLayer(layer);
        }
    }

    setupDrawControls() {
        const drawOptions = {
            polygon: {
                allowIntersection: false,
                shapeOptions: {
                    color: this.colorInput ? this.colorInput.value : '#3B82F6'
                }
            },
            circle: {
                shapeOptions: {
                    color: this.colorInput ? this.colorInput.value : '#3B82F6'
                }
            },
            polyline: false,
            marker: false,
            rectangle: false,
            circlemarker: false
        };

        // If editing existing geofence, limit to current type
        if (this.options.existingGeometry && this.typeSelect) {
            const currentType = this.typeSelect.value;
            if (currentType === 'polygon') {
                drawOptions.circle = false;
            } else if (currentType === 'circle') {
                drawOptions.polygon = false;
            }
        }

        this.drawControl = new L.Control.Draw({
            position: 'topright',
            draw: drawOptions,
            edit: {
                featureGroup: this.drawnItems,
                remove: this.options.allowRemove !== false
            }
        });

        this.map.addControl(this.drawControl);

        // Store draw options for later updates
        this.drawOptions = drawOptions;
    }

    setupEventListeners() {
        // Draw created
        this.map.on('draw:created', (e) => {
            const layer = e.layer;
            this.drawnItems.clearLayers();
            this.drawnItems.addLayer(layer);

            // Update type field based on drawn shape
            if (this.typeSelect) {
                this.typeSelect.value = e.layerType;
            }

            this.updateGeometry(layer, e.layerType);
        });

        // Draw edited
        this.map.on('draw:edited', (e) => {
            e.layers.eachLayer((layer) => {
                const type = layer instanceof L.Circle ? 'circle' : 'polygon';
                this.updateGeometry(layer, type);
            });
        });

        // Color change - update existing shapes and future shapes
        if (this.colorInput) {
            this.colorInput.addEventListener('change', (e) => {
                const newColor = e.target.value;

                // Update existing drawn shapes
                this.drawnItems.eachLayer((layer) => {
                    layer.setStyle({ color: newColor, fillColor: newColor });
                });

                // Update draw control for future shapes
                this.updateDrawControlColor(newColor);
            });
        }
    }

    updateDrawControlColor(color) {
        // Remove old control
        this.map.removeControl(this.drawControl);

        // Update options
        if (this.drawOptions.polygon) {
            this.drawOptions.polygon.shapeOptions.color = color;
        }
        if (this.drawOptions.circle) {
            this.drawOptions.circle.shapeOptions.color = color;
        }

        // Recreate control with new color
        this.drawControl = new L.Control.Draw({
            position: 'topright',
            draw: this.drawOptions,
            edit: {
                featureGroup: this.drawnItems,
                remove: this.options.allowRemove !== false
            }
        });

        this.map.addControl(this.drawControl);
    }

    updateGeometry(layer, type) {
        let geojson;

        if (type === 'circle') {
            const center = layer.getLatLng();
            const radius = layer.getRadius();
            // GeoJSON format for circle (Point with radius property)
            geojson = JSON.stringify({
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [center.lng, center.lat]  // [longitude, latitude]
                },
                properties: {
                    type: 'circle',
                    radius: radius
                }
            });
        } else if (type === 'polygon') {
            const latlngs = layer.getLatLngs()[0];
            // GeoJSON format: [[[lng, lat], [lng, lat], ...]]
            const coordinates = latlngs.map(ll => [ll.lng, ll.lat]);
            geojson = JSON.stringify({
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [coordinates]
                },
                properties: {
                    type: 'polygon'
                }
            });
        }

        if (this.geometryInput) {
            this.geometryInput.value = geojson;
        }
    }
}
