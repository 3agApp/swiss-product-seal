<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductDocumentStoreRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
                'max:20480',
            ],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'expiry_date' => ['nullable', 'date'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
            'duplicate_strategy' => ['nullable', 'string', Rule::in(['add_new', 'replace_existing'])],
            'replace_document_id' => ['nullable', 'integer'],
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

            if ($this->input('duplicate_strategy', 'add_new') !== 'replace_existing') {
                return;
            }

            $product = $this->route('product');

            if (! $product instanceof Product) {
                return;
            }

            $currentDocuments = $product->currentDocuments()
                ->where('type', $this->string('type')->toString())
                ->get();

            if ($currentDocuments->isEmpty()) {
                $validator->errors()->add(
                    'duplicate_strategy',
                    'There is no current document of this type to replace.',
                );

                return;
            }

            if ($this->filled('replace_document_id')) {
                $replaceDocumentId = (int) $this->input('replace_document_id');

                if (! $currentDocuments->contains('id', $replaceDocumentId)) {
                    $validator->errors()->add(
                        'replace_document_id',
                        'Select a current document of the same type to replace.',
                    );
                }

                return;
            }

            if ($currentDocuments->count() > 1) {
                $validator->errors()->add(
                    'replace_document_id',
                    'Select which current document should be replaced.',
                );
            }
        }];
    }
}
