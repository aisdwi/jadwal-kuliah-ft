import { useState, useEffect, useMemo } from "react";
import { motion } from "framer-motion";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Cell,
    type TooltipProps,
} from "recharts";
import type { NameType, ValueType } from "recharts/types/component/DefaultTooltipContent";
import { cachedApiGet, getCachedApiResponse } from "@/lib/api-cache";
import { useAuth } from "@/contexts/AuthContext";
import { useSemester } from "@/contexts/SemesterContext";


const barColors = [
    "hsl(151 58% 36%)",
    "hsl(156 62% 32%)",
    "hsl(147 52% 42%)",
    "hsl(163 52% 38%)",
    "hsl(140 58% 34%)",
    "hsl(38 88% 52%)",
];

const JURUSAN_PROGRESS_ROLES = new Set([
    "Super Admin",
    "Admin Fakultas",
    "Wakil Dekan I Bidang Akademik",
    "Sub-Koordinator Bidang Akademik",
]);

const CustomTooltip = ({ active, payload, label }: TooltipProps<ValueType, NameType>) => {
    if (!active || !payload?.length) return null;
    const item = payload[0].payload;
    return (
        <div className="glass-card rounded-lg px-3 py-2 text-xs">
            <p className="font-semibold text-foreground">{label}</p>
            <p className="text-muted-foreground mt-1">
                Progres: <span className="text-primary font-bold">{payload[0].value}%</span>
            </p>
            <p className="text-muted-foreground">
                {item.terjadwal ?? '-'} / {item.total ?? '-'} kelas terjadwal
            </p>
        </div>
    );
};

interface ChartItem {
    name: string;
    filled: number;
    total: number;
    terjadwal: number;
}

export function SchedulingChart() {
    const { user } = useAuth();
    const { semesterTipe } = useSemester();
    const chartParams = useMemo(() => ({ semester_tipe: semesterTipe }), [semesterTipe]);
    const cachedChart = getCachedApiResponse<ChartItem[]>('/dashboard/chart', { params: chartParams });
    const [data, setData] = useState<ChartItem[]>(cachedChart?.data ?? []);
    const [loading, setLoading] = useState(!cachedChart);

    useEffect(() => {
        setLoading(true);
        cachedApiGet<ChartItem[]>('/dashboard/chart', { params: chartParams })
            .then(res => setData(res.data))
            .catch(err => console.error('Failed to fetch chart data', err))
            .finally(() => setLoading(false));
    }, [chartParams]);

    const chartData = data;
    const isJurusanLevel = JURUSAN_PROGRESS_ROLES.has(user?.role || "");

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5, duration: 0.5 }}
            className="glass-elevated p-4 lg:p-[18px] flex flex-col w-full h-full min-h-[220px]"
        >
            <div className="mb-2 shrink-0">
                <h3 className="text-base lg:text-[1.05rem] font-semibold text-foreground">Progress Penjadwalan</h3>
                <p className="text-xs lg:text-[13px] text-muted-foreground">
                    Persentase jadwal terisi per {isJurusanLevel ? "jurusan" : "program studi"}
                </p>
            </div>
            <div className="flex-1 min-h-0 w-full">
                {loading ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
                        Memuat data chart...
                    </div>
                ) : chartData.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
                        Belum ada data kelas kuliah untuk ditampilkan.
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={chartData} layout="vertical" margin={{ top: 5, right: 30, left: 0, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="hsl(150 20% 82%)" horizontal={false} vertical={true} />
                            <XAxis
                                type="number"
                                domain={[0, 100]}
                                tick={{ fill: "hsl(151 22% 34%)", fontSize: 10 }}
                                axisLine={false}
                                tickLine={false}
                                tickFormatter={(v) => `${v}%`}
                            />
                            <YAxis
                                dataKey="name"
                                type="category"
                                tick={{ fill: "hsl(151 22% 34%)", fontSize: 10 }}
                                axisLine={{ stroke: "hsl(150 20% 82%)" }}
                                tickLine={false}
                                width={126}
                            />
                            <Tooltip content={<CustomTooltip />} cursor={{ fill: "hsl(149 60% 92%)" }} />
                            <Bar dataKey="filled" radius={[0, 8, 8, 0]} barSize={20}>
                                {chartData.map((_, i) => (
                                    <Cell key={i} fill={barColors[i % barColors.length]} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </div>
        </motion.div>
    );
}
