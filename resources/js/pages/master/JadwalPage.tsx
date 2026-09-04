import { useState, useEffect, useMemo, useCallback } from "react";
import { MasterDataPage, type Column, type FieldDef, type FilterOption } from "@/components/master/MasterDataPage";
import { FileDown } from "lucide-react";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import api from "@/lib/api";
import { cachedApiGet, invalidateApiCache } from "@/lib/api-cache";
import ExcelJS from "exceljs";
import { DosenAssignmentsCell, getDosenAssignmentsText } from "@/components/master/DosenAssignmentsCell";
import { useAuth } from "@/contexts/AuthContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";
import { useProgramStudiOptions } from "@/hooks/useProgramStudiOptions";
import { useSemester } from "@/contexts/SemesterContext";

interface KelasKuliah {
    id: number;
    dosen_id: number;
    matakuliah_id: number;
    kelas_id: number;
    jumlah_mahasiswa: number;
    dosen?: { nama_lengkap: string; inisial: string };
    dosens?: Array<{
        id: number;
        nama_lengkap: string;
        inisial: string;
        jurusan?: { nama_jurusan?: string };
        pivot?: { is_external?: boolean };
    }>;
    matakuliah?: {
        nama_mk: string;
        kode_mk: string;
        sks: number;
        semester: number;
        program_studi?: { id?: number; nama_prodi: string; jurusan?: { id: number; nama_jurusan: string } };
    };
    kelas?: { nama_kelas: string; semester: number };
    ruangan?: { ruangan: string; kapasitas: number };
    slot?: {
        hari?: { nama_hari: string };
        waktu?: { pukul: string; sks: number };
    };
    slot_id?: number;
    ruangan_id?: number;
}

const columns: Column<KelasKuliah>[] = [
    { key: "slot", label: "Hari", sortable: false, cellAlign: "center", render: (_, row) => row.slot?.hari?.nama_hari || "-" },
    { key: "waktu" as keyof KelasKuliah, label: "Waktu", cellAlign: "center", render: (_, row) => row.slot?.waktu?.pukul || "-" },
    { key: "matakuliah_id" as keyof KelasKuliah, label: "Mata Kuliah", sortable: false, render: (_, row) => row.matakuliah ? `${row.matakuliah.nama_mk} \n[${row.matakuliah.sks} SKS]` : "-" },
    { key: "kelas", label: "Kelas", render: (_, row) => row.kelas ? `${row.kelas.nama_kelas}` : "-" },
    { key: "smt" as keyof KelasKuliah, label: "SMT", cellAlign: "center", render: (_, row) => row.matakuliah?.semester || "-" },
    {
        key: "dosen",
        label: "Dosen Pengampu",
        render: (_, row) => <DosenAssignmentsCell dosens={row.dosens} fallbackDosen={row.dosen} />,
    },
    { key: "dosen_id" as keyof KelasKuliah, label: "Program Studi", cellAlign: "center", render: (_, row) => row.matakuliah?.program_studi?.nama_prodi || "-" },
    { key: "ruangan", label: "Ruangan", cellAlign: "center", render: (_, row) => row.ruangan?.ruangan || "-" },
];

export default function JadwalPage() {
    const { isJurusanRestricted, canSchedule, defaultsToOwnJurusan, user } = useAuth();
    const { semesterTipe } = useSemester();
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
    const { prodiOptions, fetchProdiOptions } = useProgramStudiOptions();
    const [kelasKuliahOptions, setKelasKuliahOptions] = useState<{ value: string; label: string }[]>([]);
    const [slotOptions, setSlotOptions] = useState<{ value: string; label: string }[]>([]);
    const [ruanganOptions, setRuanganOptions] = useState<{ value: string; label: string }[]>([]);

    const loadUnscheduledOptions = useCallback(() => {
        cachedApiGet<any>(`/kelas-kuliah?is_scheduled=false&per_page=all&semester_tipe=${semesterTipe}`).then(r => {
            const data = Array.isArray(r.data) ? r.data : (r.data.data || []);
            setKelasKuliahOptions(data.map((kk: any) => ({
                value: String(kk.id),
                label: `${kk.matakuliah?.kode_mk || ''} - ${kk.matakuliah?.nama_mk || 'N/A'} | ${kk.kelas?.nama_kelas || ''} | ${kk.dosen?.nama_lengkap || ''}`,
            })));
        });
    }, [semesterTipe]);

    useEffect(() => {
        Promise.all([
            cachedApiGet<any[]>("/referensi/slot"),
            cachedApiGet<any>("/ruangan?per_page=all"),
        ]).then(([slotResponse, ruanganResponse]) => {
            setSlotOptions(slotResponse.data.map((s: any) => ({
                value: String(s.id),
                label: `${s.hari?.nama_hari || ''} - ${s.waktu?.pukul || ''} (${s.waktu?.sks || 0} SKS)`,
            })));

            const data = Array.isArray(ruanganResponse.data) ? ruanganResponse.data : (ruanganResponse.data.data || []);
            setRuanganOptions(data.map((ru: any) => ({
                value: String(ru.id),
                label: `${ru.ruangan} (Kapasitas: ${ru.kapasitas || '-'})`,
            })));
        });
    }, []);

    useEffect(() => {
        loadUnscheduledOptions();
    }, [loadUnscheduledOptions]);

    useEffect(() => {
        if (defaultsToOwnJurusan && user?.jurusan_id) {
            fetchProdiOptions(String(user.jurusan_id));
        }
    }, [defaultsToOwnJurusan, fetchProdiOptions, user?.jurusan_id]);

    const filterOptions = useMemo<FilterOption[]>(() => {
        const filters: FilterOption[] = [];
        if (!isJurusanRestricted) {
            filters.push({ label: "Semua Jurusan", key: "jurusan_id", options: jurusanOptions });
        }
        filters.push({ label: "Semua Program Studi", key: "program_studi_id", options: prodiOptions });
        return filters;
    }, [isJurusanRestricted, jurusanOptions, prodiOptions]);

    const initialFilters = useMemo<Record<string, string>>(() => {
        if (defaultsToOwnJurusan && user?.jurusan_id) {
            return { jurusan_id: String(user.jurusan_id) };
        }
        const emptyFilters: Record<string, string> = {};
        return emptyFilters;
    }, [defaultsToOwnJurusan, user?.jurusan_id]);

    const formFields: FieldDef[] = useMemo(() => [
        {
            key: "kelas_kuliah_id",
            label: "Kelas Kuliah (Belum Terjadwal)",
            type: "select" as const,
            options: kelasKuliahOptions,
            required: true,
            placeholder: "Pilih kelas kuliah...",
        },
        {
            key: "slot_id",
            label: "Slot Jadwal (Hari & Waktu)",
            type: "select" as const,
            options: slotOptions,
            required: true,
            placeholder: "Pilih slot...",
        },
        {
            key: "ruangan_id",
            label: "Ruangan",
            type: "select" as const,
            options: ruanganOptions,
            required: true,
            placeholder: "Pilih ruangan...",
        },
    ], [kelasKuliahOptions, slotOptions, ruanganOptions]);

    const handleCustomSave = async (formState: Record<string, string>) => {
        const { kelas_kuliah_id, slot_id, ruangan_id } = formState;
        if (!kelas_kuliah_id || !slot_id || !ruangan_id) {
            toast.error("Semua field harus diisi");
            throw new Error("Validation failed");
        }
        await api.put(`/kelas-kuliah/${kelas_kuliah_id}/jadwal`, {
            slot_id: parseInt(slot_id),
            ruangan_id: parseInt(ruangan_id),
        });
        invalidateApiCache(["/kelas-kuliah", "/dashboard"]);
        toast.success("Jadwal berhasil ditambahkan", { description: "Kelas kuliah berhasil dijadwalkan." });
        loadUnscheduledOptions();
    };

    const handleExportExcel = async () => {
        try {
            const response = await api.get(`/kelas-kuliah?is_scheduled=true&per_page=all&semester_tipe=${semesterTipe}`);
            const data: KelasKuliah[] = Array.isArray(response.data) ? response.data : (response.data.data || []);
            if (!data.length) {
                toast.error("Tidak ada data jadwal untuk di-export");
                return;
            }

            const exportData = data.map((row, index) => ({
                "No": index + 1,
                "Hari": row.slot?.hari?.nama_hari || "-",
                "Waktu": row.slot?.waktu?.pukul || "-",
                "Kode MK": row.matakuliah?.kode_mk || "-",
                "Mata Kuliah": row.matakuliah?.nama_mk || "-",
                "SKS": row.matakuliah?.sks || "-",
                "Kelas": row.kelas?.nama_kelas || "-",
                "Semester": row.matakuliah?.semester || "-",
                "Dosen Pengampu": getDosenAssignmentsText(row.dosens, row.dosen),
                "Program Studi": row.matakuliah?.program_studi?.nama_prodi || "-",
                "Ruangan": row.ruangan?.ruangan || "-",
            }));

            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet("Jadwal Perkuliahan");
            worksheet.columns = Object.keys(exportData[0]).map(key => ({ header: key, key: key, width: Math.max(key.length, 15) }));
            exportData.forEach(row => worksheet.addRow(row));
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Jadwal_Perkuliahan_Semester_${semesterTipe === 'ganjil' ? 'Ganjil' : 'Genap'}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            toast.success("Berhasil mengunduh file Excel");
        } catch (error) {
            console.error("Export failed:", error);
            toast.error("Gagal mengunduh file Excel");
        }
    };

    const exportButton = (
        <Button
            onClick={handleExportExcel}
            className="bg-amber-500 hover:bg-amber-600 text-white gap-2 w-full sm:w-auto shadow-md hover:shadow-lg transition-all"
        >
            <FileDown className="h-4 w-4" /> Export Excel
        </Button>
    );

    const searchPredicate = useCallback((row: KelasKuliah, normalizedQuery: string) => {
        const searchable = [
            row.slot?.hari?.nama_hari,
            row.slot?.waktu?.pukul,
            row.matakuliah?.kode_mk,
            row.matakuliah?.nama_mk,
            row.matakuliah?.sks,
            row.kelas?.nama_kelas,
            row.matakuliah?.semester,
            row.dosen?.nama_lengkap,
            row.dosen?.inisial,
            row.matakuliah?.program_studi?.nama_prodi,
            row.matakuliah?.program_studi?.jurusan?.nama_jurusan,
            row.ruangan?.ruangan,
        ]
            .filter((v) => v !== null && v !== undefined)
            .join(" ")
            .toLowerCase();
        return searchable.includes(normalizedQuery);
    }, []);

    const filterPredicate = useCallback((row: KelasKuliah, activeFilters: Record<string, string>) => {
        if (activeFilters.jurusan_id) {
            if (String(row.matakuliah?.program_studi?.jurusan?.id ?? "") !== activeFilters.jurusan_id) {
                return false;
            }
        }
        if (activeFilters.program_studi_id) {
            if (String(row.matakuliah?.program_studi?.id ?? "") !== activeFilters.program_studi_id) {
                return false;
            }
        }
        return true;
    }, []);

    return (
        <MasterDataPage<KelasKuliah>
            title="List View Jadwal"
            description={`Daftar jadwal perkuliahan yang telah ditetapkan pada Ruangan dan Waktu (Semester ${semesterTipe === 'ganjil' ? 'Ganjil' : 'Genap'})`}
            apiEndpoint={`/kelas-kuliah?is_scheduled=true&per_page=all&semester_tipe=${semesterTipe}`}
            columns={columns}
            formFields={formFields}
            filterOptions={filterOptions}
            initialFilters={initialFilters}
            filterResetMap={{ jurusan_id: ["program_studi_id"] }}
            onFilterChange={(key, value) => {
                if (key === "jurusan_id") {
                    fetchProdiOptions(value || null, !value);
                }
            }}
            searchPredicate={searchPredicate}
            filterPredicate={filterPredicate}
            extraActions={exportButton}
            hideDeleteAllButton
            onCustomSave={handleCustomSave}
            readOnly={!canSchedule}
            pageSize={7}
        />
    );
}
