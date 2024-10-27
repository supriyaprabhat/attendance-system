<?php

namespace App\Services\AwardManagement;

use App\Repositories\AwardTypeRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class AwardTypeService
{
    public function __construct(
        protected AwardTypeRepository $awardTypeRepository
    ){}

    public function getAllAwardTypes($select= ['*'], $with=[])
    {
        return $this->awardTypeRepository->getAllAwardTypes($select,$with);
    }

    public function getAllActiveAwardTypes($select= ['*'])
    {
        return $this->awardTypeRepository->getAllActiveAwardTypes($select);
    }

    /**
     * @throws Exception
     */
    public function findAwardTypeById($id, $select=['*'], $with=[])
    {

        return $this->awardTypeRepository->findAwardTypeById($id,$select,$with);

    }

    /**
     * @throws Exception
     */
    public function store($validatedData)
    {

        DB::beginTransaction();
        $assetTypeDetail = $this->awardTypeRepository->create($validatedData);
        DB::commit();
        return $assetTypeDetail;

    }

    /**
     * @throws Exception
     */
    public function updateAwardType($id, $validatedData)
    {

        $assetTypeDetail = $this->findAwardTypeById($id);
        DB::beginTransaction();
        $updateStatus = $this->awardTypeRepository->update($assetTypeDetail, $validatedData);
        DB::commit();
        return $updateStatus;

    }

    /**
     * @throws Exception
     */
    public function deleteAwardType($id): bool
    {

        $assetTypeDetail = $this->findAwardTypeById($id);
        DB::beginTransaction();
        $this->awardTypeRepository->delete($assetTypeDetail);
        DB::commit();
        return true;

    }

    /**
     * @throws Exception
     */
    public function toggleStatus($id): bool
    {

        DB::beginTransaction();
        $clientDetail = $this->findAwardTypeById($id);
        $this->awardTypeRepository->toggleStatus($clientDetail);
        DB::commit();
        return true;

    }

}
