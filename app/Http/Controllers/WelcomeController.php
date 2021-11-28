<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WelcomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response|BinaryFileResponse
     */
    public function download(Request $request)
    {
        switch (true) {
            case $request->is('download-collection/resize'):
                $file = public_path() . '/devtools/resize/postman_collection.json';
                break;
            case $request->is('download-collection/convert'):
                $file = public_path() . '/devtools/convert/postman_collection.json';
                break;
            case $request->is('download-collection/crop'):
                $file = public_path() . '/devtools/crop/postman_collection.json';
                break;
            case $request->is('download-collection/optimize'):
                $file = public_path() . '/devtools/optimize/postman_collection.json';
                break;
            case $request->is('download-collection/rotate'):
                $file = public_path() . '/devtools/rotate/postman_collection.json';
                break;
            case $request->is('download-collection/watermark'):
                $file = public_path() . '/devtools/watermark/postman_collection.json';
                break;
            default:
                return response('Not Found', 404);
        }

        $name = basename($file);
        $headers = array(
            'Content-Disposition' => 'inline'
        );
        return response()->download($file, $name, $headers);
    }
}
