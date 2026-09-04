const AUTH_TOKEN_KEY = "auth_token";
const AUTH_USER_KEY = "auth_user";
const AUTH_LAST_ACTIVITY_KEY = "auth_last_activity_at";

function browserStorage(): Storage | null {
    if (typeof window === "undefined") {
        return null;
    }

    return window.sessionStorage;
}

export function clearLegacyPersistentAuth(): void {
    if (typeof window === "undefined") {
        return;
    }

    window.localStorage.removeItem(AUTH_TOKEN_KEY);
    window.localStorage.removeItem(AUTH_USER_KEY);
}

export function getStoredAuthToken(): string | null {
    return browserStorage()?.getItem(AUTH_TOKEN_KEY) ?? null;
}

export function setStoredAuthToken(token: string): void {
    browserStorage()?.setItem(AUTH_TOKEN_KEY, token);
}

export function removeStoredAuthToken(): void {
    browserStorage()?.removeItem(AUTH_TOKEN_KEY);
    clearLegacyPersistentAuth();
}

export function getStoredAuthUser(): string | null {
    return browserStorage()?.getItem(AUTH_USER_KEY) ?? null;
}

export function setStoredAuthUser(user: unknown): void {
    browserStorage()?.setItem(AUTH_USER_KEY, JSON.stringify(user));
}

export function removeStoredAuthUser(): void {
    browserStorage()?.removeItem(AUTH_USER_KEY);
    clearLegacyPersistentAuth();
}

export function getStoredLastActivity(): number | null {
    const raw = browserStorage()?.getItem(AUTH_LAST_ACTIVITY_KEY);
    if (!raw) return null;

    const value = Number(raw);
    return Number.isFinite(value) ? value : null;
}

export function setStoredLastActivity(timestamp = Date.now()): void {
    browserStorage()?.setItem(AUTH_LAST_ACTIVITY_KEY, String(timestamp));
}

export function removeStoredLastActivity(): void {
    browserStorage()?.removeItem(AUTH_LAST_ACTIVITY_KEY);
}

export function clearStoredAuth(): void {
    removeStoredAuthToken();
    removeStoredAuthUser();
    removeStoredLastActivity();
}
