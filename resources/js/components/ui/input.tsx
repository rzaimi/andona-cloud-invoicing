import * as React from "react"

import { cn } from "@/lib/utils"

function isZeroValue(value: unknown): boolean {
  if (typeof value === "number") return value === 0
  if (typeof value !== "string" || value.trim() === "") return false
  const numeric = Number(value.replace(",", "."))
  return !Number.isNaN(numeric) && numeric === 0
}

function stripLeadingZeros(raw: string): string {
  return raw.replace(/^(-?)0+(?=\d)/, "$1")
}

function Input({ className, type, value, onChange, onFocus, onBlur, ...props }: React.ComponentProps<"input">) {
  const isNumber = type === "number"
  const controlled = value !== undefined
  const [draft, setDraft] = React.useState<string | null>(null)

  const handleFocus = (event: React.FocusEvent<HTMLInputElement>) => {
    if (isNumber && (event.currentTarget.value === "" || event.currentTarget.valueAsNumber === 0)) {
      if (controlled) {
        setDraft("")
      } else {
        event.currentTarget.value = ""
      }
    }
    onFocus?.(event)
  }

  const handleBlur = (event: React.FocusEvent<HTMLInputElement>) => {
    setDraft(null)
    onBlur?.(event)
  }

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (isNumber) {
      const normalized = stripLeadingZeros(event.target.value)
      if (normalized !== event.target.value) {
        event.target.value = normalized
      }
      if (controlled) {
        setDraft(event.target.value)
      }
    }
    onChange?.(event)
  }

  const displayValue =
    !isNumber || !controlled
      ? value
      : draft !== null
        ? draft
        : isZeroValue(value)
          ? ""
          : value

  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm",
        "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]",
        "aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
        className
      )}
      {...props}
      value={displayValue}
      onChange={handleChange}
      onFocus={handleFocus}
      onBlur={handleBlur}
    />
  )
}

export { Input }
