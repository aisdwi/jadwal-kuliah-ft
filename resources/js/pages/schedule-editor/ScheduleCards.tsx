import { GripVertical, Loader2, MapPin, Plus, Trash2, Users } from "lucide-react";
import { cn } from "@/lib/utils";
import type { Course, ScheduleEntry } from "@/pages/schedule-editor/types";

type DragSource = "unscheduled" | "calendar";

export function CourseCard({
  course,
  onDragStart,
  onDragEnd,
  canSchedule,
  isPending,
  isDragging,
}: {
  course: Course;
  onDragStart: (e: React.DragEvent, course: Course, source: DragSource) => void;
  onDragEnd: () => void;
  canSchedule: boolean;
  isPending: boolean;
  isDragging: boolean;
}) {
  return (
    <div
      draggable={canSchedule && !isPending}
      onDragStart={(e) => canSchedule && !isPending && onDragStart(e as unknown as React.DragEvent, course, "unscheduled")}
      onDragEnd={onDragEnd}
      className={cn(
        "group rounded-xl p-3 border backdrop-blur-md transition-all select-none",
        canSchedule && !isPending ? "cursor-grab active:cursor-grabbing hover:shadow-lg" : "cursor-default",
        isPending && "opacity-60 pointer-events-none",
        isDragging && "scale-[0.98] opacity-75 ring-2 ring-primary/30",
      )}
      style={{
        background: `hsl(${course.color} / 0.15)`,
        borderColor: `hsl(${course.color} / 0.3)`,
        boxShadow: `inset 0 0 15px hsl(${course.color} / 0.08)`,
      }}
    >
      <div className="flex items-start gap-2">
        <GripVertical className="h-4 w-4 mt-0.5 shrink-0 text-muted-foreground opacity-50 group-hover:opacity-100 transition-opacity" />
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <div
              className="h-2.5 w-2.5 rounded-full shrink-0"
              style={{ background: `hsl(${course.color})` }}
            />
            {course.code && (
              <span className="text-xs font-semibold text-muted-foreground">
                {course.code}
              </span>
            )}
            <span className="ml-auto text-[10px] px-1.5 py-0.5 rounded-md bg-white/15 text-muted-foreground font-medium backdrop-blur-sm">
              {course.sks} SKS
            </span>
          </div>
          <p
            className="text-sm font-semibold truncate"
            style={{ color: `hsl(${course.color})` }}
          >
            {course.name}
          </p>
          {course.classContext && (
            <p className="text-[11px] text-muted-foreground mt-0.5 truncate">
              {course.classContext}
            </p>
          )}
          <p className="text-xs text-muted-foreground mt-0.5 flex items-center gap-1">
            <Users className="h-3 w-3" />
            {course.dosen}
          </p>
        </div>
        {isPending && <Loader2 className="h-4 w-4 mt-0.5 shrink-0 animate-spin text-primary" />}
      </div>
    </div>
  );
}

export function SchedulePill({
  entry,
  onDragStart,
  onDragEnd,
  onRemove,
  canSchedule,
  isPending,
  isDragging,
}: {
  entry: ScheduleEntry;
  onDragStart: (e: React.DragEvent, course: Course, source: DragSource) => void;
  onDragEnd: () => void;
  onRemove: (id: string, course: Course) => void;
  canSchedule: boolean;
  isPending: boolean;
  isDragging: boolean;
}) {
  return (
    <div
      draggable={canSchedule && !isPending}
      onDragStart={(e) => canSchedule && !isPending && onDragStart(e as unknown as React.DragEvent, entry.course, "calendar")}
      onDragEnd={onDragEnd}
      className={cn(
        "relative group h-full rounded-lg p-2 border overflow-hidden transition-all backdrop-blur-md flex flex-col",
        canSchedule && !isPending ? "cursor-grab active:cursor-grabbing hover:shadow-lg" : "cursor-default",
        isPending && "opacity-60 pointer-events-none",
        isDragging && "scale-[0.98] opacity-75 ring-2 ring-primary/30",
      )}
      style={{
        background: `hsl(${entry.course.color} / 0.2)`,
        borderColor: `hsl(${entry.course.color} / 0.4)`,
        boxShadow: `
          inset 0 0 20px hsl(${entry.course.color} / 0.1),
          0 8px 32px hsl(${entry.course.color} / 0.15)
        `,
      }}
    >
      {canSchedule && (
        <button
          onClick={() => onRemove(entry.id, entry.course)}
          disabled={isPending}
          className="absolute top-1 right-1 h-5 w-5 rounded-md flex items-center justify-center bg-destructive/80 text-destructive-foreground opacity-0 group-hover:opacity-100 transition-opacity z-10"
        >
          <Trash2 className="h-3 w-3" />
        </button>
      )}
      {isPending && (
        <div className="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/40 backdrop-blur-[1px]">
          <Loader2 className="h-4 w-4 animate-spin text-primary" />
        </div>
      )}
      <div className="flex-1">
        <p
          className="text-[11px] font-bold truncate pr-5"
          style={{ color: `hsl(${entry.course.color})` }}
        >
          {entry.course.name}
        </p>
        {entry.course.classContext && (
          <p
            className="text-[9px] text-muted-foreground mt-0.5 whitespace-normal break-words leading-tight overflow-hidden"
            style={{
              display: "-webkit-box",
              WebkitLineClamp: 2,
              WebkitBoxOrient: "vertical",
            }}
          >
            {entry.course.classContext}
          </p>
        )}
        <p className="text-[9px] text-muted-foreground mt-1 flex items-center gap-0.5 truncate">
          <Users className="h-2.5 w-2.5" />
          {entry.course.dosen}
        </p>
      </div>

      <p className="text-[9px] text-muted-foreground flex items-center gap-0.5 mt-1">
        <MapPin className="h-2.5 w-2.5" />
        {entry.room}
      </p>
    </div>
  );
}

export function EmptySlotHint({
  canSchedule,
  isOver,
  isUnavailable = false,
}: {
  canSchedule: boolean;
  isOver: boolean;
  isUnavailable?: boolean;
}) {
  return (
    <div
      className={cn(
        "pointer-events-none absolute inset-2 flex flex-col items-center justify-center rounded-2xl border border-dashed transition-all duration-200",
        isUnavailable
          ? "border-slate-200 bg-slate-100/70 text-slate-400"
          : isOver
            ? "border-primary/40 bg-primary/10 text-primary"
            : "border-emerald-900/10 bg-white/[0.35] text-emerald-950/25",
        canSchedule && !isOver && !isUnavailable && "group-hover:border-primary/20 group-hover:bg-primary/5 group-hover:text-primary/[0.45]",
      )}
    >
      <div
        className={cn(
          "flex h-9 w-9 items-center justify-center rounded-full transition-all duration-200",
          isUnavailable ? "bg-slate-200/80" : isOver ? "bg-primary/[0.15]" : "bg-white/[0.55]",
        )}
      >
        <Plus className={cn("h-4 w-4", isUnavailable ? "opacity-35" : isOver ? "opacity-90" : "opacity-50", !canSchedule && "opacity-35")} />
      </div>
      {isUnavailable && (
        <p className="mt-2 text-[10px] font-medium uppercase tracking-wide text-slate-400">
          Tidak Ada Slot
        </p>
      )}
    </div>
  );
}
