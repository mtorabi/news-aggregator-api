<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No authentication required as per requirements
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'source' => 'sometimes|array',
            'source.*' => 'string|max:100',
            'category' => 'sometimes|array',
            'category.*' => 'string|max:255',
            'author' => 'sometimes|array',
            'author.*' => 'string|max:255',
            'date_from' => 'sometimes|date|date_format:Y-m-d',
            'date_to' => 'sometimes|date|date_format:Y-m-d|after_or_equal:date_from',
            'sort_by' => 'sometimes|string|in:published_at,title,source,author,created_at',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date_from.date_format' => 'The date_from field must be in YYYY-MM-DD format.',
            'date_to.date_format' => 'The date_to field must be in YYYY-MM-DD format.',
            'date_to.after_or_equal' => 'The date_to field must be a date after or equal to date_from.',
            'sort_by.in' => 'The sort_by field must be one of: published_at, title, source, author, created_at.',
            'sort_order.in' => 'The sort_order field must be either asc or desc.',
            'per_page.max' => 'The per_page field may not be greater than 100.',
        ];
    }
}