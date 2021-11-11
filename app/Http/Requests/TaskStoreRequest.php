<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
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
            
            'title'=> 'required|string',
            'type'=> 'required|string|in:common_ops,invoice_ops,custom_ops',
            'amount' => 'required_if:type,invoice_ops|array',
            'amoutt.currency' =>'required_if:type,invoice_ops|string',
            'amoutt.quantity' =>'required_if:type,invoice_ops|string',
            'country' => 'required_if:type,custom_ops|string',
            'prerequisites' => 'required|array'
            
        ];
    }
}
