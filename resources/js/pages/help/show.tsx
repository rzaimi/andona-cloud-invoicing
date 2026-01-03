"use client"

import { Head, Link } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import {
    ArrowLeft,
    BookOpen,
    Users,
    ReceiptEuro,
    FileText,
    Settings,
    Package,
    Clock,
    AlertCircle,
    CheckCircle,
    Info,
    Mail,
    Calendar,
    TrendingUp,
    EuroIcon,
    HelpCircle,
} from "lucide-react"

interface HelpShowProps {
    user: any
    category: string
}

export default function HelpShow({ user, category }: HelpShowProps) {
    // Define all articles for each category
    const categoryArticles: Record<string, Array<{ id: string; title: string; content: string }>> = {
        "getting-started": [
            {
                id: "1",
                title: "Willkommen bei AndoBill",
                content: "AndoBill ist ein umfassendes Rechnungs- und Abrechnungssystem für deutsche Unternehmen. Dieses System hilft Ihnen dabei, Rechnungen zu erstellen, Kunden zu verwalten, Produkte zu katalogisieren und Ihre Finanzen zu überwachen. Beginnen Sie mit der Einrichtung Ihres Unternehmensprofils und den grundlegenden Einstellungen.",
            },
            {
                id: "2",
                title: "Erste Schritte - Firmeneinrichtung",
                content: "Nach der Anmeldung sollten Sie zunächst Ihr Unternehmensprofil einrichten. Gehen Sie zu Einstellungen > Firmeneinstellungen und füllen Sie alle erforderlichen Felder aus: Firmenname, Adresse, Steuernummer, USt-IdNr., Bankverbindung und Kontaktdaten. Diese Informationen werden automatisch in Ihre Rechnungen übernommen.",
            },
            {
                id: "3",
                title: "Benutzerrollen verstehen",
                content: "Das System hat drei Hauptrollen: Super-Admin (voller Zugriff auf alle Firmen), Admin (voller Zugriff innerhalb einer Firma) und Benutzer (eingeschränkter Zugriff). Super-Admins können Firmen und Benutzer verwalten. Admins können alle Funktionen innerhalb ihrer Firma nutzen. Benutzer können Rechnungen und Angebote erstellen, haben aber keinen Zugriff auf Einstellungen.",
            },
            {
                id: "4",
                title: "Dashboard-Navigation",
                content: "Das Dashboard bietet einen Überblick über Ihre wichtigsten Kennzahlen: Gesamtumsatz, offene Rechnungen, überfällige Rechnungen, Anzahl der Kunden und mehr. Nutzen Sie die Widgets, um schnell auf wichtige Funktionen zuzugreifen und den Status Ihres Unternehmens zu überwachen.",
            },
            {
                id: "5",
                title: "E-Mail-Einstellungen konfigurieren",
                content: "Um Rechnungen per E-Mail versenden zu können, müssen Sie Ihre SMTP-Einstellungen konfigurieren. Gehen Sie zu Einstellungen > E-Mail-Einstellungen und geben Sie Ihre SMTP-Daten ein: Server, Port, Benutzername, Passwort und Verschlüsselung. Testen Sie die Verbindung, bevor Sie speichern.",
            },
            {
                id: "6",
                title: "Rechnungsnummern-Format festlegen",
                content: "In Einstellungen > Firmeneinstellungen können Sie das Format Ihrer Rechnungsnummern festlegen. Sie können ein Präfix (z.B. 'RE-'), das Jahr und eine Startnummer konfigurieren. Beispiel: RE-2025-0001. Das System erhöht die Nummer automatisch für jede neue Rechnung.",
            },
            {
                id: "7",
                title: "Steuersätze konfigurieren",
                content: "Standardmäßig verwendet das System 19% Mehrwertsteuer. Sie können den Standardsteuersatz in Einstellungen > Firmeneinstellungen ändern. Für einzelne Produkte können Sie abweichende Steuersätze (7%, 0%, etc.) festlegen.",
            },
            {
                id: "8",
                title: "Währung und Formatierung",
                content: "Das System verwendet standardmäßig EUR (Euro) als Währung. Sie können die Währung, das Datumsformat, Dezimaltrennzeichen und Tausendertrennzeichen in Einstellungen > Firmeneinstellungen anpassen. Diese Einstellungen werden für alle Rechnungen, Berichte und Anzeigen verwendet.",
            },
            {
                id: "9",
                title: "Rechnungslayout auswählen",
                content: "Das System bietet mehrere vorgefertigte Rechnungslayouts: Clean, Modern, Professional, Elegant, Minimal und Classic. Gehen Sie zu Einstellungen > Rechnungslayouts, um ein Standardlayout auszuwählen oder eigene Layouts zu erstellen. Sie können Farben, Schriftarten und das Layout anpassen.",
            },
            {
                id: "10",
                title: "Ersten Kunden anlegen",
                content: "Bevor Sie Ihre erste Rechnung erstellen können, müssen Sie mindestens einen Kunden anlegen. Gehen Sie zu Kunden > Neuer Kunde und füllen Sie die Kundeninformationen aus: Name, Adresse, Kontaktdaten, Steuernummer (falls vorhanden) und Kundentyp (Geschäft oder Privat).",
            },
            {
                id: "11",
                title: "Erste Rechnung erstellen",
                content: "Gehen Sie zu Rechnungen > Neue Rechnung. Wählen Sie einen Kunden aus, fügen Sie Positionen hinzu (entweder aus Ihrem Produktkatalog oder als benutzerdefinierte Positionen), überprüfen Sie die Beträge und speichern Sie die Rechnung. Sie können sie als Entwurf speichern oder direkt versenden.",
            },
            {
                id: "12",
                title: "Produktkatalog aufbauen",
                content: "Ein Produktkatalog spart Zeit beim Erstellen von Rechnungen. Gehen Sie zu Produkte > Neues Produkt und fügen Sie Ihre Produkte oder Dienstleistungen hinzu. Geben Sie Name, Beschreibung, Preis, Steuersatz und optional SKU/Barcode ein. Produkte können später in Rechnungen ausgewählt werden.",
            },
            {
                id: "13",
                title: "Zahlungsmethoden einrichten",
                content: "In Einstellungen > Zahlungsmethoden können Sie die verfügbaren Zahlungsmethoden definieren: Überweisung, Bar, Kreditkarte, PayPal, etc. Diese werden bei der Erfassung von Zahlungen und in Rechnungen angezeigt.",
            },
            {
                id: "14",
                title: "Ausgabenkategorien erstellen",
                content: "Für eine bessere Organisation Ihrer Ausgaben sollten Sie Kategorien erstellen. Gehen Sie zu Ausgaben > Kategorien und erstellen Sie Kategorien wie 'Büromaterial', 'Reisekosten', 'Miete', etc. Diese helfen bei der Berichterstattung und Steuererklärung.",
            },
            {
                id: "15",
                title: "Berichte verstehen",
                content: "Das System bietet verschiedene Berichte: Umsatzberichte, Gewinn & Verlust, MwSt-Berichte, Kundenberichte und Ausgabenberichte. Gehen Sie zu Berichte, um auf alle verfügbaren Berichte zuzugreifen. Sie können Zeiträume filtern und Berichte als PDF exportieren.",
            },
        ],
        customers: [
            {
                id: "1",
                title: "Neuen Kunden anlegen",
                content: "Gehen Sie zu Kunden > Neuer Kunde. Füllen Sie die Pflichtfelder aus: Name und Kundentyp (Geschäft oder Privat). Optionale Felder: E-Mail, Telefon, Adresse, Steuernummer, USt-IdNr. und Ansprechpartner. Das System generiert automatisch eine Kundennummer.",
            },
            {
                id: "2",
                title: "Kundendaten bearbeiten",
                content: "Öffnen Sie einen Kunden aus der Kundenliste und klicken Sie auf 'Bearbeiten'. Sie können alle Informationen aktualisieren. Änderungen wirken sich nicht auf bereits erstellte Rechnungen aus, da diese eine Momentaufnahme der Kundendaten zum Zeitpunkt der Rechnungserstellung enthalten.",
            },
            {
                id: "3",
                title: "Kundentypen: Geschäft vs. Privat",
                content: "Geschäftskunden benötigen Steuernummern und USt-IdNr. für B2B-Transaktionen. Privatkunden werden ohne USt-IdNr. behandelt. Der Kundentyp beeinflusst, welche Felder in Rechnungen angezeigt werden.",
            },
            {
                id: "4",
                title: "Kundennummern verstehen",
                content: "Jeder Kunde erhält automatisch eine eindeutige Kundennummer. Das Format kann in Einstellungen angepasst werden. Die Nummer wird automatisch generiert und kann nicht manuell geändert werden, um Eindeutigkeit zu gewährleisten.",
            },
            {
                id: "5",
                title: "Kundenstatus: Aktiv/Inaktiv",
                content: "Sie können Kunden als 'Aktiv' oder 'Inaktiv' markieren. Inaktive Kunden erscheinen nicht in Auswahllisten beim Erstellen von Rechnungen, bleiben aber in der Datenbank erhalten. Dies ist nützlich für ehemalige Kunden.",
            },
            {
                id: "6",
                title: "Kundenstatistiken anzeigen",
                content: "Auf der Kundendetailseite sehen Sie Statistiken: Gesamtumsatz, Anzahl der Rechnungen, offene Beträge, letzte Rechnung und mehr. Diese Informationen helfen bei der Kundenbewertung und -pflege.",
            },
            {
                id: "7",
                title: "Kunden importieren",
                content: "Sie können Kunden aus einer CSV-Datei importieren. Gehen Sie zu Kunden > Importieren. Die CSV-Datei muss die Spalten enthalten: Name, E-Mail, Telefon, Adresse, etc. Laden Sie die Datei hoch und überprüfen Sie die Vorschau, bevor Sie importieren.",
            },
            {
                id: "8",
                title: "Kunden exportieren",
                content: "Exportieren Sie Ihre Kundendaten für Backup-Zwecke oder für die Verwendung in anderen Systemen. Gehen Sie zu Kunden und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV oder Excel) und laden Sie die Datei herunter.",
            },
            {
                id: "9",
                title: "Kunden suchen und filtern",
                content: "Verwenden Sie die Suchleiste in der Kundenliste, um nach Namen, E-Mail oder anderen Informationen zu suchen. Sie können auch nach Status (Aktiv/Inaktiv) und Kundentyp filtern.",
            },
            {
                id: "10",
                title: "Kunden löschen",
                content: "Sie können Kunden nur löschen, wenn keine Rechnungen oder Angebote mit diesem Kunden verknüpft sind. Wenn Rechnungen existieren, müssen Sie diese zuerst löschen oder den Kunden als 'Inaktiv' markieren.",
            },
            {
                id: "11",
                title: "Kunden-Duplikate vermeiden",
                content: "Vor dem Anlegen eines neuen Kunden sollten Sie prüfen, ob der Kunde bereits existiert. Verwenden Sie die Suchfunktion mit dem Namen oder der E-Mail-Adresse. Duplikate können zu Verwirrung und falschen Berichten führen.",
            },
            {
                id: "12",
                title: "Kundenhistorie anzeigen",
                content: "Auf der Kundendetailseite sehen Sie eine vollständige Historie aller Rechnungen, Angebote und Zahlungen für diesen Kunden. Dies gibt Ihnen einen Überblick über die Geschäftsbeziehung.",
            },
        ],
        invoices: [
            {
                id: "1",
                title: "Neue Rechnung erstellen",
                content: "Gehen Sie zu Rechnungen > Neue Rechnung. Wählen Sie einen Kunden aus der Liste. Fügen Sie Positionen hinzu: Klicken Sie auf 'Position hinzufügen' und wählen Sie ein Produkt aus Ihrem Katalog oder geben Sie eine benutzerdefinierte Position ein. Geben Sie Menge, Einzelpreis und Einheit an. Das System berechnet automatisch die Gesamtsumme und MwSt.",
            },
            {
                id: "2",
                title: "Rechnungspositionen verwalten",
                content: "Sie können mehrere Positionen zu einer Rechnung hinzufügen. Jede Position kann einen eigenen Steuersatz haben. Verwenden Sie die Pfeile, um Positionen neu zu ordnen. Klicken Sie auf das Löschen-Symbol, um eine Position zu entfernen.",
            },
            {
                id: "3",
                title: "Rechnungsstatus verstehen",
                content: "Rechnungen können folgende Status haben: Entwurf (noch nicht versendet), Versendet (an Kunden gesendet), Bezahlt (vollständig bezahlt), Überfällig (Fälligkeitsdatum überschritten) und Storniert (ungültig). Der Status wird automatisch aktualisiert, wenn Zahlungen erfasst werden.",
            },
            {
                id: "4",
                title: "Rechnung als PDF herunterladen",
                content: "Öffnen Sie eine Rechnung und klicken Sie auf 'PDF herunterladen'. Das System generiert eine professionelle PDF-Datei im gewählten Layout. Die PDF kann gespeichert, gedruckt oder per E-Mail versendet werden.",
            },
            {
                id: "5",
                title: "Rechnung per E-Mail versenden",
                content: "Öffnen Sie eine Rechnung und klicken Sie auf 'Versenden'. Das System generiert automatisch eine PDF und versendet diese mit einer professionellen E-Mail-Vorlage an die Kunden-E-Mail-Adresse. Stellen Sie sicher, dass Ihre E-Mail-Einstellungen konfiguriert sind.",
            },
            {
                id: "6",
                title: "Rechnung bearbeiten",
                content: "Öffnen Sie eine Rechnung und klicken Sie auf 'Bearbeiten'. Sie können alle Details ändern: Kunde, Positionen, Beträge, Daten. Beachten Sie: Bereits versendete Rechnungen sollten nur in Ausnahmefällen geändert werden. Erwägen Sie stattdessen eine Rechnungskorrektur.",
            },
            {
                id: "7",
                title: "Rechnungskorrektur erstellen",
                content: "Wenn Sie eine bereits versendete Rechnung korrigieren müssen, erstellen Sie eine Stornorechnung. Öffnen Sie die zu korrigierende Rechnung und klicken Sie auf 'Korrigieren'. Geben Sie den Korrekturgrund an. Das System erstellt eine neue Rechnung mit negativen Beträgen, die die ursprüngliche Rechnung storniert.",
            },
            {
                id: "8",
                title: "Rechnung stornieren",
                content: "Um eine Rechnung vollständig zu stornieren, erstellen Sie eine Stornorechnung. Diese hat negative Beträge und hebt die ursprüngliche Rechnung auf. Stornorechnungen müssen ebenfalls an den Kunden versendet werden.",
            },
            {
                id: "9",
                title: "Fälligkeitsdatum festlegen",
                content: "Beim Erstellen einer Rechnung können Sie ein Fälligkeitsdatum festlegen. Standardmäßig wird das Fälligkeitsdatum basierend auf den Zahlungsbedingungen in den Einstellungen berechnet. Das System überwacht überfällige Rechnungen automatisch.",
            },
            {
                id: "10",
                title: "Rechnungsnotizen hinzufügen",
                content: "Sie können Notizen zu einer Rechnung hinzufügen, die nur intern sichtbar sind (nicht auf der PDF). Diese sind nützlich für interne Informationen, Erinnerungen oder Anmerkungen zum Kunden.",
            },
            {
                id: "11",
                title: "Rechnungen filtern und suchen",
                content: "In der Rechnungsliste können Sie nach Status, Kunde, Datum oder Rechnungsnummer filtern. Verwenden Sie die Suchleiste, um nach Rechnungsnummern oder Kundennamen zu suchen.",
            },
            {
                id: "12",
                title: "Rechnungen exportieren",
                content: "Exportieren Sie Rechnungen für Buchhaltungszwecke oder Backups. Gehen Sie zu Rechnungen und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV, Excel, PDF) und den Zeitraum. Alle Rechnungen im gewählten Zeitraum werden exportiert.",
            },
            {
                id: "13",
                title: "Rechnungslayout ändern",
                content: "Sie können für jede Rechnung ein individuelles Layout wählen. Beim Erstellen oder Bearbeiten einer Rechnung wählen Sie das gewünschte Layout aus der Dropdown-Liste. Das Standardlayout wird aus den Einstellungen übernommen.",
            },
            {
                id: "14",
                title: "Wiederkehrende Rechnungen",
                content: "Für regelmäßige Rechnungen (z.B. monatliche Abonnements) können Sie eine Rechnung als Vorlage speichern und bei Bedarf duplizieren. Erstellen Sie die Rechnung einmal, speichern Sie sie, und duplizieren Sie sie für jeden Monat.",
            },
            {
                id: "15",
                title: "Teilzahlungen verwalten",
                content: "Wenn ein Kunde eine Rechnung in Teilen bezahlt, erfassen Sie jede Zahlung separat. Gehen Sie zu Zahlungen > Neue Zahlung und wählen Sie die Rechnung aus. Geben Sie den Teilbetrag ein. Die Rechnung wird automatisch als 'Bezahlt' markiert, wenn der Gesamtbetrag erreicht ist.",
            },
            {
                id: "16",
                title: "Rechnungen drucken",
                content: "Öffnen Sie eine Rechnung und klicken Sie auf 'Drucken' oder laden Sie die PDF herunter und drucken Sie diese. Die PDF ist für den Druck optimiert und enthält alle notwendigen Informationen im korrekten Format.",
            },
            {
                id: "17",
                title: "Rechnungsnummern-Format",
                content: "Rechnungsnummern werden automatisch generiert basierend auf dem Format in den Einstellungen. Das Format kann ein Präfix (z.B. 'RE-'), das Jahr und eine fortlaufende Nummer enthalten. Beispiel: RE-2025-0001, RE-2025-0002, etc.",
            },
            {
                id: "18",
                title: "MwSt. in Rechnungen",
                content: "Das System berechnet automatisch die Mehrwertsteuer basierend auf dem Steuersatz jeder Position. Der Steuersatz kann pro Position unterschiedlich sein (19%, 7%, 0%, etc.). Die Gesamt-MwSt. wird am Ende der Rechnung angezeigt.",
            },
            {
                id: "19",
                title: "Rechnungen löschen",
                content: "Sie können nur Entwürfe löschen. Bereits versendete oder bezahlte Rechnungen sollten nicht gelöscht werden, da dies die Buchhaltung beeinträchtigt. Verwenden Sie stattdessen Stornorechnungen für ungültige Rechnungen.",
            },
            {
                id: "20",
                title: "Rechnungsvorlagen verwenden",
                content: "Erstellen Sie häufig verwendete Rechnungspositionen als Produkte in Ihrem Katalog. Diese können dann schnell in neue Rechnungen eingefügt werden, was Zeit spart und Konsistenz gewährleistet.",
            },
        ],
        offers: [
            {
                id: "1",
                title: "Neues Angebot erstellen",
                content: "Gehen Sie zu Angebote > Neues Angebot. Wählen Sie einen Kunden aus und fügen Sie Positionen hinzu, ähnlich wie bei Rechnungen. Geben Sie ein Gültigkeitsdatum an, bis wann das Angebot gültig ist. Speichern Sie das Angebot als Entwurf oder versenden Sie es direkt.",
            },
            {
                id: "2",
                title: "Angebot in Rechnung umwandeln",
                content: "Wenn ein Kunde ein Angebot annimmt, können Sie es mit einem Klick in eine Rechnung umwandeln. Öffnen Sie das Angebot und klicken Sie auf 'In Rechnung umwandeln'. Alle Daten (Kunde, Positionen, Beträge) werden automatisch übernommen. Sie können die Rechnung vor dem Speichern noch anpassen.",
            },
            {
                id: "3",
                title: "Angebotsstatus verwalten",
                content: "Angebote können folgende Status haben: Entwurf, Versendet, Angenommen, Abgelehnt und Abgelaufen. Der Status 'Abgelaufen' wird automatisch gesetzt, wenn das Gültigkeitsdatum überschritten ist.",
            },
            {
                id: "4",
                title: "Gültigkeitsdatum festlegen",
                content: "Jedes Angebot sollte ein Gültigkeitsdatum haben. Dieses Datum bestimmt, wie lange das Angebot gültig ist. Nach Ablauf wird das Angebot automatisch als 'Abgelaufen' markiert. Standardmäßig sind Angebote 30 Tage gültig.",
            },
            {
                id: "5",
                title: "Angebot per E-Mail versenden",
                content: "Öffnen Sie ein Angebot und klicken Sie auf 'Versenden'. Das System generiert eine PDF und versendet diese per E-Mail an den Kunden. Die E-Mail enthält eine professionelle Vorlage mit allen Angebotsdetails.",
            },
            {
                id: "6",
                title: "Angebot bearbeiten",
                content: "Solange ein Angebot noch nicht angenommen wurde, können Sie es frei bearbeiten. Öffnen Sie das Angebot und klicken Sie auf 'Bearbeiten'. Änderungen an bereits versendeten Angeboten sollten dem Kunden mitgeteilt werden.",
            },
            {
                id: "7",
                title: "Angebotsnummern-Format",
                content: "Angebotsnummern werden ähnlich wie Rechnungsnummern generiert, aber mit einem anderen Präfix (z.B. 'AN-'). Das Format kann in Einstellungen > Firmeneinstellungen konfiguriert werden.",
            },
            {
                id: "8",
                title: "Angebote filtern",
                content: "In der Angebotsliste können Sie nach Status, Kunde oder Datum filtern. Dies hilft Ihnen, den Überblick über aktive, angenommene oder abgelaufene Angebote zu behalten.",
            },
            {
                id: "9",
                title: "Angebot verlängern",
                content: "Wenn ein Angebot abläuft, aber der Kunde noch Interesse zeigt, können Sie das Gültigkeitsdatum verlängern. Bearbeiten Sie das Angebot und setzen Sie ein neues Gültigkeitsdatum. Versenden Sie das aktualisierte Angebot erneut an den Kunden.",
            },
            {
                id: "10",
                title: "Angebot ablehnen",
                content: "Wenn ein Kunde ein Angebot ablehnt, markieren Sie es als 'Abgelehnt'. Dies hilft bei der Nachverfolgung und Statistiken. Abgelehnte Angebote können später wieder aktiviert werden, wenn der Kunde sich umentscheidet.",
            },
            {
                id: "11",
                title: "Angebotslayout anpassen",
                content: "Angebote verwenden separate Layouts von Rechnungen. Gehen Sie zu Einstellungen > Angebotslayouts, um das Standardlayout zu wählen oder eigene Layouts zu erstellen. Sie können Farben, Schriftarten und das Layout anpassen.",
            },
            {
                id: "12",
                title: "Angebote exportieren",
                content: "Exportieren Sie Angebote für Analysezwecke oder Backups. Gehen Sie zu Angebote und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV, Excel, PDF) und den Zeitraum.",
            },
        ],
        products: [
            {
                id: "1",
                title: "Neues Produkt anlegen",
                content: "Gehen Sie zu Produkte > Neues Produkt. Füllen Sie die Pflichtfelder aus: Name, Einheit, Preis und Steuersatz. Optionale Felder: Beschreibung, SKU, Barcode, Kategorie, Einkaufspreis und Lagerbestand. Wählen Sie, ob es sich um ein Produkt oder eine Dienstleistung handelt.",
            },
            {
                id: "2",
                title: "Produktkategorien verwalten",
                content: "Organisieren Sie Ihre Produkte in Kategorien. Gehen Sie zu Produkte > Kategorien und erstellen Sie Kategorien wie 'Elektronik', 'Büromaterial', 'Dienstleistungen', etc. Kategorien helfen bei der Organisation und beim Filtern.",
            },
            {
                id: "3",
                title: "Lagerverwaltung aktivieren",
                content: "Für physische Produkte können Sie die Lagerverwaltung aktivieren. Aktivieren Sie 'Lagerbestand verfolgen' beim Erstellen oder Bearbeiten eines Produkts. Geben Sie den aktuellen Bestand und den Mindestbestand ein. Das System warnt Sie, wenn der Bestand niedrig ist.",
            },
            {
                id: "4",
                title: "Lagerbestand anpassen",
                content: "Gehen Sie zu Lagerbestand, um Lagerbestände zu verwalten. Sie können Bestände manuell anpassen, Lagerbewegungen (Ein- und Ausgänge) erfassen und den Bestandsverlauf einsehen. Jede Bewegung wird protokolliert.",
            },
            {
                id: "5",
                title: "Niedrige Lagerbestände überwachen",
                content: "Das System überwacht automatisch Produkte mit niedrigem Lagerbestand. Wenn der Bestand unter den Mindestbestand fällt, erscheint eine Warnung im Dashboard. Sie können auch E-Mail-Benachrichtigungen für niedrige Bestände aktivieren.",
            },
            {
                id: "6",
                title: "Produktpreise verwalten",
                content: "Sie können für jedes Produkt einen Verkaufspreis und optional einen Einkaufspreis festlegen. Der Einkaufspreis wird für Gewinnberechnungen verwendet. Preise können jederzeit aktualisiert werden, ohne dass sich dies auf bereits erstellte Rechnungen auswirkt.",
            },
            {
                id: "7",
                title: "SKU und Barcode verwenden",
                content: "Jedes Produkt kann eine SKU (Stock Keeping Unit) und einen Barcode haben. Diese helfen bei der Identifikation und können für Barcode-Scanner verwendet werden. SKUs sollten eindeutig sein.",
            },
            {
                id: "8",
                title: "Produktstatus: Aktiv/Inaktiv",
                content: "Sie können Produkte als 'Aktiv', 'Inaktiv' oder 'Eingestellt' markieren. Inaktive Produkte erscheinen nicht in Auswahllisten beim Erstellen von Rechnungen, bleiben aber in der Datenbank erhalten.",
            },
            {
                id: "9",
                title: "Produkte importieren",
                content: "Importieren Sie Produkte aus einer CSV-Datei. Gehen Sie zu Produkte > Importieren. Die CSV-Datei muss Spalten für Name, Preis, Steuersatz, etc. enthalten. Überprüfen Sie die Vorschau vor dem Import.",
            },
            {
                id: "10",
                title: "Produkte exportieren",
                content: "Exportieren Sie Ihren Produktkatalog für Backup-Zwecke. Gehen Sie zu Produkte und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV oder Excel).",
            },
            {
                id: "11",
                title: "Produkte in Rechnungen verwenden",
                content: "Beim Erstellen einer Rechnung können Sie Produkte aus Ihrem Katalog auswählen. Klicken Sie auf 'Produkt hinzufügen' und wählen Sie aus der Liste. Menge und Preis werden automatisch übernommen, können aber angepasst werden.",
            },
            {
                id: "12",
                title: "Dienstleistungen vs. Produkte",
                content: "Dienstleistungen benötigen keine Lagerverwaltung. Markieren Sie ein Produkt als 'Dienstleistung', wenn es sich um eine erbrachte Leistung handelt (z.B. Beratung, Reparatur). Physische Produkte sollten als 'Produkt' markiert werden.",
            },
            {
                id: "13",
                title: "Lagerbestand bei Rechnungen",
                content: "Wenn Sie ein Produkt mit aktivierter Lagerverwaltung in einer Rechnung verwenden, wird der Lagerbestand automatisch reduziert. Dies geschieht, sobald die Rechnung gespeichert wird (nicht nur beim Versenden).",
            },
            {
                id: "14",
                title: "Produktsuche und -filter",
                content: "Verwenden Sie die Suchleiste in der Produktliste, um nach Name, SKU oder Barcode zu suchen. Sie können auch nach Kategorie, Status oder Lagerbestand filtern.",
            },
        ],
        payments: [
            {
                id: "1",
                title: "Neue Zahlung erfassen",
                content: "Gehen Sie zu Zahlungen > Neue Zahlung. Wählen Sie die Rechnung aus, auf die sich die Zahlung bezieht. Geben Sie den Betrag, das Zahlungsdatum, die Zahlungsmethode (Überweisung, Bar, etc.) und optional eine Referenz ein. Speichern Sie die Zahlung.",
            },
            {
                id: "2",
                title: "Teilzahlungen verwalten",
                content: "Wenn ein Kunde eine Rechnung in Teilen bezahlt, erfassen Sie jede Zahlung separat. Wählen Sie die gleiche Rechnung für jede Zahlung und geben Sie den jeweiligen Teilbetrag ein. Die Rechnung wird automatisch als 'Bezahlt' markiert, wenn der Gesamtbetrag erreicht ist.",
            },
            {
                id: "3",
                title: "Rechnung automatisch als bezahlt markieren",
                content: "Wenn der Gesamtbetrag aller Zahlungen für eine Rechnung den Rechnungsbetrag erreicht oder überschreitet, wird die Rechnung automatisch als 'Bezahlt' markiert. Dies geschieht sofort nach dem Speichern der Zahlung.",
            },
            {
                id: "4",
                title: "Zahlungsstatus verstehen",
                content: "Zahlungen können folgende Status haben: Ausstehend (noch nicht verbucht), Abgeschlossen (erfolgreich verbucht) und Storniert (rückgängig gemacht). Nur abgeschlossene Zahlungen zählen für den Rechnungsstatus.",
            },
            {
                id: "5",
                title: "Zahlung bearbeiten",
                content: "Öffnen Sie eine Zahlung und klicken Sie auf 'Bearbeiten'. Sie können Betrag, Datum, Zahlungsmethode und Referenz ändern. Wenn Sie den Betrag ändern, wird der Rechnungsstatus automatisch neu berechnet.",
            },
            {
                id: "6",
                title: "Zahlung stornieren",
                content: "Wenn eine Zahlung fälschlicherweise erfasst wurde, können Sie sie stornieren. Öffnen Sie die Zahlung und klicken Sie auf 'Stornieren'. Die Rechnung wird automatisch wieder als 'Versendet' oder 'Überfällig' markiert, wenn der Gesamtbetrag nicht mehr erreicht ist.",
            },
            {
                id: "7",
                title: "Zahlungshistorie einer Rechnung",
                content: "Auf der Rechnungsdetailseite sehen Sie alle Zahlungen, die für diese Rechnung erfasst wurden. Dies zeigt den Zahlungsverlauf, den bereits bezahlten Betrag und den verbleibenden Restbetrag.",
            },
            {
                id: "8",
                title: "Zahlungsmethoden verwenden",
                content: "Wählen Sie beim Erfassen einer Zahlung die entsprechende Zahlungsmethode aus: Überweisung, Bar, Kreditkarte, PayPal, SEPA-Lastschrift, etc. Diese Informationen werden für Berichte und Statistiken verwendet.",
            },
            {
                id: "9",
                title: "Zahlungen filtern und suchen",
                content: "In der Zahlungsliste können Sie nach Rechnung, Kunde, Datum, Zahlungsmethode oder Status filtern. Verwenden Sie die Suchleiste, um nach Referenzen oder Beträgen zu suchen.",
            },
            {
                id: "10",
                title: "Zahlungen exportieren",
                content: "Exportieren Sie Zahlungen für Buchhaltungszwecke. Gehen Sie zu Zahlungen und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV, Excel) und den Zeitraum. Alle Zahlungen im gewählten Zeitraum werden exportiert.",
            },
        ],
        expenses: [
            {
                id: "1",
                title: "Neue Ausgabe erfassen",
                content: "Gehen Sie zu Ausgaben > Neue Ausgabe. Geben Sie einen Titel, den Betrag (Brutto), das Datum, optional eine Kategorie, Steuersatz (Standard: 19%), Zahlungsmethode und Referenz ein. Das System berechnet automatisch MwSt. und Netto-Betrag.",
            },
            {
                id: "2",
                title: "Ausgabenkategorien erstellen",
                content: "Organisieren Sie Ihre Ausgaben in Kategorien. Gehen Sie zu Ausgaben > Kategorien und erstellen Sie Kategorien wie 'Büromaterial', 'Reisekosten', 'Miete', 'Marketing', etc. Kategorien helfen bei der Berichterstattung und Steuererklärung.",
            },
            {
                id: "3",
                title: "Belege hochladen",
                content: "Für jede Ausgabe können Sie einen Beleg (PDF, JPG, PNG) hochladen. Klicken Sie auf 'Beleg hochladen' beim Erstellen oder Bearbeiten einer Ausgabe. Belege werden sicher gespeichert und können später heruntergeladen werden.",
            },
            {
                id: "4",
                title: "MwSt. bei Ausgaben verstehen",
                content: "Bei Ausgaben ist die MwSt. eine Vorsteuer, die Sie von der Finanzverwaltung zurückerhalten können. Das System berechnet automatisch die MwSt. basierend auf dem Steuersatz. Der Netto-Betrag ist der Betrag ohne MwSt.",
            },
            {
                id: "5",
                title: "Ausgaben bearbeiten",
                content: "Öffnen Sie eine Ausgabe und klicken Sie auf 'Bearbeiten'. Sie können alle Details ändern. Wenn Sie den Betrag oder Steuersatz ändern, werden MwSt. und Netto-Betrag automatisch neu berechnet.",
            },
            {
                id: "6",
                title: "Ausgaben löschen",
                content: "Sie können Ausgaben löschen, wenn sie fälschlicherweise erfasst wurden. Öffnen Sie die Ausgabe und klicken Sie auf 'Löschen'. Bestätigen Sie die Löschung. Gelöschte Ausgaben erscheinen nicht mehr in Berichten.",
            },
            {
                id: "7",
                title: "Ausgaben filtern",
                content: "In der Ausgabenliste können Sie nach Kategorie, Datum oder Betrag filtern. Verwenden Sie die Suchleiste, um nach Titel, Beschreibung oder Referenz zu suchen. Dies hilft bei der Suche nach spezifischen Ausgaben.",
            },
            {
                id: "8",
                title: "Ausgabenberichte erstellen",
                content: "Gehen Sie zu Berichte > Ausgaben, um detaillierte Ausgabenberichte zu sehen. Sie können nach Zeitraum, Kategorie oder Betrag filtern. Die Berichte zeigen Gesamtausgaben, MwSt. und Netto-Beträge.",
            },
            {
                id: "9",
                title: "Ausgaben exportieren",
                content: "Exportieren Sie Ausgaben für Buchhaltungszwecke oder Steuererklärung. Gehen Sie zu Ausgaben und klicken Sie auf 'Exportieren'. Wählen Sie das Format (CSV, Excel) und den Zeitraum.",
            },
            {
                id: "10",
                title: "Wiederkehrende Ausgaben",
                content: "Für regelmäßige Ausgaben (z.B. monatliche Miete) können Sie eine Ausgabe als Vorlage speichern und bei Bedarf duplizieren. Erstellen Sie die Ausgabe einmal und duplizieren Sie sie für jeden Monat.",
            },
            {
                id: "11",
                title: "Ausgabenkategorien verwalten",
                content: "Sie können Kategorien bearbeiten oder löschen. Beachten Sie: Kategorien können nur gelöscht werden, wenn keine Ausgaben mehr dieser Kategorie zugeordnet sind. Verschieben Sie zuerst die Ausgaben in andere Kategorien.",
            },
        ],
        reports: [
            {
                id: "1",
                title: "Umsatzberichte erstellen",
                content: "Gehen Sie zu Berichte > Umsatz. Wählen Sie einen Zeitraum (Tag, Woche, Monat, Jahr oder benutzerdefiniert). Der Bericht zeigt Gesamtumsatz, Anzahl der Rechnungen, durchschnittlichen Rechnungsbetrag und Trends über die Zeit.",
            },
            {
                id: "2",
                title: "Gewinn & Verlust Bericht",
                content: "Gehen Sie zu Berichte > Gewinn & Verlust. Dieser Bericht vergleicht Einnahmen (bezahlte Rechnungen) mit Ausgaben. Er zeigt Bruttogewinn, Netto-Gewinn/Verlust und Marge. Wählen Sie einen Zeitraum für die Analyse.",
            },
            {
                id: "3",
                title: "MwSt-Berichte",
                content: "Gehen Sie zu Berichte > MwSt. Dieser Bericht zeigt Ausgangs-MwSt. (von Rechnungen) und Eingangs-MwSt. (von Ausgaben). Die Differenz ist die zu zahlende oder erstattungsfähige MwSt. Wählen Sie einen Zeitraum (normalerweise ein Quartal).",
            },
            {
                id: "4",
                title: "Ausgabenberichte",
                content: "Gehen Sie zu Berichte > Ausgaben. Dieser Bericht zeigt alle Ausgaben nach Kategorie, Zeitraum und Betrag. Sie können nach Kategorie filtern, um zu sehen, wo das meiste Geld ausgegeben wird.",
            },
            {
                id: "5",
                title: "Kundenberichte",
                content: "Gehen Sie zu Berichte > Kunden. Dieser Bericht zeigt Umsatz pro Kunde, Anzahl der Rechnungen, durchschnittlichen Rechnungsbetrag und Zahlungsverhalten. Dies hilft bei der Identifikation Ihrer besten Kunden.",
            },
            {
                id: "6",
                title: "Berichte exportieren",
                content: "Alle Berichte können als PDF exportiert werden. Klicken Sie auf 'Exportieren' im Bericht. Die PDF-Datei kann für Präsentationen, Buchhaltung oder Archivierung verwendet werden.",
            },
            {
                id: "7",
                title: "Zeiträume filtern",
                content: "Alle Berichte unterstützen Zeitraumfilter. Wählen Sie vordefinierte Zeiträume (Heute, Diese Woche, Dieser Monat, Dieses Jahr) oder einen benutzerdefinierten Zeitraum mit Start- und Enddatum.",
            },
            {
                id: "8",
                title: "Berichte drucken",
                content: "Berichte können direkt aus dem Browser gedruckt werden. Klicken Sie auf 'Drucken' oder verwenden Sie Strg+P (Windows) oder Cmd+P (Mac). Die Berichte sind für den Druck optimiert.",
            },
            {
                id: "9",
                title: "Vergleichsberichte",
                content: "Einige Berichte zeigen Vergleiche zwischen Zeiträumen (z.B. diesen Monat vs. letzten Monat). Dies hilft bei der Identifikation von Trends und Wachstum.",
            },
        ],
        calendar: [
            {
                id: "1",
                title: "Kalender öffnen",
                content: "Gehen Sie zu Kalender. Der Kalender zeigt automatisch fällige Rechnungen, ablaufende Angebote und Ihre eigenen Termine. Sie sehen eine Monatsansicht mit allen wichtigen Ereignissen.",
            },
            {
                id: "2",
                title: "Neuen Termin erstellen",
                content: "Klicken Sie auf 'Termin hinzufügen'. Geben Sie Titel, Typ (Termin, Bericht, Lager), Datum, Uhrzeit, optional Ort und Beschreibung ein. Speichern Sie den Termin. Er erscheint im Kalender und in der Liste anstehender Ereignisse.",
            },
            {
                id: "3",
                title: "Termin bearbeiten",
                content: "Klicken Sie auf einen Termin im Kalender oder in der Liste. Klicken Sie auf das Bearbeiten-Symbol. Ändern Sie die Details und speichern Sie. Nur selbst erstellte Termine können bearbeitet werden.",
            },
            {
                id: "4",
                title: "Termin löschen",
                content: "Klicken Sie auf einen Termin und dann auf das Löschen-Symbol. Bestätigen Sie die Löschung. Nur selbst erstellte Termine können gelöscht werden. Automatische Ereignisse (fällige Rechnungen) können nicht gelöscht werden.",
            },
            {
                id: "5",
                title: "Fällige Rechnungen im Kalender",
                content: "Rechnungen mit Fälligkeitsdatum erscheinen automatisch im Kalender. Überfällige Rechnungen werden rot hervorgehoben. Klicken Sie auf ein Ereignis, um zur Rechnung zu gelangen.",
            },
            {
                id: "6",
                title: "Ablaufende Angebote",
                content: "Angebote mit Gültigkeitsdatum erscheinen im Kalender. Angebote, die in den nächsten 3 Tagen ablaufen, werden orange hervorgehoben. Abgelaufene Angebote werden rot angezeigt.",
            },
            {
                id: "7",
                title: "Kalender filtern",
                content: "Verwenden Sie den Filter, um bestimmte Ereignistypen anzuzeigen: Alle, Rechnungen, Angebote, Termine, Lager oder Berichte. Dies hilft, den Überblick zu behalten, wenn viele Ereignisse vorhanden sind.",
            },
        ],
        "erechnung": [
            {
                id: "1",
                title: "E-Rechnung aktivieren",
                content: "Gehen Sie zu Einstellungen > E-Rechnung. Aktivieren Sie E-Rechnung für Ihr Unternehmen. Wählen Sie das Format: XRechnung (XML-basiert) oder ZUGFeRD (PDF mit eingebettetem XML). Beide Formate sind EN 16931-konform.",
            },
            {
                id: "2",
                title: "Elektronische Adresse konfigurieren",
                content: "Geben Sie Ihre elektronische Adresse (E-Mail oder Peppol-Adresse) in den Einstellungen ein. Diese Adresse wird in E-Rechnungen verwendet und zeigt dem Empfänger, wohin elektronische Rechnungen gesendet werden können.",
            },
            {
                id: "3",
                title: "E-Rechnung generieren",
                content: "Beim Erstellen oder Bearbeiten einer Rechnung können Sie 'E-Rechnung generieren' wählen. Das System erstellt eine E-Rechnung im gewählten Format (XRechnung oder ZUGFeRD). Die Datei kann heruntergeladen oder per E-Mail versendet werden.",
            },
            {
                id: "4",
                title: "XRechnung Format",
                content: "XRechnung ist ein XML-basiertes Format für elektronische Rechnungen in Deutschland. Es ist EN 16931-konform und wird von vielen Systemen unterstützt. Die Datei hat die Endung .xml.",
            },
            {
                id: "5",
                title: "ZUGFeRD Format",
                content: "ZUGFeRD ist ein hybrides Format: eine PDF-Datei mit eingebettetem XML. Es kann sowohl von Menschen (PDF) als auch von Maschinen (XML) gelesen werden. Die Datei hat die Endung .pdf.",
            },
            {
                id: "6",
                title: "E-Rechnung versenden",
                content: "Nach der Generierung können Sie die E-Rechnung per E-Mail versenden. Das System fügt die E-Rechnung als Anhang hinzu. Stellen Sie sicher, dass der Empfänger E-Rechnungen verarbeiten kann.",
            },
            {
                id: "7",
                title: "E-Rechnung Anforderungen",
                content: "E-Rechnungen müssen bestimmte Pflichtfelder enthalten: Rechnungsnummer, Rechnungsdatum, Lieferant, Empfänger, Positionen mit Steuersätzen, Gesamtbeträge. Das System stellt sicher, dass alle Anforderungen erfüllt sind.",
            },
            {
                id: "8",
                title: "E-Rechnung Validierung",
                content: "Vor dem Versenden validiert das System die E-Rechnung auf Vollständigkeit und Konformität. Wenn Fehler gefunden werden, werden Sie benachrichtigt und können die Rechnung korrigieren.",
            },
        ],
        reminders: [
            {
                id: "1",
                title: "Mahnungen verstehen",
                content: "Mahnungen sind Erinnerungen an überfällige Rechnungen. Das System unterstützt ein 5-stufiges Mahnverfahren nach deutschem Recht. Jede Stufe hat ein Intervall (z.B. 7, 14, 21 Tage) und optional eine Mahngebühr.",
            },
            {
                id: "2",
                title: "Mahnungseinstellungen konfigurieren",
                content: "Gehen Sie zu Einstellungen > Erinnerungen. Konfigurieren Sie die Intervalle für jede Mahnstufe (1. Mahnung nach 7 Tagen, 2. Mahnung nach weiteren 14 Tagen, etc.). Legen Sie optional Mahngebühren für jede Stufe fest.",
            },
            {
                id: "3",
                title: "Automatische Mahnungen aktivieren",
                content: "Aktivieren Sie 'Automatisch versenden' in den Mahnungseinstellungen. Das System sendet dann automatisch Mahnungen basierend auf dem Fälligkeitsdatum der Rechnung. Sie werden per E-Mail benachrichtigt, wenn eine Mahnung versendet wurde.",
            },
            {
                id: "4",
                title: "Manuelle Mahnung versenden",
                content: "Öffnen Sie eine überfällige Rechnung und klicken Sie auf 'Mahnung versenden'. Wählen Sie die Mahnstufe (1., 2., 3., etc.). Das System generiert eine Mahnung mit den entsprechenden Gebühren und versendet sie per E-Mail.",
            },
            {
                id: "5",
                title: "Mahngebühren verstehen",
                content: "Mahngebühren können für jede Mahnstufe festgelegt werden. Diese werden zur Rechnung hinzugefügt. Beachten Sie die gesetzlichen Vorschriften für Mahngebühren in Deutschland. Standardmäßig sind keine Gebühren aktiviert.",
            },
            {
                id: "6",
                title: "Mahnungshistorie",
                content: "Auf der Rechnungsdetailseite sehen Sie alle versendeten Mahnungen mit Datum, Stufe und Status. Dies hilft bei der Nachverfolgung des Mahnverfahrens.",
            },
            {
                id: "7",
                title: "Mahnungen per Cronjob automatisieren",
                content: "Für vollautomatische Mahnungen können Sie einen Cronjob einrichten. Der Befehl 'php artisan reminders:send' sendet alle fälligen Mahnungen. Richten Sie diesen Befehl täglich in Ihrem Cronjob ein.",
            },
            {
                id: "8",
                title: "Mahnungstexte anpassen",
                content: "Die Mahnungstexte können in den Einstellungen angepasst werden. Verwenden Sie professionelle, rechtlich korrekte Formulierungen. Das System fügt automatisch Rechnungsdetails und Beträge ein.",
            },
            {
                id: "9",
                title: "Mahnungen stoppen",
                content: "Wenn eine Rechnung bezahlt wird, werden keine weiteren Mahnungen für diese Rechnung versendet. Das System erkennt automatisch, wenn eine Rechnung bezahlt ist, und stoppt das Mahnverfahren.",
            },
            {
                id: "10",
                title: "Mahnungen testen",
                content: "Verwenden Sie den Befehl 'php artisan reminders:send --dry-run', um zu sehen, welche Mahnungen versendet würden, ohne sie tatsächlich zu versenden. Dies ist nützlich zum Testen der Konfiguration.",
            },
        ],
        settings: [
            {
                id: "1",
                title: "Firmeneinstellungen",
                content: "Gehen Sie zu Einstellungen > Firmeneinstellungen. Hier können Sie Firmenname, Adresse, Kontaktdaten, Steuernummern, Bankverbindung und alle grundlegenden Unternehmensinformationen verwalten. Diese werden in Rechnungen verwendet.",
            },
            {
                id: "2",
                title: "E-Mail-Einstellungen",
                content: "Konfigurieren Sie Ihre SMTP-Einstellungen in Einstellungen > E-Mail. Geben Sie Server, Port, Benutzername, Passwort und Verschlüsselung ein. Testen Sie die Verbindung vor dem Speichern. Diese Einstellungen werden für den Versand von Rechnungen und Mahnungen verwendet.",
            },
            {
                id: "3",
                title: "Rechnungslayouts verwalten",
                content: "Gehen Sie zu Einstellungen > Rechnungslayouts. Wählen Sie ein Standardlayout oder erstellen Sie eigene Layouts. Sie können Farben, Schriftarten, Logo-Position und Footer-Inhalte anpassen. Änderungen wirken sich auf alle neuen Rechnungen aus.",
            },
            {
                id: "4",
                title: "Angebotslayouts verwalten",
                content: "Ähnlich wie Rechnungslayouts können Sie Angebotslayouts in Einstellungen > Angebotslayouts verwalten. Angebote verwenden separate Layouts von Rechnungen, können aber ähnlich gestaltet werden.",
            },
            {
                id: "5",
                title: "Mahnungseinstellungen",
                content: "Konfigurieren Sie das Mahnverfahren in Einstellungen > Erinnerungen. Legen Sie Intervalle, Gebühren und automatischen Versand fest. Die Einstellungen gelten für alle Rechnungen.",
            },
            {
                id: "6",
                title: "Zahlungsmethoden verwalten",
                content: "Definieren Sie verfügbare Zahlungsmethoden in Einstellungen > Zahlungsmethoden. Diese erscheinen bei der Erfassung von Zahlungen und können in Rechnungen angezeigt werden.",
            },
            {
                id: "7",
                title: "Benachrichtigungen konfigurieren",
                content: "Aktivieren oder deaktivieren Sie E-Mail-Benachrichtigungen in Einstellungen > Benachrichtigungen. Sie können Benachrichtigungen für überfällige Rechnungen, niedrige Lagerbestände, neue Zahlungen, etc. aktivieren.",
            },
            {
                id: "8",
                title: "Profil bearbeiten",
                content: "Gehen Sie zu Einstellungen > Profil. Aktualisieren Sie Ihren Namen, E-Mail-Adresse und andere persönliche Informationen. Änderungen an der E-Mail-Adresse erfordern eine Bestätigung.",
            },
            {
                id: "9",
                title: "Passwort ändern",
                content: "Gehen Sie zu Einstellungen > Passwort. Geben Sie Ihr aktuelles Passwort und das neue Passwort ein. Das neue Passwort muss mindestens 8 Zeichen lang sein. Speichern Sie die Änderungen.",
            },
            {
                id: "10",
                title: "Erscheinungsbild anpassen",
                content: "Wählen Sie zwischen Hell, Dunkel oder System (folgt Systemeinstellungen) in Einstellungen > Erscheinungsbild. Die Auswahl wird sofort angewendet und in Ihrem Browser gespeichert.",
            },
            {
                id: "11",
                title: "E-Rechnung konfigurieren",
                content: "Aktivieren und konfigurieren Sie E-Rechnung in Einstellungen > E-Rechnung. Wählen Sie das Format (XRechnung oder ZUGFeRD) und geben Sie Ihre elektronische Adresse ein.",
            },
            {
                id: "12",
                title: "E-Mail-Verlauf anzeigen",
                content: "Sehen Sie alle versendeten E-Mails (Rechnungen, Angebote, Mahnungen) in Einstellungen > E-Mail-Verlauf. Sie können den Status, Empfänger, Betreff und Versandzeitpunkt einsehen.",
            },
            {
                id: "13",
                title: "Dokumente verwalten",
                content: "Verwalten Sie hochgeladene Dokumente in Einstellungen > Dokumente. Sie können Dokumente hochladen, herunterladen, löschen und mit Rechnungen, Angeboten oder Kunden verknüpfen.",
            },
            {
                id: "14",
                title: "Import/Export",
                content: "Importieren oder exportieren Sie Daten in Einstellungen > Import/Export. Unterstützte Formate: CSV, Excel. Sie können Kunden, Produkte, Rechnungen und andere Daten importieren/exportieren.",
            },
            {
                id: "15",
                title: "Währung und Formatierung",
                content: "Legen Sie Währung, Datumsformat, Dezimaltrennzeichen und Tausendertrennzeichen in Einstellungen > Firmeneinstellungen fest. Diese Einstellungen werden systemweit verwendet.",
            },
            {
                id: "16",
                title: "Rechnungsnummern-Format",
                content: "Konfigurieren Sie das Format für Rechnungs- und Angebotsnummern in Einstellungen > Firmeneinstellungen. Sie können Präfixe, Jahresangaben und Startnummern festlegen.",
            },
            {
                id: "17",
                title: "Steuersätze konfigurieren",
                content: "Legen Sie Standardsteuersätze in Einstellungen > Firmeneinstellungen fest. Der Standardsteuersatz wird für neue Rechnungen verwendet, kann aber pro Position geändert werden.",
            },
            {
                id: "18",
                title: "Backup-Einstellungen",
                content: "Regelmäßige Backups Ihrer Daten werden empfohlen. Exportieren Sie regelmäßig Ihre Daten über die Export-Funktionen. Für automatische Backups kontaktieren Sie Ihren Systemadministrator.",
            },
        ],
    }

    const categoryTitles: Record<string, { title: string; icon: any; color: string }> = {
        "getting-started": { title: "Erste Schritte", icon: BookOpen, color: "bg-blue-500" },
        customers: { title: "Kundenverwaltung", icon: Users, color: "bg-green-500" },
        invoices: { title: "Rechnungen", icon: ReceiptEuro, color: "bg-purple-500" },
        offers: { title: "Angebote", icon: FileText, color: "bg-orange-500" },
        products: { title: "Produktverwaltung", icon: Package, color: "bg-indigo-500" },
        payments: { title: "Zahlungen", icon: EuroIcon, color: "bg-emerald-500" },
        expenses: { title: "Ausgaben", icon: ReceiptEuro, color: "bg-red-500" },
        reports: { title: "Berichte", icon: TrendingUp, color: "bg-cyan-500" },
        calendar: { title: "Kalender", icon: Calendar, color: "bg-pink-500" },
        erechnung: { title: "E-Rechnung", icon: FileText, color: "bg-teal-500" },
        reminders: { title: "Mahnungen", icon: AlertCircle, color: "bg-amber-500" },
        settings: { title: "Einstellungen", icon: Settings, color: "bg-gray-500" },
    }

    const articles = categoryArticles[category] || []
    const categoryInfo = categoryTitles[category] || { title: "Unbekannte Kategorie", icon: HelpCircle, color: "bg-gray-500" }
    const CategoryIcon = categoryInfo.icon

    return (
        <AppLayout user={user}>
            <Head title={`${categoryInfo.title} - Hilfe & Support`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/help">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück zur Übersicht
                            </Link>
                        </Button>
                        <div className="flex items-center space-x-3">
                            <div className={`p-2 rounded-lg ${categoryInfo.color}`}>
                                <CategoryIcon className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight dark:text-gray-100">{categoryInfo.title}</h1>
                                <p className="text-muted-foreground">
                                    {articles.length} Artikel verfügbar
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Articles */}
                {articles.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Artikel</CardTitle>
                            <CardDescription>
                                Hier finden Sie detaillierte Anleitungen und Informationen zu {categoryInfo.title.toLowerCase()}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Accordion type="single" collapsible className="w-full">
                                {articles.map((article, index) => (
                                    <AccordionItem key={article.id} value={`article-${article.id}`}>
                                        <AccordionTrigger className="text-left font-semibold">
                                            {article.title}
                                        </AccordionTrigger>
                                        <AccordionContent className="text-muted-foreground whitespace-pre-line">
                                            {article.content}
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center py-8">
                                <Info className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">Keine Artikel gefunden</h3>
                                <p className="text-muted-foreground">
                                    Für diese Kategorie sind noch keine Artikel verfügbar.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Schnellaktionen</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href="/help">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Zurück zur Hilfe-Übersicht
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/help?tab=faq">
                                    <HelpCircle className="mr-2 h-4 w-4" />
                                    FAQ anzeigen
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}

