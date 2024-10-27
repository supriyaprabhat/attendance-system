<?php

namespace App\Services\TaxReport;

use App\Enum\BonusTypeEnum;
use App\Helpers\AppHelper;
use App\Helpers\PayrollHelper;
use App\Repositories\EmployeePayslipDetailRepository;
use App\Repositories\EmployeePayslipRepository;
use App\Repositories\EmployeeSalaryRepository;
use App\Repositories\SalaryGroupRepository;
use App\Repositories\TaxReportRepository;
use App\Repositories\UserRepository;
use App\Services\FiscalYear\FiscalYearService;
use App\Services\Payroll\BonusService;
use App\Services\Payroll\SalaryComponentService;
use App\Services\Payroll\SSFService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

class TaxReportService
{
    public function __construct(protected UserRepository $userRepo, protected SalaryGroupRepository $groupRepository,
                                 protected EmployeePayslipRepository $payslipRepository,
                                protected EmployeePayslipDetailRepository $payslipDetailRepository, protected EmployeeSalaryRepository $employeeSalaryRepository,
                                protected SSFService $ssfService, protected BonusService $bonusService, protected SalaryComponentService $salaryComponentService,
                                protected FiscalYearService $fiscalYearService, protected TaxReportRepository $reportRepository,
                                protected TaxReportComponentDetailService $componentDetailService, protected TaxReportAdditionalDetailService $additionalDetailService,
                                protected TaxReportBonusDetailService $bonusDetailService, protected TaxReportTdsDetailService $tdsDetailService){}


    /**
     * @throws Exception
     */
    public function getAllTaxReport()
    {
        return $this->reportRepository->getAll();
    }

    /**
     * @throws Exception
     */
    public function findTaxReportById($id, $select=['*'], $with=[])
    {
        return $this->reportRepository->find($id,$select,$with);
    }
    /**
     * @throws Exception
     */
    public function findTaxReportByEmployee($employeeId, $fiscalYearId)
    {
        return $this->reportRepository->findByEmployee($employeeId, $fiscalYearId);
    }



    public function calculateSalaryComponent($salaryComponents, $annualSalary, $basicSalary): array
    {
        $payslipComponents = [];
        if (count($salaryComponents) > 0) {
            foreach ($salaryComponents as $component) {
                $amount = $this->calculateComponent($component->value_type, $component->annual_component_value, $annualSalary, $basicSalary);

                $monthly = $amount / 12;
                $weekly = $amount / 52;

                $payslipComponents[] = [
                    "id" => $component->id,
                    "name" => $component->name,
                    "type" => $component->component_type,
                    "annual" => $amount,
                    "monthly" => $monthly,
                    "weekly" => round($weekly, 2),
                ];

            }
        }
        return $payslipComponents;

    }

    public function calculateComponent($valueType, $annualValue, $annualSalary, $basicSalary): float
    {

        $componentValue = 0;
        if ($valueType == 'fixed') {
            $componentValue = $annualValue;
        } else if ($valueType == 'ctc') {
            $componentValue = ($annualValue / 100) * $annualSalary;
        } else if ($valueType == 'basic') {
            $componentValue = ($annualValue / 100) * $basicSalary;
        }

        return round($componentValue, 2);
    }



    /**
     * @throws Exception
     */
    public function storeTaxReport($employeeId, $fiscalYearId)
    {
        $totalBasicSalary = 0;
        $totalAllowance = 0;
        $totalSSFContribution = 0;
        $totalSSFDeduction = 0;
        $totalPayableTDS = 0;
        $monthData = [];

        $fiscalYearData = $this->fiscalYearService->findFiscalYearById($fiscalYearId);

        $firstDay = $fiscalYearData->start_date;
        $lastDay = $fiscalYearData->end_date;

        $employeePayrollData = $this->userRepo->getEmployeeAccountDetailsToGeneratePayslip($employeeId);

        if(!isset($employeePayrollData[0]->joining_date)){
            throw new Exception('Employee joining date not found', 404);
        }

        $firstDayTimestamp = strtotime($firstDay);
        $joiningDateTimestamp = strtotime($employeePayrollData[0]->joining_date);

        // Create an array of all months
        $allMonths = array_merge(range(4, 12), range(1, 3));

        $firstDayTimestamp = strtotime($firstDay);
        $joiningDateTimestamp = strtotime($employeePayrollData[0]->joining_date);
        $joiningMonth = AppHelper::getMonthValue($employeePayrollData[0]->joining_date);
        $workedMonths = 0;
        foreach ($allMonths as $month) {
            if (($firstDayTimestamp > $joiningDateTimestamp) || ($month >= $joiningMonth || $month < 4)) {
                ++$workedMonths;
            }
        }

        // Determine the joining month
        $joiningMonth = AppHelper::getMonthValue($employeePayrollData[0]->joining_date);


        $additionalSalaryComponents = [];
        $componentData = [];
        $taxData = [];
        $bonusData = [];
        if (isset($employeePayrollData[0])) {
            foreach ($allMonths as $month) {
                if (($firstDayTimestamp > $joiningDateTimestamp) || $month >= $joiningMonth || $month < 4) {

                    $monthData[] = $month;
                    /** payroll Calculation of employee with  monthly salary_cycle  */

                    $grossSalary = $employeePayrollData[0]->annual_salary / 12;
                    $monthSalary = $employeePayrollData[0]->monthly_basic_salary + $employeePayrollData[0]->monthly_fixed_allowance;
                    $annualSalary = $monthSalary * 12;
                    $totalIncome = 0;
                    $total_deduction = 0;

                    /** get ssf data */

                    $ssfDetail = $this->ssfService->findSSFDetail();

                    /** office contribution */
                    $ssfContribution = isset($ssfDetail) ? ($ssfDetail->office_contribution * $employeePayrollData[0]->monthly_basic_salary) / 100 : 0;
                    $monthSalary += $ssfContribution;
                    /** employee Deduction */
                    $ssfDeduction = isset($ssfDetail) ? ($ssfDetail->employee_contribution * $employeePayrollData[0]->monthly_basic_salary) / 100 : 0;

                    $monthSalary -= $ssfDeduction;

                    /** salary components calculation */
                    $employeeSalaryComponents = [];
                    if (isset($employeePayrollData[0]->salary_group_id)) {
                        $components = $this->groupRepository->findSalaryGroupDetailById($employeePayrollData[0]->salary_group_id, ['*'], with(['salaryComponents']));

                        $employeeSalaryComponents = $this->calculateSalaryComponent($components->salaryComponents, $employeePayrollData[0]->annual_salary, $employeePayrollData[0]->monthly_basic_salary);

                        $componentData = $employeeSalaryComponents;

                        foreach ($employeeSalaryComponents as $component) {

                            if ($component['type'] == 'earning') {
                                $totalIncome += $component['monthly'];
                            }

                            if ($component['type'] == 'deductions') {
                                $total_deduction += $component['monthly'];
                            }
                        }

                        $monthSalary += $totalIncome;

                        $monthSalary -= $total_deduction;

                    }

                    /** additional Salary Components */
                    $additionalComponents = $this->salaryComponentService->getGeneralSalaryComponents();


                    if(count($additionalComponents) > 0){
                        $additionalSalaryComponents = $this->calculateSalaryComponent($additionalComponents, $employeePayrollData[0]->annual_salary, $employeePayrollData[0]->monthly_basic_salary);

                        $additionalDeduction = 0;
                        foreach ($additionalSalaryComponents as $component) {

                            if ($component['type'] == 'deductions') {
                                $additionalDeduction += $component['monthly'];
                            }
                        }

                        $monthSalary -= $additionalDeduction;
                    }




                    /** Calculate taxes without the bonus */
                    $taxableIncome = ($monthSalary * $workedMonths);
                    $taxes = PayrollHelper::salaryTDSCalculator($employeePayrollData[0]->marital_status, $taxableIncome);

                    $monthlyTax = ($ssfDeduction > 0 && $taxes['total_tax'] > $taxes['sst']) ? ($taxes['total_tax'] - $taxes['sst'])/$workedMonths : 0;

                    /** Bonus Calculation */
                    $bonus = $this->bonusCalculator($month, $employeePayrollData[0]->monthly_basic_salary, $annualSalary, $employeePayrollData[0]->marital_status);


                    $bonusTax = 0;
                    if (count($bonus) > 0) {
                        $bonusData[$month] = $bonus;
                        $bonusTax = $bonus['tax'];
                    }

                    // tax data
                    $taxData[$month] = $monthlyTax + $bonusTax;

                        // Sum up the monthly values
                    $totalBasicSalary += $employeePayrollData[0]->monthly_basic_salary;
                    $totalAllowance += $employeePayrollData[0]->monthly_fixed_allowance;
                    $totalSSFContribution += $ssfContribution;
                    $totalSSFDeduction += $ssfDeduction;
                    $totalPayableTDS += ($monthlyTax+$bonusTax);

                }
            }


            /** store tax report data */
            $reportData = [
                'employee_id' => $employeeId,
                'fiscal_year_id' => $fiscalYearId,
                'total_basic_salary' => $totalBasicSalary,
                'total_allowance' => $totalAllowance,
                'total_ssf_contribution' => $totalSSFContribution,
                'total_ssf_deduction' => $totalSSFDeduction,
                'total_payable_tds' => $totalPayableTDS,
                'months' => json_encode($monthData),
            ];


            $taxReport = $this->reportRepository->create($reportData);

            if ($taxReport) {
                //store component details

                if(count($componentData)){
                    $this->componentDetailService->store($taxReport->id, $componentData);
                }
                if(count($additionalSalaryComponents)){
                    $this->additionalDetailService->store($taxReport->id, $additionalSalaryComponents);

                }
                if(count($bonusData)){
                    $this->bonusDetailService->store($taxReport->id, $bonusData);
                }
                $this->tdsDetailService->store($taxReport->id, $taxData);
            }

        }

        return [
            'id' => $taxReport->id ?? '',
            'name' => $employeePayrollData[0]->employee_name ?? '',
            'year' => $fiscalYearData->year ?? '',
            'total_payable_tds' => $totalPayableTDS ?? 0,
        ];


    }


    /**
     * @param $month
     * @param $monthlyBasicSalary
     * @param $annualSalary
     * @param $maritalStatus
     * @return array
     * @throws Exception
     */
    public function bonusCalculator($month, $monthlyBasicSalary, $annualSalary, $maritalStatus): array
    {
        $bonusAmount = 0;
        $bonus = $this->bonusService->findBonusByMonth($month);

        if(isset($bonus)){

            if ($bonus->value_type == BonusTypeEnum::fixed->value) {
                $bonusAmount = $bonus->value;
            } else if ($bonus->value_type ==  BonusTypeEnum::annual_percent->value) {
                $bonusAmount = ($bonus->value / 100) * $annualSalary;
            } else if ($bonus->value_type ==  BonusTypeEnum::basic_percent->value) {
                $bonusAmount = ($bonus->value / 100) * $monthlyBasicSalary;
            }
            /** Calculate tax for the bonus */
            $bonusTaxableIncome = $bonusAmount * 12; // Tax as if the bonus is annual
            $bonusTaxes = PayrollHelper::salaryTDSCalculator($maritalStatus, $bonusTaxableIncome);

            return [
                'id'=>$bonus->id,
                'title'=>$bonus->title,
                'month'=>$bonus->applicable_month,
                'amount'=>$bonusAmount,
                'tax'=> $bonusTaxes['monthly_tax'],
            ];

        }
        return [];
    }

    public function updateTaxReport($taxReportId, $validatedData){
        $taxReportData = $this->findTaxReportById($taxReportId);

        $this->reportRepository->update($taxReportData, $validatedData);
    }
}
