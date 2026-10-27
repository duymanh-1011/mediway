<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;

class PortalController extends Controller
{
    public function placeholder()
    {
        return view('portal.home');
    }
}
