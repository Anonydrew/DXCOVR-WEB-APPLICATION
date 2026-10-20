/**
 * Minimal fetch helpers for the admin. Every request carries the CSRF token
 * and asks for JSON so Laravel returns validation errors as JSON.
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function request(url, { method = 'GET', body = null, json = null } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
    };

    let payload = body;

    if (json !== null) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(json);
    }

    const response = await fetch(url, { method, headers, body: payload, credentials: 'same-origin' });
    let data = null;

    try {
        data = await response.json();
    } catch {
        data = null;
    }

    if (!response.ok) {
        const message =
            data?.errors ? Object.values(data.errors).flat()[0] : data?.message || `Request failed (${response.status}).`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

/**
 * Only allow link targets that cannot execute script.
 */
export function isSafeUrl(url) {
    if (!url) return false;
    const value = url.trim();
    return /^(https?:\/\/|mailto:|tel:|\/(?!\/)|#)/i.test(value);
}
