<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function help(): View
    {
        return view('pages.help');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function submitAdvertise(): View
    {
        return view('pages.submit-advertise');
    }

    public function termsPolicies(): View
    {
        return view('pages.terms-policies');
    }

    public function editorialStandards(): View
    {
        return view('pages.editorial-standards');
    }
}
