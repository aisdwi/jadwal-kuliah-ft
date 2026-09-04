import { useCallback, useState } from "react";
import api from "@/lib/api";
import { mapProgramStudiOptions, type ProgramStudiReference, type SelectOption } from "@/lib/reference-options";

function resolveProgramStudiItems(payload: unknown): ProgramStudiReference[] {
    if (Array.isArray(payload)) {
        return payload as ProgramStudiReference[];
    }

    if (payload && typeof payload === "object" && "data" in payload && Array.isArray((payload as { data?: unknown }).data)) {
        return (payload as { data: ProgramStudiReference[] }).data;
    }

    return [];
}

export function useProgramStudiOptions() {
    const [prodiOptions, setProdiOptions] = useState<SelectOption[]>([]);

    const fetchProdiOptions = useCallback(async (jurusanId?: string | null, includeAll = false) => {
        if (!jurusanId && !includeAll) {
            setProdiOptions([]);
            return;
        }

        const query = jurusanId ? `?jurusan_id=${jurusanId}` : "";
        const response = await api.get(`/referensi/program-studi${query}`);
        setProdiOptions(mapProgramStudiOptions(resolveProgramStudiItems(response.data)));
    }, []);

    const clearProdiOptions = useCallback(() => {
        setProdiOptions([]);
    }, []);

    return {
        prodiOptions,
        fetchProdiOptions,
        clearProdiOptions,
    };
}
