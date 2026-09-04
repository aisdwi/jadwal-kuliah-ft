import { RefObject, useEffect, useRef, useState } from "react";
import {
    DEFAULT_TABLE_BOTTOM_GUTTER,
    DEFAULT_TABLE_FOOTER_HEIGHT,
    DEFAULT_TABLE_HEADER_HEIGHT,
    DEFAULT_TABLE_MIN_HEIGHT,
    DEFAULT_TABLE_ROW_HEIGHT,
    calculateAvailableTableBodyHeight,
    calculateEffectivePageSize,
    calculateSparseTableCardHeight,
    calculateTableCardHeight,
    normalizePageSize,
    shouldCommitPageSizeChange,
} from "@/lib/adaptive-table";

interface SparseTableLayoutOptions {
    enabled?: boolean;
    minRows?: number;
    maxRowsToShrink?: number;
    rowCount?: number;
}

interface UseAdaptiveTableLayoutOptions {
    containerRef: RefObject<HTMLDivElement>;
    headerRef: RefObject<HTMLTableSectionElement>;
    footerRef: RefObject<HTMLDivElement>;
    firstDataRowRef: RefObject<HTMLTableRowElement>;
    fallbackPageSize: number;
    layoutDeps?: readonly unknown[];
    bottomGutter?: number;
    minHeight?: number;
    sparseLayout?: SparseTableLayoutOptions;
}

export function useAdaptiveTableLayout({
    containerRef,
    headerRef,
    footerRef,
    firstDataRowRef,
    fallbackPageSize,
    layoutDeps = [],
    bottomGutter = DEFAULT_TABLE_BOTTOM_GUTTER,
    minHeight = DEFAULT_TABLE_MIN_HEIGHT,
    sparseLayout,
}: UseAdaptiveTableLayoutOptions) {
    const normalizedFallbackPageSize = normalizePageSize(fallbackPageSize);
    const [tableCardMaxHeight, setTableCardMaxHeight] = useState<number | null>(null);
    const [measuredPageSize, setMeasuredPageSize] = useState(normalizedFallbackPageSize);
    const [committedPageSize, setCommittedPageSize] = useState(0);
    const [isReady, setIsReady] = useState(false);
    const lastTableCardHeightRef = useRef<number | null>(null);
    const committedRowHeightRef = useRef<number | null>(null);
    const sparseMinRows = normalizePageSize(sparseLayout?.minRows ?? normalizedFallbackPageSize);
    const sparseMaxRowsToShrink = normalizePageSize(sparseLayout?.maxRowsToShrink ?? sparseMinRows);
    const isSparseLayoutEnabled = Boolean(sparseLayout?.enabled);
    const sparseRowCount = isSparseLayoutEnabled ? (sparseLayout?.rowCount ?? 0) : 0;

    useEffect(() => {
        if (typeof window === "undefined") {
            return;
        }

        let frameId = 0;
        let resizeObserver: ResizeObserver | null = null;

        const measureLayout = () => {
            const container = containerRef.current;
            if (!container) {
                return;
            }

            const nextTableCardHeight = calculateTableCardHeight(
                window.innerHeight,
                container.getBoundingClientRect().top,
                bottomGutter,
                minHeight,
            );

            const headerHeight = headerRef.current?.getBoundingClientRect().height ?? DEFAULT_TABLE_HEADER_HEIGHT;
            const footerHeight = footerRef.current?.getBoundingClientRect().height ?? DEFAULT_TABLE_FOOTER_HEIGHT;
            const firstDataRow = firstDataRowRef.current;
            const measuredRowHeight = firstDataRow?.getBoundingClientRect().height ?? null;
            const shouldCaptureRowHeight = measuredRowHeight !== null && committedRowHeightRef.current === null;
            const rowHeight = committedRowHeightRef.current ?? measuredRowHeight ?? DEFAULT_TABLE_ROW_HEIGHT;
            const shouldShrinkSparseTable = Boolean(
                isSparseLayoutEnabled
                && sparseRowCount > 0
                && sparseRowCount <= sparseMaxRowsToShrink,
            );
            const finalTableCardHeight = shouldShrinkSparseTable
                ? calculateSparseTableCardHeight(
                    nextTableCardHeight,
                    headerHeight,
                    footerHeight,
                    rowHeight,
                    sparseRowCount,
                    sparseMinRows,
                )
                : nextTableCardHeight;
            const availableBodyHeight = calculateAvailableTableBodyHeight(
                finalTableCardHeight,
                headerHeight,
                footerHeight,
            );
            const nextMeasuredPageSize = calculateEffectivePageSize(
                availableBodyHeight,
                rowHeight,
                normalizedFallbackPageSize,
            );

            setTableCardMaxHeight((currentHeight) =>
                currentHeight === finalTableCardHeight ? currentHeight : finalTableCardHeight,
            );
            setMeasuredPageSize((currentPageSize) =>
                currentPageSize === nextMeasuredPageSize ? currentPageSize : nextMeasuredPageSize,
            );
            const previousTableCardHeight = lastTableCardHeightRef.current;
            const tableCardHeightChanged = previousTableCardHeight === null
                || Math.abs(previousTableCardHeight - finalTableCardHeight) >= DEFAULT_TABLE_ROW_HEIGHT;

            setCommittedPageSize((currentPageSize) => {
                if (currentPageSize <= 0) {
                    return nextMeasuredPageSize;
                }

                if (
                    (tableCardHeightChanged || shouldCaptureRowHeight)
                    && shouldCommitPageSizeChange(currentPageSize, nextMeasuredPageSize)
                ) {
                    return nextMeasuredPageSize;
                }

                return currentPageSize;
            });
            lastTableCardHeightRef.current = finalTableCardHeight;
            if (shouldCaptureRowHeight) {
                committedRowHeightRef.current = measuredRowHeight;
            }
            setIsReady(true);
        };

        const scheduleMeasurement = () => {
            window.cancelAnimationFrame(frameId);
            frameId = window.requestAnimationFrame(measureLayout);
        };

        scheduleMeasurement();
        window.addEventListener("resize", scheduleMeasurement);

        if (typeof ResizeObserver !== "undefined") {
            resizeObserver = new ResizeObserver(() => {
                scheduleMeasurement();
            });

            if (containerRef.current) {
                resizeObserver.observe(containerRef.current);
            }
            if (headerRef.current) {
                resizeObserver.observe(headerRef.current);
            }
            if (footerRef.current) {
                resizeObserver.observe(footerRef.current);
            }
            if (firstDataRowRef.current) {
                resizeObserver.observe(firstDataRowRef.current);
            }
        }

        return () => {
            window.cancelAnimationFrame(frameId);
            window.removeEventListener("resize", scheduleMeasurement);
            resizeObserver?.disconnect();
        };
    }, [
        bottomGutter,
        containerRef,
        fallbackPageSize,
        firstDataRowRef,
        footerRef,
        headerRef,
        minHeight,
        normalizedFallbackPageSize,
        isSparseLayoutEnabled,
        sparseMaxRowsToShrink,
        sparseMinRows,
        sparseRowCount,
        ...layoutDeps,
    ]);

    return {
        tableCardMaxHeight,
        measuredPageSize,
        committedPageSize,
        isReady,
    };
}
