import type { MasterDataQueryResult } from "@/components/master/masterDataTypes";

export function resolveMasterDataResult<T>(
    payload: any,
    serverSide: boolean,
    page: number,
    perPage: number,
): MasterDataQueryResult<T> {
    const normalizedPayload = Array.isArray(payload) ? { data: payload } : (payload || {});
    const rows = Array.isArray(normalizedPayload.data) ? normalizedPayload.data : (normalizedPayload.list || []);
    const pagination = normalizedPayload.pagination || {};

    return {
        rows,
        pagination: serverSide
            ? {
                total: Number(normalizedPayload.total ?? pagination.total ?? rows.length ?? 0),
                currentPage: Number(normalizedPayload.current_page ?? pagination.current_page ?? page),
                perPage: Number(normalizedPayload.per_page ?? pagination.per_page ?? perPage),
                lastPage: Number(normalizedPayload.last_page ?? pagination.last_page ?? 1),
            }
            : {
                total: rows.length,
                currentPage: 1,
                perPage: rows.length,
                lastPage: 1,
            },
    };
}
