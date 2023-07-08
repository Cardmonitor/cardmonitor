<?php

namespace App\Support\Csv;

use Generator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    protected $delimiter = ";";
    protected $enclosure = '"';
    protected $escape_char = "\\";
    protected $filename;
    protected $header;
    protected $source;
    protected $callback;
    protected $collection;

    public static function parseCsv(string $filepath, string $separator = ','): Generator
    {
        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            yield str_getcsv($raw_string, $separator);
        }
        fclose($handle);
    }

    public static function parseHeader(array $row): array
    {
        $header = [];
        foreach ($row as $column_index => $column) {
            $header[Str::slug($column, '_')] = $column_index;
        }

        return $header;
    }

    public function file(string $filename) : self
    {
        $this->filename = $filename;
        return $this;
    }

    public function header(array $header) : self
    {
        $this->header = $header;
        return $this;
    }

    public function collection(Collection $collection) : self
    {
        $this->collection = $collection;
        return $this;
    }

    public function callback(Callable $callback) : self
    {
        $this->callback = $callback;
        return $this;
    }

    public function export()
    {
        $headers = array(
            'Content-Type'        => 'text/csv',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition' => 'attachment; filename=' . $this->filename . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public',
        );

        $additional_param = [];
        $response = new StreamedResponse( function() use($additional_param) {
            $this->make($this->open());
        }, 200, $headers);

        return $response->send();
    }

    public function save(string $path) {
        $this->make($this->open($path));
    }

    protected function open(string $path = 'php://output')
    {
        return fopen($path, 'w');
    }

    protected function make($handle)
    {
        fputcsv($handle, $this->header, $this->delimiter, $this->enclosure, $this->escape_char);
        foreach ($this->collection as $item) {
            $expo_arr = call_user_func($this->callback, $item);
            fputcsv($handle, $expo_arr, $this->delimiter, $this->enclosure, $this->escape_char);
        }

        fclose($handle);
    }


}

?>