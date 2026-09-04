import axios from 'axios';
import { clearLegacyPersistentAuth, clearStoredAuth, getStoredAuthToken } from '@/lib/auth-storage';

clearLegacyPersistentAuth();

const DEFAULT_API_ORIGIN = (() => {
    if (typeof window === 'undefined') {
        return '';
    }

    if (window.location.hostname === 'localhost') {
        return 'http://localhost:8000';
    }

    if (window.location.hostname === '127.0.0.1') {
        return 'http://127.0.0.1:8000';
    }

    return window.location.origin;
})();

const resolveApiBaseUrl = () => {
    const configuredUrl = import.meta.env.VITE_API_BASE_URL;

    if (!configuredUrl) {
        return `${DEFAULT_API_ORIGIN}/api/v2`;
    }

    if (typeof window === 'undefined') {
        return configuredUrl;
    }

    const isRunningOnLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    const pointsToLocalhost = configuredUrl.includes('localhost') || configuredUrl.includes('127.0.0.1');

    return pointsToLocalhost && !isRunningOnLocalhost
        ? `${window.location.origin}/api/v2`
        : configuredUrl;
};

const API_BASE_URL = resolveApiBaseUrl();

export const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: false,
});

// Attach token from per-tab session storage automatically.
api.interceptors.request.use((config) => {
    const token = getStoredAuthToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 by clearing token
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            clearStoredAuth();
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
