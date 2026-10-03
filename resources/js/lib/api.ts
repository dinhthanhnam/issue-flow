import { http, HttpResponseError } from '@inertiajs/core';

async function requestJson<T>(
    method: 'get' | 'post' | 'patch',
    url: string,
    data?: unknown,
    signal?: AbortSignal,
): Promise<T> {
    const response = await http.getClient().request({
        method,
        url,
        data,
        headers: { Accept: 'application/json' },
        signal,
    });

    return JSON.parse(response.data) as T;
}

async function requestWithoutResponse(
    method: 'post' | 'delete',
    url: string,
): Promise<void> {
    await http.getClient().request({
        method,
        url,
        headers: { Accept: 'application/json' },
    });
}

export async function getJson<T>(
    url: string,
    signal?: AbortSignal,
): Promise<T> {
    return requestJson<T>('get', url, undefined, signal);
}

export async function patchJson<T>(url: string, data: unknown): Promise<T> {
    return requestJson<T>('patch', url, data);
}

export async function postJson<T>(url: string, data: unknown): Promise<T> {
    return requestJson<T>('post', url, data);
}

export async function postWithoutResponse(url: string): Promise<void> {
    return requestWithoutResponse('post', url);
}

export async function deleteWithoutResponse(url: string): Promise<void> {
    return requestWithoutResponse('delete', url);
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
