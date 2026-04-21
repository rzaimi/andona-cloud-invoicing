"use client"

import { usePage } from "@inertiajs/react"
import { useEffect } from "react"
import { Toaster, toast } from "sonner"

type Flash = {
    success?: string | null
    error?: string | null
    upload_errors?: string[] | null
}

/**
 * Mounts the Sonner <Toaster /> once and converts Laravel flash messages
 * (shared via HandleInertiaRequests::share) into toasts on every navigation.
 *
 * Why this component owns the effect:
 * - Inertia page props update on navigation; a single layout-level effect
 *   picks up flashes without each page having to render its own banner.
 * - Existing pages that render flashes via <Alert> still work — they just
 *   duplicate until we migrate them one by one. The toast is additive.
 */
export function FlashToaster() {
    const { props } = usePage<{ flash?: Flash }>()
    const flash = props.flash ?? {}

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success)
        }
    }, [flash.success])

    useEffect(() => {
        if (flash.error) {
            toast.error(flash.error)
        }
    }, [flash.error])

    useEffect(() => {
        const errs = flash.upload_errors
        if (Array.isArray(errs) && errs.length > 0) {
            toast.error(errs.join("\n"))
        }
    }, [flash.upload_errors])

    return <Toaster richColors closeButton position="top-right" />
}
