import type React from "react";

export interface Column<T> {
    key: keyof T & string;
    label: string;
    sortable?: boolean;
    render?: (value: T[keyof T], row: T) => React.ReactNode;
    headerAlign?: "left" | "center" | "right";
    cellAlign?: "left" | "center" | "right";
}

export interface FieldDef {
    key: string;
    label: string;
    type?: "text" | "number" | "select" | "multiselect" | "email" | "time" | "custom";
    placeholder?: string;
    options?: { value: string; label: string }[];
    required?: boolean;
    disabled?: boolean;
    searchable?: boolean;
    className?: string;
    render?: (
        formState: Record<string, string>,
        setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>,
    ) => React.ReactNode;
}

export interface FilterOption {
    label: string;
    key: string;
    options: { value: string; label: string }[];
}

export interface TableLayoutOptions {
    sparseMode?: "fill" | "shrink-with-min";
    sparseMinRows?: number;
    sparseMaxRows?: number;
    rowDensity?: "default" | "spacious";
    adaptivePageSize?: boolean;
}

export interface MasterDataPageProps<T extends { id: string | number }> {
    title: string;
    description: string;
    columns: Column<T>[];
    data?: T[];
    apiEndpoint?: string;
    formFields: FieldDef[];
    filterOptions?: FilterOption[];
    pageSize?: number;
    topContent?: React.ReactNode;
    onFormChange?: (
        key: string,
        value: string,
        currentFormState: Record<string, string>,
        setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>,
    ) => void;
    onModalOpen?: (row: T | null, setFormState: React.Dispatch<React.SetStateAction<Record<string, string>>>) => void;
    importUrl?: string;
    templateUrl?: string;
    extraActions?: React.ReactNode;
    hideAddButton?: boolean;
    hideDeleteAllButton?: boolean;
    onCustomSave?: (formState: Record<string, string>, editingRow: T | null) => Promise<void>;
    readOnly?: boolean;
    searchPredicate?: (row: T, normalizedQuery: string) => boolean;
    filterPredicate?: (row: T, activeFilters: Record<string, string>) => boolean;
    initialFilters?: Record<string, string>;
    filterResetMap?: Record<string, string[]>;
    onFilterChange?: (key: string, value: string, nextFilters: Record<string, string>) => void;
    serverSide?: boolean;
    tableLayout?: TableLayoutOptions;
}

export interface MasterDataQueryResult<T> {
    rows: T[];
    pagination: {
        total: number;
        currentPage: number;
        perPage: number;
        lastPage: number;
    };
}
