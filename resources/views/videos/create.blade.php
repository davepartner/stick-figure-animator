<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Stick Figure Video') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Video Creation Form -->
                <div class="lg:col-span-2">
                    @if (session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <form method="POST" action="{{ route('videos.store') }}" id="videoForm">
                                @csrf

                                <!-- Prompt Input -->
                                <div class="mb-6">
                                    <label for="prompt" class="block text-sm font-medium text-gray-700 mb-2">
                                        Your Story Idea
                                    </label>
                                    <textarea name="prompt" id="prompt" rows="4" required maxlength="500"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="E.g., A poor man gets motivated to become rich and succeeds through hard work...">{{ old('prompt') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">Describe your story in one or two sentences (max 500 characters)</p>
                                </div>

                                <!-- Duration Selection -->
                                <div class="mb-6">
                                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                                        Video Duration
                                    </label>
                                    <select name="duration" id="duration" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="10">10 seconds</option>
                                        <option value="30" selected>30 seconds</option>
                                        <option value="60">1 minute</option>
                                        <option value="120">2 minutes</option>
                                        <option value="300">5 minutes</option>
                                    </select>
                                </div>

                                <!-- Text Model Selection -->
                                <div class="mb-6">
                                    <label for="text_model" class="block text-sm font-medium text-gray-700 mb-2">
                                        Story Quality
                                    </label>
                                    <select name="text_model" id="text_model" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach($textModels as $key => $model)
                                            <option value="{{ $key }}">
                                                {{ $model['name'] }} - {{ $model['quality'] }} Quality
                                                @if(Auth::user()->isAdmin())
                                                    (~${{ number_format($model['credits'] * 0.01, 2) }})
                                                @else
                                                    ({{ $model['credits'] }} credits)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Image Model Selection -->
                                <div class="mb-6">
                                    <label for="image_model" class="block text-sm font-medium text-gray-700 mb-2">
                                        Image Quality
                                    </label>
                                    <select name="image_model" id="image_model" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach($imageModels as $key => $model)
                                            <option value="{{ $key }}">
                                                {{ $model['name'] }} - {{ $model['quality'] }} Quality
                                                @if(Auth::user()->isAdmin())
                                                    (~${{ number_format($model['credits_per_image'] * 0.01, 2) }}/image)
                                                @else
                                                    ({{ $model['credits_per_image'] }} credits/image)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Voice Model Selection -->
                                <div class="mb-6">
                                    <label for="voice_model" class="block text-sm font-medium text-gray-700 mb-2">
                                        Voice Quality
                                    </label>
                                    <select name="voice_model" id="voice_model" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach($voiceModels as $key => $model)
                                            <option value="{{ $key }}">
                                                {{ $model['name'] }} - {{ $model['quality'] }} Quality
                                                @if(Auth::user()->isAdmin())
                                                    (~${{ number_format($model['credits'] * 0.01, 2) }})
                                                @else
                                                    ({{ $model['credits'] }} credits)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Cost Estimate -->
                                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">Estimated Cost:</span>
                                        <span class="text-lg font-bold text-blue-600" id="estimatedCost">Calculating...</span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-600" id="costBreakdown"></div>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <button type="submit" id="submitBtn"
                                        class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Generate Video
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Videos Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Recent Videos</h3>
                            @if($recentPrompts->count() > 0)
                                <div class="space-y-3">
                                    @foreach($recentPrompts as $prompt)
                                        <div class="border-b pb-3">
                                            <p class="text-sm text-gray-900 truncate">{{ $prompt->original_prompt }}</p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs px-2 py-1 rounded-full
                                                    {{ $prompt->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $prompt->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $prompt->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $prompt->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ ucfirst($prompt->status) }}
                                                </span>
                                                @if($prompt->status === 'completed' && $prompt->video && !$prompt->video->is_deleted)
                                                    <a href="{{ route('videos.show', $prompt->id) }}" class="text-xs text-blue-600 hover:text-blue-900">View</a>
                                                @elseif($prompt->video && $prompt->video->is_deleted)
                                                    <button onclick="regenerateVideo({{ $prompt->id }})" class="text-xs text-blue-600 hover:text-blue-900">Regenerate</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No videos yet. Create your first one!</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-update cost estimate when settings change
        function updateCostEstimate() {
            const duration = document.getElementById('duration').value;
            const textModel = document.getElementById('text_model').value;
            const imageModel = document.getElementById('image_model').value;
            const voiceModel = document.getElementById('voice_model').value;

            fetch('{{ route("videos.estimate-cost") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ duration, text_model: textModel, image_model: imageModel, voice_model: voiceModel })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('estimatedCost').textContent = data.total_credits + ' credits';
                document.getElementById('costBreakdown').innerHTML = 
                    `Story: ${data.breakdown.text} • Images (${data.breakdown.image_count}): ${data.breakdown.images} • Voice: ${data.breakdown.voice}`;
            });
        }

        document.getElementById('duration').addEventListener('change', updateCostEstimate);
        document.getElementById('text_model').addEventListener('change', updateCostEstimate);
        document.getElementById('image_model').addEventListener('change', updateCostEstimate);
        document.getElementById('voice_model').addEventListener('change', updateCostEstimate);

        // Initial estimate
        updateCostEstimate();

        function regenerateVideo(promptId) {
            if (confirm('This will use credits to regenerate the video. Continue?')) {
                window.location.href = `/videos/${promptId}/regenerate`;
            }
        }
    </script>
</x-app-layout>
