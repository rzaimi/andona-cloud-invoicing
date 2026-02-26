import { cn } from "@/lib/utils"
import { Badge } from "@/components/ui/badge"
import { CheckCircle2, Sprout, HardHat, Layers, Wrench, Package, ShoppingBag, Briefcase, X } from "lucide-react"

interface Step2Props {
    data: any
    setData: (key: string, value: any) => void
    errors: Record<string, string>
}

const INDUSTRY_TYPES = [
    {
        slug: "gartenbau",
        label: "Garten- und Außenanlagenbau",
        description: "Bepflanzung, Pflasterarbeiten, Rasen, Zäune, Bewässerung",
        icon: Sprout,
        color: "bg-green-50 border-green-200 hover:border-green-400",
        activeColor: "bg-green-100 border-green-500 ring-2 ring-green-500",
        iconColor: "text-green-600",
        badge: "bg-green-100 text-green-700",
        examples: ["Pflanzarbeiten", "Rasenpflege", "Pflaster & Terrasse", "Gehölzschnitt"],
    },
    {
        slug: "bauunternehmen",
        label: "Bauunternehmen",
        description: "Rohbau, Mauerwerk, Trockenbau, Estrich, Abriss",
        icon: HardHat,
        color: "bg-orange-50 border-orange-200 hover:border-orange-400",
        activeColor: "bg-orange-100 border-orange-500 ring-2 ring-orange-500",
        iconColor: "text-orange-600",
        badge: "bg-orange-100 text-orange-700",
        examples: ["Maurerarbeiten", "Betonarbeiten", "Trockenbau", "Schalungsarbeiten"],
    },
    {
        slug: "raumausstattung",
        label: "Raumausstattung & Fliesenarbeiten",
        description: "Fliesen, Parkett, Laminat, Vinyl, Teppich",
        icon: Layers,
        color: "bg-stone-50 border-stone-300 hover:border-stone-400",
        activeColor: "bg-stone-100 border-stone-500 ring-2 ring-stone-500",
        iconColor: "text-stone-600",
        badge: "bg-stone-100 text-stone-700",
        examples: ["Fliesenverleger", "Parkettverlegung", "Vinyl & Laminat", "Teppicharbeiten"],
    },
    {
        slug: "gebaudetechnik",
        label: "Gebäudetechnik",
        description: "Heizung, Sanitär, Elektro, Klima & Lüftung",
        icon: Wrench,
        color: "bg-blue-50 border-blue-200 hover:border-blue-400",
        activeColor: "bg-blue-100 border-blue-500 ring-2 ring-blue-500",
        iconColor: "text-blue-600",
        badge: "bg-blue-100 text-blue-700",
        examples: ["Heizungsinstallation", "Sanitärinstallation", "Elektrotechnik", "Klimatechnik"],
    },
    {
        slug: "logistik",
        label: "Logistik & Palettenhandel",
        description: "Europaletten, Industriepaletten, Transport & Lager",
        icon: Package,
        color: "bg-amber-50 border-amber-200 hover:border-amber-400",
        activeColor: "bg-amber-100 border-amber-500 ring-2 ring-amber-500",
        iconColor: "text-amber-600",
        badge: "bg-amber-100 text-amber-700",
        examples: ["EUR-Paletten A/B/C", "Einwegpaletten", "Palettenabholung", "Lagerhaltung"],
    },
    {
        slug: "handel",
        label: "Handelsunternehmen",
        description: "Warenhandel, Groß- & Einzelhandel, Distribution",
        icon: ShoppingBag,
        color: "bg-indigo-50 border-indigo-200 hover:border-indigo-400",
        activeColor: "bg-indigo-100 border-indigo-500 ring-2 ring-indigo-500",
        iconColor: "text-indigo-600",
        badge: "bg-indigo-100 text-indigo-700",
        examples: ["Waren Gruppe A–C", "Lieferkosten", "Beratung", "Expresslieferung"],
    },
    {
        slug: "dienstleistung",
        label: "Sonstige Dienstleistungen",
        description: "Beratung, IT, Schulungen, Verwaltung, Projektmanagement",
        icon: Briefcase,
        color: "bg-violet-50 border-violet-200 hover:border-violet-400",
        activeColor: "bg-violet-100 border-violet-500 ring-2 ring-violet-500",
        iconColor: "text-violet-600",
        badge: "bg-violet-100 text-violet-700",
        examples: ["Beratungsstunden", "IT-Support", "Schulungen", "Projektleitung"],
    },
]

export default function Step2IndustryType({ data, setData }: Step2Props) {
    const current = data.industry_type || { slug: null, initialize_data: true }

    const select = (slug: string | null) => {
        setData("industry_type", { slug, initialize_data: true })
    }

    return (
        <div className="space-y-6">
            {/* Intro */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>Branchenpaket wählen:</strong> Wählen Sie Ihre Branche und wir legen automatisch
                    passende Produkte, Kategorien, Lager und Layouts an — damit Sie sofort loslegen können.
                    Sie können alles danach beliebig anpassen.
                </p>
            </div>

            {/* Industry cards */}
            <div className="grid gap-3 md:grid-cols-2">
                {INDUSTRY_TYPES.map((type) => {
                    const Icon = type.icon
                    const isSelected = current.slug === type.slug

                    return (
                        <button
                            key={type.slug}
                            type="button"
                            onClick={() => select(type.slug)}
                            className={cn(
                                "relative text-left rounded-xl border-2 p-4 transition-all cursor-pointer w-full",
                                isSelected ? type.activeColor : type.color,
                            )}
                        >
                            {/* Checkmark */}
                            {isSelected && (
                                <CheckCircle2 className="absolute top-3 right-3 h-5 w-5 text-current opacity-80" />
                            )}

                            <div className="flex items-start gap-3">
                                <div className={cn("mt-0.5 rounded-lg p-2 bg-white/70", type.iconColor)}>
                                    <Icon className="h-5 w-5" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-semibold text-sm leading-tight">{type.label}</p>
                                    <p className="text-xs text-muted-foreground mt-1 leading-relaxed">
                                        {type.description}
                                    </p>
                                    <div className="flex flex-wrap gap-1 mt-2">
                                        {type.examples.map((ex) => (
                                            <span
                                                key={ex}
                                                className={cn(
                                                    "inline-flex text-[10px] font-medium px-1.5 py-0.5 rounded",
                                                    type.badge,
                                                )}
                                            >
                                                {ex}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </button>
                    )
                })}
            </div>

            {/* Skip option */}
            <button
                type="button"
                onClick={() => select(null)}
                className={cn(
                    "w-full flex items-center gap-3 rounded-xl border-2 p-4 transition-all text-left",
                    current.slug === null
                        ? "border-gray-400 bg-gray-100 ring-2 ring-gray-400"
                        : "border-dashed border-gray-300 hover:border-gray-400 bg-transparent",
                )}
            >
                <div className="rounded-lg p-2 bg-gray-100">
                    <X className="h-5 w-5 text-gray-500" />
                </div>
                <div>
                    <p className="font-semibold text-sm text-gray-700">Kein Branchenpaket</p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        Ich möchte alles manuell einrichten — Produkte, Kategorien und Layouts selbst anlegen.
                    </p>
                </div>
                {current.slug === null && (
                    <CheckCircle2 className="ml-auto h-5 w-5 text-gray-500 shrink-0" />
                )}
            </button>

            {current.slug && (
                <div className="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                    <CheckCircle2 className="h-4 w-4 text-green-600 shrink-0" />
                    <p className="text-sm text-green-800">
                        <strong>
                            {INDUSTRY_TYPES.find((t) => t.slug === current.slug)?.label}
                        </strong>{" "}
                        ausgewählt — Branchenpaket wird nach der Erstellung automatisch eingerichtet.
                    </p>
                </div>
            )}
        </div>
    )
}
