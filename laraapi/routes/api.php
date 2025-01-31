<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Car;
use App\Models\Brand;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Travel;

// post login
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = Str::random(80);
        
        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load(['car', 'location.city.country']),
            'token' => $token
        ]);
    }

    return response()->json([
        'message' => 'Invalid credentials'
    ], 401);
});

// Get all users
Route::get('/users', function () {
    $users = User::with(['car', 'location.city.country'])->get();
    return response()->json([
        'users' => $users
    ]);
});


// Get a specific user by ID
Route::get('/users/{id}', function ($id) {
    $user = User::with(['car', 'location.city.country'])->findOrFail($id);
    return response()->json([
        'user' => $user
    ]);
});


//create/register a new user
Route::post('/register', function (Request $request) {
    try {
        // Check if email already exists
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            return response()->json([
                'message' => 'Email already exists'
            ], 409);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string',
            'dob' => 'required|date',
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'bio' => 'nullable|string',
            'type' => 'nullable|string',
            'carseats' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Insert car into cars table and get the ID
        $car = Car::create([
            'type' => $validated['type'],
            'carseats' => $validated['carseats']
        ]);

        // Insert location into locations table and get the ID
        $location = Location::create([ 
            'address' => $validated['address'], 
            'city_id' => $validated['city_id'],
        ]);

        // Create the user with the retrieved location ID
        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'dob' => $validated['dob'],
            'location_id' => $location->id, 
            'bio' => $validated['bio'] ?? null,
            'car_id' => $car->id ?? null
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['car', 'location.city.country'])
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while creating the user',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Update a user by ID
Route::put('/users/{user}', function (Request $request, User $user) {
    try {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|string|unique:users,email,' . $user->id,
            'phone' => 'required|string',
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'bio' => 'nullable|string',
            'dob' => 'required|string',
            'type' => 'nullable|string',
            'carseats' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $car = Car::firstOrCreate(
            ['type' => $validated['type'], 'carseats' => $validated['carseats']]
        );

        // Then, handle the location update/creation
        $location = Location::firstOrCreate(
            ['address' => $validated['address'], 'city_id' => $validated['city_id']]
        );

        // Update the user with new car_id and location_id
        $user->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'bio' => $validated['bio'],
            'dob' => $validated['dob'],
            'car_id' => $car->id,
            'location_id' => $location->id
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load(['car', 'location.city.country'])
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while updating the user',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Patch a user by ID
Route::patch('/users/{user}', function (Request $request, User $user) {
    try {
        // Validate only the fields that are present in the request
        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|string',
            'lastname' => 'sometimes|string',
            'email' => 'sometimes|string|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'phone' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'address' => 'sometimes|string',
            'city_id' => 'sometimes|exists:cities,id',
            'bio' => 'sometimes|nullable|string',
            'car_id' => 'sometimes|nullable|exists:cars,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // If address or city_id is being updated, update or create new location
        if (isset($validated['address']) || isset($validated['city_id'])) {
            $locationData = [
                'address' => $validated['address'] ?? $user->location->address,
                'city_id' => $validated['city_id'] ?? $user->location->city_id
            ];

            // Update existing location or create new one
            if ($user->location) {
                $user->location->update($locationData);
            } else {
                $location = Location::create($locationData);
                $validated['location_id'] = $location->id;
            }

            // Remove address and city_id from validated data as they're handled separately
            unset($validated['address'], $validated['city_id']);
        }

        // Hash password if it's being updated
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Update user with validated data
        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load(['car.brand', 'location.city.country'])
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while updating the user',
            'error' => $e->getMessage()
        ], 500);
    }
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

Route::get('/travels', function () {
    // check if there are any future travels
    $basicCheck = DB::select('SELECT COUNT(*) as count FROM travels WHERE date >= CURDATE()');
    
    // Get the main query results
    $travels = DB::select(
    'SELECT 
        travels.id AS travel_id,
        destination.address AS destination_address,
        startlocation.address AS start_location_address,
        travels.date AS travel_date,
        travels.fee AS travel_fee,
        travels.km AS travel_km,
        travels.price AS travel_price,
        travels.av_seats AS travel_av_seats,
        destination.city_id AS destination_city_id,
        startlocation.city_id AS start_city_id,
        destination_city.name AS destination_city_name,
        start_city.name AS start_city_name,
        destination_city.country_id AS destination_country_id,
        start_city.country_id AS start_country_id,
        destination_country.name AS destination_country_name,
        start_country.name AS start_country_name,
        users.id AS driver_id,
        users.firstname AS driver_firstname,
        users.lastname AS driver_lastname,
        cars.id AS car_id,
        cars.type AS car_type,
        cars.carseats AS car_carseats,
        COALESCE(passengers.passengers_count, 0) AS passengers_count
        

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
        
    LEFT JOIN 
        (SELECT travel_id, COUNT(*) AS passengers_count
         FROM user_travel
         GROUP BY travel_id) AS passengers ON passengers.travel_id = travels.id
        
    WHERE 
        travels.date >= CURDATE()');

    return response()->json([
        'travels' => $travels
    ]);
});

// Get a specific travel by ID
Route::get('/travels/{id}', function ($id) {
    $travel = DB::select(
        'SELECT 
            travels.id AS travel_id,
            destination.address AS destination_address,
            startlocation.address AS start_location_address,
            travels.date AS travel_date,
            travels.fee AS travel_fee,
            travels.km AS travel_km,
            travels.price AS travel_price,
            travels.av_seats AS travel_av_seats,
            destination.city_id AS destination_city_id,
            startlocation.city_id AS start_city_id,
            destination_city.name AS destination_city_name,
            start_city.name AS start_city_name,
            destination_city.country_id AS destination_country_id,
            start_city.country_id AS start_country_id,
            destination_country.name AS destination_country_name,
            start_country.name AS start_country_name,
            users.id AS driver_id,
            users.firstname AS driver_firstname,
            users.lastname AS driver_lastname,
            cars.id AS car_id,
            cars.type AS car_type,
            cars.carseats AS car_carseats,
            COALESCE(passengers.passengers_count, 0) AS passengers_count

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

        LEFT JOIN 
            (SELECT travel_id, COUNT(*) AS passengers_count
             FROM user_travel
             GROUP BY travel_id) AS passengers ON passengers.travel_id = travels.id
        
        WHERE 
            travels.id = :id',
            ['id' => $id]
    );

    return response()->json([
        'travel' => $travel
    ]);
});

// post passenger
Route::post('/passengers', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'travel_id' => 'required|exists:travels,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Maak de passenger aan
    try {
        DB::beginTransaction();

        $passenger = DB::table('user_travel')->insert([
            'user_id' => $request->user_id,
            'travel_id' => $request->travel_id,
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Passenger added successfully',
            'passenger' => $passenger
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'An error occurred while adding the passenger',
            'error' => $e->getMessage()
        ], 500);
    }
});

// delete passenger
Route::delete('/passengers/{id}', function ($id) {
    $passenger = DB::table('user_travel')->where('id', $id)->first();

    if (!$passenger) {
        return response()->json([
            'message' => 'Passenger not found',
            'errors' => ['id' => ['Invalid passenger specified']]
        ], 422);
    }

    DB::table('user_travel')->where('id', $id)->delete();

    return response()->json([
        'message' => 'Passenger deleted successfully'
    ]);
});

//posttravel
Route::post('/travels', function (Request $request) {
    $car = DB::table('cars')->where('id', $request->car_id)->first();
        
    if (!$car) {
        return response()->json([
            'message' => 'Car not found',
            'errors' => ['car_id' => ['Invalid car specified']]
        ], 422);
    }

    $maxSeats = $car->carseats - 1; // Subtract 1 for the driver
    $validator = Validator::make($request->all(), [
        'destination_id' => 'required|exists:locations,id',
        'startlocation_id' => 'required|exists:locations,id',
        'date' => 'required|date|after_or_equal:today',
        'fee' => 'required|numeric|min:0',
        'km' => 'required|numeric|min:0',
        'price' => 'required|numeric|min:0',
        'user_id' => 'required|exists:users,id',
        'car_id' => 'required|exists:cars,id',
        'av_seats' => ['required', 'numeric', 'min:1', "max:{$maxSeats}"]
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Maak de reis aan
    try {
        DB::beginTransaction();

        $travel = DB::table('travels')->insertGetId([
            'destination_id' => $request->destination_id,
            'startlocation_id' => $request->startlocation_id,
            'date' => $request->date,
            'fee' => $request->fee,
            'km' => $request->km,
            'price' => $request->price,
            'user_id' => $request->user_id,
            'car_id' => $request->car_id,
            'av_seats' => $request->av_seats,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Travel created successfully',
            'travel_id' => $travel
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'An error occurred while creating the travel',
            'error' => $e->getMessage()
        ], 500);
    }
});

