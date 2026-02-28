<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;

class LoginLogger
{
  public function log(User $user, Request $request): void
  {
    UserLogin::create([
      'user_id' => $user->id,
      'ip_address' => $request->ip(),
      'user_agent' => substr($request->userAgent(), 0, 255),
      'logged_in_at' => now(),
    ]);
  }
}
