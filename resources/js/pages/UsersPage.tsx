import { useEffect, useMemo, useState } from "react";
import { motion } from "framer-motion";
import {
    Plus,
    Search,
    Trash2,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAuth } from "@/contexts/AuthContext";
import { getUserFriendlyError } from "@/lib/error-messages";
import api from "@/lib/api";
import { toast } from "sonner";
import { ConfirmActionDialog } from "@/components/shared/ConfirmActionDialog";
import {
    emptyUserForm,
    JURUSAN_REQUIRED_ROLES,
    PAGE_SIZE,
    PROGRAM_STUDI_REQUIRED_ROLES,
    type ApiUser,
    type DosenOption,
    type JurusanOption,
    type ProgramStudiOption,
    type RoleOption,
} from "@/pages/users/usersPageConfig";
import { UserFormModal } from "@/pages/users/UserFormModal";
import { UsersTable } from "@/pages/users/UsersTable";

export default function UsersPage() {
    const { canManageUsers, isSuperAdmin, user } = useAuth();
    const [users, setUsers] = useState<ApiUser[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [roles, setRoles] = useState<RoleOption[]>([]);
    const [jurusanList, setJurusanList] = useState<JurusanOption[]>([]);
    const [programStudiList, setProgramStudiList] = useState<
        ProgramStudiOption[]
    >([]);
    const [dosenList, setDosenList] = useState<DosenOption[]>([]);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<ApiUser | null>(null);
    const [showPassword, setShowPassword] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [page, setPage] = useState(1);
    const [formData, setFormData] = useState(emptyUserForm);
    const [deleteTarget, setDeleteTarget] = useState<ApiUser | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteAllOpen, setDeleteAllOpen] = useState(false);
    const [isDeletingAll, setIsDeletingAll] = useState(false);

    const fetchUsers = async () => {
        setIsLoading(true);
        try {
            const res = await api.get("/users?per_page=all");
            setUsers(Array.isArray(res.data) ? res.data : res.data.data || []);
        } catch (err) {
            toast.error("Gagal memuat data pengguna", {
                description: getUserFriendlyError(err, { context: "load" }),
            });
        } finally {
            setIsLoading(false);
        }
    };

    const fetchProgramStudi = async (jurusanId?: string | number | null) => {
        const query = jurusanId ? `?jurusan_id=${jurusanId}` : "";
        const res = await api.get(`/referensi/program-studi${query}`);
        setProgramStudiList(res.data);
    };

    useEffect(() => {
        if (!canManageUsers) {
            return;
        }

        fetchUsers();
        api.get("/referensi/roles").then((r) =>
            setRoles(() => {
                const mappedRoles = (Array.isArray(r.data) ? r.data : []).map(
                    (role) => ({
                        ...role,
                        role: role.role || role.display_name || role.name || "",
                    }),
                );

                return Array.from(
                    new Map(
                        mappedRoles.map((role) => [role.role, role]),
                    ).values(),
                );
            }),
        );
        api.get("/referensi/jurusan").then((r) => setJurusanList(r.data));
        fetchProgramStudi(user?.jurusan_id ?? null).catch(() =>
            setProgramStudiList([]),
        );
        api.get("/dosen?per_page=all").then((r) => {
            const data = Array.isArray(r.data) ? r.data : r.data.data || [];
            setDosenList(data);
        });
    }, [canManageUsers, user?.jurusan_id]);

    const handleAdd = () => {
        setEditingUser(null);
        setShowPassword(false);
        setFormData(emptyUserForm);
        setModalOpen(true);
    };

    const handleEdit = async (targetUser: ApiUser) => {
        setEditingUser(targetUser);
        setShowPassword(false);
        if (targetUser.jurusan_id) {
            await fetchProgramStudi(targetUser.jurusan_id).catch(() =>
                setProgramStudiList([]),
            );
        }
        setFormData({
            nama_user: targetUser.nama_user,
            email: targetUser.email,
            password: "",
            role_id: String(targetUser.role_id),
            jurusan_id: targetUser.jurusan_id
                ? String(targetUser.jurusan_id)
                : "",
            program_studi_id: targetUser.program_studi_id
                ? String(targetUser.program_studi_id)
                : "",
            dosen_id: targetUser.dosen_id ? String(targetUser.dosen_id) : "",
        });
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setEditingUser(null);
        setShowPassword(false);
        setFormData(emptyUserForm);
    };

    const selectedRole = roles.find(
        (role) => String(role.id) === formData.role_id,
    );
    const selectedDosen = dosenList.find(
        (dosen) => String(dosen.id) === formData.dosen_id,
    );
    const selectedRoleName = selectedRole?.role ?? "";
    const showJurusan = JURUSAN_REQUIRED_ROLES.has(selectedRoleName);
    const showProgramStudi = PROGRAM_STUDI_REQUIRED_ROLES.has(selectedRoleName);
    const showDosen = selectedRole?.role === "Dosen";
    const isCreatingDosenUser = showDosen && editingUser === null;

    const handleSave = async () => {
        const resolvedNamaUser =
            isCreatingDosenUser && selectedDosen
                ? selectedDosen.nama_lengkap
                : formData.nama_user.trim();

        if (!resolvedNamaUser || !formData.email || !formData.role_id) {
            toast.error("Nama, email, dan peran wajib diisi");
            return;
        }
        if (showJurusan && !formData.jurusan_id) {
            toast.error("Jurusan wajib dipilih untuk peran ini");
            return;
        }
        if (showProgramStudi && !formData.program_studi_id) {
            toast.error("Program studi wajib dipilih untuk peran ini");
            return;
        }
        if (showDosen && !formData.dosen_id) {
            toast.error("Dosen wajib dipilih untuk peran Dosen");
            return;
        }
        if (!editingUser && !formData.password) {
            toast.error("Password wajib diisi untuk pengguna baru");
            return;
        }

        setIsSaving(true);
        try {
            const payload: Record<string, number | string | null> = {
                nama_user: resolvedNamaUser,
                email: formData.email,
                role_id: Number.parseInt(formData.role_id, 10),
                jurusan_id: formData.jurusan_id
                    ? Number.parseInt(formData.jurusan_id, 10)
                    : null,
                program_studi_id: formData.program_studi_id
                    ? Number.parseInt(formData.program_studi_id, 10)
                    : null,
                dosen_id: formData.dosen_id
                    ? Number.parseInt(formData.dosen_id, 10)
                    : null,
            };

            if (formData.password) {
                payload.password = formData.password;
            }

            if (editingUser) {
                await api.put(`/users/${editingUser.id}`, payload);
                toast.warning("Pengguna diperbarui", {
                    description: "Perubahan berhasil disimpan.",
                });
            } else {
                await api.post("/users", payload);
                toast.success("Pengguna ditambahkan", {
                    description: "Pengguna baru berhasil dibuat.",
                });
            }

            closeModal();
            fetchUsers();
        } catch (err) {
            toast.error("Gagal menyimpan", {
                description: getUserFriendlyError(err, { context: "save" }),
            });
        } finally {
            setIsSaving(false);
        }
    };

    const handleDelete = async (targetUser: ApiUser) => {
        if (!canManageUsers) return;
        setDeleteTarget(targetUser);
    };

    const confirmDelete = async () => {
        if (!deleteTarget) return;

        setIsDeleting(true);
        try {
            await api.delete(`/users/${deleteTarget.id}`);
            toast.error("Pengguna dihapus", {
                description: "Pengguna berhasil dihapus dari sistem.",
            });
            setDeleteTarget(null);
            fetchUsers();
        } catch (err) {
            toast.error("Gagal menghapus pengguna", {
                description: getUserFriendlyError(err, { context: "delete" }),
            });
        } finally {
            setIsDeleting(false);
        }
    };

    const confirmDeleteAll = async () => {
        setIsDeletingAll(true);
        try {
            const response = await api.delete("/users");
            const deletedCount = Number(response.data?.deleted_count ?? 0);
            toast.error("Semua pengguna dihapus", {
                description: deletedCount > 0
                    ? `${deletedCount} pengguna berhasil dihapus dari sistem. Akun Anda tetap dipertahankan.`
                    : "Tidak ada pengguna yang dihapus pada scope ini.",
            });
            setDeleteAllOpen(false);
            fetchUsers();
        } catch (err) {
            toast.error("Gagal menghapus semua pengguna", {
                description: getUserFriendlyError(err, { context: "delete" }),
            });
        } finally {
            setIsDeletingAll(false);
        }
    };

    const filteredUsers = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();
        if (!query) {
            return users;
        }

        return users.filter((item) => {
            const searchable = [
                item.nama_user,
                item.email,
                item.role?.role,
                item.jurusan?.nama_jurusan,
                item.program_studi?.nama_prodi,
            ]
                .filter(Boolean)
                .join(" ")
                .toLowerCase();

            return searchable.includes(query);
        });
    }, [searchQuery, users]);

    const getDisplayName = (item: ApiUser) => {
        const explicitName = item.nama_user?.trim();
        if (explicitName) {
            return explicitName;
        }

        const emailPrefix = item.email?.split("@")[0]?.trim();
        return emailPrefix || "Belum diisi";
    };

    const totalPages = Math.max(1, Math.ceil(filteredUsers.length / PAGE_SIZE));
    const paginatedUsers = filteredUsers.slice(
        (page - 1) * PAGE_SIZE,
        page * PAGE_SIZE,
    );
    const visibleStart =
        filteredUsers.length === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
    const visibleEnd =
        filteredUsers.length === 0
            ? 0
            : Math.min(page * PAGE_SIZE, filteredUsers.length);

    useEffect(() => {
        setPage(1);
    }, [searchQuery]);

    useEffect(() => {
        if (page > totalPages) {
            setPage(totalPages);
        }
    }, [page, totalPages]);

    if (!canManageUsers) {
        return (
            <Alert className="bg-amber-50 border-amber-200">
                <AlertTitle className="text-amber-800">
                    Akses dibatasi
                </AlertTitle>
                <AlertDescription className="text-amber-700">
                    Halaman data pengguna hanya dapat diakses oleh Super Admin
                    atau Admin Fakultas.
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="space-y-2">
            <motion.div
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                className="flex flex-col gap-1.5 mb-2"
            >
                <div>
                    <h1 className="text-[2rem] font-bold leading-tight text-foreground">
                        Manajemen Pengguna
                    </h1>
                    <p className="text-sm text-muted-foreground mt-0.5">
                        Kelola pengguna sistem penjadwalan
                    </p>
                </div>
            </motion.div>

            <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 }}
                className="flex flex-col sm:flex-row items-center justify-between gap-2.5 my-3"
            >
                <div className="flex items-center gap-3 w-full sm:w-auto flex-1">
                    <div className="relative w-full sm:w-72 md:w-80">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="text"
                            placeholder="Cari nama, email, atau peran..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="table-search-input pl-9 w-full"
                        />
                    </div>
                </div>

                <div className="flex items-center gap-2 w-full sm:w-auto">
                    <Button
                        onClick={() => setDeleteAllOpen(true)}
                        variant="destructive"
                        className="gap-2 w-full sm:w-auto rounded-xl px-4 py-2 shadow-md transition-all duration-300 hover:brightness-110 active:scale-95"
                    >
                        <Trash2 className="h-4 w-4" />
                        Delete All
                    </Button>
                    <Button
                        onClick={handleAdd}
                        className="gradient-btn gap-2 w-full sm:w-auto"
                    >
                        <Plus className="h-4 w-4" />
                        Tambah Pengguna
                    </Button>
                </div>
            </motion.div>

            <UsersTable
                users={paginatedUsers}
                isLoading={isLoading}
                searchQuery={searchQuery}
                visibleStart={visibleStart}
                visibleEnd={visibleEnd}
                totalCount={filteredUsers.length}
                page={page}
                totalPages={totalPages}
                onPageChange={setPage}
                onEdit={handleEdit}
                onDelete={handleDelete}
                getDisplayName={getDisplayName}
            />

            <UserFormModal
                open={modalOpen}
                editingUser={editingUser}
                formData={formData}
                setFormData={setFormData}
                roles={roles}
                jurusanList={jurusanList}
                programStudiList={programStudiList}
                dosenList={dosenList}
                showPassword={showPassword}
                setShowPassword={setShowPassword}
                showJurusan={showJurusan}
                showProgramStudi={showProgramStudi}
                showDosen={showDosen}
                isCreatingDosenUser={isCreatingDosenUser}
                isSuperAdmin={isSuperAdmin}
                isSaving={isSaving}
                onClose={closeModal}
                onSave={handleSave}
                onLoadProgramStudi={fetchProgramStudi}
                onProgramStudiLoadError={() => setProgramStudiList([])}
            />
            <ConfirmActionDialog
                open={Boolean(deleteTarget)}
                onOpenChange={(open) => {
                    if (!open && !isDeleting) {
                        setDeleteTarget(null);
                    }
                }}
                title="Hapus pengguna?"
                description={`Pengguna "${deleteTarget?.nama_user ?? ""}" akan dihapus dari sistem. Tindakan ini tidak dapat dikembalikan dari halaman ini.`}
                confirmLabel="Hapus"
                destructive
                loading={isDeleting}
                onConfirm={confirmDelete}
            />
            <ConfirmActionDialog
                open={deleteAllOpen}
                onOpenChange={(open) => {
                    if (!open && !isDeletingAll) {
                        setDeleteAllOpen(false);
                    }
                }}
                title="Delete all pengguna?"
                description="Semua data pengguna dalam scope role Anda akan dihapus, kecuali akun yang sedang Anda gunakan. Tindakan ini tidak dapat dikembalikan dari halaman ini."
                confirmLabel="Delete All"
                destructive
                loading={isDeletingAll}
                onConfirm={confirmDeleteAll}
            />
        </div>
    );
}
