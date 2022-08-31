<?php

namespace App\Providers;

use App\Models\Group;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return config('app.frontend_url') . "/reset-password/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Gate::define('access-group', function ($user, $groupID) {
            $group = Group::find($groupID);
            if (is_null($group)) {
                return false;
            }
            return $group->members->contains($user);
        });
    }
}
