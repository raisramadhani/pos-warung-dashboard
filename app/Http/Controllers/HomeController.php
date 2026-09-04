<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            if ($user->role === RoleType::Merchant) {
                $url = Filament::getPanel('merchant')->getUrl();
            } else {
                $url = Filament::getPanel('admin')->getUrl();
            }
        } else {
            $url = Filament::getLoginUrl();
        }

        return redirect($url);
    }
}
