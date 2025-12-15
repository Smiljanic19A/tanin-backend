<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Booking extends Model
{
    /**
     * Status constants
     */
    public const STATUS_PENDING = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_DECLINED = 2;

    /**
     * Reservation type constants
     */
    public const TYPE_DINING = 'dining';
    public const TYPE_DRINKS = 'drinks';
    public const TYPE_BOTH = 'both';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'time',
        'guests',
        'reservation_type',
        'phone',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'guests' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get all valid statuses.
     *
     * @return array<int, int>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
        ];
    }

    /**
     * Get all valid reservation types.
     *
     * @return array<int, string>
     */
    public static function getReservationTypes(): array
    {
        return [
            self::TYPE_DINING,
            self::TYPE_DRINKS,
            self::TYPE_BOTH,
        ];
    }

    /**
     * Check if the booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the booking is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the booking is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }
}

