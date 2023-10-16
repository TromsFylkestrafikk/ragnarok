<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSinkRequest extends FormRequest
{
    use ChunkFormRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $operation = $this->input('operation');
        $authOp = $operation === 'importNew' ? 'import' : $operation;
        return $this->user()->can("$authOp chunks");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->operationAndFilterRules('importNew');
    }
}
