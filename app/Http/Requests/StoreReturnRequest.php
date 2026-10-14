<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'borrowing_id' => ['required', 'exists:borrowings,id'],
            'returned_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
