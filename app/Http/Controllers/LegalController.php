<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    /**
     * Display the privacy policy page.
     */
    public function privacy()
    {
        return view('legal.privacy');
    }

    /**
     * Display the terms of service page.
     */
    public function terms()
    {
        return view('legal.terms');
    }
}
