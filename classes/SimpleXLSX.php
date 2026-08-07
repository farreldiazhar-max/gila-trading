<?php

class SimpleXLSX
{
    private $zip;
    private $sharedStrings = [];
    private $sheets = [];

    public static function parse($filename)
    {
        $xlsx = new self();
        if (!file_exists($filename) || !class_exists('ZipArchive')) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            return null;
        }
        $xlsx->zip = $zip;

        $workbookXml = $xlsx->getXml('xl/workbook.xml');
        if ($workbookXml === null) {
            $xlsx->zip->close();
            return null;
        }

        $xlsx->parseSharedStrings();
        $xlsx->parseSheets($workbookXml);
        return $xlsx;
    }

    private function getXml($path)
    {
        $index = $this->zip->locateName($path);
        if ($index === false) {
            return null;
        }
        return $this->zip->getFromIndex($index);
    }

    private function parseSharedStrings()
    {
        $xml = $this->getXml('xl/sharedStrings.xml');
        if ($xml === null) {
            return;
        }
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        foreach ($dom->getElementsByTagName('si') as $si) {
            $text = '';
            foreach ($si->childNodes as $child) {
                if ($child->nodeName === 't') {
                    $text .= $child->nodeValue;
                } elseif ($child->nodeName === 'r') {
                    foreach ($child->getElementsByTagName('t') as $t) {
                        $text .= $t->nodeValue;
                    }
                }
            }
            $this->sharedStrings[] = $text;
        }
    }

    private function parseSheets($workbookXml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($workbookXml);
        $sheetElements = $dom->getElementsByTagName('sheet');
        foreach ($sheetElements as $sheet) {
            $name = $sheet->getAttribute('name');
            $rid = $sheet->getAttribute('r:id');
            $path = $this->findWorksheetPath($rid);
            if ($path) {
                $this->sheets[] = ['name' => $name, 'path' => $path];
            }
        }
    }

    private function findWorksheetPath($rid)
    {
        $relsXml = $this->getXml('xl/_rels/workbook.xml.rels');
        if ($relsXml === null) {
            return null;
        }
        $dom = new DOMDocument();
        $dom->loadXML($relsXml);
        foreach ($dom->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('Id') === $rid) {
                $target = $rel->getAttribute('Target');
                return 'xl/' . ltrim($target, '/');
            }
        }
        return null;
    }

    public function rows($sheetIndex = 0)
    {
        if (!isset($this->sheets[$sheetIndex])) {
            return [];
        }
        $xml = $this->getXml($this->sheets[$sheetIndex]['path']);
        if ($xml === null) {
            return [];
        }
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $rows = [];
        foreach ($dom->getElementsByTagName('row') as $row) {
            $currentRow = [];
            foreach ($row->getElementsByTagName('c') as $c) {
                $cellType = $c->getAttribute('t');
                $value = '';
                $v = $c->getElementsByTagName('v')->item(0);
                if ($v) {
                    $value = $v->nodeValue;
                    if ($cellType === 's') {
                        $idx = intval($value);
                        $value = $this->sharedStrings[$idx] ?? $value;
                    }
                }
                $currentRow[] = $value;
            }
            $rows[] = $currentRow;
        }
        return $rows;
    }
}
