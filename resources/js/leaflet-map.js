const mapInstances = new Map();
let livewireInitialized = false;

const leafletTileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const leafletAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

const coordinatesFor = (marker) => {
    const latitude = Number(marker.latitude);
    const longitude = Number(marker.longitude);

    return Number.isFinite(latitude) && Number.isFinite(longitude)
        ? [latitude, longitude]
        : null;
};

const popupFor = (marker, urlTemplate) => {
    const label = marker.popupLabel ?? marker.name;

    if (!label) {
        return null;
    }

    const popupUrl = marker.popupUrl
        ?? (urlTemplate && marker.id !== undefined
            ? urlTemplate.replace('__ID__', encodeURIComponent(String(marker.id)))
            : null);

    if (!popupUrl) {
        return document.createTextNode(String(label));
    }

    const url = new URL(popupUrl, window.location.origin);

    if (!['http:', 'https:'].includes(url.protocol)) {
        return document.createTextNode(String(label));
    }

    const link = document.createElement('a');
    link.href = url.href;
    link.textContent = String(label);

    return link;
};

const replaceMarkers = (instance, markers) => {
    instance.markers.forEach((marker) => marker.remove());
    instance.markers = [];

    if (!Array.isArray(markers)) {
        return;
    }

    markers.forEach((markerData) => {
        const coordinates = coordinatesFor(markerData);

        if (!coordinates) {
            return;
        }

        const marker = window.L.marker(coordinates).addTo(instance.map);
        const popup = popupFor(markerData, instance.configuration.markerUrlTemplate);

        if (popup) {
            marker.bindPopup(popup);
        }

        instance.markers.push(marker);
    });
};

const spotsFrom = (payload) => {
    const detail = Array.isArray(payload) ? payload[0] : payload;

    return detail?.spots ?? [];
};

const livewireIsReady = () => Boolean(
    window.Livewire
    && (livewireInitialized || window.Livewire.all?.().length > 0),
);

const dispatchBounds = (instance) => {
    // livewire:init is too early: component event listeners are registered during initialization.
    if (!livewireIsReady()) {
        instance.boundsDispatchPending = true;
        return;
    }

    const bounds = instance.map.getBounds();

    window.Livewire.dispatch(instance.configuration.boundsEvent, {
        bounds: {
            north: bounds.getNorth(),
            east: bounds.getEast(),
            south: bounds.getSouth(),
            west: bounds.getWest(),
        },
    });
};

const connectLivewire = (instance) => {
    if (!instance.configuration.markersEvent || instance.livewireConnected) {
        return;
    }

    if (!livewireIsReady()) {
        return;
    }

    window.Livewire.on(instance.configuration.markersEvent, (payload) => {
        replaceMarkers(instance, spotsFrom(payload));
    });
    instance.livewireConnected = true;
};

const showInitializationError = (element, error) => {
    const root = element.closest('[data-leaflet-map-root]');
    const errorMessage = root?.querySelector('[data-leaflet-map-error]');

    element.classList.add('hidden');
    errorMessage?.classList.remove('hidden');
    errorMessage?.classList.add('flex');
    root?.dispatchEvent(new CustomEvent('leaflet-map:error', {
        bubbles: true,
        detail: { error },
    }));

    console.error('Leaflet map initialization failed.', error);
};

const initializeMap = (element) => {
    if (mapInstances.has(element.id)) {
        return;
    }

    try {
        if (!window.L) {
            throw new Error('Leaflet assets are unavailable.');
        }

        const configuration = JSON.parse(element.dataset.leafletConfiguration ?? '{}');
        const center = coordinatesFor(configuration.center ?? {});

        if (!center) {
            throw new Error('Map center coordinates are invalid.');
        }

        const map = window.L.map(element).setView(center, configuration.zoom);

        window.L.tileLayer(leafletTileUrl, { attribution: leafletAttribution }).addTo(map);

        const instance = {
            map,
            configuration,
            markers: [],
            livewireConnected: false,
            boundsDispatchPending: false,
        };

        mapInstances.set(element.id, instance);
        replaceMarkers(instance, configuration.markers);
        connectLivewire(instance);

        if (configuration.boundsEvent) {
            map.whenReady(() => dispatchBounds(instance));
            map.on('moveend', () => dispatchBounds(instance));
        }

        element.dispatchEvent(new CustomEvent('leaflet-map:ready', {
            bubbles: true,
            detail: { map },
        }));
    } catch (error) {
        showInitializationError(element, error);
    }
};

const initializeMaps = () => {
    document.querySelectorAll('[data-leaflet-map]').forEach(initializeMap);
};

document.addEventListener('livewire:initialized', () => {
    livewireInitialized = true;

    mapInstances.forEach((instance) => {
        connectLivewire(instance);

        if (instance.boundsDispatchPending) {
            instance.boundsDispatchPending = false;
            dispatchBounds(instance);
        }
    });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMaps, { once: true });
} else {
    initializeMaps();
}

window.LeafletMaps = Object.freeze({
    setMarkers(mapId, markers) {
        const instance = mapInstances.get(mapId);

        if (instance) {
            replaceMarkers(instance, markers);
        }
    },
});
