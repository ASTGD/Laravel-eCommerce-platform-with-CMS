<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Str;

class BangladeshDistrictService
{
    /**
     * Canonical Bangladesh district names for checkout selection.
     */
    protected const DISTRICTS = [
        'Bagerhat',
        'Bandarban',
        'Barguna',
        'Barishal',
        'Bhola',
        'Bogura',
        'Brahmanbaria',
        'Chandpur',
        'Chattogram',
        'Chuadanga',
        'Cumilla',
        'Cox\'s Bazar',
        'Dhaka',
        'Dinajpur',
        'Faridpur',
        'Feni',
        'Gaibandha',
        'Gazipur',
        'Gopalganj',
        'Habiganj',
        'Jamalpur',
        'Jashore',
        'Jhalokathi',
        'Jhenaidah',
        'Joypurhat',
        'Khagrachhari',
        'Khulna',
        'Kishoreganj',
        'Kurigram',
        'Kushtia',
        'Lakshmipur',
        'Lalmonirhat',
        'Madaripur',
        'Magura',
        'Manikganj',
        'Meherpur',
        'Moulvibazar',
        'Munshiganj',
        'Mymensingh',
        'Naogaon',
        'Narail',
        'Narayanganj',
        'Narsingdi',
        'Natore',
        'Netrokona',
        'Nilphamari',
        'Noakhali',
        'Pabna',
        'Panchagarh',
        'Patuakhali',
        'Pirojpur',
        'Rajbari',
        'Rajshahi',
        'Rangamati',
        'Rangpur',
        'Satkhira',
        'Shariatpur',
        'Sherpur',
        'Sirajganj',
        'Sunamganj',
        'Sylhet',
        'Tangail',
        'Thakurgaon',
    ];

    public function defaultCountryCode(): string
    {
        return strtoupper(
            core()->getConfigData('sales.shipping.origin.country')
                ?: config('app.default_country')
                ?: 'BD'
        );
    }

    public function districts(): array
    {
        return self::DISTRICTS;
    }

    public function districtOptions(): array
    {
        return collect($this->districts())
            ->map(fn (string $district) => [
                'code' => Str::slug($district),
                'name' => $district,
            ])
            ->all();
    }

    public function defaultRate(): float
    {
        return (float) (
            core()->getConfigData('sales.carriers.courier.default_rate')
                ?: core()->getConfigData('sales.carriers.courier.outside_dhaka_rate')
                ?: 120
        );
    }

    public function configuredRates(): array
    {
        $rawValue = (string) core()->getConfigData('sales.carriers.courier.district_rates');

        if (blank($rawValue)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $rawValue) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->reduce(function (array $rates, string $line) {
                [$district, $rate] = array_pad(preg_split('/\s*[:=|-]\s*/', $line, 2) ?: [], 2, null);

                if (blank($district) || ! is_numeric($rate)) {
                    return $rates;
                }

                $rates[$this->normalizeDistrictKey($district)] = (float) $rate;

                return $rates;
            }, []);
    }

    public function resolveDistrictName(?string $district): string
    {
        $candidate = trim((string) $district);

        if ($candidate === '') {
            return '';
        }

        return collect($this->districts())
            ->first(fn (string $knownDistrict) => $this->normalizeDistrictKey($knownDistrict) === $this->normalizeDistrictKey($candidate))
            ?: $candidate;
    }

    public function resolveRate(?string $district): float
    {
        $districtName = $this->resolveDistrictName($district);
        $rates = $this->configuredRates();
        $districtKey = $this->normalizeDistrictKey($districtName);

        if (isset($rates[$districtKey])) {
            return $rates[$districtKey];
        }

        $legacyDhakaDistrict = $this->normalizeDistrictKey(
            (string) (core()->getConfigData('sales.carriers.courier.dhaka_district') ?: 'Dhaka')
        );

        if ($districtKey !== '' && $districtKey === $legacyDhakaDistrict) {
            return (float) (core()->getConfigData('sales.carriers.courier.dhaka_rate') ?: 60);
        }

        return $this->defaultRate();
    }

    public function resolveTitle(?string $district): string
    {
        $districtName = $this->resolveDistrictName($district);
        $districtKey = $this->normalizeDistrictKey($districtName);
        $legacyDhakaDistrict = $this->normalizeDistrictKey(
            (string) (core()->getConfigData('sales.carriers.courier.dhaka_district') ?: 'Dhaka')
        );

        if ($districtKey !== '' && $districtKey === $legacyDhakaDistrict) {
            return (string) (core()->getConfigData('sales.carriers.courier.dhaka_title') ?: 'Dhaka Delivery');
        }

        return $districtName !== ''
            ? sprintf('%s Delivery', $districtName)
            : 'Delivery';
    }

    public function resolveDescription(?string $district): string
    {
        $configuredDescription = (string) core()->getConfigData('sales.carriers.courier.description');

        if ($configuredDescription !== '') {
            return $configuredDescription;
        }

        $districtName = $this->resolveDistrictName($district);

        return $districtName !== ''
            ? sprintf('Delivery charge for %s district', $districtName)
            : 'District-based delivery charges';
    }

    protected function normalizeDistrictKey(?string $district): string
    {
        return Str::of((string) $district)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
