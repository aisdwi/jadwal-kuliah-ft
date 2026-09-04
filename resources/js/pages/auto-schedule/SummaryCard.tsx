import { BarChart, CheckCircle2, RefreshCcw } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import {
  PlainCard as Card,
  PlainCardContent as CardContent,
  PlainCardHeader as CardHeader,
  PlainCardTitle as CardTitle,
} from "@/components/shared/PlainCard";

type SummaryCardProps = {
  preview: any;
  isLoadingPreview: boolean;
  scopeLabel: string;
};

export function SummaryCard({ preview, isLoadingPreview, scopeLabel }: SummaryCardProps) {
  const totalClasses = preview?.all_classes ?? preview?.total_kelas_kuliah ?? 0;
  const progress = preview
    ? (Number(preview.scheduled_count ?? 0) / Math.max(1, Number(totalClasses))) * 100
    : 0;

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          <BarChart className="w-5 h-5 text-indigo-500" /> Ringkasan Data
        </CardTitle>
        <p className="text-xs text-muted-foreground">Data aktif: {scopeLabel}</p>
      </CardHeader>
      <CardContent>
        {isLoadingPreview ? (
          <div className="h-24 flex items-center justify-center">
            <RefreshCcw className="w-5 h-5 animate-spin text-muted-foreground" />
          </div>
        ) : preview ? (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p className="text-[10px] uppercase font-bold text-muted-foreground">Kapasitas Slot</p>
                <p className="text-xl font-black mt-1 text-slate-800">{preview.slots_total ?? preview.max_capacity ?? 0}</p>
              </div>
              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p className="text-[10px] uppercase font-bold text-muted-foreground">Total Kelas</p>
                <p className="text-xl font-black mt-1 text-indigo-600">{totalClasses}</p>
              </div>
            </div>

            <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col gap-2">
              <div className="flex justify-between items-center text-sm">
                <span className="font-medium text-slate-600">Sudah Terjadwal</span>
                <span className="font-bold text-emerald-600">{preview.scheduled_count}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="font-medium text-slate-600">Belum Terjadwal</span>
                <span className="font-bold text-rose-500">{preview.unscheduled_count}</span>
              </div>

              <div className="w-full h-2 bg-slate-200 mt-1 rounded-full overflow-hidden">
                <div className="h-full bg-emerald-500" style={{ width: `${progress}%` }} />
              </div>
            </div>

            {preview.unscheduled_count === 0 && (
              <Alert className="bg-emerald-50 border-emerald-200">
                <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                <AlertTitle className="text-emerald-800">Selesai</AlertTitle>
                <AlertDescription className="text-emerald-700 text-xs">
                  Semua kelas telah memiliki jadwal.
                </AlertDescription>
              </Alert>
            )}
          </div>
        ) : (
          <div className="text-center text-sm text-rose-500">Gagal mengambil data preview</div>
        )}
      </CardContent>
    </Card>
  );
}
