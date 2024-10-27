<?php

namespace App\Helpers\SMPush;

use App\Helpers\AppHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use phpseclib3\Math\PrimeField\Integer;

class SMPushHelper
{
    const IS_ACTIVE = 1;

    public static function getAllCompanyUsersFCMTokens(): array
    {
        return User::where([
                ['is_active', self::IS_ACTIVE],
                ['status', 'verified']
            ])
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id')
            ->toArray();
    }

    public static function getEmployeeFCMTokensForSending(array $userIds)
    {
        return User::where([
                ['is_active', self::IS_ACTIVE],
                ['status', 'verified']
            ])
            ->whereIn('id',$userIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id')
            ->toArray();
    }

    public static function getUserFCMToken($userId): array
    {
        return User::where([
                ['id', $userId],
                ['is_active', self::IS_ACTIVE],
                ['status', 'verified']
            ])
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token','id')
            ->toArray();
    }

    public static function getFCMTokensWithGivenRoleIds($roleIds):array
    {
        return User::where([
            ['is_active', self::IS_ACTIVE],
            ['status', 'verified']
        ])
        ->whereHas('role', fn($query) => $query->whereIn('id', $roleIds))
        ->whereNotNull('fcm_token')
        ->pluck('fcm_token', 'id')
        ->toArray();
    }
    public static function getFCMToken($roleIds)
    {
        return User::where([
            ['is_active', self::IS_ACTIVE],
            ['status', 'verified']
        ])
            ->whereHas('role', fn($query) => $query->whereIn('id', $roleIds))
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token','id')->toArray();
    }


    public static function sendPush(string $title, string $description, array $data=[]): void
    {
        SMPushNotification::smSend(
            title: $title,
            message: Str::limit($description, 100, '...'),
            data: [
                'title'=>$title,
                'message'=> Str::limit($description, 3700, '...'),
            ],
            recipients: self::getAllCompanyUsersFCMTokens()
        );

    }

    /**
     * @throws MessagingException
     * @throws FirebaseException
     */
    public static function sendLeaveStatusNotification(string $title, string $description, $userId)
    {
         SMPushNotification::smSend(
            title: $title,
            message: $description,
            data: [
                'title'=>$title,
                'message'=> $description
            ],
            recipients: self::getUserFCMToken($userId)
        );
    }

    public static function sendNoticeNotification(string $title,
                                                  string $description,
                                                  array $userIds,
                                                  bool $teamMeeting = false,
                                                  $id=''
    ): void
    {
        SMPushNotification::smSend(
            title: $title,
            message: Str::limit($description, 100, '...'),
            data: [
                'title'=>$title,
                'message'=> Str::limit($description, 3700, '...'),
                'id'=> $id
            ],
            recipients: self::getEmployeeFCMTokensForSending($userIds)
        );

    }

    public static function sendSupportNotification(string $title,
                                                  string $description,
                                                  array $userIds,
                                                         $id=''
    ): void
    {
        SMPushNotification::smSend(
            title: $title,
            message: $description,
            data: [
                'title'=> $title,
                'message'=> $description,
                'id'=> $id
            ],
            recipients: self::getEmployeeFCMTokensForSending($userIds)
        );

    }

    public static function sendProjectManagementNotification(string $title,
                                                   string $description,
                                                   array $userIds,
                                                          $id=''
    ): void
    {
        SMPushNotification::smSend(
            title: $title,
            message: $description,
            data: [
                'title'=>$title,
                'message'=> $description,
                'id'=> $id ?? 0
            ],
            recipients: self::getEmployeeFCMTokensForSending($userIds)
        );

    }

    public static function sendNotificationToAuthorizedUsers(string $title,
                                                             string $description,
                                                             array $roleIds)
    {
        $data = [
            'title' => $title,
            'message' => $description,
        ];

        self::sendNotificationToRecipients($title, $description, $data, $roleIds);
    }

    /**
     * @throws MessagingException
     * @throws FirebaseException
     */
    private static function sendNotificationToRecipients(string $title,
                                                         string $message,
                                                         array  $data,
                                                         array  $roleIds): void
    {
        $recipients = self::getFCMToken($roleIds);
        SMPushNotification::smSend(
            title: $title,
            message: $message,
            data: $data,
            recipients: $recipients
        );
    }

    public static function sendPushNotification(string $title,
                                                string $conversation_id,
                                                string $message,
                                                string $type,
                                                array $usernames,
                                                string $project_id,
    ): void
    {

        SMPushNotification::smSend(
            title: $title,
            message: Str::limit($message, 100, '...'),
            data: [
                'title' => $title,
                'conversation_id' => $conversation_id,
                'sender_name' => auth()->user()->name,
                'sender_image' => auth()->user()->image ? asset(User::AVATAR_UPLOAD_PATH . auth()->user()->image) : asset('assets/images/img.png'),
                'message' => $message,
                'type' => $type,
                'project_id' => $project_id,
                'sender_username' => auth()->user()->username,
            ],
            recipients: $type == 'group_chat' ? self::getEmployeeFCMTokensForSending($usernames) : self::getEmployeeByUsernameFCMTokensForSending($usernames)
        );

    }

    public static function getEmployeeByUsernameFCMTokensForSending(array $usernames)
    {

        return User::where([
            ['is_active', self::IS_ACTIVE],
            ['status', 'verified']
        ])
            ->whereIn('username',$usernames)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id')
            ->toArray();
    }

    public static function sendAdvanceSalaryNotification(string $title, string $description,$userId)
    {
        SMPushNotification::smSend(
            title: $title,
            message: $description,
            data: [
                'title'=>$title,
                'message'=> $description
            ],
            recipients: self::getUserFCMToken($userId)
        );


    }


    public static function sendNotificationToDepartmentHead(string $title,
                                                             string $description,
                                                             int $userId)
    {
        $data = [
            'title' => $title,
            'message' => $description
        ];
        self::sendNotificationToUser($title, $description, $data, $userId);
        self::sendNotificationToUser($title, $description, $data, $userId);
    }

    private static function sendNotificationToUser(string $title,
                                                         string $message,
                                                         array $data,
                                                         int $userId,
                                                         )
    {
        $recipients = self::getFCMTokensWithGivenUserId($userId);
        SMPushNotification::smSend(
            title: $title,
            message: $message,
            data: $data,
            recipients: $recipients
        );
    }

    public static function getFCMTokensWithGivenUserId($userId):array
    {
        return User::where([
            ['is_active', self::IS_ACTIVE],
            ['status', 'verified']
        ])
            ->where('id',$userId)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id')
            ->toArray();
    }

}
