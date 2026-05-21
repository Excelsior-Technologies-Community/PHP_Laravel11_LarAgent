<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LarAgent - AI Chat Assistant</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .chat-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .message-user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 4px 20px;
            padding: 12px 18px;
            max-width: 80%;
            margin-left: auto;
            animation: slideInRight 0.3s ease;
        }
        
        .message-ai {
            background: #f0f0f0;
            color: #333;
            border-radius: 20px 20px 20px 4px;
            padding: 12px 18px;
            max-width: 80%;
            animation: slideInLeft 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 18px;
            background: #f0f0f0;
            border-radius: 20px;
            width: fit-content;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-10px); opacity: 1; }
        }
        
        .tool-badge {
            background: #e8f5e9;
            color: #2e7d32;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 6px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -280px;
                transition: left 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .message-user, .message-ai {
                max-width: 90%;
            }
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="app">
        <button @click="toggleSidebar" class="fixed top-4 left-4 z-50 md:hidden bg-white rounded-full p-3 shadow-lg">
            <i class="fas fa-bars text-gray-600"></i>
        </button>
        
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-6xl mx-auto">
                <div class="text-center mb-6">
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-robot"></i> LarAgent
                    </h1>
                    <p class="text-white/80">Your Intelligent AI Assistant</p>
                </div>
                
                <div class="chat-container flex h-[600px]">
                    <!-- Sidebar -->
                    <div class="sidebar w-80 bg-gray-50 border-r border-gray-200 flex flex-col" :class="{ open: sidebarOpen }">
                        <div class="p-4 border-b border-gray-200">
                            <h2 class="font-semibold text-gray-700">
                                <i class="fas fa-history mr-2"></i> Chat History
                            </h2>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-4">
                            <div v-if="historyLoading" class="text-center py-8">
                                <div class="spinner mx-auto"></div>
                            </div>
                            <div v-else-if="history.length === 0" class="text-center text-gray-400 py-8">
                                <i class="fas fa-comment-dots text-3xl mb-2"></i>
                                <p>No messages yet</p>
                            </div>
                            <div v-else>
                                <div v-for="msg in history" :key="msg.id" class="mb-3 p-2 hover:bg-gray-100 rounded-lg cursor-pointer" @click="loadMessage(msg)">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-user-circle text-gray-400 mt-1"></i>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-700 truncate">@{{ msg.user_message }}</p>
                                            <p class="text-xs text-gray-400">@{{ msg.created_at }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border-t border-gray-200">
                            <button @click="clearHistory" class="w-full bg-red-500 text-white rounded-lg py-2 hover:bg-red-600 transition">
                                <i class="fas fa-trash-alt mr-2"></i> Clear History
                            </button>
                        </div>
                    </div>
                    
                    <!-- Chat Area -->
                    <div class="flex-1 flex flex-col">
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white p-3 flex justify-around">
                            <div class="text-center">
                                <div class="text-sm opacity-80">Messages</div>
                                <div class="font-bold">@{{ stats.total_messages || 0 }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-80">Today</div>
                                <div class="font-bold">@{{ stats.today_messages || 0 }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm opacity-80">Tools Used</div>
                                <div class="font-bold">@{{ stats.tools_used || 0 }}</div>
                            </div>
                            <div class="text-center cursor-pointer" @click="showStats = !showStats">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-6" ref="messagesContainer">
                            <div v-if="messages.length === 0" class="text-center py-12">
                                <i class="fas fa-robot text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-600 mb-2">Welcome to LarAgent!</h3>
                                <p class="text-gray-400">Ask me anything or try these tools:</p>
                                <div class="flex flex-wrap justify-center gap-2 mt-4">
                                    <span v-for="tool in tools" :key="tool.name" class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                                        @{{ tool.name }}
                                    </span>
                                </div>
                            </div>
                            
                            <div v-for="(msg, index) in messages" :key="index" class="mb-4">
                                <div class="message-user">
                                    <div class="text-sm font-medium mb-1">You</div>
                                    <div>@{{ msg.user }}</div>
                                </div>
                                
                                <div class="mt-2">
                                    <div class="message-ai">
                                        <div v-if="msg.tool" class="tool-badge">
                                            <i class="fas fa-magic"></i> Used: @{{ msg.tool }}
                                        </div>
                                        <div class="text-sm font-medium mb-1">LarAgent</div>
                                        <div v-html="formatMessage(msg.ai)"></div>
                                        <div v-if="msg.meta" class="text-xs text-gray-400 mt-2">
                                            <i class="far fa-clock"></i> @{{ msg.meta.response_time_ms }}ms
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="isTyping" class="mb-4">
                                <div class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border-t border-gray-200 bg-white">
                            <div class="flex gap-2">
                                <textarea 
                                    v-model="currentMessage"
                                    @keydown.enter.prevent="sendMessage"
                                    placeholder="Type your message... (Enter to send, Shift+Enter for new line)"
                                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 resize-none focus:outline-none focus:border-purple-500"
                                    rows="2"
                                ></textarea>
                                <button 
                                    @click="sendMessage"
                                    :disabled="isTyping || !currentMessage.trim()"
                                    class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg px-6 py-2 hover:opacity-90 transition disabled:opacity-50"
                                >
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="text-xs text-gray-400 mt-2">
                                <i class="fas fa-info-circle"></i> Try: "time", "uppercase hello", "5 + 3", "random number", "weather in London", "tell me a joke", "give me a quote"
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Modal -->
                <div v-if="showStats" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showStats = false">
                    <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-chart-line text-purple-500"></i> Analytics
                            </h2>
                            <button @click="showStats = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="stat-card">
                                <i class="fas fa-comments text-purple-500 text-2xl mb-2"></i>
                                <div class="text-2xl font-bold">@{{ stats.total_messages || 0 }}</div>
                                <div class="text-sm text-gray-500">Total Messages</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-users text-blue-500 text-2xl mb-2"></i>
                                <div class="text-2xl font-bold">@{{ stats.total_users || 0 }}</div>
                                <div class="text-sm text-gray-500">Total Users</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-calendar-day text-green-500 text-2xl mb-2"></i>
                                <div class="text-2xl font-bold">@{{ stats.today_messages || 0 }}</div>
                                <div class="text-sm text-gray-500">Today's Messages</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-tachometer-alt text-orange-500 text-2xl mb-2"></i>
                                <div class="text-2xl font-bold">@{{ stats.avg_response_time || 0 }}ms</div>
                                <div class="text-sm text-gray-500">Avg Response</div>
                            </div>
                        </div>
                        <div class="border-t pt-4">
                            <h3 class="font-semibold mb-2">Recent Activity</h3>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                <div v-for="activity in recentActivity" :key="activity.id" class="text-sm p-2 bg-gray-50 rounded">
                                    <div class="flex justify-between">
                                        <span class="font-medium">@{{ activity.user_message }}</span>
                                        <span class="text-xs text-gray-400">@{{ activity.time }}</span>
                                    </div>
                                    <div class="text-gray-600 text-xs mt-1">@{{ activity.ai_response }}</div>
                                    <div v-if="activity.used_tool" class="text-xs text-green-600 mt-1">
                                        <i class="fas fa-magic"></i> @{{ activity.tool_name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                messages: [],
                currentMessage: '',
                isTyping: false,
                history: [],
                historyLoading: false,
                sidebarOpen: false,
                showStats: false,
                stats: {},
                recentActivity: [],
                tools: [],
            },
            mounted() {
                this.loadTools();
                this.loadHistory();
                this.loadStats();
                this.scrollToBottom();
            },
            methods: {
                async sendMessage() {
                    const message = this.currentMessage.trim();
                    if (!message || this.isTyping) return;
                    
                    this.messages.push({ user: message, ai: null });
                    this.currentMessage = '';
                    this.scrollToBottom();
                    
                    this.isTyping = true;
                    
                    try {
                        const response = await axios.get(`/api/chat/message/${encodeURIComponent(message)}`);
                        const data = response.data;
                        
                        if (data.status === 'success') {
                            this.messages[this.messages.length - 1] = {
                                user: message,
                                ai: data.message,
                                tool: data.tool_name,
                                meta: data.meta,
                                used_tool: data.used_tool
                            };
                            
                            this.loadHistory();
                            this.loadStats();
                        } else {
                            this.messages[this.messages.length - 1] = {
                                user: message,
                                ai: 'Sorry, an error occurred. Please try again.',
                            };
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.messages[this.messages.length - 1] = {
                            user: message,
                            ai: 'Network error. Please check your connection.',
                        };
                    } finally {
                        this.isTyping = false;
                        this.scrollToBottom();
                    }
                },
                
                async loadHistory() {
                    this.historyLoading = true;
                    try {
                        const response = await axios.get('/api/chat/history');
                        this.history = response.data.messages;
                    } catch (error) {
                        console.error('Error loading history:', error);
                    } finally {
                        this.historyLoading = false;
                    }
                },
                
                async loadStats() {
                    try {
                        const response = await axios.get('/api/chat/stats');
                        this.stats = response.data.stats;
                        this.recentActivity = response.data.recent;
                    } catch (error) {
                        console.error('Error loading stats:', error);
                    }
                },
                
                async loadTools() {
                    try {
                        const response = await axios.get('/api/chat/tools');
                        this.tools = response.data;
                    } catch (error) {
                        console.error('Error loading tools:', error);
                    }
                },
                
                async clearHistory() {
                    if (confirm('Are you sure you want to clear all chat history?')) {
                        try {
                            await axios.delete('/api/chat/history');
                            this.messages = [];
                            this.loadHistory();
                            this.loadStats();
                        } catch (error) {
                            console.error('Error clearing history:', error);
                        }
                    }
                },
                
                loadMessage(msg) {
                    this.messages.push({ user: msg.user_message, ai: msg.ai_response });
                    this.scrollToBottom();
                    if (window.innerWidth < 768) {
                        this.sidebarOpen = false;
                    }
                },
                
                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },
                
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                
                formatMessage(text) {
                    if (!text) return '';
                    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-purple-500 underline">$1</a>');
                    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    text = text.replace(/\n/g, '<br>');
                    return text;
                }
            },
            watch: {
                messages: {
                    handler() {
                        this.scrollToBottom();
                    },
                    deep: true
                }
            }
        });
    </script>
</body>
</html>