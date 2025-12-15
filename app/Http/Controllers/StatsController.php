<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PrivateReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StatsController extends Controller
{
    /**
     * People range to approximate headcount mapping.
     * Uses the midpoint or reasonable estimate for each range.
     */
    private const PEOPLE_RANGE_ESTIMATES = [
        'under10' => 5,
        '10to30' => 20,
        '30to50' => 40,
        'over50' => 60,
    ];

    /**
     * Get reservation statistics for a specific date.
     *
     * Returns:
     * - Total reservations count
     * - Total head count (sum of guests)
     * - Breakdown by bookings and private reservations
     */
    public function daily(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        $date = $request->input('date');

        // Get bookings stats for the date (only accepted reservations count toward headcount)
        $bookingsQuery = Booking::whereDate('date', $date);
        $bookingsTotal = (clone $bookingsQuery)->count();
        $bookingsAccepted = (clone $bookingsQuery)->where('status', Booking::STATUS_ACCEPTED)->count();
        $bookingsHeadcount = (clone $bookingsQuery)->where('status', Booking::STATUS_ACCEPTED)->sum('guests');

        // Get private reservations stats for the date
        $privateQuery = PrivateReservation::whereDate('date', $date);
        $privateTotal = (clone $privateQuery)->count();
        $privateAccepted = (clone $privateQuery)->where('status', PrivateReservation::STATUS_ACCEPTED)->count();

        // Calculate estimated headcount for private reservations (using range estimates)
        $privateHeadcount = 0;
        $acceptedPrivateReservations = (clone $privateQuery)
            ->where('status', PrivateReservation::STATUS_ACCEPTED)
            ->get(['people_range']);

        foreach ($acceptedPrivateReservations as $reservation) {
            $privateHeadcount += self::PEOPLE_RANGE_ESTIMATES[$reservation->people_range] ?? 0;
        }

        // Calculate totals
        $totalReservations = $bookingsTotal + $privateTotal;
        $totalAccepted = $bookingsAccepted + $privateAccepted;
        $totalHeadcount = (int) $bookingsHeadcount + $privateHeadcount;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_reservations' => $totalReservations,
                'total_accepted' => $totalAccepted,
                'total_headcount' => $totalHeadcount,
                'bookings' => [
                    'count' => $bookingsTotal,
                    'accepted' => $bookingsAccepted,
                    'headcount' => (int) $bookingsHeadcount,
                ],
                'private_reservations' => [
                    'count' => $privateTotal,
                    'accepted' => $privateAccepted,
                    'headcount_estimate' => $privateHeadcount,
                ],
            ],
        ]);
    }
}

