<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Support\BackgroundTasks;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BackgroundTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BackgroundTasks $BackgroundTasks)
    {
        return $BackgroundTasks->all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, BackgroundTasks $BackgroundTasks)
    {
        $attributes = $request->validate([
            'background_tasks' => 'required|array',
        ]);

        foreach ($attributes['background_tasks'] as $task) {
            $BackgroundTasks->put($task, 1);
        }

        return $BackgroundTasks->all();
    }

    public function show(Request $request, BackgroundTasks $BackgroundTasks, string $task)
    {
        $path = $this->getLatestFileForTask($task);
        $storage_path = storage_path($path);

        if (! file_exists($storage_path) || ! is_file($storage_path)) {
            $content = '';
        }
        else {
            $content = file_get_contents($storage_path);
        }

        if ($request->wantsJson()) {
            return [
                'task' => $task,
                'path' => $path,
                'content' => $content,
                'is_running' => !empty($BackgroundTasks->get($task)),
            ];
        }

        return view('user.backgroundtask.show', [
            'task' => $task,
            'path' => $path,
            'content' => $content,
        ]);
    }

    private function getLatestFileForTask(string $task): string
    {
        $path = BackgroundTasks::path($task);
        $files = glob($path . '/*.log');
        if (empty($files)) {
            return '';
        }
        $files = array_combine($files, array_map("filemtime", $files));
        arsort($files);
        $files = array_keys($files);
        return str_replace(storage_path(). '/', '', $files[0]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(BackgroundTasks $BackgroundTasks, string $task)
    {
        $BackgroundTasks->forget($task);

        return $BackgroundTasks->all();
    }
}
