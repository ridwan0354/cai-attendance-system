<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Participant;
use App\Models\Session;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function __construct(private FaceRecognitionService $faceService) {}

    public function index(Request $request)
    {
        $query = Participant::with('group');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('group_id')) {
            $groupId = $request->input('group_id');
            $query->where('group_id', $groupId);
        }

        $participants = $query->orderBy('name')->paginate(20)->withQueryString();
        $groups = Group::orderBy('name')->get();

        return view('admin.participants.index', compact('participants', 'groups'));
    }

    public function create()
    {
        $groups = Group::all();
        return view('admin.participants.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'group_id'    => 'required|exists:groups,id',
            'gender'      => 'required|string|in:Laki-laki,Perempuan',
            'phone'       => 'required|string|max:20',
            'photo'       => 'nullable|image|max:4096',
            'face_base64' => 'nullable|string', // from webcam capture
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('participants', 'public');
        }

        $participant = Participant::create([
            'name'       => $validated['name'],
            'group_id'   => $validated['group_id'],
            'gender'     => $validated['gender'],
            'phone'      => $validated['phone'],
            'photo_path' => $photoPath,
        ]);

        // ── Register face to DeepFace database ───────────────────────
        // Priority 1: webcam base64 capture
        $base64 = $request->input('face_base64');

        // Priority 2: uploaded file (convert to base64)
        if (empty($base64) && $request->hasFile('photo')) {
            $base64 = base64_encode(file_get_contents($request->file('photo')->getRealPath()));
        }

        if (!empty($base64)) {
            $result = $this->faceService->registerFace($participant->id, $participant->name, $base64);
            if ($result['success'] ?? false) {
                $participant->update(['face_registered' => true]);
            } else {
                // Face registration failed — still save participant, warn user
                return redirect()->route('admin.participants.index')
                    ->with('warning', "Peserta {$participant->name} ditambahkan, tapi wajah gagal didaftarkan: " . ($result['error'] ?? 'Coba lagi via tombol Edit'));
            }
        }

        return redirect()->route('admin.participants.index')
            ->with('success', "Peserta {$participant->name} berhasil ditambahkan" . (!empty($base64) ? ' + wajah terdaftar ✅' : ' (belum ada foto wajah)') . '.');
    }

    public function edit(Participant $participant)
    {
        $groups = Group::all();
        return view('admin.participants.edit', compact('participant', 'groups'));
    }

    public function update(Request $request, Participant $participant)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'group_id' => 'required|exists:groups,id',
            'gender'   => 'required|string|in:Laki-laki,Perempuan',
            'phone'    => 'required|string|max:20',
        ]);

        $participant->update($validated);
        return redirect()->route('admin.participants.index')->with('success', 'Peserta diperbarui.');
    }

    public function destroy(Participant $participant)
    {
        $this->faceService->deleteFace($participant->id);
        $participant->delete();
        return redirect()->route('admin.participants.index')->with('success', 'Peserta dihapus.');
    }

    /**
     * Register/update face for a participant via webcam capture.
     * POST /admin/participants/{participant}/register-face
     */
    public function registerFace(Request $request, Participant $participant)
    {
        $request->validate(['image' => 'required|string']);

        $result = $this->faceService->registerFace(
            $participant->id,
            $participant->name,
            $request->input('image')
        );

        if ($result['success'] ?? false) {
            $participant->update(['face_registered' => true]);
            return response()->json(['success' => true, 'message' => 'Wajah berhasil didaftarkan']);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Gagal mendaftarkan wajah'
        ], 422);
    }

    /**
     * Serve the participant's registered face photo from filesystem.
     */
    public function faceImage(Participant $participant)
    {
        $path = base_path('../python-face-service/face_db/' . $participant->id . '/photo.jpg');

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
