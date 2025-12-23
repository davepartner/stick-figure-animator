<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                @foreach($settings as $category => $categorySettings)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 capitalize">{{ str_replace('_', ' ', $category) }} Settings</h3>
                            
                            <div class="space-y-4">
                                @foreach($categorySettings as $setting)
                                    <div>
                                        <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700">
                                            {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                                        </label>
                                        <p class="text-xs text-gray-500 mb-1">{{ $setting->description }}</p>
                                        
                                        @if($setting->type === 'boolean')
                                            <select name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>No</option>
                                            </select>
                                        @elseif($setting->type === 'integer')
                                            <input type="number" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @else
                                            <input type="text" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
