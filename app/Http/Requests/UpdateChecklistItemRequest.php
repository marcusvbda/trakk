<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'is_done' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function title(): ?string
    {
        return $this->validated('title');
    }

    public function isDone(): ?bool
    {
        return $this->has('is_done') ? $this->boolean('is_done') : null;
    }
}
