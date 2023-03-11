<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackgroundTasks
{
    private string $filename = '';

    public static function make(): self {
        return new self();
    }

    public static function path(string $task): string
    {
        return Storage::disk('backgroundtasks')->path(str_replace('.', '/', $task));
    }

    public static function makeLogger(string $task, string $filename): \Psr\Log\LoggerInterface
    {
        return Log::build([
            'driver' => 'single',
            'path' => self::path($task) . '/' . $filename,
        ]);
    }

    private function __construct()
    {
        $this->filename = storage_path('app/background_tasks.json');
    }

    public function all(): array
    {
        if (! file_exists($this->filename)) {
            return [];
        }

        return json_decode(file_get_contents($this->filename), true) ?? [];
    }

    public function put(string $key, mixed $value): self
    {
        $content = $this->all();
        Arr::set($content, $key, $value);
        $this->setContent($content);

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function forget(string $key): self
    {
        $content = $this->all();
        Arr::forget($content, $key);
        $this->setContent($content);

        return $this;
    }

    public function flush(): self
    {
        return $this->setContent([]);
    }

    public function setContent(array $content): self
    {
        file_put_contents($this->filename, json_encode($content));

        if (count($content) === 0) {
            unlink($this->filename);
        }

        return $this;
    }
}
