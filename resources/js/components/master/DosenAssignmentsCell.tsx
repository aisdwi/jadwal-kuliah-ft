type JurusanInfo = {
    nama_jurusan?: string;
};

type DosenPivotInfo = {
    is_external?: boolean;
};

type DosenAssignment = {
    id: number;
    nama_lengkap: string;
    inisial: string;
    jurusan?: JurusanInfo;
    pivot?: DosenPivotInfo;
};

type PrimaryDosen = {
    nama_lengkap: string;
    inisial: string;
};

type DosenDisplayItem = {
    label: string;
    isExternal: boolean;
};

function buildDosenDisplayItems(dosens: DosenAssignment[] | undefined, fallbackDosen?: PrimaryDosen): DosenDisplayItem[] {
    const seenLabels = new Set<string>();
    const displayItems: DosenDisplayItem[] = [];

    for (const dosen of dosens ?? []) {
        const isExternal = !!dosen.pivot?.is_external;
        const label = isExternal
            ? `${dosen.nama_lengkap} (${dosen.jurusan?.nama_jurusan || "Luar Jurusan"})`
            : `${dosen.nama_lengkap} (${dosen.inisial})`;

        if (seenLabels.has(label)) {
            continue;
        }

        seenLabels.add(label);
        displayItems.push({ label, isExternal });
    }

    if (displayItems.length === 0 && fallbackDosen) {
        displayItems.push({
            label: `${fallbackDosen.nama_lengkap} (${fallbackDosen.inisial})`,
            isExternal: false,
        });
    }

    return displayItems;
}

export function getDosenAssignmentsText(
    dosens: DosenAssignment[] | undefined,
    fallbackDosen?: PrimaryDosen,
    emptyValue = "-",
) {
    const displayItems = buildDosenDisplayItems(dosens, fallbackDosen);
    return displayItems.length > 0 ? displayItems.map((item) => item.label).join(", ") : emptyValue;
}

interface DosenAssignmentsCellProps {
    dosens?: DosenAssignment[];
    fallbackDosen?: PrimaryDosen;
}

export function DosenAssignmentsCell({ dosens, fallbackDosen }: DosenAssignmentsCellProps) {
    const displayItems = buildDosenDisplayItems(dosens, fallbackDosen);

    if (displayItems.length === 0) {
        return "-";
    }

    return (
        <div className="space-y-1">
            {displayItems.map((item) => (
                <div key={item.label} className="flex items-center gap-2 leading-tight">
                    <span className="text-sm">{item.label}</span>
                    {item.isExternal && (
                        <span className="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700">
                            External
                        </span>
                    )}
                </div>
            ))}
        </div>
    );
}
