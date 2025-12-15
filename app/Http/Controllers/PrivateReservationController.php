<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexPrivateReservationRequest;
use App\Http\Requests\StorePrivateReservationRequest;
use App\Models\PrivateReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class PrivateReservationController extends Controller
{
    /**
     * Display a listing of private reservations with optional filters.
     *
     * Filters:
     * - status: Filter by status (0=pending, 1=accepted, 2=declined)
     * - date: Filter by exact date (Y-m-d)
     * - date_from: Filter by start date (Y-m-d)
     * - date_to: Filter by end date (Y-m-d)
     * - event_type: Filter by type (birthday, anniversary, corporate, wedding, other)
     * - per_page: Number of results per page (default: 15, max: 100)
     * - page: Page number
     */
    public function index(IndexPrivateReservationRequest $request): JsonResponse
    {
        $query = PrivateReservation::query();

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

        // Apply event type filter
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        // Order by date ascending (soonest first), then by creation time
        $query->orderBy('date', 'asc')->orderBy('created_at', 'asc');

        // Paginate results
        $perPage = $request->integer('per_page', 15);
        $reservations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reservations->items(),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    /**
     * Store a newly created private reservation.
     */
    public function store(StorePrivateReservationRequest $request): JsonResponse
    {
        $reservation = PrivateReservation::create([
            'date' => $request->input('date'),
            'email' => $request->input('email'),
            'event_type' => $request->input('event_type'),
            'people_range' => $request->input('people_range'),
            'budget' => $request->input('budget'),
            'message' => $request->input('message'),
            'status' => PrivateReservation::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Private reservation created successfully.',
            'data' => $reservation,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified private reservation.
     */
    public function show(int $id): JsonResponse
    {
        $reservation = PrivateReservation::find($id);

        if ($reservation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Private reservation not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }

    /**
     * Approve a pending private reservation.
     *
     * Uses database transaction with row-level locking to prevent race conditions.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $reservation = DB::transaction(function () use ($id): PrivateReservation {
                // Lock the row for update to prevent concurrent modifications
                $reservation = PrivateReservation::lockForUpdate()->find($id);

                if ($reservation === null) {
                    throw new \RuntimeException('Private reservation not found.', Response::HTTP_NOT_FOUND);
                }

                if (!$reservation->isPending()) {
                    throw new \RuntimeException(
                        'Private reservation has already been processed. Current status: ' . $this->getStatusLabel($reservation->status),
                        Response::HTTP_CONFLICT
                    );
                }

                $reservation->update(['status' => PrivateReservation::STATUS_ACCEPTED]);

                return $reservation;
            });

            return response()->json([
                'success' => true,
                'message' => 'Private reservation approved successfully.',
                'data' => $reservation->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Decline a pending private reservation.
     *
     * Uses database transaction with row-level locking to prevent race conditions.
     */
    public function decline(int $id): JsonResponse
    {
        try {
            $reservation = DB::transaction(function () use ($id): PrivateReservation {
                // Lock the row for update to prevent concurrent modifications
                $reservation = PrivateReservation::lockForUpdate()->find($id);

                if ($reservation === null) {
                    throw new \RuntimeException('Private reservation not found.', Response::HTTP_NOT_FOUND);
                }

                if (!$reservation->isPending()) {
                    throw new \RuntimeException(
                        'Private reservation has already been processed. Current status: ' . $this->getStatusLabel($reservation->status),
                        Response::HTTP_CONFLICT
                    );
                }

                $reservation->update(['status' => PrivateReservation::STATUS_DECLINED]);

                return $reservation;
            });

            return response()->json([
                'success' => true,
                'message' => 'Private reservation declined successfully.',
                'data' => $reservation->fresh(),
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
            PrivateReservation::STATUS_PENDING => 'pending',
            PrivateReservation::STATUS_ACCEPTED => 'accepted',
            PrivateReservation::STATUS_DECLINED => 'declined',
            default => 'unknown',
        };
    }
}

