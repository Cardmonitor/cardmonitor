<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DeploymentController extends Controller
{
    public function store(Request $request)
    {
        $githubPayload = $request->getContent();
        $githubHash = $request->header('X-Hub-Signature');

        $localToken = config('app.deploy_secret');
        $localHash = 'sha1=' . hash_hmac('sha1', $githubPayload, $localToken, false);

        // if (! hash_equals($githubHash, $localHash))
        // {
        //     return abort(404);
        // }

        // if (! $githubHash)
        // {
        //     return abort(404);
        // }

        $root_path = base_path();
        $process = Process::fromShellCommandline('cd ' . $root_path . '; sh ./deploy.sh');
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful())
        {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();
    }
}
