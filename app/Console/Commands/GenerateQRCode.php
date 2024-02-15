<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Endroid\QrCode\QrCode;

class GenerateQRCode extends Command
{
    protected $signature = 'generate:qr';

    protected $description = 'Generate QR codes for data in Excel file';

    public function handle()
    {
        // Read data from Excel file
        $data = Excel::toArray(new YourImportClass(), public_path('path/to/your/excel.xlsx'));

        foreach ($data as $row) {
            foreach ($row as $entry) {
                $qrCode = new QrCode($entry['data']); // Adjust as per your Excel structure

                // Save QR code as image
                $qrCode->writeFile(public_path('qr_codes/'.$entry['filename'].'.png'));
            }
        }

        $this->info('QR codes generated successfully.');
    }
}
