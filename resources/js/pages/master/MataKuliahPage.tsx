import { useEffect, useMemo } from "react";
import { MasterDataPage, type Column, type FieldDef, type FilterOption } from "@/components/master/MasterDataPage";
import { useAuth } from "@/contexts/AuthContext";
import { useSemester } from "@/contexts/SemesterContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";
import { useProgramStudiOptions } from "@/hooks/useProgramStudiOptions";
import { getSemesterOptions } from "@/lib/reference-options";

interface MataKuliah {
    id: number;
    kode_mk: string;
    nama_mk: string;
    sks: number;
    semester: number;
    program_studi_id: number;
    jurusan_id: number;
    program_studi?: { nama_prodi: string };
    jurusan?: { nama_jurusan: string };
}

const columns: Column<MataKuliah>[] = [
    { key: "kode_mk", label: "Kode MK", sortable: true, cellAlign: "left" },
    { key: "nama_mk", label: "Mata Kuliah", sortable: true, cellAlign: "left" },
    { key: "sks", label: "SKS", sortable: true, cellAlign: "center", render: (v) => `${v} SKS` },
    { key: "semester", label: "Semester", sortable: true, cellAlign: "center" },
    { key: "program_studi", label: "Program Studi", cellAlign: "center", render: (_, row) => row.program_studi?.nama_prodi || "-" },
    { key: "jurusan", label: "Jurusan", cellAlign: "center", render: (_, row) => row.jurusan?.nama_jurusan || "-" },
];

export default function MataKuliahPage() {
    const { isJurusanRestricted, canEditData, defaultsToOwnJurusan, user } = useAuth();
    const { semesterTipe } = useSemester();
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
    const { prodiOptions, fetchProdiOptions, clearProdiOptions } = useProgramStudiOptions();
    const semesterOptions = useMemo(() => getSemesterOptions(semesterTipe), [semesterTipe]);

    useEffect(() => {
        if (defaultsToOwnJurusan && user?.jurusan_id) {
            fetchProdiOptions(String(user.jurusan_id));
        }
    }, [defaultsToOwnJurusan, fetchProdiOptions, user?.jurusan_id]);

    const handleFormChange = (key: string, value: string, currentFormState: Record<string, string>, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => {
        if (key === "jurusan_id") {
            setFormState(prev => ({ ...prev, program_studi_id: "" })); // reset prodi
            if (value) {
                fetchProdiOptions(value);
            } else {
                clearProdiOptions();
            }
        }
    };

    const handleModalOpen = (row: MataKuliah | null, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => {
        if (isJurusanRestricted && user?.jurusan_id && !row) {
            setFormState(prev => ({ ...prev, jurusan_id: String(user.jurusan_id) }));
        }
        if (row && row.jurusan_id) {
            fetchProdiOptions(String(row.jurusan_id));
        } else if (!isJurusanRestricted) {
            clearProdiOptions();
        }
    };

    const formFields = useMemo<FieldDef[]>(() => {
        const fields: FieldDef[] = [
            { key: "kode_mk", label: "Kode MK", required: true, placeholder: "Contoh: IF-301" },
            { key: "nama_mk", label: "Nama Mata Kuliah", required: true },
            { key: "sks", label: "SKS", type: "number", required: true },
            { key: "semester", label: "Semester", type: "select", required: true, options: semesterOptions },
        ];
        if (!isJurusanRestricted) {
            fields.push({ key: "jurusan_id", label: "Jurusan", type: "select", required: true, options: jurusanOptions });
        }
        fields.push({ key: "program_studi_id", label: "Program Studi", type: "select", required: true, options: prodiOptions, disabled: prodiOptions.length === 0 });
        return fields;
    }, [jurusanOptions, prodiOptions, isJurusanRestricted, semesterOptions]);

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

    return (
        <MasterDataPage<MataKuliah>
            title="Mata Kuliah"
            description={`Manajemen data mata kuliah (Semester ${semesterTipe === 'ganjil' ? 'Ganjil' : 'Genap'})`}
            apiEndpoint={`/matakuliah?semester_tipe=${semesterTipe}`}
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
            onFormChange={handleFormChange}
            onModalOpen={handleModalOpen}
            readOnly={!canEditData}
            importUrl={canEditData ? "/matakuliah/import" : undefined}
            templateUrl={canEditData ? "/template/template-import-matakuliah.xlsx" : undefined}
        />
    );
}
