<?php

namespace App\Http\Controllers\Web;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Repositories\LeaveRepository;
use App\Repositories\LeaveTypeRepository;
use App\Requests\Leave\LeaveTypeRequest;
use Exception;

class LeaveTypeController extends Controller
{
    private $view = 'admin.leaveType.';

    private LeaveTypeRepository $leaveTypeRepo;
    private LeaveRepository $leaveRepo;


    public function __construct(LeaveTypeRepository $leaveTypeRepo,
                                LeaveRepository     $leaveRepo
    )
    {
        $this->leaveTypeRepo = $leaveTypeRepo;
        $this->leaveRepo = $leaveRepo;
    }

    public function index()
    {
        $this->authorize('list_leave_type');
        try {
            $leaveTypes = $this->leaveTypeRepo->getAllLeaveTypes();
            return view($this->view . 'index', compact('leaveTypes'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function create()
    {
        $this->authorize('leave_type_create');
        try {
            return view($this->view . 'create');
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function store(LeaveTypeRequest $request)
    {
        $this->authorize('leave_type_create');
        try {
            $validatedData = $request->validated();
            $validatedData['company_id'] = AppHelper::getAuthUserCompanyId();
            $this->leaveTypeRepo->store($validatedData);
            return redirect()
                ->route('admin.leaves.index')
                ->with('success', __('message.leave_type_added'));
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $this->authorize('leave_type_edit');
        try {
            $leaveDetail = $this->leaveTypeRepo->findLeaveTypeDetailById($id);
            return view($this->view . 'edit', compact('leaveDetail'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function update(LeaveTypeRequest $request, $id)
    {
        $this->authorize('leave_type_edit');
        try {
            $validatedData = $request->validated();
            $validatedData['company_id'] = AppHelper::getAuthUserCompanyId();
            $leaveDetail = $this->leaveTypeRepo->findLeaveTypeDetailById($id);
            if (!$leaveDetail) {
                throw new Exception(__('message.leave_type_not_found'), 404);
            }
            $this->leaveTypeRepo->update($leaveDetail, $validatedData);
            return redirect()
                ->route('admin.leaves.index')
                ->with('success',__('message.leave_type_updated'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage())
                ->withInput();
        }

    }

    public function toggleStatus($id)
    {
        $this->authorize('leave_type_edit');
        try {
            $this->leaveTypeRepo->toggleStatus($id);
            return redirect()->back()->with('success', __('message.status_changed'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function toggleEarlyExit($id)
    {
        $this->authorize('leave_type_edit');
        try {
            $this->leaveTypeRepo->toggleEarlyExitStatus($id);
            return redirect()->back()->with('success', __('message.leave_type_early_exit_status_changed'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function delete($id)
    {
        $this->authorize('leave_type_delete');
        try {
            $leaveType = $this->leaveTypeRepo->findLeaveTypeDetailById($id);
            if (!$leaveType) {
                throw new Exception(__('message.leave_type_not_found'), 404);
            }
            $checkLeaveTypeIfUsed = $this->leaveRepo->findLeaveRequestCountByLeaveTypeId($leaveType->id);
            if ($checkLeaveTypeIfUsed > 0) {
                throw new Exception(__('message.leave_type_cannot_delete_in_use', ['name' => ucfirst($leaveType->name)]), 402);
            }
            $this->leaveTypeRepo->delete($leaveType);
            return redirect()->back()->with('success', __('message.leave_type_deleted'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function getLeaveTypesBasedOnEarlyExitStatus($status)
    {
        try {
            $leaveType = $this->leaveTypeRepo->getAllLeaveTypesBasedOnEarlyExitStatus($status);
            return AppHelper::sendSuccessResponse(__('message.data_found'),$leaveType);
        } catch (Exception $exception) {
            return AppHelper::sendErrorResponse($exception->getMessage(),$exception->getCode());
        }
    }
}
