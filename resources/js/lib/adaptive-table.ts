export const DEFAULT_TABLE_ROW_HEIGHT = 56;
export const DEFAULT_TABLE_HEADER_HEIGHT = 48;
export const DEFAULT_TABLE_FOOTER_HEIGHT = 49;
export const DEFAULT_TABLE_CHROME_HEIGHT = 2;
export const DEFAULT_TABLE_MIN_HEIGHT = 360;
export const DEFAULT_TABLE_BOTTOM_GUTTER = 8;
export const DEFAULT_PAGE_SIZE_DELTA_THRESHOLD = 2;
export const DEFAULT_PAGE_SIZE_RATIO_THRESHOLD = 0.2;

export function normalizePageSize(pageSize: number) {
    return Math.max(1, Math.floor(pageSize) || 1);
}

export function calculateTableCardHeight(
    viewportHeight: number,
    tableTop: number,
    bottomGutter = DEFAULT_TABLE_BOTTOM_GUTTER,
    minHeight = DEFAULT_TABLE_MIN_HEIGHT,
) {
    const availableHeight = Math.floor(viewportHeight - tableTop - bottomGutter);
    return Math.max(minHeight, availableHeight);
}

export function calculateAvailableTableBodyHeight(
    tableCardHeight: number,
    headerHeight: number,
    footerHeight: number,
    chromeHeight = DEFAULT_TABLE_CHROME_HEIGHT,
) {
    return Math.max(0, tableCardHeight - headerHeight - footerHeight - chromeHeight);
}

export function calculateSparseTableCardHeight(
    maxTableCardHeight: number,
    headerHeight: number,
    footerHeight: number,
    estimatedRowHeight: number,
    rowCount: number,
    minRows: number,
    chromeHeight = DEFAULT_TABLE_CHROME_HEIGHT,
) {
    const safeRowCount = normalizePageSize(rowCount);
    const safeMinRows = normalizePageSize(minRows);
    const targetRows = Math.max(safeRowCount, safeMinRows);
    const contentHeight = headerHeight + footerHeight + chromeHeight + (targetRows * estimatedRowHeight);

    return Math.min(maxTableCardHeight, contentHeight);
}

export function calculateEffectivePageSize(
    availableBodyHeight: number,
    estimatedRowHeight: number,
    fallbackPageSize: number,
) {
    const safeFallbackPageSize = normalizePageSize(fallbackPageSize);
    if (availableBodyHeight <= 0 || estimatedRowHeight <= 0) {
        return safeFallbackPageSize;
    }

    return Math.max(1, Math.floor(availableBodyHeight / estimatedRowHeight));
}

export function shouldCommitPageSizeChange(
    currentPageSize: number,
    nextPageSize: number,
    deltaThreshold = DEFAULT_PAGE_SIZE_DELTA_THRESHOLD,
    ratioThreshold = DEFAULT_PAGE_SIZE_RATIO_THRESHOLD,
) {
    const safeCurrentPageSize = normalizePageSize(currentPageSize);
    const safeNextPageSize = normalizePageSize(nextPageSize);

    if (safeCurrentPageSize === safeNextPageSize) {
        return false;
    }

    const delta = Math.abs(safeNextPageSize - safeCurrentPageSize);
    if (delta >= deltaThreshold) {
        return true;
    }

    return (delta / safeCurrentPageSize) >= ratioThreshold;
}
