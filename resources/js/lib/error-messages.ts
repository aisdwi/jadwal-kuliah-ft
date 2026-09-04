import axios from "axios";

type ErrorContext = "login" | "load" | "save" | "delete" | "import" | "schedule" | "generate";

interface ApiErrorPayload {
    message?: string;
    errors?: Record<string, string[] | string>;
    conflict_details?: string[];
}

interface FriendlyErrorOptions {
    context?: ErrorContext;
    fallback?: string;
}

const FIELD_LABELS: Record<string, string> = {
    nama_user: "nama user",
    nama_lengkap: "nama lengkap",
    email: "email",
    password: "password",
    role_id: "role",
    jurusan_id: "jurusan",
    program_studi_id: "program studi",
    dosen_id: "dosen",
    dosen_ids: "dosen pengampu",
    dosen_luar: "dosen luar",
    matakuliah_id: "mata kuliah",
    kelas_id: "kelas",
    kelas_kuliah_id: "kelas kuliah",
    ruangan_id: "ruangan",
    slot_id: "slot jadwal",
    waktu_id: "waktu",
    hari_id: "hari",
    semester_tipe: "semester",
    file: "file Excel",
};

const DEFAULT_MESSAGES: Record<ErrorContext, string> = {
    login: "Login belum berhasil. Periksa kembali data akun Anda dan coba lagi.",
    load: "Data belum dapat dimuat saat ini. Coba lagi beberapa saat.",
    save: "Perubahan belum dapat disimpan. Periksa kembali isian Anda lalu coba lagi.",
    delete: "Data belum dapat dihapus saat ini. Coba lagi beberapa saat.",
    import: "Import belum berhasil. Periksa kembali file yang diunggah lalu coba lagi.",
    schedule: "Perubahan jadwal belum dapat diproses. Coba pilih slot atau ruangan lain.",
    generate: "Proses belum dapat dijalankan saat ini. Coba lagi beberapa saat.",
};

function normalizeText(value: string): string {
    return value.toLowerCase().replace(/\s+/g, " ").trim();
}

function includesAny(text: string, patterns: string[]): boolean {
    return patterns.some((pattern) => text.includes(pattern));
}

function toNaturalList(values: string[]): string {
    if (values.length <= 1) return values[0] || "";
    if (values.length === 2) return `${values[0]} dan ${values[1]}`;
    return `${values.slice(0, -1).join(", ")}, dan ${values[values.length - 1]}`;
}

function humanizeFieldName(field: string): string {
    const segments = field.split(".");
    const lastSegment = segments[segments.length - 1]?.replace(/\[\d+\]/g, "") || field;

    if (FIELD_LABELS[lastSegment]) {
        return FIELD_LABELS[lastSegment];
    }

    return lastSegment.replace(/_/g, " ");
}

function getValidationFieldSummary(errors?: Record<string, string[] | string>): string | null {
    if (!errors || typeof errors !== "object") {
        return null;
    }

    const labels = Array.from(
        new Set(
            Object.keys(errors)
                .map((key) => humanizeFieldName(key))
                .filter(Boolean)
        )
    );

    if (labels.length === 0) {
        return null;
    }

    return `Periksa kembali isian berikut: ${toNaturalList(labels)}.`;
}

function collectMessageText(payload?: ApiErrorPayload): string {
    if (!payload) {
        return "";
    }

    const parts: string[] = [];

    if (payload.message) {
        parts.push(payload.message);
    }

    if (Array.isArray(payload.conflict_details)) {
        parts.push(...payload.conflict_details.filter(Boolean));
    }

    if (payload.errors && typeof payload.errors === "object") {
        Object.entries(payload.errors).forEach(([field, value]) => {
            parts.push(field);
            if (Array.isArray(value)) {
                parts.push(...value.filter(Boolean));
            } else if (value) {
                parts.push(value);
            }
        });
    }

    return normalizeText(parts.join(" "));
}

export function getUserFriendlyError(error: unknown, options: FriendlyErrorOptions = {}): string {
    const context = options.context ?? "save";
    const fallback = options.fallback ?? DEFAULT_MESSAGES[context];

    if (!axios.isAxiosError<ApiErrorPayload>(error)) {
        return fallback;
    }

    const status = error.response?.status;
    const payload = error.response?.data;
    const combinedText = collectMessageText(payload);
    const validationSummary = getValidationFieldSummary(payload?.errors);

    if (!error.response) {
        if (context === "login") {
            return "Tidak dapat terhubung ke server. Periksa koneksi lalu coba login kembali.";
        }

        return "Tidak dapat terhubung ke server. Coba lagi beberapa saat.";
    }

    if (status === 401) {
        return context === "login"
            ? "Email atau password tidak sesuai."
            : "Sesi Anda sudah berakhir. Silakan login kembali.";
    }

    if (status === 403) {
        return "Anda tidak memiliki akses untuk melakukan tindakan ini.";
    }

    if (status === 404) {
        if (context === "schedule") {
            return "Data jadwal yang ingin diubah sudah tidak ditemukan. Muat ulang halaman lalu coba lagi.";
        }

        return "Data yang diminta tidak ditemukan atau sudah tidak tersedia.";
    }

    if (status === 413) {
        return "Ukuran file terlalu besar untuk diproses. Gunakan file yang lebih kecil.";
    }

    if (status === 415) {
        return "Format file belum didukung. Gunakan file dengan format yang sesuai.";
    }

    if (status === 429) {
        return context === "login"
            ? "Terlalu banyak percobaan login. Coba lagi beberapa saat."
            : "Permintaan dikirim terlalu sering. Coba lagi beberapa saat.";
    }

    if (
        context === "delete" &&
        includesAny(combinedText, [
            "foreign key",
            "constraint fails",
            "integrity constraint",
            "cannot delete",
            "masih digunakan",
            "sedang digunakan",
            "terkait dengan data lain",
            "referenced",
        ])
    ) {
        return "Data ini masih terhubung ke data lain sehingga belum bisa dihapus.";
    }

    if (
        includesAny(combinedText, [
            "duplicate entry",
            "already exists",
            "has already been taken",
            "integrity constraint",
            "unique",
        ])
    ) {
        if (includesAny(combinedText, ["slot", "ruangan", "jadwal", "ruang"])) {
            return "Slot jadwal dan ruangan yang dipilih sudah dipakai kelas lain. Pilih kombinasi lain.";
        }

        if (combinedText.includes("email")) {
            return "Email tersebut sudah digunakan. Gunakan email lain.";
        }

        return "Data serupa sudah ada. Periksa kembali isian yang harus unik.";
    }

    if (status === 422) {
        if (payload?.message && !payload?.errors) {
            return payload.message;
        }

        if (context === "schedule" && payload?.message) {
            return payload.message;
        }

        if (context === "schedule" || includesAny(combinedText, ["bentrok", "conflict", "slot", "ruangan", "jadwal"])) {
            return "Jadwal bentrok atau kombinasi slot dan ruangan belum sesuai. Coba pilih kombinasi lain.";
        }

        if (
            context === "import" ||
            includesAny(combinedText, ["excel", "template", "kolom", "baris", "header", "worksheet", "sheet", "file"])
        ) {
            return "File Excel tidak sesuai template atau ada beberapa baris data yang belum valid.";
        }

        if (context === "login") {
            return "Email atau password tidak sesuai.";
        }

        if (validationSummary) {
            return validationSummary;
        }

        return "Data yang dikirim belum lengkap atau belum sesuai.";
    }

    if (status !== undefined && status >= 500) {
        if (context === "generate") {
            return "Proses penjadwalan belum dapat dijalankan karena sistem sedang bermasalah. Coba lagi beberapa saat.";
        }

        return "Terjadi gangguan pada sistem. Coba lagi beberapa saat.";
    }

    return validationSummary || fallback;
}
