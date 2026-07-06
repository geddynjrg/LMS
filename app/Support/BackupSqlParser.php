<?php

namespace App\Support;

class BackupSqlParser
{
    /**
     * @return array<string, list<list<string|null>>>
     */
    public function parseInserts(string $sql): array
    {
        $result = [];
        $pattern = '/INSERT\s+INTO\s+`([^`]+)`\s+VALUES\s*(.*?);/is';

        if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return $result;
        }

        foreach ($matches as $match) {
            $table = $match[1];
            $valuesBlock = trim($match[2]);

            if ($valuesBlock === '') {
                continue;
            }

            $rows = $this->parseValueRows($valuesBlock);
            if ($rows !== []) {
                $result[$table] = array_merge($result[$table] ?? [], $rows);
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function extractInsertStatements(string $sql): array
    {
        $pattern = '/INSERT\s+INTO\s+`[^`]+`\s+VALUES\s*.*?;/is';

        if (!preg_match_all($pattern, $sql, $matches)) {
            return [];
        }

        return $matches[0];
    }

    /**
     * @return list<list<string|null>>
     */
    protected function parseValueRows(string $valuesBlock): array
    {
        $rows = [];
        $normalized = trim($valuesBlock);

        if (!str_starts_with($normalized, '(')) {
            $normalized = '(' . $normalized;
        }

        if (!str_ends_with($normalized, ')')) {
            $normalized .= ')';
        }

        $rowsRaw = preg_split('/\)\s*,\s*\(/', trim($normalized, " \t\n\r\0\x0B()"));

        foreach ($rowsRaw as $rowRaw) {
            $rowRaw = trim($rowRaw, " \t\n\r\0\x0B()");
            if ($rowRaw === '') {
                continue;
            }

            $rows[] = $this->parseTuple($rowRaw);
        }

        return $rows;
    }

    /**
     * @return list<string|null>
     */
    protected function parseTuple(string $tuple): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $stringChar = null;
        $len = strlen($tuple);

        for ($i = 0; $i < $len; $i++) {
            $char = $tuple[$i];

            if ($inString) {
                if ($char === '\\' && $i + 1 < $len) {
                    $current .= $char . $tuple[++$i];
                    continue;
                }

                if ($char === $stringChar) {
                    $inString = false;
                    $current .= $char;
                    continue;
                }

                $current .= $char;
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($char === ',') {
                $values[] = $this->normalizeValue(trim($current));
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '' || str_ends_with(rtrim($tuple), ',')) {
            $values[] = $this->normalizeValue(trim($current));
        }

        return $values;
    }

    protected function normalizeValue(string $value): ?string
    {
        if ($value === 'NULL' || $value === 'null') {
            return null;
        }

        if ((str_starts_with($value, "'") && str_ends_with($value, "'"))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }
}
