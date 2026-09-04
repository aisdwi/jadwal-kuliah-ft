import { useMemo } from "react";
import { MasterDataPage, type Column, type FieldDef, type FilterOption } from "@/components/master/MasterDataPage";
import { useAuth } from "@/contexts/AuthContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";

interface Dosen {
    id: number;
    nip: string;
    nama_lengkap: string;
    inisial: string;
    jurusan_id: number;
    jurusan?: { nama_jurusan: string };
}

const columns: Column<Dosen>[] = [
    { key: "nip", label: "NIP", sortable: true, cellAlign: "left" },
    { key: "nama_lengkap", label: "Nama Dosen", sortable: true, cellAlign: "left" },
    { key: "inisial", label: "Inisial", sortable: false, cellAlign: "center" },
    { key: "jurusan", label: "Jurusan", sortable: true, cellAlign: "center", render: (_, row) => row.jurusan?.nama_jurusan || "-" },
];

export default function DosenPage() {
    const { isJurusanRestricted, canEditData, defaultsToOwnJurusan, user } = useAuth();
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);

    const formFields = useMemo<FieldDef[]>(() => {
        const fields: FieldDef[] = [
            { key: "nip", label: "NIP", placeholder: "Nomor Induk Pegawai", required: true },
            { key: "nama_lengkap", label: "Nama Dosen", required: true },
            { key: "inisial", label: "Inisial", required: true },
        ];
        if (!isJurusanRestricted) {
            fields.push({ key: "jurusan_id", label: "Jurusan", type: "select", required: true, options: jurusanOptions });
        }
        return fields;
    }, [jurusanOptions, isJurusanRestricted]);

    const filterOptions = useMemo<FilterOption[]>(() => {
        if (isJurusanRestricted) return [];
        return [{ label: "Semua Jurusan", key: "jurusan_id", options: jurusanOptions }];
    }, [jurusanOptions, isJurusanRestricted]);

    const initialFilters = useMemo<Record<string, string>>(() => {
        if (defaultsToOwnJurusan && user?.jurusan_id) {
            return { jurusan_id: String(user.jurusan_id) };
        }
        const emptyFilters: Record<string, string> = {};
        return emptyFilters;
    }, [defaultsToOwnJurusan, user?.jurusan_id]);

    const handleModalOpen = (row: Dosen | null, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => {
        if (isJurusanRestricted && user?.jurusan_id && !row) {
            setFormState(prev => ({ ...prev, jurusan_id: String(user.jurusan_id) }));
        }
    };

    return (
        <MasterDataPage<Dosen>
            title="Data Dosen"
            description="Manajemen data dosen pengajar"
            apiEndpoint="/dosen"
            columns={columns}
            formFields={formFields}
            filterOptions={filterOptions}
            initialFilters={initialFilters}
            onModalOpen={handleModalOpen}
            readOnly={!canEditData}
            importUrl={canEditData ? "/dosen/import" : undefined}
            templateUrl={canEditData ? "/template/template-import-dosen.xlsx" : undefined}
        />
    );
}
