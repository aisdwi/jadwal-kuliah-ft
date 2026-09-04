import type { ReactNode } from "react";

export function PlainCard({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <div className={`bg-white rounded-xl p-5 border border-slate-200 shadow-sm ${className}`}>
      {children}
    </div>
  );
}

export function PlainCardHeader({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={`mb-4 ${className}`}>{children}</div>;
}

export function PlainCardTitle({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <h3 className={`text-lg font-bold flex items-center gap-2 ${className}`}>{children}</h3>;
}

export function PlainCardContent({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={className}>{children}</div>;
}
