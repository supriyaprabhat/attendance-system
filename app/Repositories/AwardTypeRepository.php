<?php

namespace App\Repositories;

use App\Models\AwardType;

class AwardTypeRepository
{

    public function getAllAwardTypes($select=['*'],$with=[])
    {
        return AwardType::select($select)->withCount($with)->get();
    }

    public function getAllActiveAwardTypes($select=['*'])
    {
        return AwardType::select($select)->where('status',1)->get();
    }

    public function findAwardTypeById($id,$select=['*'],$with=[])
    {
        return AwardType::with($with)->select($select)->where('id',$id)->first();
    }

    public function create($validatedData)
    {
        return AwardType::create($validatedData)->fresh();
    }

    public function update($assetTypeDetail,$validatedData)
    {
        return $assetTypeDetail->update($validatedData);
    }

    public function delete($assetTypeDetail)
    {
        return $assetTypeDetail->delete();
    }

    public function toggleStatus($assetTypeDetail)
    {
        return $assetTypeDetail->update([
            'status' => !$assetTypeDetail->status,
        ]);
    }
}
