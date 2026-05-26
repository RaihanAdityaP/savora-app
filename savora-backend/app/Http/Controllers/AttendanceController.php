<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private const ATTENDEE_TYPES = ['murid', 'guru', 'tamu_undangan'];
    private const MAJORS = ['pplg', 'tjkt', 'dkv', 'lk', 'ps'];

    public function __construct(private SupabaseService $supabase)
    {
    }

    public function index(): View
    {
        return view('attendance.index', [
            'attendeeTypes' => self::ATTENDEE_TYPES,
            'majors' => self::MAJORS,
        ]);
    }

    public function adminIndex(): View
    {
        $attendances = $this->getRecentAttendances();

        return view('admin.attendances', [
            'attendances' => $attendances,
            'stats' => $this->getStats($attendances),
        ]);
    }

    public function adminDestroy(string $id): RedirectResponse
    {
        try {
            $this->supabase->delete('event_attendances', ['id' => $id]);

            return back()->with('status', 'Data presensi berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Failed to delete attendance', ['id' => $id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Data presensi gagal dihapus: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'attendee_type' => ['required', 'in:' . implode(',', self::ATTENDEE_TYPES)],
            'major' => ['nullable', 'required_if:attendee_type,murid', 'in:' . implode(',', self::MAJORS)],
            'impression' => ['required', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'contact_number.required' => 'Nomor wajib diisi.',
            'contact_number.regex' => 'Nomor hanya boleh berisi angka.',
            'attendee_type.required' => 'Asal dari wajib dipilih.',
            'attendee_type.in' => 'Pilihan asal dari tidak valid.',
            'major.required_if' => 'Jurusan wajib dipilih untuk murid.',
            'major.in' => 'Pilihan jurusan tidak valid.',
            'impression.required' => 'Kesan wajib diisi.',
        ]);

        $attendeeType = $validated['attendee_type'];

        try {
            $this->supabase->insert('event_attendances', [
                'name' => trim($validated['name']),
                'contact_number' => trim($validated['contact_number']),
                'attendee_type' => $attendeeType,
                'major' => $attendeeType === 'murid' ? $validated['major'] : null,
                'impression' => trim($validated['impression']),
                'feedback' => isset($validated['feedback']) ? trim($validated['feedback']) : null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]);

            return redirect()
                ->route('attendance.index')
                ->with('success', 'Kehadiran berhasil dicatat, terimakasih telah berkunjung.');
        } catch (\Throwable $e) {
            Log::error('Failed to save attendance', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Kehadiran belum bisa disimpan. Pastikan table event_attendances sudah dibuat di Supabase.');
        }
    }

    private function getRecentAttendances(): array
    {
        try {
            return $this->supabase->select(
                'event_attendances',
                ['id', 'name', 'contact_number', 'attendee_type', 'major', 'impression', 'feedback', 'created_at'],
                [],
                ['order' => 'created_at.desc', 'limit' => 100]
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to load attendances', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function getStats(array $attendances): array
    {
        $stats = [
            'total' => count($attendances),
            'murid' => 0,
            'guru' => 0,
            'tamu_undangan' => 0,
        ];

        foreach ($attendances as $attendance) {
            $type = $attendance['attendee_type'] ?? null;
            if (isset($stats[$type])) {
                $stats[$type]++;
            }
        }

        return $stats;
    }
}
