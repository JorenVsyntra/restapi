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
use App\Models\Location;
use App\Models\Travel;

// Get all users
Route::get('/users', function () {
    $users = User::with(['car.brand', 'location.city.country'])->get();
    return response()->json([
        'users' => $users
    ]);
});


// Get a specific user by ID
Route::get('/users/{id}', function ($id) {
    $user = User::with(['car.brand', 'location.city.country'])->findOrFail($id);
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
        'location_id' => 'required|exists:locations,id',
        'bio' => 'sometimes',
        'car_id' => 'sometimes|exists:cars,id'
    ]);

    $validated['password'] = Hash::make($validated['password']);
    
    $user = User::create($validated);

    return response()->json([
        'message' => 'User created successfully',
        'user' => $user->load(['car.brand', 'location.city.country'])
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
        'location_id' => 'sometimes|exists:locations,id',
        'bio' => 'sometimes|string',
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
        'user' => $user->load(['car.brand', 'location.city.country'])
    ]);
});

// Delete user by id
Route::delete('/users/{id}', function ($id) {
    $user = User::findOrFail($id);
    $user->delete();
    return response()->json([
        'message' => 'User deleted successfully'
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

// Get a specific country by ID
Route::get('/countries/{id}', function ($id) {
    $country = Country::findOrFail($id);
    return response()->json([
        'country' => $country
    ]);
});

// Delete country by id
Route::delete('/countries/{id}', function ($id) {
    $country = Country::findOrFail($id);
    $country->delete();
    return response()->json([
        'message' => 'country deleted successfully'
    ]);
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

// Get a specific city by ID
Route::get('/cities/{id}', function ($id) {
    $city = City::with('country')->findOrFail($id);
    return response()->json([
        'city' => $city
    ]);
});

// Delete city by id
Route::delete('/cities/{id}', function ($id) {
    $city = City::findOrFail($id);
    $city->delete();
    return response()->json([
        'message' => 'city deleted successfully'
    ]);
});

// get all cars
Route::get('/cars', function () {
    $cars = Car::with('brand')->get();
    return response()->json([
        'cars' => $cars
    ]);
});

// create a new car
Route::post('/cars', function (Request $request) {
    $validated = $request->validate([
        'seats' => 'required|integer',
        'model' => 'required|string|unique:cars,model',
        'brand_id' => 'required|exists:brands,id'
    ]);
    
    $car = Car::create($validated);
    return response()->json([
        'message' => 'car created successfully',
        'car' => $car->load(['brand'])
    ], 201);
});

// Get a specific car by ID
Route::get('/cars/{id}', function ($id) {
    $car = Car::with('brand')->findOrFail($id);
    return response()->json([
        'car' => $car
    ]);
});

// Delete car by id
Route::delete('/cars/{id}', function ($id) {
    $car = Car::findOrFail($id);
    $car->delete();
    return response()->json([
        'message' => 'car deleted successfully'
    ]);
});

// Get all brands
Route::get('/brands', function () {
    $brands = Brand::with('cars')->get();
    return response()->json([
        'brands' => $brands
    ]);
});

// create a new brand
Route::post('/brands', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|unique:brands,name',
    ]);
    
    $brand = Brand::create($validated);
    return response()->json([
        'message' => 'brand created successfully',
        'brand' => $brand
    ], 201);
});

// Get a specific brand by ID
Route::get('/brands/{id}', function ($id) {
    $brand = Brand::with('cars')->findOrFail($id);
    return response()->json([
        'brand' => $brand
    ]);
});

// Delete brand by id
Route::delete('/brands/{id}', function ($id) {
    $brand = Brand::findOrFail($id);
    $brand->delete();
    return response()->json([
        'message' => 'brand deleted successfully'
    ]);
});

// Get all locations
Route::get('/locations', function () {
    $locations = Location::with('city.country')->get();
    return response()->json([
        'locations' => $locations
    ]);
});

// create a new location
Route::post('/locations', function (Request $request) {
    $validated = $request->validate([
        'address' => 'required|string|unique:locations,address',
        'city_id' => 'required|exists:cities,id'
    ]);
    
    $location = Location::create($validated);
    return response()->json([
        'message' => 'location created successfully',
        'location' => $location->load(['city.country'])
    ], 201);
});

// Get a specific location by ID
Route::get('/locations/{id}', function ($id) {
    $location = Location::with('city.country')->findOrFail($id);
    return response()->json([
        'location' => $location
    ]);
});

// Delete location by id
Route::delete('/locations/{id}', function ($id) {
    $location = Location::findOrFail($id);
    $location->delete();
    return response()->json([
        'message' => 'location deleted successfully'
    ]);
});

// Get all future travels
Route::get('/travels', function () {
        $travels = DB::select(
        'SELECT 
            travels.id AS travel_id,
            destination.address AS destination_address,
            startlocation.address AS start_location_address,
            travels.date AS travel_date,
            travels.fee AS travel_fee,
            travels.km AS travel_km,
            destination.city_id AS destination_city_id,
            startlocation.city_id AS start_city_id,
            destination_city.name AS destination_city_name,
            start_city.name AS start_city_name,
            destination_city.country_id AS destination_country_id,
            start_city.country_id AS start_country_id,
            destination_country.name AS destination_country_name,
            start_country.name AS start_country_name,
            users.id AS user_id,
            users.firstname AS user_firstname,
            users.lastname AS user_lastname,
            cars.id AS car_id,
            cars.model AS car_model,
            cars.seats AS car_seats,
            cars.brand_id AS car_brand_id,
            brands.name AS car_brand_name,
            brands.id AS car_brand_id

        FROM 
            travels
        JOIN 
            locations AS destination ON destination.id = travels.destination_id
        JOIN 
            locations AS startlocation ON startlocation.id = travels.startlocation_id
        JOIN 
            cities AS destination_city ON destination_city.id = destination.city_id
        JOIN 
            cities AS start_city ON start_city.id = startlocation.city_id
        JOIN 
            countries AS destination_country ON destination_country.id = destination_city.country_id
        JOIN 
            countries AS start_country ON start_country.id = start_city.country_id
        JOIN
            users ON users.id = travels.user_id
        JOIN
            cars ON cars.id = travels.car_id
        JOIN
            brands ON brands.id = cars.brand_id
        WHERE 
            travels.date >= CURDATE();');
    return response()->json([
        'travels' => $travels
    ]);
});

