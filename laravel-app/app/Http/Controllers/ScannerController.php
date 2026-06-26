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

        return view('scanner.index', compact('activeSession', 'faceServiceHealthy'));
    }
}
