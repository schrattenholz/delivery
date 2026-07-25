<?php

namespace Schrattenholz\Delivery\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Seeds the PLZ/Orte reference data (Delivery_City, Delivery_ZIPCode and their relation)
 * needed for the checkout address city/ZIP lookup. Ships as a SQL dump of the data already
 * in production use (vendor/schrattenholz/delivery/data/seed-cities.sql) rather than a fresh
 * public dataset, so it matches exactly what the live site already relies on.
 *
 * Idempotent: does nothing if Delivery_City already has rows, so it's safe to run on every
 * deploy/install without risking duplicate data.
 */
class SeedCitiesTask extends BuildTask
{
    protected static string $commandName = 'seed-cities';

    protected string $title = 'Seed PLZ/Orte data (Delivery_City / Delivery_ZIPCode)';

    protected static string $description = 'Imports the bundled German postcode/city reference '
        . 'data used by the delivery address lookup. Skips if data already exists.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $existing = DB::query('SELECT COUNT(*) FROM "Delivery_City"')->value();
        if ($existing > 0) {
            $output->writeln("Delivery_City already has $existing rows -- skipping, nothing to do.");
            return Command::SUCCESS;
        }

        $file = BASE_PATH . '/vendor/schrattenholz/delivery/data/seed-cities.sql';
        if (!file_exists($file)) {
            $output->writeln("<error>Seed file not found: $file</error>");
            return Command::FAILURE;
        }

        $statements = array_filter(
            array_map('trim', file($file, FILE_IGNORE_NEW_LINES)),
            fn($line) => $line !== ''
        );

        $count = 0;
        foreach ($statements as $statement) {
            DB::query($statement);
            $count++;
        }

        $cities = DB::query('SELECT COUNT(*) FROM "Delivery_City"')->value();
        $zips = DB::query('SELECT COUNT(*) FROM "Delivery_ZIPCode"')->value();
        $output->writeln("Executed $count statement(s). Delivery_City now has $cities rows, Delivery_ZIPCode has $zips rows.");

        return Command::SUCCESS;
    }
}
