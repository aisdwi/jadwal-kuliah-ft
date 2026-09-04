export interface Course {
  id: number;
  name: string;
  code?: string;
  sks: number;
  dosen: string;
  classContext?: string;
  color: string;
  jurusanId?: number | null;
  programStudiId?: number | null;
}

export interface ScheduleEntry {
  id: string;
  course: Course;
  day: string;
  timeSlot: string;
  roomId: number | null;
  room: string;
}
