<?php


namespace App\Controllers;


class HomeController
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function contact()
    {
        return view('contact', ['test' => __METHOD__]);
    }
}