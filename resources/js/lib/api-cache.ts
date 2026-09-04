import type { AxiosRequestConfig, AxiosResponse } from "axios";
import api from "@/lib/api";
import { getStoredAuthToken, getStoredAuthUser } from "@/lib/auth-storage";

export const DEFAULT_GET_CACHE_TTL_MS = 5 * 60 * 1000;

type CacheEntry = {
    response: AxiosResponse<unknown>;
    cachedAt: number;
    ttlMs: number;
};

type CachedGetOptions = {
    ttlMs?: number;
    force?: boolean;
};

const responseCache = new Map<string, CacheEntry>();
const pendingRequests = new Map<string, Promise<AxiosResponse<unknown>>>();
const CACHE_SCOPE_SEPARATOR = "::";

function getAuthCacheScope() {
    if (typeof window === "undefined") {
        return "auth:server";
    }

    const storedUser = getStoredAuthUser();
    if (storedUser) {
        try {
            const user = JSON.parse(storedUser) as {
                id?: number | string;
                role_id?: number | string;
                jurusan_id?: number | string | null;
                program_studi_id?: number | string | null;
            };

            return [
                "auth:user",
                user.id ?? "unknown",
                "role",
                user.role_id ?? "unknown",
                "jurusan",
                user.jurusan_id ?? "all",
                "prodi",
                user.program_studi_id ?? "all",
            ].join(":");
        } catch {
            // Fall back to token presence if stored user data is temporarily invalid.
        }
    }

    const token = getStoredAuthToken();
    return token ? `auth:token:${token.slice(0, 12)}` : "auth:guest";
}

function normalizeParamValue(value: unknown): string {
    if (Array.isArray(value)) {
        return value.map(normalizeParamValue).join(",");
    }

    if (value && typeof value === "object") {
        return JSON.stringify(value);
    }

    return String(value);
}

function normalizeParams(params: unknown): string {
    if (!params) {
        return "";
    }

    if (params instanceof URLSearchParams) {
        return params.toString();
    }

    if (typeof params !== "object") {
        return String(params);
    }

    const entries = Object.entries(params as Record<string, unknown>)
        .filter(([, value]) => value !== undefined && value !== null && value !== "")
        .sort(([left], [right]) => left.localeCompare(right));

    return new URLSearchParams(entries.map(([key, value]) => [key, normalizeParamValue(value)])).toString();
}

export function buildApiCacheKey(url: string, config?: AxiosRequestConfig) {
    const params = normalizeParams(config?.params);
    const requestKey = params ? `${url}?${params}` : url;
    return `${getAuthCacheScope()}${CACHE_SCOPE_SEPARATOR}${requestKey}`;
}

export function getCachedApiResponse<T = unknown>(
    url: string | undefined,
    config?: AxiosRequestConfig,
    ttlMs = DEFAULT_GET_CACHE_TTL_MS,
) {
    if (!url) {
        return null;
    }

    const cached = responseCache.get(buildApiCacheKey(url, config));
    if (!cached || Date.now() - cached.cachedAt > Math.min(cached.ttlMs, ttlMs)) {
        return null;
    }

    return cached.response as AxiosResponse<T>;
}

export async function cachedApiGet<T = unknown>(
    url: string,
    config?: AxiosRequestConfig,
    options: CachedGetOptions = {},
) {
    const ttlMs = options.ttlMs ?? DEFAULT_GET_CACHE_TTL_MS;
    const cacheKey = buildApiCacheKey(url, config);

    if (!options.force) {
        const cached = getCachedApiResponse<T>(url, config, ttlMs);
        if (cached) {
            return cached;
        }

        const pending = pendingRequests.get(cacheKey);
        if (pending) {
            return pending as Promise<AxiosResponse<T>>;
        }
    }

    const request = api.get<T>(url, config).then((response) => {
        responseCache.set(cacheKey, {
            response: response as AxiosResponse<unknown>,
            cachedAt: Date.now(),
            ttlMs,
        });

        return response;
    });

    pendingRequests.set(cacheKey, request as Promise<AxiosResponse<unknown>>);

    try {
        return await request;
    } finally {
        pendingRequests.delete(cacheKey);
    }
}

export function invalidateApiCache(prefixes?: string | Array<string | undefined | null>) {
    if (!prefixes) {
        responseCache.clear();
        pendingRequests.clear();
        return;
    }

    const normalizedPrefixes = (Array.isArray(prefixes) ? prefixes : [prefixes]).filter(Boolean) as string[];
    const matches = (key: string) => normalizedPrefixes.some((prefix) => (
        key.startsWith(prefix) || key.includes(`${CACHE_SCOPE_SEPARATOR}${prefix}`)
    ));

    for (const key of responseCache.keys()) {
        if (matches(key)) {
            responseCache.delete(key);
        }
    }

    for (const key of pendingRequests.keys()) {
        if (matches(key)) {
            pendingRequests.delete(key);
        }
    }
}
