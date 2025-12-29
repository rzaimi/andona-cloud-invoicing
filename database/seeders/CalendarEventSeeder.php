<?php

namespace Database\Seeders;

use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Company\Models\Company;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with('users')->get();

        $eventTemplates = [
            [
                'title' => 'Kundenbesuch',
                'type' => 'appointment',
                'description' => 'Besprechung mit Kunde über neues Projekt',
                'time_range' => ['09:00', '17:00'],
            ],
            [
                'title' => 'Team-Meeting',
                'type' => 'appointment',
                'description' => 'Wöchentliches Team-Meeting',
                'time_range' => ['10:00', '11:00'],
            ],
            [
                'title' => 'Projektpräsentation',
                'type' => 'appointment',
                'description' => 'Präsentation des neuen Projekts für Kunden',
                'time_range' => ['14:00', '16:00'],
            ],
            [
                'title' => 'Lagerbestand prüfen',
                'type' => 'inventory',
                'description' => 'Monatliche Überprüfung des Lagerbestands',
                'time_range' => ['09:00', '12:00'],
            ],
            [
                'title' => 'Quartalsbericht erstellen',
                'type' => 'report',
                'description' => 'Erstellung des Quartalsberichts für Geschäftsführung',
                'time_range' => ['13:00', '17:00'],
            ],
            [
                'title' => 'Lieferantengespräch',
                'type' => 'appointment',
                'description' => 'Besprechung mit Lieferanten über neue Konditionen',
                'time_range' => ['11:00', '12:30'],
            ],
            [
                'title' => 'Schulung',
                'type' => 'appointment',
                'description' => 'Schulung für neue Software',
                'time_range' => ['09:00', '13:00'],
            ],
            [
                'title' => 'Messe-Besuch',
                'type' => 'appointment',
                'description' => 'Besuch der Branchenmesse',
                'time_range' => ['10:00', '18:00'],
            ],
        ];

        foreach ($companies as $company) {
            $users = $company->users;

            if ($users->isEmpty()) {
                continue;
            }

            // Create calendar events
            for ($i = 1; $i <= 15; $i++) {
                $user = $users->random();
                $template = collect($eventTemplates)->random();
                
                // Events in the past 30 days and future 60 days
                $daysOffset = rand(-30, 60);
                $eventDate = Carbon::now()->addDays($daysOffset);
                
                // Random time within the range
                $timeStart = strtotime($template['time_range'][0]);
                $timeEnd = strtotime($template['time_range'][1]);
                $randomTime = rand($timeStart, $timeEnd);
                $time = date('H:i', $randomTime);
                
                $locations = [
                    'Büro',
                    'Kundenstandort',
                    'Online',
                    'Konferenzraum',
                    null,
                ];

                CalendarEvent::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'title' => $template['title'],
                    'type' => $template['type'],
                    'date' => $eventDate,
                    'time' => $time,
                    'description' => $template['description'],
                    'location' => collect($locations)->random(),
                    'is_recurring' => rand(0, 1) === 1 && $template['type'] === 'appointment',
                    'recurrence_pattern' => rand(0, 1) === 1 ? collect(['weekly', 'monthly'])->random() : null,
                    'recurrence_end_date' => rand(0, 1) === 1 ? $eventDate->copy()->addMonths(rand(3, 12)) : null,
                ]);
            }
        }
    }
}
