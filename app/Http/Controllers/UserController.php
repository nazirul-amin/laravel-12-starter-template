<?php

namespace App\Http\Controllers;

use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', User::class);
        $query = User::query()->select(['id', 'name', 'email', 'created_at', 'created_by']);

        $user = request()->user();
        if (! $user->hasRole('super-admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('id', $user->id);
            });
        }

        $users = $query->latest()->paginate(15);

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', User::class);
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ]);

            $validated['password'] = Str::random(12);
            $validated['created_by'] = $request->user()->id;

            $user = DB::transaction(function () use ($validated) {
                $user = User::create($validated);

                $user->assignRole('user');

                return $user;
            });

            Mail::to($user->email)->send(new UserCreated($user, $validated['password']));

            return redirect()->route('users.index')->with('success', 'User created');
        } catch (\Throwable $th) {
            Log::error('Error creating user', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to create user');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id.',id'],
            ]);

            DB::transaction(function () use ($user, $validated) {
                $user->update($validated);
            });

            return redirect()->route('users.index')->with('success', 'User updated');
        } catch (\Throwable $th) {
            Log::error('Error updating user', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to update user');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        try {
            DB::transaction(function () use ($user) {
                $user->delete();
            });

            return redirect()->route('users.index')->with('success', 'User deleted');
        } catch (\Throwable $th) {
            Log::error('Error deleting user', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete user');
        }
    }
}
