import { useState, useMemo, useEffect, useLayoutEffect, useDeferredValue, useCallback, useRef } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    X,
} from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import api from "@/lib/api";
import { cachedApiGet, getCachedApiResponse, invalidateApiCache } from "@/lib/api-cache";
import { getUserFriendlyError } from "@/lib/error-messages";
import { useAuth } from "@/contexts/AuthContext";
import { useAdaptiveTableLayout } from "@/hooks/useAdaptiveTableLayout";
import { useToast } from "@/hooks/use-toast";
import { ExcelImportModal } from "./ExcelImportModal";
import { ConfirmActionDialog } from "@/components/shared/ConfirmActionDialog";
import { MasterDataToolbar } from "@/components/master/MasterDataToolbar";
import { SearchableSelect } from "@/components/master/SearchableSelect";
import { resolveMasterDataResult } from "@/components/master/masterDataResult";
import type { MasterDataPageProps } from "@/components/master/masterDataTypes";
export type { Column, FieldDef, FilterOption } from "@/components/master/masterDataTypes";

// ── Types ──────────────────────────────────────────────
type SortDir = "asc" | "desc" | null;
const tableHeaderCellClass = "h-12 px-3 py-0 text-[11px] font-bold uppercase tracking-wider leading-tight align-middle border border-[#dee2e6]";
const SEARCHABLE_SELECT_MIN_OPTIONS = 8;

// ── Component ──────────────────────────────────────────
export function MasterDataPage<T extends { id: string | number }>({
    title,
    description,
    columns,
    data = [],
    apiEndpoint,
    formFields,
    filterOptions = [],
    pageSize: fallbackPageSize = 7,
    topContent,
    onFormChange,
    onModalOpen,
    importUrl,
    templateUrl,
    extraActions,
    hideAddButton,
    hideDeleteAllButton,
    onCustomSave,
    readOnly: readOnlyProp,
    searchPredicate,
    filterPredicate,
    initialFilters = {},
    filterResetMap = {},
    onFilterChange,
    serverSide = false,
    tableLayout,
}: MasterDataPageProps<T>) {
    const { canEditData, user } = useAuth();
    const { toast } = useToast();
    
    // Variabel pengecekan kondisi sesuai role user yang login
    const canEdit = ["Super Admin", "Admin Jurusan", "Admin Fakultas"].includes(user?.role || "");
    
    // Gunakan canEdit untuk menentukan readOnly jika readOnlyProp tidak di-pass secara eksplisit
    const readOnly = readOnlyProp ?? !canEdit;
    const initialClientCache = !serverSide
        ? getCachedApiResponse(apiEndpoint, { params: { per_page: "all" } })
        : null;
    const initialClientData = initialClientCache
        ? resolveMasterDataResult<T>(initialClientCache.data, false, 1, fallbackPageSize)
        : null;
    const [fetchedData, setFetchedData] = useState<T[]>(() => initialClientData?.rows ?? []);
    const [isLoading, setIsLoading] = useState(() => Boolean(apiEndpoint && !initialClientData));
    const [isSaving, setIsSaving] = useState(false);
    const [search, setSearch] = useState("");
    const [filters, setFilters] = useState<Record<string, string>>(() => initialFilters);
    const [sortKey, setSortKey] = useState<string | null>(null);
    const [sortDir, setSortDir] = useState<SortDir>(null);
    const [page, setPage] = useState(1);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRow, setEditingRow] = useState<T | null>(null);
    const [formState, setFormState] = useState<Record<string, string>>({});
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<T | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteAllOpen, setDeleteAllOpen] = useState(false);
    const [isDeletingAll, setIsDeletingAll] = useState(false);
    const deferredSearch = useDeferredValue(search);
    const [paginationMeta, setPaginationMeta] = useState(() => initialClientData?.pagination ?? {
        total: 0,
        currentPage: 1,
        perPage: fallbackPageSize,
        lastPage: 1,
    });
    const activeData = apiEndpoint ? fetchedData : data;
    const tableCardRef = useRef<HTMLDivElement | null>(null);
    const tableBodyRef = useRef<HTMLDivElement | null>(null);
    const tableHeaderRef = useRef<HTMLTableSectionElement | null>(null);
    const tableFooterRef = useRef<HTMLDivElement | null>(null);
    const firstDataRowRef = useRef<HTMLTableRowElement | null>(null);
    const requestSeqRef = useRef(0);
    const initialFiltersKey = JSON.stringify(initialFilters);

    useEffect(() => {
        setFilters((prev) => {
            const next = { ...prev };
            let changed = false;

            for (const [key, value] of Object.entries(initialFilters)) {
                if (!next[key] && value) {
                    next[key] = value;
                    changed = true;
                }
            }

            return changed ? next : prev;
        });
    }, [initialFiltersKey]);

    // ── Filtering ────────────────────────────────────────
    const filtered = useMemo(() => {
        let result = [...activeData];

        if (serverSide) {
            if (sortKey && sortDir) {
                result.sort((a, b) => {
                    const av = a[sortKey as keyof T];
                    const bv = b[sortKey as keyof T];
                    if (typeof av === "number" && typeof bv === "number") return sortDir === "asc" ? av - bv : bv - av;
                    return sortDir === "asc"
                        ? String(av).localeCompare(String(bv))
                        : String(bv).localeCompare(String(av));
                });
            }

            return result;
        }

        // search across all string columns
        if (deferredSearch.trim()) {
            const q = deferredSearch.toLowerCase();
            if (searchPredicate) {
                result = result.filter((row) => searchPredicate(row, q));
            } else {
                result = result.filter((row) =>
                    columns.some((col) => {
                        const v = row[col.key];
                        return typeof v === "string" && v.toLowerCase().includes(q) || typeof v === "number" && v.toString().includes(q);
                    })
                );
            }
        }

        // select filters
        const activeFilters = Object.entries(filters).filter(([, value]) => !!value);
        if (activeFilters.length > 0) {
            if (filterPredicate) {
                result = result.filter((row) => filterPredicate(row, filters));
            } else {
                for (const [key, value] of activeFilters) {
                    result = result.filter((row) => {
                        const cellVal = row[key as keyof T];
                        // Handle nested object matching if needed, but standard is direct string match
                        if (typeof cellVal === 'object' && cellVal !== null) {
                            return Object.values(cellVal).some(v => String(v) === value);
                        }
                        return String(cellVal) === value;
                    });
                }
            }
        }

        // sorting
        if (sortKey && sortDir) {
            result.sort((a, b) => {
                const av = a[sortKey as keyof T];
                const bv = b[sortKey as keyof T];
                if (typeof av === "number" && typeof bv === "number") return sortDir === "asc" ? av - bv : bv - av;
                return sortDir === "asc"
                    ? String(av).localeCompare(String(bv))
                    : String(bv).localeCompare(String(av));
            });
        }

        return result;
    }, [activeData, deferredSearch, filters, sortKey, sortDir, columns, searchPredicate, filterPredicate, serverSide]);

    const isSparseTableLayout = tableLayout?.sparseMode === "shrink-with-min";
    const visibleRowCountForLayout = serverSide ? fetchedData.length : filtered.length;
    const {
        tableCardMaxHeight,
        measuredPageSize,
        committedPageSize,
        isReady: isAdaptiveLayoutReady,
    } = useAdaptiveTableLayout({
        containerRef: tableCardRef,
        headerRef: tableHeaderRef,
        footerRef: tableFooterRef,
        firstDataRowRef,
        fallbackPageSize,
        sparseLayout: {
            enabled: isSparseTableLayout,
            minRows: tableLayout?.sparseMinRows,
            maxRowsToShrink: tableLayout?.sparseMaxRows,
            rowCount: isSparseTableLayout ? visibleRowCountForLayout : 0,
        },
        layoutDeps: [
            columns.length,
            filterOptions.length,
            readOnly,
            serverSide,
            title,
            description,
            Boolean(topContent),
            isLoading,
            isSparseTableLayout ? visibleRowCountForLayout : 0,
        ],
    });
    const shouldUseAdaptivePageSize = tableLayout?.adaptivePageSize ?? true;
    const effectivePageSize = shouldUseAdaptivePageSize
        ? (committedPageSize > 0 ? committedPageSize : measuredPageSize)
        : fallbackPageSize;
    const tableCardStyle = tableCardMaxHeight
        ? { height: `${tableCardMaxHeight}px`, maxHeight: `${tableCardMaxHeight}px` }
        : undefined;

    const fetchData = useCallback(() => {
        if (!apiEndpoint) {
            return;
        }

        const requestSeq = requestSeqRef.current + 1;
        requestSeqRef.current = requestSeq;
        const params: Record<string, unknown> = serverSide
            ? {
                page,
                per_page: effectivePageSize,
                ...(deferredSearch.trim() ? { search: deferredSearch.trim() } : {}),
                ...Object.fromEntries(
                    Object.entries(filters).filter(([, value]) => !!value)
                ),
            }
            : { per_page: "all" };
        const cached = getCachedApiResponse(apiEndpoint, { params });

        if (cached) {
            const result = resolveMasterDataResult<T>(cached.data, serverSide, page, effectivePageSize);
            setFetchedData(result.rows);
            setPaginationMeta(result.pagination);
            setIsLoading(false);
            return;
        }

        setIsLoading(true);
        cachedApiGet(apiEndpoint, { params })
            .then((response) => {
                if (requestSeqRef.current !== requestSeq) {
                    return;
                }

                const result = resolveMasterDataResult<T>(response.data, serverSide, page, effectivePageSize);
                setFetchedData(result.rows);
                setPaginationMeta(result.pagination);
            })
            .catch((err) => {
                if (requestSeqRef.current !== requestSeq) {
                    return;
                }

                console.error(err);
                toast({
                    title: "Gagal memuat data",
                    description: getUserFriendlyError(err, { context: "load" }),
                    variant: "destructive",
                });
            })
            .finally(() => {
                if (requestSeqRef.current === requestSeq) {
                    setIsLoading(false);
                }
            });
    }, [apiEndpoint, effectivePageSize, deferredSearch, filters, page, serverSide, toast]);

    useEffect(() => {
        if (serverSide && isAdaptiveLayoutReady) {
            fetchData();
        }
    }, [fetchData, isAdaptiveLayoutReady, serverSide]);

    useEffect(() => {
        if (!serverSide && apiEndpoint) {
            fetchData();
        }
    }, [apiEndpoint, serverSide]);

    useEffect(() => {
        if (serverSide && paginationMeta.lastPage > 0 && page > paginationMeta.lastPage) {
            setPage(paginationMeta.lastPage);
        }
    }, [page, paginationMeta.lastPage, serverSide]);

    const totalPages = serverSide
        ? Math.max(1, paginationMeta.lastPage || 1)
        : Math.max(1, Math.ceil(filtered.length / effectivePageSize));
    const paginated = serverSide
        ? filtered
        : filtered.slice((page - 1) * effectivePageSize, page * effectivePageSize);
    const visibleTotal = serverSide ? paginationMeta.total : filtered.length;
    const visibleStart = visibleTotal === 0
        ? 0
        : serverSide
            ? ((paginationMeta.currentPage - 1) * paginationMeta.perPage) + 1
            : ((page - 1) * effectivePageSize) + 1;
    const visibleEnd = visibleTotal === 0
        ? 0
        : serverSide
            ? visibleStart + paginated.length - 1
            : Math.min(page * effectivePageSize, filtered.length);

    useLayoutEffect(() => {
        const tableBody = tableBodyRef.current;
        if (!tableBody) {
            return;
        }

        tableBody.scrollTop = 0;
    }, [page, effectivePageSize, deferredSearch, filters, sortKey, sortDir]);

    useEffect(() => {
        if (!serverSide && page > totalPages) {
            setPage(totalPages);
        }
    }, [page, serverSide, totalPages]);

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir(sortDir === "asc" ? "desc" : sortDir === "desc" ? null : "asc");
            if (sortDir === "desc") setSortKey(null);
        } else {
            setSortKey(key);
            setSortDir("asc");
        }
    };

    const openAdd = () => {
        setEditingRow(null);
        setFormState({});
        setModalOpen(true);
        if (onModalOpen) onModalOpen(null, setFormState);
    };

    const buildApiUrlWithId = (endpoint: string, id: string | number) => {
        const [base, query] = endpoint.split("?");
        const trimmedBase = base.replace(/\/$/, "");
        return `${trimmedBase}/${id}${query ? `?${query}` : ""}`;
    };

    const openEdit = (row: T) => {
        setEditingRow(row);
        const state: Record<string, string> = {};
        const rowRecord = row as unknown as Record<string, unknown>;
        formFields.forEach((f) => (state[f.key] = String(rowRecord[f.key] ?? "")));
        setFormState(state);
        setModalOpen(true);
        if (onModalOpen) onModalOpen(row, setFormState);
    };

    const updateFormField = (
        fieldKey: string,
        value: string,
    ) => {
        const newState = { ...formState, [fieldKey]: value };
        setFormState(newState);
        if (onFormChange) onFormChange(fieldKey, value, newState, setFormState);
    };

    const handleSave = async () => {
        if (!apiEndpoint) return;
        setIsSaving(true);

        const payload: Record<string, any> = { ...formState };
        if (payload.dosen_ids) {
            payload.dosen_ids = String(payload.dosen_ids)
                .split(",")
                .map((m: string) => m.trim())
                .filter((v: string) => v !== "");
        }

        if (payload.dosen_luar === "" || payload.dosen_luar === undefined) {
            delete payload.dosen_luar;
        }

        try {
            if (onCustomSave) {
                await onCustomSave(payload, editingRow);
            } else if (editingRow) {
                await api.put(buildApiUrlWithId(apiEndpoint, editingRow.id), payload);
                toast({ title: "Data diperbarui", description: "Perubahan berhasil disimpan." });
            } else {
                await api.post(apiEndpoint, payload);
                toast({ title: "Data ditambahkan", description: "Data baru berhasil disimpan." });
            }
            setModalOpen(false);
            invalidateApiCache(["/kelas-kuliah", "/dashboard", apiEndpoint]);
            fetchData();
        } catch (err: any) {
            toast({
                title: "Gagal menyimpan",
                description: getUserFriendlyError(err, { context: "save" }),
                variant: "destructive",
            });
        } finally {
            setIsSaving(false);
        }
    };

    const handleDelete = (row: T) => {
        if (!apiEndpoint) return;
        setDeleteTarget(row);
    };

    const confirmDelete = () => {
        if (!apiEndpoint || !deleteTarget) return;

        setIsDeleting(true);
        api.delete(buildApiUrlWithId(apiEndpoint, deleteTarget.id))
            .then(() => {
                toast({ title: "Data dihapus", description: "Data berhasil dihapus dari sistem." });
                invalidateApiCache(["/kelas-kuliah", "/dashboard", apiEndpoint]);
                setDeleteTarget(null);
                fetchData();
            })
            .catch((err) => {
                toast({
                    title: "Gagal menghapus",
                    description: getUserFriendlyError(err, { context: "delete" }),
                    variant: "destructive",
                });
            })
            .finally(() => setIsDeleting(false));
    };

    const confirmDeleteAll = () => {
        if (!apiEndpoint) return;

        setIsDeletingAll(true);
        api.delete(apiEndpoint)
            .then((response) => {
                const deletedCount = Number(response.data?.deleted_count ?? 0);
                toast({
                    title: "Semua data dihapus",
                    description: deletedCount > 0
                        ? `${deletedCount} data berhasil dihapus dari sistem.`
                        : "Tidak ada data yang dihapus pada scope ini.",
                });
                invalidateApiCache(["/kelas-kuliah", "/dashboard", apiEndpoint]);
                setDeleteAllOpen(false);
                fetchData();
            })
            .catch((err) => {
                toast({
                    title: "Gagal menghapus semua data",
                    description: getUserFriendlyError(err, { context: "delete" }),
                    variant: "destructive",
                });
            })
            .finally(() => setIsDeletingAll(false));
    };

    const SortIcon = ({ col }: { col: string }) => {
        if (sortKey !== col) return <ArrowUpDown className="h-3.5 w-3.5 text-muted-foreground/50" />;
        if (sortDir === "asc") return <ArrowUp className="h-3.5 w-3.5 text-primary" />;
        return <ArrowDown className="h-3.5 w-3.5 text-primary" />;
    };

    const resolveAlignClass = (
        align: "left" | "center" | "right",
        mode: "text" | "content"
    ) => {
        if (mode === "text") {
            if (align === "center") return "text-center";
            if (align === "right") return "text-right";
            return "text-left";
        }
        if (align === "center") return "justify-center";
        if (align === "right") return "justify-end";
        return "justify-start";
    };

    const bodyCellPaddingClass = tableLayout?.rowDensity === "spacious" ? "py-2" : "py-1.5";
    const bodyCellContentClass = "master-table-cell-content";

    return (
        <div className="flex min-h-0 flex-1 flex-col space-y-2 overflow-hidden">
            {/* Header */}
            <motion.div
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                className="mb-2 flex shrink-0 flex-col gap-1.5"
            >
                <div>
                    <h1 className="text-[2rem] font-bold leading-tight text-foreground">{title}</h1>
                    <p className="text-sm text-muted-foreground mt-0.5">{description}</p>
                </div>
            </motion.div>

            {/* Optional Top Content (e.g., Statistics) */}
            {topContent && (
                <div className="mb-1 shrink-0">
                    {topContent}
                </div>
            )}

            {/* Search, Filters, & Action */}
            <MasterDataToolbar
                search={search}
                setSearch={setSearch}
                setPage={setPage}
                filters={filters}
                setFilters={setFilters}
                filterOptions={filterOptions}
                filterResetMap={filterResetMap}
                onFilterChange={onFilterChange}
                readOnly={readOnly}
                extraActions={extraActions}
                hideAddButton={hideAddButton}
                importEnabled={Boolean(importUrl && templateUrl)}
                deleteAllEnabled={Boolean(apiEndpoint) && !hideDeleteAllButton}
                onAdd={openAdd}
                onImport={() => setImportModalOpen(true)}
                onDeleteAll={() => setDeleteAllOpen(true)}
            />

            {/* Table */}
            <motion.div
                ref={tableCardRef}
                initial={{ opacity: 0, y: 15 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.2 }}
                className="glass-card mt-2 flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl"
                style={tableCardStyle}
            >
                <div ref={tableBodyRef} className="min-h-0 flex-1 overflow-auto">
                    <table className="w-full text-sm">
                        <thead ref={tableHeaderRef} className="bg-[#115e59] text-white sticky top-0 z-10">
                            <tr>
                                <th className={cn(tableHeaderCellClass, "w-16 text-center")}>
                                    <div className="flex h-12 items-center justify-center">
                                        No
                                    </div>
                                </th>
                                {columns.map((col) => (
                                    <th
                                        key={col.key}
                                        className={cn(
                                            tableHeaderCellClass,
                                            resolveAlignClass(col.headerAlign || "center", "text"),
                                            col.sortable && "cursor-pointer select-none hover:text-white/80 transition-colors"
                                        )}
                                        onClick={() => col.sortable && handleSort(col.key)}
                                    >
                                        <div className={cn("flex h-12 items-center gap-2", resolveAlignClass(col.headerAlign || "center", "content"))}>
                                            {col.label}
                                            {col.sortable && <SortIcon col={col.key} />}
                                        </div>
                                    </th>
                                ))}
                                {!readOnly && (
                                    <th className={cn(tableHeaderCellClass, "text-center")}>
                                        <div className="flex h-12 items-center justify-center">
                                            Aksi
                                        </div>
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {isLoading ? (
                                <tr>
                                    <td colSpan={columns.length + (readOnly ? 1 : 2)} className="px-7 py-14 text-center md:px-8">
                                        <div className="flex flex-col items-center justify-center gap-3">
                                            <div className="h-6 w-6 animate-spin rounded-full border-b-2 border-primary"></div>
                                            <p className="text-sm text-muted-foreground">Memuat data...</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : paginated.length === 0 ? (
                                <tr>
                                    <td colSpan={columns.length + (readOnly ? 1 : 2)} className="px-7 py-8 text-center text-muted-foreground md:px-8">
                                        Tidak ada data ditemukan.
                                    </td>
                                </tr>
                            ) : (
                                paginated.map((row, index) => (
                                    <tr
                                        key={String(row.id)}
                                        ref={index === 0 ? firstDataRowRef : undefined}
                                        className="h-14 even:bg-white odd:bg-gray-50/70 hover:bg-gray-100 transition-colors"
                                    >
                                        <td className={cn("h-14 px-4 text-[#333] border border-[#dee2e6] text-sm text-center font-medium align-middle", bodyCellPaddingClass)}>
                                            <div className={bodyCellContentClass}>
                                                {visibleStart + index}
                                            </div>
                                        </td>
                                        {columns.map((col) => (
                                            <td
                                                key={col.key}
                                                className={cn(
                                                    "h-14 px-4 text-[#333] border border-[#dee2e6] text-sm align-middle",
                                                    bodyCellPaddingClass,
                                                    resolveAlignClass(col.cellAlign || "left", "text")
                                                )}
                                            >
                                                <div className={bodyCellContentClass}>
                                                    {col.render ? col.render(row[col.key], row) : String(row[col.key] ?? "")}
                                                </div>
                                            </td>
                                        ))}
                                        {!readOnly && (
                                            <td className={cn("h-14 px-4 border border-[#dee2e6] align-middle", bodyCellPaddingClass)}>
                                                <div className="flex items-center justify-center gap-1">
                                                    <button
                                                        onClick={() => openEdit(row)}
                                                        className="flex h-8 w-8 items-center justify-center rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        className="flex h-8 w-8 items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                                        title="Hapus"
                                                        onClick={() => handleDelete(row)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div ref={tableFooterRef} className="flex items-center justify-between border-t border-border/20 px-7 py-2 md:px-8">
                    {serverSide ? (
                        <p className="text-xs text-muted-foreground">
                        Menampilkan {visibleStart}–{visibleEnd} dari {visibleTotal} data
                    </p>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Menampilkan {visibleStart} - {visibleEnd} dari {filtered.length} data
                        </p>
                    )}
                    <div className="flex items-center gap-1">
                        <button
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            disabled={page === 1}
                            className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </button>
                        {Array.from(
                            { length: Math.min(10, totalPages - Math.floor((page - 1) / 10) * 10) },
                            (_, i) => Math.floor((page - 1) / 10) * 10 + 1 + i
                        ).map((p) => (
                            <button
                                key={p}
                                onClick={() => setPage(p)}
                                className={cn(
                                    "flex h-8 w-8 items-center justify-center rounded-lg text-sm transition-colors",
                                    p === page
                                        ? "bg-primary/20 text-primary font-semibold"
                                        : "text-muted-foreground hover:text-foreground hover:bg-muted/30"
                                )}
                            >
                                {p}
                            </button>
                        ))}
                        <button
                            onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                            disabled={page === totalPages}
                            className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </motion.div>

            {/* Add/Edit Modal */}
            <AnimatePresence>
                {modalOpen && (
                    <div className="fixed inset-0 z-[70]">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="modal-overlay-soft fixed inset-0"
                            onClick={() => setModalOpen(false)}
                        />

                        <div className="fixed inset-0 z-[71] flex items-center justify-center overflow-y-auto px-4 py-6">
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                                animate={{ opacity: 1, scale: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                                transition={{ type: "spring", damping: 25, stiffness: 350 }}
                                className="modal-surface w-full max-w-3xl max-h-[calc(100vh-80px)] flex flex-col"
                            >
                                {/* Modal Header - sticky */}
                                <div className="flex items-center justify-between mb-4 shrink-0">
                                    <h2 className="text-lg font-bold text-foreground">
                                        {editingRow ? "Edit Data" : "Tambah Data Baru"}
                                    </h2>
                                    <button
                                        onClick={() => setModalOpen(false)}
                                        className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-colors"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>

                                {/* Modal Body - scrollable */}
                                <div className="flex-1 overflow-y-auto min-h-0 pr-1">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        {formFields.map((field) => (
                                            <div key={field.key} className={field.className || ""}>
                                                {field.render ? (
                                                    field.render(formState, setFormState)
                                                ) : (
                                                    <>
                                                        <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                                                            {field.label}
                                                            {field.required && <span className="text-destructive ml-0.5">*</span>}
                                                        </label>
                                                        {field.type === "select" ? (
                                                            (field.searchable ?? ((field.options?.length ?? 0) >= SEARCHABLE_SELECT_MIN_OPTIONS)) ? (
                                                                <SearchableSelect
                                                                    value={formState[field.key] || undefined}
                                                                    disabled={field.disabled}
                                                                    placeholder={`Pilih ${field.label}`}
                                                                    searchPlaceholder={`Cari ${field.label.toLowerCase()}...`}
                                                                    options={field.options}
                                                                    onValueChange={(value) => updateFormField(field.key, value)}
                                                                />
                                                            ) : (
                                                                <Select
                                                                    value={formState[field.key] || undefined}
                                                                    disabled={field.disabled}
                                                                    onValueChange={(value) => updateFormField(field.key, value)}
                                                                >
                                                                    <SelectTrigger className="form-field-select-trigger w-full">
                                                                        <SelectValue placeholder={`Pilih ${field.label}`} />
                                                                    </SelectTrigger>
                                                                    <SelectContent className="form-field-select-content">
                                                                        {field.options?.map((o) => (
                                                                            <SelectItem key={o.value} value={o.value}>
                                                                                {o.label}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            )
                                                        ) : field.type === "multiselect" ? (
                                                            <div className="max-h-40 overflow-y-auto rounded-md border border-muted p-2">
                                                                {field.options?.map((o) => {
                                                                    const checked = (formState[field.key] || "").split(",").map((x) => x.trim()).filter(Boolean).includes(o.value);
                                                                    return (
                                                                        <label key={o.value} className="flex items-center gap-2 mb-1 text-sm">
                                                                            <Checkbox
                                                                                checked={checked}
                                                                                onCheckedChange={(checkedState) => {
                                                                                    const current = (formState[field.key] || "").split(",").map((x) => x.trim()).filter(Boolean);
                                                                                    const next = new Set(current);
                                                                                    if (checkedState) {
                                                                                        next.add(o.value);
                                                                                    } else {
                                                                                        next.delete(o.value);
                                                                                    }
                                                                                    const nextValue = Array.from(next).join(",");
                                                                                    const newState = { ...formState, [field.key]: nextValue };
                                                                                    setFormState(newState);
                                                                                    if (onFormChange) onFormChange(field.key, nextValue, newState, setFormState);
                                                                                }}
                                                                            />
                                                                            <span>{o.label}</span>
                                                                        </label>
                                                                    );
                                                                })}
                                                            </div>
                                                        ) : (
                                                            <Input
                                                                type={field.type || "text"}
                                                                placeholder={field.placeholder || `Masukkan ${field.label.toLowerCase()}`}
                                                                value={formState[field.key] ?? ""}
                                                                disabled={field.disabled}
                                                                onChange={(e) => {
                                                                    const val = e.target.value;
                                                                    const newState = { ...formState, [field.key]: val };
                                                                    setFormState(newState);
                                                                    if (onFormChange) onFormChange(field.key, val, newState, setFormState);
                                                                }}
                                                                className="form-field-input"
                                                            />
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Modal Footer - sticky */}
                                <div className="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-border/20 shrink-0">
                                    <Button
                                        variant="ghost"
                                        onClick={() => setModalOpen(false)}
                                        className="bg-transparent hover:bg-muted text-muted-foreground hover:text-foreground"
                                    >
                                        Batal
                                    </Button>
                                    <Button
                                        onClick={handleSave}
                                        disabled={isSaving}
                                        className="gradient-btn"
                                    >
                                        {isSaving ? "Menyimpan..." : (editingRow ? "Simpan Perubahan" : "Simpan")}
                                    </Button>
                                </div>
                            </motion.div>
                        </div>
                    </div>
                )}
            </AnimatePresence>

            {/* Import Modal */}
            {importUrl && templateUrl && (
                <ExcelImportModal
                    open={importModalOpen}
                    onOpenChange={setImportModalOpen}
                    title={title}
                    templateUrl={templateUrl}
                    importUrl={importUrl}
                    onSuccess={() => {
                        invalidateApiCache(["/kelas-kuliah", "/dashboard", apiEndpoint]);
                        fetchData();
                    }}
                />
            )}
            <ConfirmActionDialog
                open={Boolean(deleteTarget)}
                onOpenChange={(open) => {
                    if (!open && !isDeleting) {
                        setDeleteTarget(null);
                    }
                }}
                title="Hapus data?"
                description="Data yang dihapus tidak dapat dikembalikan dari halaman ini. Pastikan data tidak sedang digunakan oleh proses lain."
                confirmLabel="Hapus"
                destructive
                loading={isDeleting}
                onConfirm={confirmDelete}
            />
            <ConfirmActionDialog
                open={deleteAllOpen}
                onOpenChange={(open) => {
                    if (!open && !isDeletingAll) {
                        setDeleteAllOpen(false);
                    }
                }}
                title="Delete all data?"
                description={`Semua data ${title.toLowerCase()} dalam scope role Anda akan dihapus. Tindakan ini tidak dapat dikembalikan dari halaman ini.`}
                confirmLabel="Delete All"
                destructive
                loading={isDeletingAll}
                onConfirm={confirmDeleteAll}
            />
        </div>
    );
}
