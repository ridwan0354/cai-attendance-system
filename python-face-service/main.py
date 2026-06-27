"""
CAI LOMBOK 2026 - Face Recognition Service
Python FastAPI + DeepFace (ArcFace model)

SPEED OPTIMIZED:
  - opencv detector  (~0.3s vs retinaface ~10s)
  - Single DeepFace.find() call per request (no double extract)
  - Thread pool for non-blocking async
  - Model pre-warm on startup

Endpoint:
  POST /recognize  - Identifikasi wajah dari base64 image
  GET  /health     - Health check
  POST /register   - Daftarkan wajah baru ke face database
"""

import os
import base64
import asyncio
import logging
import shutil
from io import BytesIO
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor

import cv2
import numpy as np
from PIL import Image
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from deepface import DeepFace

# ─── Config ───────────────────────────────────────────────────────────────────
FACE_DB_PATH = "./face_db"
MODEL_NAME   = "ArcFace"   # Best accuracy, cached after first load
DETECTOR     = "opencv"    # OpenCV: completely offline, no downloads, very fast
METRIC       = "cosine"
THRESHOLD    = 0.60        # Cosine distance: 0=identical, 1=different. Balanced for ArcFace (default is 0.68)

# Thread pool for blocking DeepFace calls
executor = ThreadPoolExecutor(max_workers=3)

# Global flag to dynamically refresh database representations cache on changes
db_needs_refresh = False

# ─── Logging ──────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%H:%M:%S"
)
logger = logging.getLogger("cai-face")

# ─── App ──────────────────────────────────────────────────────────────────────
app = FastAPI(
    title="CAI LOMBOK 2026 Face Recognition Service",
    description="DeepFace ArcFace recognition engine - speed optimized",
    version="2.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─── Request Models ────────────────────────────────────────────────────────────
class RecognizeRequest(BaseModel):
    image: str
    session_id: int | None = None
    detect_face: bool | None = False

class RegisterRequest(BaseModel):
    participant_id: int
    name: str
    image: str

# ─── Helpers ──────────────────────────────────────────────────────────────────
def b64_to_cv2(b64: str) -> np.ndarray:
    """Decode base64 image to OpenCV BGR array."""
    if "," in b64:
        b64 = b64.split(",")[1]
    b64 = b64.replace(" ", "+")
    raw = base64.b64decode(b64)
    img = Image.open(BytesIO(raw)).convert("RGB")
    return cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)


def clear_pkl_cache():
    """Remove DeepFace representations cache so it rebuilds with new photos."""
    for pkl in Path(FACE_DB_PATH).glob("*.pkl"):
        pkl.unlink()
        logger.info(f"Cleared cache: {pkl.name}")


def _recognize_sync(img_array: np.ndarray, detect_face: bool = False) -> list[dict]:
    """
    Synchronous face recognition — runs in thread pool.
    Loads representations from pickle and manually computes cosine distance.
    This gives 100% visibility on matching scores and bypasses DeepFace.find limitations.
    """
    global db_needs_refresh
    import time
    import pickle
    t0 = time.time()

    # 1. Extract embedding of the query face (using skip since query is cropped, or configured detector)
    try:
        backend = "skip"
        if detect_face:
            backend = DETECTOR
            logger.info("Detecting face in query image first...")
            
        query_res = DeepFace.represent(
            img_path=img_array,
            model_name=MODEL_NAME,
            detector_backend=backend,
            enforce_detection=False
        )
        if not query_res or len(query_res) == 0:
            logger.warning("Failed to extract embedding from query face.")
            return []
        query_emb = np.array(query_res[0]["embedding"])
    except Exception as e:
        logger.error(f"Error extracting query embedding: {e}")
        return []

    # 2. Find representations pickle file
    pkl_files = list(Path(FACE_DB_PATH).glob(f"ds_model_{MODEL_NAME.lower()}_detector_skip_*.pkl"))
    if not pkl_files or db_needs_refresh:
        # Re-index if needed (we can run a dummy DeepFace.find to force rebuild)
        try:
            logger.info("Rebuilding representations database...")
            DeepFace.find(
                img_path=img_array,
                db_path=FACE_DB_PATH,
                model_name=MODEL_NAME,
                distance_metric=METRIC,
                detector_backend="skip",
                enforce_detection=False,
                silent=True,
                refresh_database=True
            )
            pkl_files = list(Path(FACE_DB_PATH).glob(f"ds_model_{MODEL_NAME.lower()}_detector_skip_*.pkl"))
            db_needs_refresh = False
        except Exception as e:
            logger.error(f"Failed to rebuild pickle cache: {e}")
            return []

    if not pkl_files:
        logger.warning("No representations pickle file found.")
        return []

    # 3. Load database embeddings
    try:
        with open(str(pkl_files[0]), "rb") as f:
            db_representations = pickle.load(f)
    except Exception as e:
        logger.error(f"Failed to load pickle file: {e}")
        return []

    # 4. Compare query embedding against database embeddings manually
    matches = []
    logger.info(f"Comparing query face against {len(db_representations)} database entries:")
    
    for entry in db_representations:
        identity = None
        db_emb = None
        
        if isinstance(entry, dict):
            identity = entry.get("identity")
            db_emb = entry.get("embedding") or entry.get(f"{MODEL_NAME}_representation") or entry.get("representation")
        elif isinstance(entry, list) or isinstance(entry, tuple):
            if len(entry) >= 2:
                identity = entry[0]
                db_emb = entry[1]
        
        if identity is None or db_emb is None:
            continue
            
        # Compute cosine distance
        db_emb = np.array(db_emb)
        dot_product = np.dot(query_emb, db_emb)
        norm_q = np.linalg.norm(query_emb)
        norm_db = np.linalg.norm(db_emb)
        cosine_sim = dot_product / (norm_q * norm_db)
        distance = float(1 - cosine_sim)

        # Log exact distance
        participant_id = int(Path(identity).parent.name)
        confidence = round((1 - distance) * 100, 1)
        logger.info(f"   -> Participant {participant_id}: distance={distance:.4f}, confidence={confidence}%, threshold={THRESHOLD}")

        if distance <= THRESHOLD:
            logger.info(f"✅ Match! participant_id={participant_id}, confidence={confidence}%, dist={distance:.4f}")
            matches.append({
                "participant_id": participant_id,
                "confidence":     confidence,
                "distance":       round(distance, 4),
            })

    # Sort matches by distance (ascending)
    matches.sort(key=lambda m: m["distance"])
    
    elapsed = time.time() - t0
    logger.info(f"Manual matching took {elapsed:.2f}s (found {len(matches)} matches)")
    return matches


def _register_sync(participant_id: int, name: str, img_array: np.ndarray) -> dict:
    """Synchronous face registration — runs in thread pool."""
    global db_needs_refresh
    face_dir  = Path(FACE_DB_PATH) / str(participant_id)
    face_dir.mkdir(parents=True, exist_ok=True)
    save_path = face_dir / "photo.jpg"

    # Verify face is detectable and extract coordinates
    try:
        faces = DeepFace.extract_faces(
            img_path=img_array,
            detector_backend=DETECTOR,
            enforce_detection=True,
            align=True,
        )
        if not faces:
            return {"success": False, "error": "Wajah tidak terdeteksi dalam foto"}
        
        # Crop the face directly from the BGR image array using detected box
        area = faces[0]['facial_area']
        x, y, w, h = area['x'], area['y'], area['w'], area['h']
        
        # Add slight padding (10%) to crop area for better feature representation
        pad_x = int(w * 0.1)
        pad_y = int(h * 0.1)
        sx = max(0, x - pad_x)
        sy = max(0, y - pad_y)
        ew = min(img_array.shape[1] - sx, w + pad_x * 2)
        eh = min(img_array.shape[0] - sy, h + pad_y * 2)
        
        cropped_face = img_array[sy:sy+eh, sx:sx+ew]
        
        # Save ONLY the cropped face
        cv2.imwrite(str(save_path), cropped_face)
        
    except Exception as e:
        return {"success": False, "error": f"Wajah tidak terdeteksi: {str(e)}"}

    # Clear PKL cache so new face gets picked up on next recognition
    clear_pkl_cache()
    db_needs_refresh = True

    logger.info(f"✅ Registered participant {participant_id}: {name} (saved cropped face)")
    return {"success": True, "face_saved": str(save_path)}


# ─── Endpoints ────────────────────────────────────────────────────────────────
@app.get("/health")
async def health():
    db_path = Path(FACE_DB_PATH)
    count   = len([d for d in db_path.iterdir() if d.is_dir()]) if db_path.exists() else 0
    return {
        "status":                 "healthy",
        "model":                  MODEL_NAME,
        "detector":               DETECTOR,
        "face_db_path":           FACE_DB_PATH,
        "registered_participants": count,
        "threshold":              THRESHOLD,
    }


@app.post("/recognize")
async def recognize(req: RecognizeRequest):
    """
    Recognize faces in a video frame.
    Returns list of matched participants.
    """
    if not req.image:
        raise HTTPException(400, "image is required")

    db_path = Path(FACE_DB_PATH)
    if not db_path.exists() or not any(db_path.iterdir()):
        return {"success": True, "matches": [], "faces_found": 0, "message": "Face database empty"}

    try:
        img   = b64_to_cv2(req.image)
        loop  = asyncio.get_event_loop()
        hits  = await loop.run_in_executor(executor, _recognize_sync, img, req.detect_face)

        return {
            "success":    True,
            "matches":    hits,
            "faces_found": len(hits),
            "session_id": req.session_id,
        }
    except Exception as e:
        logger.error(f"Recognition failed: {e}")
        raise HTTPException(500, f"Recognition error: {e}")


@app.post("/register")
async def register(req: RegisterRequest):
    """
    Register a participant's face to the database.
    Clears PKL cache so next recognize() picks it up immediately.
    """
    try:
        img    = b64_to_cv2(req.image)
        loop   = asyncio.get_event_loop()
        result = await loop.run_in_executor(
            executor, _register_sync, req.participant_id, req.name, img
        )

        if not result["success"]:
            raise HTTPException(400, result["error"])

        return {
            "success":        True,
            "participant_id": req.participant_id,
            "name":           req.name,
            "face_saved":     result["face_saved"],
        }
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Registration failed: {e}")
        raise HTTPException(500, f"Registration error: {e}")


@app.delete("/register/{participant_id}")
async def delete_face(participant_id: int):
    global db_needs_refresh
    face_dir = Path(FACE_DB_PATH) / str(participant_id)
    if not face_dir.exists():
        raise HTTPException(404, "Participant face not found")

    shutil.rmtree(face_dir)
    clear_pkl_cache()
    db_needs_refresh = True
    return {"success": True, "message": f"Face {participant_id} removed"}


# ─── Startup: pre-warm model ──────────────────────────────────────────────────
@app.on_event("startup")
async def startup():
    logger.info("🚀 CAI Face Recognition Service starting...")
    logger.info(f"   Model:    {MODEL_NAME}")
    logger.info(f"   Detector: {DETECTOR} (fast mode)")
    logger.info(f"   Threshold:{THRESHOLD}")

    Path(FACE_DB_PATH).mkdir(parents=True, exist_ok=True)

    # Pre-warm: run a dummy recognition so TF model loads into memory
    # This prevents the first real request from being slow
    async def prewarm():
        try:
            dummy = np.zeros((100, 100, 3), dtype=np.uint8)
            loop  = asyncio.get_event_loop()
            await loop.run_in_executor(executor, _recognize_sync, dummy, True)
            logger.info("✅ Model and detector pre-warmed and ready!")
        except Exception as e:
            logger.info(f"Pre-warm done (expected no-match): {e}")

    asyncio.create_task(prewarm())
    logger.info("✅ Service ready!")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=False)
