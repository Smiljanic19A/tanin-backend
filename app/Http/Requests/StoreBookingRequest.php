<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBookingRequest extends FormRequest
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
            'date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'guests' => ['required', 'integer', 'min:1', 'max:10'],
            'reservation_type' => ['required', 'string', Rule::in(Booking::getReservationTypes())],
            'phone' => ['required', 'string', 'max:50'],
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
            'date.after_or_equal' => 'The reservation date must be today or a future date.',
            'time.regex' => 'The time must be in HH:MM format (e.g., 18:00).',
            'guests.min' => 'At least 1 guest is required.',
            'guests.max' => 'Maximum 10 guests allowed per booking.',
            'reservation_type.in' => 'Invalid reservation type. Must be: dining, drinks, or both.',
        ];
    }
}

