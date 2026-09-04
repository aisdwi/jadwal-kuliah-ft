import { useState } from "react";
import { Check, ChevronsUpDown } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

interface SearchableSelectOption {
    value: string;
    label: string;
}

interface SearchableSelectProps {
    value?: string;
    disabled?: boolean;
    placeholder: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    options?: SearchableSelectOption[];
    onValueChange: (value: string) => void;
}

export function SearchableSelect({
    value,
    disabled,
    placeholder,
    searchPlaceholder = "Cari pilihan...",
    emptyMessage = "Pilihan tidak ditemukan.",
    options = [],
    onValueChange,
}: SearchableSelectProps) {
    const [open, setOpen] = useState(false);
    const selectedOption = options.find((option) => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    disabled={disabled}
                    className={cn(
                        "form-field-select-trigger w-full justify-between px-3 font-normal",
                        !selectedOption && "text-muted-foreground",
                    )}
                >
                    <span className="truncate">
                        {selectedOption?.label ?? placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="z-[120] w-[--radix-popover-trigger-width] min-w-[18rem] p-0"
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={`${option.label} ${option.value}`}
                                    onSelect={() => {
                                        onValueChange(option.value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            "mr-2 h-4 w-4",
                                            option.value === value ? "opacity-100" : "opacity-0",
                                        )}
                                    />
                                    <span className="truncate">{option.label}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
