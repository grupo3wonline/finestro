<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Excel;

/**
 * Neutralises spreadsheet formula injection in externally supplied text.
 *
 * XLSX: the export extends StringValueBinder, which stores every cell with
 * setValueExplicit(TYPE_STRING) (formula conversion stays on), so a value
 * starting with "=" is written as plain text and never evaluated. The text
 * is kept as-is there, without a visible guard character.
 *
 * CSV has no cell types, so a spreadsheet evaluates a value starting with
 * =, +, -, @, tab or CR. In the CSV writer such values get a leading
 * apostrophe (OWASP CSV injection guidance).
 *
 * The using class must implement writerType() returning the
 * Maatwebsite\Excel\Excel writer constant the export is written with.
 */
trait EscapesFormulaInjection
{
    /**
     * Maatwebsite\Excel\Excel writer constant the export is written with.
     */
    abstract protected function writerType(): string;

    protected function text(?string $value): string
    {
        $value = (string) $value;

        if ($this->writerType() !== Excel::CSV) {
            return $value;
        }

        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }
}
