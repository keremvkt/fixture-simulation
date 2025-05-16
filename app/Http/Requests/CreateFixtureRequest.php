<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateFixtureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Set to false if you want to implement authorization checks
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'teams' => ['required', 'array', 'min:2'],
            'teams.*' => [
                'required',
                'distinct',
                function ($attribute, $value, $fail) {
                    if (!is_int($value)) {
                        $fail($this->messages()['teams.*.integer']);
                        return;
                    }

                    $teams = config('teams');
                    $teamIds = array_column($teams, 'id');

                    if (!in_array($value, $teamIds)) {
                        $fail("Team with ID $value not found.");
                    }
                }
            ]
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'teams.required' => 'Team list is required.',
            'teams.array' => 'Team list must be an array.',
            'teams.min' => 'At least :min teams are required to generate a fixture.',
            'teams.*.required' => 'Team ID values cannot be empty.',
            'teams.*.integer' => 'Team ID values must be integers.',
            'teams.*.distinct' => 'Team ID values must be unique.'
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Validation failed',
            'messages' => $validator->errors()
        ], 422));
    }
}