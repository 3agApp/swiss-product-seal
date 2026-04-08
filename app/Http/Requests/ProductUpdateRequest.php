<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use App\Models\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductUpdateRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'internal_article_number' => ['nullable', 'string', 'max:255'],
            'supplier_article_number' => ['nullable', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:255'],
            'ean' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'required_with:brand_id', 'integer', 'exists:suppliers,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::in(ProductStatus::cases())],
            'kontor_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('brand_id') || ! $this->filled('supplier_id')) {
                return;
            }

            $brandBelongsToSupplier = Brand::query()
                ->whereKey($this->integer('brand_id'))
                ->where('supplier_id', $this->integer('supplier_id'))
                ->exists();

            if (! $brandBelongsToSupplier) {
                $validator->errors()->add('brand_id', 'The selected brand must belong to the selected supplier.');
            }
        }];
    }
}
