<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KolkataMumineen;
use Illuminate\Support\Facades\DB;

class KolkataMumineenController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));

        $header = array_shift($data); // Extract header row
        $rows = array_map(function ($row) use ($header) {
            return array_combine($header, $row);
        }, $data);

        foreach ($rows as $row) {
            KolkataMumineen::updateOrCreate(
                ['id' => $row['id']], // Unique column
                [
                    'year' => $row['year'],
                    'event' => $row['event'],
                    'type' => $row['type'],
                    'family_id' => $row['family_id'],
                    'title' => $row['title'] ?? null,
                    'name' => $row['name'] ?? null,
                    'its' => $row['its'] ?? null,
                    'hof_id' => $row['hof_id'] ?? null,
                    'family_its_id' => $row['family_its_id'] ?? null,
                    'mobile' => $row['mobile'] ?? null,
                    'email' => $row['email'] ?? null,
                    'address' => $row['address'] ?? null,
                    'building' => $row['building'] ?? null,
                    'delivery_person' => $row['delivery_person'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'dob' => $row['dob'],
                    'folio_no' => $row['folio_no'] ?? null,
                    'hub' => $row['hub'] ?? null,
                    'zabihat' => $row['zabihat'] ?? null,
                    'prev_tanzeem' => $row['prev_tanzeem'] ?? null,
                    'sector' => $row['sector'] ?? null,
                    'sub_sector' => $row['sub_sector'] ?? null,
                    'is_taking_thali' => $row['is_taking_thali'],
                    'status' => $row['status'],
                    'log_user' => $row['log_user'],
                    'log_date' => $row['log_date'],
                ]
            );
        }

        return response()->json(['message' => 'Data imported successfully.']);
    }
}