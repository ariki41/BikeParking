export const installLeafletMock = async (page) => {
    await page.addInitScript(() => {
        const maps = [];
        let markerCount = 0;

        class MockMap {
            constructor() {
                this.handlers = {};
                this.center = { lat: 35.68, lng: 139.76 };
                this.zoom = 15;
                maps.push(this);
            }

            setView(center, zoom) {
                this.center = { lat: center[0], lng: center[1] };
                this.zoom = zoom;
                return this;
            }

            getBounds() {
                return {
                    getNorth: () => this.center.lat + 0.1,
                    getEast: () => this.center.lng + 0.1,
                    getSouth: () => this.center.lat - 0.1,
                    getWest: () => this.center.lng - 0.1,
                };
            }

            getCenter() { return this.center; }
            getZoom() { return this.zoom; }
            whenReady(callback) { callback(); }
            on(event, callback) { this.handlers[event] = callback; return this; }
            fire(event) { this.handlers[event]?.(); }
        }

        window.__e2eLeafletMaps = maps;
        window.__e2eLeafletMarkerCount = () => markerCount;
        window.L = {
            map: () => new MockMap(),
            tileLayer: () => ({ addTo: () => ({}) }),
            marker: () => ({
                addTo: () => {
                    markerCount += 1;

                    return { bindPopup() {}, on() {}, options: {}, remove() {} };
                },
            }),
            Icon: { Default: class { constructor(options) { this.options = options; } } },
        };
    });
};
