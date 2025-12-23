<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoAssemblyService
{
    /**
     * Create a video from images and audio
     *
     * @param array $imagePaths Array of image file paths
     * @param string $audioPath Path to audio file
     * @param int $durationSeconds Total video duration
     * @return array ['video_path' => string, 'file_size' => int]
     */
    public function assembleVideo(array $imagePaths, string $audioPath, int $durationSeconds): array
    {
        try {
            $outputFilename = 'videos/' . uniqid('video_') . '.mp4';
            $outputPath = Storage::disk('public')->path($outputFilename);

            // Calculate duration per image
            $imageCount = count($imagePaths);
            $durationPerImage = $durationSeconds / $imageCount;

            // Create a temporary file list for FFmpeg concat
            $fileListPath = sys_get_temp_dir() . '/ffmpeg_list_' . uniqid() . '.txt';
            $fileListContent = '';

            foreach ($imagePaths as $imagePath) {
                $fileListContent .= "file '" . $imagePath . "'\n";
                $fileListContent .= "duration " . $durationPerImage . "\n";
            }
            // Add the last image again (FFmpeg concat requirement)
            $fileListContent .= "file '" . end($imagePaths) . "'\n";

            file_put_contents($fileListPath, $fileListContent);

            // Build FFmpeg command
            // Step 1: Create video from images with crossfade transitions
            $tempVideoPath = sys_get_temp_dir() . '/temp_video_' . uniqid() . '.mp4';
            
            $command = sprintf(
                'ffmpeg -f concat -safe 0 -i %s -vf "fps=30,format=yuv420p" -y %s 2>&1',
                escapeshellarg($fileListPath),
                escapeshellarg($tempVideoPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('FFmpeg video creation failed: ' . implode("\n", $output));
                throw new \Exception('Failed to create video from images');
            }

            // Step 2: Add audio to the video
            $finalCommand = sprintf(
                'ffmpeg -i %s -i %s -c:v copy -c:a aac -strict experimental -shortest -y %s 2>&1',
                escapeshellarg($tempVideoPath),
                escapeshellarg($audioPath),
                escapeshellarg($outputPath)
            );

            exec($finalCommand, $finalOutput, $finalReturnCode);

            if ($finalReturnCode !== 0) {
                Log::error('FFmpeg audio merge failed: ' . implode("\n", $finalOutput));
                throw new \Exception('Failed to add audio to video');
            }

            // Clean up temporary files
            @unlink($fileListPath);
            @unlink($tempVideoPath);

            // Get file size
            $fileSize = filesize($outputPath);

            return [
                'video_path' => $outputPath,
                'file_size' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error('Video assembly failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add crossfade transitions between images (advanced version)
     */
    public function assembleVideoWithTransitions(array $imagePaths, string $audioPath, int $durationSeconds): array
    {
        try {
            $outputFilename = 'videos/' . uniqid('video_') . '.mp4';
            $outputPath = Storage::disk('public')->path($outputFilename);

            $imageCount = count($imagePaths);
            $durationPerImage = $durationSeconds / $imageCount;
            $transitionDuration = 0.5; // 0.5 second crossfade

            // Build complex filter for crossfade transitions
            $inputs = '';
            $filters = '';
            
            foreach ($imagePaths as $index => $imagePath) {
                $inputs .= sprintf('-loop 1 -t %f -i %s ', $durationPerImage, escapeshellarg($imagePath));
            }

            // Create crossfade filter chain
            for ($i = 0; $i < $imageCount - 1; $i++) {
                if ($i == 0) {
                    $filters .= sprintf('[0:v][1:v]xfade=transition=fade:duration=%f:offset=%f[v01];', 
                        $transitionDuration, 
                        $durationPerImage - $transitionDuration
                    );
                } else {
                    $prevLabel = ($i == 1) ? 'v01' : 'v' . ($i - 1) . $i;
                    $currLabel = 'v' . $i . ($i + 1);
                    $offset = ($i + 1) * $durationPerImage - ($i + 1) * $transitionDuration;
                    $filters .= sprintf('[%s][%d:v]xfade=transition=fade:duration=%f:offset=%f[%s];', 
                        $prevLabel, 
                        $i + 1, 
                        $transitionDuration, 
                        $offset,
                        $currLabel
                    );
                }
            }

            $lastLabel = 'v' . ($imageCount - 2) . ($imageCount - 1);
            $filters = rtrim($filters, ';');

            // Create video with transitions
            $tempVideoPath = sys_get_temp_dir() . '/temp_video_' . uniqid() . '.mp4';
            
            $command = sprintf(
                'ffmpeg %s -filter_complex "%s" -map "[%s]" -y %s 2>&1',
                $inputs,
                $filters,
                $lastLabel,
                escapeshellarg($tempVideoPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // Fallback to simple version if transitions fail
                Log::warning('Crossfade transitions failed, using simple version');
                return $this->assembleVideo($imagePaths, $audioPath, $durationSeconds);
            }

            // Add audio
            $finalCommand = sprintf(
                'ffmpeg -i %s -i %s -c:v copy -c:a aac -strict experimental -shortest -y %s 2>&1',
                escapeshellarg($tempVideoPath),
                escapeshellarg($audioPath),
                escapeshellarg($outputPath)
            );

            exec($finalCommand, $finalOutput, $finalReturnCode);

            if ($finalReturnCode !== 0) {
                throw new \Exception('Failed to add audio to video');
            }

            @unlink($tempVideoPath);

            $fileSize = filesize($outputPath);

            return [
                'video_path' => $outputPath,
                'file_size' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error('Video assembly with transitions failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
