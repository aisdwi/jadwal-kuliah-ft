import { useState, useEffect, useMemo, useCallback } from "react";
import { MasterDataPage, type Column, type FieldDef, type FilterOption } from "@/components/master/MasterDataPage";
import api from "@/lib/api";
import { useAuth } from "@/contexts/AuthContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";

interface Slot {
    id: number;
    hari_id: number;
    waktu_id: number;
    jurusan_ids?: number[];
    hari?: { nama_hari: string };
    waktu?: { pukul: string; sks: number };
}

interface Hari { id: number; nama_hari: string; }
interface Waktu { id: number; pukul: string; sks: number; }

const columns: Column<Slot>[] = [
    { key: "hari", label: "Hari", sortable: true, cellAlign: "center", render: (_, row) => row.hari?.nama_hari || "-" },
    { key: "waktu", label: "Waktu", cellAlign: "center", render: (_, row) => row.waktu?.pukul || "-" },
    { key: "id" as keyof Slot, label: "SKS", cellAlign: "center", render: (_, row) => row.waktu ? `${row.waktu.sks}` : "-" },
];

export default function SlotPage() {
    const { isJurusanRestricted, defaultsToOwnJurusan, user } = useAuth();
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
    const [hariOptions, setHariOptions] = useState<{ value: string; label: string }[]>([]);
    const [waktuOptions, setWaktuOptions] = useState<{ value: string; label: string }[]>([]);

    useEffect(() => {
        api.get("/referensi/hari").then(r =>
            setHariOptions(r.data.map((h: Hari) => ({ value: String(h.id), label: h.nama_hari })))
        );
        api.get("/referensi/waktu").then(r =>
            setWaktuOptions(r.data.map((w: Waktu) => ({ value: String(w.id), label: `${w.pukul} (${w.sks} SKS)` })))
        );
    }, []);

    const formFields = useMemo<FieldDef[]>(() => [
        ...(!isJurusanRestricted
            ? [{ key: "jurusan_id", label: "Jurusan", type: "select" as const, required: true, options: jurusanOptions }]
            : []),
        { key: "hari_id", label: "Hari", type: "select" as const, required: true, options: hariOptions },
        { key: "waktu_id", label: "Waktu (Jam Kuliah)", type: "select" as const, required: true, options: waktuOptions },
    ], [hariOptions, isJurusanRestricted, jurusanOptions, waktuOptions]);

    const searchPredicate = useCallback((row: Slot, normalizedQuery: string) => {
        const searchable = [
            row.hari?.nama_hari,
            row.waktu?.pukul,
            row.waktu?.sks,
        ]
            .filter((v) => v !== null && v !== undefined)
            .join(" ")
            .toLowerCase();
        return searchable.includes(normalizedQuery);
    }, []);

    const filterOptions = useMemo<FilterOption[]>(() => {
        if (isJurusanRestricted) return [];
        return [{ label: "Semua Jurusan", key: "jurusan_id", options: jurusanOptions }];
    }, [isJurusanRestricted, jurusanOptions]);

    const initialFilters = useMemo<Record<string, string>>(() => {
        if (defaultsToOwnJurusan && user?.jurusan_id) {
            return { jurusan_id: String(user.jurusan_id) };
        }
        const emptyFilters: Record<string, string> = {};
        return emptyFilters;
    }, [defaultsToOwnJurusan, user?.jurusan_id]);

    const filterPredicate = useCallback((row: Slot, activeFilters: Record<string, string>) => {
        if (activeFilters.jurusan_id) {
            return (row.jurusan_ids ?? []).map(String).includes(activeFilters.jurusan_id);
        }
        return true;
    }, []);

    return (
        <MasterDataPage<Slot>
            title="Slot Jadwal"
            description="Manajemen kombinasi hari dan jam perkuliahan"
            apiEndpoint="/slot"
            columns={columns}
            formFields={formFields}
            filterOptions={filterOptions}
            initialFilters={initialFilters}
            searchPredicate={searchPredicate}
            filterPredicate={filterPredicate}
        />
    );
}
