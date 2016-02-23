<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class EnergyRequest extends Request
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
            'sensor_id' => 'required|numeric',
            'node'      => 'required|numeric',
            'instance'  => 'required|numeric',
            'value'     => 'required|numeric|min:1'
        ];
    }
}
