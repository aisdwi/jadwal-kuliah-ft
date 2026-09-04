import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import api from "@/lib/api";
import { invalidateApiCache } from "@/lib/api-cache";
import { getUserFriendlyError } from "@/lib/error-messages";
import {
    clearLegacyPersistentAuth,
    clearStoredAuth,
    getStoredLastActivity,
    getStoredAuthUser,
    setStoredLastActivity,
    setStoredAuthToken,
    setStoredAuthUser,
} from "@/lib/auth-storage";

export type RoleName = "Super Admin" | "Admin Fakultas" | "Admin Jurusan" | string;

interface AuthUser {
    id: number;
    nama_user: string;
    email: string;
    role_id: number;
    role: RoleName;
    jurusan_id: number | null;
    jurusan_name: string | null;
    program_studi_id: number | null;
    program_studi_name: string | null;
}

interface AuthContextValue {
    isAuthenticated: boolean;
    user: AuthUser | null;
    login: (email: string, password: string) => Promise<{ success: boolean; message?: string }>;
    logout: () => void;
    isSuperAdmin: boolean;
    isAdminFakultas: boolean;
    isAdminJurusan: boolean;
    isJurusanRestricted: boolean;
    canSchedule: boolean;
    canManageUsers: boolean;
    canEditData: boolean;
    canEditRuangan: boolean;
    isAcademicObserver: boolean;
    defaultsToOwnJurusan: boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
const IDLE_TIMEOUT_MS = 5 * 60 * 1000;

function normalizeRoleName(role: RoleName | null | undefined): RoleName {
    if (role === "Admin Prodi") return "Admin Jurusan";
    if (role === "Kajur") return "Ketua Jurusan";
    if (role === "Kaprodi") return "Koordinator Program Studi";

    return role || "";
}

export function useAuth(): AuthContextValue {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error("useAuth must be used within AuthProvider");
    return ctx;
}

export default function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<AuthUser | null>(() => {
        clearLegacyPersistentAuth();

        const stored = getStoredAuthUser();
        if (!stored) return null;

        const lastActivity = getStoredLastActivity();
        if (lastActivity && Date.now() - lastActivity >= IDLE_TIMEOUT_MS) {
            clearStoredAuth();
            return null;
        }

        const parsed = JSON.parse(stored) as AuthUser;
        return { ...parsed, role: normalizeRoleName(parsed.role) };
    });

    const isAuthenticated = !!user;

    // Role helpers
    const normalizedRole = normalizeRoleName(user?.role);
    const isSuperAdmin = normalizedRole === "Super Admin";
    const isAdminFakultas = normalizedRole === "Admin Fakultas";
    const isAdminJurusan = normalizedRole === "Admin Jurusan";
    const isJurusanScopedRole = normalizedRole === "Admin Jurusan";
    const isAcademicObserver = [
        "Ketua Jurusan",
        "Koordinator Program Studi",
        "Wakil Dekan I Bidang Akademik",
        "Sub-Koordinator Bidang Akademik",
    ].includes(normalizedRole);
    const defaultsToOwnJurusan = [
        "Admin Jurusan",
        "Ketua Jurusan",
        "Koordinator Program Studi",
    ].includes(normalizedRole);

    const isJurusanRestricted = isJurusanScopedRole;

    // Permission helpers
    const canSchedule = isAdminJurusan;
    const canManageUsers = isSuperAdmin || isAdminFakultas;
    const canEditData = isSuperAdmin || isAdminFakultas || isAdminJurusan;
    const canEditRuangan = isSuperAdmin || isAdminFakultas || isAdminJurusan;

    const login = useCallback(async (email: string, password: string): Promise<{ success: boolean; message?: string }> => {
        try {
            const response = await api.post("/auth/login", { email, password });
            const { token, user: userData } = response.data;
            const normalizedUser = { ...userData, role: normalizeRoleName(userData.role) };

            invalidateApiCache();
            setStoredAuthToken(token);
            setStoredAuthUser(normalizedUser);
            setStoredLastActivity();
            setUser(normalizedUser);
            return { success: true };
        } catch (error: any) {
            return {
                success: false,
                message: getUserFriendlyError(error, { context: "login" }),
            };
        }
    }, []);

    const logout = useCallback(async () => {
        try {
            await api.post("/auth/logout");
        } catch {
            // ignore
        } finally {
            invalidateApiCache();
            clearStoredAuth();
            setUser(null);
        }
    }, []);

    useEffect(() => {
        if (!user || typeof window === "undefined") {
            return;
        }

        let timeoutId: number | undefined;
        const activityEvents = ["click", "keydown", "mousemove", "scroll", "touchstart"];

        const getIdleElapsed = () => {
            const lastActivity = getStoredLastActivity();
            if (!lastActivity) return 0;

            return Date.now() - lastActivity;
        };

        const checkIdleTimeout = () => {
            if (timeoutId !== undefined) {
                window.clearTimeout(timeoutId);
            }

            const elapsed = getIdleElapsed();
            const remaining = IDLE_TIMEOUT_MS - elapsed;

            if (remaining <= 0) {
                logout();
                return;
            }

            timeoutId = window.setTimeout(checkIdleTimeout, remaining);
        };

        const recordActivity = () => {
            setStoredLastActivity();
            checkIdleTimeout();
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === "visible") {
                checkIdleTimeout();
            }
        };

        activityEvents.forEach((eventName) => {
            window.addEventListener(eventName, recordActivity, { passive: true });
        });
        document.addEventListener("visibilitychange", handleVisibilityChange);
        window.addEventListener("focus", checkIdleTimeout);
        window.addEventListener("pageshow", checkIdleTimeout);
        if (!getStoredLastActivity()) {
            setStoredLastActivity();
        }
        checkIdleTimeout();

        return () => {
            if (timeoutId !== undefined) {
                window.clearTimeout(timeoutId);
            }
            activityEvents.forEach((eventName) => {
                window.removeEventListener(eventName, recordActivity);
            });
            document.removeEventListener("visibilitychange", handleVisibilityChange);
            window.removeEventListener("focus", checkIdleTimeout);
            window.removeEventListener("pageshow", checkIdleTimeout);
        };
    }, [logout, user]);

    const value = useMemo(() => ({
        isAuthenticated, user, login, logout,
        isSuperAdmin, isAdminFakultas, isAdminJurusan, isJurusanRestricted,
        canSchedule, canManageUsers, canEditData, canEditRuangan,
        isAcademicObserver, defaultsToOwnJurusan
    }), [isAuthenticated, user, login, logout, isSuperAdmin, isAdminFakultas, isAdminJurusan, isJurusanRestricted, canSchedule, canManageUsers, canEditData, canEditRuangan, isAcademicObserver, defaultsToOwnJurusan]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
