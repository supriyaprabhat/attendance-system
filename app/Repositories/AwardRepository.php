<?php

namespace App\Repositories;


use App\Helpers\AppHelper;
use App\Models\Award;

use App\Traits\ImageService;

class AwardRepository
{
    use ImageService;

    public function getAllAwardsPaginated($select=['*'],$with=[])
    {
        return Award::select($select)->with($with)
//            ->when(isset($filterParameters['type']), function($query) use ($filterParameters){
//                $query->whereHas('type',function($query) use ($filterParameters){
//                    $query->where('id', $filterParameters['type']);
//                });
//            })
//            ->when(isset($filterParameters['name']), function ($query) use ($filterParameters) {
//                $query->where('name', 'like', '%' . $filterParameters['name'] . '%');
//            })
//            ->when(isset($filterParameters['is_working']), function ($query) use ($filterParameters) {
//                $query->where('is_working', 'like', '%' . $filterParameters['is_working'] . '%');
//            })
//            ->when(isset($filterParameters['is_available']), function ($query) use ($filterParameters) {
//                $query->where('is_available', $filterParameters['is_available']);
//            })
//            ->when(isset($filterParameters['purchased_from']), function($query) use ($filterParameters){
//                $query->whereDate('purchased_date','>=',date('Y-m-d',strtotime($filterParameters['purchased_from'])));
//            })
//            ->when(isset($filterParameters['purchased_to']), function($query) use ($filterParameters){
//                $query->whereDate('purchased_date','<=',date('Y-m-d',strtotime($filterParameters['purchased_to'])));
//            })
            ->latest()
            ->paginate(Award::RECORDS_PER_PAGE);
    }

    public function getEmployeeAwardsPaginated($employeeId, $perPage,$select=['*'],$with=[], $userProfile=0)
    {
        $awardList =  Award::select($select)->with($with)
            ->where('employee_id',$employeeId);
            if($userProfile == 1){
                $awardList = $awardList->latest()->take(5)->get();
            }else{
                $awardList = $awardList->latest()
                    ->paginate($perPage);
            }

            return $awardList;

    }

    public function findAwardById($id,$select=['*'],$with=[])
    {
        return Award::select($select)
            ->with($with)
            ->where('id',$id)
            ->first();
    }

     public function getRecentAward($select,$with, $employeeId = 0)
    {
        $recentAward =  Award::select($select)
            ->with($with);
            if($employeeId != 0){
                $recentAward = $recentAward->where('employee_id',$employeeId);
            }

            return $recentAward->orderBy('awarded_date', 'desc')
            ->first();
    }

    public function store($validatedData)
    {
        if(isset($validatedData['attachment'])){
            $validatedData['attachment'] = $this->storeImage($validatedData['attachment'], Award::UPLOAD_PATH,500,500);
        }

        if(!isset($validatedData['awarded_by'])){
            $validatedData['awarded_by'] = AppHelper::getAuthUserCompanyName();
        }

        return Award::create($validatedData)->fresh();
    }

    public function update($assetDetail,$validatedData)
    {
        if (isset($validatedData['attachment'])) {
            if($assetDetail['attachment']){
                $this->removeImage(Award::UPLOAD_PATH, $assetDetail['attachment']);
            }
            $validatedData['attachment'] = $this->storeImage($validatedData['attachment'], Award::UPLOAD_PATH,500,500);
        }
        return $assetDetail->update($validatedData);
    }

    public function delete($assetDetail)
    {
        if($assetDetail['attachment']){
            $this->removeImage(Award::UPLOAD_PATH, $assetDetail['attachment']);
        }
        return $assetDetail->delete();
    }


}
