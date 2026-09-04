import { useCallback, useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Bell, CheckCheck, Clock3, Loader2 } from "lucide-react";
import api from "@/lib/api";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

type AppNotification = {
    id: number;
    type: string;
    title: string;
    message: string | null;
    data: {
        url?: string;
        status?: string;
        scope_label?: string;
    } | null;
    read_at: string | null;
    created_at: string;
};

type NotificationBellProps = {
    userId?: number | string | null;
};

function formatNotificationTime(value: string) {
    const timestamp = new Date(value).getTime();
    const diffMs = Date.now() - timestamp;
    const diffMinutes = Math.max(0, Math.floor(diffMs / 60000));

    if (diffMinutes < 1) return "baru saja";
    if (diffMinutes < 60) return `${diffMinutes} menit lalu`;

    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours} jam lalu`;

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
    }).format(new Date(value));
}

export function NotificationBell({ userId }: NotificationBellProps) {
    const navigate = useNavigate();
    const [notifications, setNotifications] = useState<AppNotification[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const fetchNotifications = useCallback(async () => {
        if (!userId) return;

        setLoading(true);
        try {
            const response = await api.get<{
                data: AppNotification[];
                unread_count: number;
            }>("/notifications", { params: { limit: 10 } });

            setNotifications(response.data.data);
            setUnreadCount(response.data.unread_count);
        } catch (error) {
            console.error("Gagal mengambil notifikasi", error);
        } finally {
            setLoading(false);
        }
    }, [userId]);

    useEffect(() => {
        fetchNotifications();

        const intervalId = window.setInterval(fetchNotifications, 30000);

        return () => window.clearInterval(intervalId);
    }, [fetchNotifications]);

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);
        if (nextOpen) {
            fetchNotifications();
        }
    };

    const handleReadNotification = async (notification: AppNotification) => {
        try {
            const response = await api.patch<{ unread_count: number }>(`/notifications/${notification.id}/read`);
            setUnreadCount(response.data.unread_count);
            setNotifications((items) => items.map((item) => (
                item.id === notification.id ? { ...item, read_at: item.read_at ?? new Date().toISOString() } : item
            )));

            if (notification.data?.url) {
                navigate(notification.data.url);
            }
        } catch (error) {
            console.error("Gagal menandai notifikasi", error);
        }
    };

    const handleReadAllNotifications = async () => {
        try {
            await api.patch("/notifications/read-all");
            setUnreadCount(0);
            setNotifications((items) => items.map((item) => (
                { ...item, read_at: item.read_at ?? new Date().toISOString() }
            )));
        } catch (error) {
            console.error("Gagal menandai semua notifikasi", error);
        }
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <button className="relative flex h-10 w-10 items-center justify-center rounded-full glass-card text-muted-foreground hover:text-foreground transition-all duration-200 hover:shadow-lg">
                    <Bell className="h-5 w-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -top-1.5 -right-1.5 min-w-5 h-5 rounded-full bg-primary px-1.5 text-[10px] font-bold leading-5 text-white shadow-lg border-2 border-white/85">
                            {unreadCount > 9 ? "9+" : unreadCount}
                        </span>
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-96 max-w-[calc(100vw-1.5rem)] border-white/80 bg-white/95 backdrop-blur-md p-0 overflow-hidden">
                <div className="flex items-center justify-between border-b border-primary/10 px-4 py-3">
                    <div>
                        <DropdownMenuLabel className="p-0 text-sm font-semibold text-foreground">
                            Notifikasi
                        </DropdownMenuLabel>
                        <p className="mt-0.5 text-[11px] text-muted-foreground">
                            Status generate jadwal background
                        </p>
                    </div>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={handleReadAllNotifications}
                            className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-primary/15 bg-primary/5 px-2.5 text-[11px] font-semibold text-primary hover:bg-primary/10"
                        >
                            <CheckCheck className="h-3.5 w-3.5" />
                            Tandai dibaca
                        </button>
                    )}
                </div>

                <div className="max-h-96 overflow-y-auto p-2">
                    {loading && notifications.length === 0 ? (
                        <div className="flex items-center justify-center gap-2 px-4 py-8 text-sm text-muted-foreground">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Memuat notifikasi...
                        </div>
                    ) : notifications.length === 0 ? (
                        <div className="px-4 py-8 text-center">
                            <Bell className="mx-auto h-8 w-8 text-primary/35" />
                            <p className="mt-2 text-sm font-medium text-foreground">Belum ada notifikasi</p>
                            <p className="mt-1 text-xs text-muted-foreground">Hasil generate jadwal akan muncul di sini.</p>
                        </div>
                    ) : notifications.map((notification) => (
                        <button
                            type="button"
                            key={notification.id}
                            onClick={() => handleReadNotification(notification)}
                            className="mb-1 w-full rounded-xl border border-transparent px-3 py-2.5 text-left transition hover:border-primary/15 hover:bg-primary/5"
                        >
                            <div className="flex items-start gap-2.5">
                                <span className={`mt-1 h-2.5 w-2.5 rounded-full ${notification.read_at ? "bg-muted" : "bg-primary"}`} />
                                <span className="min-w-0 flex-1">
                                    <span className="flex items-center justify-between gap-2">
                                        <span className="truncate text-sm font-semibold text-foreground">
                                            {notification.title}
                                        </span>
                                        <span className="inline-flex shrink-0 items-center gap-1 text-[10px] text-muted-foreground">
                                            <Clock3 className="h-3 w-3" />
                                            {formatNotificationTime(notification.created_at)}
                                        </span>
                                    </span>
                                    {notification.message && (
                                        <span className="mt-1 line-clamp-2 block text-xs leading-5 text-muted-foreground">
                                            {notification.message}
                                        </span>
                                    )}
                                    {notification.data?.scope_label && (
                                        <span className="mt-1.5 inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">
                                            {notification.data.scope_label}
                                        </span>
                                    )}
                                </span>
                            </div>
                        </button>
                    ))}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
