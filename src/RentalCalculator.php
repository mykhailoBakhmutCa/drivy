<?php
declare(strict_types=1);

namespace Drivy;

class RentalCalculator
{
    public function calculatePrices(array $data): array
    {
        $carMap = [];
        foreach ($data['cars'] as $car) {
            $carMap[$car['id']] = $car;
        }

        $rentalMap = [];
        foreach ($data['rentals'] as $rental) {
            $rentalMap[$rental['id']] = $rental;
        }

        $outputModifications = [];

        foreach ($data['rental_modifications'] as $modification) {

            $originalRental = $rentalMap[$modification['rental_id']];
            $car = $carMap[$originalRental['car_id']];

            $amountBefore = $this->getRentalAmounts($originalRental, $car);

            $modifiedRental = array_merge($originalRental, $modification);

            $amountAfter = $this->getRentalAmounts($modifiedRental, $car);

            $actions = [];

            $actors = ['driver', 'owner', 'insurance', 'assistance', 'drivy'];

            foreach ($actors as $actor) {
                $delta = $amountAfter[$actor] - $amountBefore[$actor];

                if ($delta === 0) {
                    continue;
                }

                if ($actor === 'driver') {
                    // debit - driver pay more
                    $type = ($delta > 0) ? 'debit' : 'credit';
                } else {
                    // credit - company receive more
                    $type = ($delta > 0) ? 'credit' : 'debit';
                }

                $actions[] = [
                    'who' => $actor,
                    'type' => $type,
                    'amount' => abs($delta),
                ];
            }

            $outputModifications[] = [
                'id' => $modification['id'],
                'rental_id' => $modification['rental_id'],
                'actions' => $actions,
            ];
        }


        return ['rentals' => $outputModifications];
    }

    private function calculateTimePrice(int $days, int $pricePerDay): int
    {
        $totalPrice = 0;

        for ($i = 1; $i <= $days; $i++) {
            $dailyRate = match (true) {
                $i == 1 => 1,
                $i <= 4 => 0.9,
                $i <= 10 => 0.7,
                default => 0.5,
            };

            $totalPrice += $dailyRate * $pricePerDay;
        }

        return (int)$totalPrice;
    }

    private function calculateCommission(int $totalPrice, int $days): array
    {
        $totalCommission = (int)($totalPrice * 0.3);

        $insuranceFee = (int)($totalCommission * 0.5);

        $assistanceFee = (int)($days * 100);

        $drivyFee = $totalCommission - $assistanceFee - $insuranceFee;

        return [
            'total_commission' => $totalCommission,
            'insurance_fee' => $insuranceFee,
            'assistance_fee' => $assistanceFee,
            'drivy_fee' => $drivyFee,
        ];
    }

    private function getRentalAmounts(array $rental, array $car): array
    {
        $startDate = new \DateTime($rental['start_date']);
        $endDate = new \DateTime($rental['end_date']);

        $days = $startDate->diff($endDate)->days + 1;

        $timePrice = $this->calculateTimePrice($days, $car['price_per_day']);
        $distancePrice = $rental['distance'] * $car['price_per_km'];

        $totalPrice = $timePrice + $distancePrice;

        $commissionData = $this->calculateCommission($totalPrice, $days);

        [
            'total_commission' => $totalCommission,
            'insurance_fee' => $insuranceFee,
            'assistance_fee' => $assistanceFee,
            'drivy_fee' => $drivyFee,
        ] = $commissionData;

        $deductibleReductionFee = 0;
        if ($rental['deductible_reduction']) {
            $deductibleReductionFee = $days * 400;
        }

        $driverAmount = $totalPrice + $deductibleReductionFee;

        $ownerAmount = $totalPrice - $totalCommission;

        $insuranceAmount = $insuranceFee;

        $assistanceAmount = $assistanceFee;

        $drivyAmount = $drivyFee + $deductibleReductionFee;

        return [
            'driver' => $driverAmount,
            'owner' => $ownerAmount,
            'insurance' => $insuranceAmount,
            'assistance' => $assistanceAmount,
            'drivy' => $drivyAmount,
        ];
    }
}