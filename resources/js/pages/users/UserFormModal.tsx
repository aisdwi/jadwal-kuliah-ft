import { AnimatePresence, motion } from "framer-motion";
import { Eye, EyeOff, Loader2, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import type { Dispatch, SetStateAction } from "react";
import type {
    ApiUser,
    DosenOption,
    JurusanOption,
    ProgramStudiOption,
    RoleOption,
    UserFormData,
} from "@/pages/users/usersPageConfig";

type UserFormModalProps = {
    open: boolean;
    editingUser: ApiUser | null;
    formData: UserFormData;
    setFormData: Dispatch<SetStateAction<UserFormData>>;
    roles: RoleOption[];
    jurusanList: JurusanOption[];
    programStudiList: ProgramStudiOption[];
    dosenList: DosenOption[];
    showPassword: boolean;
    setShowPassword: Dispatch<SetStateAction<boolean>>;
    showJurusan: boolean;
    showProgramStudi: boolean;
    showDosen: boolean;
    isCreatingDosenUser: boolean;
    isSuperAdmin: boolean;
    isSaving: boolean;
    onClose: () => void;
    onSave: () => void;
    onLoadProgramStudi: (jurusanId?: string | number | null) => Promise<void>;
    onProgramStudiLoadError: () => void;
};

export function UserFormModal({
    open,
    editingUser,
    formData,
    setFormData,
    roles,
    jurusanList,
    programStudiList,
    dosenList,
    showPassword,
    setShowPassword,
    showJurusan,
    showProgramStudi,
    showDosen,
    isCreatingDosenUser,
    isSuperAdmin,
    isSaving,
    onClose,
    onSave,
    onLoadProgramStudi,
    onProgramStudiLoadError,
}: UserFormModalProps) {
    return (
        <AnimatePresence>
            {open && (
                <div className="fixed inset-0 z-[70]">
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="modal-overlay-soft fixed inset-0"
                        onClick={onClose}
                    />

                    <div className="fixed inset-0 z-[71] flex items-center justify-center overflow-y-auto px-4 py-6">
                        <motion.div
                            initial={{ opacity: 0, scale: 0.95, y: 20 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            exit={{ opacity: 0, scale: 0.95, y: 20 }}
                            transition={{ type: "spring", damping: 25, stiffness: 350 }}
                            className="modal-surface w-full max-w-2xl max-h-[calc(100vh-80px)] flex flex-col"
                            onClick={(event) => event.stopPropagation()}
                        >
                            <div className="flex items-center justify-between mb-4 shrink-0">
                                <div>
                                    <h2 className="text-lg font-bold text-foreground">
                                        {editingUser ? "Ubah Pengguna" : "Tambah Pengguna"}
                                    </h2>
                                    <p className="text-sm text-muted-foreground mt-0.5">
                                        Atur akun, peran, dan keterkaitan akademik pengguna.
                                    </p>
                                </div>
                                <button
                                    onClick={onClose}
                                    className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-colors"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <div className="flex-1 overflow-y-auto min-h-0 pr-1">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                                            {showDosen ? "Nama Akun" : "Nama"} <span className="text-destructive">*</span>
                                        </label>
                                        <Input
                                            placeholder={
                                                isCreatingDosenUser
                                                    ? "Otomatis mengikuti dosen yang dipilih"
                                                    : "Contoh: Muhammad Farhan"
                                            }
                                            value={formData.nama_user}
                                            onChange={(event) =>
                                                setFormData({ ...formData, nama_user: event.target.value })
                                            }
                                            className="form-field-input"
                                            readOnly={isCreatingDosenUser}
                                            disabled={isCreatingDosenUser}
                                        />
                                        {isCreatingDosenUser && (
                                            <p className="mt-1.5 text-xs text-muted-foreground">
                                                Nama akun diisi otomatis dari data dosen yang dipilih.
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                                            Email <span className="text-destructive">*</span>
                                        </label>
                                        <Input
                                            type="email"
                                            placeholder="Contoh: user@universitas.edu"
                                            value={formData.email}
                                            onChange={(event) =>
                                                setFormData({ ...formData, email: event.target.value })
                                            }
                                            className="form-field-input"
                                            autoComplete="off"
                                            name="new-email"
                                            data-lpignore="true"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                                            Password {!editingUser && <span className="text-destructive">*</span>}
                                        </label>
                                        <div className="relative">
                                            <Input
                                                type={showPassword ? "text" : "password"}
                                                placeholder={
                                                    editingUser
                                                        ? "Kosongkan jika tidak ingin mengubah password"
                                                        : "Password minimal 6 karakter"
                                                }
                                                value={formData.password}
                                                onChange={(event) =>
                                                    setFormData({ ...formData, password: event.target.value })
                                                }
                                                className="form-field-input password-input-no-reveal pr-10"
                                                autoComplete="new-password"
                                                name="new-password"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                                aria-label={showPassword ? "Sembunyikan password" : "Tampilkan password"}
                                            >
                                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                                            Peran <span className="text-destructive">*</span>
                                        </label>
                                        <Select
                                            value={formData.role_id}
                                            onValueChange={(value) =>
                                                setFormData({
                                                    ...formData,
                                                    role_id: value,
                                                    jurusan_id: "",
                                                    program_studi_id: "",
                                                    dosen_id: "",
                                                })
                                            }
                                            disabled={!isSuperAdmin && editingUser !== null}
                                        >
                                            <SelectTrigger className="form-field-select-trigger w-full">
                                                <SelectValue placeholder="Pilih peran" />
                                            </SelectTrigger>
                                            <SelectContent className="form-field-select-content">
                                                {roles.map((role) => (
                                                    <SelectItem key={role.id} value={String(role.id)}>
                                                        {role.role}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {showJurusan && (
                                        <JurusanField
                                            value={formData.jurusan_id}
                                            jurusanList={jurusanList}
                                            onChange={(value) => {
                                                setFormData({ ...formData, jurusan_id: value, program_studi_id: "" });
                                                onLoadProgramStudi(value).catch(onProgramStudiLoadError);
                                            }}
                                        />
                                    )}

                                    {showProgramStudi && (
                                        <ProgramStudiField
                                            value={formData.program_studi_id}
                                            disabled={!formData.jurusan_id}
                                            programStudiList={programStudiList}
                                            onChange={(value) => setFormData({ ...formData, program_studi_id: value })}
                                        />
                                    )}

                                    {showDosen && (
                                        <DosenField
                                            value={formData.dosen_id}
                                            dosenList={dosenList}
                                            className={cn(showJurusan ? "" : "md:col-span-1")}
                                            onChange={(value) => {
                                                const matchedDosen = dosenList.find((dosen) => String(dosen.id) === value);

                                                setFormData({
                                                    ...formData,
                                                    dosen_id: value,
                                                    nama_user:
                                                        isCreatingDosenUser && matchedDosen
                                                            ? matchedDosen.nama_lengkap
                                                            : formData.nama_user,
                                                });
                                            }}
                                        />
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-border/20 shrink-0">
                                <Button
                                    variant="ghost"
                                    onClick={onClose}
                                    className="bg-transparent hover:bg-muted text-muted-foreground hover:text-foreground"
                                    disabled={isSaving}
                                >
                                    Batal
                                </Button>
                                <Button onClick={onSave} disabled={isSaving} className="gradient-btn">
                                    {isSaving ? (
                                        <>
                                            <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                            Menyimpan...
                                        </>
                                    ) : editingUser ? (
                                        "Simpan Perubahan"
                                    ) : (
                                        "Simpan"
                                    )}
                                </Button>
                            </div>
                        </motion.div>
                    </div>
                </div>
            )}
        </AnimatePresence>
    );
}

function JurusanField({
    value,
    jurusanList,
    onChange,
}: {
    value: string;
    jurusanList: JurusanOption[];
    onChange: (value: string) => void;
}) {
    return (
        <div>
            <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                Jurusan <span className="text-destructive">*</span>
            </label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger className="form-field-select-trigger w-full">
                    <SelectValue placeholder="Pilih jurusan" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content">
                    {jurusanList.map((jurusan) => (
                        <SelectItem key={jurusan.id} value={String(jurusan.id)}>
                            {jurusan.nama_jurusan}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function ProgramStudiField({
    value,
    disabled,
    programStudiList,
    onChange,
}: {
    value: string;
    disabled: boolean;
    programStudiList: ProgramStudiOption[];
    onChange: (value: string) => void;
}) {
    return (
        <div>
            <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                Program Studi <span className="text-destructive">*</span>
            </label>
            <Select value={value} onValueChange={onChange} disabled={disabled}>
                <SelectTrigger className="form-field-select-trigger w-full">
                    <SelectValue placeholder={disabled ? "Pilih jurusan terlebih dahulu" : "Pilih program studi"} />
                </SelectTrigger>
                <SelectContent className="form-field-select-content">
                    {programStudiList.map((programStudi) => (
                        <SelectItem key={programStudi.id} value={String(programStudi.id)}>
                            {programStudi.nama_prodi}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function DosenField({
    value,
    dosenList,
    className,
    onChange,
}: {
    value: string;
    dosenList: DosenOption[];
    className?: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className={className}>
            <label className="block text-sm font-medium text-muted-foreground mb-1.5">
                Dosen <span className="text-destructive">*</span>
            </label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger className="form-field-select-trigger w-full">
                    <SelectValue placeholder="Pilih dosen" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content">
                    {dosenList.map((dosen) => (
                        <SelectItem key={dosen.id} value={String(dosen.id)}>
                            {dosen.nama_lengkap} ({dosen.inisial})
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
