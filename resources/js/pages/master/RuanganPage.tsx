import { useState, useEffect, useMemo } from "react";
import { MasterDataPage, type Column, type FieldDef } from "@/components/master/MasterDataPage";
import api from "@/lib/api";
import { useAuth } from "@/contexts/AuthContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";

interface Ruangan {
    id: number;
    ruangan: string;
    kapasitas: number;
    gedung_id: number;
    gedung?: { nama_gedung: string };
    jurusan_ids?: number[];
    jurusans?: Array<{ id: number; nama_jurusan: string }>;
}

interface Gedung { id: number; nama_gedung: string; }

const columns: Column<Ruangan>[] = [
    { key: "ruangan", label: "Ruang", sortable: true, cellAlign: "left" },
    { key: "gedung", label: "Gedung", cellAlign: "left", render: (_, row) => row.gedung?.nama_gedung || "-" },
    { key: "kapasitas", label: "Kapasitas", sortable: true, cellAlign: "center", render: (v) => `${v} orang` },
    {
        key: "jurusans",
        label: "Jurusan",
        cellAlign: "left",
        render: (_, row) => row.jurusans?.map((jurusan) => jurusan.nama_jurusan).join(", ") || "-",
    },
];

export default function RuanganPage() {
    const { canEditRuangan, isJurusanRestricted, user } = useAuth();
    const [gedungOptions, setGedungOptions] = useState<{ value: string; label: string }[]>([]);
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
    const shouldShowJurusanField = canEditRuangan && !isJurusanRestricted;

    useEffect(() => {
        api.get("/referensi/gedung").then(r =>
            setGedungOptions(r.data.map((g: Gedung) => ({ value: String(g.id), label: g.nama_gedung })))
        );
    }, []);

    const formFields = useMemo<FieldDef[]>(() => [
        { key: "ruangan", label: "Nama Ruangan", placeholder: "Contoh: R-101", required: true },
        { key: "gedung_id", label: "Gedung", type: "select", required: true, options: gedungOptions },
        { key: "kapasitas", label: "Kapasitas", type: "number", required: true, placeholder: "Contoh: 40" },
        ...(shouldShowJurusanField
            ? [{ key: "jurusan_ids", label: "Jurusan Pemakai", type: "multiselect" as const, required: true, options: jurusanOptions }]
            : []),
    ], [gedungOptions, jurusanOptions, shouldShowJurusanField]);

    return (
        <MasterDataPage<Ruangan>
            title="Data Ruangan"
            description="Manajemen ruangan untuk kegiatan perkuliahan"
            apiEndpoint="/ruangan"
            columns={columns}
            formFields={formFields}
            pageSize={10}
            tableLayout={{ rowDensity: "spacious" }}
            readOnly={!canEditRuangan}
        />
    );
}
