import { useState, useEffect, useMemo, useRef, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { X } from "lucide-react";
import { MasterDataPage, type Column, type FieldDef, type FilterOption } from "@/components/master/MasterDataPage";
import { DosenAssignmentsCell } from "@/components/master/DosenAssignmentsCell";
import { SearchableSelect } from "@/components/master/SearchableSelect";
import { Input } from "@/components/ui/input";
import api from "@/lib/api";
import { cachedApiGet } from "@/lib/api-cache";
import { useSemester } from "@/contexts/SemesterContext";
import { getSemesterOptions } from "@/lib/reference-options";
import { ConfirmActionDialog } from "@/components/shared/ConfirmActionDialog";
import { useAuth } from "@/contexts/AuthContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";

interface KelasKuliah {
    id: number;
    dosen_id: number;
    matakuliah_id: number;
    kelas_id: number;
    jumlah_mahasiswa: number;
    dosen?: { id: number; nama_lengkap: string; inisial: string; jurusan?: { id: number; nama_jurusan: string } };
    dosens?: Array<{ id: number; nama_lengkap: string; inisial: string; jurusan?: { id: number; nama_jurusan: string }; pivot?: { is_external: boolean; preferred_slot_id: number | null } }>;
    matakuliah?: {
        nama_mk: string;
        kode_mk: string;
        sks?: number;
        program_studi?: { id: number; nama_prodi: string; jurusan?: { id: number; nama_jurusan: string } };
    };
    kelas?: { nama_kelas: string; semester: number };
    sks?: number;
}

type TeamDosenEntry = {
    id: string;
    dosenId: string;
    preferredSlotId: string;
    isExternal: boolean;
    /** Teks pada input dosen luar (autocomplete) */
    externalQuery: string;
};

type ExternalSuggestion = {
    id: number;
    nama_lengkap: string;
    inisial: string;
    jurusan?: { nama_jurusan?: string };
};

type KelasKuliahDosenAssignment = NonNullable<KelasKuliah["dosens"]>[number];

const columns: Column<KelasKuliah>[] = [
    { key: "matakuliah_id" as keyof KelasKuliah, label: "Kode MK", sortable: false, render: (_, row) => row.matakuliah?.kode_mk || "-" },
    { key: "id" as keyof KelasKuliah, label: "Mata Kuliah", sortable: false, render: (_, row) => row.matakuliah?.nama_mk || "-" },
    { key: "sks" as keyof KelasKuliah, label: "SKS", headerAlign: "center", cellAlign: "center", render: (_, row) => `${row.matakuliah?.sks ?? row.sks ?? "-"} SKS` },
    { key: "kelas", label: "Kelas", render: (_, row) => row.kelas ? `${row.kelas.nama_kelas} - Smt ${row.kelas.semester}` : "-" },
    { key: "jumlah_mahasiswa", label: "Mahasiswa", cellAlign: "center", render: (v) => `${v} orang` },
    {
        key: "dosen",
        label: "Dosen Pengampu",
        render: (_, row) => <DosenAssignmentsCell dosens={row.dosens} fallbackDosen={row.dosen} />,
    },
    { key: "dosen_id" as keyof KelasKuliah, label: "Program Studi", cellAlign: "center", render: (_, row) => row.matakuliah?.program_studi?.nama_prodi || "-" },
    { key: "kelas_id" as keyof KelasKuliah, label: "Jurusan", cellAlign: "center", render: (_, row) => row.matakuliah?.program_studi?.jurusan?.nama_jurusan || "-" },
];

const emptyEntry = (): TeamDosenEntry => ({
    id: "0",
    dosenId: "",
    preferredSlotId: "",
    isExternal: false,
    externalQuery: "",
});

const NO_PREFERRED_SLOT_VALUE = "__none__";

export default function KelasKuliahPage() {
    const navigate = useNavigate();
    const { semesterTipe } = useSemester();
    const { canEditData, isJurusanRestricted, defaultsToOwnJurusan, user } = useAuth();
    const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
    const semesterOptions = useMemo(() => getSemesterOptions(semesterTipe), [semesterTipe]);
    const canEditKelasKuliah = canEditData || user?.role === "Koordinator Program Studi" || user?.role === "Kaprodi";
    const isProgramStudiRestricted = (user?.role === "Koordinator Program Studi" || user?.role === "Kaprodi") && !!user?.program_studi_id;
    const ownProgramStudiOption = useMemo(() => {
        if (!isProgramStudiRestricted || !user?.program_studi_id) {
            return null;
        }

        return {
            value: String(user.program_studi_id),
            label: user.program_studi_name || "Program Studi Anda",
        };
    }, [isProgramStudiRestricted, user?.program_studi_id, user?.program_studi_name]);
    const [prodiOptions, setProdiOptions] = useState<{ value: string; label: string }[]>([]);
    const [filterProdiOptions, setFilterProdiOptions] = useState<{ value: string; label: string }[]>([]);
    const [dosenOptions, setDosenOptions] = useState<{ value: string; label: string }[]>([]);
    const [mkOptions, setMkOptions] = useState<{ value: string; label: string }[]>([]);
    const [kelasOptions, setKelasOptions] = useState<{ value: string; label: string }[]>([]);
    const [slotOptions, setSlotOptions] = useState<{ value: string; label: string }[]>([]);
    const [teamDosenEntries, setTeamDosenEntries] = useState<TeamDosenEntry[]>([emptyEntry()]);
    const [dosenEntryCounter, setDosenEntryCounter] = useState(1);
    /** Saran nama dosen (lintas jurusan) per baris, dari API unscoped */
    const [externalSuggest, setExternalSuggest] = useState<Record<string, { items: ExternalSuggestion[]; loading: boolean }>>({});
    const debounceTimers = useRef<Record<string, ReturnType<typeof setTimeout>>>({});
    const [generatePromptOpen, setGeneratePromptOpen] = useState(false);

    const loadFilterProdiOptions = useCallback((jurusanId?: string | null, includeAll = false) => {
        if (ownProgramStudiOption) {
            setFilterProdiOptions([ownProgramStudiOption]);
            return;
        }

        if (!jurusanId && !includeAll) {
            setFilterProdiOptions([]);
            return;
        }

        const query = jurusanId ? `?jurusan_id=${jurusanId}` : "";
        api.get(`/referensi/program-studi${query}`).then((r) => {
            setFilterProdiOptions(r.data.map((p: { id: number; nama_prodi: string }) => ({
                value: String(p.id),
                label: p.nama_prodi,
            })));
        });
    }, [ownProgramStudiOption]);

    useEffect(() => {
        cachedApiGet<{ data: Array<{ id: number; nama_lengkap: string; inisial: string }> }>("/dosen?per_page=all").then((r) =>
            setDosenOptions(r.data.data.map((d: { id: number; nama_lengkap: string; inisial: string }) => ({ value: String(d.id), label: `${d.nama_lengkap} (${d.inisial})` })))
        );
        cachedApiGet<Array<{ id: number; hari?: { nama_hari: string }; waktu?: { pukul: string; sks?: number } }>>("/referensi/slot").then((r) =>
            setSlotOptions(r.data.map((slot: { id: number; hari?: { nama_hari: string }; waktu?: { pukul: string; sks?: number } }) => ({
                value: String(slot.id),
                label: `${slot.hari?.nama_hari || "-"} - ${slot.waktu?.pukul || "-"}${slot.waktu?.sks ? ` (${slot.waktu.sks} SKS)` : ""}`,
            })))
        );
    }, []);

    useEffect(() => {
        cachedApiGet<Array<{ id: number; nama_prodi: string }>>("/referensi/program-studi").then((r) => {
            const options = r.data.map((p: { id: number; nama_prodi: string }) => ({ value: String(p.id), label: p.nama_prodi }));
            setProdiOptions(ownProgramStudiOption ? options.filter((option) => option.value === ownProgramStudiOption.value) : options);
        });
    }, [ownProgramStudiOption]);

    useEffect(() => {
        if (ownProgramStudiOption) {
            setFilterProdiOptions([ownProgramStudiOption]);
        } else if (defaultsToOwnJurusan && user?.jurusan_id) {
            loadFilterProdiOptions(String(user.jurusan_id));
        } else {
            loadFilterProdiOptions(null, true);
        }
    }, [defaultsToOwnJurusan, loadFilterProdiOptions, ownProgramStudiOption, user?.jurusan_id]);

    const fetchExternalSuggestions = useCallback((entryId: string, q: string) => {
        if (debounceTimers.current[entryId]) {
            clearTimeout(debounceTimers.current[entryId]);
        }
        const trimmed = q.trim();
        if (trimmed.length < 1) {
            setExternalSuggest((prev) => ({ ...prev, [entryId]: { items: [], loading: false } }));
            return;
        }
        setExternalSuggest((prev) => ({ ...prev, [entryId]: { items: [], loading: true } }));
        debounceTimers.current[entryId] = setTimeout(() => {
            api
                .get("/dosen", { params: { unscoped: 1, outside_jurusan: 1, search: trimmed, per_page: 20 } })
                .then((r) => {
                    const list = r.data.data ?? r.data ?? [];
                    setExternalSuggest((prev) => ({
                        ...prev,
                        [entryId]: { items: Array.isArray(list) ? list : [], loading: false },
                    }));
                })
                .catch(() => {
                    setExternalSuggest((prev) => ({ ...prev, [entryId]: { items: [], loading: false } }));
                });
        }, 280);
    }, []);

    const fetchMatakuliahAndKelas = (prodiId: string, semester: string) => {
        cachedApiGet<{ data: Array<{ id: number; kode_mk: string; nama_mk: string; sks?: number }> }>(`/matakuliah?per_page=all&program_studi_id=${prodiId}&semester=${semester}`).then(r =>
            setMkOptions(r.data.data.map((m: { id: number; kode_mk: string; nama_mk: string; sks?: number }) => ({
                value: String(m.id),
                label: `${m.kode_mk} - ${m.nama_mk}${m.sks ? ` (${m.sks} SKS)` : ""}`,
            })))
        );
        cachedApiGet<{ data: Array<{ id: number; nama_kelas: string; semester: number }> }>(`/kelas?per_page=all&program_studi_id=${prodiId}&semester=${semester}`).then(r =>
            setKelasOptions(r.data.data.map((k: { id: number; nama_kelas: string; semester: number }) => ({ value: String(k.id), label: `${k.nama_kelas} (Sem ${k.semester})` })))
        );
    };

    const addDosenEntry = () => {
        setTeamDosenEntries((prev) => [...prev, { ...emptyEntry(), id: String(dosenEntryCounter) }]);
        setDosenEntryCounter((prev) => prev + 1);
    };

    const removeDosenEntry = (id: string) => {
        setTeamDosenEntries((prev) => prev.filter((entry) => entry.id !== id));
        setExternalSuggest((prev) => {
            const next = { ...prev };
            delete next[id];
            return next;
        });
    };

    const updateDosenEntry = (id: string, patch: Partial<TeamDosenEntry>) => {
        setTeamDosenEntries((prev) => prev.map((entry) => (entry.id === id ? { ...entry, ...patch } : entry)));
    };

    const resetTeamDosenEntries = () => {
        setTeamDosenEntries([emptyEntry()]);
        setDosenEntryCounter(1);
        setExternalSuggest({});
    };

    const handleFormChange = (key: string, value: string, currentFormState: Record<string, string>, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => {
        if (key === "program_studi_id") {
            setFormState(prev => ({ ...prev, semester: "", matakuliah_id: "", kelas_id: "" }));
            setMkOptions([]); setKelasOptions([]);
        } else if (key === "semester") {
            setFormState(prev => ({ ...prev, matakuliah_id: "", kelas_id: "" }));
            if (currentFormState.program_studi_id && value) fetchMatakuliahAndKelas(currentFormState.program_studi_id, value);
            else { setMkOptions([]); setKelasOptions([]); }
        }
    };

    const handleModalOpen = (row: KelasKuliah | null, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => {
        if (row) {
            const prodiId = String(row.matakuliah?.program_studi?.id || "");
            const semester = String(row.kelas?.semester || "");
            const sourceDosens: KelasKuliahDosenAssignment[] = row.dosens && row.dosens.length > 0
                ? row.dosens
                : row.dosen
                    ? [{
                        ...row.dosen,
                        pivot: { preferred_slot_id: null, is_external: false },
                    }]
                    : [];
            const entries: TeamDosenEntry[] = sourceDosens.length > 0
                ? sourceDosens.map((d, index) => {
                    const isEx = !!(d as { pivot?: { is_external?: boolean } }).pivot?.is_external;
                    const label = `${d.nama_lengkap} (${d.inisial})`;
                    const extQ = isEx
                        ? `${label}${d.jurusan?.nama_jurusan ? ` — ${d.jurusan.nama_jurusan}` : ""}`
                        : "";
                    return {
                        id: String(index),
                        dosenId: String(d.id),
                        preferredSlotId: String((d as { pivot?: { preferred_slot_id?: number } }).pivot?.preferred_slot_id || ""),
                        isExternal: isEx,
                        externalQuery: extQ,
                    };
                })
                : [emptyEntry()];

            setFormState(prev => ({
                ...prev,
                program_studi_id: prodiId,
                semester: semester,
            }));
            setTeamDosenEntries(entries);
            setDosenEntryCounter(entries.length + 1);
            setExternalSuggest({});

            if (prodiId && semester) fetchMatakuliahAndKelas(prodiId, semester);
        } else {
            setMkOptions([]);
            setKelasOptions([]);
            resetTeamDosenEntries();
            if (ownProgramStudiOption) {
                setFormState(prev => ({ ...prev, program_studi_id: ownProgramStudiOption.value }));
            }
        }
    };

    const formFields = useMemo<FieldDef[]>(() => [
        { key: "program_studi_id", label: "Program Studi", type: "select", required: true, options: prodiOptions, disabled: prodiOptions.length === 0 || isProgramStudiRestricted },
        { key: "semester", label: "Semester", type: "select", required: true, options: semesterOptions, disabled: prodiOptions.length === 0 },
        { key: "matakuliah_id", label: "Mata Kuliah", type: "select", required: true, options: mkOptions, disabled: mkOptions.length === 0 },
        { key: "kelas_id", label: "Kelas", type: "select", required: true, options: kelasOptions, disabled: kelasOptions.length === 0 },
        { key: "jumlah_mahasiswa", label: "Jumlah Mahasiswa", type: "number", required: true, placeholder: "Contoh: 35" },
        {
            key: "team_dosens",
            label: "Dosen Pengampu",
            type: "custom",
            className: "md:col-span-2",
            render: () => (
                <div className="space-y-3 rounded-2xl border border-border/50 bg-surface p-4">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p className="text-sm font-medium text-foreground">Dosen pengampu</p>
                            <p className="text-xs text-muted-foreground mt-1">Tim pengajar: tambah baris jika lebih dari satu. Untuk dosen dari jurusan lain, centang &quot;Dosen luar jurusan&quot; lalu ketik nama dan pilih dari saran.</p>
                        </div>
                        <button
                            type="button"
                            onClick={addDosenEntry}
                            className="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-primary bg-primary/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary/15"
                        >
                            + Tambah Dosen
                        </button>
                    </div>

                    <div className="space-y-3">
                        {teamDosenEntries.map((entry, index) => (
                            <div key={entry.id} className="space-y-2 rounded-lg border border-border/30 bg-background p-3">
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id={`external-${entry.id}`}
                                        checked={entry.isExternal}
                                        onChange={(e) => {
                                            const checked = e.target.checked;
                                            updateDosenEntry(entry.id, {
                                                isExternal: checked,
                                                dosenId: "",
                                                externalQuery: "",
                                            });
                                            setExternalSuggest((prev) => ({ ...prev, [entry.id]: { items: [], loading: false } }));
                                        }}
                                        className="h-4 w-4 rounded border-gray-300"
                                    />
                                    <label htmlFor={`external-${entry.id}`} className="text-xs font-medium text-muted-foreground">
                                        Dosen luar jurusan
                                    </label>
                                </div>

                                <div className="grid gap-2 sm:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_auto]">
                                    <div className="relative">
                                        <label className="block text-xs font-medium text-muted-foreground mb-1">Dosen</label>
                                        {entry.isExternal ? (
                                            <div className="space-y-1">
                                                <Input
                                                    type="text"
                                                    autoComplete="off"
                                                    placeholder="Ketik nama dosen luar jurusan"
                                                    value={entry.externalQuery}
                                                    onChange={(e) => {
                                                        const v = e.target.value;
                                                        updateDosenEntry(entry.id, { externalQuery: v, dosenId: "" });
                                                        fetchExternalSuggestions(entry.id, v);
                                                    }}
                                                    className="form-field-input h-10"
                                                />
                                                {entry.externalQuery.trim().length > 0 && !entry.dosenId && (
                                                    <div className="rounded-md border border-border bg-popover text-popover-foreground shadow-md max-h-40 overflow-y-auto z-10">
                                                        {externalSuggest[entry.id]?.loading ? (
                                                            <p className="px-3 py-2 text-xs text-muted-foreground">Mencari…</p>
                                                        ) : (externalSuggest[entry.id]?.items ?? []).length === 0 ? (
                                                            <p className="px-3 py-2 text-xs text-muted-foreground">Tidak ada dosen yang cocok. Ubah kata kunci.</p>
                                                        ) : (
                                                            (externalSuggest[entry.id]?.items ?? []).map((s) => (
                                                                <button
                                                                    key={s.id}
                                                                    type="button"
                                                                    className="w-full text-left px-3 py-2 text-sm hover:bg-muted/60 border-b border-border/40 last:border-0"
                                                                    onClick={() => {
                                                                        updateDosenEntry(entry.id, {
                                                                            dosenId: String(s.id),
                                                                            externalQuery: `${s.nama_lengkap} (${s.inisial})${s.jurusan?.nama_jurusan ? ` — ${s.jurusan.nama_jurusan}` : ""}`,
                                                                        });
                                                                        setExternalSuggest((prev) => ({
                                                                            ...prev,
                                                                            [entry.id]: { items: [], loading: false },
                                                                        }));
                                                                    }}
                                                                >
                                                                    <span className="font-medium">{s.nama_lengkap}</span>
                                                                    <span className="text-muted-foreground"> ({s.inisial})</span>
                                                                    {s.jurusan?.nama_jurusan && (
                                                                        <span className="block text-xs text-muted-foreground">{s.jurusan.nama_jurusan}</span>
                                                                    )}
                                                                </button>
                                                            ))
                                                        )}
                                                    </div>
                                                )}
                                                {entry.dosenId && (
                                                    <p className="text-xs text-emerald-700 dark:text-emerald-400">Terhubung ke data dosen (ID: {entry.dosenId})</p>
                                                )}
                                            </div>
                                        ) : (
                                            <SearchableSelect
                                                value={entry.dosenId || undefined}
                                                placeholder="Pilih dosen"
                                                searchPlaceholder="Cari dosen..."
                                                options={dosenOptions}
                                                onValueChange={(value) => updateDosenEntry(entry.id, { dosenId: value })}
                                            />
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-muted-foreground mb-1">Preferensi Slot (Opsional)</label>
                                        <SearchableSelect
                                            value={entry.preferredSlotId || NO_PREFERRED_SLOT_VALUE}
                                            onValueChange={(value: string) =>
                                                updateDosenEntry(entry.id, {
                                                    preferredSlotId: value === NO_PREFERRED_SLOT_VALUE ? "" : value,
                                                })
                                            }
                                            placeholder="Pilih slot"
                                            searchPlaceholder="Cari slot..."
                                            options={[
                                                { value: NO_PREFERRED_SLOT_VALUE, label: "Tanpa preferensi" },
                                                ...slotOptions,
                                            ]}
                                        />
                                    </div>
                                    <div className="flex items-end justify-end h-full">
                                        {index > 0 && (
                                            <button
                                                type="button"
                                                onClick={() => removeDosenEntry(entry.id)}
                                                className="group inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border/60 bg-white text-[0px] leading-none shadow-sm transition-colors hover:border-destructive/35 hover:bg-destructive/5"
                                                aria-label="Hapus dosen"
                                                title="Hapus dosen"
                                            >
                                                <X className="h-4 w-4 text-muted-foreground transition-colors group-hover:text-destructive" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ),
        },
    ], [prodiOptions, dosenOptions, mkOptions, kelasOptions, slotOptions, teamDosenEntries, externalSuggest, fetchExternalSuggestions, isProgramStudiRestricted]);

    const handleSaveKelasKuliah = async (formState: Record<string, string>, editingRow: KelasKuliah | null) => {
        const selectedDosen = teamDosenEntries
            .filter((entry) => entry.dosenId)
            .map((entry) => ({
                dosen_id: entry.dosenId,
                preferred_slot_id: entry.preferredSlotId || null,
                is_external: entry.isExternal,
            }));

        if (selectedDosen.length === 0) {
            throw new Error("Pilih minimal satu dosen pengampu.");
        }
        const uniqueDosenIds = new Set(selectedDosen.map((item) => item.dosen_id));
        if (uniqueDosenIds.size !== selectedDosen.length) {
            throw new Error("Dosen pengampu tidak boleh duplikat. Setiap dosen hanya boleh dipilih satu kali.");
        }

        for (const e of teamDosenEntries) {
            if (e.isExternal && e.externalQuery.trim() && !e.dosenId) {
                throw new Error('Untuk dosen luar jurusan, pilih salah satu nama dari daftar saran di bawah kolom ketik.');
            }
        }

        const payload: Record<string, unknown> = {
            ...formState,
            dosen_id: selectedDosen[0].dosen_id,
            dosen_ids: selectedDosen.map((item) => item.dosen_id),
            preferred_slot_ids: selectedDosen.map((item) => item.preferred_slot_id),
            is_externals: selectedDosen.map((item) => item.is_external),
            dosen_team: selectedDosen.map((item) => ({
                dosen_id: item.dosen_id,
                preferred_slot_id: item.preferred_slot_id,
                is_external: item.is_external,
            })),
        };

        delete payload.dosen_luar;

        if (editingRow) {
            await api.put(`/kelas-kuliah/${editingRow.id}`, payload);
        } else {
            await api.post(`/kelas-kuliah`, payload);
            setGeneratePromptOpen(true);
        }
    };

    const filterOptions = useMemo<FilterOption[]>(() => {
        const filters: FilterOption[] = [];
        if (!isJurusanRestricted) {
            filters.push({ label: "Semua Jurusan", key: "jurusan_id", options: jurusanOptions });
        }
        filters.push({ label: "Semua Program Studi", key: "program_studi_id", options: filterProdiOptions });
        return filters;
    }, [filterProdiOptions, isJurusanRestricted, jurusanOptions]);

    const initialFilters = useMemo<Record<string, string>>(() => {
        if (ownProgramStudiOption) {
            return { program_studi_id: ownProgramStudiOption.value };
        }

        if (defaultsToOwnJurusan && user?.jurusan_id) {
            return { jurusan_id: String(user.jurusan_id) };
        }
        const emptyFilters: Record<string, string> = {};
        return emptyFilters;
    }, [defaultsToOwnJurusan, ownProgramStudiOption, user?.jurusan_id]);

    const searchPredicate = useCallback((row: KelasKuliah, normalizedQuery: string) => {
        const primaryDosen = row.dosen ? `${row.dosen.nama_lengkap} ${row.dosen.inisial}` : "";
        const teamDosen = (row.dosens ?? [])
            .map((d) => `${d.nama_lengkap} ${d.inisial} ${d.jurusan?.nama_jurusan || ""}`)
            .join(" ");
        const searchableText = [
            row.matakuliah?.kode_mk,
            row.matakuliah?.nama_mk,
            row.matakuliah?.sks,
            row.sks,
            row.kelas?.nama_kelas,
            row.kelas?.semester,
            row.matakuliah?.program_studi?.nama_prodi,
            row.matakuliah?.program_studi?.jurusan?.nama_jurusan,
            primaryDosen,
            teamDosen,
            row.jumlah_mahasiswa,
        ]
            .filter((value) => value !== null && value !== undefined)
            .join(" ")
            .toLowerCase();

        return searchableText.includes(normalizedQuery);
    }, []);

    const filterPredicate = useCallback((row: KelasKuliah, activeFilters: Record<string, string>) => {
        if (activeFilters.jurusan_id) {
            if (String(row.matakuliah?.program_studi?.jurusan?.id ?? "") !== activeFilters.jurusan_id) {
                return false;
            }
        }
        if (activeFilters.program_studi_id) {
            return String(row.matakuliah?.program_studi?.id || "") === activeFilters.program_studi_id;
        }
        return true;
    }, []);

    return (
        <>
            <MasterDataPage<KelasKuliah>
                title="Data Kelas Kuliah"
                description={`Kelola penawaran mata kuliah dan kelas yang dibuka (Semester ${semesterTipe === 'ganjil' ? 'Ganjil' : 'Genap'})`}
                apiEndpoint={`/kelas-kuliah?per_page=all&semester_tipe=${semesterTipe}`}
                columns={columns}
                formFields={formFields}
                filterOptions={filterOptions}
                initialFilters={initialFilters}
                filterResetMap={{ jurusan_id: ["program_studi_id"] }}
                onFilterChange={(key, value) => {
                    if (key === "jurusan_id") {
                        loadFilterProdiOptions(value || null, !value);
                    }
                }}
                searchPredicate={searchPredicate}
                filterPredicate={filterPredicate}
                onFormChange={handleFormChange}
                onModalOpen={handleModalOpen}
                onCustomSave={handleSaveKelasKuliah}
                readOnly={!canEditKelasKuliah}
                importUrl={canEditKelasKuliah ? "/kelas-kuliah/import" : undefined}
                templateUrl={canEditKelasKuliah ? "/template/template-import-kelas-kuliah.xlsx" : undefined}
                pageSize={7}
            />
            <ConfirmActionDialog
                open={generatePromptOpen}
                onOpenChange={setGeneratePromptOpen}
                title="Generate ulang jadwal?"
                description="Kelas kuliah baru sudah ditambahkan. Anda dapat langsung menuju proses generate agar jadwal terbaru ikut dihitung."
                confirmLabel="Generate Sekarang"
                onConfirm={() => {
                    setGeneratePromptOpen(false);
                    navigate("/scheduling/auto");
                }}
            />
        </>
    );
}
