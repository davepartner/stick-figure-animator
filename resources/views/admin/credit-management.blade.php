<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Credit Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Search User Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Search User by Email</h3>
                    
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.search-user') }}" class="flex gap-4">
                        @csrf
                        <div class="flex-1">
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="Enter user email address" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value="{{ isset($user) ? $user->email : old('email') }}"
                                required
                            >
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            @if(isset($user))
                <!-- User Details Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    
                    <!-- User Info Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">User Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Name</p>
                                    <p class="font-medium">{{ $user->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="font-medium">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Role</p>
                                    <p class="font-medium">
                                        <span class="px-2 py-1 rounded-full text-xs {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Member Since</p>
                                    <p class="font-medium">{{ $user->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credits Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Credit Balance</h3>
                            <div class="text-center py-6">
                                <p class="text-5xl font-bold text-blue-600">{{ number_format($user->credits, 0) }}</p>
                                <p class="text-sm text-gray-600 mt-2">Available Credits</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total_credits_purchased'], 0) }}</p>
                                    <p class="text-xs text-gray-600">Purchased</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-red-600">{{ number_format($stats['total_credits_spent'], 0) }}</p>
                                    <p class="text-xs text-gray-600">Spent</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Stats Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Video Statistics</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Videos</span>
                                    <span class="text-2xl font-bold">{{ $stats['total_videos'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Completed</span>
                                    <span class="text-2xl font-bold text-green-600">{{ $stats['completed_videos'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Failed/Processing</span>
                                    <span class="text-2xl font-bold text-gray-600">{{ $stats['total_videos'] - $stats['completed_videos'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Management Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Add Credits -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 text-green-700">Add Credits</h3>
                            <form method="POST" action="{{ route('admin.add-credits', $user->id) }}">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                                        <input 
                                            type="number" 
                                            name="amount" 
                                            min="1" 
                                            step="1"
                                            placeholder="Enter credits to add" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                                        <input 
                                            type="text" 
                                            name="reason" 
                                            placeholder="e.g., Promotional bonus, Refund, etc." 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        >
                                    </div>
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-medium">
                                        Add Credits
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Remove Credits -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 text-red-700">Remove Credits</h3>
                            <form method="POST" action="{{ route('admin.remove-credits', $user->id) }}" onsubmit="return confirm('Are you sure you want to remove credits from this user?');">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                                        <input 
                                            type="number" 
                                            name="amount" 
                                            min="1" 
                                            step="1"
                                            max="{{ $user->credits }}"
                                            placeholder="Enter credits to remove" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                            required
                                        >
                                        <p class="text-xs text-gray-500 mt-1">Maximum: {{ number_format($user->credits, 0) }} credits</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                                        <input 
                                            type="text" 
                                            name="reason" 
                                            placeholder="e.g., Policy violation, Chargeback, etc." 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        >
                                    </div>
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-md font-medium">
                                        Remove Credits
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Videos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Videos (Last 10)</h3>
                        @if($recentVideos->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prompt</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits Used</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentVideos as $video)
                                            <tr>
                                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ $video->original_prompt }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">{{ $video->duration_seconds }}s</td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs rounded-full
                                                        {{ $video->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                        {{ $video->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                        {{ $video->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                                        {{ $video->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                        {{ ucfirst($video->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($video->credits_used, 0) }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-500">{{ $video->created_at->format('M d, Y g:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No videos generated yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Transactions (Last 10)</h3>
                        @if($recentTransactions->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentTransactions as $transaction)
                                            <tr>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs rounded-full
                                                        {{ $transaction->type === 'purchase' ? 'bg-blue-100 text-blue-800' : '' }}
                                                        {{ $transaction->type === 'usage' ? 'bg-orange-100 text-orange-800' : '' }}
                                                        {{ $transaction->type === 'admin_credit' ? 'bg-green-100 text-green-800' : '' }}
                                                        {{ $transaction->type === 'admin_debit' ? 'bg-red-100 text-red-800' : '' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">{{ $transaction->description }}</td>
                                                <td class="px-6 py-4 text-sm font-medium
                                                    {{ $transaction->credits > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $transaction->credits > 0 ? '+' : '' }}{{ number_format($transaction->credits, 0) }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">${{ number_format($transaction->amount, 2) }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-500">{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No transactions yet.</p>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
