<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $employer = Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
        $jobSeeker = Role::firstOrCreate(['name' => 'job_seeker', 'guard_name' => 'web']);
        $freelancer = Role::firstOrCreate(['name' => 'freelancer', 'guard_name' => 'web']);
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            // Job permissions
            'view jobs',
            'create jobs',
            'edit jobs',
            'delete jobs',
            'apply jobs',
            
            // Application permissions
            'view applications',
            'manage applications',
            
            // Company permissions
            'view companies',
            'create companies',
            'edit companies',
            'delete companies',
            
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // AI Features
            'use ai features',
            'use autonomous agent',
            'use negotiation tools',
            'use skill analyzer',
            'use resume builder',
            
            // Admin features
            'access admin panel',
            'manage settings',
            'view analytics',
            'manage subscriptions',
            
            // Marketplace
            'access marketplace',
            'create gigs',
            'hire freelancers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $admin->syncPermissions(Permission::all());
        $superAdmin->syncPermissions(Permission::all());
        
        $employer->syncPermissions([
            'view jobs', 'create jobs', 'edit jobs', 'delete jobs',
            'view applications', 'manage applications',
            'view companies', 'create companies', 'edit companies',
            'use ai features', 'view analytics',
            'access marketplace', 'hire freelancers',
        ]);
        
        $jobSeeker->syncPermissions([
            'view jobs', 'apply jobs',
            'view applications',
            'view companies',
            'use ai features', 'use autonomous agent', 'use negotiation tools',
            'use skill analyzer', 'use resume builder',
            'access marketplace', 'create gigs',
        ]);
        
        $freelancer->syncPermissions([
            'view jobs',
            'view companies',
            'use ai features',
            'access marketplace', 'create gigs',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
