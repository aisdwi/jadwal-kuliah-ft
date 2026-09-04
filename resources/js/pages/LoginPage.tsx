import { useState } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { Eye, EyeOff, Loader2, Lock, LogIn, Mail } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAuth } from "@/hooks/useAuth";

export default function LoginPage() {
    const navigate = useNavigate();
    const { isAuthenticated, login } = useAuth();

    const [username, setUsername] = useState("");
    const [password, setPassword] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState("");
    const [isLoading, setIsLoading] = useState(false);

    if (isAuthenticated) {
        return <Navigate to="/" replace />;
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError("");
        setIsLoading(true);

        try {
            const result = await login(username, password);
            if (!result.success) {
                setError(result.message || "Username atau password salah.");
                return;
            }
            navigate("/", { replace: true });
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="relative min-h-screen overflow-hidden bg-[linear-gradient(140deg,#eef7f1_0%,#d8eadf_42%,#cfe2dc_100%)] px-4 py-6 sm:px-6 lg:px-10">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(32,126,77,0.26),transparent_30%),radial-gradient(circle_at_85%_14%,rgba(255,255,255,0.72),transparent_18%),radial-gradient(circle_at_bottom_right,rgba(16,78,58,0.18),transparent_28%)]" />
            <div className="pointer-events-none absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(255,255,255,0.45)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.45)_1px,transparent_1px)] [background-size:32px_32px]" />
            <div className="pointer-events-none absolute -left-24 top-16 h-64 w-64 rounded-full bg-emerald-300/30 blur-3xl" />
            <div className="pointer-events-none absolute bottom-0 right-0 h-80 w-80 rounded-full bg-teal-200/45 blur-3xl" />

            <div className="relative z-10 mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl items-center justify-center">
                <div className="relative w-full max-w-[500px] overflow-hidden rounded-[40px] border border-white/60 bg-[linear-gradient(160deg,rgba(244,251,247,0.9)_0%,rgba(232,245,238,0.86)_100%)] px-8 py-10 shadow-[0_30px_90px_rgba(17,57,39,0.18)] backdrop-blur-2xl sm:px-10 sm:py-11">
                    <div className="pointer-events-none absolute inset-0 rounded-[40px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.52),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(74,166,118,0.12),transparent_28%)]" />
                    <div className="pointer-events-none absolute -left-10 top-6 h-28 w-28 rounded-full bg-white/35 blur-2xl" />
                    <div className="pointer-events-none absolute bottom-0 right-0 h-36 w-36 rounded-full bg-emerald-200/25 blur-3xl" />

                    <div className="relative mx-auto max-w-[440px]">
                        <div className="mb-6 flex flex-col items-center text-center">
                            <div className="flex h-[88px] w-[88px] items-center justify-center rounded-[28px] border border-white/75 bg-white/94 shadow-[0_18px_38px_rgba(5,31,19,0.16)]">
                                <img
                                    src="/unri-logo.png"
                                    alt="Logo Universitas Riau"
                                    className="object-contain"
                                    style={{ width: 108, height: 108, maxWidth: 108, maxHeight: 108 }}
                                />
                            </div>
                            <h1 className="mt-4 text-[1.85rem] font-semibold leading-[1.06] tracking-tight text-foreground sm:text-[1.95rem]">
                                Website Penjadwalan Perkuliahan
                            </h1>
                            <p className="mt-2 text-[1rem] font-semibold tracking-[0.08em] text-foreground/78 sm:text-[1.05rem]">
                                Fakultas Teknik
                            </p>
                            <p className="mt-3 text-[15px] font-semibold uppercase tracking-[0.28em] text-primary/65">
                                Universitas Riau
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="mb-2 block text-sm font-semibold text-foreground/85">
                                    Email atau Nama Pengguna
                                </label>
                                <div className="relative">
                                    <Mail className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={username}
                                        onChange={(e) => setUsername(e.target.value)}
                                        placeholder="nama@email.com"
                                        className="h-[56px] rounded-[30px] border-white/85 bg-white/95 pl-11 text-[14px] shadow-none focus-visible:ring-primary/30"
                                        autoComplete="username"
                                        required
                                        disabled={isLoading}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-semibold text-foreground/85">
                                    Password
                                </label>
                                <div className="relative">
                                    <Lock className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        type={showPassword ? "text" : "password"}
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        placeholder="Masukkan password"
                                        className="h-[56px] rounded-[30px] border-white/85 bg-white/95 px-11 text-[14px] shadow-none focus-visible:ring-primary/30"
                                        autoComplete="current-password"
                                        required
                                        disabled={isLoading}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-4 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-emerald-50 hover:text-foreground"
                                        aria-label={showPassword ? "Sembunyikan password" : "Tampilkan password"}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                            </div>

                            {error ? (
                                <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                                    {error}
                                </div>
                            ) : null}

                            <Button
                                type="submit"
                                disabled={isLoading}
                                className="h-[56px] w-full rounded-[30px] text-sm font-semibold shadow-[0_18px_34px_rgba(22,113,73,0.22)] transition-all duration-300 hover:-translate-y-0.5 disabled:translate-y-0"
                                style={{
                                    background: "linear-gradient(135deg, hsl(151 62% 31%), hsl(163 58% 38%))",
                                }}
                            >
                                {isLoading ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Memproses...
                                    </>
                                ) : (
                                    <>
                                        <LogIn className="mr-2 h-4 w-4" />
                                        Masuk
                                    </>
                                )}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
