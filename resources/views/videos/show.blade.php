<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Video Generation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Prompt Details -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Your Story</h3>
                        <p class="text-gray-700">{{ $prompt->original_prompt }}</p>
                        <div class="mt-2 text-sm text-gray-500">
                            Duration: {{ $prompt->duration_seconds }} seconds • Credits Used: {{ $prompt->credits_used }}
                        </div>
                    </div>

                    <!-- Status Display -->
                    <div id="statusContainer" class="mb-6">
                        @if($prompt->status === 'pending' || $prompt->status === 'processing')
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-yellow-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-yellow-800">Generating your video...</p>
                                        <p class="text-sm text-yellow-700 mt-1" id="statusMessage">This may take a few minutes. Please don't close this page.</p>
                                    </div>
                                </div>
                            </div>
                        @elseif($prompt->status === 'completed')
                            @if($prompt->video && !$prompt->video->is_deleted)
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                    <p class="font-semibold text-green-800">✓ Video generated successfully!</p>
                                </div>

                                <!-- Expiration Warning -->
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-orange-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <p class="font-semibold text-orange-800">Video will be deleted in: <span id="timeRemaining">{{ $prompt->video->getTimeRemaining() }}</span></p>
                                            <p class="text-sm text-orange-700 mt-1">Download now to keep it permanently!</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Player -->
                                <div class="mb-4">
                                    <video controls class="w-full rounded-lg shadow-lg">
                                        <source src="{{ asset('storage/' . basename(dirname($prompt->video->file_path)) . '/' . basename($prompt->video->file_path)) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex space-x-4 mb-6">
                                    <a href="{{ route('videos.download', $prompt->video->id) }}" 
                                        class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download Video
                                    </a>

                                    <a href="{{ route('videos.index') }}" 
                                        class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Create Another Video
                                    </a>
                                </div>

                                <!-- YouTube Optimization Section -->
                                <div class="border-t pt-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">YouTube Optimization</h3>
                                        @if(!$prompt->video->youtube_titles)
                                            <button onclick="generateYouTubeContent()" id="generateYTBtn"
                                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                                </svg>
                                                Generate YouTube Details
                                            </button>
                                        @endif
                                    </div>

                                    <div id="youtubeContent">
                                        @if($prompt->video->youtube_titles)
                                            <!-- Title Options -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Title Options (Click to Copy)</label>
                                                @foreach($prompt->video->youtube_titles as $index => $titleData)
                                                    <div class="bg-gray-50 rounded-lg p-3 mb-2 cursor-pointer hover:bg-gray-100" onclick="copyToClipboard('{{ $titleData['title'] }}')">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-gray-900">{{ $titleData['title'] }}</span>
                                                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Virality: {{ $titleData['virality_score'] }}/10</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Click to Copy)</label>
                                                <div class="bg-gray-50 rounded-lg p-3 cursor-pointer hover:bg-gray-100" onclick="copyToClipboard(`{{ $prompt->video->youtube_description }}`)">
                                                    <p class="text-sm text-gray-700">{{ $prompt->video->youtube_description }}</p>
                                                </div>
                                            </div>

                                            <!-- Hashtags -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Hashtags (Click to Copy)</label>
                                                <div class="bg-gray-50 rounded-lg p-3 cursor-pointer hover:bg-gray-100" onclick="copyToClipboard('{{ $prompt->video->youtube_hashtags }}')">
                                                    <p class="text-sm text-blue-600">{{ $prompt->video->youtube_hashtags }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-gray-500 text-sm">Click "Generate YouTube Details" to create optimized titles, description, and hashtags for your video.</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <p class="font-semibold text-gray-800">Video has been deleted</p>
                                    <p class="text-sm text-gray-600 mt-1">This video is no longer available. You can regenerate it below.</p>
                                    <form method="POST" action="{{ route('videos.regenerate', $prompt->id) }}" class="mt-4">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                            Regenerate Video ({{ $prompt->credits_used }} credits)
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @elseif($prompt->status === 'failed')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <p class="font-semibold text-red-800">✗ Video generation failed</p>
                                <p class="text-sm text-red-700 mt-1">{{ $prompt->error_message ?? 'An unknown error occurred.' }}</p>
                                <p class="text-sm text-red-600 mt-2">Your credits have been refunded.</p>
                                <a href="{{ route('videos.index') }}" class="inline-block mt-4 text-sm text-blue-600 hover:text-blue-900">Try again</a>
                            </div>
                        @endif
                    </div>

                    <!-- Generated Script (if available) -->
                    @if($prompt->generated_script)
                        <div class="mt-6 border-t pt-6">
                            <h3 class="text-lg font-semibold mb-2">Generated Script</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-700 text-sm">{{ $prompt->generated_script }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($prompt->status === 'pending' || $prompt->status === 'processing')
    <script>
        // Poll for status updates every 5 seconds
        let pollInterval = setInterval(function() {
            fetch('{{ route("videos.check-status", $prompt->id) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(pollInterval);
                        location.reload();
                    }
                });
        }, 5000);
    </script>
    @endif

    @if($prompt->status === 'completed' && $prompt->video && !$prompt->video->is_deleted)
    <script>
        function generateYouTubeContent() {
            const btn = document.getElementById('generateYTBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';

            fetch('/videos/{{ $prompt->video->id }}/youtube-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to generate YouTube content: ' + data.error);
                    btn.disabled = false;
                    btn.innerHTML = 'Generate YouTube Details';
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Generate YouTube Details';
            });
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show temporary success message
                const msg = document.createElement('div');
                msg.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg';
                msg.textContent = 'Copied to clipboard!';
                document.body.appendChild(msg);
                setTimeout(() => msg.remove(), 2000);
            }, function(err) {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
    @endif
</x-app-layout>
