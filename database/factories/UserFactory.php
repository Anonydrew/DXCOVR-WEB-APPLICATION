<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Assign one of the default roles (permissions are synced on demand).
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            if (! Role::query()->where('name', $role)->exists()) {
                Artisan::call('dxcovr:sync-permissions');
            }

            $user->assignRole($role);
        });
    }

    public function superAdmin(): static
    {
        return $this->withRole(Permissions::SUPER_ADMIN_ROLE);
    }
}
