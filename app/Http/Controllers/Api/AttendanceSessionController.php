<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Support\CurrentChurch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceSessionController extends Controller
{
    /**
     * GET /attendance/sessions
     *
     * List attendance sessions.
     */
    public function index(): JsonResponse
    {
        $sessions = AttendanceSession::query()
            ->withCount('records')
            ->latest('id')
            ->paginate(15);

        return response()->json($sessions);
    }

    /**
     * POST /attendance/sessions
     *
     * Create an open attendance session.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['sometimes', 'integer', Rule::exists('services', 'id')
                ->where('church_id', app(CurrentChurch::class)->id())->where('is_active', true)],
            'title' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        $session = AttendanceSession::query()->create([
            'service_id' => $data['service_id'] ?? null,
            'title' => $data['title'],
            'status' => 'open',
            'started_at' => now(),
            'started_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Attendance session created.',
            'session' => $session,
        ], 201);
    }

    /**
     * GET /attendance/sessions/{attendanceSession}
     *
     * Show an individual attendance session.
     */
    public function show(
        AttendanceSession $attendanceSession
    ): JsonResponse {
        $attendanceSession->loadCount('records');

        return response()->json([
            'session' => $attendanceSession,
        ]);
    }

    /**
     * POST /attendance/sessions/{attendanceSession}/check-ins
     *
     * Manually check in one Member.
     */
    public function checkIn(
        Request $request,
        AttendanceSession $attendanceSession
    ): JsonResponse {
        $churchId = app(CurrentChurch::class)->id();

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('church_id', $churchId),
            ],
        ]);

        $record = DB::transaction(
            function () use (
                $request,
                $attendanceSession,
                $data,
                $churchId
            ): AttendanceRecord {
                // Serialize operations on this session.
                $session = AttendanceSession::query()
                    ->whereKey($attendanceSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    (int) $session->church_id === $churchId,
                    404,
                    'Attendance session not found.'
                );

                abort_if(
                    $session->status !== 'open',
                    409,
                    'This attendance session is closed.'
                );

                // Global church scope also applies here.
                $member = User::query()
                    ->whereKey($data['user_id'])
                    ->firstOrFail();

                abort_unless(
                    (int) $member->church_id === $churchId,
                    422,
                    'Member belongs to another church.'
                );

                abort_unless(
                    $member->hasRole('Member'),
                    422,
                    'Only approved Members can be checked in.'
                );

                abort_unless(
                    $member->email_verified_at !== null,
                    422,
                    'Member email must be verified.'
                );

                $profile = $member->memberProfile()->where('church_id', $churchId)->first();

                abort_unless(
                    $profile !== null,
                    422,
                    'Member profile is required.'
                );

                $alreadyCheckedIn = AttendanceRecord::query()
                    ->where(
                        'attendance_session_id',
                        $session->id
                    )
                    ->where('member_id', $profile->id)
                    ->exists();

                abort_if(
                    $alreadyCheckedIn,
                    409,
                    'Member has already checked in.'
                );

                return $session->records()->create([
                    'member_id' => $profile->id,
                    'checked_in_by' => $request->user()->id,
                    'check_in_method' => 'manual',
                    'checked_in_at' => now(),
                ]);
            }
        );

        return response()->json([
            'message' => 'Member checked in successfully.',
            'record' => $record,
        ], 201);
    }

    /**
     * GET /attendance/sessions/{attendanceSession}/records
     *
     * List attendance records and basic member details.
     */
    public function records(
        AttendanceSession $attendanceSession
    ): JsonResponse {
        $records = $attendanceSession->records()
            ->with('member.user:id,name,church_id')
            ->latest('checked_in_at')
            ->paginate(25);

        return response()->json([
            'session_id' => $attendanceSession->id,
            'status' => $attendanceSession->status,
            'total_checked_in' => $attendanceSession
                ->records()
                ->count(),
            'records' => $records,
        ]);
    }

    /**
     * PATCH /attendance/sessions/{attendanceSession}/close
     *
     * Close a session and reject future check-ins.
     */
    public function close(
        Request $request,
        AttendanceSession $attendanceSession
    ): JsonResponse {
        $session = DB::transaction(
            function () use (
                $request,
                $attendanceSession
            ): AttendanceSession {
                $session = AttendanceSession::query()
                    ->whereKey($attendanceSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_if(
                    $session->status !== 'open',
                    409,
                    'Attendance session is already closed.'
                );

                $session->update([
                    'status' => 'closed',
                    'ended_at' => now(),
                    'ended_by' => $request->user()->id,
                ]);

                return $session->refresh();
            }
        );

        return response()->json([
            'message' => 'Attendance session closed.',
            'session' => $session,
        ]);
    }
}
