<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'required_document_types' => ['present', 'array'],
            'required_document_types.*' => ['string', Rule::in(array_column(DocumentType::cases(), 'value'))],
            'optional_document_types' => ['present', 'array'],
            'optional_document_types.*' => ['string', Rule::in(array_column(DocumentType::cases(), 'value'))],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $required = $this->input('required_document_types', []);
            $optional = $this->input('optional_document_types', []);
            $overlap = array_intersect($required, $optional);

            if (count($overlap) > 0) {
                $validator->errors()->add(
                    'optional_document_types',
                    'A document type cannot be both required and optional.',
                );
            }
        }];
    }
}
