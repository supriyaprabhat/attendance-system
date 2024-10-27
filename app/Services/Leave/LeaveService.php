<?php

namespace App\Services\Leave;

use App\Helpers\AppHelper;
use App\Helpers\AttendanceHelper;
use App\Models\OfficeTime;
use App\Repositories\LeaveRepository;
use App\Repositories\LeaveTypeRepository;
use Carbon\Carbon;
use DateTime;
use Exception;
//use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;

class LeaveService
{
    private LeaveRepository $leaveRepo;
    private LeaveTypeRepository $leaveTypeRepo;

    public function __construct(LeaveRepository $leaveRepo, LeaveTypeRepository $leaveTypeRepo)
    {
        $this->leaveRepo = $leaveRepo;
        $this->leaveTypeRepo = $leaveTypeRepo;
    }

    /**
     * @param $filterParameters
     * @param $select
     * @param $with
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public function getAllEmployeeLeaveRequests($filterParameters, $select=['*'], $with=[])
    {

            if(AppHelper::ifDateInBsEnabled()){
                $dateInAD = AppHelper::findAdDatesFromNepaliMonthAndYear($filterParameters['year'],$filterParameters['month']);
                $filterParameters['start_date'] = $dateInAD['start_date'];
                $filterParameters['end_date'] = $dateInAD['end_date'];
            }
            return $this->leaveRepo->getAllEmployeeLeaveRequest($filterParameters,$select,$with);

    }

    /**
     * @param $filterParameters
     * @param $select
     * @param $with
     * @return array|Builder|Collection|HigherOrderWhenProxy
     * @throws Exception
     *
     */
    public function getAllLeaveRequestOfEmployee($filterParameters)
    {

        if(AppHelper::ifDateInBsEnabled()){
            $nepaliDate = AppHelper::getCurrentNepaliYearMonth();
            $month = isset($filterParameters['month']) ? $nepaliDate['month']: '';
            $dateInAD = AppHelper::findAdDatesFromNepaliMonthAndYear($nepaliDate['year'],$month);
            $filterParameters['start_date'] = $dateInAD['start_date'];
            $filterParameters['end_date'] = $dateInAD['end_date'];
        }
        return $this->leaveRepo->getAllLeaveRequestDetailOfEmployee($filterParameters);

    }

    /**
     * @param $leaveRequestId
     * @param $select
     * @param $with
     * @return Builder|Model|object|null
     * @throws Exception
     */
    public function findEmployeeLeaveRequestById($leaveRequestId, $select=['*'], $with=[])
    {

        return $this->leaveRepo->findEmployeeLeaveRequestByEmployeeId($leaveRequestId,$select,$with);

    }

    public function findLeaveRequestReasonById($leaveRequestId)
    {

        return $this->leaveRepo->findEmployeeLeaveRequestReasonById($leaveRequestId);

    }

    /**
     * @param $validatedData
     * @return mixed
     * @throws Exception
     */
    public function storeLeaveRequest($validatedData)
    {

            $leaveDate = $this->checkIfDateIsValidToRequestLeave($validatedData);
            $validatedData['no_of_days'] = ($leaveDate['to']->diffInDays($leaveDate['from']) + 1);
            $validatedData['company_id'] = AppHelper::getAuthUserCompanyId();
            $validatedData['leave_requested_date'] = Carbon::now()->format('Y-m-d h:i:s');

            $this->checkEmployeeLeaveRequest($validatedData);

            DB::beginTransaction();
                $this->leaveRepo->store($validatedData);
            DB::commit();
            return $validatedData;

    }

    /**
     * @param $validatedData
     * @return array
     * @throws Exception
     */
    private function checkIfDateIsValidToRequestLeave($validatedData)
    {

            if(AppHelper::ifDateInBsEnabled()){
                $leave_start = AppHelper::dateInYmdFormatEngToNep($validatedData['leave_from']);

                $leave_end = AppHelper::dateInYmdFormatEngToNep($validatedData['leave_to']);
                $from = AppHelper::getDayMonthYearFromDate($leave_start);
                $to = AppHelper::getDayMonthYearFromDate($leave_end);

                $leave_from = \Carbon\Carbon::createFromFormat('Y-m-d', $validatedData['leave_from']);
                $leave_to = \Carbon\Carbon::createFromFormat('Y-m-d', $validatedData['leave_to']);

                if($from['year'] != $to['year']){
                    throw new Exception(__('message.different_leave_bs_year'),403);
                }


            }else{
                $leave_from = \Carbon\Carbon::createFromFormat('Y-m-d', $validatedData['leave_from']);
                $leave_to = \Carbon\Carbon::createFromFormat('Y-m-d', $validatedData['leave_to']);
                if($leave_from->year != $leave_to->year){
                    throw new Exception(__('message.different_leave_ad_year'),403);
                }
            }

            $checkHolidayAndWeekend = AttendanceHelper::isHolidayOrWeekend($validatedData['leave_from'], $validatedData['leave_to']);
            if ($checkHolidayAndWeekend) {
                throw new Exception(__('message.offday_leave'), 403);
            }

            return [
               'from' => $leave_from,
               'to' => $leave_to
            ];

    }


    /**
     * @param $validatedData
     * @return void
     * @throws Exception
     */
    private function checkEmployeeLeaveRequest($validatedData): void
    {

            $select= ['id','status'];
            $data['from_date'] = $validatedData['leave_from'];
            $data['to_date'] = $validatedData['leave_to'];
            $data['requested_by'] = $validatedData['requested_by'] ?? getAuthUserCode();

            $employeeLatestPendingLeaveRequest = $this->leaveRepo->getEmployeeLatestLeaveRequestBetweenFromAndToDate($validatedData,$select);

            if($employeeLatestPendingLeaveRequest){
                throw new Exception(__('message.leave_status_error',['status'=>$employeeLatestPendingLeaveRequest->status]),400);
            }
            $leaveType =  $this->leaveTypeRepo->findLeaveTypeDetail($validatedData['leave_type_id'],  $data['requested_by']);


            $totalLeaveAllocated = $leaveType->leave_allocated;
            /**
             * unpaid leave are not allocated with any leave days .
             */
            if(is_null($leaveType->is_paid)){
                return;
            }

            $startYear = date('Y', strtotime($data['from_date']));

                $totalLeaveTakenTillNow = $this->leaveRepo->employeeTotalApprovedLeavesForGivenLeaveType($validatedData['leave_type_id'], $startYear);
                if( (int)$validatedData['no_of_days'] + $totalLeaveTakenTillNow > $totalLeaveAllocated  ){
                    throw new Exception(__('message.leave_exceed_error',['day'=>((int)$validatedData['no_of_days'] + $totalLeaveTakenTillNow - $totalLeaveAllocated),'name'=>$leaveType->name]),400);
                }

    }


    /**
     * @param $validatedData
     * @param $leaveRequestId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     * @throws Exception
     */
    public function updateLeaveRequestStatus($validatedData, $leaveRequestId)
    {
            $leaveRequestDetail = $this->findEmployeeLeaveRequestById($leaveRequestId);
            if(!$leaveRequestDetail){
                throw new \Exception(__('message.leave_request_not_found'),404);
            }
            DB::beginTransaction();
                $this->leaveRepo->update($leaveRequestDetail,$validatedData);
            DB::commit();
            return $leaveRequestDetail;

    }

    /**
     * @return array|void
     * @throws Exception
     */
    public function getLeaveCountDetailOfEmployeeOfTwoMonth()
    {
            $allLeaveRequest = $this->leaveRepo->getLeaveCountDetailOfEmployeeOfTwoMonth();
            if($allLeaveRequest){
                $leaveDates = [];
                foreach($allLeaveRequest as $key => $value){
                    $leaveRequestedDays = $value->no_of_days;
                    $i=0;
                    $fromDate = Carbon::parse( $value->leave_from)->format('Y-m-d');
                    for($i; $i<$leaveRequestedDays; $i++){
                        $leaveDates[] = date('Y-m-d', strtotime("+$i day", strtotime($fromDate)));
                    }
                }
                $leaveDetail = array_count_values($leaveDates);
                $dateWithNumberOfEmployeeOnLeave = [];
                foreach($leaveDetail as $key => $value){
                    $data = [];
                    $data['date']= $key;
                    $data['leave_count']= $value;
                    $dateWithNumberOfEmployeeOnLeave[] = $data;
                }
                return $dateWithNumberOfEmployeeOnLeave;
            }

    }

    /**
     * @param $filterParameter
     * @return mixed
     * @throws Exception

     */
    public function getAllEmployeeLeaveDetailBySpecificDay($filterParameter)
    {

        return $this->leaveRepo->getAllEmployeeLeaveDetailBySpecificDay($filterParameter);

    }

    /**
     * @param $leaveRequestId
     * @param $employeeId
     * @param $select
     * @return Builder|Model|object
     * @throws Exception
     */
    public function findLeaveRequestDetailByIdAndEmployeeId($leaveRequestId, $employeeId, $select=['*'])
    {

        $leaveRequestDetail = $this->leaveRepo->findEmployeeLeaveRequestDetailById($leaveRequestId,$employeeId,$select);
        if(!$leaveRequestDetail){
            throw new \Exception(__('message.leave_request_not_found'),404);
        }
        return $leaveRequestDetail;

    }

    /**
     * @param $validatedData
     * @param $leaveRequestDetail
     * @throws Exception
     * @return mixed
     */
    public function cancelLeaveRequest($validatedData, $leaveRequestDetail)
    {

            DB::beginTransaction();
                $this->leaveRepo->update($leaveRequestDetail,$validatedData);
            DB::commit();
            return $leaveRequestDetail;

    }

    /**
     * @param $validatedData
     * @return mixed
     * @throws Exception
     */
    public function storeTimeLeaveRequest($validatedData)
    {

        $shift = OfficeTime::where('id',auth()->user()->office_time_id)->first();
        $validatedData['issue_date'] = AppHelper::getEnglishDate($validatedData['issue_date']);

        if(strtotime(date('Y-m-d')) == strtotime($validatedData['issue_date'])){
            $startTime = $validatedData['leave_from'] ?? $shift['opening_time'];
            $endTime = $validatedData['leave_to'] ?? $shift['closing_time'];
        }else{
            $startTime = $validatedData['leave_from'];
            $endTime = $validatedData['leave_to'];
        }
        $validatedData['start_time'] = $startTime;
        $validatedData['end_time'] =  $endTime;

        $this->checkExistingLeaveRequest($validatedData);

        DB::beginTransaction();
        $this->leaveRepo->store($validatedData);
        DB::commit();
        return $validatedData;

    }

    /**
     * @param $validatedData
     * @return void
     * @throws Exception
     */
    private function checkExistingLeaveRequest($validatedData): void
    {


            $date = date('Y-m-d', strtotime($validatedData['issue_date']));

            $employeeLatestPendingLeaveRequest = $this->leaveRepo->getEmployeeLatestLeaveRequestDate($date);
            if($employeeLatestPendingLeaveRequest){
                throw new Exception(__('message.leave_pending_error',['status'=>$employeeLatestPendingLeaveRequest->status]),400);
            }


    }

}
