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

        $outputRentals = [];

        foreach ($data['rentals'] as $rental) {

            $car = $carMap[$rental['car_id']];

            $startDate = new \DateTime($rental['start_date']);
            $endDate = new \DateTime($rental['end_date']);

            $days = $startDate->diff($endDate)->days + 1;

            $timePrice = $this->calculateTimePrice($days, $car['price_per_day']);
            $distancePrice = $rental['distance'] * $car['price_per_km'];

            $totalPrice = $timePrice + $distancePrice;

            $commissionData = $this->calculateCommission($totalPrice, $days);

            $outputRentals[] = [
                'id' => $rental['id'],
                'price' => $totalPrice,
                'commission' => $commissionData,
            ];
        }

        return ['rentals' => $outputRentals];
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
            'insurance_fee' => $insuranceFee,
            'assistance_fee' => $assistanceFee,
            'drivy_fee' => $drivyFee,
        ];
    }
}