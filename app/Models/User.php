<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function defaultShippingAddress(): ?Address
    {
        return $this->addresses()->byType('shipping')->default()->first();
    }

    public function defaultBillingAddress(): ?Address
    {
        return $this->addresses()->byType('billing')->default()->first();
    }

    public function getCart(): Cart
    {
        return $this->cart ?? Cart::getForUser($this);
    }

    public function getDefaultWishlist(): Wishlist
    {
        return Wishlist::getDefaultForUser($this);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->sum('total');
    }

    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getCompletedOrdersAttribute(): int
    {
        return $this->orders()->where('status', 'delivered')->count();
    }

    public function getPendingOrdersAttribute(): int
    {
        return $this->orders()->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])->count();
    }
}
