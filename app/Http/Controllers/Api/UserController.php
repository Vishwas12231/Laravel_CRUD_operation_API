<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($flag)
    {
        $query = User::select('email', 'name');
        if($flag == 1){
            $query->where('status', 1);
        } else if($flag == 0) {
            $query->where('status', 0);
        } else {
            return response()->json([
                'message' => 'invalid parameters passed, it can be either 1 or 0',
                'status' => 0
            ], 400);
        }
        $users = $query->get();
        if(count($users) > 0){
            //user exists
            $response = [
                'message' => count($users)  .  'users found',
                'status' => 1,
                'data' => $users
            ];

            return response()->json($response, 200);
        } else {
            //doesn't exists
            $response = [
                'message' => count($users)  .  'users found',
                'status' => 0,
            ];
        }
        return response()->json($response, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => ['required'], 
            'email' => ['required', 'email', 'unique:users,email']  ,
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']     
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }else{
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ];
            DB::beginTransaction();
            try{
                $user = User::create($data);
                DB::commit();
            } 
            catch (\Exception $e) {
                $user =  null;
            }
            if($user != null)
            {
                return response()->json([
                    'message' => 'User registerd successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Internal server error'
                ], 500);
            }
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                    'message' => 'user not found',
                    'status' => 0
            ];
        }
        else{
            $response = [
                'message' => 'User found',
                'status' => 1,
                'data' => $user
            ];
        }
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            //user does not exist
            return response()->json(
                [
                    'status' => 0,
                    'message' => 'User does not exists'
                ],
                404
            );
        } else{
            DB::beginTransaction();
            try{
                $user->name = $request['name'];
                $user->email = $request['email'];
                $user->contact = $request['contact'];
                $user->pincode = $request['pincode'];
                $user->address = $request['address'];
                $user->save();
                DB::commit();
            }
            catch(\Exception $err){
                DB::rollBack();
                $user =  null;
            }
            if(is_null($user))
            {
                return response()->json(
                    [
                        'status' => 0,
                        'message' => 'Internal server error',
                        'error_msg' => $err->getMessage()
                    ],
                    500
                );
            }else{
                return response()->json(
                    [
                        'status' => 0,
                        'message' => 'Data updated successfully'
                    ],
                    200
                );
            }
        }  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $users = User::find($id);
        if(is_null($users)) {
            $response = [
                'message' => "User doesn't exists",
                'status' => 0
            ];
            $respCode = 404;
        }else{
            DB::beginTransaction();
            try{
                $users->delete();
                DB::commit();
                $response = [
                    'message' => 'User deleted successfully',
                    'status' => 1
                ];
                $respCode = 200;
            }
            catch(\Exception $err){
                DB::rollBack();
                $response = [
                    'message' => 'Internal server error',
                    'status' => 0
                ];
                $respCode = 500;
            }
        }
        return response()->json($response, $respCode);
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
        if(is_null($user))
        {
            return response()->json(
                [
                    'status' => 0,
                    'message' => 'User does not exists'
                ],
                404
            );
        }else{
            if($user->password == $request['old_password'])
            {
                if($request['new_password'] == $request['confirm_password']){
                    DB::beginTransaction();
                    try{
                        $user->password = $request['new_password'];
                        $user->save();
                        DB::commit();
                    }catch(\Exception $err){
                        $user = null;
                        DB::rollBack();
                    }
                    if(is_null($user))
                    {
                        return response()->json(
                            [
                                'status' => 0,
                                'message' => 'Internal server error',
                                'error_msg' => $err->getMessage()
                            ],
                            500
                        );
                    }else{
                        return response()->json(
                            [
                                'status' => 0,
                                'message' => 'Password updated successfully'
                            ],
                            200
                        );
                    }
                }else{
                    return response()->json(
                    [
                        'status' => 0,
                        'message' => 'New password and confirm password does not match'
                    ],
                    400
                );
                }
            }else{
                return response()->json(
                    [
                        'status' => 0,
                        'message' => 'old password does not match'
                    ],
                    400
                ); 
            }
        }
    }
}
