<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'whatsapp_phone', 'active', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'active'            => 'boolean',
        ];
    }

    public function assignedStudents()
    {
        return $this->hasMany(Student::class, 'assigned_cs_agent_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApplicationAgent(): bool
    {
        return $this->role === 'application';
    }

    public function isAdminOrApplication(): bool
    {
        return $this->isAdmin() || $this->isApplicationAgent();
    }

    public function isCsAgent(): bool
    {
        return $this->role === 'cs_agent';
    }

    public function isSalesAgent(): bool
    {
        return $this->role === 'sales_agent';
    }

    public function assignedSalesLeads()
    {
        return $this->hasMany(Student::class, 'assigned_sales_agent_id')->salesLeadsOnly();
    }

    /**
     * The SalesConsultant record linked to this user (sales_agent role only).
     * Lets the sales UI surface their historical book of business — students
     * that came in via the form webhook with their name as Sales Advisor.
     */
    public function salesConsultant()
    {
        return $this->hasOne(SalesConsultant::class);
    }

    /**
     * The route this user should land on after login or when visiting /.
     * Admin / Applications → admin dashboard. CS agents → /my dashboard.
     * Sales agents → sales kanban.
     */
    public function defaultRoute(): string
    {
        if ($this->isCsAgent()) {
            return route('my.dashboard', absolute: false);
        }
        if ($this->isSalesAgent()) {
            return route('sales.kanban', absolute: false);
        }
        return route('admin.dashboard', absolute: false);
    }
}
