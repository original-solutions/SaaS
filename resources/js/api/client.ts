import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios';
import type { ApiError } from './types';

const api: AxiosInstance = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

// Request interceptor: attach access token and tenant header
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const tenantId = localStorage.getItem('current_tenant_id');
    if (tenantId) {
        config.headers['X-Tenant-ID'] = tenantId;
    }

    return config;
});

// Response interceptor: handle 401 with token refresh
let isRefreshing = false;
let failedQueue: Array<{
    resolve: (value: unknown) => void;
    reject: (reason: unknown) => void;
}> = [];

function processQueue(error: unknown, token: string | null = null): void {
    failedQueue.forEach(({ resolve, reject }) => {
        if (error) {
            reject(error);
        } else {
            resolve(token);
        }
    });
    failedQueue = [];
}

api.interceptors.response.use(
    (response) => response,
    async (error: AxiosError<ApiError>) => {
        const originalRequest = error.config;

        if (!originalRequest || error.response?.status !== 401) {
            return Promise.reject(error);
        }

        // Don't retry refresh or login requests
        if (
            originalRequest.url?.includes('/auth/refresh') ||
            originalRequest.url?.includes('/auth/login')
        ) {
            return Promise.reject(error);
        }

        if (isRefreshing) {
            return new Promise((resolve, reject) => {
                failedQueue.push({ resolve, reject });
            }).then((token) => {
                if (originalRequest.headers) {
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                }
                return api(originalRequest);
            });
        }

        isRefreshing = true;

        try {
            const refreshToken = localStorage.getItem('refresh_token');
            if (!refreshToken) {
                throw new Error('No refresh token');
            }

            const { data } = await axios.post('/api/v1/auth/refresh', {
                refresh_token: refreshToken,
            });

            const newAccessToken = data.access_token as string;
            const newRefreshToken = data.refresh_token as string;

            localStorage.setItem('access_token', newAccessToken);
            localStorage.setItem('refresh_token', newRefreshToken);

            processQueue(null, newAccessToken);

            if (originalRequest.headers) {
                originalRequest.headers.Authorization = `Bearer ${newAccessToken}`;
            }

            return api(originalRequest);
        } catch (refreshError) {
            processQueue(refreshError, null);
            localStorage.removeItem('access_token');
            localStorage.removeItem('refresh_token');
            localStorage.removeItem('current_tenant_id');
            window.location.href = '/login';
            return Promise.reject(refreshError);
        } finally {
            isRefreshing = false;
        }
    },
);

export default api;
