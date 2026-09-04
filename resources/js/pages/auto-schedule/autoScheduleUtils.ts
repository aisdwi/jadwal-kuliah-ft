export const DEFAULT_PARAMS = {
  num_kromosom: 45,
  max_generation: 60,
  crossover_rate: 85,
  mutation_rate: 40,
};

export const COMPLETED_PROGRESS_STORAGE_KEY = "auto-schedule:last-completed-progress";
export const PROCESS_PANEL_DISMISSED_KEY = "auto-schedule:process-panel-dismissed";

export type PendingScheduleAction = "restore" | "clear" | null;

export function formatElapsed(seconds: number): string {
  if (seconds < 60) return `${seconds.toFixed(1)}s`;
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}m ${secs}s`;
}

export function getPreviewLoadMetrics(preview?: any) {
  const unscheduledCount = Number(preview?.unscheduled_count ?? 0);
  const slotsTotal = Number(preview?.slots_total ?? preview?.max_capacity ?? 0);
  const allClasses = Number(preview?.all_classes ?? preview?.total_kelas_kuliah ?? 0);
  return {
    unscheduledCount,
    slotsTotal,
    allClasses,
    slotLoad: slotsTotal > 0 ? unscheduledCount / slotsTotal : 0,
    remainingRatio: allClasses > 0 ? unscheduledCount / allClasses : 0,
  };
}

export function getRecommendedParams(preview?: any) {
  const { unscheduledCount, slotLoad, remainingRatio } = getPreviewLoadMetrics(preview);

  if (unscheduledCount <= 20) {
    return {
      num_kromosom: 25,
      max_generation: 35,
      crossover_rate: 80,
      mutation_rate: 35,
    };
  }

  if (unscheduledCount <= 50) {
    return {
      num_kromosom: 45,
      max_generation: 60,
      crossover_rate: 85,
      mutation_rate: 40,
    };
  }

  if (unscheduledCount <= 100 || slotLoad >= 0.35 || remainingRatio >= 0.55) {
    return {
      num_kromosom: 55,
      max_generation: 80,
      crossover_rate: 85,
      mutation_rate: 45,
    };
  }

  if (unscheduledCount <= 180 || slotLoad >= 0.5 || remainingRatio >= 0.75) {
    return {
      num_kromosom: 70,
      max_generation: 110,
      crossover_rate: 85,
      mutation_rate: 45,
    };
  }

  return {
    num_kromosom: 90,
    max_generation: 140,
    crossover_rate: 80,
    mutation_rate: 50,
  };
}

export function getRecommendationProfile(preview?: any) {
  const { unscheduledCount, slotLoad, remainingRatio } = getPreviewLoadMetrics(preview);

  if (unscheduledCount <= 20 && slotLoad < 0.2 && remainingRatio < 0.35) {
    return {
      label: "Data Ringan",
      note: "Cocok untuk data kecil dengan ruang pencarian yang masih longgar.",
    };
  }

  if (unscheduledCount <= 50 && slotLoad < 0.35 && remainingRatio < 0.55) {
    return {
      label: "Data Harian",
      note: "Cocok untuk penggunaan rutin dengan jumlah kelas yang masih terkendali.",
    };
  }

  if (unscheduledCount <= 100 || slotLoad >= 0.35 || remainingRatio >= 0.55) {
    return {
      label: "Data Menengah",
      note: "Perlu populasi dan iterasi lebih besar agar bentrok cepat turun.",
    };
  }

  if (unscheduledCount <= 180 || slotLoad >= 0.5 || remainingRatio >= 0.75) {
    return {
      label: "Data Padat",
      note: "Dipakai saat kelas belum terjadwal cukup banyak dibanding slot yang tersedia.",
    };
  }

  return {
    label: "Data Sangat Padat",
    note: "Gunakan parameter lebih besar untuk ruang pencarian yang sempit dan kombinasi yang berat.",
  };
}

export function getRecommendationNote(preview?: any) {
  return getRecommendationProfile(preview).note;
}

export function getScheduleUiStorage(): Storage | null {
  if (typeof window === "undefined") return null;
  return window.sessionStorage;
}

export function buildScheduleUiStorageKey(baseKey: string, userId?: number | string | null, semesterTipe?: string | null) {
  return `${baseKey}:user:${userId ?? "guest"}:semester:${semesterTipe ?? "all"}`;
}

export function clearLegacyScheduleUiStorage() {
  if (typeof window === "undefined") return;

  window.localStorage.removeItem(COMPLETED_PROGRESS_STORAGE_KEY);
  window.localStorage.removeItem(PROCESS_PANEL_DISMISSED_KEY);
  window.sessionStorage.removeItem(COMPLETED_PROGRESS_STORAGE_KEY);
  window.sessionStorage.removeItem(PROCESS_PANEL_DISMISSED_KEY);
}

export function readCompletedProgress(storageKey: string) {
  const storage = getScheduleUiStorage();
  if (!storage) return null;

  const raw = storage.getItem(storageKey);
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    storage.removeItem(storageKey);
    return null;
  }
}
