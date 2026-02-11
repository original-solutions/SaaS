import { ref, type Ref } from 'vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

type ApiFunction<T> = (...args: unknown[]) => Promise<{ data: T }>;

interface UseApiReturn<T> {
    data: Ref<T | null>;
    error: Ref<string | null>;
    errors: Ref<Record<string, string>>;
    isLoading: Ref<boolean>;
    execute: (...args: Parameters<ApiFunction<T>>) => Promise<T | null>;
}

/**
 * Composable for wrapping async API calls with loading/error state.
 */
export function useApi<T>(fn: ApiFunction<T>): UseApiReturn<T> {
    const data = ref<T | null>(null) as Ref<T | null>;
    const error = ref<string | null>(null);
    const errors = ref<Record<string, string>>({});
    const isLoading = ref(false);

    async function execute(...args: Parameters<ApiFunction<T>>): Promise<T | null> {
        isLoading.value = true;
        error.value = null;
        errors.value = {};

        try {
            const response = await fn(...args);
            data.value = response.data;
            return response.data;
        } catch (err) {
            const axiosError = err as AxiosError<ApiError>;
            if (axiosError.response?.data?.errors) {
                errors.value = Object.fromEntries(
                    Object.entries(axiosError.response.data.errors).map(([k, v]) => [k, v[0]]),
                );
            }
            error.value = axiosError.response?.data?.message ?? 'An error occurred.';
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    return { data, error, errors, isLoading, execute };
}
