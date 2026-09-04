export interface ApiUser {
    id: number;
    nama_user: string;
    email: string;
    role_id: number;
    jurusan_id: number | null;
    program_studi_id?: number | null;
    dosen_id?: number | null;
    role?: { id: number; role: string };
    jurusan?: { id: number; nama_jurusan: string };
    program_studi?: { id: number; nama_prodi: string };
}

export interface RoleOption {
    id: number;
    role: string;
    name?: string;
    display_name?: string;
}

export interface JurusanOption {
    id: number;
    nama_jurusan: string;
}

export interface ProgramStudiOption {
    id: number;
    nama_prodi: string;
    jurusan_id: number;
}

export interface DosenOption {
    id: number;
    nama_lengkap: string;
    inisial: string;
}

export const PAGE_SIZE = 7;

export const ROLE_STYLES: Record<string, string> = {
    "Super Admin": "bg-rose-100 text-rose-700",
    "Admin Fakultas": "bg-sky-100 text-sky-700",
    "Admin Jurusan": "bg-emerald-100 text-emerald-700",
    "Ketua Jurusan": "bg-teal-100 text-teal-700",
    "Koordinator Program Studi": "bg-cyan-100 text-cyan-700",
    Dosen: "bg-amber-100 text-amber-700",
    "Wakil Dekan I Bidang Akademik": "bg-violet-100 text-violet-700",
};

export const JURUSAN_REQUIRED_ROLES = new Set([
    "Admin Jurusan",
    "Ketua Jurusan",
    "Koordinator Program Studi",
]);

export const PROGRAM_STUDI_REQUIRED_ROLES = new Set([
    "Koordinator Program Studi",
]);

export const emptyUserForm = {
    nama_user: "",
    email: "",
    password: "",
    role_id: "",
    jurusan_id: "",
    program_studi_id: "",
    dosen_id: "",
};

export type UserFormData = typeof emptyUserForm;
