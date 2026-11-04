<?php

namespace Database\Factories;

use App\Enums\AppMobileRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    // Tago barangays (Surigao del Sur)
    private static array $barangays = [
        'Alba', 'Anahao Bag-o', 'Badong', 'Bajao', 'Bangsud',
        'Cabangahan', 'Cagdapao', 'Camagong', 'Cayale', 'Dayo-an',
        'Gamut', 'Jubang', 'Kinabigtasan', 'Layog', 'Mercedes',
        'Purisima', 'Sumo-sumo', 'Umbay', 'Unaban', 'Victoria',
    ];

    // Sample street names
    private static array $streets = [
        'Del Pilar St.', 'Fernandez St.', 'JP Rizal St.',
        'Mabini St.', 'National Highway', 'P. Burgos St.',
        'Quezon St.', 'Aguinaldo St.', 'Luna St.',
        'Bonifacio St.', 'Magsaysay St.', 'Roxas Ave.',
    ];

    // Tago bounding box
    private const LAT_MIN  = 8.8800;
    private const LAT_MAX  = 9.0300;
    private const LNG_MIN  = 126.0900;
    private const LNG_MAX  = 126.2400;

    private function tagoAddress(): string
    {
        $houseNo  = fake()->numberBetween(1, 999);
        $street   = fake()->randomElement(self::$streets);
        $barangay = fake()->randomElement(self::$barangays);

        return "{$houseNo} {$street}, Brgy. {$barangay}, Tago, Surigao del Sur";
    }

    private function tagoLatitude(): float
    {
        return fake()->randomFloat(7, self::LAT_MIN, self::LAT_MAX);
    }

    private function tagoLongitude(): float
    {
        return fake()->randomFloat(7, self::LNG_MIN, self::LNG_MAX);
    }

    public function definition(): array
    {
        $fname = fake()->firstName();
        $lname = fake()->lastName();

        return [
            'fname'              => $fname,
            'lname'              => $lname,
            'name'               => $fname . ' ' . $lname,
            'mname'              => fake()->randomLetter(),
            'email'              => fake()->unique()->safeEmail(),
            'confirmed_at'       => now(),
            'image'              => 'image_path.png',
            'address'            => $this->tagoAddress(),
            'latitude'           => $this->tagoLatitude(),
            'longitude'          => $this->tagoLongitude(),
            'phone'              => fake()->numerify('09#########'),
            'email_verified_at'  => now(),
            'app_role'           => AppMobileRole::Citizen,
            'password'           => static::$password ??= Hash::make('password'),
            'remember_token'     => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
