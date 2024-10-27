<?php

namespace App\Http\Controllers\Web;

use App\Helpers\AppHelper;
use App\Helpers\SMPush\SMPushHelper;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequestMaster;
use App\Repositories\LeaveTypeRepository;
use App\Repositories\UserRepository;
use App\Requests\Leave\LeaveRequestAdd;
use App\Requests\Leave\LeaveRequestStoreFromWeb;
use App\Requests\Leave\LeaveRequestStoreRequest;
use App\Services\Leave\LeaveService;
use App\Services\Notification\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    private $view = 'admin.leaveRequest.';

    private LeaveService $leaveService;
    private LeaveTypeRepository $leaveTypeRepo;
    private NotificationService $notificationService;
    private UserRepository $userRepository;

    public function __construct(LeaveService $leaveService, LeaveTypeRepository $leaveTypeRepo,NotificationService $notificationService, UserRepository $userRepository)
    {
        $this->leaveService = $leaveService;
        $this->leaveTypeRepo = $leaveTypeRepo;
        $this->notificationService = $notificationService;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        $this->authorize('list_leave_request');
        try {
            $filterParameters = [
                'leave_type' => $request->leave_type ?? null,
                'requested_by' => $request->requested_by ?? null,
                'month' => $request->month ?? null,
                'year' => $request->year ?? Carbon::now()->format('Y'),
                'status' => $request->status ?? null
            ];
            if(AppHelper::ifDateInBsEnabled()){
                $nepaliDate = AppHelper::getCurrentNepaliYearMonth();
                $filterParameters['year'] = $request->year ?? $nepaliDate['year'];
            }
            $leaveTypes = $this->leaveTypeRepo->getAllLeaveTypes(['id','name']);
            $months = AppHelper::MONTHS;
            $with = ['leaveType:id,name', 'leaveRequestedBy:id,name'];
            $select = ['leave_requests_master.*'];
            $leaveDetails = $this->leaveService->getAllEmployeeLeaveRequests($filterParameters,$select, $with);
            return view($this->view . 'index',
                compact('leaveDetails', 'filterParameters',  'leaveTypes','months') );
         } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function show($leaveId)
    {
        try {

            $leaveRequest = $this->leaveService->findLeaveRequestReasonById($leaveId);

            $leaveRequest->reasons = strip_tags($leaveRequest->reasons);
            return response()->json([
                'data' => $leaveRequest,
            ]);
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function updateLeaveRequestStatus(Request $request, $leaveRequestId)
    {
        $this->authorize('update_leave_request');

        $validatedData = $request->validate([
            'status' => ['required', 'string', Rule::in(LeaveRequestMaster::STATUS)],
            'admin_remark' => ['nullable', 'required_if:status,rejected', 'string', 'min:10'],
        ]);

        try {
            DB::beginTransaction();

            $leaveRequestDetail = $this->leaveService->updateLeaveRequestStatus($validatedData, $leaveRequestId);

            if ($leaveRequestDetail) {
                $notificationData = [
                    'title' => 'Leave Status Update',
                    'type' => 'leave',
                    'user_id' => [$leaveRequestDetail->requested_by],
                    'description' => 'Your ' . $leaveRequestDetail->no_of_days . ' day leave request requested on ' . date('M d Y h:i A', strtotime($leaveRequestDetail->leave_requested_date)) . ' has been ' . ucfirst($validatedData['status']),
                    'notification_for_id' => $leaveRequestId,
                ];

                $notification = $this->notificationService->store($notificationData);

                if($notification){
                    $this->sendLeaveStatusNotification($notification,$leaveRequestDetail->requested_by);
                }
            }
            DB::commit();
            return redirect()
                ->route('admin.leave-request.index')
                ->with('success', __('message.leave_status_updated'));
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    private function sendLeaveStatusNotification($notificationData,$userId)
    {
        SMPushHelper::sendLeaveStatusNotification($notificationData->title, $notificationData->description,$userId);
    }

    public function createLeaveRequest()
    {
        $this->authorize('request_leave');
        try {
            $leaveTypes = $this->leaveTypeRepo->getAllActiveLeaveTypes(['id','name']);
            $bsEnabled = AppHelper::ifDateInBsEnabled();

            return view($this->view . 'create', compact('leaveTypes','bsEnabled'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function storeLeaveRequest(LeaveRequestStoreFromWeb $request)
    {
        $this->authorize('request_leave');
        try {
            $validatedData = $request->validated();
            $permissionKeyForNotification = 'employee_leave_request';

            $validatedData['requested_by'] = auth()->user()->id;
            DB::beginTransaction();
                $leaveRequest = $this->leaveService->storeLeaveRequest($validatedData);
            DB::commit();
            AppHelper::sendNotificationToAuthorizedUser(
                __('message.notification_title'),
                __('message.leave_notification_message', [
                    'name' => ucfirst(auth()->user()->name),
                    'days' => $leaveRequest['no_of_days'],
                    'from_date' => AppHelper::formatDateForView($leaveRequest['leave_from']),
                    'request_date' => AppHelper::convertLeaveDateFormat($leaveRequest['leave_requested_date']),
                    'reason' => $validatedData['reasons']
                ]),
                $permissionKeyForNotification
            );

            return redirect()
                ->back()
                ->with('success', __('message.leave_submitted'));
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->with('danger', $exception->getMessage())
                ->withInput();
        }
    }

    public function addLeaveRequest()
    {
        $this->authorize('request_leave');
        try {
            $leaveTypes = $this->leaveTypeRepo->getAllActiveLeaveTypes(['id','name']);
            $bsEnabled = AppHelper::ifDateInBsEnabled();

            $employees = $this->userRepository->getAllVerifiedEmployeesExceptAdminOfCompany(['id','name']);

            return view($this->view . 'add', compact('employees','leaveTypes','bsEnabled'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function saveLeaveRequest(LeaveRequestAdd $request)
    {
        $this->authorize('request_leave');
        try {
            $validatedData = $request->validated();
            $permissionKeyForNotification = 'employee_leave_request';

            $validatedData['referred_by'] = auth()->user()->id;

            $employee = $this->userRepository->findUserDetailById($validatedData['requested_by'], ['name']);

            DB::beginTransaction();
                $leaveRequest = $this->leaveService->storeLeaveRequest($validatedData);
            DB::commit();
            AppHelper::sendNotificationToAuthorizedUser(
                __('message.leave_notification_title'),
                __('message.leave_notification_message_on_behalf', [
                    'requester_name' => ucfirst(auth()->user()->name),
                    'employee_name' => $employee->name,
                    'days' => $leaveRequest['no_of_days'],
                    'from_date' => AppHelper::formatDateForView($leaveRequest['leave_from']),
                    'request_date' => AppHelper::convertLeaveDateFormat($leaveRequest['leave_requested_date']),
                    'reason' => $validatedData['reasons']
                ]),
                $permissionKeyForNotification
            );

            return redirect()
                ->route('admin.leave-request.index')
                ->with('success', __('message.leave_submitted'));
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->with('danger', $exception->getMessage())
                ->withInput();
        }
    }

}
