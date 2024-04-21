<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\Models\User;
use App\Helpers\APIFormatter;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer',]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()
                ->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->token = $token;
        $user->token_type = 'Bearer';

        return response()
            ->json([
                    'success' => true,
                    'message' => 'Hi ' . $user->name . ', selamat datang di sistem presensi',
                    'data' => $user
                ]);
    }

    public function signIn(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user && !Hash::check($request->password, $user->password)) {
                return APIFormatter::createAPI(422, 'Email atau kata sandi salah.');
            } else {
                $token = $user->createToken($request->email, ['user'])->plainTextToken;

                if ($token) {
                    return APIFormatter::createAPI(200, "Success", $token);
                } else {
                    return APIFormatter::createAPI(400, 'Failed');
                }
            }
        } catch (Exception $e) {
            return APIFormatter::createAPI(500, 'Failed', $e);
        }
    }

    // method for user logout and delete token
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }

    public function signOut($tokenId)
    {
        try {
            $data = DB::delete("delete from personal_access_tokens where id = '$tokenId'");

            if($data) {
                return APIFormatter::createAPI(200, 'Berhasil logout', true);
            } else {
                return APIFormatter::createAPI(400, 'Failed', false);
            }
        } catch (Exception $e) {
            return APIFormatter::createAPI(500, 'Failed', $e);
        }
    }
}
