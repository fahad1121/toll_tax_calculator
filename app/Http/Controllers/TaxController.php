<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class TaxController extends Controller
{
    public function saveVehicleInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'interchange' => ['required', 'unique:taxes'],
            'number_plat' => ['required', 'unique:taxes'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->messages()]);
        }
        $toll = new Tax();
        $toll->interchange = $request->interchange;
        $toll->number_plat = $request->number_plat;
        $toll->base_price = 20;
        $toll->save();
        if ($toll->id) {
            return response()->json(['success' => true, 'message' => 'Toll has been added']);
        }
        return response()->json(['success' => false, 'message' => 'Toll could not be add']);
    }

    public function calculateTax(Request $request)
    {

        $vehicleInfo = Tax::where("number_plat", $request->number_plat)->first();
        $data = $this->getActualTax($vehicleInfo,$request);
        if(!empty($data)){
            return response()->json([
                'success' => true,
                'result' => $data
            ]);
        }
        return response()->json(['success' => false, 'message' => 'no toll found']);
    }

    private function getActualTax($vehicleInfo, $request)
    {
        $result = [];
        $numberOnlyFromNumberPlate = (int)filter_var($request->number_plat, FILTER_SANITIZE_NUMBER_INT);
        $exitPointsForInterchanges = [
            "ns" => 5,
            "ph4" => 10,
            "ferozpur" => 17,
            "lakecity" => 24,
            "raiwand" => 29,
            "bahria" => 34
        ];
        if (array_key_exists($request->interchange, $exitPointsForInterchanges)) {
            $basicPriceOnExitWithFiftyPercentDiscount = $basicPriceOnExitWithExtraOnWeekends = $basicPriceOnExitWithTenPercentDiscount = 0;
            $basicPriceOnExit = round($exitPointsForInterchanges[$request->interchange] * 0.2); // per km charging 0.2 here
            $currentDay = lcfirst(date("D"));

            if (date("d-M") == '23-Mar' || date("d-M") == '14-Aug' || date("d-M") == '25-Dec') {
                $discount = $basicPriceOnExit * 50 / 100; // 50% discount
                $basicPriceOnExitWithFiftyPercentDiscount = $basicPriceOnExit - $discount;
            } else {
                if ($currentDay == 'sun' || $currentDay == 'sat') {
                    $basicPriceOnExitWithExtraOnWeekends = $basicPriceOnExit * 1.5;
                } else if (($currentDay == 'mon' || $currentDay == 'wed') && $numberOnlyFromNumberPlate % 2 == 0) {
                    $discount = $basicPriceOnExit * 10 / 100; // 10% discount
                    $basicPriceOnExitWithTenPercentDiscount = $basicPriceOnExit - $discount;
                } else if (($currentDay == 'tue' || $currentDay == 'thur') && $numberOnlyFromNumberPlate % 2 != 0) {
                    $discount = $basicPriceOnExit * 10 / 100; // 10% discount
                    $basicPriceOnExitWithTenPercentDiscount = $basicPriceOnExit - $discount;
                }
                $allDiscountsOrOthers = $basicPriceOnExitWithFiftyPercentDiscount + $basicPriceOnExitWithFiftyPercentDiscount + $basicPriceOnExitWithExtraOnWeekends + $basicPriceOnExitWithTenPercentDiscount;
                $actualTollCollected = $basicPriceOnExit + $allDiscountsOrOthers;

                $result = [
                    'base_rate' => $vehicleInfo->base_price,
                    'distance_cost' => $basicPriceOnExit,
                    'breakdown' => [
                        'entry' => $vehicleInfo->interchange,
                        'exit' => $request->interchange,
                        'total_distance' => $exitPointsForInterchanges[$request->interchange] . " KM",
                        'per_km_charge' => 0.2
                    ],
                    'sub_total' => $basicPriceOnExit,
                    'discounts/others' => $allDiscountsOrOthers,
                    'actual_toll' => $actualTollCollected
                ];
            }
        }
        return $result;
    }
}
