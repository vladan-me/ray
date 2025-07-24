<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Csv\Reader;
use League\Csv\Info;
use League\Csv\Statement;
use League\Csv\Writer;
use SplTempFileObject;

class CsvProcessorController extends Controller
{
    // Method to display the upload form
    public function showUploadForm()
    {
        return view('upload');
    }

    // Method to process the uploaded file
    public function processCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $csv = Reader::createFromPath($path, 'r');

        $possibleDelimiters = [
            ',',
            '|',
            "\t",
            ';',
        ];

        $stats = Info::getDelimiterStats($csv, $possibleDelimiters, 2);
        arsort($stats);
        $delimiter = array_keys($stats)[0];

        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0);

        // Prepare a new CSV in memory to write the cleaned data
        $newCsv = Writer::createFromFileObject(new SplTempFileObject());
        $newCsv->insertOne($csv->getHeader()); // Add header row

        $records = $csv->getRecords();

        foreach ($records as $record) {
            $record = $this->cleanRecord($record);
            $newCsv->insertOne($record);
        }

        return response((string) $newCsv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Format 1.csv"',
        ]);
    }

    private function cleanRecord(array $record): array
    {
        // Columns with multiple phone numbers and corresponding DNC status
        $phoneFields = [
            'DIRECT_NUMBER',
            'MOBILE_PHONE',
            'PERSONAL_PHONE',
        ];

        foreach ($phoneFields as $field) {
            $dncField = $field . '_DNC';

            // If the phone number or DNC data is missing, we can't proceed. Clear both.
            if (empty($record[$field]) || empty($record[$dncField])) {
                $record[$field] = '';
                $record[$dncField] = '';
                continue;
            }

            $phoneNumbers = array_map('trim', explode(',', $record[$field]));
            $dncFlags = array_map('trim', explode(',', $record[$dncField]));

            $numberToKeep = null;
            $dncToKeep = null;

            // Find the first number with a corresponding 'N' DNC flag
            foreach ($dncFlags as $index => $flag) {
                if (strtoupper($flag) === 'N' && isset($phoneNumbers[$index])) {
                    $numberToKeep = $phoneNumbers[$index];
                    $dncToKeep = 'N';
                    break; // Found our number, no need to look further
                }
            }

            // If we found a number with an 'N' flag, keep it. Otherwise, clear the fields.
            if ($numberToKeep !== null) {
                $record[$field] = str_replace(' ', '', $numberToKeep);
                $record[$dncField] = $dncToKeep;
            } else {
                $record[$field] = '';
                $record[$dncField] = '';
            }
        }

        // Clean email fields: keep only the first one
        $emailFields = [
            'PERSONAL_EMAILS',
            'BUSINESS_EMAIL',
            'SHA256_PERSONAL_EMAIL',
            'SHA256_BUSINESS_EMAIL',
        ];

        foreach ($emailFields as $field) {
            if (!empty($record[$field])) {
                $values = array_map('trim', explode(',', $record[$field]));
                $record[$field] = $values[0];
            }
        }

        return $record;
    }
}
