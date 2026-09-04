import { Clock, RefreshCcw, Terminal, XCircle } from "lucide-react";
import { formatElapsed } from "@/pages/auto-schedule/autoScheduleUtils";
import type { Ref } from "react";

type ProgressConsoleProps = {
  logContainerRef: Ref<HTMLDivElement>;
  effectiveProgress: any;
  progressLogs: any[];
  processStatus?: string;
  isQueued: boolean;
  isProcessing: boolean;
  isFinished: boolean;
  isGenerating: boolean;
  isWaitingForWorker: boolean;
  showQueueWarning: boolean;
  paramsMaxGeneration: number;
  cancelPending: boolean;
  onCancel: () => void;
  onClose: () => void;
};

const getIndicatorLabel = (indicator?: string) => {
  if (indicator === "up" || indicator === "Ã¢â€ â€˜" || indicator === "â†‘") return "NAIK";
  if (indicator === "down" || indicator === "Ã¢â€ â€œ" || indicator === "â†“") return "TURUN";
  return "STABIL";
};

const getLogIcon = (type?: string) => {
  if (type === "success") return "OK";
  if (type === "warning") return "WARN";
  if (type === "error") return "ERR";
  return "INFO";
};

const normalizeIndicator = (indicator?: string) => {
  if (indicator === "up" || indicator === "â†‘") return "NAIK";
  if (indicator === "down" || indicator === "â†“") return "TURUN";
  return getIndicatorLabel(indicator);
};

const getGenerationColor = (indicator?: string) => {
  if (normalizeIndicator(indicator) === "NAIK") return "text-emerald-700";
  if (normalizeIndicator(indicator) === "TURUN") return "text-rose-700";
  return "text-slate-600";
};

const getLogColor = (log: any) => {
  if (log.type === "success") return "text-emerald-700";
  if (log.type === "warning") return "text-amber-700";
  if (log.type === "error") return "text-rose-700";
  if (log.type === "info") return "text-sky-700";
  if (log.type === "generation") return getGenerationColor(log.indicator);
  return "text-slate-700";
};

const renderLogEntry = (log: any, i: number) => {
  if (log.type === "generation") {
    const hardTotal = Number(log.hard_total ?? log.total_clash ?? 0);
    const hardDosen = Number(log.hard_dosen ?? log.clash_dosen ?? 0);
    const hardRuang = Number(log.hard_ruang ?? log.clash_ruang ?? 0);
    const hardKelas = Number(log.hard_kelas ?? log.clash_kelas ?? 0);
    const hardAngkatan = Number(log.hard_angkatan ?? log.clash_angkatan ?? 0);
    const softTotal = Number(log.soft_total ?? 0);
    const softPW = Number(log.soft_pw ?? 0);
    const softMK = Number(log.soft_mk ?? 0);
    const softJW = Number(log.soft_jw ?? 0);

    const hardColor = hardTotal === 0 ? "text-emerald-700" : "text-rose-700";
    const softColor = softTotal === 0 ? "text-emerald-700" : "text-amber-700";
    const fitnessColor = log.fitness >= 0.5 ? "text-emerald-700" : "text-amber-700";

    return (
      <div key={i} className="flex flex-wrap gap-x-1 leading-relaxed">
        <span className="text-slate-500">[{log.timestamp}]</span>
        <span className="text-indigo-700 font-semibold">Gen {String(log.gen).padStart(3, " ")}</span>
        <span className="text-slate-300">|</span>
        <span className={fitnessColor}>F={log.fitness}</span>
        <span className="text-slate-300">|</span>
        <span className={hardColor}>
          Hard({hardTotal}): D({hardDosen}), R({hardRuang}), K({hardKelas}), A({hardAngkatan})
        </span>
        <span className="text-slate-300">|</span>
        <span className={softColor}>
          Soft({softTotal}): PW({softPW}), MK({softMK}), JW({softJW})
        </span>
        <span className="text-slate-300">|</span>
        <span className={log.stagnant > 30 ? "text-amber-700" : "text-slate-500"}>
          Stag:{log.stagnant}
        </span>
        <span className="text-slate-300">|</span>
        <span className={getLogColor(log)}>{normalizeIndicator(log.indicator)}</span>
        <span className="text-slate-500 ml-auto">{formatElapsed(log.elapsed)}</span>
      </div>
    );
  }

  return (
    <div key={i} className={`${getLogColor(log)} leading-relaxed`}>
      <span className="text-slate-500">[{log.timestamp}]</span>{" "}
      <span>{getLogIcon(log.type)} {log.message}</span>
    </div>
  );
};

export function ProgressConsole({
  logContainerRef,
  effectiveProgress,
  progressLogs,
  processStatus,
  isQueued,
  isProcessing,
  isFinished,
  isGenerating,
  isWaitingForWorker,
  showQueueWarning,
  paramsMaxGeneration,
  cancelPending,
  onCancel,
  onClose,
}: ProgressConsoleProps) {
  const lastGenerationLog = progressLogs.filter((log: any) => log.type === "generation").pop();

  return (
    <div className="rounded-xl border border-slate-200 bg-white overflow-hidden relative shadow-sm">
      <div className="absolute top-0 left-0 w-full h-1 bg-slate-100 z-10">
        <div
          className="h-full bg-primary transition-all duration-300"
          style={{
            width: `${effectiveProgress?.max_generation ? (effectiveProgress.generation / effectiveProgress.max_generation) * 100 : 0}%`,
          }}
        />
      </div>

      <div className="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 mt-1">
        <Terminal className="w-4 h-4 text-emerald-700" />
        <span className="text-sm font-semibold text-slate-800">
          {processStatus === "completed" ? "Riwayat Generate" : "Log Generate"}
        </span>
        <span className="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600">
          {isQueued
            ? "Dalam Antrean"
            : isProcessing
            ? "Sedang Diproses"
            : processStatus === "completed"
            ? "Selesai"
            : processStatus === "failed"
            ? "Gagal"
            : processStatus === "canceled"
            ? "Dibatalkan"
            : "Siaga"}
        </span>
        <span className="ml-auto text-xs text-slate-500 flex items-center gap-1">
          <Clock className="w-3 h-3" />
          {lastGenerationLog ? formatElapsed(lastGenerationLog.elapsed || 0) : "0.0s"}
        </span>
      </div>

      <div className="px-4 py-3 border-b border-slate-200 bg-white space-y-2">
        <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-600">
          Keterangan
        </p>
        <div className="grid gap-2 md:grid-cols-2 text-[11px] leading-relaxed">
          <div className="rounded-lg border border-rose-100 bg-rose-50 p-2">
            <p className="font-semibold text-rose-700">Aturan Utama (harus 0)</p>
            <p className="text-slate-600">D = bentrok dosen, R = bentrok ruang, K = bentrok kelas, A = bentrok angkatan.</p>
          </div>
          <div className="rounded-lg border border-amber-100 bg-amber-50 p-2">
            <p className="font-semibold text-amber-700">Aturan Tambahan (semakin kecil semakin baik)</p>
            <p className="text-slate-600">PW = preferensi waktu dosen, MK = beban mengajar harian, JW = jeda waktu mengajar.</p>
          </div>
        </div>
        <p className="text-[11px] text-slate-600">
          Target praktis: capai <span className="font-semibold text-emerald-700">Hard(0)</span>, lalu kecilkan nilai <span className="font-semibold text-amber-700">Soft</span>.
        </p>
      </div>

      <div
        ref={logContainerRef}
        className="p-4 font-mono text-xs overflow-y-auto flex flex-col gap-0.5 bg-white text-slate-700"
        style={{ height: "400px" }}
      >
        {(isWaitingForWorker || progressLogs.length === 0) && (
          <div className="flex items-center gap-2 text-slate-500">
            <RefreshCcw className="w-3 h-3 animate-spin" />
            {isWaitingForWorker
              ? "Menunggu worker memulai proses..."
              : isFinished
              ? "Belum ada log yang ditampilkan."
              : "Menyiapkan data..."}
          </div>
        )}

        {progressLogs.map((log: any, i: number) => renderLogEntry(log, i))}
      </div>

      <div className="p-4 border-t border-slate-200 bg-slate-50 flex flex-col gap-3">
        <div className="flex justify-between items-center text-slate-700">
          <div className="flex items-center gap-3">
            <div className={`w-2.5 h-2.5 rounded-full ${isGenerating ? "bg-primary animate-pulse" : "bg-emerald-400"}`} />
            <span className="font-semibold text-sm">
              {isWaitingForWorker
                ? "Menunggu Worker..."
                : isProcessing
                ? "Sedang Berjalan..."
                : processStatus === "completed"
                ? "Selesai"
                : processStatus === "failed"
                ? "Gagal"
                : processStatus === "canceled"
                ? "Dibatalkan"
                : "Siap"}
            </span>
          </div>
          <div className="flex items-center gap-4">
            <div className="text-right">
              <div className="font-bold text-sm text-slate-900">
                Fitness: {effectiveProgress?.best_fitness ? Number(effectiveProgress.best_fitness).toFixed(4) : "0.0000"}
              </div>
              <div className="text-[10px] text-slate-500 uppercase tracking-wider">Terbaik Saat Ini</div>
            </div>
            <div className="text-right">
              <div className="font-bold text-lg text-slate-900">
                {effectiveProgress?.generation || 0} <span className="text-slate-500 text-sm">/ {effectiveProgress?.max_generation || paramsMaxGeneration}</span>
              </div>
              <div className="text-[10px] text-slate-500 uppercase tracking-wider">Generasi</div>
            </div>
          </div>
        </div>

        {effectiveProgress?.message && (
          <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
            {effectiveProgress.message}
          </div>
        )}

        {showQueueWarning && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Proses masih dalam antrean. Jika terlalu lama, pastikan worker queue sedang aktif.
          </div>
        )}

        {isGenerating ? (
          <button
            onClick={onCancel}
            disabled={cancelPending}
            className="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold transition-colors border border-rose-200 text-sm"
          >
            <XCircle className="w-4 h-4" />
            {cancelPending ? "Membatalkan..." : "Batalkan Generate"}
          </button>
        ) : (
          <button
            onClick={onClose}
            className="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-bold transition-colors border border-slate-200 text-sm"
          >
            Tutup Panel Proses
          </button>
        )}
      </div>
    </div>
  );
}
