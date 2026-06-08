<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Approvals - LarAgent</title>
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
            <a href="/admin/approvals" class="text-indigo-200 border-b border-indigo-300 transition">
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

    <div class="max-w-5xl mx-auto p-8">

        {{-- Success Message --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-5 py-3 rounded-xl mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-hourglass-half text-yellow-500 mr-2"></i>Pending Approvals
                </h1>
                <p class="text-gray-500 mt-1">Review and approve flagged messages.</p>
            </div>
            <span class="bg-indigo-100 text-indigo-700 px-5 py-2 rounded-full text-sm font-bold shadow-sm">
                {{ $pendingApprovals }} Pending
            </span>
        </div>

        {{-- Empty State --}}
        @if($pendingMessages->isEmpty())
            <div class="bg-white p-16 rounded-2xl shadow-sm border border-gray-100 text-center">
                <i class="fas fa-check-circle text-6xl text-green-400 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">All Clear!</h3>
                <p class="text-gray-400">કોઈ પેન્ડિંગ એપ્રોવલ બાકી નથી! 🎉</p>
                <a href="/admin/analytics" class="inline-block mt-6 bg-indigo-600 text-white px-6 py-2 rounded-xl hover:bg-indigo-700 transition font-semibold">
                    ← Back to Dashboard
                </a>
            </div>

        {{-- Pending Messages --}}
        @else
            @foreach($pendingMessages as $msg)
                <div class="bg-white p-6 mb-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">

                    {{-- Message ID + Time --}}
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs text-gray-400 font-mono bg-gray-100 px-3 py-1 rounded-full">
                            #{{ $msg->id }}
                        </span>
                        <span class="text-xs text-gray-400">
                            <i class="far fa-clock mr-1"></i>
                            {{ $msg->created_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Content Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                                <i class="fas fa-user text-indigo-400 mr-1"></i> User Input
                            </p>
                            <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl text-gray-700 text-sm leading-relaxed">
                                {{ $msg->user_message }}
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                                <i class="fas fa-robot text-indigo-400 mr-1"></i> AI Response
                            </p>
                            <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl text-indigo-800 text-sm leading-relaxed">
                                {{ $msg->ai_response }}
                            </div>
                        </div>
                    </div>

                    {{-- Session Info --}}
                    <div class="flex items-center gap-4 text-xs text-gray-400 mb-4">
                        <span><i class="fas fa-network-wired mr-1"></i> {{ $msg->ip_address ?? 'N/A' }}</span>
                        <span><i class="fas fa-tag mr-1"></i>
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-semibold">Pending</span>
                        </span>
                    </div>

                    {{-- Approve Button --}}
                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <form action="/admin/approve/{{ $msg->id }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-8 py-2.5 rounded-xl font-semibold transition-all shadow-lg shadow-indigo-200">
                                <i class="fas fa-check"></i> Approve Message
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif

    </div>
</body>
</html>