import { ArrowRight, Info, Settings } from "lucide-react";
import { Link } from "react-router-dom";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AlertTriangle } from "lucide-react";
import {
  PlainCard as Card,
  PlainCardContent as CardContent,
  PlainCardHeader as CardHeader,
  PlainCardTitle as CardTitle,
} from "@/components/shared/PlainCard";

type ResultPanelProps = {
  result: any;
  resultBestFitness: number;
  resultGeneration: number;
  resultRur: number;
  resultClashes: {
    dosen: number;
    ruang: number;
    kelas: number;
    angkatan: number;
  };
  shouldShowProcessPanel: boolean;
};

export function ResultPanel({
  result,
  resultBestFitness,
  resultGeneration,
  resultRur,
  resultClashes,
  shouldShowProcessPanel,
}: ResultPanelProps) {
  if (!result) {
    if (shouldShowProcessPanel) return null;

    return (
      <Card className="min-h-[400px] flex flex-col items-center justify-center bg-slate-50 border-dashed border-2 border-slate-200">
        <div className="w-20 h-20 mb-4 rounded-full bg-slate-100 flex items-center justify-center">
          <Settings className="w-8 h-8 text-slate-300" />
        </div>
        <h3 className="text-lg font-bold text-slate-400">Belum Ada Hasil</h3>
        <p className="text-muted-foreground text-sm max-w-sm text-center mt-1">
          Atur parameter di panel kiri lalu klik tombol "Jalankan Generate".
        </p>
      </Card>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in zoom-in-95 duration-500">
      {result.warnings && result.warnings.length > 0 && (
        <Alert className="bg-amber-50 border-amber-200">
          <AlertTriangle className="h-5 w-5 text-amber-600" />
          <AlertTitle className="text-amber-800 font-bold mb-1">Peringatan: Jadwal Tersimpan Bermasalah</AlertTitle>
          <AlertDescription className="text-amber-700 text-xs">
            <ul className="list-disc pl-4 space-y-1 mt-2">
              {result.warnings.map((warning: string, i: number) => (
                <li key={i}>{warning}</li>
              ))}
            </ul>
            <p className="mt-2 font-medium">Jadwal yang sudah tersimpan tidak diubah. Periksa kembali di editor jadwal.</p>
          </AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-3 gap-4">
        <Card className="bg-gradient-to-br from-indigo-50 to-white">
          <p className="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Nilai Fitness</p>
          <div className="flex items-end gap-2">
            <p className="text-4xl font-black text-slate-800">{resultBestFitness.toFixed(4)}</p>
            <p className="text-sm font-medium text-slate-500 mb-1">/ 1</p>
          </div>
        </Card>

        <Card className="bg-gradient-to-br from-emerald-50 to-white">
          <p className="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-1">Generasi Tercapai</p>
          <div className="flex items-end gap-2">
            <p className="text-4xl font-black text-slate-800">{resultGeneration}</p>
            <p className="text-sm font-medium text-slate-500 mb-1">iterasi</p>
          </div>
        </Card>

        <Card className="bg-gradient-to-br from-amber-50 to-white">
          <div className="flex justify-between items-start">
            <p className="text-xs font-bold text-amber-500 uppercase tracking-widest mb-1 title-with-tooltip" title="Tingkat Pemakaian Ruang">R U R</p>
            <Info className="w-4 h-4 text-amber-300" />
          </div>
          <div className="flex items-end gap-2">
            <p className="text-4xl font-black text-slate-800">{resultRur}%</p>
          </div>
        </Card>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>
            Ringkasan Bentrok
            {resultBestFitness === 1 && (
              <span className="ml-3 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">
                Sempurna
              </span>
            )}
          </CardTitle>
          <Link
            to="/scheduling/editor"
            className="text-xs font-bold text-primary bg-primary/10 px-3 py-1.5 rounded-lg hover:bg-primary/20 transition-colors flex items-center gap-1"
          >
            Buka Editor Jadwal <ArrowRight className="w-3 h-3" />
          </Link>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <ClashStat label="Bentrok Dosen" value={resultClashes.dosen} warningClass="rose" />
            <ClashStat label="Bentrok Ruang" value={resultClashes.ruang} warningClass="rose" />
            <ClashStat label="Bentrok Kelas" value={resultClashes.kelas} warningClass="amber" />
            <ClashStat label="Kepadatan SMT" value={resultClashes.angkatan} warningClass="amber" />
          </div>
        </CardContent>
      </Card>

      {result.best_cromossom_printed?.genes && (
        <Card>
          <CardHeader>
            <CardTitle>Detail Hasil</CardTitle>
            <p className="text-xs text-muted-foreground mt-1">
              Ringkasan penempatan mata kuliah, ruang, dan waktu.
            </p>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-1.5 max-h-[300px] overflow-y-auto overflow-x-hidden pr-2 custom-scrollbar py-2">
              {result.best_cromossom_printed.genes.map((gene: any, i: number) => (
                <GeneDot key={i} gene={gene} index={i} />
              ))}
            </div>
            <div className="flex items-center gap-4 mt-4 text-[11px] font-semibold text-slate-500 bg-slate-50 p-2 rounded-lg justify-center w-max mx-auto">
              <div className="flex items-center gap-1.5"><div className="w-3 h-3 rounded bg-emerald-500" /> Aman</div>
              <div className="flex items-center gap-1.5"><div className="w-3 h-3 rounded bg-amber-400" /> Aturan Tambahan</div>
              <div className="flex items-center gap-1.5"><div className="w-3 h-3 rounded bg-rose-500" /> Aturan Utama</div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function ClashStat({
  label,
  value,
  warningClass,
}: {
  label: string;
  value: number;
  warningClass: "rose" | "amber";
}) {
  const hasIssue = value > 0;
  const issueBackground = warningClass === "rose" ? "bg-rose-50 border-rose-200" : "bg-amber-50 border-amber-200";
  const issueText = warningClass === "rose" ? "text-rose-600" : "text-amber-600";

  return (
    <div className={`p-3 rounded-xl border ${hasIssue ? issueBackground : "bg-emerald-50 border-emerald-200"}`}>
      <p className="text-[11px] font-bold text-slate-500 mb-1">{label}</p>
      <p className={`text-2xl font-black ${hasIssue ? issueText : "text-emerald-600"}`}>
        {value}
      </p>
    </div>
  );
}

function GeneDot({ gene, index }: { gene: any; index: number }) {
  let colorClass = "bg-emerald-500";
  let isHard = false;

  if (gene.status === "hard") {
    colorClass = "bg-rose-500";
    isHard = true;
  } else if (gene.status === "soft") {
    colorClass = "bg-amber-400";
  }

  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <div
          className={`w-6 h-6 rounded-full ${colorClass} shadow-sm cursor-pointer flex items-center justify-center transition-all hover:scale-125 hover:shadow-lg ${isHard ? "animate-pulse" : ""}`}
          data-gene-index={index}
        />
      </TooltipTrigger>
      <TooltipContent side="top" sideOffset={6} className="z-[9999] w-64 max-w-xs bg-slate-900 text-white text-xs p-3 rounded-lg shadow-2xl border border-slate-700 whitespace-normal">
        <p className="font-semibold text-emerald-300 mb-2 break-words">{gene.nama_mk}</p>
        <div className="space-y-1 text-slate-300 text-[11px]">
          <div className="flex justify-between gap-2">
            <span className="text-slate-400">Kelas:</span>
            <span className="text-right break-words flex-1">{gene.nama_kelas}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-slate-400">Hari:</span>
            <span className="text-right">{gene.hari}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-slate-400">Pukul:</span>
            <span className="text-right">{gene.waktu}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-slate-400">Ruangan:</span>
            <span className="text-right">{gene.ruangan}</span>
          </div>
        </div>
      </TooltipContent>
    </Tooltip>
  );
}
