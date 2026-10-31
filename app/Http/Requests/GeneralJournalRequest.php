<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class GeneralJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'journal_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2', 'max:100'],
            'lines.*.chart_of_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where('is_active', 1)->where('is_header', 0),
            ],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999999999'],
            'lines.*.credit_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999999999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $debitTotal = 0;
                $creditTotal = 0;

                foreach ($this->input('lines', []) as $index => $line) {
                    $debit = self::toMinorUnits((string) $line['debit_amount']);
                    $credit = self::toMinorUnits((string) $line['credit_amount']);

                    if (($debit > 0) === ($credit > 0)) {
                        $validator->errors()->add(
                            "lines.{$index}.debit_amount",
                            'Setiap baris harus berisi nilai debit atau kredit saja.',
                        );
                    }

                    $debitTotal += $debit;
                    $creditTotal += $credit;
                }

                if ($debitTotal !== $creditTotal) {
                    $validator->errors()->add('lines', 'Total debit dan kredit jurnal harus seimbang.');
                }
            },
        ];
    }

    private static function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
