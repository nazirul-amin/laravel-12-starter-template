<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends BaseResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', User::class);
        $query = User::query()->select(['id', 'name', 'email', 'created_by', 'created_at', 'updated_at']);

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
    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);
        $data = $request->validated();
        $generatedPassword = Str::random(12);
        $data['password'] = $generatedPassword;
        $data['created_by'] = $request->user()->id;

        return $this->attemptTransaction(
            function () use ($data) {
                $user = User::create($data);
                $user->assignRole('user');

                return $user;
            },
            function ($user) use ($generatedPassword) {
                Mail::to($user->email)->send(new UserCreated($user, $generatedPassword));
            },
            'users.index',
            'User created',
            'Failed to create user',
            'Error creating user'
        );
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
    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);
        $data = $request->validated();

        return $this->attemptTransaction(
            function () use ($user, $data) {
                $user->update($data);

                return $user;
            },
            null,
            'users.index',
            'User updated',
            'Failed to update user',
            'Error updating user'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        return $this->attemptTransaction(
            function () use ($user) {
                $user->delete();

                return true;
            },
            null,
            'users.index',
            'User deleted',
            'Failed to delete user',
            'Error deleting user'
        );
    }
}
