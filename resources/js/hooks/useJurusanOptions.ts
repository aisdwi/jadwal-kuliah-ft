import { useEffect, useState } from "react";
import api from "@/lib/api";
import { mapJurusanOptions, type SelectOption, type JurusanReference } from "@/lib/reference-options";

function resolveJurusanItems(payload: unknown): JurusanReference[] {
    if (Array.isArray(payload)) {
        return payload as JurusanReference[];
    }

    if (payload && typeof payload === "object" && "data" in payload && Array.isArray((payload as { data?: unknown }).data)) {
        return (payload as { data: JurusanReference[] }).data;
    }

    return [];
}

export function useJurusanOptions(isJurusanRestricted: boolean, jurusanId: number | null | undefined) {
    const [jurusanOptions, setJurusanOptions] = useState<SelectOption[]>([]);

    useEffect(() => {
        let isCancelled = false;

        api.get("/referensi/jurusan").then((response) => {
            if (isCancelled) {
                return;
            }

            const mappedOptions = mapJurusanOptions(resolveJurusanItems(response.data));

            if (isJurusanRestricted && jurusanId) {
                const selectedJurusan = mappedOptions.find((option) => option.value === String(jurusanId));
                setJurusanOptions(selectedJurusan ? [selectedJurusan] : []);
                return;
            }

            setJurusanOptions(mappedOptions);
        });

        return () => {
            isCancelled = true;
        };
    }, [isJurusanRestricted, jurusanId]);

    return jurusanOptions;
}
