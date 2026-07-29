<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'credential_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_active' => 'boolean',
            'credential_expires_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && (! $this->credential_expires_at || $this->credential_expires_at->isFuture());
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $initials = collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96">'
            .'<rect width="96" height="96" rx="28" fill="#1a4e5c"/>'
            .'<text x="48" y="57" text-anchor="middle" font-family="Arial,sans-serif" font-size="30" font-weight="700" fill="#f3f3f3">'
            .e($initials)
            .'</text></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
