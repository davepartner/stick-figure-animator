<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_users' => DB::table('users')->count(),
            'total_videos' => DB::table('videos')->count(),
            'total_prompts' => DB::table('prompts')->count(),
            'active_videos' => DB::table('videos')->where('is_deleted', false)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Display system settings page
     */
    public function settings()
    {
        $settings = SystemSetting::all()->groupBy('category');
        
        return view('admin.settings', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            SystemSetting::set($key, $value);
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Display users management page
     */
    public function users()
    {
        $users = DB::table('users')
            ->select('id', 'name', 'email', 'role', 'credits', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users', compact('users'));
    }

    /**
     * Update user credits
     */
    public function updateUserCredits(Request $request, $userId)
    {
        $validated = $request->validate([
            'credits' => 'required|numeric|min:0',
        ]);

        DB::table('users')
            ->where('id', $userId)
            ->update(['credits' => $validated['credits']]);

        return redirect()->route('admin.users')
            ->with('success', 'User credits updated successfully!');
    }

    /**
     * Display video analytics
     */
    public function videos()
    {
        $videos = DB::table('videos')
            ->join('prompts', 'videos.prompt_id', '=', 'prompts.id')
            ->join('users', 'prompts.user_id', '=', 'users.id')
            ->select(
                'videos.*',
                'prompts.original_prompt',
                'prompts.credits_used',
                'prompts.actual_cost',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderBy('videos.created_at', 'desc')
            ->paginate(20);

        return view('admin.videos', compact('videos'));
    }
}
