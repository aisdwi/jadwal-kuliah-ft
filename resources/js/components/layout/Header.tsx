import { useLocation, useNavigate } from "react-router-dom";
import { Calendar, ChevronRight, LogOut } from "lucide-react";
import { useAuth } from "@/hooks/useAuth";
import { useSemester } from "@/contexts/SemesterContext";
import { NotificationBell } from "@/components/layout/NotificationBell";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

const routeLabels: Record<string, string> = {
    "/": "Dashboard",
    "/master/dosen": "Data Dosen",
    "/master/ruangan": "Data Ruangan",
    "/master/matakuliah": "Mata Kuliah",
    "/master/jurusan": "Jurusan",
    "/master/waktu": "Jam Kuliah",
    "/master/kelas": "Data Kelas",
    "/master/kelas-kuliah": "Kelas Perkuliahan",
    "/master/slot": "Slot Jadwal",
    "/scheduling/list": "Jadwal - Table View",
    "/scheduling/editor": "Jadwal - Timetable View",
    "/scheduling/auto": "Jadwal - Generate Otomatis",
    "/master/users": "Pengguna",
};

export function Header() {
    const location = useLocation();
    const navigate = useNavigate();
    const { user, logout } = useAuth();
    const userName = user?.nama_user ?? "Admin";
    const userRole = user?.role ?? "Pengguna";
    const currentLabel = routeLabels[location.pathname] || "Halaman";
    const segments = location.pathname.split("/").filter(Boolean);
    const { semesterTipe, setSemesterTipe } = useSemester();

    const handleLogout = async () => {
        await logout();
        navigate("/login", { replace: true });
    };

    return (
        <header className="sticky top-0 z-30 glass-elevated border-b border-white/50 shadow-xl">
            <div className="px-5 py-3.5 flex items-center justify-between gap-4">
                {/* Breadcrumbs */}
                <div className="flex items-center gap-1.5 text-sm">
                    <span className="text-muted-foreground">Beranda</span>
                    {segments.map((seg, i) => (
                        <span key={i} className="flex items-center gap-1.5">
                            <ChevronRight className="h-3 w-3 text-muted-foreground/50" />
                            <span className={i === segments.length - 1 ? "text-foreground font-semibold" : "text-muted-foreground capitalize"}>
                                {i === segments.length - 1 ? currentLabel : seg}
                            </span>
                        </span>
                    ))}
                    {segments.length === 0 && (
                        <>
                            <ChevronRight className="h-3 w-3 text-muted-foreground/50" />
                            <span className="text-foreground font-semibold">Dashboard</span>
                        </>
                    )}
                </div>

                {/* Right actions */}
                <div className="flex items-center gap-3">
                    <div className="hidden md:flex items-center gap-2 mr-2">
                        <Calendar className="w-4 h-4 text-primary" />
                        <Select
                            value={semesterTipe}
                            onValueChange={(val: any) => setSemesterTipe(val)}
                        >
                            <SelectTrigger className="w-[160px] h-9 bg-white/50 backdrop-blur-md border-white/40 focus:ring-primary shadow-sm rounded-xl text-sm font-medium">
                                <SelectValue placeholder="Pilih Semester" />
                            </SelectTrigger>
                            <SelectContent className="bg-white/95 backdrop-blur-xl border-white/80 rounded-xl shadow-xl">
                                <SelectItem value="ganjil" className="focus:bg-primary/10 rounded-lg cursor-pointer">
                                    Semester Ganjil
                                </SelectItem>
                                <SelectItem value="genap" className="focus:bg-primary/10 rounded-lg cursor-pointer">
                                    Semester Genap
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <NotificationBell userId={user?.id} />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button className="flex items-center gap-2.5 rounded-xl border-l border-white/50 pl-3 text-left outline-none">
                                <div className="h-8 w-8 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-xs font-bold text-white shadow-lg">
                                    {(userName?.[0] || "A").toUpperCase()}
                                </div>
                                <div className="hidden md:block">
                                    <p className="text-sm font-semibold text-foreground leading-none">{userName}</p>
                                    <p className="text-[11px] text-muted-foreground">{userRole}</p>
                                </div>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-52 border-white/80 bg-white/95 backdrop-blur-md">
                            <DropdownMenuLabel className="pb-0 text-foreground">{userName}</DropdownMenuLabel>
                            <p className="px-2 pb-2 text-xs text-muted-foreground">{userRole}</p>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={handleLogout} className="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive">
                                <LogOut className="mr-2 h-4 w-4" />
                                Keluar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>
    );
}
