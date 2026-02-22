<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BackgroundCheckPackage;
use Illuminate\Database\Seeder;

class BackgroundCheckPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            // Checkr Packages
            [
                'name' => 'Basic Background Check',
                'description' => 'Essential background screening for entry-level positions. Includes SSN trace, national criminal search, and sex offender registry.',
                'provider' => 'checkr',
                'provider_package_id' => 'tasker_standard',
                'checks_included' => ['ssn_trace', 'criminal', 'sex_offender'],
                'price' => 29.99,
                'estimated_days' => 2,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Standard Employment Check',
                'description' => 'Comprehensive check for professional positions. Includes criminal, employment, and education verification.',
                'provider' => 'checkr',
                'provider_package_id' => 'driver_standard',
                'checks_included' => ['ssn_trace', 'criminal', 'sex_offender', 'employment', 'education', 'identity'],
                'price' => 79.99,
                'estimated_days' => 5,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Driver Screening',
                'description' => 'Complete driver background check with motor vehicle report and criminal history.',
                'provider' => 'checkr',
                'provider_package_id' => 'driver_pro',
                'checks_included' => ['ssn_trace', 'criminal', 'mvr', 'drug_test', 'identity'],
                'price' => 49.99,
                'estimated_days' => 3,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Executive Screening',
                'description' => 'Premium background check for executive and senior positions. Full verification suite with credit and global watchlist.',
                'provider' => 'checkr',
                'provider_package_id' => 'executive',
                'checks_included' => ['ssn_trace', 'criminal', 'employment', 'education', 'credit', 'professional_license', 'reference', 'global_watchlist', 'identity'],
                'price' => 199.99,
                'estimated_days' => 7,
                'is_active' => true,
                'is_default' => false,
            ],
            
            // Sterling Packages
            [
                'name' => 'Sterling Basic',
                'description' => 'Sterling basic background check with criminal and identity verification.',
                'provider' => 'sterling',
                'provider_package_id' => 'BASIC_PKG',
                'checks_included' => ['ssn_trace', 'criminal', 'identity'],
                'price' => 34.99,
                'estimated_days' => 2,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Sterling Professional',
                'description' => 'Professional-grade screening including employment and education verification.',
                'provider' => 'sterling',
                'provider_package_id' => 'PRO_PKG',
                'checks_included' => ['ssn_trace', 'criminal', 'employment', 'education', 'reference', 'identity'],
                'price' => 89.99,
                'estimated_days' => 5,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Sterling Healthcare',
                'description' => 'Healthcare-specific screening with license verification and sanction checks.',
                'provider' => 'sterling',
                'provider_package_id' => 'HEALTHCARE_PKG',
                'checks_included' => ['ssn_trace', 'criminal', 'education', 'professional_license', 'drug_test', 'global_watchlist', 'identity'],
                'price' => 149.99,
                'estimated_days' => 5,
                'is_active' => true,
                'is_default' => false,
            ],
            
            // GoodHire Packages
            [
                'name' => 'GoodHire Express',
                'description' => 'Fast turnaround basic background check for quick hiring needs.',
                'provider' => 'goodhire',
                'provider_package_id' => 'express',
                'checks_included' => ['ssn_trace', 'criminal', 'sex_offender'],
                'price' => 24.99,
                'estimated_days' => 1,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'GoodHire Standard',
                'description' => 'Standard employment screening with criminal and employment verification.',
                'provider' => 'goodhire',
                'provider_package_id' => 'standard',
                'checks_included' => ['ssn_trace', 'criminal', 'employment', 'identity'],
                'price' => 59.99,
                'estimated_days' => 3,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'GoodHire Complete',
                'description' => 'Comprehensive screening with all major verification types.',
                'provider' => 'goodhire',
                'provider_package_id' => 'complete',
                'checks_included' => ['ssn_trace', 'criminal', 'employment', 'education', 'mvr', 'reference', 'identity'],
                'price' => 129.99,
                'estimated_days' => 5,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($packages as $package) {
            BackgroundCheckPackage::updateOrCreate(
                ['name' => $package['name'], 'provider' => $package['provider']],
                $package
            );
        }

        $this->command->info('Background check packages seeded successfully!');
    }
}
