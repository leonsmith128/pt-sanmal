<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EmployeeNumberGenerator
{
    private const SEQUENCE_ID = 1;

    /**
     * Return the next available number for display without reserving it.
     */
    public function preview(): string
    {
        $nextNumber = DB::table('employee_number_sequences')
            ->where('id', self::SEQUENCE_ID)
            ->value('next_number');

        if ($nextNumber === null) {
            throw new LogicException('The employee number sequence has not been initialized. Run database migrations first.');
        }

        return $this->format($this->nextAvailableNumber((int) $nextNumber));
    }

    /**
     * Allocate a number atomically. Callers should create the Employee in the
     * same outer transaction so a failed creation also rolls back this counter.
     */
    public function next(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('employee_number_sequences')
                ->where('id', self::SEQUENCE_ID)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new LogicException('The employee number sequence has not been initialized. Run database migrations first.');
            }

            $number = $this->nextAvailableNumber((int) $sequence->next_number);

            DB::table('employee_number_sequences')
                ->where('id', self::SEQUENCE_ID)
                ->update([
                    'next_number' => $number + 1,
                    'updated_at' => now(),
                ]);

            return $this->format($number);
        });
    }

    private function nextAvailableNumber(int $number): int
    {
        while (Employee::query()->where('employee_number', $this->format($number))->exists()) {
            $number++;
        }

        return $number;
    }

    private function format(int $number): string
    {
        return 'EMP-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }
}
