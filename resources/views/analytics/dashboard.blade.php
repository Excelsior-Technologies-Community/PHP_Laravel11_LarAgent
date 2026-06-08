<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- Navbar --}}
    <nav class="bg-indigo-700 text-white px-8 py-4 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <i class="fas fa-robot text-2xl"></i>
            <span class="text-xl font-bold">LarAgent Admin</span>
        </div>
        <div class="flex gap-6 text-sm font-medium">
            <a href="/admin/analytics" class="hover:text-indigo-200 transition">
                <i class="fas fa-chart-line mr-1"></i> Dashboard
            </a>
            <a href="/admin/approvals" class="hover:text-indigo-200 transition">
                <i class="fas fa-check-circle mr-1"></i> Approvals
                @if($pendingApprovals > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full ml-1">{{ $pendingApprovals }}</span>
                @endif
            </a>
            <a href="/" class="hover:text-indigo-200 transition">
                <i class="fas fa-comments mr-1"></i> Chat
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-8">

        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">AI Agent Dashboard</h1>
            <p class="text-gray-500 mt-1">Monitor your AI agent performance and handle pending approvals.</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Messages</h2>
                    <div class="bg-indigo-100 p-2 rounded-lg">
                        <i class="fas fa-comments text-indigo-600"></i>
                    </div>
                </div>
                <p class="text-4xl font-bold text-indigo-600">{{ $totalTokens }}</p>
                <p class="text-xs text-gray-400 mt-2">All time messages</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Pending Approvals</h2>
                    <div class="bg-red-100 p-2 rounded-lg">
                        <i class="fas fa-clock text-red-500"></i>
                    </div>
                </div>
                <p class="text-4xl font-bold text-red-500">{{ $pendingApprovals }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    <a href="/admin/approvals" class="text-indigo-500 hover:underline">View all →</a>
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Avg Response Time</h2>
                    <div class="bg-emerald-100 p-2 rounded-lg">
                        <i class="fas fa-tachometer-alt text-emerald-600"></i>
                    </div>
                </div>
                <p class="text-4xl font-bold text-emerald-600">{{ round($avgResponseTime, 2) }} <span class="text-lg">ms</span></p>
                <p class="text-xs text-gray-400 mt-2">Average across all sessions</p>
            </div>
        </div>

        {{-- Pending Approvals Section --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-hourglass-half text-yellow-500 mr-2"></i>Pending Approvals
                </h2>
                <a href="/admin/approvals" class="text-sm text-indigo-600 hover:underline font-medium">View All →</a>
            </div>

            @if($pendingMessages->isEmpty())
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center">
                    <i class="fas fa-check-circle text-4xl text-green-400 mb-3"></i>
                    <p class="text-gray-500 text-lg">No pending approvals found! 🎉</p>
                </div>
            @else
                @foreach($pendingMessages->take(3) as $msg)
                    <div class="bg-white p-6 mb-4 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">
                                    <i class="fas fa-user mr-1"></i> User Input
                                </p>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-xl border border-gray-100">{{ $msg->user_message }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">
                                    <i class="fas fa-robot mr-1"></i> AI Response
                                </p>
                                <p class="text-indigo-700 bg-indigo-50 p-3 rounded-xl border border-indigo-100">{{ $msg->ai_response }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                            <form action="/admin/approve/{{ $msg->id }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold transition shadow-md shadow-indigo-200">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Recent Activity Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-history text-indigo-500 mr-2"></i> Recent Activity Log
                </h2>
                <span class="text-xs text-gray-400">Last 10 messages</span>
            </div>
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-500">#</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-500">Message</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($recentActivities as $i => $activity)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-400 text-sm">{{ $i + 1 }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $activity['user_message'] }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $activity['status'] == 'approved'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700' }}">
                                <i class="fas {{ $activity['status'] == 'approved' ? 'fa-check' : 'fa-clock' }} mr-1"></i>
                                {{ ucfirst($activity['status']) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>