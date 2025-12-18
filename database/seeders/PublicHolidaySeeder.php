<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use Illuminate\Database\Seeder;

/**
 * GLO-007: Public Holiday Seeder
 *
 * Seeds initial public holidays for major markets to ensure
 * the system works without requiring external API calls.
 *
 * Note: For production, use the holidays:sync command to fetch
 * complete and up-to-date holiday data from external APIs.
 */
class PublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding public holidays for major markets...');

        $year = now()->year;

        // Seed holidays for each major market
        $this->seedUSHolidays($year);
        $this->seedUKHolidays($year);
        $this->seedGermanyHolidays($year);
        $this->seedFranceHolidays($year);
        $this->seedAustraliaHolidays($year);
        $this->seedCanadaHolidays($year);
        $this->seedMaltaHolidays($year);
        $this->seedIrelandHolidays($year);
        $this->seedNetherlandsHolidays($year);
        $this->seedSpainHolidays($year);

        $count = PublicHoliday::count();
        $this->command->info("Seeded {$count} public holidays.");
    }

    /**
     * Seed US Federal Holidays
     */
    protected function seedUSHolidays(int $year): void
    {
        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 1, 1, 3), 'name' => 'Martin Luther King Jr. Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 2, 1, 3), 'name' => "Presidents' Day", 'type' => 'public'],
            ['date' => $this->getLastWeekdayOfMonth($year, 5, 1), 'name' => 'Memorial Day', 'type' => 'public'],
            ['date' => "{$year}-06-19", 'name' => 'Juneteenth', 'type' => 'public'],
            ['date' => "{$year}-07-04", 'name' => 'Independence Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 9, 1, 1), 'name' => 'Labor Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 10, 1, 2), 'name' => 'Columbus Day', 'type' => 'public'],
            ['date' => "{$year}-11-11", 'name' => 'Veterans Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 11, 4, 4), 'name' => 'Thanksgiving Day', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'US',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed UK Bank Holidays
     */
    protected function seedUKHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'bank'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'type' => 'bank'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'type' => 'bank'],
            ['date' => $this->getNthWeekdayOfMonth($year, 5, 1, 1), 'name' => 'Early May Bank Holiday', 'type' => 'bank'],
            ['date' => $this->getLastWeekdayOfMonth($year, 5, 1), 'name' => 'Spring Bank Holiday', 'type' => 'bank'],
            ['date' => $this->getLastWeekdayOfMonth($year, 8, 1), 'name' => 'Summer Bank Holiday', 'type' => 'bank'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'bank'],
            ['date' => "{$year}-12-26", 'name' => 'Boxing Day', 'type' => 'bank'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'GB',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Germany Holidays
     */
    protected function seedGermanyHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'local_name' => 'Neujahr', 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'local_name' => 'Karfreitag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'local_name' => 'Ostermontag', 'type' => 'public'],
            ['date' => "{$year}-05-01", 'name' => 'Labour Day', 'local_name' => 'Tag der Arbeit', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(39)->toDateString(), 'name' => 'Ascension Day', 'local_name' => 'Christi Himmelfahrt', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(50)->toDateString(), 'name' => 'Whit Monday', 'local_name' => 'Pfingstmontag', 'type' => 'public'],
            ['date' => "{$year}-10-03", 'name' => 'German Unity Day', 'local_name' => 'Tag der Deutschen Einheit', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'local_name' => 'Erster Weihnachtstag', 'type' => 'public'],
            ['date' => "{$year}-12-26", 'name' => 'Second Day of Christmas', 'local_name' => 'Zweiter Weihnachtstag', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'DE',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'local_name' => $holiday['local_name'] ?? null,
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed France Holidays
     */
    protected function seedFranceHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'local_name' => 'Jour de l\'an', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'local_name' => 'Lundi de Paques', 'type' => 'public'],
            ['date' => "{$year}-05-01", 'name' => 'Labour Day', 'local_name' => 'Fete du Travail', 'type' => 'public'],
            ['date' => "{$year}-05-08", 'name' => 'Victory in Europe Day', 'local_name' => 'Fete de la Victoire', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(39)->toDateString(), 'name' => 'Ascension Day', 'local_name' => 'Ascension', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(50)->toDateString(), 'name' => 'Whit Monday', 'local_name' => 'Lundi de Pentecote', 'type' => 'public'],
            ['date' => "{$year}-07-14", 'name' => 'Bastille Day', 'local_name' => 'Fete nationale', 'type' => 'public'],
            ['date' => "{$year}-08-15", 'name' => 'Assumption Day', 'local_name' => 'Assomption', 'type' => 'religious'],
            ['date' => "{$year}-11-01", 'name' => "All Saints' Day", 'local_name' => 'Toussaint', 'type' => 'religious'],
            ['date' => "{$year}-11-11", 'name' => 'Armistice Day', 'local_name' => 'Armistice', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'local_name' => 'Noel', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'FR',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'local_name' => $holiday['local_name'] ?? null,
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Australia Holidays
     */
    protected function seedAustraliaHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'public'],
            ['date' => "{$year}-01-26", 'name' => 'Australia Day', 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDay()->toDateString(), 'name' => 'Easter Saturday', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'type' => 'public'],
            ['date' => "{$year}-04-25", 'name' => 'ANZAC Day', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'public'],
            ['date' => "{$year}-12-26", 'name' => 'Boxing Day', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'AU',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Canada Holidays
     */
    protected function seedCanadaHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'type' => 'public'],
            ['date' => $this->getMondayBeforeOrOn($year, 5, 25), 'name' => 'Victoria Day', 'type' => 'public'],
            ['date' => "{$year}-07-01", 'name' => 'Canada Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 9, 1, 1), 'name' => 'Labour Day', 'type' => 'public'],
            ['date' => "{$year}-09-30", 'name' => 'National Day for Truth and Reconciliation', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 10, 1, 2), 'name' => 'Thanksgiving', 'type' => 'public'],
            ['date' => "{$year}-11-11", 'name' => 'Remembrance Day', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'CA',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Malta Holidays (original hardcoded list)
     */
    protected function seedMaltaHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'public'],
            ['date' => "{$year}-02-10", 'name' => "St. Paul's Shipwreck", 'type' => 'religious'],
            ['date' => "{$year}-03-19", 'name' => "St. Joseph's Day", 'type' => 'religious'],
            ['date' => "{$year}-03-31", 'name' => 'Freedom Day', 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'type' => 'religious'],
            ['date' => "{$year}-05-01", 'name' => "Worker's Day", 'type' => 'public'],
            ['date' => "{$year}-06-07", 'name' => 'Sette Giugno', 'type' => 'public'],
            ['date' => "{$year}-06-29", 'name' => 'St. Peter & St. Paul', 'type' => 'religious'],
            ['date' => "{$year}-08-15", 'name' => 'Assumption Day', 'type' => 'religious'],
            ['date' => "{$year}-09-08", 'name' => 'Our Lady of Victories', 'type' => 'religious'],
            ['date' => "{$year}-09-21", 'name' => 'Independence Day', 'type' => 'public'],
            ['date' => "{$year}-12-08", 'name' => 'Immaculate Conception', 'type' => 'religious'],
            ['date' => "{$year}-12-13", 'name' => 'Republic Day', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'MT',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Ireland Holidays
     */
    protected function seedIrelandHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'type' => 'public'],
            ['date' => "{$year}-02-01", 'name' => "St. Brigid's Day", 'type' => 'public'],
            ['date' => "{$year}-03-17", 'name' => "St. Patrick's Day", 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 5, 1, 1), 'name' => 'May Day', 'type' => 'public'],
            ['date' => $this->getNthWeekdayOfMonth($year, 6, 1, 1), 'name' => 'June Bank Holiday', 'type' => 'bank'],
            ['date' => $this->getNthWeekdayOfMonth($year, 8, 1, 1), 'name' => 'August Bank Holiday', 'type' => 'bank'],
            ['date' => $this->getLastWeekdayOfMonth($year, 10, 1), 'name' => 'October Bank Holiday', 'type' => 'bank'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'type' => 'public'],
            ['date' => "{$year}-12-26", 'name' => "St. Stephen's Day", 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'IE',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Netherlands Holidays
     */
    protected function seedNetherlandsHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'local_name' => 'Nieuwjaarsdag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'local_name' => 'Goede Vrijdag', 'type' => 'observance'],
            ['date' => $easterSunday->toDateString(), 'name' => 'Easter Sunday', 'local_name' => 'Eerste Paasdag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDay()->toDateString(), 'name' => 'Easter Monday', 'local_name' => 'Tweede Paasdag', 'type' => 'public'],
            ['date' => "{$year}-04-27", 'name' => "King's Day", 'local_name' => 'Koningsdag', 'type' => 'public'],
            ['date' => "{$year}-05-05", 'name' => 'Liberation Day', 'local_name' => 'Bevrijdingsdag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(39)->toDateString(), 'name' => 'Ascension Day', 'local_name' => 'Hemelvaartsdag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(49)->toDateString(), 'name' => 'Whit Sunday', 'local_name' => 'Eerste Pinksterdag', 'type' => 'public'],
            ['date' => $easterSunday->copy()->addDays(50)->toDateString(), 'name' => 'Whit Monday', 'local_name' => 'Tweede Pinksterdag', 'type' => 'public'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'local_name' => 'Eerste Kerstdag', 'type' => 'public'],
            ['date' => "{$year}-12-26", 'name' => 'Second Day of Christmas', 'local_name' => 'Tweede Kerstdag', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'NL',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'local_name' => $holiday['local_name'] ?? null,
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Seed Spain Holidays
     */
    protected function seedSpainHolidays(int $year): void
    {
        $easterSunday = $this->getEasterSunday($year);

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day", 'local_name' => 'Ano Nuevo', 'type' => 'public'],
            ['date' => "{$year}-01-06", 'name' => 'Epiphany', 'local_name' => 'Dia de Reyes', 'type' => 'religious'],
            ['date' => $easterSunday->copy()->subDays(2)->toDateString(), 'name' => 'Good Friday', 'local_name' => 'Viernes Santo', 'type' => 'public'],
            ['date' => "{$year}-05-01", 'name' => 'Labour Day', 'local_name' => 'Dia del Trabajador', 'type' => 'public'],
            ['date' => "{$year}-08-15", 'name' => 'Assumption Day', 'local_name' => 'Asuncion de la Virgen', 'type' => 'religious'],
            ['date' => "{$year}-10-12", 'name' => 'National Day', 'local_name' => 'Fiesta Nacional de Espana', 'type' => 'public'],
            ['date' => "{$year}-11-01", 'name' => "All Saints' Day", 'local_name' => 'Todos los Santos', 'type' => 'religious'],
            ['date' => "{$year}-12-06", 'name' => 'Constitution Day', 'local_name' => 'Dia de la Constitucion', 'type' => 'public'],
            ['date' => "{$year}-12-08", 'name' => 'Immaculate Conception', 'local_name' => 'Inmaculada Concepcion', 'type' => 'religious'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day', 'local_name' => 'Navidad', 'type' => 'public'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'country_code' => 'ES',
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                ],
                [
                    'local_name' => $holiday['local_name'] ?? null,
                    'is_national' => true,
                    'is_observed' => true,
                    'type' => $holiday['type'],
                    'surge_multiplier' => 1.50,
                ]
            );
        }
    }

    /**
     * Calculate Easter Sunday date using Anonymous Gregorian algorithm
     */
    protected function getEasterSunday(int $year): \Carbon\Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return \Carbon\Carbon::createFromDate($year, $month, $day);
    }

    /**
     * Get the Nth occurrence of a weekday in a month
     * $weekday: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
     */
    protected function getNthWeekdayOfMonth(int $year, int $month, int $weekday, int $n): string
    {
        $firstDay = \Carbon\Carbon::createFromDate($year, $month, 1);
        $diff = ($weekday - $firstDay->dayOfWeek + 7) % 7;
        $date = $firstDay->addDays($diff)->addWeeks($n - 1);

        return $date->toDateString();
    }

    /**
     * Get the last occurrence of a weekday in a month
     */
    protected function getLastWeekdayOfMonth(int $year, int $month, int $weekday): string
    {
        $lastDay = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $diff = ($lastDay->dayOfWeek - $weekday + 7) % 7;
        $date = $lastDay->subDays($diff);

        return $date->toDateString();
    }

    /**
     * Get the Monday before or on a specific date (for Victoria Day)
     */
    protected function getMondayBeforeOrOn(int $year, int $month, int $day): string
    {
        $date = \Carbon\Carbon::createFromDate($year, $month, $day);
        $dayOfWeek = $date->dayOfWeek;

        // Monday = 1
        if ($dayOfWeek === 1) {
            return $date->toDateString();
        }

        // Go back to the previous Monday
        $daysToSubtract = ($dayOfWeek === 0) ? 6 : $dayOfWeek - 1;

        return $date->subDays($daysToSubtract)->toDateString();
    }
}
