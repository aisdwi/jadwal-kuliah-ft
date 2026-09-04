import { motion } from "framer-motion";
import { type LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";

interface SummaryCardProps {
    icon: LucideIcon;
    label: string;
    value: number | string;
    trend?: string;
    trendColor?: string;
    color: string;
    index: number;
}

export function SummaryCard({ icon: Icon, label, value, trend, trendColor, color, index }: SummaryCardProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: index * 0.1, duration: 0.4 }}
            className="glass-elevated group relative overflow-hidden"
        >
            {/* Background icon */}
            <Icon className="absolute -right-6 -bottom-6 h-24 w-24 text-foreground/[0.05] group-hover:text-foreground/[0.08] transition-colors duration-300" strokeWidth={0.5} />

            {/* Gradient accent */}
            <div className="absolute -mt-20 -mr-20 h-36 w-36 rounded-full bg-gradient-to-br from-primary/10 to-accent/10 blur-3xl" />

            <div className="relative z-10 p-4 lg:p-[18px]">
                <div className={`inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/50 backdrop-blur-lg border border-white/70 ${color} mb-2.5 shadow-sm`}>
                    <Icon className="h-[18px] w-[18px]" />
                </div>
                <p className="text-sm font-medium text-muted-foreground">{label}</p>
                <p className="mt-1 text-[2.15rem] leading-none font-bold text-foreground tracking-tight">{value}</p>
                {trend && (
                    <p className={cn("mt-2 text-[11px] font-semibold line-clamp-2 leading-5 h-10", trendColor || "text-accent")}>{trend}</p>
                )}
            </div>
        </motion.div>
    );
}
