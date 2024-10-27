<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\AppSettingRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private UserRepository $userRepo;
    private AppSettingRepository $appSettingRepo;


    public function __construct(UserRepository $userRepo,AppSettingRepository $appSettingRepo)
    {
        $this->userRepo = $userRepo;
        $this->appSettingRepo = $appSettingRepo;
    }

    public function checkCredential($validatedData): array
    {
        try {
            $user = '';
            $select = ['*'];

            $userWithUserEmail = $this->userRepo->getUserByUserEmail($validatedData['username'], $select);
            if ($userWithUserEmail) {
                $user = $userWithUserEmail;
                $credential['login_type'] = 'email';
                $credential['email'] = $validatedData['username'];
            }

            $userWithUserName = $this->userRepo->getUserByUserName($validatedData['username'], $select);
            if ($userWithUserName) {
                $user = $userWithUserName;
                $credential['login_type'] = 'username';
                $credential['username'] = $validatedData['username'];
            }

            if (!$user) {
                throw new Exception(__('message.invalid_credential'), 401);
            }

            if (!Hash::check($validatedData['password'], $user->password)) {
                throw new Exception(__('message.no_record_credentials'), 401);
            }

            return array(
                'credential'=> $credential,
                'user'=> $user
            );
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function updateUserLoginDetail($validatedData)
    {
        try{
            $update['uuid'] = $validatedData['uuid'];
            $update['fcm_token'] = $validatedData['fcm_token'];
            $update['device_type'] = $validatedData['device_type'];
            $update['logout_status'] = User::LOGOUT_STATUS['approve'];
            $userDetail = $this->userRepo->findUserDetailById($validatedData['id']);
            if(!$userDetail){
                throw new Exception(__('message.user_not_found'),404);
            }
            $slug = 'authorize-login';
            $authorizeLogin = $this->appSettingRepo->findAppSettingDetailBySlug($slug);
            if($authorizeLogin && $authorizeLogin->status == 1){

                if($userDetail->logout_status == User::LOGOUT_STATUS['pending']){
                    throw new Exception(__('message.log_out_request'),401);
                }
                if($userDetail->uuid){
                    throw new Exception(__('message.log_out_error'),401);
                }
            }


            DB::beginTransaction();
                $this->userRepo->update($userDetail,$update);
            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

}
