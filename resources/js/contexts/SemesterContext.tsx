import React, { createContext, useContext, useEffect, useState } from 'react';

type SemesterTipe = 'ganjil' | 'genap';

interface SemesterContextType {
    semesterTipe: SemesterTipe;
    setSemesterTipe: (tipe: SemesterTipe) => void;
}

const SemesterContext = createContext<SemesterContextType | undefined>(undefined);

export function SemesterProvider({ children }: { children: React.ReactNode }) {
    const [semesterTipe, setSemesterTipeState] = useState<SemesterTipe>(() => {
        const stored = localStorage.getItem('semester_tipe');
        return (stored === 'ganjil' || stored === 'genap') ? stored : 'ganjil';
    });

    const setSemesterTipe = (tipe: SemesterTipe) => {
        setSemesterTipeState(tipe);
        localStorage.setItem('semester_tipe', tipe);
    };

    return (
        <SemesterContext.Provider value={{ semesterTipe, setSemesterTipe }}>
            {children}
        </SemesterContext.Provider>
    );
}

export function useSemester() {
    const context = useContext(SemesterContext);
    if (context === undefined) {
        throw new Error('useSemester must be used within a SemesterProvider');
    }
    return context;
}
