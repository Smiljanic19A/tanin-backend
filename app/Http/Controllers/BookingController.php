<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class BookingController extends Controller
{
    /**
     * Display a listing of bookings with optional filters.
     *
     * Filters:
     * - status: Filter by status (0=pending, 1=accepted, 2=declined)
     * - date: Filter by exact date (Y-m-d)
     * - date_from: Filter by start date (Y-m-d)
     * - date_to: Filter by end date (Y-m-d)
     * - reservation_type: Filter by type (dining, drinks, both)
     * - per_page: Number of results per page (default: 15, max: 100)
     * - page: Page number
     */
    public function index(IndexBookingRequest $request): JsonResponse
    {
        $query = Booking::query();

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        // Apply exact date filter
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        // Apply reservation type filter
        if ($request->filled('reservation_type')) {
            $query->where('reservation_type', $request->input('reservation_type'));
        }

        // Order by date ascending (soonest first), then by time
        $query->orderBy('date', 'asc')->orderBy('time', 'asc');

        // Paginate results
        $perPage = $request->integer('per_page', 15);
        $bookings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created booking.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = Booking::create([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'guests' => $request->integer('guests'),
            'reservation_type' => $request->input('reservation_type'),
            'phone' => $request->input('phone'),
            'status' => Booking::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $booking,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified booking.
     */
    public function show(int $id): JsonResponse
    {
        $booking = Booking::find($id);

        if ($booking === null) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * Approve a pending booking.
     *
     * Uses database transaction with row-level locking to prevent race conditions.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $booking = DB::transaction(function () use ($id): Booking {
                // Lock the row for update to prevent concurrent modifications
                $booking = Booking::lockForUpdate()->find($id);

                if ($booking === null) {
                    throw new \RuntimeException('Booking not found.', Response::HTTP_NOT_FOUND);
                }

                if (!$booking->isPending()) {
                    throw new \RuntimeException(
                        'Booking has already been processed. Current status: ' . $this->getStatusLabel($booking->status),
                        Response::HTTP_CONFLICT
                    );
                }

                $booking->update(['status' => Booking::STATUS_ACCEPTED]);

                return $booking;
            });

            return response()->json([
                'success' => true,
                'message' => 'Booking approved successfully.',
                'data' => $booking->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Decline a pending booking.
     *
     * Uses database transaction with row-level locking to prevent race conditions.
     */
    public function decline(int $id): JsonResponse
    {
        try {
            $booking = DB::transaction(function () use ($id): Booking {
                // Lock the row for update to prevent concurrent modifications
                $booking = Booking::lockForUpdate()->find($id);

                if ($booking === null) {
                    throw new \RuntimeException('Booking not found.', Response::HTTP_NOT_FOUND);
                }

                if (!$booking->isPending()) {
                    throw new \RuntimeException(
                        'Booking has already been processed. Current status: ' . $this->getStatusLabel($booking->status),
                        Response::HTTP_CONFLICT
                    );
                }

                $booking->update(['status' => Booking::STATUS_DECLINED]);

                return $booking;
            });

            return response()->json([
                'success' => true,
                'message' => 'Booking declined successfully.',
                'data' => $booking->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => 'pending',
            Booking::STATUS_ACCEPTED => 'accepted',
            Booking::STATUS_DECLINED => 'declined',
            default => 'unknown',
        };
    }
}

