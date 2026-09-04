export interface SelectOption {
    value: string;
    label: string;
}

export interface JurusanReference {
    id: number;
    nama_jurusan: string;
}

export interface ProgramStudiReference {
    id: number;
    nama_prodi: string;
}

export type SemesterTipeOption = 'ganjil' | 'genap';

export function mapJurusanOptions(items: JurusanReference[]): SelectOption[] {
    return items.map((item) => ({
        value: String(item.id),
        label: item.nama_jurusan,
    }));
}

export function mapProgramStudiOptions(items: ProgramStudiReference[]): SelectOption[] {
    return items.map((item) => ({
        value: String(item.id),
        label: item.nama_prodi,
    }));
}

export function getSemesterOptions(semesterTipe: SemesterTipeOption): SelectOption[] {
    const values = semesterTipe === 'ganjil' ? [1, 3, 5, 7] : [2, 4, 6, 8];

    return values.map((semester) => ({
        value: String(semester),
        label: `Semester ${semester}`,
    }));
}
