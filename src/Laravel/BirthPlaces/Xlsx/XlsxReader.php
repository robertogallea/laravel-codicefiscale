<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Xlsx;

/**
 * Minimal xlsx reader for a single flat sheet with no formulas or
 * styling - just enough to read a government-published data table
 * (ZipArchive + the raw sharedStrings.xml/sheet1.xml XML), avoiding a
 * heavy spreadsheet library dependency for that one shape of file.
 */
final class XlsxReader
{
    private const NAMESPACE_URI = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @return list<list<string>> one row per array, one cell per entry, columns left-padded with '' for gaps */
    public function read(string $xlsxContents): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');

        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create a temporary file to read the xlsx contents.');
        }

        file_put_contents($tempFile, $xlsxContents);

        try {
            return $this->readFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    /** @return list<list<string>> */
    private function readFile(string $path): array
    {
        $zip = new \ZipArchive();
        $zip->open($path);

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('xlsx file has no xl/worksheets/sheet1.xml.');
        }

        return $this->parseSheet($sheetXml, $sharedStrings);
    }

    /** @return list<string> */
    private function readSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach ($this->query(new \SimpleXMLElement($xml), '//a:si') as $si) {
            $strings[] = implode('', array_map(strval(...), $this->query($si, './/a:t')));
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string>>
     */
    private function parseSheet(string $sheetXml, array $sharedStrings): array
    {
        $document = new \SimpleXMLElement($sheetXml);

        $rows = [];
        foreach ($this->query($document, '//a:row') as $rowElement) {
            /** @var array<int, string> $row */
            $row = [];
            foreach ($this->query($rowElement, 'a:c') as $cell) {
                $column = $this->columnIndex((string) $cell['r']);
                $row[$column] = $this->cellValue($cell, $sharedStrings);
            }

            $lastColumn = $row === [] ? -1 : max(array_keys($row));
            $rows[] = array_map(
                static fn (int $i): string => $row[$i] ?? '',
                range(0, $lastColumn)
            );
        }

        return $rows;
    }

    /**
     * xpath() on a SimpleXMLElement needs the namespace prefix
     * registered on that specific object - it does not propagate to
     * elements obtained via a previous xpath()/iteration - and can
     * return false; this centralizes both concerns.
     *
     * @return array<\SimpleXMLElement>
     */
    private function query(\SimpleXMLElement $element, string $expression): array
    {
        $element->registerXPathNamespace('a', self::NAMESPACE_URI);

        return $element->xpath($expression) ?: [];
    }

    /** @param list<string> $sharedStrings */
    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $value = (string) $cell->v;

        if ((string) $cell['t'] === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    /** Converts a cell reference like "AB12" into its 0-indexed column number. */
    private function columnIndex(string $cellReference): int
    {
        $letters = preg_replace('/[0-9]/', '', $cellReference) ?? '';

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - ord('A') + 1);
        }

        return $index - 1;
    }
}
