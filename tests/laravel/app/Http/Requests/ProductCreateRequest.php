<?php

namespace App\Http\Requests;

use App\Rules\SuitableTags;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sku' => 'required|string|max:255|unique:products,sku',
            'name' => 'required|string|max:255',
            'brand_id' => 'required|int|exists:brands,id',
            'categories' => 'required|array|min:1',
            'categories.*' => 'int|exists:categories,id',
            'json' => 'required|array', // this should not be accessible in the docs as $json is an existing property
            'published_at' => [
                'nullable',
                'date',
            ],
            'price' => [
                'required',
                'numeric',
            ],
            'tags' => [
                new SuitableTags(),
            ],
        ];
    }
}
