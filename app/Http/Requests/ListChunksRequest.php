<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListChunksRequest extends FormRequest
{
    use ChunkFormRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('read sinks');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->filterRules();
    }
}
