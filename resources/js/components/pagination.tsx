import { Link, router } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { translatePaginationLabel } from "@/utils/pagination"

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginationProps {
    links: PaginationLink[]
    variant?: "default" | "minimal"
    className?: string
}

export function Pagination({ links, variant = "default", className = "" }: PaginationProps) {
    if (!links || links.length <= 3) return null

    const handleClick = (url: string | null) => {
        if (url) {
            router.get(url)
        }
    }

    if (variant === "minimal") {
        return (
            <div className={`flex justify-center gap-2 ${className}`}>
                {links.map((link, index) => (
                    <Link
                        key={index}
                        href={link.url || "#"}
                        className={`px-3 py-2 rounded ${
                            link.active
                                ? "bg-blue-600 text-white"
                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                        } ${!link.url ? "opacity-50 cursor-not-allowed" : ""}`}
                        dangerouslySetInnerHTML={{ __html: translatePaginationLabel(link.label) }}
                    />
                ))}
            </div>
        )
    }

    return (
        <div className={`flex justify-center gap-2 ${className}`}>
            {links.map((link, index) => (
                <Button
                    key={index}
                    variant={link.active ? "default" : "outline"}
                    size="sm"
                    disabled={!link.url}
                    onClick={() => handleClick(link.url)}
                    dangerouslySetInnerHTML={{ __html: translatePaginationLabel(link.label) }}
                />
            ))}
        </div>
    )
}



