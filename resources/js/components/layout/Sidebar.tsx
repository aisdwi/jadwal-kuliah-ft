import { useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import {
  LayoutDashboard,
  Users,
  DoorOpen,
  BookOpen,
  Clock,
  CalendarDays,
  GripVertical,
  ChevronDown,
  PanelLeft,
  PanelLeftClose,
  Library,
  Layers,
  Component,
  Sparkles,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { useAuth } from "@/contexts/AuthContext";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

interface NavItem {
  title: string;
  url: string;
  icon: React.ElementType;
}

const masterDataItems: NavItem[] = [
  { title: "Mata Kuliah", url: "/master/matakuliah", icon: BookOpen },
  { title: "Dosen", url: "/master/dosen", icon: Users },
  { title: "Nama Kelas", url: "/master/kelas", icon: Library },
  { title: "Kelas Perkuliahan", url: "/master/kelas-kuliah", icon: Layers },
  { title: "Ruang Kuliah", url: "/master/ruangan", icon: DoorOpen },
  { title: "Jam Kuliah", url: "/master/waktu", icon: Clock },
  { title: "Slot Jadwal", url: "/master/slot", icon: Component },
];

const schedulingItems: NavItem[] = [
  { title: "Table View", url: "/scheduling/list", icon: CalendarDays },
  { title: "Timetable View", url: "/scheduling/editor", icon: GripVertical },
  { title: "Generate Otomatis", url: "/scheduling/auto", icon: Sparkles },
];

function CollapsibleGroup({
  label,
  items,
  collapsed,
}: {
  label: string;
  items: NavItem[];
  collapsed: boolean;
}) {
  const location = useLocation();
  const isActive = items.some((i) => location.pathname.startsWith(i.url));
  const [open, setOpen] = useState(isActive);

  if (collapsed) {
    return (
      <div className="flex flex-col gap-1">
        {items.map((item) => (
          <SidebarNavItem key={item.url} item={item} collapsed />
        ))}
      </div>
    );
  }

  return (
    <div>
      <button
        onClick={() => setOpen(!open)}
        className="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
      >
        {label}
        <ChevronDown
          className={cn("h-3.5 w-3.5 transition-transform", open && "rotate-180")}
        />
      </button>
      <AnimatePresence initial={false}>
        {open && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="overflow-hidden"
          >
            <div className="flex flex-col gap-0.5 pb-1">
              {items.map((item) => (
                <SidebarNavItem key={item.url} item={item} collapsed={false} />
              ))}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function SidebarNavItem({ item, collapsed }: { item: NavItem; collapsed: boolean }) {
  const location = useLocation();
  const isActive =
    item.url === "/"
      ? location.pathname === "/"
      : location.pathname === item.url || location.pathname.startsWith(`${item.url}/`);

  const content = (
    <NavLink
      to={item.url}
      className={
        cn(
          "nav-item",
          collapsed && "justify-center px-2",
          isActive && "nav-item-active"
        )
      }
    >
      <item.icon className="h-5 w-5 shrink-0" />
      {!collapsed && <span>{item.title}</span>}
    </NavLink>
  );

  if (!collapsed) return content;

  return (
    <Tooltip delayDuration={1000}>
      <TooltipTrigger asChild>
        {content}
      </TooltipTrigger>
      <TooltipContent side="right" sideOffset={10} className="text-xs font-medium z-[100]">
        {item.title}
      </TooltipContent>
    </Tooltip>
  );
}

export function Sidebar() {
  const isMobile = useIsMobile();
  const { canManageUsers, canSchedule } = useAuth();
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const visibleSchedulingItems = canSchedule
    ? schedulingItems
    : schedulingItems.filter((item) => item.url !== "/scheduling/auto");

  const sidebarContent = (
    <div className={cn("flex h-full flex-col", collapsed ? "w-16" : "w-64")}>
      {/* Logo */}
      <div className={cn("relative border-b border-white/40 py-4", collapsed ? "px-3" : "px-4")}>
        <div className={cn("flex items-start gap-1", collapsed && "justify-center")}>
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/40 backdrop-blur-lg border border-white/60">
            <img src="/unri-logo.png" alt="Logo Universitas Riau" className="h-10 w-10 object-contain" />
          </div>
          {!collapsed && (
            <>
              <div className="min-w-0 flex-1 flex flex-col">
                <span className="text-[12px] font-bold leading-tight text-foreground">
                  Website Penjadwalan Perkuliahan
                </span>
                <span className="mt-0.5 text-[11px] font-semibold leading-tight text-foreground/80">
                  Fakultas Teknik
                </span>
                <span className="mt-0.5 text-[10px] text-muted-foreground">Universitas Riau</span>
              </div>
              {!isMobile && (
                <button
                  onClick={() => setCollapsed(true)}
                  className="mt-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-emerald-200/80 bg-white/70 text-muted-foreground transition-all duration-200 hover:bg-emerald-50 hover:text-foreground active:bg-emerald-100"
                  title="Ciutkan menu samping"
                >
                  <PanelLeftClose className="h-3.5 w-3.5" />
                </button>
              )}
            </>
          )}
        </div>
      </div>

      {collapsed && !isMobile && (
        <div className="border-b border-white/40 px-3 py-3">
          <button
            onClick={() => setCollapsed(false)}
            className="mx-auto flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-200/80 bg-white/70 text-muted-foreground transition-all duration-200 hover:bg-emerald-50 hover:text-foreground active:bg-emerald-100"
            title="Perluas menu samping"
          >
            <PanelLeft className="h-4 w-4" />
          </button>
        </div>
      )}

      {/* Nav */}
      <nav className="flex-1 space-y-4 overflow-y-auto px-2 py-4 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <div>
          {!collapsed && (
            <span className="block px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              Ringkasan
            </span>
          )}
          <div className="mt-1">
            <SidebarNavItem
              item={{ title: "Dashboard", url: "/", icon: LayoutDashboard }}
              collapsed={collapsed}
            />
          </div>
        </div>

        <CollapsibleGroup label="Master Data" items={masterDataItems} collapsed={collapsed} />
        <CollapsibleGroup label="Penjadwalan" items={visibleSchedulingItems} collapsed={collapsed} />

        {canManageUsers && (
          <div>
            {!collapsed && (
              <span className="block px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Sistem
              </span>
            )}
            <div className="mt-1">
              <SidebarNavItem
                item={{ title: "Pengguna", url: "/master/users", icon: Users }}
                collapsed={collapsed}
              />
            </div>
          </div>
        )}
      </nav>
    </div>
  );

  const providerWrappedContent = <TooltipProvider>{sidebarContent}</TooltipProvider>;

  // Mobile overlay
  if (isMobile) {
    return (
      <>
        <button
          onClick={() => setMobileOpen(true)}
          className="fixed top-4 left-4 z-50 flex h-10 w-10 items-center justify-center rounded-full glass-card text-foreground shadow-lg"
        >
          <PanelLeft className="h-5 w-5" />
        </button>
        <AnimatePresence>
          {mobileOpen && (
            <>
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm"
                onClick={() => setMobileOpen(false)}
              />
              <motion.aside
                initial={{ x: -280 }}
                animate={{ x: 0 }}
                exit={{ x: -280 }}
                transition={{ type: "spring", damping: 25, stiffness: 300 }}
                className="fixed left-0 top-0 z-50 h-full glass-sidebar"
              >
                {providerWrappedContent}
              </motion.aside>
            </>
          )}
        </AnimatePresence>
      </>
    );
  }

  return (
    <aside className="sticky top-0 h-screen shrink-0 glass-sidebar">
      {providerWrappedContent}
    </aside>
  );
}
