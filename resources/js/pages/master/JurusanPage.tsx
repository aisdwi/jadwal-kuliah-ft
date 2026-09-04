import { MasterDataPage, type Column, type FieldDef } from "@/components/master/MasterDataPage";

interface Jurusan {
    id: number;
    nama_jurusan: string;
}

const columns: Column<Jurusan>[] = [
    { key: "id", label: "ID", sortable: true, cellAlign: "center" },
    { key: "nama_jurusan", label: "Nama Jurusan", sortable: true, cellAlign: "left" },
];

const formFields: FieldDef[] = [
    { key: "nama_jurusan", label: "Nama Jurusan", required: true },
];

export default function JurusanPage() {
    return (
        <MasterDataPage<Jurusan>
            title="Data Jurusan"
            description="Kelola data jurusan aktif."
            columns={columns}
            apiEndpoint="/jurusan"
            formFields={formFields}
        />
    );
}
