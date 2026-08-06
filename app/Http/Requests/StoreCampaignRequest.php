<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth is handled by sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "start_date" => ["nullable", "date"],
            "end_date" => ["nullable", "date", "after_or_equal:start_date"],
            "deadline" => ["nullable", "date"],
            "status" => ["required", new Enum(CampaignStatus::class)],
            "pic_id" => ["nullable", "exists:users,id"],
            "brand_id" => ["required", "uuid", "exists:brands,id"], // Ditambahkan
        ];
    }
}

