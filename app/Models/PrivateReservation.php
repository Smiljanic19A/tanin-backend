<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PrivateReservation extends Model
{
    /**
     * Status constants
     */
    public const STATUS_PENDING = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_DECLINED = 2;

    /**
     * Event type constants
     */
    public const EVENT_BIRTHDAY = 'birthday';
    public const EVENT_ANNIVERSARY = 'anniversary';
    public const EVENT_CORPORATE = 'corporate';
    public const EVENT_WEDDING = 'wedding';
    public const EVENT_OTHER = 'other';

    /**
     * People range constants
     */
    public const PEOPLE_UNDER_10 = 'under10';
    public const PEOPLE_10_TO_30 = '10to30';
    public const PEOPLE_30_TO_50 = '30to50';
    public const PEOPLE_OVER_50 = 'over50';

    /**
     * Budget range constants
     */
    public const BUDGET_UNDER_1000 = 'under1000';
    public const BUDGET_1000_TO_3000 = '1000to3000';
    public const BUDGET_3000_TO_5000 = '3000to5000';
    public const BUDGET_5000_TO_10000 = '5000to10000';
    public const BUDGET_OVER_10000 = 'over10000';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'email',
        'event_type',
        'people_range',
        'budget',
        'message',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
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
     * Get all valid event types.
     *
     * @return array<int, string>
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_BIRTHDAY,
            self::EVENT_ANNIVERSARY,
            self::EVENT_CORPORATE,
            self::EVENT_WEDDING,
            self::EVENT_OTHER,
        ];
    }

    /**
     * Get all valid people ranges.
     *
     * @return array<int, string>
     */
    public static function getPeopleRanges(): array
    {
        return [
            self::PEOPLE_UNDER_10,
            self::PEOPLE_10_TO_30,
            self::PEOPLE_30_TO_50,
            self::PEOPLE_OVER_50,
        ];
    }

    /**
     * Get all valid budget ranges.
     *
     * @return array<int, string>
     */
    public static function getBudgetRanges(): array
    {
        return [
            self::BUDGET_UNDER_1000,
            self::BUDGET_1000_TO_3000,
            self::BUDGET_3000_TO_5000,
            self::BUDGET_5000_TO_10000,
            self::BUDGET_OVER_10000,
        ];
    }

    /**
     * Check if the reservation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the reservation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the reservation is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }
}

