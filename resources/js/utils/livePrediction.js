/**
 * Same prediction as the mobile app. The app stays quiet while its own prediction
 * holds, so the map has to keep moving markers between updates.
 */
export const PREDICT_MAX_AGE_MS = 15000;
export const PREDICT_MIN_SPEED = 1;
export const STALE_AFTER_MS = 60000;

const EARTH_RADIUS_METERS = 6371000;

export function ageMs(position, nowMs) {
    return Math.max(0, nowMs - new Date(position.recorded_at).getTime());
}

export function isStale(position, nowMs) {
    return ageMs(position, nowMs) >= STALE_AFTER_MS;
}

/** Where to draw the marker: moved along heading at speed while the rules allow it. */
export function predictPosition(position, nowMs) {
    const age = ageMs(position, nowMs);
    const { speed, heading } = position;

    if (
        age >= PREDICT_MAX_AGE_MS ||
        speed === null ||
        speed === undefined ||
        speed < PREDICT_MIN_SPEED ||
        heading === null ||
        heading === undefined
    ) {
        return [position.lat, position.lng];
    }

    const angular = (speed * (age / 1000)) / EARTH_RADIUS_METERS;
    const bearing = (heading * Math.PI) / 180;
    const lat = (position.lat * Math.PI) / 180;
    const lng = (position.lng * Math.PI) / 180;

    const newLat = Math.asin(
        Math.sin(lat) * Math.cos(angular) + Math.cos(lat) * Math.sin(angular) * Math.cos(bearing),
    );
    const newLng =
        lng +
        Math.atan2(
            Math.sin(bearing) * Math.sin(angular) * Math.cos(lat),
            Math.cos(angular) - Math.sin(lat) * Math.sin(newLat),
        );

    return [(newLat * 180) / Math.PI, (newLng * 180) / Math.PI];
}
