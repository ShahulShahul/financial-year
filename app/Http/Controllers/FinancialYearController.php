<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;

class FinancialYearController extends Controller
{
    public function index()
    {
        $countries = [
            'uk' => 'United Kingdom',
            'ireland' => 'Ireland'
        ];

        return view('home', compact('countries'));
    }

    public function getYears(Request $request)
    {
        try {
            $validated = $request->validate([
                'country'    => 'required|string|in:uk,ireland',
            ]);
            $country = $validated['country'];
            $currentYear = now()->year;
            $years = [];
            
            if($country == 'ireland') {
                for ($i = 10; $i >= 0; $i--) {
                    $year = $currentYear - $i;
                    $years[] = [
                        'value' => $year,
                        'label' => (string) $year
                    ];
                }
            }
            elseif ($country == 'uk') {
                for ($i = 10; $i >= 0; $i--) {
                    $year = $currentYear - $i;
                    $nextYearShort = substr($year + 1, -2);
                    $label = "$year-$nextYearShort";

                    $years[] = [
                        'value' => $year,
                        'label' => $label
                    ];
                }
            }
            return response()->json([
                'status'  => 200,
                'message' => 'Years fetched successfully',
                'data'    => $years
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'error'   => 'Validation error',
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
                'data'    => null
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => 'Failed to fetch years.',
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    public function financialData(Request $request)
    {
        try {
            $validated = $request->validate([
                'country' => 'required|in:uk,ireland',
                'year' => 'required|integer|min:1900|max:' . now()->year,
            ]);

            $country = $validated['country'];
            $year = $validated['year'];

            // start/end dates
            if ($country === 'ireland') {
                $startDate = \Carbon\Carbon::parse("$year-01-01");
                if ($startDate->isWeekend()) {
                    $startDate = $startDate->nextWeekday();
                }

                $endDate = \Carbon\Carbon::parse("$year-12-31");
                if ($endDate->isWeekend()) {
                    $endDate = $endDate->previousWeekday();
                }
            } 
            elseif($country == 'uk') { 
                $startDate = \Carbon\Carbon::parse(($year + 1) . '-04-06')->subYear();
                if ($startDate->isWeekend()) {
                    $startDate = $startDate->nextWeekday();
                }

                $endDate = \Carbon\Carbon::parse(($year + 1) . '-04-05');
                if ($endDate->isWeekend()) {
                    $endDate = $endDate->previousWeekday();
                }
            }

            $countryCode = $country === 'uk' ? 'GB' : 'IE';

            $yearsToFetch = [$startDate->year];
            if ($startDate->year !== $endDate->year) {
                $yearsToFetch[] = $endDate->year;
            }

            // Get holidays
            $holidays = [];
            foreach ($yearsToFetch as $apiYear) {
                $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$countryCode}");

                if ($response->ok()) {
                    $holidays = array_merge($holidays, $response->json());
                }
            }
            // Createing new set of holidays which are not in weekend
            $filteredHolidays = [];
            foreach ($holidays as $holiday) {
                $holidayDate = \Carbon\Carbon::parse($holiday['date']);
                if ($holidayDate->between($startDate, $endDate) && !$holidayDate->isWeekend()) {
                    $filteredHolidays[] = [
                        'date' => $holidayDate->toDateString(),
                        'name' => $holiday['name'],
                    ];
                }
            }
            return response()->json([
                'status'  => 200,
                'message' => 'Financial year data fetched successfully',
                'data'    => [
                    'start_date'  => $startDate->toFormattedDateString(),
                    'end_date'    => $endDate->toFormattedDateString(),
                    'holidays'    => $filteredHolidays,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'error'   => 'Validation error',
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
                'data'    => null
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => 'Failed to fetch financial year data.',
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}
