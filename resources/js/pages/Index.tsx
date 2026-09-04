import { useState, useEffect, useMemo } from "react";
import { BookOpen, CalendarCheck, DoorOpen, GraduationCap, MapPinned, Users } from "lucide-react";
import { motion } from "framer-motion";
import { SummaryCard } from "@/components/dashboard/SummaryCard";
import { SchedulingChart } from "@/components/dashboard/SchedulingChart";
import { cachedApiGet, getCachedApiResponse } from "@/lib/api-cache";
import { useAuth } from "@/contexts/AuthContext";
import { useSemester } from "@/contexts/SemesterContext";

interface DashboardStats {
    totalDosen: number;
    totalRuangan: number;
    totalMataKuliah: number;
    totalKelasKuliah: number;
    totalKelasKuliahTerjadwal?: number;
    totalMataKuliahTerjadwal: number;
}

const getSummaryData = (stats: DashboardStats, loading: boolean) => {
    // Dashboard release metrics come from the API; keep display fallbacks local only.
    const kelasTerjadwal = stats.totalKelasKuliahTerjadwal ?? stats.totalMataKuliahTerjadwal;
    const persentaseTerisi = stats.totalKelasKuliah > 0
        ? Math.round((kelasTerjadwal / stats.totalKelasKuliah) * 100)
        : 0;

    return [
        {
            icon: Users,
            label: "Total Dosen",
            value: loading ? "..." : stats.totalDosen,
            trend: `${stats.totalDosen} dosen terdaftar di sistem`,
            trendColor: "text-[hsl(151,58%,36%)]",
            color: "text-primary",
        },
        {
            icon: DoorOpen,
            label: "Total Ruangan",
            value: loading ? "..." : stats.totalRuangan,
            color: "text-accent",
        },
        {
            icon: BookOpen,
            label: "Mata Kuliah Aktif",
            value: loading ? "..." : stats.totalMataKuliah,
            trend: "Mata kuliah tersedia untuk dijadwalkan",
            trendColor: "text-[hsl(151,58%,36%)]",
            color: "text-[hsl(147,52%,42%)]",
        },
        {
            icon: CalendarCheck,
            label: "Progres Jadwal",
            value: loading ? "..." : `${persentaseTerisi}%`,
            trend: `${kelasTerjadwal}/${stats.totalKelasKuliah} kelas kuliah sudah dijadwalkan`,
            trendColor: "text-[hsl(156,62%,32%)]",
            color: "text-[hsl(140,58%,34%)]",
        },
    ];
};

interface ActivityItem {
    id: number;
    title: string;
    detail: string;
    time: string;
}

const getDescriptionByRole = (role: string | undefined) => {
    switch (role) {
        case "Admin Jurusan":
            return {
                description: "Kelola data jadwal untuk seluruh program studi di jurusan Anda."
            };
        case "Ketua Jurusan":
            return {
                description: "Pantau progres penjadwalan dan data akademik pada jurusan Anda."
            };
        case "Koordinator Program Studi":
            return {
                description: "Pantau progres penjadwalan dan data akademik pada program studi Anda."
            };
        case "Admin Fakultas":
            return {
                description: "Kelola sistem penjadwalan untuk seluruh program studi di fakultas."
            };
        case "Super Admin":
            return {
                description: "Akses lengkap ke sistem - kelola semua aspek penjadwalan perkuliahan."
            };
        default:
            return {
                description: "Ringkasan penjadwalan terbaru dan akses cepat untuk tugas harian."
            };
    }
};

const getDashboardGreeting = (user: ReturnType<typeof useAuth>["user"]) => {
    const role = (user?.role || "").trim();
    if (!role) return "Pengguna";

    if (role === "Admin Jurusan" || role === "Ketua Jurusan") {
        return [role, user?.jurusan_name].filter(Boolean).join(" ");
    }

    if (role === "Koordinator Program Studi") {
        return [role, user?.program_studi_name].filter(Boolean).join(" ");
    }

    return role;
};


const Index = () => {
    const { user } = useAuth();
    const { semesterTipe } = useSemester();
    const dashboardParams = useMemo(() => ({ semester_tipe: semesterTipe }), [semesterTipe]);
    const cachedStats = getCachedApiResponse<DashboardStats>('/dashboard/stats', { params: dashboardParams });
    const cachedActivities = getCachedApiResponse<ActivityItem[]>('/dashboard/activity');
    const [stats, setStats] = useState<DashboardStats>(cachedStats?.data ?? {
        totalDosen: 0,
        totalRuangan: 0,
        totalMataKuliah: 0,
        totalKelasKuliah: 0,
        totalKelasKuliahTerjadwal: 0,
        totalMataKuliahTerjadwal: 0,
    });
    const [loading, setLoading] = useState(!cachedStats);
    const [activities, setActivities] = useState<ActivityItem[]>(cachedActivities?.data ?? []);
    const [activityLoading, setActivityLoading] = useState(!cachedActivities);

    useEffect(() => {
        const fetchStats = async () => {
            setLoading(true);
            try {
                const response = await cachedApiGet<DashboardStats>('/dashboard/stats', { params: dashboardParams });
                setStats(response.data);
            } catch (error) {
                console.error("Gagal mengambil statistik dashboard", error);
            } finally {
                setLoading(false);
            }
        };

        const fetchActivity = async () => {
            try {
                const response = await cachedApiGet<ActivityItem[]>('/dashboard/activity');
                setActivities(response.data);
            } catch (error) {
                console.error("Gagal mengambil aktivitas", error);
            } finally {
                setActivityLoading(false);
            }
        };

        fetchStats();
        fetchActivity();
    }, [dashboardParams]);

    const summaryCards = getSummaryData(stats, loading);
    const { description } = getDescriptionByRole(user?.role);
    const dashboardGreeting = getDashboardGreeting(user);
    const scopeBadges = [
        user?.jurusan_name ? { icon: MapPinned, label: "Jurusan", value: user.jurusan_name } : null,
        user?.program_studi_name ? { icon: GraduationCap, label: "Program Studi", value: user.program_studi_name } : null,
    ].filter(Boolean) as Array<{ icon: typeof MapPinned; label: string; value: string }>;
    const visibleActivities = activities.slice(0, 10);

    return (
        <div className="space-y-4 lg:space-y-4 flex flex-col flex-1 min-h-0 h-full overflow-hidden">
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35 }} className="shrink-0">
                <h1 className="text-2xl font-bold text-foreground lg:text-[2.35rem] lg:leading-tight">
                    Selamat Datang, <span className="text-gradient">{dashboardGreeting}</span>
                </h1>
                <p className="mt-1 text-sm text-muted-foreground lg:text-[15px]">
                    {description}
                </p>
                {scopeBadges.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                        {scopeBadges.map(({ icon: Icon, label, value }) => (
                            <span
                                key={label}
                                className="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/55 px-3 py-1.5 text-xs font-semibold text-emerald-900 shadow-sm backdrop-blur-md"
                            >
                                <Icon className="h-3.5 w-3.5 text-emerald-700" />
                                <span className="text-muted-foreground">{label}:</span>
                                <span>{value}</span>
                            </span>
                        ))}
                    </div>
                )}
            </motion.div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4 shrink-0">
                {summaryCards.map((card, i) => (
                    <SummaryCard key={card.label} {...card} index={i} />
                ))}
            </div>

            <div className="grid grid-cols-1 gap-3 lg:gap-4 lg:grid-cols-12 flex-1 min-h-0 pb-0">
                <div className="lg:col-span-8 flex flex-col h-full min-h-0 overflow-hidden">
                    <SchedulingChart />
                </div>

                <div className="lg:col-span-4 flex flex-col h-full min-h-0 overflow-hidden">
                    <motion.section
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="glass-elevated p-4 lg:p-[18px] h-full flex flex-col min-h-0 overflow-hidden"
                    >
                        <div className="mb-3 flex items-center justify-between shrink-0">
                            <h2 className="text-base lg:text-[1.05rem] font-semibold text-foreground">Aktivitas Terbaru</h2>
                            <span className="text-[11px] text-muted-foreground">Terbaru</span>
                        </div>
                        <div className="space-y-3 flex-1 overflow-y-auto min-h-0 pr-1 overscroll-contain [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            {activityLoading ? (
                                <p className="text-sm text-muted-foreground text-center py-4">Memuat aktivitas...</p>
                            ) : visibleActivities.length === 0 ? (
                                <p className="text-sm text-muted-foreground text-center py-4">Belum ada aktivitas tercatat.</p>
                            ) : visibleActivities.map((item) => (
                                <article
                                    key={item.id}
                                    className="rounded-xl border border-white/70 bg-white/55 px-3 py-2.5"
                                >
                                    <p className="text-[13px] lg:text-[13.5px] font-medium text-foreground leading-5">{item.title}</p>
                                    <div className="mt-1 flex items-center justify-between text-[11px] text-muted-foreground">
                                        <span className="truncate mr-2">{item.detail}</span>
                                        <span className="shrink-0">{item.time}</span>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </motion.section>
                </div>
            </div>
        </div>
    );
};

export default Index;
