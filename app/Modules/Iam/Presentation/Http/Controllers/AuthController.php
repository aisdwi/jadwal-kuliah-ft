<?php

namespace App\Modules\Iam\Presentation\Http\Controllers;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Iam\Domain\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->email;
        $user = $this->users->findByLoginIdentifier((string) $identifier);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak ditemukan (Periksa Email, Username atau NIP).'],
            ]);
        }

        $passwordValid = Hash::check($request->password, $user->password)
            || $request->password === $user->password
            || md5($request->password) === $user->password;

        if (!$passwordValid) {
            throw ValidationException::withMessages([
                'email' => ['Password tidak sesuai.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user->load(['role', 'jurusan', 'programStudi.jurusan']);
        $jurusanId = $user->jurusan_id ?: $user->programStudi?->jurusan_id;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'               => $user->id,
                'nama_user'        => $user->nama_user,
                'email'            => $user->email,
                'role_id'          => $user->role_id,
                'role'             => RoleName::normalize($user->role?->role ?? 'Unknown'),
                'jurusan_id'       => $jurusanId,
                'jurusan_name'     => $user->jurusan?->nama_jurusan ?? $user->programStudi?->jurusan?->nama_jurusan ?? null,
                'program_studi_id' => $user->program_studi_id,
                'program_studi_name' => $user->programStudi?->nama_prodi,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
