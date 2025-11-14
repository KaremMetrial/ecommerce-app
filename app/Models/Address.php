<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city . ', ' . $this->state . ' ' . $this->postal_code,
            $this->country,
        ];

        return implode("\n", array_filter($parts));
    }

    public function getOneLineAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    public function setAsDefault(): void
    {
        // Remove default status from other addresses of the same type
        static::where('user_id', $this->user_id)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public static function getDefaultForUser(User $user, string $type): ?self
    {
        return static::where('user_id', $user->id)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();
    }

    public static function createForUser(User $user, array $data, bool $isDefault = false): self
    {
        $address = static::create(array_merge($data, [
            'user_id' => $user->id,
        ]));

        if ($isDefault) {
            $address->setAsDefault();
        }

        return $address;
    }
}
