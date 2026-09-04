import { MasterDataPage, type Column, type FieldDef } from "@/components/master/MasterDataPage";

interface Waktu {
    id: number;
    pukul: string;
    sks: number;
}

const columns: Column<Waktu>[] = [
    { key: "pukul", label: "Waktu", sortable: true, cellAlign: "center" },
    { key: "sks", label: "SKS", sortable: true, cellAlign: "center" },
];

const formFields: FieldDef[] = [
    { key: "pukul", label: "Waktu", placeholder: "Contoh: 08:40 - 09:30", required: true },
    { key: "sks", label: "SKS", type: "number", required: true },
];

export default function WaktuPage() {
    return (
        <MasterDataPage<Waktu>
            title="Jam Kuliah"
            description="Manajemen waktu dan porsi SKS perkuliahan."
            apiEndpoint="/waktu"
            columns={columns}
            formFields={formFields}
        />
    );
}
