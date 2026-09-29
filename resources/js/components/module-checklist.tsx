"use client"

import { usePage } from "@inertiajs/react"
import { Checkbox } from "@/components/ui/checkbox"
import type { PageProps } from "@/types"

interface ModuleChecklistProps {
    /** Selected module keys; null means "all modules" (the default product). */
    value: string[] | null
    /** Always called with a materialized, catalog-ordered list. */
    onChange: (modules: string[]) => void
    error?: string | null
}

/**
 * Module checkboxes with hard-dependency handling, driven by the shared
 * moduleCatalog / moduleDependencies props. Enabling a module pulls in its
 * requirements; disabling one drags every dependent module along — dependent
 * modules are never separated (mirrors the server-side validation).
 */
export function ModuleChecklist({ value, onChange, error }: ModuleChecklistProps) {
    const pageProps = usePage<PageProps>().props
    const moduleCatalog = (pageProps.moduleCatalog as { key: string; label: string }[] | undefined) ?? []
    const moduleDependencies = (pageProps.moduleDependencies as {
        requires?: Record<string, string[]>
        requires_any?: Record<string, string[]>
    } | undefined) ?? {}
    const requires = moduleDependencies.requires ?? {}
    const requiresAny = moduleDependencies.requires_any ?? {}

    const allKeys = moduleCatalog.map((entry) => entry.key)
    const selected = value ?? allKeys
    const moduleLabel = (key: string) => moduleCatalog.find((entry) => entry.key === key)?.label ?? key

    const toggleModule = (module: string, checked: boolean) => {
        const current = new Set(selected)

        if (checked) {
            const queue = [module]
            while (queue.length > 0) {
                const next = queue.shift() as string
                if (current.has(next)) continue
                current.add(next)
                queue.push(...(requires[next] ?? []))
            }
        } else {
            current.delete(module)
            let changed = true
            while (changed) {
                changed = false
                for (const enabled of [...current]) {
                    const missingRequired = (requires[enabled] ?? []).some((dep) => !current.has(dep))
                    const anyOf = requiresAny[enabled] ?? []
                    const missingAnyOf = anyOf.length > 0 && !anyOf.some((dep) => current.has(dep))
                    if (missingRequired || missingAnyOf) {
                        current.delete(enabled)
                        changed = true
                    }
                }
            }
        }

        // Keep catalog order for a stable payload.
        onChange(allKeys.filter((key) => current.has(key)))
    }

    const dependencyHint = (module: string): string | null => {
        const hard = requires[module] ?? []
        const anyOf = requiresAny[module] ?? []
        if (hard.length > 0) return `benötigt ${hard.map(moduleLabel).join(", ")}`
        if (anyOf.length > 0) return `benötigt ${anyOf.map(moduleLabel).join(" oder ")}`
        return null
    }

    // Any-of dependencies can't be auto-resolved (we can't pick for the
    // admin), so the checkbox is disabled until an option is enabled.
    const moduleDisabled = (module: string): boolean => {
        const anyOf = requiresAny[module] ?? []
        return anyOf.length > 0 && !anyOf.some((dep) => selected.includes(dep))
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {moduleCatalog.map((module) => (
                <label key={module.key} className={`flex items-start gap-3 ${moduleDisabled(module.key) ? "opacity-50" : ""}`}>
                    <Checkbox
                        className="mt-0.5"
                        checked={selected.includes(module.key)}
                        disabled={moduleDisabled(module.key)}
                        onCheckedChange={(checked) => toggleModule(module.key, checked === true)}
                    />
                    <span className="flex flex-col">
                        <span>{module.label}</span>
                        {dependencyHint(module.key) && (
                            <span className="text-xs text-muted-foreground">{dependencyHint(module.key)}</span>
                        )}
                    </span>
                </label>
            ))}
            {error && <p className="text-sm text-red-500 sm:col-span-2">{error}</p>}
        </div>
    )
}
