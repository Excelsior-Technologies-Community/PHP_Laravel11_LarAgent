<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $totalTokens = ChatMessage::sum('token_usage');
        $avgResponseTime = ChatMessage::avg('response_time_ms') ?? 0;

        $modelUsage = ChatMessage::select('model_name', DB::raw('count(*) as total'))
            ->groupBy('model_name')
            ->get();

        $dailyChats = ChatMessage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->limit(7)
            ->get();

        $pendingApprovals = ChatMessage::where('status', 'pending')->count();

        $pendingMessages = ChatMessage::where('status', 'pending')
            ->latest()
            ->get();

        $recentActivities = ChatMessage::latest()
            ->take(10)
            ->get()
            ->map(fn($m) => [
                'user_message' => $m->user_message,
                'status'       => $m->status,
            ]);

        return view('analytics.dashboard', compact(
            'totalTokens',
            'avgResponseTime',
            'modelUsage',
            'dailyChats',
            'pendingApprovals',
            'pendingMessages',
            'recentActivities'
        ));
    }

    public function approvals()
    {
        $pendingMessages = ChatMessage::where('status', 'pending')
            ->latest()
            ->get();

        $pendingApprovals = $pendingMessages->count();

        return view('analytics.approvals', compact(
            'pendingMessages',
            'pendingApprovals'
        ));
    }

    public function approve(Request $request, $id)
    {
        $chat = ChatMessage::findOrFail($id);
        $chat->update([
            'status'      => 'approved',
            'is_approved' => true,
        ]);

        return redirect()->back()->with('success', 'Message approved!');
    }
}