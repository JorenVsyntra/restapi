<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Get all users
Route::get('/users', function () {
    $users = DB::select('SELECT * FROM users');
    return response()->json($users);
});

// Get a specific user by ID
Route::get('/users/{id}', function ($id) {
    $user = DB::select('SELECT * FROM users WHERE id = ?', [$id]);
    if (empty($user)) {
        return response()->json(['message' => 'User not found'], 404);
    }
    return response()->json($user[0]);
});

// Create a new user
Route::post('/users', function (\Illuminate\Http\Request $request) {
    $name = $request->input('name');
    $email = $request->input('email');
    $password = $request->input('password');

    DB::insert('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', [$name, $email, $password]);

    return response()->json(['message' => 'User created successfully'], 201);
});

// Update a user by ID
Route::put('/users/{id}', function (\Illuminate\Http\Request $request, $id) {
    $name = $request->input('name');
    $email = $request->input('email');
    $password = $request->input('password');

    $affected = DB::update('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?', [$name, $email, $password, $id]);

    if ($affected === 0) {
        return response()->json(['message' => 'User not found or no changes made'], 404);
    }
    return response()->json(['message' => 'User updated successfully']);
});

// Delete a user by ID
Route::delete('/users/{id}', function ($id) {
    $deleted = DB::delete('DELETE FROM users WHERE id = ?', [$id]);
    if ($deleted === 0) {
        return response()->json(['message' => 'User not found'], 404);
    }
    return response()->json(['message' => 'User deleted successfully']);
});
