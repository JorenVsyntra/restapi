<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Car;
use App\Models\Brand;
use App\Models\City;
use App\Models\Country;
use App\Models\Travel;

// Get all users
Route::get('/users', function () {
    $users = User::with(['car.brand', 'city.country'])->get();
    return response()->json([
        'users' => $users
    ]);
});


// Get a specific user by ID
Route::get('/users/{id}', function ($id) {
    $user = User::with(['car.brand', 'city.country'])->findOrFail($id);
    return response()->json([
        'user' => $user
    ]);
});

// Create a new user
Route::post('/users', function (Request $request) {
    $validated = $request->validate([
        'firstname' => 'required|string',
        'lastname' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required',
        'phone' => 'required',
        'streetnum' => 'required',
        'city_id' => 'required|exists:cities,id',
        'car_id' => 'required|exists:cars,id'
    ]);

    $validated['password'] = Hash::make($validated['password']);
    
    $user = User::create($validated);

    return response()->json([
        'message' => 'User created successfully',
        'user' => $user->load(['car.brand', 'city.country'])
    ], 201);
});

// Update user by id
Route::patch('/users/{id}', function (Request $request, $id) {
    $user = User::findOrFail($id);
    
    // Validation rules
    $rules = [
        'firstname' => 'sometimes|string',
        'lastname' => 'sometimes|string',
        'email' => [
            'sometimes',
            'email',
            Rule::unique('users')->ignore($user->id),
        ],
        'password' => 'sometimes|string',
        'phone' => 'sometimes|string',
        'streetnum' => 'sometimes|string',
        'city_id' => 'sometimes|exists:cities,id',
        'car_id' => 'sometimes|exists:cars,id'
    ];
    
    $validated = $request->validate($rules);
    
    // Hash password if it's being updated
    if (isset($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    }
    
    $user->update($validated);
    
    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user->load(['car.brand', 'city.country'])
    ]);
});

// Get all countries
Route::get('/countries', function () {
    $countries = Country::get();
    return response()->json([
        'countries' => $countries
    ]);
});

// create a new country
Route::post('/countries', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|unique:countries,name',
    ]);
    
    $country = Country::create($validated);
    return response()->json([
        'message' => 'country created successfully',
        'country' => $country
    ], 201);
});

// Get all cities
Route::get('/cities', function () {
    $cities = City::with('country')->get();
    return response()->json([
        'cities' => $cities
    ]);
});

// create a new city
Route::post('/cities', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|unique:cities,name',
        'country_id' => 'required|exists:countries,id'
    ]);
    
    $city = City::create($validated);
    return response()->json([
        'message' => 'city created successfully',
        'city' => $city->load(['country'])
    ], 201);
});
