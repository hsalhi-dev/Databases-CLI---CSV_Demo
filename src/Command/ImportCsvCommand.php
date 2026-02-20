<?php

namespace App\Command;

use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;

final class ImportCsvCommand extends Command
{
    protected static $defaultName = 'app:import-csv';

    public function __construct()
    {
        parent::__construct('app:import-csv');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import a CSV file into MySQL using PDO + raw SQL.')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getArgument('file');
        if (!is_file($file)) {
            $output->writeln("<error>File not found: $file</error>");
            return Command::FAILURE;
        }

        $pdo = $this->makePdo();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // CSV
        $csv = Reader::from(new \SplFileObject($file, 'r'));
        $csv->setHeaderOffset(0);

        // CSV header names
        $sql = "
        INSERT INTO customers (
            csv_index,
            customer_id,
            first_name,
            last_name,
            company,
            city,
            country,
            phone1,
            phone2,
            email,
            subscription_date,
            website
        ) VALUES (
            :csv_index,
            :customer_id,
            :first_name,
            :last_name,
            :company,
            :city,
            :country,
            :phone1,
            :phone2,
            :email,
            :subscription_date,
            :website
        )";
        $stmt = $pdo->prepare($sql);

        $start = hrtime(true); // nanoseconds
        $inserted = 0;

        try {
            $pdo->beginTransaction();

            $rowNumber = 1; // data row counter (not header)
            foreach ($csv->getRecords() as $record) {

                // Normalize Customer Id: avoid casting empty/non-numeric to 0
                $customerIdRaw = trim((string)($record['Customer Id'] ?? ''));
                $customerId = (ctype_digit($customerIdRaw) && (int)$customerIdRaw > 0) ? (int)$customerIdRaw : null;

                // Normalize date (example: 2022-03-15 or 03/15/2022)
                $date = null;
                if (!empty($record['Subscription Date'])) {
                    $dt = new DateTime($record['Subscription Date']);
                    $date = $dt->format('Y-m-d');
                }

                $stmt->execute([
                    ':csv_index'        => (int) $record['Index'],
                    ':customer_id'     => $customerId, // use normalized value
                    ':first_name'      => $record['First Name'],
                    ':last_name'       => $record['Last Name'],
                    ':company'         => $record['Company'] ?: null,
                    ':city'            => $record['City'] ?: null,
                    ':country'         => $record['Country'] ?: null,
                    ':phone1'          => $record['Phone 1'] ?: null,
                    ':phone2'          => $record['Phone 2'] ?: null,
                    ':email'           => $record['Email'] ?: null,
                    ':subscription_date'=> $date,
                    ':website'         => $record['Website'] ?: null,
                ]);

                $inserted++;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $output->writeln("<error>Import failed: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $end = hrtime(true);
        $seconds = ($end - $start) / 1_000_000_000;

        $output->writeln("<info>Inserted rows: {$inserted}</info>");
        $output->writeln(sprintf("<info>Execution time: %.4f seconds</info>", $seconds));

        return Command::SUCCESS;
    }

    private function makePdo(): \PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: '';
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        // For large imports, this improves performance a bit:
        $options = [
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO($dsn, $user, $pass, $options);
    }
}