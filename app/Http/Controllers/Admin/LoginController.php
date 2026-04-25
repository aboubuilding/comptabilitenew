<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class LoginController extends Controller
{

    /**
     * Afficher le login
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function login()
    {
        return view('admin.login');


    }


    /**
     * Se deconnecter
     *
     * @return \Illuminate\Contracts\Foundation\Application|
     * \Illuminate\Http\RedirectResponse|
     * \Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {

        $request->session()->forget('LoginUser');

        return redirect('/login');

    }



}
