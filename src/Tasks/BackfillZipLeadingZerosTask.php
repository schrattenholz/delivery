<?php

namespace Schrattenholz\Delivery\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Backfills German postal codes that lost their leading zero (Dresden 01067 and generally
 * Saxony/Thuringia/parts of Berlin) back when Delivery_ZIPCode::$db['Title'] was still typed
 * `Int` instead of `Varchar(10)`. The schema was already fixed and the dev DB backfilled by
 * hand on 2026-07-19 (see CLAUDE.md), but that fix never ran against production -- this task
 * packages the exact same UPDATE so it can be run as a normal step of the production rollout
 * instead of a manual SQL command someone has to remember.
 *
 * Idempotent: the WHERE clause only ever matches rows still missing their leading zero(s), so
 * running it again (e.g. on a DB that's already correct, like the current dev copy) is a no-op.
 */
class BackfillZipLeadingZerosTask extends BuildTask
{
    protected static string $commandName = 'backfill-zip-leading-zeros';

    protected string $title = 'Backfill PLZ leading zeros (Delivery_ZIPCode)';

    protected static string $description = 'Re-pads German postal codes that lost a leading '
        . 'zero from the old Int-typed column (e.g. Dresden 1067 -> 01067). Safe to run '
        . 'repeatedly -- only touches rows still under 5 digits.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $before = DB::query('SELECT COUNT(*) FROM "Delivery_ZIPCode" WHERE LENGTH("Title") < 5')->value();

        if ($before == 0) {
            $output->writeln('No Delivery_ZIPCode rows with a missing leading zero -- nothing to do.');
            return Command::SUCCESS;
        }

        $output->writeln("Found $before row(s) with a missing leading zero. Backfilling...");
        DB::query('UPDATE "Delivery_ZIPCode" SET "Title" = LPAD("Title", 5, \'0\') WHERE LENGTH("Title") < 5');

        $after = DB::query('SELECT COUNT(*) FROM "Delivery_ZIPCode" WHERE LENGTH("Title") < 5')->value();
        $output->writeln("Done. $before row(s) backfilled, $after row(s) still short (should be 0).");

        return $after == 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
