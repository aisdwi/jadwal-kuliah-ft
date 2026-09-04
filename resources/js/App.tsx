import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Navigate, Route, Routes, useLocation } from "react-router-dom";
import type { ReactElement } from "react";
import { DashboardLayout } from "@/components/layout/DashboardLayout";
import AuthProvider from "@/contexts/AuthContext";
import { SemesterProvider } from "@/contexts/SemesterContext";
import { useAuth } from "@/hooks/useAuth";
import Index from "./pages/Index";
import DosenPage from "./pages/master/DosenPage";
import RuanganPage from "./pages/master/RuanganPage";
import MataKuliahPage from "./pages/master/MataKuliahPage";
import JurusanPage from "./pages/master/JurusanPage";
import WaktuPage from "./pages/master/WaktuPage";
import SlotPage from "./pages/master/SlotPage";
import JadwalPage from "./pages/master/JadwalPage";
import KelasPage from "./pages/master/KelasPage";
import KelasKuliahPage from "./pages/master/KelasKuliahPage";
import ScheduleEditor from "./pages/ScheduleEditor";
import AutoSchedulePage from "./pages/AutoSchedulePage";
import UsersPage from "./pages/UsersPage";
import LoginPage from "./pages/LoginPage";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient();

function RequireAuth({ children }: { children: ReactElement }) {
    const { isAuthenticated } = useAuth();
    const location = useLocation();

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return children;
}

function RedirectIfAuthed({ children }: { children: ReactElement }) {
    const { isAuthenticated } = useAuth();
    if (isAuthenticated) {
        return <Navigate to="/" replace />;
    }
    return children;
}

function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <SemesterProvider>
                    <TooltipProvider>
                        <Toaster />
                        <Sonner />
                        <BrowserRouter>
                            <Routes>
                                <Route
                                    path="/login"
                                    element={
                                        <RedirectIfAuthed>
                                            <LoginPage />
                                        </RedirectIfAuthed>
                                    }
                                />
                                <Route
                                    element={
                                        <RequireAuth>
                                            <DashboardLayout />
                                        </RequireAuth>
                                    }
                                >
                                    <Route path="/" element={<Index />} />
                                    <Route path="/master/dosen" element={<DosenPage />} />
                                    <Route path="/master/ruangan" element={<RuanganPage />} />
                                    <Route path="/master/matakuliah" element={<MataKuliahPage />} />
                                    <Route path="/master/slot" element={<SlotPage />} />
                                    <Route path="/master/waktu" element={<WaktuPage />} />
                                    <Route path="/master/kelas" element={<KelasPage />} />
                                    <Route path="/master/kelas-kuliah" element={<KelasKuliahPage />} />
                                    <Route path="/scheduling/list" element={<JadwalPage />} />
                                    <Route path="/scheduling/editor" element={<ScheduleEditor />} />
                                    <Route path="/scheduling/auto" element={<AutoSchedulePage />} />
                                    <Route path="/master/users" element={<UsersPage />} />
                                </Route>
                                <Route path="*" element={<NotFound />} />
                            </Routes>
                        </BrowserRouter>
                    </TooltipProvider>
                </SemesterProvider>
            </AuthProvider>
        </QueryClientProvider>
    );
}

export default App;
