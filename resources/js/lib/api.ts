import { http, HttpResponseError } from '@inertiajs/core';

export async function getJson<T>(
    url: string,
    signal?: AbortSignal,
): Promise<T> {
    const response = await http.getClient().request({
        method: 'get',
        url,
        headers: { Accept: 'application/json' },
        signal,
    });

    return JSON.parse(response.data) as T;
}

export function apiErrorMessage(error: unknown): string {
    if (error instanceof HttpResponseError) {
        if (error.response.status === 401) {
            return 'Your session has expired. Please sign in again.';
        }

        if (error.response.status === 403) {
            return 'You do not have access to this content.';
        }
    }

    return 'Could not load data. Please try again.';
}
