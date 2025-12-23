<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Prompt;
use App\Models\Transaction;
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

    /**
     * Display credit management page
     */
    public function creditManagement()
    {
        return view('admin.credit-management');
    }

    /**
     * Search user by email for credit management
     */
    public function searchUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->with('error', 'User not found with email: ' . $validated['email']);
        }

        // Get user statistics
        $stats = [
            'total_videos' => Prompt::where('user_id', $user->id)->count(),
            'completed_videos' => Prompt::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total_credits_spent' => Prompt::where('user_id', $user->id)->sum('credits_used'),
            'total_credits_purchased' => Transaction::where('user_id', $user->id)
                ->where('type', 'purchase')
                ->sum('credits'),
        ];

        // Get recent videos
        $recentVideos = Prompt::where('user_id', $user->id)
            ->with('video')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.credit-management', compact('user', 'stats', 'recentVideos', 'recentTransactions'));
    }

    /**
     * Add credits to user
     */
    public function addCredits(Request $request, $userId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $user->addCredits($validated['amount']);

        // Record transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'admin_credit',
            'credits' => $validated['amount'],
            'amount' => 0, // No actual payment
            'description' => $validated['reason'] ?? 'Admin credit adjustment',
            'status' => 'completed',
        ]);

        return back()->with('success', "Successfully added {$validated['amount']} credits to {$user->name}'s account.");
    }

    /**
     * Remove credits from user
     */
    public function removeCredits(Request $request, $userId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        
        if ($user->credits < $validated['amount']) {
            return back()->with('error', 'User does not have enough credits. Current balance: ' . $user->credits);
        }

        $user->deductCredits($validated['amount']);

        // Record transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'admin_debit',
            'credits' => -$validated['amount'],
            'amount' => 0,
            'description' => $validated['reason'] ?? 'Admin credit deduction',
            'status' => 'completed',
        ]);

        return back()->with('success', "Successfully removed {$validated['amount']} credits from {$user->name}'s account.");
    }
}
