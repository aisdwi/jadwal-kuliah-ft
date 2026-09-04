import { motion } from "framer-motion";
import { ArrowUp, Plus, Search, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { Dispatch, ReactNode, SetStateAction } from "react";
import type { FilterOption } from "@/components/master/masterDataTypes";

const FILTER_ALL = "__all__";

type MasterDataToolbarProps = {
    search: string;
    setSearch: Dispatch<SetStateAction<string>>;
    setPage: Dispatch<SetStateAction<number>>;
    filters: Record<string, string>;
    setFilters: Dispatch<SetStateAction<Record<string, string>>>;
    filterOptions: FilterOption[];
    filterResetMap: Record<string, string[]>;
    onFilterChange?: (key: string, value: string, nextFilters: Record<string, string>) => void;
    readOnly: boolean;
    extraActions?: ReactNode;
    hideAddButton?: boolean;
    importEnabled: boolean;
    deleteAllEnabled: boolean;
    onAdd: () => void;
    onImport: () => void;
    onDeleteAll: () => void;
};

export function MasterDataToolbar({
    search,
    setSearch,
    setPage,
    filters,
    setFilters,
    filterOptions,
    filterResetMap,
    onFilterChange,
    readOnly,
    extraActions,
    hideAddButton,
    importEnabled,
    deleteAllEnabled,
    onAdd,
    onImport,
    onDeleteAll,
}: MasterDataToolbarProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="my-2 flex shrink-0 flex-col items-center justify-between gap-2 sm:flex-row"
        >
            <div className="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto flex-1">
                <div className="relative w-full sm:w-72 md:w-80">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Cari data..."
                        value={search}
                        onChange={(event) => {
                            setSearch(event.target.value);
                            setPage(1);
                        }}
                        className="table-search-input pl-9 w-full"
                    />
                </div>
                {filterOptions.map((filter) => (
                    <div key={filter.key} className="w-full sm:w-auto">
                        <Select
                            value={filters[filter.key] || FILTER_ALL}
                            onValueChange={(value) => {
                                const normalizedValue = value === FILTER_ALL ? "" : value;
                                const nextFilters = { ...filters, [filter.key]: normalizedValue };

                                for (const resetKey of filterResetMap[filter.key] ?? []) {
                                    nextFilters[resetKey] = "";
                                }

                                setFilters(nextFilters);
                                onFilterChange?.(filter.key, normalizedValue, nextFilters);
                                setPage(1);
                            }}
                        >
                            <SelectTrigger className="form-field-select-trigger w-full sm:w-[200px]">
                                <SelectValue placeholder={filter.label} />
                            </SelectTrigger>
                            <SelectContent className="form-field-select-content">
                                <SelectItem value={FILTER_ALL} className="text-sm">
                                    {filter.label}
                                </SelectItem>
                                {filter.options.map((option) => (
                                    <SelectItem key={option.value} value={option.value} className="text-sm">
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ))}
            </div>

            <div className="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                {!readOnly && extraActions}
                {!readOnly && deleteAllEnabled && (
                    <Button
                        onClick={onDeleteAll}
                        variant="destructive"
                        className="gap-2 w-full sm:w-auto rounded-xl px-4 py-2 shadow-md transition-all duration-300 hover:brightness-110 active:scale-95"
                    >
                        <Trash2 className="h-4 w-4" />
                        Delete All
                    </Button>
                )}
                {!readOnly && !hideAddButton && (
                    <Button onClick={onAdd} className="gradient-btn gap-2 w-full sm:w-auto">
                        <Plus className="h-4 w-4" />
                        Tambah Baru
                    </Button>
                )}
                {!readOnly && importEnabled && (
                    <Button
                        onClick={onImport}
                        className="bg-amber-500 hover:bg-amber-600 text-white gap-2 w-full sm:w-auto rounded-xl px-4 py-2 shadow-md transition-all duration-300 hover:brightness-110 hover:shadow-xl hover:shadow-amber-500/30 active:scale-95"
                    >
                        <ArrowUp className="h-4 w-4" />
                        Import Excel
                    </Button>
                )}
            </div>
        </motion.div>
    );
}
