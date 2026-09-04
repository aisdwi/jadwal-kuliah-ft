import { motion } from "framer-motion";
import { ChevronLeft, ChevronRight, Pencil, Trash2 } from "lucide-react";
import { cn } from "@/lib/utils";
import { ROLE_STYLES, type ApiUser } from "@/pages/users/usersPageConfig";
import type { ReactNode } from "react";

type UsersTableProps = {
    users: ApiUser[];
    isLoading: boolean;
    searchQuery: string;
    visibleStart: number;
    visibleEnd: number;
    totalCount: number;
    page: number;
    totalPages: number;
    onPageChange: (page: number | ((current: number) => number)) => void;
    onEdit: (user: ApiUser) => void;
    onDelete: (user: ApiUser) => void;
    getDisplayName: (user: ApiUser) => string;
};

export function UsersTable({
    users,
    isLoading,
    searchQuery,
    visibleStart,
    visibleEnd,
    totalCount,
    page,
    totalPages,
    onPageChange,
    onEdit,
    onDelete,
    getDisplayName,
}: UsersTableProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="overflow-hidden rounded-2xl border border-white/60 bg-white/75 shadow-xl backdrop-blur-xl"
        >
            <div className="overflow-x-auto">
                <table className="min-w-full border-collapse">
                    <thead>
                        <tr>
                            <HeaderCell className="w-16">No</HeaderCell>
                            <HeaderCell className="min-w-[220px]">Nama</HeaderCell>
                            <HeaderCell className="min-w-[260px]">Email</HeaderCell>
                            <HeaderCell className="min-w-[190px]">Peran</HeaderCell>
                            <HeaderCell className="min-w-[180px]">Jurusan</HeaderCell>
                            <HeaderCell className="w-24">Aksi</HeaderCell>
                        </tr>
                    </thead>
                    <tbody>
                        {isLoading ? (
                            <tr>
                                <td colSpan={6} className="px-7 md:px-8 py-20 text-center">
                                    <div className="flex flex-col items-center justify-center gap-3">
                                        <div className="h-6 w-6 animate-spin rounded-full border-b-2 border-primary" />
                                        <p className="text-sm text-muted-foreground">Memuat data...</p>
                                    </div>
                                </td>
                            </tr>
                        ) : users.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-7 md:px-8 py-12 text-center text-muted-foreground">
                                    {searchQuery
                                        ? "Tidak ada pengguna yang cocok dengan pencarian."
                                        : "Tidak ada data pengguna ditemukan."}
                                </td>
                            </tr>
                        ) : (
                            users.map((item, index) => (
                                <tr
                                    key={item.id}
                                    className="even:bg-white odd:bg-gray-50/70 hover:bg-gray-100 transition-colors"
                                >
                                    <td className="px-4 py-2.5 text-[#333] border border-[#dee2e6] text-sm text-center font-medium">
                                        {visibleStart + index}
                                    </td>
                                    <td className="px-4 py-2.5 text-[#333] border border-[#dee2e6] text-sm font-medium align-center">
                                        <div className="max-w-[240px] break-words">{getDisplayName(item)}</div>
                                    </td>
                                    <td className="px-4 py-2.5 text-[#333] border border-[#dee2e6] text-sm align-center">
                                        <div className="max-w-[300px] break-all text-muted-foreground">
                                            {item.email || "-"}
                                        </div>
                                    </td>
                                    <td className="px-4 py-2.5 border border-[#dee2e6] text-sm text-center">
                                        <span
                                            className={cn(
                                                "inline-flex max-w-[250px] rounded-full px-3 py-0 text-xs font-semibold leading-tight",
                                                ROLE_STYLES[item.role?.role || ""] || "bg-gray-100 text-gray-700",
                                            )}
                                        >
                                            {item.role?.role || "-"}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2.5 text-[#333] border border-[#dee2e6] text-sm align-center">
                                        <div className="max-w-[220px] break-words text-muted-foreground">
                                            {item.jurusan?.nama_jurusan || "-"}
                                        </div>
                                    </td>
                                    <td className="px-4 py-2.5 border border-[#dee2e6] align-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <button
                                                onClick={() => onEdit(item)}
                                                className="flex h-8 w-8 items-center justify-center rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                                title="Edit"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => onDelete(item)}
                                                className="flex h-8 w-8 items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                                title="Hapus"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-between border-t border-border/20 px-7 md:px-8 py-2">
                <p className="text-xs text-muted-foreground">
                    Menampilkan {visibleStart} - {visibleEnd} dari {totalCount} data
                </p>
                <div className="flex items-center gap-1">
                    <button
                        onClick={() => onPageChange((current) => Math.max(1, current - 1))}
                        disabled={page === 1}
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>
                    {Array.from({ length: totalPages }, (_, index) => index + 1).map((pageNumber) => (
                        <button
                            key={pageNumber}
                            onClick={() => onPageChange(pageNumber)}
                            className={cn(
                                "flex h-8 min-w-8 px-2 items-center justify-center rounded-lg text-sm transition-colors",
                                pageNumber === page
                                    ? "bg-primary/20 text-primary font-semibold"
                                    : "text-muted-foreground hover:text-foreground hover:bg-muted/30",
                            )}
                        >
                            {pageNumber}
                        </button>
                    ))}
                    <button
                        onClick={() => onPageChange((current) => Math.min(totalPages, current + 1))}
                        disabled={page === totalPages}
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </motion.div>
    );
}

function HeaderCell({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <th
            className={cn(
                "bg-[#1f6b65] px-4 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-white border border-white/15",
                className,
            )}
        >
            {children}
        </th>
    );
}
