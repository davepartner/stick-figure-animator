<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buy Credits') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Current Balance -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-8 mb-8 text-white">
                <div class="text-center">
                    <p class="text-lg opacity-90">Your Current Balance</p>
                    <p class="text-5xl font-bold mt-2">{{ number_format(Auth::user()->credits, 0) }}</p>
                    <p class="text-xl opacity-90 mt-1">Credits</p>
                </div>
            </div>

            <!-- Credit Packages -->
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Choose Your Package</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($packages as $key => $package)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden {{ isset($package['popular']) ? 'ring-2 ring-blue-500' : '' }}">
                            @if(isset($package['popular']))
                                <div class="bg-blue-500 text-white text-center py-2 text-sm font-semibold">
                                    MOST POPULAR
                                </div>
                            @endif
                            
                            <div class="p-6">
                                <h4 class="text-xl font-bold text-gray-900 mb-2">{{ $package['name'] }}</h4>
                                <p class="text-3xl font-bold text-blue-600 mb-1">${{ number_format($package['price'], 2) }}</p>
                                <p class="text-gray-600 text-sm mb-4">{{ number_format($package['credits']) }} credits</p>
                                <p class="text-gray-500 text-sm mb-6">{{ $package['description'] }}</p>
                                
                                <div class="space-y-2">
                                    <!-- Stripe Payment Button -->
                                    <form method="POST" action="{{ route('payments.stripe-checkout') }}">
                                        @csrf
                                        <input type="hidden" name="package" value="{{ $key }}">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M3 10h18v2H3v-2zm0-4h18v2H3V6zm0 8h18v2H3v-2z"/>
                                            </svg>
                                            Pay with Card
                                        </button>
                                    </form>

                                    <!-- Paystack Payment Button -->
                                    <form method="POST" action="{{ route('payments.paystack-payment') }}">
                                        @csrf
                                        <input type="hidden" name="package" value="{{ $key }}">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                            </svg>
                                            Pay with Paystack
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Transaction History</h3>
                    
                    @if($transactions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($transactions as $transaction)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $transaction->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ ucfirst($transaction->type) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ ucfirst($transaction->payment_gateway) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format($transaction->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                +{{ number_format($transaction->credits, 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $transaction->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ ucfirst($transaction->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No transactions yet. Purchase credits to get started!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
