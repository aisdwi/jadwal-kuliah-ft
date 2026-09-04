import React, { useState, useCallback, useEffect, useMemo } from "react";
import { motion } from "framer-motion";
import { Clock, BookOpen, Loader2, RefreshCw, CopyCheck } from "lucide-react";
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
import api from "@/lib/api";
import { cachedApiGet, invalidateApiCache } from "@/lib/api-cache";
import { getUserFriendlyError } from "@/lib/error-messages";
import { toast } from "sonner";
import { useAuth } from "@/contexts/AuthContext";
import { useSemester } from "@/contexts/SemesterContext";
import { useJurusanOptions } from "@/hooks/useJurusanOptions";
import { useProgramStudiOptions } from "@/hooks/useProgramStudiOptions";
import { CourseCard, EmptySlotHint, SchedulePill } from "@/pages/schedule-editor/ScheduleCards";
import type { Course, ScheduleEntry } from "@/pages/schedule-editor/types";

type ScheduleEditorSnapshot = {
  schedule: ScheduleEntry[];
  unscheduled: Course[];
  days: string[];
  timeSlots: string[];
  rooms: RoomOption[];
  slotsData: any[];
  daysData: any[];
  waktuData: any[];
  roomsData: any[];
  selectedRoom: string;
};

const scheduleEditorSnapshots = new Map<string, ScheduleEditorSnapshot>();
const FILTER_ALL = "__all__";

type RoomOption = {
  id: number;
  name: string;
  jurusanIds: string[];
};

function buildScheduleEditorSnapshotKey(semesterTipe: string, user: ReturnType<typeof useAuth>["user"]) {
  return [
    semesterTipe,
    user?.id ?? "guest",
    user?.role_id ?? "role",
    user?.jurusan_id ?? "all",
    user?.program_studi_id ?? "all",
  ].join(":");
}

const COLORS = [
  "250 70% 60%",
  "180 60% 45%",
  "280 60% 55%",
  "200 70% 50%",
  "340 65% 55%",
  "38 92% 50%",
  "160 60% 40%",
];

function insertAtIndex<T>(items: T[], item: T, index: number) {
  if (index < 0 || index > items.length) {
    return [...items, item];
  }

  return [...items.slice(0, index), item, ...items.slice(index)];
}

function replaceScheduleEntry(entries: ScheduleEntry[], nextEntry: ScheduleEntry) {
  const index = entries.findIndex((entry) => entry.course.id === nextEntry.course.id);
  if (index === -1) {
    return [...entries, nextEntry];
  }

  const nextEntries = [...entries];
  nextEntries[index] = nextEntry;
  return nextEntries;
}

export default function ScheduleEditor() {
  const { canSchedule, user, isJurusanRestricted, defaultsToOwnJurusan } = useAuth();
  const { semesterTipe } = useSemester();
  const jurusanOptions = useJurusanOptions(isJurusanRestricted, user?.jurusan_id);
  const { prodiOptions, fetchProdiOptions } = useProgramStudiOptions();
  const snapshotKey = buildScheduleEditorSnapshotKey(semesterTipe, user);
  const initialSnapshot = scheduleEditorSnapshots.get(snapshotKey);
  const [selectedJurusanFilter, setSelectedJurusanFilter] = useState(
    defaultsToOwnJurusan && user?.jurusan_id ? String(user.jurusan_id) : FILTER_ALL
  );
  const [selectedProdiFilter, setSelectedProdiFilter] = useState(FILTER_ALL);
  const [schedule, setSchedule] = useState<ScheduleEntry[]>(initialSnapshot?.schedule ?? []);
  const [unscheduled, setUnscheduled] = useState<Course[]>(initialSnapshot?.unscheduled ?? []);

  // API Reference Data
  const [DAYS, setDAYS] = useState<string[]>(initialSnapshot?.days ?? []);
  const [TIME_SLOTS, setTIME_SLOTS] = useState<string[]>(initialSnapshot?.timeSlots ?? []);
  const [ROOMS, setROOMS] = useState<RoomOption[]>(initialSnapshot?.rooms ?? []);
  const [slotsData, setSlotsData] = useState<any[]>(initialSnapshot?.slotsData ?? []);
  const [daysData, setDaysData] = useState<any[]>(initialSnapshot?.daysData ?? []);
  const [waktuData, setWaktuData] = useState<any[]>(initialSnapshot?.waktuData ?? []);
  const [roomsData, setRoomsData] = useState<any[]>(initialSnapshot?.roomsData ?? []);

  const [isLoading, setIsLoading] = useState(!initialSnapshot);
  const [dragOverCell, setDragOverCell] = useState<string | null>(null);
  const [draggingCourseId, setDraggingCourseId] = useState<number | null>(null);
  const [pendingCourseIds, setPendingCourseIds] = useState<number[]>([]);
  const [selectedRoom, setSelectedRoom] = useState<string>(initialSnapshot?.selectedRoom ?? "");
  const selectedRoomRef = React.useRef(selectedRoom);
  const pageRef = React.useRef<HTMLDivElement | null>(null);
  const timetableRef = React.useRef<HTMLDivElement | null>(null);
  const [unscheduledSearch, setUnscheduledSearch] = useState("");

  useEffect(() => {
    selectedRoomRef.current = selectedRoom;
  }, [selectedRoom]);

  const handleSelectRoom = useCallback((roomId: string) => {
    setSelectedRoom(roomId);
    setDragOverCell(null);

    window.requestAnimationFrame(() => {
      timetableRef.current?.scrollTo({ top: 0, left: 0 });
      pageRef.current?.closest("main")?.scrollTo({ top: 0, left: 0 });
    });
  }, []);

  useEffect(() => {
    if (selectedJurusanFilter !== FILTER_ALL) {
      fetchProdiOptions(selectedJurusanFilter);
    } else {
      fetchProdiOptions(null, true);
    }
  }, [fetchProdiOptions, selectedJurusanFilter]);

  const normalizeKey = (value: string) => value.trim().toLowerCase().replace(/\s+/g, " ");
  const normalizeTime = (value: string) => normalizeKey(value).replace(/\s*-\s*/g, "-");
  const timeStartMinute = (value: string) => {
    const match = value.match(/(\d{1,2})[.:](\d{2})/);
    if (!match) return Number.MAX_SAFE_INTEGER;

    return Number(match[1]) * 60 + Number(match[2]);
  };
  const compareTimeLabels = (left: string, right: string) => {
    const byStart = timeStartMinute(left) - timeStartMinute(right);
    return byStart !== 0 ? byStart : left.localeCompare(right, "id-ID", { numeric: true });
  };
  const cellKey = useCallback((day: string, slot: string) => `${normalizeKey(day)}__${normalizeTime(slot)}`, []);
  const uniqueBy = (values: string[], normalizer: (value: string) => string) => {
    const seen = new Set<string>();
    return values.filter((value) => {
      if (!value) return false;
      const normalized = normalizer(value);
      if (seen.has(normalized)) return false;
      seen.add(normalized);
      return true;
    });
  };
  const slotBelongsToSelectedJurusan = useCallback((slot: any) => {
    if (selectedJurusanFilter === FILTER_ALL) {
      return true;
    }

    return (slot?.jurusan_ids ?? slot?.jurusans?.map((jurusan: any) => jurusan.id) ?? [])
      .map(String)
      .includes(selectedJurusanFilter);
  }, [selectedJurusanFilter]);

  const slotIndex = useMemo(() => {
    const index = new Map<string, number>();
    for (const s of slotsData) {
      if (!slotBelongsToSelectedJurusan(s)) {
        continue;
      }
      const dayName = s?.hari?.nama_hari;
      const timeLabel = s?.waktu?.pukul;
      if (!dayName || !timeLabel || !s?.id) continue;
      const key = `${normalizeKey(dayName)}__${normalizeTime(timeLabel)}`;
      if (!index.has(key)) {
        index.set(key, Number(s.id));
      }
    }
    return index;
  }, [slotBelongsToSelectedJurusan, slotsData]);
  const timeSlotMeta = useMemo(() => {
    const meta = new Map<string, { sks: number | null }>();
    for (const waktu of waktuData) {
      const label = waktu?.pukul;
      if (!label) continue;
      meta.set(normalizeTime(label), {
        sks: typeof waktu?.sks === "number" ? waktu.sks : Number(waktu?.sks ?? 0) || null,
      });
    }
    return meta;
  }, [waktuData]);
  const courseById = useMemo(() => {
    const map = new Map<number, Course>();
    unscheduled.forEach((course) => map.set(course.id, course));
    schedule.forEach((entry) => map.set(entry.course.id, entry.course));
    return map;
  }, [schedule, unscheduled]);
  const draggingCourse = draggingCourseId !== null ? courseById.get(draggingCourseId) ?? null : null;
  const isSlotCompatibleWithCourse = useCallback((course: Course | null | undefined, slot: string) => {
    if (!course) {
      return true;
    }

    const slotSks = timeSlotMeta.get(normalizeTime(slot))?.sks ?? null;
    const courseSks = Number(course.sks ?? 0) || null;

    return !slotSks || !courseSks || slotSks === courseSks;
  }, [timeSlotMeta]);

  const fetchData = useCallback(async (force = false) => {
    const cachedSnapshot = !force ? scheduleEditorSnapshots.get(snapshotKey) : undefined;
    if (cachedSnapshot) {
      setSchedule(cachedSnapshot.schedule);
      setUnscheduled(cachedSnapshot.unscheduled);
      setDAYS(cachedSnapshot.days);
      setTIME_SLOTS(cachedSnapshot.timeSlots);
      setROOMS(cachedSnapshot.rooms);
      setSlotsData(cachedSnapshot.slotsData);
      setDaysData(cachedSnapshot.daysData);
      setWaktuData(cachedSnapshot.waktuData);
      setRoomsData(cachedSnapshot.roomsData);
      setSelectedRoom(cachedSnapshot.selectedRoom);
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    try {
      const [hariRes, waktuRes, ruanganRes, slotRes, scheduledRes, unscheduledRes] = await Promise.all([
        cachedApiGet<any[]>('/referensi/hari', undefined, { force }),
        cachedApiGet<any[]>('/referensi/waktu', undefined, { force }),
        cachedApiGet<any>('/ruangan?per_page=all', undefined, { force }),
        cachedApiGet<any[]>('/referensi/slot', undefined, { force }),
        cachedApiGet<any>(`/kelas-kuliah?is_scheduled=true&per_page=all&semester_tipe=${semesterTipe}`, undefined, { force }),
        cachedApiGet<any>(`/kelas-kuliah?is_scheduled=false&per_page=all&semester_tipe=${semesterTipe}`, undefined, { force })
      ]);

      const hariList = hariRes.data;
      const waktuList = waktuRes.data;
      const r_Data = ruanganRes.data.data || ruanganRes.data;

      setDaysData(hariList);
      setWaktuData(waktuList);
      setSlotsData(slotRes.data);
      setRoomsData(r_Data);

      const mappedDays = uniqueBy(hariList.map((h: any) => h.nama_hari), normalizeKey);
      const mappedTimes = uniqueBy(
        slotRes.data
          .map((slot: any) => slot.waktu?.pukul)
          .filter((pukul: unknown): pukul is string => typeof pukul === "string" && pukul.trim() !== ""),
        normalizeTime
      ).sort(compareTimeLabels);
      const mappedRooms = r_Data.map((r: any) => ({
        id: r.id,
        name: r.ruangan,
        jurusanIds: (r.jurusan_ids ?? r.jurusans?.map((jurusan: any) => jurusan.id) ?? []).map(String),
      }));
      const currentSelectedRoom = selectedRoomRef.current;
      const selectedRoomStillExists = mappedRooms.some((room: { id: number }) => String(room.id) === currentSelectedRoom);
      const nextSelectedRoom = selectedRoomStillExists
        ? currentSelectedRoom
        : (mappedRooms.length > 0 ? String(mappedRooms[0].id) : "");

      setDAYS(mappedDays);
      setTIME_SLOTS(mappedTimes);
      setROOMS(mappedRooms);
      if (nextSelectedRoom !== currentSelectedRoom) {
        setSelectedRoom(nextSelectedRoom);
      }

      const buildClassContext = (item: any) => {
        const className = item.kelas?.nama_kelas;
        const prodiName = item.kelas?.program_studi?.nama_prodi
          ?? item.matakuliah?.program_studi?.nama_prodi;
        const semester = item.kelas?.semester ?? item.matakuliah?.semester;
        const parts = [
          className,
          prodiName,
          semester ? `Semester ${semester}` : null,
        ].filter(Boolean);

        return parts.length > 0 ? parts.join(" - ") : undefined;
      };

      // Helper to map DB record to front-end Course object
      const mapCourse = (item: any): Course => ({
        id: item.id,
        name: item.matakuliah?.nama_mk || "Tanpa Nama",
        code: item.matakuliah?.kode_mk,
        sks: item.matakuliah?.sks || 0,
        dosen: item.dosen?.nama_lengkap || "Belum ada dosen",
        classContext: buildClassContext(item),
        color: COLORS[(item.id || 0) % COLORS.length],
        jurusanId: item.matakuliah?.program_studi?.jurusan?.id ?? item.matakuliah?.jurusan_id ?? null,
        programStudiId: item.matakuliah?.program_studi?.id ?? item.matakuliah?.program_studi_id ?? null,
      });

      // Parse Unscheduled
      const nextUnscheduled = (unscheduledRes.data.data || unscheduledRes.data).map(mapCourse);
      setUnscheduled(nextUnscheduled);

      // Parse Scheduled
      const allScheduled = scheduledRes.data.data || scheduledRes.data;
      const nextSchedule = allScheduled.map((item: any) => {
        const c = mapCourse(item);
        return {
          id: String(c.id),
          course: c,
          day: item.slot?.hari?.nama_hari || "",
          timeSlot: item.slot?.waktu?.pukul || "",
          roomId: Number(item.ruangan_id ?? item.ruangan?.id ?? 0) || null,
          room: item.ruangan?.ruangan || "",
        };
      });
      setSchedule(nextSchedule);
      scheduleEditorSnapshots.set(snapshotKey, {
        schedule: nextSchedule,
        unscheduled: nextUnscheduled,
        days: mappedDays,
        timeSlots: mappedTimes,
        rooms: mappedRooms,
        slotsData: slotRes.data,
        daysData: hariList,
        waktuData: waktuList,
        roomsData: r_Data,
        selectedRoom: nextSelectedRoom,
      });

    } catch (err) {
      toast.error("Gagal memuat data jadwal", {
        description: getUserFriendlyError(err, { context: "load" }),
      });
    } finally {
      setIsLoading(false);
    }
  }, [semesterTipe, snapshotKey]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const invalidateScheduleCaches = useCallback(() => {
    scheduleEditorSnapshots.delete(snapshotKey);
    invalidateApiCache(["/kelas-kuliah", "/dashboard"]);
  }, [snapshotKey]);

  useEffect(() => {
    const snapshot = scheduleEditorSnapshots.get(snapshotKey);
    if (snapshot) {
      snapshot.selectedRoom = selectedRoom;
    }
  }, [selectedRoom, snapshotKey]);

  const filteredRooms = useMemo(() => {
    if (selectedJurusanFilter === FILTER_ALL) {
      return ROOMS;
    }

    return ROOMS.filter((room) => {
      return room.jurusanIds.includes(selectedJurusanFilter);
    });
  }, [ROOMS, selectedJurusanFilter]);

  useEffect(() => {
    const selectedRoomStillVisible = filteredRooms.some((room) => String(room.id) === selectedRoom);
    const fallbackRoom = filteredRooms.length > 0 ? String(filteredRooms[0].id) : "";

    if (!selectedRoomStillVisible && selectedRoom !== fallbackRoom) {
      handleSelectRoom(fallbackRoom);
    }
  }, [filteredRooms, handleSelectRoom, selectedRoom]);

  const selectedRoomId = useMemo(() => Number.parseInt(selectedRoom, 10) || 0, [selectedRoom]);
  const selectedRoomName = useMemo(
    () => ROOMS.find((room) => room.id === selectedRoomId)?.name || "",
    [ROOMS, selectedRoomId]
  );
  const currentRoomEntries = useMemo(
    () => schedule.filter((entry) => {
      if (entry.roomId !== selectedRoomId) return false;
      if (selectedJurusanFilter !== FILTER_ALL && String(entry.course.jurusanId ?? "") !== selectedJurusanFilter) {
        return false;
      }
      if (selectedProdiFilter !== FILTER_ALL && String(entry.course.programStudiId ?? "") !== selectedProdiFilter) {
        return false;
      }
      return true;
    }),
    [schedule, selectedJurusanFilter, selectedProdiFilter, selectedRoomId]
  );
  const entryMap = useMemo(() => {
    const map = new Map<string, ScheduleEntry>();
    currentRoomEntries.forEach((entry) => {
      map.set(cellKey(entry.day, entry.timeSlot), entry);
    });
    return map;
  }, [cellKey, currentRoomEntries]);

  const getEntry = useCallback(
    (day: string, slot: string) => entryMap.get(cellKey(day, slot)),
    [cellKey, entryMap]
  );

  const getSlotId = (dayStr: string, timeStr: string) => {
    const byNameKey = `${normalizeKey(dayStr)}__${normalizeTime(timeStr)}`;
    const slotIdByName = slotIndex.get(byNameKey);
    if (slotIdByName) return slotIdByName;

    // Fallback ke pendekatan lama berbasis id relation
    const hari = daysData.find((d: any) => normalizeKey(d.nama_hari || "") === normalizeKey(dayStr));
    const waktu = waktuData.find((w: any) => normalizeTime(w.pukul || "") === normalizeTime(timeStr));
    if (!hari || !waktu) {
      console.warn("Slot lookup failed:", { dayStr, timeStr, foundHari: !!hari, foundWaktu: !!waktu });
      return null;
    }
    const slot = slotsData.find((s: any) => {
      if (!slotBelongsToSelectedJurusan(s)) return false;
      const sHariId = s.hari_id ?? s.hari?.id;
      const sWaktuId = s.waktu_id ?? s.waktu?.id;
      return Number(sHariId) === Number(hari.id) && Number(sWaktuId) === Number(waktu.id);
    });
    return slot ? Number(slot.id) : null;
  };

  const handleDragStart = useCallback(
    (e: React.DragEvent, course: Course, source: "unscheduled" | "calendar") => {
      setDraggingCourseId(course.id);
      e.dataTransfer.setData(
        "application/json",
        JSON.stringify({ type: source, course })
      );
      e.dataTransfer.effectAllowed = source === "unscheduled" ? "copy" : "move";
    },
    []
  );

  const handleDragEnd = useCallback(() => {
    setDraggingCourseId(null);
    setDragOverCell(null);
  }, []);

  const handleDrop = async (e: React.DragEvent, day: string, slot: string) => {
    e.preventDefault();
    setDragOverCell(null);

    const payload = e.dataTransfer.getData("application/json");
    if (!payload) return;
    let droppedCourseId: number | null = null;
    let draggedCourse: Course | null = null;
    let draggedFromUnscheduled = false;
    let previousEntry: ScheduleEntry | undefined;
    let previousUnscheduledIndex = -1;

    try {
      const parsed = JSON.parse(payload);
      const existing = entryMap.get(cellKey(day, slot));
      if (existing) return;

      const course = parsed.course as Course;
      draggedCourse = course;
      draggedFromUnscheduled = parsed.type === "unscheduled";
      droppedCourseId = course.id;
      if (pendingCourseIds.includes(course.id)) {
        return;
      }
      if (!isSlotCompatibleWithCourse(course, slot)) {
        const slotSks = timeSlotMeta.get(normalizeTime(slot))?.sks;
        toast.error("SKS tidak sesuai", {
          description: `Mata kuliah ${course.sks} SKS tidak dapat ditempatkan pada slot ${slotSks ?? "-"} SKS.`,
        });
        return;
      }
      previousEntry = schedule.find((entry) => entry.course.id === course.id);
      previousUnscheduledIndex = unscheduled.findIndex((item) => item.id === course.id);
      const targetRoomId = selectedRoomId;
      const targetRoomName = selectedRoomName;
      const targetSlotId = getSlotId(day, slot);

      if (!targetRoomId || !targetSlotId) {
        toast.error("Jadwal/Ruangan tidak valid", {
          description: `Tidak menemukan referensi slot untuk ${day} ${slot} di ruangan ${targetRoomName || "-"}.
Pastikan data Hari/Waktu/Slot sinkron dan ruangan target sudah dipilih.`,
        });
        return;
      }

      setPendingCourseIds((prev) => prev.includes(course.id) ? prev : [...prev, course.id]);
      const nextEntry: ScheduleEntry = {
        id: String(course.id),
        course,
        day,
        timeSlot: slot,
        roomId: targetRoomId,
        room: targetRoomName,
      };

      // Optimistic UI updates
      if (draggedFromUnscheduled) {
        setSchedule((prev) => replaceScheduleEntry(prev, nextEntry));
        setUnscheduled((prev) => prev.filter((c) => c.id !== course.id));
      } else if (parsed.type === "calendar") {
        setSchedule((prev) => replaceScheduleEntry(prev, nextEntry));
      }

      // API Request
      await api.put(`/kelas-kuliah/${course.id}/jadwal`, {
        ruangan_id: targetRoomId,
        slot_id: targetSlotId
      });
      invalidateScheduleCaches();
      toast.success("Jadwal diperbarui");

    } catch (err) {
      toast.error("Gagal mengupdate jadwal", {
        description: getUserFriendlyError(err, { context: "schedule" }),
      });
      if (droppedCourseId !== null) {
        if (draggedFromUnscheduled) {
          setSchedule((prev) => prev.filter((entry) => entry.course.id !== droppedCourseId));
          if (draggedCourse) {
            const restoredCourse = draggedCourse;
            setUnscheduled((prev) => {
              if (prev.some((item) => item.id === restoredCourse.id)) {
                return prev;
              }

              return insertAtIndex(prev, restoredCourse, previousUnscheduledIndex);
            });
          }
        } else if (previousEntry) {
          const restoredEntry = previousEntry;
          setSchedule((prev) => replaceScheduleEntry(prev, restoredEntry));
        }
      }
    } finally {
      if (droppedCourseId !== null) {
        setPendingCourseIds((prev) => prev.filter((id) => id !== droppedCourseId));
      }
      setDraggingCourseId(null);
    }
  };

  const handleRemove = async (id: string, course: Course) => {
    if (pendingCourseIds.includes(course.id)) {
      return;
    }
    const removedEntry = schedule.find((entry) => entry.id === id);
    const previousUnscheduledIndex = unscheduled.findIndex((item) => item.id === course.id);
    setPendingCourseIds((prev) => prev.includes(course.id) ? prev : [...prev, course.id]);
    // Optimistic un-schedule
    setSchedule((prev) => prev.filter((e) => e.id !== id));
    setUnscheduled((u) => [...u, course]);

    try {
      await api.put(`/kelas-kuliah/${course.id}/jadwal`, {
        ruangan_id: null,
        slot_id: null
      });
      invalidateScheduleCaches();
      toast.success("Kelas dihapus dari jadwal");
    } catch (err) {
      toast.error("Gagal menghapus jadwal", {
        description: getUserFriendlyError(err, { context: "schedule" }),
      });
      setUnscheduled((prev) => prev.filter((item) => item.id !== course.id));
      if (removedEntry) {
        setSchedule((prev) => replaceScheduleEntry(prev, removedEntry));
      } else if (previousUnscheduledIndex >= 0) {
        setUnscheduled((prev) => insertAtIndex(prev, course, previousUnscheduledIndex));
      }
    } finally {
      setPendingCourseIds((prev) => prev.filter((pendingId) => pendingId !== course.id));
    }
  };

  const handleChangeRoom = async (entryId: string, newRoomId: number, newRoomName: string) => {
    const entry = schedule.find(e => e.id === entryId);
    if (!entry) return;
    if (pendingCourseIds.includes(entry.course.id)) return;
    if (entry.room === newRoomName) return;
    setPendingCourseIds((prev) => prev.includes(entry.course.id) ? prev : [...prev, entry.course.id]);

    // Optimistic UI updates
    setSchedule((prev) => prev.map(e => e.id === entryId ? { ...e, roomId: newRoomId, room: newRoomName } : e));

    try {
      const targetSlotId = getSlotId(entry.day, entry.timeSlot);
      await api.put(`/kelas-kuliah/${entry.course.id}/jadwal`, {
        ruangan_id: newRoomId,
        slot_id: targetSlotId
      });
      invalidateScheduleCaches();
      toast.success("Ruangan diperbarui");
    } catch (err) {
      toast.error("Gagal mengupdate ruangan", {
        description: getUserFriendlyError(err, { context: "schedule" }),
      });
      fetchData(); // Rollback on fail
    } finally {
      setPendingCourseIds((prev) => prev.filter((pendingId) => pendingId !== entry.course.id));
    }
  };

  const gridTemplateColumns = useMemo(
    () => `108px repeat(${DAYS.length}, minmax(0, 1fr))`,
    [DAYS.length]
  );

  const visibleUnscheduled = unscheduled.filter((c) => {
    if (selectedJurusanFilter !== FILTER_ALL && String(c.jurusanId ?? "") !== selectedJurusanFilter) {
      return false;
    }
    if (selectedProdiFilter !== FILTER_ALL && String(c.programStudiId ?? "") !== selectedProdiFilter) {
      return false;
    }
    const q = unscheduledSearch.trim().toLowerCase();
    if (!q) return true;
    const text = `${c.code || ""} ${c.name || ""} ${c.dosen || ""} ${c.sks || ""}`.toLowerCase();
    return text.includes(q);
  });

  if (isLoading) {
    return (
      <div className="flex h-[calc(100vh-150px)] items-center justify-center">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="text-muted-foreground animate-pulse">Menyiapkan editor jadwal...</p>
        </div>
      </div>
    );
  }

  return (
    <div ref={pageRef} className="flex h-[calc(100vh-112px)] max-w-full flex-col gap-4 overflow-hidden">
      {/* Page header */}
      <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} className="flex shrink-0 justify-between items-center pr-2 flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Editor Jadwal</h1>
          <p className="text-sm text-muted-foreground mt-1">
            {canSchedule 
              ? "Tarik kelas ke slot jadwal yang tersedia. Perubahan akan tersimpan otomatis."
              : "Pilih ruangan untuk melihat jadwal perkuliahan."}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {!isJurusanRestricted && (
            <>
              <Select
                value={selectedJurusanFilter}
                onValueChange={(value) => {
                  setSelectedJurusanFilter(value);
                  setSelectedProdiFilter(FILTER_ALL);
                }}
              >
                <SelectTrigger className="form-field-select-trigger w-52 bg-white">
                  <SelectValue placeholder="Semua Jurusan" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content bg-white">
                  <SelectItem value={FILTER_ALL}>Semua Jurusan</SelectItem>
                  {jurusanOptions.map((item) => (
                    <SelectItem key={item.value} value={item.value}>
                      {item.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select
                value={selectedProdiFilter}
                onValueChange={setSelectedProdiFilter}
              >
                <SelectTrigger className="form-field-select-trigger w-56 bg-white">
                  <SelectValue placeholder="Semua Program Studi" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content bg-white">
                  <SelectItem value={FILTER_ALL}>Semua Program Studi</SelectItem>
                  {prodiOptions.map((item) => (
                    <SelectItem key={item.value} value={item.value}>
                      {item.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </>
          )}
          {!canSchedule && filteredRooms.length > 0 && (
            <div className="flex items-center gap-2">
              <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">
                Pilih Ruangan:
              </label>
              <Select
                value={selectedRoom}
                onValueChange={handleSelectRoom}
              >
                <SelectTrigger className="form-field-select-trigger w-48 bg-white">
                  <SelectValue placeholder="Pilih ruangan" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content bg-white">
                  {filteredRooms.map((r) => (
                    <SelectItem key={r.id} value={String(r.id)}>
                      {r.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
          <Button variant="outline" size="sm" onClick={() => fetchData(true)} className="gap-2">
            <RefreshCw className="h-4 w-4" /> Muat Ulang Data
          </Button>
        </div>
      </motion.div>

      <div className="flex min-h-0 max-w-full flex-1 flex-col gap-3 overflow-hidden lg:flex-row">
        
        {/* Left panel — timetable grid */}
        <motion.div
          ref={timetableRef}
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.15 }}
          className="min-w-0 flex-1 overflow-y-auto overflow-x-hidden rounded-3xl border border-emerald-900/10 bg-white/[0.65] shadow-[0_20px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl custom-scrollbar lg:flex-[1.75]"
        >
          <div className="w-full">
            <div
              className="sticky top-0 z-20 grid border-b border-emerald-900/10 bg-white/90 backdrop-blur-md"
              style={{ gridTemplateColumns }}
            >
              <div className="sticky left-0 z-30 flex items-center justify-center gap-1 border-r border-emerald-900/10 bg-slate-50/95 px-2 py-4 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">
                <Clock className="h-4 w-4" /> Waktu
              </div>
              {DAYS.map((day) => (
                <div
                  key={normalizeKey(day)}
                  className="border-l border-emerald-900/10 px-2 py-4 text-center text-[13px] font-bold uppercase tracking-[0.14em] text-emerald-700/90"
                >
                  {day}
                </div>
              ))}
            </div>

            {TIME_SLOTS.map((slot) => (
              <div
                key={normalizeTime(slot)}
                className="grid border-b border-emerald-900/10 last:border-b-0"
                style={{ gridTemplateColumns }}
              >
                <div className="sticky left-0 z-10 flex flex-col items-center justify-center gap-1 border-r border-emerald-900/10 bg-slate-50/90 px-2 py-4 text-[13px] font-semibold whitespace-nowrap text-slate-600 shadow-[8px_0_18px_rgba(255,255,255,0.85)] md:text-[14px] xl:text-[15px]">
                  <span>{slot}</span>
                  <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                    {(timeSlotMeta.get(normalizeTime(slot))?.sks ?? "-")} SKS
                  </span>
                </div>
                {DAYS.map((day) => {
                  const entry = getEntry(day, slot);
                  const key = cellKey(day, slot);
                  const isOver = dragOverCell === key;
                  const hasActualSlot = slotIndex.has(key);
                  const canAcceptDraggedCourse = isSlotCompatibleWithCourse(draggingCourse, slot);

                  return (
                    <div
                      key={key}
                      onDragOver={(e) => {
                        e.preventDefault();
                        if (!entry && canSchedule && hasActualSlot && canAcceptDraggedCourse) setDragOverCell(key);
                      }}
                      onDragLeave={() => setDragOverCell(null)}
                      onDrop={(e) => canSchedule && hasActualSlot && canAcceptDraggedCourse && handleDrop(e, day, slot)}
                      className={cn(
                        "group relative min-h-[126px] border-l border-emerald-900/10 p-2.5 transition-all duration-200",
                        !hasActualSlot && "bg-slate-50/80",
                        isOver && !entry && canSchedule && canAcceptDraggedCourse && "bg-primary/[0.06]",
                        !entry && !isOver && canSchedule && hasActualSlot && "hover:bg-emerald-50/55"
                      )}
                    >
                      {entry ? (
                        <SchedulePill
                          key={`${selectedRoomId}:${entry.id}`}
                          entry={entry}
                          onDragStart={handleDragStart}
                          onDragEnd={handleDragEnd}
                          onRemove={handleRemove}
                          canSchedule={canSchedule}
                          isPending={pendingCourseIds.includes(entry.course.id)}
                          isDragging={draggingCourseId === entry.course.id}
                        />
                      ) : (
                        <EmptySlotHint canSchedule={canSchedule} isOver={isOver} isUnavailable={!hasActualSlot} />
                      )}
                    </div>
                  );
                })}
              </div>
            ))}
          </div>
        </motion.div>

        {/* Right panel — course list (only show if canSchedule is true) */}
        {canSchedule && (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.1 }}
            className="flex w-full shrink-0 flex-col overflow-hidden rounded-3xl border border-emerald-900/10 bg-white/75 shadow-[0_20px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl lg:w-[300px] xl:w-[320px]"
          >
            <div className="border-b border-white/20 p-4">
              <div className="flex items-center gap-2 mb-3">
                <BookOpen className="h-4 w-4 text-primary" />
                <h2 className="text-sm font-semibold text-foreground">
                  Belum Terjadwal
                </h2>
                <span className="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/20 text-primary">
                  {visibleUnscheduled.length}
                </span>
              </div>
              {/* Room selector */}
              <label className="text-[11px] text-muted-foreground font-medium uppercase tracking-wider">
                Ruangan Acuan Jadwal
              </label>
              <Select
                value={selectedRoom}
                onValueChange={handleSelectRoom}
              >
                <SelectTrigger className="form-field-select-trigger mt-1 w-full bg-white">
                  <SelectValue placeholder="Pilih ruangan" />
                </SelectTrigger>
                <SelectContent className="form-field-select-content bg-white">
                  {filteredRooms.map((r) => (
                    <SelectItem key={r.id} value={String(r.id)}>
                      {r.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Input
                value={unscheduledSearch}
                onChange={(e) => setUnscheduledSearch(e.target.value)}
                placeholder="Cari kode MK / mata kuliah / dosen..."
                className="mt-2 h-9 bg-white/60"
              />
            </div>

            {/* Course list */}
            <div className="relative flex-1 space-y-2 overflow-y-auto p-3 pb-6">
              {visibleUnscheduled.length === 0 ? (
                <div className="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                  <div className="w-16 h-16 rounded-full bg-green-500/10 flex items-center justify-center mb-3">
                    <CopyCheck className="h-8 w-8 text-green-500/80" />
                  </div>
                  <p className="text-sm font-medium text-muted-foreground">
                    {unscheduled.length === 0
                      ? "Semua kelas sudah memiliki jadwal."
                      : "Tidak ada kelas yang cocok dengan pencarian."}
                  </p>
                </div>
              ) : (
                visibleUnscheduled.map((c) => (
                  <CourseCard
                    key={c.id}
                    course={c}
                    onDragStart={handleDragStart}
                    onDragEnd={handleDragEnd}
                    canSchedule={canSchedule}
                    isPending={pendingCourseIds.includes(c.id)}
                    isDragging={draggingCourseId === c.id}
                  />
                ))
              )}
            </div>
          </motion.div>
        )}
      </div>
    </div>
  );
}
