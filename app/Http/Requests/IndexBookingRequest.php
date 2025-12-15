<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'integer', Rule::in(Booking::getStatuses())],
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'reservation_type' => ['nullable', 'string', Rule::in(Booking::getReservationTypes())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Must be: 0 (pending), 1 (accepted), or 2 (declined).',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'reservation_type.in' => 'Invalid reservation type. Must be: dining, drinks, or both.',
        ];
    }
}

