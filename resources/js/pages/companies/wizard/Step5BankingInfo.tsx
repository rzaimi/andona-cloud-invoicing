import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Landmark } from "lucide-react"

export default function Step5BankingInfo({ data, setData, errors }: any) {
    return (
        <div className="space-y-6">
            <Alert>
                <Landmark className="h-4 w-4" />
                <AlertDescription>
                    Geben Sie Ihre Bankverbindung ein. Diese wird auf Rechnungen angezeigt, damit Kunden Zahlungen vornehmen können.
                </AlertDescription>
            </Alert>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                    <Label htmlFor="bank_name">Bank Name</Label>
                    <Input
                        id="bank_name"
                        value={data.banking_info?.bank_name || ''}
                        onChange={(e) => setData('banking_info', { ...data.banking_info, bank_name: e.target.value })}
                        placeholder="z.B. Sparkasse Berlin"
                    />
                </div>

                <div>
                    <Label htmlFor="iban">IBAN *</Label>
                    <Input
                        id="iban"
                        value={data.banking_info?.iban || ''}
                        onChange={(e) => setData('banking_info', { ...data.banking_info, iban: e.target.value })}
                        placeholder="DE89 3704 0044 0532 0130 00"
                        maxLength={34}
                    />
                    {errors?.['banking_info.iban'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['banking_info.iban']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="bic">BIC/SWIFT *</Label>
                    <Input
                        id="bic"
                        value={data.banking_info?.bic || ''}
                        onChange={(e) => setData('banking_info', { ...data.banking_info, bic: e.target.value })}
                        placeholder="COBADEFFXXX"
                        maxLength={11}
                    />
                    {errors?.['banking_info.bic'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['banking_info.bic']}</p>
                    )}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="account_holder">Kontoinhaber</Label>
                    <Input
                        id="account_holder"
                        value={data.banking_info?.account_holder || ''}
                        onChange={(e) => setData('banking_info', { ...data.banking_info, account_holder: e.target.value })}
                        placeholder="Musterfirma GmbH"
                    />
                </div>
            </div>

            <Alert>
                <AlertDescription className="text-xs">
                    <strong>Hinweis:</strong> Die IBAN wird auf allen Rechnungen angezeigt. Stellen Sie sicher, dass die Angaben korrekt sind.
                </AlertDescription>
            </Alert>
        </div>
    )
}


