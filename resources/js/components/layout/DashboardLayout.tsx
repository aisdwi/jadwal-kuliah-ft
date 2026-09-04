import { useEffect, useRef } from "react";
import { Outlet, useLocation } from "react-router-dom";
import { Sidebar } from "./Sidebar";
import { Header } from "./Header";

export function DashboardLayout() {
    const location = useLocation();
    const mainRef = useRef<HTMLElement | null>(null);
    const isDashboardHome = location.pathname === "/";

    useEffect(() => {
        if (mainRef.current) {
            mainRef.current.scrollTo({ top: 0, behavior: "auto" });
        }
    }, [location.pathname]);

    return (
        <div className="flex h-screen w-full overflow-hidden" style={{ background: "var(--gradient-mesh)" }}>
            <Sidebar />
            <div className="flex flex-1 flex-col min-w-0 min-h-0">
                <Header />
                <main
                    ref={mainRef}
                    className={`flex-1 px-4 py-4 overflow-x-hidden min-h-0 flex flex-col ${
                        isDashboardHome ? "overflow-y-hidden" : "overflow-y-auto"
                    }`}
                >
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
