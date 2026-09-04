import { useState, useRef } from "react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Download, Upload, AlertCircle } from "lucide-react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import api from "@/lib/api";
import { getUserFriendlyError } from "@/lib/error-messages";
import { useToast } from "@/hooks/use-toast";

interface ExcelImportModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    templateUrl: string;
    importUrl: string;
    onSuccess: () => void;
    description?: string;
}

export function ExcelImportModal({
    open,
    onOpenChange,
    title,
    templateUrl,
    importUrl,
    onSuccess,
    description = "Pastikan file Excel yang diunggah sesuai dengan format template yang disediakan.",
}: ExcelImportModalProps) {
    const [file, setFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { toast } = useToast();

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setError(null);
        if (e.target.files && e.target.files.length > 0) {
            const selectedFile = e.target.files[0];
            const validTypes = [
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "application/vnd.ms-excel",
            ];
            if (!validTypes.includes(selectedFile.type) && !selectedFile.name.match(/\.(xls|xlsx)$/i)) {
                setError("Format file tidak didukung. Harap unggah file .xls atau .xlsx");
                setFile(null);
                return;
            }
            setFile(selectedFile);
        }
    };

    const handleImport = async () => {
        if (!file) {
            setError("Silakan pilih file terlebih dahulu.");
            return;
        }

        setIsUploading(true);
        setError(null);

        const formData = new FormData();
        formData.append("file", file);

        try {
            await api.post(importUrl, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });
            toast({ title: "Import Berhasil", description: "Data berhasil diimpor dari file Excel." });
            onSuccess();
            onOpenChange(false);
            setFile(null);
            if (fileInputRef.current) fileInputRef.current.value = "";
        } catch (err: any) {
            const message = getUserFriendlyError(err, { context: "import" });
            setError(message);
            toast({ title: "Import Gagal", description: message, variant: "destructive" });
        } finally {
            setIsUploading(false);
        }
    };

    const handleDownloadTemplate = () => {
        window.location.href = templateUrl;
    };

    return (
        <Dialog open={open} onOpenChange={(val) => {
            onOpenChange(val);
            if (!val) {
                setFile(null);
                setError(null);
            }
        }}>
            <DialogContent className="sm:max-w-md glass-elevated border-white/40 shadow-2xl">
                <DialogHeader>
                    <DialogTitle className="text-xl">Import {title}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">{description}</DialogDescription>
                </DialogHeader>

                <div className="grid gap-6 py-4">
                    <div className="flex flex-col gap-3">
                        <Label>Langkah 1: Unduh Format Template</Label>
                        <div className="flex flex-col sm:flex-row gap-2 items-start sm:items-center justify-between p-3 rounded-lg border border-dashed border-primary/20 bg-primary/5">
                            <span className="text-xs text-muted-foreground w-full sm:w-2/3">Gunakan template ini untuk memastikan struktur kolom sama dengan yang terbaca oleh sistem.</span>
                            <Button type="button" variant="outline" size="sm" onClick={handleDownloadTemplate} className="shrink-0 w-full sm:w-auto h-8">
                                <Download className="mr-2 h-3.5 w-3.5" />
                                Template
                            </Button>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3">
                        <Label htmlFor="file-upload">Langkah 2: Unggah File Data</Label>
                        <Input
                            id="file-upload"
                            ref={fileInputRef}
                            type="file"
                            accept=".xlsx,.xls"
                            onChange={handleFileChange}
                            disabled={isUploading}
                            className="cursor-pointer file:cursor-pointer"
                        />
                        {error && (
                            <Alert variant="destructive" className="py-2 px-3 border-red-500/50 bg-red-500/10 h-auto">
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription className="text-xs ml-2">{error}</AlertDescription>
                            </Alert>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button variant="ghost" onClick={() => onOpenChange(false)} disabled={isUploading}>
                        Batal
                    </Button>
                    <Button onClick={handleImport} disabled={!file || isUploading} className="bg-amber-500 hover:bg-amber-600 text-white min-w-[120px]">
                        {isUploading ? (
                            <>
                                <div className="h-4 w-4 rounded-full border-2 border-background border-t-foreground animate-spin mr-2" />
                                Mengimpor...
                            </>
                        ) : (
                            <>
                                <Upload className="mr-2 h-4 w-4" /> Import Data
                            </>
                        )}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
