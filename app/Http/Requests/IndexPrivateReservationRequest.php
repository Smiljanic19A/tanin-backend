<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PrivateReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPrivateReservationRequest extends FormRequest
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
            'status' => ['nullable', 'integer', Rule::in(PrivateReservation::getStatuses())],
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'event_type' => ['nullable', 'string', Rule::in(PrivateReservation::getEventTypes())],
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
            'event_type.in' => 'Invalid event type. Must be: birthday, anniversary, corporate, wedding, or other.',
        ];
    }
}

