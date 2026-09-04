import { useState, useRef, useEffect, useMemo } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Sparkles,
  Settings,
  Zap,
  AlertTriangle,
  ChevronDown,
  RefreshCcw,
  Info,
} from "lucide-react";
import { toast } from "sonner";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import api from "@/lib/api";
import { getUserFriendlyError } from "@/lib/error-messages";
import { useAuth } from "@/contexts/AuthContext";
import { useSemester } from "@/contexts/SemesterContext";
import { ConfirmActionDialog } from "@/components/shared/ConfirmActionDialog";
import {
  PlainCard as Card,
  PlainCardContent as CardContent,
  PlainCardHeader as CardHeader,
  PlainCardTitle as CardTitle,
} from "@/components/shared/PlainCard";
import { ProgressConsole } from "@/pages/auto-schedule/ProgressConsole";
import { ResultPanel } from "@/pages/auto-schedule/ResultPanel";
import { SummaryCard } from "@/pages/auto-schedule/SummaryCard";
import {
  buildScheduleUiStorageKey,
  clearLegacyScheduleUiStorage,
  COMPLETED_PROGRESS_STORAGE_KEY,
  DEFAULT_PARAMS,
  getRecommendedParams,
  getRecommendationNote,
  getRecommendationProfile,
  getScheduleUiStorage,
  PROCESS_PANEL_DISMISSED_KEY,
  readCompletedProgress,
} from "@/pages/auto-schedule/autoScheduleUtils";
import type { PendingScheduleAction } from "@/pages/auto-schedule/autoScheduleUtils";

const SHOW_PARAMETER_RECOMMENDATION = false;

export default function AutoSchedulePage() {
  const { canSchedule, user } = useAuth();
  const { semesterTipe } = useSemester();
  const queryClient = useQueryClient();
  const completedProgressStorageKey = useMemo(
    () => buildScheduleUiStorageKey(COMPLETED_PROGRESS_STORAGE_KEY, user?.id, semesterTipe),
    [user?.id, semesterTipe],
  );
  const processPanelDismissedKey = useMemo(
    () => buildScheduleUiStorageKey(PROCESS_PANEL_DISMISSED_KEY, user?.id, semesterTipe),
    [user?.id, semesterTipe],
  );
  const [showAdvanced, setShowAdvanced] = useState(false);
  const [showParameters, setShowParameters] = useState(false);
  const [showProcessPanel, setShowProcessPanel] = useState(false);
  const [shouldPollProgress, setShouldPollProgress] = useState(false);
  const [isWaitingForWorker, setIsWaitingForWorker] = useState(false);
  const [hasCustomizedParams, setHasCustomizedParams] = useState(false);
  const [pendingScheduleAction, setPendingScheduleAction] = useState<PendingScheduleAction>(null);
  const [savedCompletedProgress, setSavedCompletedProgress] = useState<any | null>(null);
  const [isProcessPanelDismissed, setIsProcessPanelDismissed] = useState<boolean>(false);
  const logContainerRef = useRef<HTMLDivElement>(null);
  const [params, setParams] = useState(DEFAULT_PARAMS);

  const { data: preview, isLoading: isLoadingPreview } = useQuery({
    queryKey: ["scheduling-preview", user?.id, semesterTipe],
    queryFn: async () => {
      const res = await api.get(`/scheduling/preview?semester_tipe=${semesterTipe}`);
      return res.data;
    },
    enabled: canSchedule && !!user?.id,
  });

  const generateMutation = useMutation({
    mutationFn: async (data: typeof params) => {
      const res = await api.post("/scheduling/generate", { ...data, semester_tipe: semesterTipe });
      return res.data;
    },
    onSuccess: (data) => {
      const accepted = data?.success ?? true;
      if (accepted) {
        toast.success(data.message);
        setIsProcessPanelDismissed(false);
        getScheduleUiStorage()?.removeItem(processPanelDismissedKey);
        setShowProcessPanel(true);
        setShouldPollProgress(true);
        setIsWaitingForWorker(true);
        queryClient.invalidateQueries({ queryKey: ["scheduling-progress"] });
      } else {
        toast.warning(data.message);
      }
      queryClient.invalidateQueries({ queryKey: ["scheduling-preview"] });
    },
    onError: (error: any) => {
      toast.error(getUserFriendlyError(error, { context: "generate" }));
    },
  });

  const cancelMutation = useMutation({
    mutationFn: async () => {
      const res = await api.post("/scheduling/cancel");
      return res.data;
    },
    onSuccess: (data) => {
      toast.info(data.message);
      // Stop polling and clear UI state immediately on cancel
      setShouldPollProgress(false);
      setIsWaitingForWorker(false);
      // Refresh progress to get the canceled status from server
      setTimeout(() => {
        queryClient.invalidateQueries({ queryKey: ["scheduling-progress"] });
      }, 500);
    },
    onError: (error: any) => {
      toast.error(getUserFriendlyError(error, { context: "generate" }));
    },
  });

  const restoreLastMutation = useMutation({
    mutationFn: async () => {
      const res = await api.post("/scheduling/restore-last", { semester_tipe: semesterTipe });
      return res.data;
    },
    onSuccess: (data) => {
      toast.success(data.message);
      getScheduleUiStorage()?.removeItem(completedProgressStorageKey);
      setSavedCompletedProgress(null);
      setShowProcessPanel(false);
      setShouldPollProgress(false);
      queryClient.invalidateQueries({ queryKey: ["scheduling-preview"] });
      queryClient.invalidateQueries({ queryKey: ["scheduling-progress"] });
    },
    onError: (error: any) => {
      toast.error(getUserFriendlyError(error, { context: "generate" }));
    },
  });

  const clearAllMutation = useMutation({
    mutationFn: async () => {
      const res = await api.post("/scheduling/clear-all", { semester_tipe: semesterTipe });
      return res.data;
    },
    onSuccess: (data) => {
      const deletedCount = Number(data?.deleted_count ?? 0);
      toast.success(deletedCount > 0 ? `${data.message} (${deletedCount} data)` : data.message);
      getScheduleUiStorage()?.removeItem(completedProgressStorageKey);
      setSavedCompletedProgress(null);
      setShowProcessPanel(false);
      setShouldPollProgress(false);
      queryClient.invalidateQueries({ queryKey: ["scheduling-preview"] });
      queryClient.invalidateQueries({ queryKey: ["scheduling-progress"] });
    },
    onError: (error: any) => {
      toast.error(getUserFriendlyError(error, { context: "generate" }));
    },
  });

  const handleGenerate = () => {
    getScheduleUiStorage()?.removeItem(completedProgressStorageKey);
    setSavedCompletedProgress(null);
    generateMutation.mutate(params);
  };

  const applyRecommendedParams = () => {
    setParams(getRecommendedParams(preview));
    setHasCustomizedParams(false);
  };

  const handleCancel = () => {
    cancelMutation.mutate();
  };

  const handleRestoreLast = () => {
    setPendingScheduleAction("restore");
  };

  const handleClearAll = () => {
    setPendingScheduleAction("clear");
  };

  const confirmPendingScheduleAction = () => {
    if (pendingScheduleAction === "restore") {
      restoreLastMutation.mutate();
      setPendingScheduleAction(null);
      return;
    }

    if (pendingScheduleAction === "clear") {
      clearAllMutation.mutate();
      setPendingScheduleAction(null);
    }
  };

  const handleCloseProcessPanel = () => {
    // Close and reset UI state to default view while keeping generated schedule in DB.
    setShowProcessPanel(false);
    setShouldPollProgress(false);
    setIsWaitingForWorker(false);
    setSavedCompletedProgress(null);
    const storage = getScheduleUiStorage();
    storage?.removeItem(completedProgressStorageKey);
    storage?.setItem(processPanelDismissedKey, "1");
    setIsProcessPanelDismissed(true);
    queryClient.setQueryData(["scheduling-progress", user?.id], {
      status: "idle",
      generation: 0,
      max_generation: 0,
      best_fitness: 0,
      logs: [],
      result: null,
    });
    // Invalidate queries to force UI reset
    queryClient.invalidateQueries({ queryKey: ["scheduling-progress"] });
    queryClient.invalidateQueries({ queryKey: ["scheduling-preview"] });
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setHasCustomizedParams(true);
    setParams({ ...params, [e.target.name]: parseInt(e.target.value) || 0 });
  };

  const { data: progress } = useQuery({
    queryKey: ["scheduling-progress", user?.id],
    queryFn: async () => {
      const res = await api.get("/scheduling/progress");
      return res.data;
    },
    enabled: canSchedule && !!user?.id,
    refetchInterval: shouldPollProgress ? 1500 : false,
  });

  const effectiveProgress =
    (progress?.status === "idle" || progress?.status === "no_progress") && savedCompletedProgress
      ? savedCompletedProgress
      : progress;

  const progressLogs = effectiveProgress?.logs ?? [];
  const processStatus = effectiveProgress?.status;
  const isQueued = processStatus === "queued";
  const isProcessing = processStatus === "processing";
  const isFinished = processStatus === "completed" || processStatus === "failed" || processStatus === "canceled";
  const isGenerating = generateMutation.isPending || isWaitingForWorker || isQueued || isProcessing;

  // Auto-scroll log container to bottom when new logs arrive
  useEffect(() => {
    if (logContainerRef.current) {
      logContainerRef.current.scrollTop = logContainerRef.current.scrollHeight;
    }
  }, [effectiveProgress?.logs]);

  useEffect(() => {
    if (processStatus === "queued" || processStatus === "processing") {
      setIsProcessPanelDismissed(false);
      getScheduleUiStorage()?.removeItem(processPanelDismissedKey);
      setShowProcessPanel(true);
      setShouldPollProgress(true);
      setIsWaitingForWorker(processStatus === "queued");
    }

    if (isFinished) {
      setShowProcessPanel(!isProcessPanelDismissed);
      setShouldPollProgress(false);
      setIsWaitingForWorker(false);
    }

    if (processStatus === "completed" && effectiveProgress?.result) {
      queryClient.invalidateQueries({ queryKey: ["scheduling-preview"] });
    }
  }, [processStatus, effectiveProgress?.result, isFinished, isProcessPanelDismissed, processPanelDismissedKey, queryClient]);

  useEffect(() => {
    clearLegacyScheduleUiStorage();
    setSavedCompletedProgress(readCompletedProgress(completedProgressStorageKey));
    setIsProcessPanelDismissed(getScheduleUiStorage()?.getItem(processPanelDismissedKey) === "1");
  }, [completedProgressStorageKey, processPanelDismissedKey]);

  useEffect(() => {
    if (!isFinished || !effectiveProgress) {
      return;
    }

    if (processStatus === "completed" && effectiveProgress?.result) {
      setSavedCompletedProgress(effectiveProgress);
      getScheduleUiStorage()?.setItem(completedProgressStorageKey, JSON.stringify(effectiveProgress));

      return;
    }

    setSavedCompletedProgress(null);
    getScheduleUiStorage()?.removeItem(completedProgressStorageKey);
  }, [completedProgressStorageKey, effectiveProgress, isFinished, processStatus]);

  useEffect(() => {
    if (!preview || hasCustomizedParams || isGenerating) {
      return;
    }

    setParams(getRecommendedParams(preview));
  }, [preview, hasCustomizedParams, isGenerating]);

  const result = !isProcessPanelDismissed && processStatus === "completed" && effectiveProgress?.result
    ? effectiveProgress.result
    : null;
  const resultBestFitnessRaw = Number(result?.best_fitness ?? effectiveProgress?.best_fitness ?? 0);
  const resultBestFitness = Number.isFinite(resultBestFitnessRaw) ? resultBestFitnessRaw : 0;
  const resultGenerationRaw = Number(result?.generation ?? effectiveProgress?.generation ?? 0);
  const resultGeneration = Number.isFinite(resultGenerationRaw) ? resultGenerationRaw : 0;
  const resultRurRaw = Number(result?.rur ?? 0);
  const resultRur = Number.isFinite(resultRurRaw) ? resultRurRaw : 0;
  const resultClashes = {
    dosen: Number(result?.clashes?.dosen ?? 0) || 0,
    ruang: Number(result?.clashes?.ruang ?? 0) || 0,
    kelas: Number(result?.clashes?.kelas ?? 0) || 0,
    angkatan: Number(result?.clashes?.angkatan ?? 0) || 0,
  };
  const scopeLabel = effectiveProgress?.scope_label || preview?.scope_label || "Semua Data";
  // Keep panel forced-open only while generating; otherwise respect explicit close action.
  const shouldShowProcessPanel = isGenerating || showProcessPanel;
  const recommendedParams = getRecommendedParams(preview);
  const recommendationNote = getRecommendationNote(preview);
  const recommendationProfile = getRecommendationProfile(preview);
  const generatedCount = Number(preview?.generated_count ?? 0) || 0;
  const lastSnapshot = preview?.latest_snapshot;
  const lastSnapshotLabel = lastSnapshot?.action_type === "clear"
    ? "Pulihkan Sebelum Pembersihan"
    : "Pulihkan Generate Terakhir";
  const queuedSeconds = effectiveProgress?.started_at
    ? Math.max(0, Math.floor((Date.now() - new Date(effectiveProgress.started_at).getTime()) / 1000))
    : 0;
  const showQueueWarning = isQueued && queuedSeconds >= 8;
  if (!canSchedule) {
    return (
      <Alert className="bg-amber-50 border-amber-200">
        <AlertTriangle className="h-5 w-5 text-amber-600" />
        <AlertTitle className="text-amber-800">Akses dibatasi</AlertTitle>
        <AlertDescription className="text-amber-700">
          Halaman ini hanya dapat diakses oleh Admin Jurusan.
        </AlertDescription>
      </Alert>
    );
  }

  return (
    <>
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground lg:text-3xl flex items-center gap-2">
            <Sparkles className="h-6 w-6 text-primary lg:h-7 lg:w-7" />
            Generate Jadwal
          </h1>
          <p className="text-muted-foreground mt-1">
            Atur parameter lalu jalankan generate jadwal untuk semester aktif.
          </p>
        </div>
      </div>

      {isGenerating && (
        <Alert className="bg-sky-50 border-sky-200">
          <Info className="h-5 w-5 text-sky-600" />
          <AlertTitle className="text-sky-800">Proses Sedang Berjalan</AlertTitle>
          <AlertDescription className="text-sky-700">
            Generate jadwal untuk <span className="font-semibold">{scopeLabel}</span> sedang berjalan. Anda bisa membuka halaman lain dan kembali lagi nanti.
          </AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* L E F T   P A N E L  :  C O N F I G */}
        <div className="lg:col-span-1 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>
                <Settings className="w-5 h-5 text-primary" /> Parameter Generate
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="rounded-lg border border-slate-200 bg-slate-50/70">
                <button
                  type="button"
                  onClick={() => setShowParameters(!showParameters)}
                  className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                >
                  <span>
                    <span className="block text-sm font-bold text-foreground">Opsi Settingan Parameter</span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">
                      Populasi {params.num_kromosom}, generasi {params.max_generation}
                    </span>
                  </span>
                  <span className="inline-flex items-center gap-2 text-xs font-bold text-primary">
                    {showParameters ? "Sembunyikan" : "Tampilkan"}
                    <ChevronDown className={`h-4 w-4 transition-transform ${showParameters ? "rotate-180" : ""}`} />
                  </span>
                </button>

                {showParameters && (
                  <div className="space-y-4 border-t border-slate-200 bg-white p-4 animate-in fade-in slide-in-from-top-2">
                    <div>
                      <label className="text-xs font-semibold uppercase text-muted-foreground mb-1 block">
                        Jumlah Populasi
                      </label>
                      <input
                        type="number"
                        name="num_kromosom"
                        value={params.num_kromosom}
                        onChange={handleChange}
                        className="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white focus:bg-white transition-colors focus:ring-2 ring-primary/20 outline-none text-sm font-medium"
                      />
                      <p className="text-[11px] text-muted-foreground mt-1">
                        Jumlah kandidat jadwal pada tiap generasi.
                      </p>
                    </div>

                    <div>
                      <label className="text-xs font-semibold uppercase text-muted-foreground mb-1 block">
                        Maksimal Generasi
                      </label>
                      <input
                        type="number"
                        name="max_generation"
                        value={params.max_generation}
                        onChange={handleChange}
                        className="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white focus:bg-white transition-colors focus:ring-2 ring-primary/20 outline-none text-sm font-medium"
                      />
                      <p className="text-[11px] text-muted-foreground mt-1">
                        Batas perulangan proses generate.
                      </p>
                    </div>

                    {SHOW_PARAMETER_RECOMMENDATION && preview && (
                      <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 space-y-2">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <p className="text-[11px] font-bold uppercase tracking-wider text-emerald-700">
                              Saran Parameter
                            </p>
                            <p className="text-[11px] font-semibold text-emerald-700 mt-1">
                              {recommendationProfile.label}
                            </p>
                            <p className="text-xs text-emerald-800 mt-1">
                              {recommendedParams.num_kromosom} populasi, {recommendedParams.max_generation} generasi,
                              {" "}crossover {recommendedParams.crossover_rate}%, mutasi {recommendedParams.mutation_rate}%.
                            </p>
                          </div>
                          <button
                            type="button"
                            onClick={applyRecommendedParams}
                            disabled={isGenerating}
                            className="shrink-0 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                          >
                            Terapkan
                          </button>
                        </div>
                        <p className="text-[11px] text-emerald-700">
                          Berdasarkan {preview.unscheduled_count} kelas yang belum terjadwal. {recommendationNote}
                        </p>
                        {hasCustomizedParams && (
                          <p className="text-[11px] text-amber-700">
                            Anda sedang memakai pengaturan manual.
                          </p>
                        )}
                      </div>
                    )}

                    <button
                      type="button"
                      onClick={() => setShowAdvanced(!showAdvanced)}
                      className="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-primary transition-colors hover:bg-slate-100"
                    >
                      Opsi Tambahan
                      <span className="inline-flex items-center gap-2 text-muted-foreground">
                        {showAdvanced ? "Sembunyikan" : "Tampilkan"}
                        <ChevronDown className={`h-4 w-4 transition-transform ${showAdvanced ? "rotate-180" : ""}`} />
                      </span>
                    </button>

                    {showAdvanced && (
                      <div className="space-y-4 animate-in fade-in slide-in-from-top-2">
                        <div>
                          <label className="text-xs font-semibold uppercase text-muted-foreground mb-1 block">
                            Crossover Rate (%)
                          </label>
                          <input
                            type="number"
                            name="crossover_rate"
                            value={params.crossover_rate}
                            onChange={handleChange}
                            className="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white focus:bg-white transition-colors focus:ring-2 ring-primary/20 outline-none text-sm font-medium"
                          />
                        </div>
                        <div>
                          <label className="text-xs font-semibold uppercase text-muted-foreground mb-1 block">
                            Mutation Rate (%)
                          </label>
                          <input
                            type="number"
                            name="mutation_rate"
                            value={params.mutation_rate}
                            onChange={handleChange}
                            className="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white focus:bg-white transition-colors focus:ring-2 ring-primary/20 outline-none text-sm font-medium"
                          />
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>

              <div className="pt-4">
                <button
                  onClick={handleGenerate}
                  disabled={isGenerating || preview?.unscheduled_count === 0}
                  className={`w-full relative overflow-hidden rounded-xl h-12 flex items-center justify-center gap-2 font-bold text-white transition-all shadow-lg ${
                    isGenerating
                      ? "bg-primary/70 cursor-not-allowed"
                      : preview?.unscheduled_count === 0
                      ? "bg-slate-400 cursor-not-allowed"
                      : "bg-primary hover:bg-primary/90 hover:scale-[1.02] active:scale-[0.98]"
                  }`}
                >
                  {isGenerating ? (
                    <>
                      <RefreshCcw className="w-5 h-5 animate-spin" />
                      Menjalankan...
                    </>
                  ) : (
                    <>
                      <Zap className="w-5 h-5" />
                      Jalankan Generate
                    </>
                  )}
                </button>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <button
                  type="button"
                  onClick={handleRestoreLast}
                  disabled={isGenerating || restoreLastMutation.isPending || !lastSnapshot}
                  className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 transition-colors hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {restoreLastMutation.isPending ? "Memulihkan..." : lastSnapshotLabel}
                </button>
                <button
                  type="button"
                  onClick={handleClearAll}
                  disabled={isGenerating || clearAllMutation.isPending || generatedCount === 0}
                  className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 transition-colors hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {clearAllMutation.isPending ? "Membersihkan..." : "Hapus Hasil Generate"}
                </button>
              </div>

              <p className="text-[11px] text-muted-foreground">
                Sistem menyimpan snapshot sebelum generate dan sebelum pembersihan.
              </p>
            </CardContent>
          </Card>

          <SummaryCard preview={preview} isLoadingPreview={isLoadingPreview} scopeLabel={scopeLabel} />
        </div>

        {/* R I G H T   P A N E L  :  R E S U L T S */}
        <div className="lg:col-span-2 space-y-6">
          {shouldShowProcessPanel ? (
            <ProgressConsole
              logContainerRef={logContainerRef}
              effectiveProgress={effectiveProgress}
              progressLogs={progressLogs}
              processStatus={processStatus}
              isQueued={isQueued}
              isProcessing={isProcessing}
              isFinished={isFinished}
              isGenerating={isGenerating}
              isWaitingForWorker={isWaitingForWorker}
              showQueueWarning={showQueueWarning}
              paramsMaxGeneration={params.max_generation}
              cancelPending={cancelMutation.isPending}
              onCancel={handleCancel}
              onClose={handleCloseProcessPanel}
            />
          ) : null}

          <ResultPanel
            result={result}
            resultBestFitness={resultBestFitness}
            resultGeneration={resultGeneration}
            resultRur={resultRur}
            resultClashes={resultClashes}
            shouldShowProcessPanel={shouldShowProcessPanel}
          />
        </div>
      </div>
    </div>
    <ConfirmActionDialog
      open={pendingScheduleAction !== null}
      onOpenChange={(open) => {
        if (!open && !restoreLastMutation.isPending && !clearAllMutation.isPending) {
          setPendingScheduleAction(null);
        }
      }}
	      title={pendingScheduleAction === "restore" ? "Pulihkan hasil generate terakhir?" : "Hapus hasil generate?"}
	      description={
	        pendingScheduleAction === "restore"
	          ? "Hanya jadwal hasil generate untuk scope ini yang akan dipulihkan. Jadwal manual tetap dipertahankan."
	          : "Hanya jadwal hasil generate pada scope dan semester aktif yang akan dihapus. Jadwal manual tetap dipertahankan."
	      }
	      confirmLabel={pendingScheduleAction === "restore" ? "Pulihkan" : "Hapus"}
      destructive={pendingScheduleAction === "clear"}
      loading={restoreLastMutation.isPending || clearAllMutation.isPending}
      onConfirm={confirmPendingScheduleAction}
    />
    </>
  );
}
