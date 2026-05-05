<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AiAgents\ChatAgent;

class ChatController extends Controller
{
    public function chat(Request $request, $message)
    {
        // session-based AI memory
        $sessionId = $request->session()->getId();

        // DO NOT inject wrong constructor
        $agent = new ChatAgent($sessionId);

        return response()->json(
            $agent->safeRespond($message)
        );
    }
}