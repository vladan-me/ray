<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Csv\Reader;
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
        $csv->setDelimiter(';');
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

            if (empty($record[$field])) {
                $record[$dncField] = ''; // Clear DNC if phone is empty
                continue;
            }

            if (!empty($record[$dncField])) {
                $phoneNumbers = array_map('trim', explode(',', $record[$field]));
                $dncFlags = array_map('trim', explode(',', $record[$dncField]));

                $foundNumber = null;
                $foundDnc = null;

                // Find the first number with a corresponding 'N' DNC flag
                foreach ($dncFlags as $index => $flag) {
                    if (strtoupper($flag) === 'N' && isset($phoneNumbers[$index])) {
                        $foundNumber = $phoneNumbers[$index];
                        $foundDnc = 'N';
                        break; // Stop after finding the first one
                    }
                }

                // If no 'N' flag number was found, just take the first number
                if ($foundNumber === null && count($phoneNumbers) > 0) {
                    $foundNumber = $phoneNumbers[0];
                    $foundDnc = $dncFlags[0] ?? 'Y';
                }

                // Clean the phone number (remove spaces)
                if ($foundNumber) {
                    $cleanedNumber = str_replace(' ', '', $foundNumber);
                    $record[$field] = $cleanedNumber;
                    $record[$dncField] = $foundDnc;
                }
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
