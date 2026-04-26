<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AffiliateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_required' => ['required', 'boolean'],
            'default_commission_type' => ['required', 'in:percentage,fixed'],
            'default_commission_value' => ['required', 'numeric', 'min:0'],
            'cookie_window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'minimum_payout_amount' => ['required', 'numeric', 'min:0'],
            'payout_methods_text' => ['required', 'string', 'max:3000'],
            'terms_text' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->parsedPayoutMethods() === []) {
                $validator->errors()->add('payout_methods_text', 'Add at least one payout method using code=Label.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'default_commission_type.in' => 'Choose Percentage or Fixed commission.',
            'cookie_window_days.max' => 'Cookie window cannot be more than 365 days.',
            'payout_methods_text.required' => 'Add at least one payout method.',
        ];
    }

    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'approval_required' => (bool) $validated['approval_required'],
            'default_commission_type' => $validated['default_commission_type'],
            'default_commission_value' => (float) $validated['default_commission_value'],
            'cookie_window_days' => (int) $validated['cookie_window_days'],
            'minimum_payout_amount' => (float) $validated['minimum_payout_amount'],
            'payout_methods' => $this->parsedPayoutMethods(),
            'terms_text' => $validated['terms_text'] ?? '',
        ];
    }

    protected function parsedPayoutMethods(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->input('payout_methods_text', '')))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->mapWithKeys(function (string $line): array {
                [$code, $label] = str_contains($line, '=')
                    ? explode('=', $line, 2)
                    : explode(':', $line, 2) + [1 => null];

                $code = str($code)->trim()->snake()->value();
                $label = trim((string) $label);

                return $code !== '' && $label !== ''
                    ? [$code => $label]
                    : [];
            })
            ->all();
    }
}
