<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Services\FaceRecognitionService;

class ScannerController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    public function index()
    {
        $activeSession = Session::getActive();
        $faceServiceHealthy = $this->faceService->isHealthy();
        $apiKey = config('mobile.api_key', env('MOBILE_API_KEY', ''));

        return view('scanner.index', compact('activeSession', 'faceServiceHealthy', 'apiKey'));
    }
}
