<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PrivateReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrivateReservationRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'event_type' => ['required', 'string', Rule::in(PrivateReservation::getEventTypes())],
            'people_range' => ['required', 'string', Rule::in(PrivateReservation::getPeopleRanges())],
            'budget' => ['required', 'string', Rule::in(PrivateReservation::getBudgetRanges())],
            'message' => ['nullable', 'string', 'max:2000'],
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
            'event_type.in' => 'Invalid event type. Must be: birthday, anniversary, corporate, wedding, or other.',
            'people_range.in' => 'Invalid people range. Must be: under10, 10to30, 30to50, or over50.',
            'budget.in' => 'Invalid budget range. Must be: under1000, 1000to3000, 3000to5000, 5000to10000, or over10000.',
            'message.max' => 'The message cannot exceed 2000 characters.',
        ];
    }
}

