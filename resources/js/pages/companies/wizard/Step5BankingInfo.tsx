import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Landmark } from "lucide-react"

function formatIban(value: string): string {
    return value.replace(/[^a-zA-Z0-9]/g, "").toUpperCase().replace(/(.{4})/g, "$1 ").trim()
}

export default function Step5BankingInfo({ data, setData, errors }: any) {
    const bank = data.banking_info ?? {}
    const companyName = data.company_info?.name || "Musterfirma GmbH"

    const set = (field: string, value: string) =>
        setData("banking_info", { ...bank, [field]: value })

    return (
        <div className="space-y-6">
            <Alert>
                <Landmark className="h-4 w-4" />
                <AlertDescription>
                    Optional. Die Bankverbindung erscheint auf Rechnungen, damit Kunden überweisen können.
                    Alle Felder können später nachgetragen werden.
                </AlertDescription>
            </Alert>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                    <Label htmlFor="account_holder">Kontoinhaber</Label>
                    <Input
                        id="account_holder"
                        value={bank.account_holder || ""}
                        onChange={(e) => set("account_holder", e.target.value)}
                        placeholder={companyName}
                    />
                    {errors?.["banking_info.account_holder"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["banking_info.account_holder"]}</p>
                    )}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="bank_name">Bankname</Label>
                    <Input
                        id="bank_name"
                        value={bank.bank_name || ""}
                        onChange={(e) => set("bank_name", e.target.value)}
                        placeholder="z.B. Sparkasse Berlin"
                    />
                    {errors?.["banking_info.bank_name"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["banking_info.bank_name"]}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="iban">IBAN</Label>
                    <Input
                        id="iban"
                        value={bank.iban || ""}
                        onChange={(e) => set("iban", formatIban(e.target.value))}
                        placeholder="DE89 3704 0044 0532 0130 00"
                        maxLength={42}
                        className="font-mono"
                        autoComplete="off"
                    />
                    {errors?.["banking_info.iban"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["banking_info.iban"]}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="bic">BIC / SWIFT</Label>
                    <Input
                        id="bic"
                        value={bank.bic || ""}
                        onChange={(e) => set("bic", e.target.value.replace(/\s+/g, "").toUpperCase())}
                        placeholder="COBADEFFXXX"
                        maxLength={11}
                        className="font-mono"
                        autoComplete="off"
                    />
                    {errors?.["banking_info.bic"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["banking_info.bic"]}</p>
                    )}
                </div>
            </div>
        </div>
    )
}


