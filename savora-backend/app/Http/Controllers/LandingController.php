<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function license()
    {
        return view('license');
    }
}
