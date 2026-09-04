export default function NotFound() {
    return (
        <div className="flex min-h-screen items-center justify-center">
            <div className="glass-elevated p-12 text-center">
                <h1 className="text-6xl font-bold text-primary mb-4">404</h1>
                <p className="text-xl font-semibold text-foreground mb-2">Halaman Tidak Ditemukan</p>
                <p className="text-muted-foreground mb-6">Halaman yang Anda cari tidak ada.</p>
                <a href="/" className="gradient-btn inline-block">Kembali ke Dashboard</a>
            </div>
        </div>
    );
}
