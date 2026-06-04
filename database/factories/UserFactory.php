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

    // Tandag City barangays (Surigao del Sur)
    private static array $barangays = [
        'Awasian', 'Bagong', 'Banahao', 'Bioto', 'Bongtod Pob.',
        'Buenavista', 'Cantapoy', 'Dagocdoc', 'Figueroa', 'Mabini',
        'Mabua', 'Mabuhay', 'Quezon', 'Rosario', 'Sabang',
        'Salvacion', 'San Isidro', 'Telaje',
    ];

    // Common streets in Tandag City
    private static array $streets = [
        'Del Pilar St.', 'Fernandez St.', 'JP Rizal St.',
        'Mabini St.', 'National Highway', 'P. Burgos St.',
        'Quezon St.', 'Aguinaldo St.', 'Luna St.',
        'Bonifacio St.', 'Magsaysay St.', 'Roxas Ave.',
    ];

    // Tandag City bounding box
    private const LAT_MIN  = 9.0200;
    private const LAT_MAX  = 9.1400;
    private const LNG_MIN  = 126.1600;
    private const LNG_MAX  = 126.2500;

    private function tandagAddress(): string
    {
        $houseNo  = fake()->numberBetween(1, 999);
        $street   = fake()->randomElement(self::$streets);
        $barangay = fake()->randomElement(self::$barangays);

        return "{$houseNo} {$street}, Brgy. {$barangay}, Tandag City, Surigao del Sur";
    }

    private function tandagLatitude(): float
    {
        return fake()->randomFloat(7, self::LAT_MIN, self::LAT_MAX);
    }

    private function tandagLongitude(): float
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
            'address'            => $this->tandagAddress(),
            'latitude'           => $this->tandagLatitude(),
            'longitude'          => $this->tandagLongitude(),
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
